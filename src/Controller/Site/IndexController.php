<?php
namespace Mapping\Controller\Site;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function browseAction()
    {
        // Get all markers in this site's item pool and render them on a map.
        $site = $this->currentSite();

        $query = $this->params()->fromQuery();
        $query['site_id'] = $site->id();
        if ($this->siteSettings()->get('browse_attached_items', false)) {
            $query['site_attachments_only'] = true;
        }

        // Limit to a reasonable amount of items that have markers to avoid
        // reaching the server memory limit and to improve client performance.
        $query['limit'] = 5000;
        $query['has_markers'] = true;

        $items = $this->api()->search('items', $query)->getContent();
        $itemIds = [];
        foreach ($items as $item) {
            $itemIds[] = $item->id();
        }
        unset($items);

        // Get all markers for all items that match the query, if any.
        $markers = [];
        if ($itemIds) {
            $markersQuery = ['item_id' => $itemIds];
            if (isset($query['mapping_address']) && isset($query['mapping_radius'])) {
                $markersQuery['address'] = $query['mapping_address'];
                $markersQuery['radius'] = $query['mapping_radius'];
                $markersQuery['radius_unit'] = isset($query['mapping_radius_unit'])
                    ? $query['mapping_radius_unit'] : null;
            }
            $response = $this->api()->search('mapping_markers', $markersQuery);
            $markers = $response->getContent();
        }

        $view = new ViewModel;
        $view->setVariable('query', $query);
        $view->setVariable('markers', $markers);
        return $view;
    }
}
