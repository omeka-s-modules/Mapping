<?php
namespace Mapping\Form\Fieldset;

use Laminas\Form\Fieldset;

class OverlaysFieldset extends Fieldset
{
    public function init()
    {
        $this->add([
            'type' => 'select',
            'name' => 'o:block[__blockIndex__][o:data][overlay_mode]',
            'options' => [
                'label' => 'Overlay mode', // @translate
                'empty_option' => 'Exclusive', // @translate
                'value_options' => [
                    'inclusive' => 'Inclusive', // @translate
                ],
            ],
            'attributes' => [
                'class' => 'mapping-overlay-mode-select',
            ],
        ]);
        $this->add([
            'type' => 'select',
            'name' => 'type',
            'options' => [
                'label' => 'Overlay', // @translate
                'empty_option' => 'Select overlay type', // @translate
                'value_options' => [
                    'wms' => 'Web Map Service (WMS)', // @translate
                    'iiif' => 'IIIF Georeference Annotation', // @translate
                    'geojson' => 'GeoJSON', // @translate
                ],
            ],
            'attributes' => [
                'class' => 'mapping-overlays-type-select',
            ],
        ]);
        $this->add([
            'type' => 'text',
            'name' => 'label',
            'options' => [
                'label' => 'Label', // @translate
                'show_required' => true,
            ],
            'attributes' => [
                'class' => 'mapping-overlay-label',
            ],
        ]);

        $this->add([
            'type' => 'fieldset',
            'name' => 'mapping-overlays-fieldset-wms',
            'attributes' => [
                'class' => 'mapping-overlays-fieldset-wms',
            ],
        ]);
        $fieldset = $this->get('mapping-overlays-fieldset-wms');
        $fieldset->add([
            'type' => 'url',
            'name' => 'base_url',
            'options' => [
                'label' => 'Base URL', // @translate
            ],
            'attributes' => [
                'class' => 'mapping-overlay-wms-base-url',
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
                'class' => 'mapping-overlay-wms-layers',
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
                'class' => 'mapping-overlay-wms-styles',
            ],
        ]);

        $this->add([
            'type' => 'fieldset',
            'name' => 'mapping-overlays-fieldset-iiif',
            'attributes' => [
                'class' => 'mapping-overlays-fieldset-iiif',
            ],
        ]);
        $fieldset = $this->get('mapping-overlays-fieldset-iiif');
        $fieldset->add([
            'type' => 'url',
            'name' => 'url',
            'options' => [
                'label' => 'URL', // @translate
            ],
            'attributes' => [
                'class' => 'mapping-overlay-iiif-url',
            ],
        ]);

        $this->add([
            'type' => 'fieldset',
            'name' => 'mapping-overlays-fieldset-geojson',
            'attributes' => [
                'class' => 'mapping-overlays-fieldset-geojson',
            ],
        ]);
        $fieldset = $this->get('mapping-overlays-fieldset-geojson');
        $fieldset->add([
            'type' => 'textarea',
            'name' => 'geojson',
            'options' => [
                'label' => 'GeoJSON', // @translate
            ],
            'attributes' => [
                'class' => 'mapping-overlay-geojson-geojson',
                'rows' => '10',
            ],
        ]);
        $fieldset->add([
            'type' => 'text',
            'name' => 'property_key_label',
            'options' => [
                'label' => 'Label property key', // @translate
                'info' => 'Enter the GeoJSON property key to use for the popup label, if desired.', // @translate
            ],
            'attributes' => [
                'class' => 'mapping-overlay-geojson-property-key-label',
            ],
        ]);
        $fieldset->add([
            'type' => 'text',
            'name' => 'property_key_comment',
            'options' => [
                'label' => 'Comment property key', // @translate
                'info' => 'Enter the GeoJSON property key to use for the popup comment, if desired.', // @translate
            ],
            'attributes' => [
                'class' => 'mapping-overlay-geojson-property-key-comment',
            ],
        ]);
        $fieldset->add([
            'type' => 'checkbox',
            'name' => 'show_property_list',
            'options' => [
                'label' => 'Show GeoJSON property list?', // @translate
                'info' => 'Do you want to show all the available GeoJSON properties in the popup?', // @translate
            ],
            'attributes' => [
                'class' => 'mapping-overlay-geojson-show-property-list',
            ],
        ]);
    }

    public function filterBlockData(array $rawData)
    {
        $data = [
            'overlay_mode' => '',
            'overlays' => [],
        ];

        if (isset($rawData['overlay_mode'])) {
            $data['overlay_mode'] = $rawData['overlay_mode'];
        }

        if (isset($rawData['overlays']) && is_array($rawData['overlays'])) {
            foreach ($rawData['overlays'] as $overlayData) {
                if (is_string($overlayData)) {
                    $overlayData = json_decode($overlayData, true);
                }
                if (!is_array($overlayData)) {
                    continue; // Overlay data must be an array.
                }
                if (!isset($overlayData['type'])) {
                    continue; // Overlay type is required.
                }
                if (!in_array($overlayData['type'], ['wms', 'iiif', 'geojson'])) {
                    continue; // Overlay type is invalid.
                }
                $data['overlays'][] = $overlayData;
            }
        }

        return $data;
    }
}
