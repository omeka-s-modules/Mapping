<?php
namespace Mapping\Form;

use Laminas\Form\Form;
use Mapping\Module;

class BlockLayoutForm extends Form
{
    public function init()
    {
        // Fieldset : default_view
        $this->add([
            'type' => 'fieldset',
            'name' => 'default_view',
        ]);
        $fieldset = $this->get('default_view');
        $fieldset->add([
            'type' => 'select',
            'name' => 'o:block[__blockIndex__][o:data][basemap_provider]',
            'options' => [
                'label' => 'Basemap provider', // @translate
                'info' => 'Select the basemap provider. The default is OpenStreetMap.Mapnik. These providers are offered AS-IS. There is no guarantee of service or speed.', // @translate
                'empty_option' => '[Default provider]',
                'value_options' => Module::BASEMAP_PROVIDERS,
            ],
            'attributes' => [
                'class' => 'basemap-provider',
            ],
        ]);
        $fieldset->add([
            'type' => 'number',
            'name' => 'o:block[__blockIndex__][o:data][min_zoom]',
            'options' => [
                'label' => 'Minimum zoom level', // @translate
                'info' => 'Set the minimum zoom level down to which the map will be displayed. The default is 0.', // @translate
            ],
            'attributes' => [
                'class' => 'min-zoom',
                'min' => '0',
                'step' => '1',
                'placeholder' => '0',
            ],
        ]);
        $fieldset->add([
            'type' => 'number',
            'name' => 'o:block[__blockIndex__][o:data][max_zoom]',
            'options' => [
                'label' => 'Maximum zoom level', // @translate
                'info' => 'Set the maximum zoom level up to which the map will be displayed. The default is 19.', // @translate
            ],
            'attributes' => [
                'class' => 'max-zoom',
                'min' => '0',
                'step' => '1',
                'placeholder' => '19',
            ],
        ]);
        $fieldset->add([
            'type' => 'select',
            'name' => 'o:block[__blockIndex__][o:data][scroll_wheel_zoom]',
            'options' => [
                'label' => 'Scroll wheel zoom', // @translate
                'info' => 'Set whether users can zoom with their mouse wheel when hovering over the map, either automatically upon page load or after clicking inside the map.', // @translate
                'empty_option' => 'Enabled',
                'value_options' => [
                    'disable' => 'Disabled', // @translate
                    'click' => 'Disabled until map click', // @translate
                ],
            ],
            'attributes' => [
                'class' => 'scroll-wheel-zoom',
            ],
        ]);

        // Fieldset : wms_overlays
        $this->add([
            'type' => 'fieldset',
            'name' => 'wms_overlays',
        ]);
        $fieldset = $this->get('wms_overlays');
        $fieldset->add([
            'type' => 'text',
            'name' => 'label',
            'options' => [
                'label' => 'Label', // @translate
            ],
            'attributes' => [
                'class' => 'mapping-wms-label',
            ],
        ]);
        $fieldset->add([
            'type' => 'text',
            'name' => 'base_url',
            'options' => [
                'label' => 'Base URL', // @translate
            ],
            'attributes' => [
                'class' => 'mapping-wms-base-url',
            ],
        ]);
        $fieldset->add([
            'type' => 'text',
            'name' => 'layers',
            'options' => [
                'label' => 'Layers', // @translate
                'info' => 'The WMS layers, if any (comma-separated).', // @translate
            ],
            'attributes' => [
                'class' => 'mapping-wms-layers',
            ],
        ]);
        $fieldset->add([
            'type' => 'text',
            'name' => 'styles',
            'options' => [
                'label' => 'Styles', // @translate
                'info' => 'The WMS styles, if any (comma-separated).', // @translate
            ],
            'attributes' => [
                'class' => 'mapping-wms-styles',
            ],
        ]);

        // Fieldset : timeline
        $this->add([
            'type' => 'fieldset',
            'name' => 'timeline',
        ]);
        $fieldset = $this->get('timeline');
        $fieldset->add([
            'type' => 'text',
            'name' => 'o:block[__blockIndex__][o:data][timeline][title_headline]',
            'options' => [
                'label' => 'Title headline', // @translate
            ],
        ]);
        $fieldset->add([
            'type' => 'textarea',
            'name' => 'o:block[__blockIndex__][o:data][timeline][title_text]',
            'options' => [
                'label' => 'Title text', // @translate
            ],
            'attributes' => [
                'class' => 'block-html full wysiwyg',
            ],
        ]);
        $fieldset->add([
            'type' => 'select',
            'name' => 'o:block[__blockIndex__][o:data][timeline][fly_to]',
            'options' => [
                'label' => 'Fly to', // @translate
                'info' => 'Select the map view to fly to when navigating between events.', // @translate
                'empty_option' => 'Default view', // @translate
                'value_options' => [
                    '0' => 'Event marker, zoom 0', // @translate
                    '2' => 'Event marker, zoom 2', // @translate
                    '4' => 'Event marker, zoom 4', // @translate
                    '6' => 'Event marker, zoom 6', // @translate
                    '8' => 'Event marker, zoom 8', // @translate
                    '10' => 'Event marker, zoom 10', // @translate
                    '12' => 'Event marker, zoom 12', // @translate
                    '14' => 'Event marker, zoom 14', // @translate
                    '16' => 'Event marker, zoom 16', // @translate
                    '18' => 'Event marker, zoom 18', // @translate
                ],
            ],
        ]);
        $fieldset->add([
            'type' => 'checkbox',
            'name' => 'o:block[__blockIndex__][o:data][timeline][show_contemporaneous]',
            'options' => [
                'label' => 'Show contemporaneous events?', // @translate
                'info' => 'Check this if you want to show all events on the map that exist in the same time period as the current event (default view only).', // @translate
            ],
        ]);
        $fieldset->add([
            'type' => 'select',
            'name' => 'o:block[__blockIndex__][o:data][timeline][timenav_position]',
            'options' => [
                'label' => 'Timeline navigation position', // @translate
                'info' => 'Select the position of the timeline navigation.', // @translate
                'value_options' => [
                    'full_width_below' => 'Full width, below story slider and map', // @translate
                    'full_width_above' => 'Full width, above story slider and map', // @translate
                ],
            ],
        ]);
        $fieldset->add([
            'type' => 'NumericDataTypes\Form\Element\NumericPropertySelect',
            'name' => 'o:block[__blockIndex__][o:data][timeline][data_type_properties]',
            'options' => [
                'label' => 'Property', // @translate
                'info' => 'Select the timestamp or interval property to use when populating the timeline.', // @translate
                'empty_option' => '',
                'numeric_data_type' => ['timestamp', 'interval'],
                'numeric_data_type_disambiguate' => true,
            ],
            'attributes' => [
                'class' => 'chosen-select',
                'data-placeholder' => 'Select property…', // @translate
            ],
        ]);

        // Fieldset : query
        $this->add([
            'type' => 'fieldset',
            'name' => 'query',
        ]);
        $fieldset = $this->get('query');
        $fieldset->add([
            'type' => 'Omeka\Form\Element\Query',
            'name' => 'o:block[__blockIndex__][o:data][query]',
            'options' => [
                'label' => 'Query', // @translate
                'info' => 'Attach items using this query. No query means all items.', // @translate
            ],
        ]);
    }
}
