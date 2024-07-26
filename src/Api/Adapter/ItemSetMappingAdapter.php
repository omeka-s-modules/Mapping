<?php
namespace Mapping\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class ItemSetMappingAdapter extends AbstractMappingAdapter
{
    public function getResourceName()
    {
        return 'item_set_mappings';
    }

    public function getRepresentationClass()
    {
        return 'Mapping\Api\Representation\ItemSetMappingRepresentation';
    }

    public function getEntityClass()
    {
        return 'Mapping\Entity\ItemSetMapping';
    }

    public function hydrate(Request $request, EntityInterface $entity, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        if (Request::CREATE === $request->getOperation() && isset($data['o:item_set']['o:id'])) {
            $itemSet = $this->getAdapter('items')->findEntity($data['o:item_set']['o:id']);
            $entity->setItemSet($itemSet);
        }
        parent::hydrate($request, $entity, $errorStore);
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        if (!$entity->getItemSet()) {
            $errorStore->addError('o:item', 'A mapping zone must have an item set.'); // @translate
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
                    'omeka_root.item', $itemSetAlias,
                    'WITH', $qb->expr()->in("$itemSetAlias.id", $this->createNamedParameter($qb, $itemSets))
                );
            }
        }
    }
}
