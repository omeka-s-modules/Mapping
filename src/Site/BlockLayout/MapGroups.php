<?php
namespace Mapping\Site\BlockLayout;

use Doctrine\DBAL\Connection;
use Laminas\View\Renderer\PhpRenderer;
use Mapping\Form\BlockLayoutMapGroupsForm;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Form\Element;
use Omeka\Stdlib\ErrorStore;

class MapGroups extends AbstractMap
{
    public function getLabel()
    {
        return 'Map by groups'; // @translate
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {
        $form = $this->formElementManager->get(BlockLayoutMapGroupsForm::class);
        $data = $form->prepareBlockData($block->getData());
        $block->setData($data);
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null
    ) {
        $form = $this->formElementManager->get(BlockLayoutMapGroupsForm::class);
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
        $formHtml[] = $view->partial('common/block-layout/mapping-block-form/groups', [
            'data' => $data,
            'form' => $form,
        ]);
        return implode('', $formHtml);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $form = $this->formElementManager->get(BlockLayoutMapGroupsForm::class);
        $data = $form->prepareBlockData($block->data());
        $dataItems = $data;
        $dataItems['bounds'] = null; // Do not use the configured bounds for the items map.

        // Get group data according to type.
        switch ($data['groups']['type']) {
            case 'item_sets':
                $popupPartial = 'common/mapping-item-set-popup';
                $itemSetIds = array_map('intval', $data['groups']['type_data']['item_set_ids']);
                switch ($data['groups']['feature_type']) {
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
                $results = $this->connection->executeQuery($sql, [$itemSetIds], [Connection::PARAM_INT_ARRAY])->fetchAll();
                foreach ($results as $result) {
                    $itemSet = $view->api()->read('item_sets', $result['item_set_id'])->getContent();
                    $groups[] = [
                        'group' => $itemSet,
                        'geography' => $result['geography'],
                    ];
                }
                break;
            default:
                $groups = [];
                $popupPartial = null;
        }

        return $view->partial('common/block-layout/mapping-block-groups', [
            'data' => $data,
            'dataItems' => $dataItems,
            'groups' => $groups,
            'popupPartial' => $popupPartial,
        ]);
    }
}
