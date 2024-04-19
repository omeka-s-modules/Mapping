<?php
namespace Mapping\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Stdlib\ErrorStore;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;

class MapGeoJson extends AbstractMap
{
    public function getLabel()
    {
        return 'Map by GeoJSON'; // @translate
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {
        $block->setData($this->filterBlockData($block->getData()));
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null
    ) {
        $data = $this->filterBlockData($block ? $block->data() : []);

        $labelElement = (new Element\Text('o:block[__blockIndex__][o:data][geojson_property_key_label]'))
            ->setLabel($view->translate('Label property key'))
            ->setOption('info', $view->translate('Enter the GeoJSON property key used for the popup label, if any.'))
            ->setValue($data['geojson_property_key_label'] ?? null);
        $commentElement = (new Element\Text('o:block[__blockIndex__][o:data][geojson_property_key_comment]'))
            ->setLabel($view->translate('Comment property key'))
            ->setOption('info', $view->translate('Enter the GeoJSON property key used for the popup comment, if any.'))
            ->setValue($data['geojson_property_key_comment'] ?? null);
        $showPropertyListElement = (new Element\Checkbox('o:block[__blockIndex__][o:data][geojson_show_property_list]'))
            ->setLabel($view->translate('Show GeoJSON property list?'))
            ->setOption('info', $view->translate('Do you want to show the GeoJSON property list if available?'))
            ->setValue($data['geojson_show_property_list'] ?? false);
        $geojsonElement = (new Element\Textarea('o:block[__blockIndex__][o:data][geojson]'))
            ->setLabel($view->translate('GeoJSON'))
            ->setValue($data['geojson'] ?? null)
            ->setAttribute('rows', '18');
        $fieldset = (new Fieldset('geojson'))
            ->add($labelElement)
            ->add($commentElement)
            ->add($showPropertyListElement)
            ->add($geojsonElement);

        return sprintf(
            '%s<a href="#" class="mapping-map-expander expand"><h4>%s</h4></a><div class="collapsible">%s</div>',
            parent::form($view, $site, $page, $block),
            $view->translate('GeoJSON'),
            $view->formCollection($fieldset, false)
        );
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $data = $this->filterBlockData($block->data());

        return $view->partial('common/block-layout/mapping-block', [
            'data' => $data,
            'features' => [],
            'isTimeline' => false,
            'timelineData' => null,
            'timelineOptions' => null,
        ]);
    }

    protected function filterBlockData($data)
    {
        $geojson = $data['geojson'] ?? null;
        $labelKey = $data['geojson_property_key_label'] ?? null;
        $commentKey = $data['geojson_property_key_comment'] ?? null;
        $showPropertyList = $data['geojson_show_property_list'] ?? false;
        $data = parent::filterBlockData($data);
        $data['geojson'] = $geojson;
        $data['geojson_property_key_label'] = $labelKey;
        $data['geojson_property_key_comment'] = $commentKey;
        $data['geojson_show_property_list'] = $showPropertyList;
        return $data;
    }
}
