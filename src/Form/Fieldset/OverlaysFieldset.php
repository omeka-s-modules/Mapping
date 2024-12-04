<?php
namespace Mapping\Form\Fieldset;

use Laminas\Form\Fieldset;
use Mapping\Module;

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
            ],
            'attributes' => [
                'class' => 'mapping-overlay-label',
            ],
        ]);

        $this->add([
            'type' => 'fieldset',
            'name' => 'mapping-overlays-fieldset-wms',
            'attributes' => [
                'class' => 'mapping-overlays-fieldset-wms'
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
                'class' => 'mapping-overlays-fieldset-iiif'
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
                if (!in_array($overlayData['type'], ['wms', 'iiif'])) {
                    continue; // Overlay type is invalid.
                }
                $data['overlays'][] = $overlayData;
            }
        }

        return $data;
    }
}
