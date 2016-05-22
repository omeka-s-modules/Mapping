<?php
namespace Mapping\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class MappingMarkerAdapter extends AbstractEntityAdapter
{
    public function getResourceName()
    {
        return 'mapping_markers';
    }

    public function getRepresentationClass()
    {
        return 'Mapping\Api\Representation\MappingMarkerRepresentation';
    }

    public function getEntityClass()
    {
        return 'Mapping\Entity\MappingMarker';
    }

    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        $data = $request->getContent();
        if (Request::CREATE === $request->getOperation()
            && isset($data['o:item']['o:id'])
        ) {
            $item = $this->getAdapter('items')->findEntity($data['o:item']['o:id']);
            $entity->setItem($item);
        }
        if ($this->shouldHydrate($request, 'o:media')
            && isset($data['o:media']['o:id'])
            && is_numeric($data['o:media']['o:id'])
        ) {
            $media = $this->getAdapter('media')->findEntity($data['o:media']['o:id']);
            $entity->setMedia($media);
        } else {
            $entity->setMedia(null);
        }
        if ($this->shouldHydrate($request, 'o-module-mapping:lat')) {
            $entity->setLat($request->getValue('o-module-mapping:lat'));
        }
        if ($this->shouldHydrate($request, 'o-module-mapping:lng')) {
            $entity->setLng($request->getValue('o-module-mapping:lng'));
        }
        if ($this->shouldHydrate($request, 'o-module-mapping:label')) {
            $entity->setLabel($request->getValue('o-module-mapping:label'));
        }
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        if (!$entity->getItem()) {
            $errorStore->addError('o:item', 'A marker must have an item.');
        }
        if (!is_numeric($entity->getLat())) {
            $errorStore->addError('o-module-mapping:lat', 'A marker must have a numeric latitude.');
        }
        if (!is_numeric($entity->getLng())) {
            $errorStore->addError('o-module-mapping:lng', 'A marker must have a numeric longitude.');
        }
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['item_id'])) {
            $items = $query['item_id'];
            if (!is_array($items)) {
                $items = [$items];
            }
            $items = array_filter($items, 'is_numeric');

            $itemAlias = $this->createAlias();
            $qb->innerJoin(
                $this->getEntityClass() . '.item', $itemAlias,
                'WITH', $qb->expr()->in("$itemAlias.id", $this->createNamedParameter($qb, $items))
            );
        }
        if (isset($query['media_id'])) {
            $media = $query['media_id'];
            if (!is_array($media)) {
                $media = [$media];
            }
            $media = array_filter($media, 'is_numeric');

            $mediaAlias = $this->createAlias();
            $qb->innerJoin(
                $this->getEntityClass() . '.media', $mediaAlias,
                'WITH', $qb->expr()->in("$mediaAlias.id", $this->createNamedParameter($qb, $media))
            );
        }
        if (isset($query['address']) && '' !== trim($query['address'])
            && isset($query['radius']) && is_numeric($query['radius'])
        ) {
            // If no address is found below, these settings result in no markers.
            $centerLat = 0;
            $centerLng = 0;
            $radius = -1;

            // Get the address' latitude and longitude from OpenStreetMap.
            $client = $this->getServiceLocator()->get('Omeka\HttpClient')
                ->setUri('http://nominatim.openstreetmap.org/search')
                ->setParameterGet([
                    'q'  => $query['address'],
                    'format'  => 'json',
                ]);
            $response = $client->send();
            if ($response->isSuccess()) {
                $results = json_decode($response->getBody(), true);
                if (isset($results[0]['lat']) && isset($results[0]['lon'])) {
                    $centerLat = $results[0]['lat'];
                    $centerLng = $results[0]['lon'];
                    $radius = $query['radius'];
                }
            }

            // Calculate the distance of markers from center coordinates.
            // @see http://stackoverflow.com/questions/8994718/mysql-longitude-and-latitude-query-for-other-rows-within-x-mile-radius
            $dql = '
            (6371 * acos(
                (
                    cos(radians(%1$s)) *
                    cos(radians(Mapping\Entity\MappingMarker.lat)) *
                    cos(
                        (radians(Mapping\Entity\MappingMarker.lng) - radians(%2$s))
                    ) +
                    sin(radians(%1$s)) *
                    sin(radians(Mapping\Entity\MappingMarker.lat))
                )
            )) <= %3$s';
            $qb->andWhere(sprintf(
                $dql,
                $this->createNamedParameter($qb, $centerLat),
                $this->createNamedParameter($qb, $centerLng),
                $this->createNamedParameter($qb, $radius)
            ));
        }
    }
}
