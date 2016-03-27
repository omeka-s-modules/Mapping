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
            $itemAlias = $this->createAlias();
            $qb->innerJoin(
                'Mapping\Entity\MappingMarker.item',
                $itemAlias
            );
            $qb->andWhere($qb->expr()->eq(
                "$itemAlias.id",
                $this->createNamedParameter($qb, $query['item_id']))
            );
        }
    }
}
