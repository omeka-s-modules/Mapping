<?php
namespace Mapping\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class MappingAdapter extends AbstractEntityAdapter
{
    public function getResourceName()
    {
        return 'mappings';
    }

    public function getRepresentationClass()
    {
        return 'Mapping\Api\Representation\MappingRepresentation';
    }

    public function getEntityClass()
    {
        return 'Mapping\Entity\Mapping';
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
        if ($this->shouldHydrate($request, 'o-module-mapping:default_zoom')) {
            $entity->setDefaultZoom($request->getValue('o-module-mapping:default_zoom'));
        }
        if ($this->shouldHydrate($request, 'o-module-mapping:default_lat')) {
            $entity->setDefaultLat($request->getValue('o-module-mapping:default_lat'));
        }
        if ($this->shouldHydrate($request, 'o-module-mapping:default_lng')) {
            $entity->setDefaultLng($request->getValue('o-module-mapping:default_lng'));
        }
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        if (!$entity->getItem()) {
            $errorStore->addError('o:item', 'A marker must have an item.');
        }
        $zoom = $entity->getDefaultZoom();
        if (null !== $zoom && !is_numeric($zoom)) {
            $errorStore->addError('o-module-mapping:default_zoom', 'The default zoom must be numeric.');
        }
        $lat = $entity->getDefaultLat();
        if (null !== $lat && !is_numeric($lat)) {
            $errorStore->addError('o-module-mapping:default_lat', 'The default latitude must be numeric.');
        }
        $lng = $entity->getDefaultLng();
        if (null !== $lng && !is_numeric($lng)) {
            $errorStore->addError('o-module-mapping:default_lng', 'The default longitude must be numeric.');
        }
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['item_id'])) {
            $itemAlias = $this->createAlias();
            $qb->innerJoin(
                'Mapping\Entity\Mapping.item',
                $itemAlias
            );
            $qb->andWhere($qb->expr()->eq(
                "$itemAlias.id",
                $this->createNamedParameter($qb, $query['item_id']))
            );
        }
    }
}
