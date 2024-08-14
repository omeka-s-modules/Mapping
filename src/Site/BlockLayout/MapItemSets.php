<?php
namespace Mapping\Site\BlockLayout;

use Doctrine\DBAL\Connection;
use Laminas\View\Renderer\PhpRenderer;
use Mapping\Form\BlockLayoutMapItemSetsForm;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Form\Element;
use Omeka\Stdlib\ErrorStore;

class MapItemSets extends AbstractMap
{
    public function getLabel()
    {
        return 'Map by item sets'; // @translate
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {
        $form = $this->formElementManager->get(BlockLayoutMapItemSetsForm::class);
        $data = $form->prepareBlockData($block->getData());
        $block->setData($data);
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null
    ) {
        $form = $this->formElementManager->get(BlockLayoutMapItemSetsForm::class);
        $data = $form->prepareBlockData($block ? $block->data() : []);

        $formHtml = [];
        $formHtml[] = $view->partial('common/block-layout/mapping-block-form/default-view', [
            'data' => $data,
            'form' => $form,
        ]);
        $formHtml[] = $view->partial('common/block-layout/mapping-block-form/wms-overlays', [
            'data' => $data,
            'form' => $form,
        ]);
        $formHtml[] = $view->partial('common/block-layout/mapping-block-form/item-sets', [
            'data' => $data,
            'form' => $form,
        ]);
        return implode('', $formHtml);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $form = $this->formElementManager->get(BlockLayoutMapItemSetsForm::class);
        $data = $form->prepareBlockData($block->data());
        $dataItems = $data;
        $dataItems['bounds'] = null; // Do not use the configured bounds for the items map.

        $conn = $this->connection;
        $itemSetIds = array_map('intval', $data['item_sets']['ids']);

        switch ($data['item_sets']['feature_type']) {
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
        $itemSets = [];
        foreach ($results as $result) {
            $itemSet = $view->api()->read('item_sets', $result['item_set_id'])->getContent();
            $itemSets[] = [
                'item_set' => $itemSet,
                'geography' => $result['geography'],
            ];
        }

        return $view->partial('common/block-layout/mapping-block-item-sets', [
            'data' => $data,
            'dataItems' => $dataItems,
            'itemSets' => $itemSets,
        ]);
    }
}
