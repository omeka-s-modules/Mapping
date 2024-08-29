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

/**
 * The "Map by groups" block layout.
 */
class MapGroups extends AbstractMap
{
    protected $popupPartials = [
        'item_sets' => 'common/mapping-popup/item-set-group',
        'resource_classes' => 'common/mapping-popup/resource-class-group',
        'property_values_is_exactly' => 'common/mapping-popup/property-value-is-exactly-group',
        'property_values_contains' => 'common/mapping-popup/property-value-contains-group',
        'properties_has_any_value' => 'common/mapping-popup/property-has-any-value-group',
    ];

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
        $siteId = $block->page()->site()->id();

        // Get the GROUP BY clause depending on feature type.
        switch ($data['groups']['feature_type']) {
            case 'point':
                $geographySelect = 'ST_AsGeoJSON(ST_Centroid(ST_ConvexHull(ST_Collect(geography)))) AS geography';
                break;
            case 'polygon':
            default:
                $geographySelect = 'ST_AsGeoJSON(ST_ConvexHull(ST_Collect(geography))) AS geography';
        }

        // Get group data according to type. Note that we're querying the
        // database once for every groups type. This is an optimization of the
        // ideal solution of using one "advanced search" query per group, which
        // scales poorly, requiring at least two database queries per group. For
        // example, say we have 10 groups. This approach needs only one query,
        // while the "ideal" approach would need at least 20 queries.
        switch ($data['groups']['type']) {
            case 'item_sets':
                $itemSetIds = array_map('intval', $data['groups']['type_data']['item_set_ids']);
                $sql = sprintf('SELECT item_item_set.item_set_id, %s
                    FROM mapping_feature
                    INNER JOIN item ON mapping_feature.item_id = item.id
                    INNER JOIN item_item_set ON item.id = item_item_set.item_id
                    INNER JOIN item_site ON item.id = item_site.item_id
                    WHERE item_item_set.item_set_id IN (?)
                    AND item_site.site_id = ?
                    GROUP BY item_item_set.item_set_id', $geographySelect);
                $results = $this->connection->executeQuery(
                    $sql,
                    [$itemSetIds, $siteId],
                    [Connection::PARAM_INT_ARRAY, \PDO::PARAM_INT]
                )->fetchAll();
                foreach ($results as $result) {
                    $itemSet = $view->api()->read('item_sets', $result['item_set_id'])->getContent();
                    $groups[] = [
                        'group' => $itemSet,
                        'geography' => $result['geography'],
                    ];
                }
                break;
            case 'resource_classes':
                $resourceClassIds = array_map('intval', $data['groups']['type_data']['resource_class_ids']);
                $sql = sprintf('SELECT resource.resource_class_id, %s
                    FROM mapping_feature
                    INNER JOIN item ON mapping_feature.item_id = item.id
                    INNER JOIN resource ON item.id = resource.id
                    INNER JOIN item_site ON item.id = item_site.item_id
                    WHERE resource.resource_class_id IN (?)
                    AND item_site.site_id = ?
                    GROUP BY resource.resource_class_id', $geographySelect);
                $results = $this->connection->executeQuery(
                    $sql,
                    [$resourceClassIds, $siteId],
                    [Connection::PARAM_INT_ARRAY, \PDO::PARAM_INT]
                )->fetchAll();
                foreach ($results as $result) {
                    $resourceClass = $view->api()->read('resource_classes', $result['resource_class_id'])->getContent();
                    $groups[] = [
                        'group' => $resourceClass,
                        'geography' => $result['geography'],
                    ];
                }
                break;
            case 'property_values_is_exactly':
                $propertyId = (int) $data['groups']['type_data']['property_id'];
                $values = array_filter(array_map('trim', explode("\n", $data['groups']['type_data']['values'])));
                $sql = sprintf('SELECT value.value, %s
                    FROM mapping_feature
                    INNER JOIN item ON mapping_feature.item_id = item.id
                    INNER JOIN value ON item.id = value.resource_id
                    INNER JOIN item_site ON item.id = item_site.item_id
                    WHERE value.value IN (?)
                    AND value.property_id = ?
                    AND item_site.site_id = ?
                    GROUP BY value.value', $geographySelect);
                $results = $this->connection->executeQuery(
                    $sql,
                    [$values, $propertyId, $siteId],
                    [Connection::PARAM_INT_ARRAY, \PDO::PARAM_INT, \PDO::PARAM_INT]
                )->fetchAll();
                foreach ($results as $result) {
                    $groups[] = [
                        'group' => ['property_id' => $propertyId, 'value' => $result['value']],
                        'geography' => $result['geography'],
                    ];
                }
                break;
            case 'property_values_contains':
                $propertyId = (int) $data['groups']['type_data']['property_id'];
                $values = array_filter(array_map('trim', explode("\n", $data['groups']['type_data']['values'])));
                // Must use UNION instead of IN() because of wildcard LIKE query.
                $unions = $queryParams = $queryTypes = [];
                foreach ($values as $value) {
                    $unions[] = sprintf('SELECT ? as contains_value, %s
                        FROM mapping_feature
                        INNER JOIN item ON mapping_feature.item_id = item.id
                        INNER JOIN value ON item.id = value.resource_id
                        INNER JOIN item_site ON item.id = item_site.item_id
                        WHERE value.value LIKE ?
                        AND value.property_id = ?
                        AND item_site.site_id = ?
                        GROUP BY contains_value', $geographySelect);
                        $queryParams = array_merge($queryParams, [$value,  '%' . $value . '%', $propertyId, $siteId]);
                        $queryTypes = array_merge($queryTypes, [\PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_INT]);
                }
                $sql = implode(' UNION ', $unions);
                $results = $this->connection->executeQuery($sql, $queryParams, $queryTypes)->fetchAll();
                foreach ($results as $result) {
                    $groups[] = [
                        'group' => ['property_id' => $propertyId, 'value' => $result['contains_value']],
                        'geography' => $result['geography'],
                    ];
                }
                break;
            case 'properties_has_any_value':
                $propertyIds = array_map('intval', $data['groups']['type_data']['property_ids']);
                $sql = sprintf('SELECT value.property_id, %s
                    FROM mapping_feature
                    INNER JOIN item ON mapping_feature.item_id = item.id
                    INNER JOIN value ON item.id = value.resource_id
                    INNER JOIN item_site ON item.id = item_site.item_id
                    WHERE value.property_id IN (?)
                    AND item_site.site_id = ?
                    GROUP BY value.property_id', $geographySelect);
                $results = $this->connection->executeQuery(
                    $sql,
                    [$propertyIds, $siteId],
                    [Connection::PARAM_INT_ARRAY, \PDO::PARAM_INT]
                )->fetchAll();
                foreach ($results as $result) {
                    $property = $view->api()->read('properties', $result['property_id'])->getContent();
                    $groups[] = [
                        'group' => $property,
                        'geography' => $result['geography'],
                    ];
                }
                break;
            default:
                $groups = [];
        }

        return $view->partial('common/block-layout/mapping-block-groups', [
            'data' => $data,
            'dataItems' => $dataItems,
            'groups' => $groups,
            'popupPartial' => $this->popupPartials[$data['groups']['type']] ?? null,
        ]);
    }
}
