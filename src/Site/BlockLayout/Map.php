<?php
namespace Mapping\Site\BlockLayout;

use Zend\Form\Element\Select;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Stdlib\ErrorStore;
use Zend\View\Renderer\PhpRenderer;

class Map extends AbstractBlockLayout
{
    public function getLabel()
    {
        $translator = $this->getServiceLocator()->get('MvcTranslator');
        return $translator->translate('Map');
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {
        $data = $block->getData();

        // Validate the defualt view data.
        $defaultView = ['zoom' => null, 'lat' => null, 'lng' => null];
        if (isset($data['default_view']) && is_array($data['default_view'])
            && isset($data['default_view']['zoom']) && is_numeric($data['default_view']['zoom'])
            && isset($data['default_view']['lat']) && is_numeric($data['default_view']['lat'])
            && isset($data['default_view']['lng']) && is_numeric($data['default_view']['lng'])
        ) {
            // Default view data must have numeric zoom, lat, and lng.
            $defaultView['zoom'] = $data['default_view']['zoom'];
            $defaultView['lat'] = $data['default_view']['lat'];
            $defaultView['lng'] = $data['default_view']['lng'];
        }

        // Validate the WMS overlay data.
        $wmsOverlays = [];
        if (isset($data['wms']) && is_array($data['wms'])) {
            foreach ($data['wms'] as $wmsOverlay) {
                // WMS data must have label and base URL.
                if (is_array($wmsOverlay)
                    && isset($wmsOverlay['label'])
                    && isset($wmsOverlay['base_url'])
                ) {
                    $wmsOverlays[] = $wmsOverlay;
                }
            }
        }

        $block->setData([
            'default_view' => $defaultView,
            'wms' => $wmsOverlays,
        ]);

        // Validate attachments.
        $itemIds = [];
        $attachments = $block->getAttachments();
        foreach ($attachments as $attachment) {
            // Duplicate items are redundant, so remove them.
            $itemId = $attachment->getItem()->getId();
            if (in_array($itemId, $itemIds)) {
                $attachments->removeElement($attachment);
            }
            $itemIds[] = $itemId;
            // Media and caption are unneeded.
            $attachment->setMedia(null);
            $attachment->setCaption('');
        }
    }

    public function prepareForm(PhpRenderer $view)
    {
        $view->headScript()->appendFile($view->assetUrl('js/mapping-block-form.js', 'Mapping'));
        $view->headLink()->appendStylesheet($view->assetUrl('js/Leaflet/0.7.7/leaflet.css', 'Mapping'));
        $view->headScript()->appendFile($view->assetUrl('js/Leaflet/0.7.7/leaflet.js', 'Mapping'));
        $view->headScript()->appendFile($view->assetUrl('js/control.default-view.js', 'Mapping'));
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageBlockRepresentation $block = null
    ) {
        return $view->partial('common/block-layout/mapping-block-form', [
            'block' => $block,
        ]) . $this->attachmentsForm($view, $site, $block, true);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $data = $block->data();

        // Get the default view data.
        $defaultZoom = 1;
        $defaultLat = 0;
        $defaultLng = 0;
        if (
            isset($data['default_view']['zoom']) && is_numeric($data['default_view']['zoom'])
            && isset($data['default_view']['lat']) && is_numeric($data['default_view']['lat'])
            && isset($data['default_view']['lng']) && is_numeric($data['default_view']['lng'])
        ) {
            $defaultZoom = $data['default_view']['zoom'];
            $defaultLat = $data['default_view']['lat'];
            $defaultLng = $data['default_view']['lng'];
        }

        // Get WMS data.
        $wms = [];
        if (isset($data['wms']) && is_array($data['wms'])) {
            $wms = $data['wms'];
        }

        // Get all markers from the attachment items.
        $allMarkers = [];
        foreach ($block->attachments() as $attachment) {
            $markers = $view->api()->search(
                'mapping_markers',
                ['item_id' => $attachment->item()->id()]
            )->getContent();
            $allMarkers = array_merge($allMarkers, $markers);
        }

        return $view->partial('common/block-layout/mapping-block', [
            'block' => $block,
            'defaultZoom' => $defaultZoom,
            'defaultLat' => $defaultLat,
            'defaultLng' => $defaultLng,
            'wms' => $wms,
            'markers' => $allMarkers,
        ]);

    }
}
