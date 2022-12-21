<?php
namespace Mapping\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Adapter\ItemAdapter;
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

            if ($items) {
                $itemAlias = $this->createAlias();
                $qb->innerJoin(
                    'omeka_root.item', $itemAlias,
                    'WITH', $qb->expr()->in("$itemAlias.id", $this->createNamedParameter($qb, $items))
                );
            }
        }
        if (isset($query['media_id'])) {
            $media = $query['media_id'];
            if (!is_array($media)) {
                $media = [$media];
            }
            $media = array_filter($media, 'is_numeric');

            if ($media) {
                $mediaAlias = $this->createAlias();
                $qb->innerJoin(
                    'omeka_root.media', $mediaAlias,
                    'WITH', $qb->expr()->in("$mediaAlias.id", $this->createNamedParameter($qb, $media))
                );
            }
        }
        $address = $query['address'] ?? null;
        $radius = $query['radius'] ?? null;
        $radiusUnit = $query['radius_unit'] ?? null;
        if (isset($address) && '' !== trim($address) && isset($radius) && is_numeric($radius)) {
            $this->buildGeographicLocationQuery($qb, $address, $radius, $radiusUnit);
        }
    }

    /**
     * Build a geographic location query.
     *
     * @param QueryBuilder $qb
     * @param string $address A geographic address
     * @param string $radius The radius within which to search
     * @param string $radiusUnit The radius unit, "km" or "mile"
     * @param ItemAdapter|null $itemAdapter The item adapter, if searching items
     * @return bool Whether an address was found
     */
    public function buildGeographicLocationQuery($qb, $address, $radius, $radiusUnit, ItemAdapter $itemAdapter = null)
    {
        // Get the address' latitude and longitude from OpenStreetMap.
        $client = $this->getServiceLocator()->get('Omeka\HttpClient')
            ->setUri('http://nominatim.openstreetmap.org/search')
            ->setParameterGet([
                'q' => $address,
                'format' => 'json',
            ]);
        $response = $client->send();

        $addressFound = false;
        if ($response->isSuccess()) {
            $results = json_decode($response->getBody(), true);
            if (isset($results[0]['lat']) && isset($results[0]['lon'])) {

                // Address coordinates were found.
                $addressFound = true;

                // The adapter and alias depend on whether an item adapter was
                // passed. If not, assume this is a direct marker search. If so,
                // assume this is an indirect item search.
                $adapter = $this;
                $mappingMarkerAlias = 'omeka_root';
                if ($itemAdapter) {
                    $adapter = $itemAdapter;
                    $mappingMarkerAlias = $itemAdapter->createAlias();
                    $qb->innerJoin(
                        'Mapping\Entity\MappingMarker', $mappingMarkerAlias,
                        'WITH', "$mappingMarkerAlias.item = omeka_root.id"
                    );
                }

                // Set the radius unit constant needed for the distance
                // calcluation below.
                $unit = $radiusUnit ?? 'km';
                switch ($unit) {
                    case 'mile':
                        $unitConst = 3959;
                        break;
                    case 'km':
                    default:
                        $unitConst = 6371;
                }

                // Calculate the distance of markers from center coordinates
                // using the Haversine formula.
                $dql = sprintf('
                    (%1$s * acos(
                        (
                            cos(radians(%2$s)) *
                            cos(radians(%5$s.lat)) *
                            cos(
                                (radians(%5$s.lng) - radians(%3$s))
                            ) +
                            sin(radians(%2$s)) *
                            sin(radians(%5$s.lat))
                        )
                    )) <= %4$s',
                    $unitConst,
                    $adapter->createNamedParameter($qb, $results[0]['lat']),
                    $adapter->createNamedParameter($qb, $results[0]['lon']),
                    $adapter->createNamedParameter($qb, $radius),
                    $mappingMarkerAlias
                );
                $qb->andWhere($dql);
            }
        }
        if (!$addressFound) {
            // If no address is found there are no results. This WHERE
            // statement will always have no results.
            $qb->andWhere(sprintf('%s.id = 0', $mappingMarkerAlias));
        }
        return $addressFound;
    }
}
