<?php
namespace Mapping\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Form\Element;
use Omeka\Stdlib\ErrorStore;
use Laminas\View\Renderer\PhpRenderer;

class MapQuery extends AbstractMap
{
    public function getLabel()
    {
        return 'Map by query'; // @translate
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {
        $block->setData($this->filterBlockData($block->getData()));
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null
    ) {
        $form = parent::form($view, $site, $page, $block);
        $data = $this->filterBlockData($block ? $block->data() : []);
        $element = new Element\Query('o:block[__blockIndex__][o:data][query]');
        $element->setValue($data['query'] ?? null)
            ->setLabel($view->translate('Query'))
            ->setOption('info', $view->translate('Attach items using this query. No query means all items.'));
        $form .= '
<a href="#" class="mapping-map-expander collapse"><h4>' . $view->translate('Query') . '</h4></a>
<div class="collapsible">' . $view->formRow($element) . '</div>';
        return $form;
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $data = $this->filterBlockData($block->data());
        $isTimeline = (bool) $data['timeline']['data_type_properties'];
        $timelineIsAvailable = $this->timelineIsAvailable();

        // Get markers (and events, if applicable) from the attached items.
        $events = [];
        $markers = [];
        parse_str($data['query'], $query);
        // Search only for items with markers that are in the current site, and
        // set a reasonable item limit.
        $query = array_merge($query, [
            'site_id' => $block->page()->site()->id(),
            'has_markers' => true,
            'limit' => 5000,
        ]);
        $response = $view->api()->search('items', $query);
        foreach ($response->getContent() as $item) {
            if ($isTimeline && $timelineIsAvailable) {
                // Set the timeline event for this item.
                $event = $this->getTimelineEvent($item, $data['timeline']['data_type_properties'], $view);
                if ($event) {
                    $events[] = $event;
                }
            }
            // Set the map markers for this item.
            $itemMarkers = $view->api()->search('mapping_markers', ['item_id' => $item->id()])->getContent();
            $markers = array_merge($markers, $itemMarkers);
        }

        return $view->partial('common/block-layout/mapping-block', [
            'data' => $data,
            'markers' => $markers,
            'isTimeline' => $isTimeline,
            'timelineData' => $this->getTimelineData($events, $data, $view),
            'timelineOptions' => $this->getTimelineOptions($data),
        ]);
    }

    protected function filterBlockData($data)
    {
        $query = $data['query'] ?? null;
        $data = parent::filterBlockData($data);
        $data['query'] = $query;
        return $data;
    }
}
