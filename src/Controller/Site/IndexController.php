<?php
namespace Mapping\Controller\Site;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function browseAction()
    {
        $itemsQuery = $this->params()->fromQuery();

        if ($this->siteSettings()->get('browse_attached_items', false)) {
            // Respect the browse_attached_items setting.
            $itemsQuery['site_attachments_only'] = true;
        }

        // Only get items that are in this site's item pool.
        $itemsQuery['site_id'] = $this->currentSite()->id();
        // Only get items that have features.
        $itemsQuery['has_features'] = true;
        // Limit to a reasonable amount of items that have features to avoid
        // reaching the server memory limit and to improve client performance.
        $itemsQuery['limit'] = 5000;
        // Do not include geographic location query when searching items.
        unset(
            $itemsQuery['mapping_address'],
            $itemsQuery['mapping_radius'],
            $itemsQuery['mapping_radius_unit'],
        );
        $itemIds = $this->api()->search('items', $itemsQuery, ['returnScalar' => 'id'])->getContent();

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
}
