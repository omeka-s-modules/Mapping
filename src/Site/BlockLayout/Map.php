<?php
namespace Mapping\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Stdlib\ErrorStore;
use Zend\View\Renderer\PhpRenderer;

class Map extends AbstractBlockLayout
{
    public function getLabel()
    {
        return 'Map'; // @translate
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {
        $block->setData($this->filterBlockData($block->getData()));

        // Validate attachments.
        $itemIds = [];
        $attachments = $block->getAttachments();
        foreach ($attachments as $attachment) {
            // When an item was removed from the base, it should be removed.
            $item = $attachment->getItem();
            if (!$item) {
                $attachments->removeElement($attachment);
                continue;
            }
            // Duplicate items are redundant, so remove them.
            $itemId = $item->getId();
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
        $view->headLink()->appendStylesheet($view->assetUrl('vendor/leaflet/leaflet.css', 'Mapping'));
        $view->headScript()->appendFile($view->assetUrl('vendor/leaflet/leaflet.js', 'Mapping'));
        $view->headScript()->appendFile($view->assetUrl('js/control.default-view.js', 'Mapping'));
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null
    ) {
        $data = $block ? $block->data() : [];
        return $view->partial('common/block-layout/mapping-block-form', [
            'data' => $this->filterBlockData($data),
        ]) . $view->blockAttachmentsForm($block, true, ['has_markers' => true]);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $data = $this->filterBlockData($block->data());

        $timeline = [];
        if (isset($data['timeline']['data_type_property'])) {
            $dataTypeProperty = explode(':', $data['timeline']['data_type_property']);
            $timeline['data_type'] = sprintf('%s:%s', $dataTypeProperty[0], $dataTypeProperty[1]);
            $timeline['property'] = $view->api()->read('properties', $dataTypeProperty[2])->getContent();
        }
        if (!isset($timeline['property'])) {
            $timeline = [];
        }

        // Get all markers from the attachment items.
        $allMarkers = [];
        $timelineEvents = [];
        foreach ($block->attachments() as $attachment) {
            // When an item was removed from the base, it should be skipped.
            $item = $attachment->item();
            if (!$item) {
                continue;
            }
            if ($timeline) {
                $timelineEvents[] = $this->getTimelineEvent($item, $timeline['property'], $timeline['data_type']);
            }
            $markers = $view->api()->search(
                'mapping_markers',
                ['item_id' => $item->id()]
            )->getContent();
            $allMarkers = array_merge($allMarkers, $markers);
        }

        return $view->partial('common/block-layout/mapping-block', [
            'data' => $data,
            'markers' => $allMarkers,
            'timelineData' => [
                'events' => array_filter($timelineEvents),
            ],
            'timelineOptions' => [
                'debug' => false,
                'timenav_position' => 'top',
            ],
        ]);
    }

    /**
     * Get a timeline event.
     *
     * @see https://timeline.knightlab.com/docs/json-format.html
     * @param ItemRepresentation $item
     * @param PropertyRepresentation $property
     * @param string $dataType
     * @return array
     */
    public function getTimelineEvent($item, $property, $dataType)
    {
        $value = $item->value($property->term(), ['type' => $dataType]);
        if (!$value) {
            return;
        }

        // Set the unique ID and "text" object.
        $title = $item->value('dcterms:title');
        $description = $item->value('dcterms:description');
        $event = [
            'unique_id' => (string) $item->id(), // must cast to string
            'text' => [
                'headline' => $title ? $title->value() : '', // must set empty string
                'text' => $description ? $description->value() : '', // must set empty string
            ],
        ];

        // Set the "media" object.
        $media = $item->primaryMedia();
        if ($media) {
            $event['media'] = [
                'url' => $media->thumbnailUrl('large'),
                'thumbnail' => $media->thumbnailUrl('medium'),
                'link' => $item->url(),
            ];
        }

        // Set the start and end "date" objects.
        if ('numeric:timestamp' === $dataType) {
            $dateTime = \NumericDataTypes\DataType\Timestamp::getDateTimeFromValue($value->value());
            $event['start_date'] = [
                'year' => $dateTime['year'],
                'month' => $dateTime['month'],
                'day' => $dateTime['day'],
                'hour' => $dateTime['hour'],
                'minute' => $dateTime['minute'],
                'second' => $dateTime['second'],
            ];
        } elseif ('numeric:interval' === $dataType) {
            list($intervalStart, $intervalEnd) = explode('/', $value->value());
            $dateTimeStart = \NumericDataTypes\DataType\Timestamp::getDateTimeFromValue($intervalStart);
            $event['start_date'] = [
                'year' => $dateTimeStart['year'],
                'month' => $dateTimeStart['month'],
                'day' => $dateTimeStart['day'],
                'hour' => $dateTimeStart['hour'],
                'minute' => $dateTimeStart['minute'],
                'second' => $dateTimeStart['second'],
            ];
            $dateTimeEnd = \NumericDataTypes\DataType\Timestamp::getDateTimeFromValue($intervalEnd);
            $event['end_date'] = [
                'year' => $dateTimeEnd['year'],
                'month' => $dateTimeEnd['month'],
                'day' => $dateTimeEnd['day'],
                'hour' => $dateTimeEnd['hour'],
                'minute' => $dateTimeEnd['minute'],
                'second' => $dateTimeEnd['second'],
            ];
        }
        return $event;
    }

    /**
     * Filter Map block data.
     *
     * We filter data on input and output to ensure a valid format, regardless
     * of version.
     *
     * @param array $data
     * @return array
     */
    protected function filterBlockData($data)
    {
        // Filter the defualt view data.
        $bounds = null;
        if (isset($data['bounds'])
            && 4 === count(array_filter(explode(',', $data['bounds']), 'is_numeric'))
        ) {
            $bounds = $data['bounds'];
        }

        // Filter the WMS overlay data.
        $wmsOverlays = [];
        if (isset($data['wms']) && is_array($data['wms'])) {
            foreach ($data['wms'] as $wmsOverlay) {
                // WMS data must have label and base URL.
                if (is_array($wmsOverlay)
                    && isset($wmsOverlay['label'])
                    && isset($wmsOverlay['base_url'])
                ) {
                    $layers = '';
                    if (isset($wmsOverlay['layers']) && '' !== trim($wmsOverlay['layers'])) {
                        $layers = $wmsOverlay['layers'];
                    }
                    $wmsOverlay['layers'] = $layers;

                    $styles = '';
                    if (isset($wmsOverlay['styles']) && '' !== trim($wmsOverlay['styles'])) {
                        $styles = $wmsOverlay['styles'];
                    }
                    $wmsOverlay['styles'] = $styles;

                    $open = null;
                    if (isset($wmsOverlay['open']) && $wmsOverlay['open']) {
                        $open = true;
                    }
                    $wmsOverlay['open'] = $open;

                    $wmsOverlays[] = $wmsOverlay;
                }
            }
        }

        // Filter the timeline data.
        $timeline = [
            'data_type_property' => null,
        ];
        if (isset($data['timeline'])) {
            if (isset($data['timeline']['data_type_property'])) {
                $property = explode(':', $data['timeline']['data_type_property']);
                if (3 === count($property)) {
                    list($namespace, $type, $propertyId) = $property;
                    if ('numeric' === $namespace && is_string($type) && is_numeric($propertyId)) {
                        $timeline['data_type_property'] = $data['timeline']['data_type_property'];
                    }
                }
            }
        }

        return [
            'bounds' => $bounds,
            'wms' => $wmsOverlays,
            'timeline' => $timeline,
        ];
    }
}
