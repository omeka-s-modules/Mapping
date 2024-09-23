<?php
namespace Mapping\Controller\Site;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function browseAction()
    {
        $itemsQuery = $this->params()->fromQuery();
        unset(
            $itemsQuery['mapping_address'],
            $itemsQuery['mapping_radius'],
            $itemsQuery['mapping_radius_unit'],
        );
        if ($this->siteSettings()->get('browse_attached_items', false)) {
            $itemsQuery['site_attachments_only'] = true;
        }

        // Get all features for all items that match the query, if any.
        $featuresQuery = [
            'address' => $this->params()->fromQuery('mapping_address'),
            'radius' => $this->params()->fromQuery('mapping_radius'),
            'radius_unit' => $this->params()->fromQuery('mapping_radius_unit'),
        ];

        $view = new ViewModel;
        $view->setVariable('query', $this->params()->fromQuery());
        $view->setVariable('itemsQuery', $itemsQuery);
        $view->setVariable('featuresQuery', $featuresQuery);
        return $view;
    }

    public function getFeaturePopupsByGroupAction()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();

        $groupType = $this->params()->fromPost('group_type');
        $group = $this->params()->fromPost('group');

        $itemsQuery = ['site_id' => $this->currentSite()->id()];

        switch ($groupType) {
            case 'item_sets':
                $itemsQuery['item_set_id'] = $group['item_set_id'];
                $itemsQuery['resource_class_id'] = $group['resource_class_id'];
                break;
            case 'resource_classes':
                $itemsQuery['resource_class_id'] = $group['resource_class_id'];
                $itemsQuery['item_set_id'] = $group['item_set_id'];
                break;
            case 'property_values_eq':
                $itemsQuery['property'] = [
                    [
                        'joiner' => 'and',
                        'type' => 'eq',
                        'property' => $group['property_id'],
                        'text' => $group['value'],
                    ],
                ];
                $itemsQuery['item_set_id'] = $group['item_set_id'];
                $itemsQuery['resource_class_id'] = $group['resource_class_id'];
                break;
            case 'property_values_in':
                $itemsQuery['property'] = [
                    [
                        'joiner' => 'and',
                        'type' => 'in',
                        'property' => $group['property_id'],
                        'text' => $group['value'],
                    ],
                ];
                $itemsQuery['item_set_id'] = $group['item_set_id'];
                $itemsQuery['resource_class_id'] = $group['resource_class_id'];
                break;
            case 'property_values_res':
                $itemsQuery['property'] = [
                    [
                        'joiner' => 'and',
                        'type' => 'res',
                        'property' => $group['property_id'],
                        'text' => $group['resource_id'],
                    ],
                ];
                $itemsQuery['item_set_id'] = $group['item_set_id'];
                $itemsQuery['resource_class_id'] = $group['resource_class_id'];
                break;
            case 'properties_ex':
                $itemsQuery['property'] = [
                    [
                        'joiner' => 'and',
                        'type' => 'ex',
                        'property' => $group['property_id'],
                    ],
                ];
                $itemsQuery['item_set_id'] = $group['item_set_id'];
                $itemsQuery['resource_class_id'] = $group['resource_class_id'];
                break;
        }

        $features = [];
        if ($itemsQuery) {
            $itemIds = $this->api()->search('items', $itemsQuery, ['returnScalar' => 'id'])->getContent();
            $featuresQuery = ['item_id' => $itemIds];
            $features = $this->api()->search('mapping_features', $featuresQuery)->getContent();
        }

        $view = new ViewModel;
        $view->setVariable('features', $features);
        return $view;
    }

    public function getFeaturesAction()
    {
        $itemsQuery = json_decode($this->params()->fromQuery('items_query'), true);
        $itemsQuery['site_id'] = $this->currentSite()->id();
        $itemsQuery['has_features'] = true;
        $itemsQuery['limit'] = 100000;
        $itemIds = $this->api()->search('items', $itemsQuery, ['returnScalar' => 'id'])->getContent();

        $featuresQuery = json_decode($this->params()->fromQuery('features_query'), true);
        $featuresQuery['page'] = $this->params()->fromQuery('features_page');
        $featuresQuery['per_page'] = 10000;
        $featuresQuery['item_id'] = $itemIds;
        $featureResponse = $this->api()->search('mapping_features', $featuresQuery);

        $features = [];
        foreach ($featureResponse->getContent() as $feature) {
            $features[] = [
                $feature->id(),
                $feature->item()->id(),
                $feature->geography(),
            ];
        }

        return new \Laminas\View\Model\JsonModel($features);
    }

    public function getFeaturePopupContentAction()
    {
        $featureId = $this->params()->fromQuery('feature_id');
        $feature = $this->api()->read('mapping_features', $featureId)->getContent();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('feature', $feature);
        return $view;
    }
}
