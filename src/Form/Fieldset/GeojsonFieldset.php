<?php
namespace Mapping\Form\Fieldset;

use Laminas\Form\Fieldset;
use Mapping\Module;

class GeojsonFieldset extends Fieldset
{
    public function init()
    {
        $this->add([
            'type' => 'text',
            'name' => 'o:block[__blockIndex__][o:data][geojson][property_key_label]',
            'options' => [
                'label' => 'Label property key', // @translate
                'info' => 'Enter the GeoJSON property key used for the popup label, if any.', // @translate
            ],
        ]);
        $this->add([
            'type' => 'text',
            'name' => 'o:block[__blockIndex__][o:data][geojson][property_key_comment]',
            'options' => [
                'label' => 'Label property key', // @translate
                'info' => 'Enter the GeoJSON property key used for the popup comment, if any.', // @translate
            ],
        ]);
        $this->add([
            'type' => 'checkbox',
            'name' => 'o:block[__blockIndex__][o:data][geojson][show_property_list]',
            'options' => [
                'label' => 'Show GeoJSON property list?', // @translate
                'info' => 'Do you want to show the GeoJSON property list if available?', // @translate
            ],
        ]);
        $this->add([
            'type' => 'textarea',
            'name' => 'o:block[__blockIndex__][o:data][geojson][geojson]',
            'options' => [
                'label' => 'GeoJSON', // @translate
            ],
            'attributes' => [
                'rows' => '18',
            ],
        ]);
    }

    public function filterBlockData(array $rawData)
    {
        $data = [
            'geojson' => [
                'property_key_label' => null,
                'property_key_comment' => null,
                'show_property_list' => false,
                'geojson' => null,
            ]
        ];

        if (isset($rawData['geojson']['property_key_label']) && is_string($rawData['geojson']['property_key_label'])) {
            $data['geojson']['property_key_label'] = $rawData['geojson']['property_key_label'];
        }
        if (isset($rawData['geojson']['property_key_comment']) && is_string($rawData['geojson']['property_key_comment'])) {
            $data['geojson']['property_key_comment'] = $rawData['geojson']['property_key_comment'];
        }
        if (isset($rawData['geojson']['show_property_list'])) {
            $data['geojson']['show_property_list'] = (bool) $rawData['geojson']['show_property_list'];
        }
        if (isset($rawData['geojson']['geojson']) && is_string($rawData['geojson']['geojson'])) {
            $data['geojson']['geojson'] = $rawData['geojson']['geojson'];
        }

        return $data;
    }
}
