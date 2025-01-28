<?php
namespace Mapping\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Mapping\Form\BlockLayoutMapQueryForm;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Stdlib\ErrorStore;

class MapQuery extends AbstractMap
{
    public function getLabel()
    {
        return 'Map by query'; // @translate
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {
        $form = $this->formElementManager->get(BlockLayoutMapQueryForm::class);
        $data = $form->prepareBlockData($block->getData());
        $block->setData($data);
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null
    ) {
        $form = $this->formElementManager->get(BlockLayoutMapQueryForm::class);
        $data = $form->prepareBlockData($block ? $block->data() : []);

        $formHtml = [];
        $formHtml[] = $view->partial('common/block-layout/mapping-block-form/default-view', [
            'data' => $data,
            'form' => $form,
        ]);
        $formHtml[] = $view->partial('common/block-layout/mapping-block-form/overlays', [
            'data' => $data,
            'form' => $form,
        ]);
        if ($this->timelineIsAvailable()) {
            $formHtml[] = $view->partial('common/block-layout/mapping-block-form/timeline', [
                'data' => $data,
                'form' => $form,
            ]);
        }
        $formHtml[] = $view->partial('common/block-layout/mapping-block-form/query', [
            'data' => $data,
            'form' => $form,
        ]);
        return implode('', $formHtml);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $form = $this->formElementManager->get(BlockLayoutMapQueryForm::class);
        $data = $form->prepareBlockData($block->data());

        $isTimeline = (bool) $data['timeline']['data_type_properties'];
        $timelineIsAvailable = $this->timelineIsAvailable();

        parse_str($data['query'], $itemsQuery);
        $featuresQuery = [];

        // Get all events for the items.
        $events = [];
        if ($isTimeline && $timelineIsAvailable) {
            $itemsQuery['site_id'] = $block->page()->site()->id();
            $itemsQuery['has_features'] = true;
            $itemsQuery['limit'] = 100000;
            $itemIds = $this->apiManager->search('items', $itemsQuery, ['returnScalar' => 'id'])->getContent();
            foreach ($itemIds as $itemId) {
                // Set the timeline event for this item.
                $event = $this->getTimelineEvent($itemId, $data['timeline']['data_type_properties'], $view);
                if ($event) {
                    $events[] = $event;
                }
            }
        }

        return $view->partial('common/block-layout/mapping-block', [
            'data' => $data,
            'itemsQuery' => $itemsQuery,
            'featuresQuery' => $featuresQuery,
            'isTimeline' => $isTimeline,
            'timelineData' => $this->getTimelineData($events, $data, $view),
            'timelineOptions' => $this->getTimelineOptions($data),
        ]);
    }
}
