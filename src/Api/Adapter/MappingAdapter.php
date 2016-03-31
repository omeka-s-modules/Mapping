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
        if ($this->shouldHydrate($request, 'o-module-mapping:wms_base_url')) {
            $entity->setWmsBaseUrl($request->getValue('o-module-mapping:wms_base_url'));
        }
        if ($this->shouldHydrate($request, 'o-module-mapping:wms_layers')) {
            $entity->setWmsLayers($request->getValue('o-module-mapping:wms_layers'));
        }
        if ($this->shouldHydrate($request, 'o-module-mapping:wms_styles')) {
            $entity->setWmsStyles($request->getValue('o-module-mapping:wms_styles'));
        }
        if ($this->shouldHydrate($request, 'o-module-mapping:wms_label')) {
            $entity->setWmsLabel($request->getValue('o-module-mapping:wms_label'));
        }
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        if (!$entity->getItem()) {
            $errorStore->addError('o:item', 'A marker must have an item.');
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
