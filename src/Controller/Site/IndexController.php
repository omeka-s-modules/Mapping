<?php
namespace Mapping\Controller\Site;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function browseAction()
    {
        // Limit to a reasonable amount of items that have features to avoid
        // reaching the server memory limit and to improve client performance.
        $query = $this->getRequest()->getQuery();
        $query->set('page', $query->get('page', 1));
        $browsePerPage = $this->siteSettings()->get('mapping_browse_per_page');
        $browsePerpage = is_numeric($browsePerPage) ? $browsePerPage : '100000';
        $query->set('per_page', $query->get('per_page', $browsePerpage));

        $itemsQuery = $this->params()->fromQuery();
        $itemsQuery['site_id'] = $this->currentSite()->id(); // Items must be assigned to this site.
        $itemsQuery['has_features'] = true; // Items must have features.
        if ($this->siteSettings()->get('browse_attached_items', false)) {
            $itemsQuery['site_attachments_only'] = true; // Respect the browse_attached_items setting.
        }
        // Do not include geographic location query when searching items.
        unset(
            $itemsQuery['mapping_address'],
            $itemsQuery['mapping_radius'],
            $itemsQuery['mapping_radius_unit'],
        );
        $itemsResponse = $this->api()->search('items', $itemsQuery, ['returnScalar' => 'id']);
        $this->paginator($itemsResponse->getTotalResults());

        // Get all features for all items that match the query, if any.
        $featuresQuery = [
            'item_id' => $itemsResponse->getContent(),
            'address' => $this->params()->fromQuery('mapping_address'),
            'radius' => $this->params()->fromQuery('mapping_radius'),
            'radius_unit' => $this->params()->fromQuery('mapping_radius_unit'),
        ];
        $features = $this->api()->search('mapping_features', $featuresQuery)->getContent();

        $view = new ViewModel;
        $view->setVariable('query', $this->params()->fromQuery());
        $view->setVariable('features', $features);
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
