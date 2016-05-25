<?php
namespace Mapping\Controller\Site;

use Omeka\Controller\Site\AbstractSiteController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractSiteController
{
    public function browseAction()
    {
        // Get all markers in this site's item pool and render them on a map.
        $site = $this->getSite();
        $itemPool = $site->itemPool();
        $itemPool['has_markers'] = true;

        $settings = $this->getServiceLocator()->get('Omeka\SiteSettings');
        if ($settings->get('browse_attached_items', false)) {
            $itemPool['site_id'] = $site->id();
        }

        $query = $this->params()->fromQuery();
        $response = $this->api()->search('items', array_merge_recursive($query, $itemPool));
        $items = $response->getContent();
        $itemIds = [];
        foreach ($items as $item) {
            $itemIds[] = $item->id();
        }
        unset($items);

        $markersQuery = ['item_id' => $itemIds];
        if (isset($query['mapping_address']) && isset($query['mapping_radius'])) {
            $markersQuery['address'] = $query['mapping_address'];
            $markersQuery['radius'] = $query['mapping_radius'];
            $markersQuery['radius_unit'] = isset($query['mapping_radius_unit'])
                ? $query['mapping_radius_unit'] : null;
        }
        $response = $this->api()->search('mapping_markers', $markersQuery);
        $markers = $response->getContent();

        $view = new ViewModel;
        $view->setVariable('query', $query);
        $view->setVariable('markers', $markers);
        return $view;
    }
}
