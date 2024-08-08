<?php
namespace Mapping\Site\BlockLayout;

use Doctrine\DBAL\Connection;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Form\Element;
use Omeka\Stdlib\ErrorStore;
use Laminas\View\Renderer\PhpRenderer;

class MapItemSets extends AbstractMap
{
    public function getLabel()
    {
        return 'Map by item sets'; // @translate
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {
        $block->setData($this->filterBlockData($block->getData()));
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $data = $this->filterBlockData($block->data());
        $conn = $this->connection;
        $itemSetIds = array_map('intval', $data['item_sets']);

        $sql = 'SELECT iis.item_set_id,
                ST_AsGeoJSON(ST_Centroid(ST_ConvexHull(ST_Collect(geography)))) AS centroid
            FROM mapping_feature mf
            INNER JOIN item i ON mf.item_id = i.id
            INNER JOIN item_item_set iis ON i.id = iis.item_id
            WHERE iis.item_set_id IN (?)
            GROUP BY iis.item_set_id';
        $results = $conn->executeQuery($sql, [$itemSetIds], [Connection::PARAM_INT_ARRAY])->fetchAll();
        $itemSets = [];
        foreach ($results as $result) {
            $itemSet = $view->api()->read('item_sets', $result['item_set_id'])->getContent();
            $itemSets[] = [
                'item_set' => $itemSet,
                'geography' => $result['centroid'],
            ];
        }
        return $view->partial('common/block-layout/mapping-block', [
            'data' => $data,
            'itemSets' => $itemSets,
            'isTimeline' => false,
        ]);
    }
}
