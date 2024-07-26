<?php
namespace Mapping\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class ItemSetMappingFeatureAdapter extends AbstractMappingFeatureAdapter
{
    public function getResourceName()
    {
        return 'item_set_mapping_features';
    }

    public function getRepresentationClass()
    {
        return 'Mapping\Api\Representation\ItemSetMappingFeatureRepresentation';
    }

    public function getEntityClass()
    {
        return 'Mapping\Entity\ItemSetMappingFeature';
    }

    public function hydrate(Request $request, EntityInterface $entity, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        if (Request::CREATE === $request->getOperation()
            && isset($data['o:item_set']['o:id'])
        ) {
            $itemSet = $this->getAdapter('item_sets')->findEntity($data['o:item_set']['o:id']);
            $entity->setItemSet($itemSet);
        }
        parent::hydrate($request, $entity, $errorStore);
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        if (!$entity->getItemSet()) {
            $errorStore->addError('o:item_set', 'A Mapping feature must have an item set.');
        }
        parent::validateEntity($entity, $errorStore);
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['item_set_id'])) {
            $itemSets = $query['item_set_id'];
            if (!is_array($itemSets)) {
                $itemSets = [$itemSets];
            }
            $itemSets = array_filter($itemSets, 'is_numeric');

            if ($itemSets) {
                $itemSetAlias = $this->createAlias();
                $qb->innerJoin(
                    'omeka_root.item_set', $itemSetAlias,
                    'WITH', $qb->expr()->in("$itemSetAlias.id", $this->createNamedParameter($qb, $itemSets))
                );
            }
        }
        parent::buildQuery($qb, $query);
    }
}
