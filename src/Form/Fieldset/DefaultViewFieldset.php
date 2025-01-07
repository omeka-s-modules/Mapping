<?php
namespace Mapping\Form\Fieldset;

use Laminas\Form\Fieldset;
use Mapping\Module;

class DefaultViewFieldset extends Fieldset
{
    public function init()
    {
        $this->add([
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
        $this->add([
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
        $this->add([
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
        $this->add([
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
    }

    public function filterBlockData(array $rawData)
    {
        $data = [
            'basemap_provider' => null,
            'min_zoom' => null,
            'max_zoom' => null,
            'scroll_wheel_zoom' => '',
            'bounds' => null,
        ];

        if (isset($rawData['basemap_provider']) && array_key_exists($rawData['basemap_provider'], Module::BASEMAP_PROVIDERS)) {
            $data['basemap_provider'] = $rawData['basemap_provider'];
        }
        if (isset($rawData['min_zoom']) && is_numeric($rawData['min_zoom'])) {
            $data['min_zoom'] = $rawData['min_zoom'];
        }
        if (isset($rawData['max_zoom']) && is_numeric($rawData['max_zoom'])) {
            $data['max_zoom'] = $rawData['max_zoom'];
        }
        if (isset($rawData['scroll_wheel_zoom'])) {
            $data['scroll_wheel_zoom'] = $rawData['scroll_wheel_zoom'];
        }
        if (isset($rawData['bounds']) && 4 === count(array_filter(explode(',', $rawData['bounds']), 'is_numeric'))) {
            $data['bounds'] = $rawData['bounds'];
        }

        return $data;
    }
}
