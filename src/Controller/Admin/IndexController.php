<?php
namespace Mapping\Controller\Admin;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function getFeaturesAction()
    {
        $itemsQuery = json_decode($this->params()->fromQuery('items_query'), true);
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
