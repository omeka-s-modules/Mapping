<?php
namespace Mapping\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class MappingFeatureAdapter extends AbstractMappingFeatureAdapter
{
    public function getResourceName()
    {
        return 'mapping_features';
    }

    public function getRepresentationClass()
    {
        return 'Mapping\Api\Representation\MappingFeatureRepresentation';
    }

    public function getEntityClass()
    {
        return 'Mapping\Entity\MappingFeature';
    }

    public function hydrate(Request $request, EntityInterface $entity, ErrorStore $errorStore)
    {
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
        parent::hydrate($request, $entity, $errorStore);
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        if (!$entity->getItem()) {
            $errorStore->addError('o:item', 'A Mapping feature must have an item.');
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
        if (isset($query['item_set_id']) && is_numeric($query['item_set_id'])) {
            $itemAlias = $this->createAlias();
            $itemSetAlias = $this->createAlias();
            $qb->innerJoin('omeka_root.item', $itemAlias);
            $qb->innerJoin("$itemAlias.itemSets", $itemSetAlias);
            $qb->andWhere($qb->expr()->eq("$itemSetAlias.id", $this->createNamedParameter($qb, $query['item_set_id'])));
        }
        parent::buildQuery($qb, $query);
    }
}
