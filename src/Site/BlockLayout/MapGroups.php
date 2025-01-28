<?php
namespace Mapping\Site\BlockLayout;

use Doctrine\DBAL\Connection;
use Laminas\View\Renderer\PhpRenderer;
use Mapping\Form\BlockLayoutMapGroupsForm;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Stdlib\ErrorStore;

/**
 * The "Map by groups" block layout.
 */
class MapGroups extends AbstractMap
{
    protected $popupPartials = [
        'item_sets' => 'common/mapping-popup/item-set-group',
        'resource_classes' => 'common/mapping-popup/resource-class-group',
        'property_values_eq' => 'common/mapping-popup/property-value-eq-group',
        'property_values_in' => 'common/mapping-popup/property-value-in-group',
        'property_values_res' => 'common/mapping-popup/property-value-res-group',
        'properties_ex' => 'common/mapping-popup/property-ex-group',
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
        if (!$this->isSupported()) {
            $serverVersion = $this->connection->getWrappedConnection()->getServerVersion();
            return sprintf(
                $view->translate('This block requires MySQL 8.0.24+ or MariaDB 11.7+. Your database version is %s.'),
                $serverVersion
            );
        }

        $form = $this->formElementManager->get(BlockLayoutMapGroupsForm::class);
        $data = $form->prepareBlockData($block ? $block->data() : []);

        $formHtml = [];
        $formHtml[] = $view->partial('common/block-layout/mapping-block-form/default-view', [
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
        if (!$this->isSupported()) {
            return '';
        }

        $form = $this->formElementManager->get(BlockLayoutMapGroupsForm::class);
        $data = $form->prepareBlockData($block->data());
        $dataItems = $data;
        $dataItems['bounds'] = null; // Do not use the configured bounds for the items map.
        $siteId = $block->page()->site()->id();

        // Get group data according to type. Note that we're querying the
        // database once for every groups type. This is an optimization of the
        // ideal solution of using one "advanced search" query per group, which
        // scales poorly, requiring at least two database queries per group. For
        // example, say we have 10 groups. This approach needs only one query,
        // while the "ideal" approach would need at least 20 queries.
        switch ($data['groups']['type']) {
            case 'item_sets':
                $groupsData = $this->getGroupsItemSets($data, $siteId, $view);
                break;
            case 'resource_classes':
                $groupsData = $this->getGroupsResourceClasses($data, $siteId, $view);
                break;
            case 'property_values_eq':
                $groupsData = $this->getGroupsPropertyValuesEq($data, $siteId, $view);
                break;
            case 'property_values_in':
                $groupsData = $this->getGroupsPropertyValuesIn($data, $siteId, $view);
                break;
            case 'property_values_res':
                $groupsData = $this->getGroupsPropertyValuesRes($data, $siteId, $view);
                break;
            case 'properties_ex':
                $groupsData = $this->getGroupsPropertyEx($data, $siteId, $view);
                break;
            default:
                $groupsData = [];
        }

        return $view->partial('common/block-layout/mapping-block-groups', [
            'data' => $data,
            'dataItems' => $dataItems,
            'groupsData' => $groupsData,
            'popupPartial' => $this->popupPartials[$data['groups']['type']] ?? null,
        ]);
    }

    protected function isSupported()
    {
        try {
            // The ST_COLLECT function must exist.
            $this->connection->executeQuery('SELECT ST_COLLECT(null)');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function getGroupsItemSets($data, $siteId, PhpRenderer $view)
    {
        $itemSetIds = array_map('intval', $data['groups']['type_data']['item_set_ids']);
        $resourceClassId = $data['groups']['filter_data']['resource_class_id'];

        $queryParams = [$itemSetIds, $siteId];
        $queryTypes = [Connection::PARAM_INT_ARRAY, \PDO::PARAM_INT];
        if ($resourceClassId) {
            $queryParams[] = $resourceClassId;
            $queryTypes[] = \PDO::PARAM_INT;
        }

        $sql = sprintf('SELECT COUNT(DISTINCT item.id) AS count, item_item_set.item_set_id, %s
            FROM mapping_feature
            INNER JOIN item ON mapping_feature.item_id = item.id
            INNER JOIN item_item_set ON item.id = item_item_set.item_id
            INNER JOIN item_site ON item.id = item_site.item_id
            %s
            WHERE item_item_set.item_set_id IN (?)
            AND item_site.site_id = ?
            %s
            GROUP BY item_item_set.item_set_id',
            $this->getGeographySelect($data),
            $resourceClassId ? 'INNER JOIN resource ON item.id = resource.id' : '',
            $resourceClassId ? 'AND resource.resource_class_id = ?' : ''
        );

        $results = $this->connection->executeQuery($sql, $queryParams, $queryTypes)->fetchAll();
        $groupsData = [];
        foreach ($results as $result) {
            $groupsData[] = [
                'group' => [
                    'count' => $result['count'],
                    'item_set_id' => $result['item_set_id'],
                    'resource_class_id' => $resourceClassId,
                ],
                'geography' => $result['geography'],
                'items_query' => [
                    'item_set_id' => $result['item_set_id'],
                    'resource_class_id' => $resourceClassId,
                ],
            ];
        }
        return $groupsData;
    }

    protected function getGroupsResourceClasses($data, $siteId, PhpRenderer $view)
    {
        $resourceClassIds = array_map('intval', $data['groups']['type_data']['resource_class_ids']);
        $itemSetId = $data['groups']['filter_data']['item_set_id'];

        $queryParams = [$resourceClassIds, $siteId];
        $queryTypes = [Connection::PARAM_INT_ARRAY, \PDO::PARAM_INT];
        if ($itemSetId) {
            $queryParams[] = $itemSetId;
            $queryTypes[] = \PDO::PARAM_INT;
        }

        $sql = sprintf('SELECT COUNT(DISTINCT item.id) AS count, resource.resource_class_id, %s
            FROM mapping_feature
            INNER JOIN item ON mapping_feature.item_id = item.id
            INNER JOIN resource ON item.id = resource.id
            INNER JOIN item_site ON item.id = item_site.item_id
            %s
            WHERE resource.resource_class_id IN (?)
            AND item_site.site_id = ?
            %s
            GROUP BY resource.resource_class_id',
            $this->getGeographySelect($data),
            $itemSetId ? 'INNER JOIN item_item_set ON item.id = item_item_set.item_id' : '',
            $itemSetId ? 'AND item_item_set.item_set_id = ?' : '',
        );

        $results = $this->connection->executeQuery($sql, $queryParams, $queryTypes)->fetchAll();
        $groupsData = [];
        foreach ($results as $result) {
            $groupsData[] = [
                'group' => [
                    'count' => $result['count'],
                    'resource_class_id' => $result['resource_class_id'],
                    'item_set_id' => $itemSetId,
                ],
                'geography' => $result['geography'],
                'items_query' => [
                    'item_set_id' => $itemSetId,
                    'resource_class_id' => $result['resource_class_id'],
                ],
            ];
        }
        return $groupsData;
    }

    protected function getGroupsPropertyValuesEq($data, $siteId, PhpRenderer $view)
    {
        $propertyId = (int) $data['groups']['type_data']['property_id'];
        $values = array_filter(array_map('trim', explode("\n", $data['groups']['type_data']['values'])));
        $itemSetId = $data['groups']['filter_data']['item_set_id'];
        $resourceClassId = $data['groups']['filter_data']['resource_class_id'];

        $queryParams = [$values, $propertyId, $siteId];
        $queryTypes = [Connection::PARAM_STR_ARRAY, \PDO::PARAM_INT, \PDO::PARAM_INT];
        if ($itemSetId) {
            $queryParams[] = $itemSetId;
            $queryTypes[] = \PDO::PARAM_INT;
        }
        if ($resourceClassId) {
            $queryParams[] = $resourceClassId;
            $queryTypes[] = \PDO::PARAM_INT;
        }

        $sql = sprintf('SELECT COUNT(DISTINCT item.id) AS count, value.value, %s
            FROM mapping_feature
            INNER JOIN item ON mapping_feature.item_id = item.id
            INNER JOIN value ON item.id = value.resource_id
            INNER JOIN item_site ON item.id = item_site.item_id
            %s
            %s
            WHERE value.value IN (?)
            AND value.property_id %s ?
            AND item_site.site_id = ?
            %s
            %s
            GROUP BY value.value',
            $this->getGeographySelect($data),
            $itemSetId ? 'INNER JOIN item_item_set ON item.id = item_item_set.item_id' : '',
            $resourceClassId ? 'INNER JOIN resource ON item.id = resource.id' : '',
            $propertyId ? '=' : '!=',
            $itemSetId ? 'AND item_item_set.item_set_id = ?' : '',
            $resourceClassId ? 'AND resource.resource_class_id = ?' : ''
        );

        $results = $this->connection->executeQuery($sql, $queryParams, $queryTypes)->fetchAll();
        $groupsData = [];
        foreach ($results as $result) {
            $groupsData[] = [
                'group' => [
                    'count' => $result['count'],
                    'property_id' => $propertyId,
                    'value' => $result['value'],
                    'item_set_id' => $itemSetId,
                    'resource_class_id' => $resourceClassId,
                ],
                'geography' => $result['geography'],
                'items_query' => [
                    'item_set_id' => $itemSetId,
                    'resource_class_id' => $resourceClassId,
                    'property' => [
                        [
                            'joiner' => 'and',
                            'type' => 'eq',
                            'property' => $propertyId,
                            'text' => $result['value'],
                        ],
                    ],
                ],
            ];
        }
        return $groupsData;
    }

    protected function getGroupsPropertyValuesIn($data, $siteId, PhpRenderer $view)
    {
        $propertyId = (int) $data['groups']['type_data']['property_id'];
        $values = array_filter(array_map('trim', explode("\n", $data['groups']['type_data']['values'])));
        $itemSetId = $data['groups']['filter_data']['item_set_id'];
        $resourceClassId = $data['groups']['filter_data']['resource_class_id'];

        // Must use UNION instead of IN() because of wildcard LIKE query.
        $unions = $queryParams = $queryTypes = [];
        foreach ($values as $value) {
            $thisQueryParams = [$value,  '%' . $value . '%', $propertyId, $siteId];
            $thisQueryTypes = [\PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_INT];
            if ($itemSetId) {
                $thisQueryParams[] = $itemSetId;
                $thisQueryTypes[] = \PDO::PARAM_INT;
            }
            if ($resourceClassId) {
                $thisQueryParams[] = $resourceClassId;
                $thisQueryTypes[] = \PDO::PARAM_INT;
            }
            $unions[] = sprintf('SELECT COUNT(DISTINCT item.id) AS count, ? as contains_value, %s
                FROM mapping_feature
                INNER JOIN item ON mapping_feature.item_id = item.id
                INNER JOIN value ON item.id = value.resource_id
                INNER JOIN item_site ON item.id = item_site.item_id
                %s
                %s
                WHERE value.value LIKE ?
                AND value.property_id %s ?
                AND item_site.site_id = ?
                %s
                %s
                GROUP BY contains_value',
                $this->getGeographySelect($data),
                $itemSetId ? 'INNER JOIN item_item_set ON item.id = item_item_set.item_id' : '',
                $resourceClassId ? 'INNER JOIN resource ON item.id = resource.id' : '',
                $propertyId ? '=' : '!=',
                $itemSetId ? 'AND item_item_set.item_set_id = ?' : '',
                $resourceClassId ? 'AND resource.resource_class_id = ?' : ''
            );
            $queryParams = array_merge($queryParams, $thisQueryParams);
            $queryTypes = array_merge($queryTypes, $thisQueryTypes);
        }
        $sql = implode(' UNION ', $unions);

        $results = $this->connection->executeQuery($sql, $queryParams, $queryTypes)->fetchAll();
        $groupsData = [];
        foreach ($results as $result) {
            $groupsData[] = [
                'group' => [
                    'count' => $result['count'],
                    'property_id' => $propertyId,
                    'value' => $result['contains_value'],
                    'item_set_id' => $itemSetId,
                    'resource_class_id' => $resourceClassId,
                ],
                'geography' => $result['geography'],
                'items_query' => [
                    'item_set_id' => $itemSetId,
                    'resource_class_id' => $resourceClassId,
                    'property' => [
                        [
                            'joiner' => 'and',
                            'type' => 'in',
                            'property' => $propertyId,
                            'text' => $result['contains_value'],
                        ],
                    ],
                ],
            ];
        }
        return $groupsData;
    }

    protected function getGroupsPropertyValuesRes($data, $siteId, PhpRenderer $view)
    {
        $propertyId = (int) $data['groups']['type_data']['property_id'];
        $values = array_filter(array_map('trim', explode("\n", $data['groups']['type_data']['values'])));
        $itemSetId = $data['groups']['filter_data']['item_set_id'];
        $resourceClassId = $data['groups']['filter_data']['resource_class_id'];

        $queryParams = [$values, $propertyId, $siteId];
        $queryTypes = [Connection::PARAM_INT_ARRAY, \PDO::PARAM_INT, \PDO::PARAM_INT];
        if ($itemSetId) {
            $queryParams[] = $itemSetId;
            $queryTypes[] = \PDO::PARAM_INT;
        }
        if ($resourceClassId) {
            $queryParams[] = $resourceClassId;
            $queryTypes[] = \PDO::PARAM_INT;
        }
        $sql = sprintf('SELECT COUNT(DISTINCT item.id) AS count, value.value_resource_id, %s
            FROM mapping_feature
            INNER JOIN item ON mapping_feature.item_id = item.id
            INNER JOIN value ON item.id = value.resource_id
            INNER JOIN item_site ON item.id = item_site.item_id
            %s
            %s
            WHERE value.value_resource_id IN (?)
            AND value.property_id %s ?
            AND item_site.site_id = ?
            %s
            %s
            GROUP BY value.value_resource_id',
            $this->getGeographySelect($data),
            $itemSetId ? 'INNER JOIN item_item_set ON item.id = item_item_set.item_id' : '',
            $resourceClassId ? 'INNER JOIN resource ON item.id = resource.id' : '',
            $propertyId ? '=' : '!=',
            $itemSetId ? 'AND item_item_set.item_set_id = ?' : '',
            $resourceClassId ? 'AND resource.resource_class_id = ?' : ''
        );

        $results = $this->connection->executeQuery($sql, $queryParams, $queryTypes)->fetchAll();
        $groupsData = [];
        foreach ($results as $result) {
            $groupsData[] = [
                'group' => [
                    'count' => $result['count'],
                    'property_id' => $propertyId,
                    'resource_id' => $result['value_resource_id'],
                    'item_set_id' => $itemSetId,
                    'resource_class_id' => $resourceClassId,
                ],
                'geography' => $result['geography'],
                'items_query' => [
                    'item_set_id' => $itemSetId,
                    'resource_class_id' => $resourceClassId,
                    'property' => [
                        [
                            'joiner' => 'and',
                            'type' => 'res',
                            'property' => $propertyId,
                            'text' => $result['value_resource_id'],
                        ],
                    ],
                ],
            ];
        }
        return $groupsData;
    }

    protected function getGroupsPropertyEx($data, $siteId, PhpRenderer $view)
    {
        $propertyIds = array_map('intval', $data['groups']['type_data']['property_ids']);
        $itemSetId = $data['groups']['filter_data']['item_set_id'];
        $resourceClassId = $data['groups']['filter_data']['resource_class_id'];

        $queryParams = [$propertyIds, $siteId];
        $queryTypes = [Connection::PARAM_INT_ARRAY, \PDO::PARAM_INT];
        if ($itemSetId) {
            $queryParams[] = $itemSetId;
            $queryTypes[] = \PDO::PARAM_INT;
        }
        if ($resourceClassId) {
            $queryParams[] = $resourceClassId;
            $queryTypes[] = \PDO::PARAM_INT;
        }
        $sql = sprintf('SELECT COUNT(DISTINCT item.id) AS count, value.property_id, %s
            FROM mapping_feature
            INNER JOIN item ON mapping_feature.item_id = item.id
            INNER JOIN value ON item.id = value.resource_id
            INNER JOIN item_site ON item.id = item_site.item_id
            %s
            %s
            WHERE value.property_id IN (?)
            AND item_site.site_id = ?
            %s
            %s
            GROUP BY value.property_id',
            $this->getGeographySelect($data),
            $itemSetId ? 'INNER JOIN item_item_set ON item.id = item_item_set.item_id' : '',
            $resourceClassId ? 'INNER JOIN resource ON item.id = resource.id' : '',
            $itemSetId ? 'AND item_item_set.item_set_id = ?' : '',
            $resourceClassId ? 'AND resource.resource_class_id = ?' : ''
        );

        $results = $this->connection->executeQuery($sql, $queryParams, $queryTypes)->fetchAll();
        $groupsData = [];
        foreach ($results as $result) {
            $groupsData[] = [
                'group' => [
                    'count' => $result['count'],
                    'property_id' => $result['property_id'],
                    'item_set_id' => $itemSetId,
                    'resource_class_id' => $resourceClassId,
                ],
                'geography' => $result['geography'],
                'items_query' => [
                    'item_set_id' => $itemSetId,
                    'resource_class_id' => $resourceClassId,
                    'property' => [
                        [
                            'joiner' => 'and',
                            'type' => 'ex',
                            'property' => $result['property_id'],
                        ],
                    ],
                ],
            ];
        }
        return $groupsData;
    }

    protected function getGeographySelect($data)
    {
        // Get the GROUP BY clause depending on feature type.
        switch ($data['groups']['feature_type']) {
            case 'point':
                return 'ST_AsGeoJSON(ST_Centroid(ST_ConvexHull(ST_Collect(geography)))) AS geography';
            case 'polygon':
            default:
                return 'ST_AsGeoJSON(ST_ConvexHull(ST_Collect(geography))) AS geography';
        }
    }
}
