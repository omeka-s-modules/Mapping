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

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $data = $this->filterBlockData($block->data());
        $isTimeline = (bool) $data['timeline']['data_type_properties'];
        $timelineIsAvailable = $this->timelineIsAvailable();

        // Get features (and events, if applicable) from the attached items.
        $events = [];
        $features = [];
        parse_str($data['query'], $query);
        // Search only for items with features that are in the current site, and
        // set a reasonable item limit.
        $query = array_merge($query, [
            'site_id' => $block->page()->site()->id(),
            'has_features' => true,
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
            // Set the map features for this item.
            $itemFeatures = $view->api()->search('mapping_features', ['item_id' => $item->id()])->getContent();
            $features = array_merge($features, $itemFeatures);
        }

        return $view->partial('common/block-layout/mapping-block', [
            'data' => $data,
            'features' => $features,
            'isTimeline' => $isTimeline,
            'timelineData' => $this->getTimelineData($events, $data, $view),
            'timelineOptions' => $this->getTimelineOptions($data),
        ]);
    }
}
