<?php
namespace Mapping\Site\BlockLayout;

use NumericDataTypes\DataType\Timestamp;
use Omeka\Api\Exception\NotFoundException;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Stdlib\ErrorStore;
use Omeka\Stdlib\HtmlPurifier;
use Zend\View\Renderer\PhpRenderer;

class Map extends AbstractBlockLayout
{
    /**
     * @var HtmlPurifier
     */
    protected $htmlPurifier;

    public function __construct(HtmlPurifier $htmlPurifier)
    {
        $this->htmlPurifier = $htmlPurifier;
    }

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
        $data = $this->filterBlockData($block->data());
        return $view->partial(
            'common/block-layout/mapping-block-form',
            ['data' => $data]
        ) . $view->blockAttachmentsForm($block, true, ['has_markers' => true]);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $data = $this->filterBlockData($block->data());

        // Get all markers from the attachment items.
        $allMarkers = [];
        $timelineEvents = [];
        foreach ($block->attachments() as $attachment) {
            // When an item was removed from the base, it should be skipped.
            $item = $attachment->item();
            if (!$item) {
                continue;
            }
            if ($data['timeline']['data_type_properties']) {
                $timelineEvents[] = $this->getTimelineEvent($item, $data['timeline']['data_type_properties'], $view);
            }
            $markers = $view->api()->search(
                'mapping_markers',
                ['item_id' => $item->id()]
            )->getContent();
            $allMarkers = array_merge($allMarkers, $markers);
        }

        // Set the timeline title.
        $timelineTitle = null;
        if (isset($data['timeline']['title_headline']) || isset($data['timeline']['title_text'])) {
            $timelineTitle = [
                'text' => [
                    'headline' => $data['timeline']['title_headline'],
                    'text' => $data['timeline']['title_text'],
                ],
            ];
        }
        return $view->partial('common/block-layout/mapping-block', [
            'data' => $data,
            'markers' => $allMarkers,
            'timelineData' => [
                'title' => $timelineTitle,
                // Remove empty events and reset the keys.
                'events' => array_values(array_filter($timelineEvents)),
            ],
            'timelineOptions' => [
                'debug' => false,
                'timenav_position' => 'top',
            ],
        ]);
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
            'title_headline' => null,
            'title_text' => null,
            'data_type_properties' => null,
        ];
        if (isset($data['timeline']) && is_array($data['timeline'])) {
            if (isset($data['timeline']['title_headline'])) {
                $timeline['title_headline'] = $this->htmlPurifier->purify($data['timeline']['title_headline']);
            }
            if (isset($data['timeline']['title_text'])) {
                $timeline['title_text'] = $this->htmlPurifier->purify($data['timeline']['title_text']);
            }
            if (isset($data['timeline']['data_type_properties'])
                && is_array($data['timeline']['data_type_properties'])
            ) {
                foreach ($data['timeline']['data_type_properties'] as $dataTypeProperty) {
                    if (is_string($dataTypeProperty)) {
                        $dataTypeProperty = explode(':', $dataTypeProperty);
                        if (3 === count($dataTypeProperty)) {
                            list($namespace, $type, $propertyId) = $dataTypeProperty;
                            if ('numeric' === $namespace
                                && in_array($type, ['timestamp', 'interval'])
                                && is_numeric($propertyId)
                            ) {
                                $timeline['data_type_properties'][] = sprintf('%s:%s:%s', $namespace, $type, $propertyId);
                            }
                        }
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

    /**
     * Get a timeline event.
     *
     * @see https://timeline.knightlab.com/docs/json-format.html
     * @param ItemRepresentation $item
     * @param array $dataTypeProperties
     * @return array
     */
    public function getTimelineEvent($item, array $dataTypeProperties, $view)
    {
        $property = null;
        $dataType = null;
        $value = null;
        foreach ($dataTypeProperties as $dataTypeProperty) {
            $dataTypeProperty = explode(':', $dataTypeProperty);
            try {
                $property = $view->api()->read('properties', $dataTypeProperty[2])->getContent();
            } catch (NotFoundException $e) {
                // Invalid property.
                continue;
            }
            $dataType = sprintf('%s:%s', $dataTypeProperty[0], $dataTypeProperty[1]);
            $value = $item->value($property->term(), ['type' => $dataType]);
            if ($value) {
                // Set only the first matching numeric value.
                break;
            }
        }
        if (!$value) {
            // This item has no numeric values.
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
            $dateTime = Timestamp::getDateTimeFromValue($value->value());
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
            $dateTimeStart = Timestamp::getDateTimeFromValue($intervalStart);
            $event['start_date'] = [
                'year' => $dateTimeStart['year'],
                'month' => $dateTimeStart['month'],
                'day' => $dateTimeStart['day'],
                'hour' => $dateTimeStart['hour'],
                'minute' => $dateTimeStart['minute'],
                'second' => $dateTimeStart['second'],
            ];
            $dateTimeEnd = Timestamp::getDateTimeFromValue($intervalEnd);
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
}
