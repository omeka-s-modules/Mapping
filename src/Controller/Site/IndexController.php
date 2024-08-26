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
        $perPage = $this->siteSettings()->get('mapping_browse_per_page', 5000);
        $query->set('per_page', $query->get('per_page', $perPage));

        $itemsQuery = $this->params()->fromQuery();

        if ($this->siteSettings()->get('browse_attached_items', false)) {
            // Respect the browse_attached_items setting.
            $itemsQuery['site_attachments_only'] = true;
        }

        // Only get items that are in this site's item pool.
        $itemsQuery['site_id'] = $this->currentSite()->id();
        // Only get items that have features.
        $itemsQuery['has_features'] = true;
        // Do not include geographic location query when searching items.
        unset(
            $itemsQuery['mapping_address'],
            $itemsQuery['mapping_radius'],
            $itemsQuery['mapping_radius_unit'],
        );
        $response = $this->api()->search('items', $itemsQuery, ['returnScalar' => 'id']);
        $itemIds = $response->getContent();
        $this->paginator($response->getTotalResults());

        // Get all features for all items that match the query, if any.
        $features = [];
        if ($itemIds) {
            $featuresQuery = [
                'item_id' => $itemIds,
                'address' => $this->params()->fromQuery('mapping_address'),
                'radius' => $this->params()->fromQuery('mapping_radius'),
                'radius_unit' => $this->params()->fromQuery('mapping_radius_unit'),
            ];
            $features = $this->api()->search('mapping_features', $featuresQuery)->getContent();
        }

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

        switch ($groupType) {
            case 'item_sets':
                $itemsQuery = ['item_set_id' => $group];
                $itemIds = $this->api()->search('items', $itemsQuery, ['returnScalar' => 'id'])->getContent();
                $featuresQuery = ['item_id' => $itemIds];
                $features = $this->api()->search('mapping_features', $featuresQuery)->getContent();
                break;
            case 'resource_classes':
                $itemsQuery = ['resource_class_id' => $group];
                $itemIds = $this->api()->search('items', $itemsQuery, ['returnScalar' => 'id'])->getContent();
                $featuresQuery = ['item_id' => $itemIds];
                $features = $this->api()->search('mapping_features', $featuresQuery)->getContent();
                break;
            case 'values_is_exactly':
                $itemsQuery = [
                    'property' => [
                        [
                            'joiner' => 'and',
                            'type' => 'eq',
                            'property' => $group['property_id'],
                            'text' => $group['value'],
                        ],
                    ],
                ];
                $itemIds = $this->api()->search('items', $itemsQuery, ['returnScalar' => 'id'])->getContent();
                $featuresQuery = ['item_id' => $itemIds];
                $features = $this->api()->search('mapping_features', $featuresQuery)->getContent();
                break;
            case 'properties_has_any_value':
                $itemsQuery = [
                    'property' => [
                        [
                            'joiner' => 'and',
                            'type' => 'ex',
                            'property' => $group,
                        ],
                    ],
                ];
                $itemIds = $this->api()->search('items', $itemsQuery, ['returnScalar' => 'id'])->getContent();
                $featuresQuery = ['item_id' => $itemIds];
                $features = $this->api()->search('mapping_features', $featuresQuery)->getContent();
                break;
            default:
                $features = [];
        }

        $view = new ViewModel;
        $view->setVariable('features', $features);
        return $view;
    }
}
