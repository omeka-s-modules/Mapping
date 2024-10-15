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
        // An empty string would get all features, so set 0 if there are no items.
        $featuresQuery['item_id'] = $itemIds ? $itemIds : 0;
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
