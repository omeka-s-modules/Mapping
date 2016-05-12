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

        $response = $this->api()->search('items', $itemPool);
        $items = $response->getContent();
        $itemIds = [];
        foreach ($items as $item) {
            $itemIds[] = $item->id();
        }
        unset($items);

        $response = $this->api()->search('mapping_markers', ['item_id' => $itemIds]);
        $markers = $response->getContent();

        $view = new ViewModel;
        $view->setVariable('markers', $markers);
        return $view;
    }
}
