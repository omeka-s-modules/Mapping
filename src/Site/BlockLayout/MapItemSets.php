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

        switch ($data['item_set_feature_type']) {
            case 'point':
                $geographySelect = 'ST_AsGeoJSON(ST_Centroid(ST_ConvexHull(ST_Collect(geography)))) AS geography';
                break;
            case 'polygon':
            default:
                $geographySelect = 'ST_AsGeoJSON(ST_ConvexHull(ST_Collect(geography))) AS geography';
        }

        $sql = sprintf('SELECT iis.item_set_id, %s
            FROM mapping_feature mf
            INNER JOIN item i ON mf.item_id = i.id
            INNER JOIN item_item_set iis ON i.id = iis.item_id
            WHERE iis.item_set_id IN (?)
            GROUP BY iis.item_set_id', $geographySelect);
        $results = $conn->executeQuery($sql, [$itemSetIds], [Connection::PARAM_INT_ARRAY])->fetchAll();
        $features = [];
        foreach ($results as $result) {
            $itemSet = $view->api()->read('item_sets', $result['item_set_id'])->getContent();
            $features[] = [
                'item_set' => $itemSet,
                'geography' => $result['geography'],
            ];
        }
        return $view->partial('common/block-layout/mapping-block', [
            'data' => $data,
            'features' => $features,
            'isTimeline' => false,
        ]);
    }
}
