<?php
namespace Mapping\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class MappingAdapter extends AbstractMappingAdapter
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

    public function hydrate(Request $request, EntityInterface $entity, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        if (Request::CREATE === $request->getOperation() && isset($data['o:item']['o:id'])) {
            $item = $this->getAdapter('items')->findEntity($data['o:item']['o:id']);
            $entity->setItem($item);
        }
        parent::hydrate($request, $entity, $errorStore);
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        if (!$entity->getItem()) {
            $errorStore->addError('o:item', 'A mapping zone must have an item.'); // @translate
        }
        parent::validateEntity($entity, $errorStore);
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
    }
}
