<?php
namespace Mapping\Form\Fieldset;

use Laminas\Form\Fieldset;

class WmsOverlaysFieldset extends Fieldset
{
    public function init()
    {
        $this->add([
            'type' => 'text',
            'name' => 'label',
            'options' => [
                'label' => 'Label', // @translate
            ],
            'attributes' => [
                'class' => 'mapping-wms-label',
            ],
        ]);
        $this->add([
            'type' => 'text',
            'name' => 'base_url',
            'options' => [
                'label' => 'Base URL', // @translate
            ],
            'attributes' => [
                'class' => 'mapping-wms-base-url',
            ],
        ]);
        $this->add([
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
        $this->add([
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
    }

    public function filterBlockData(array $rawData)
    {
        $data = [
            'wms' => [],
        ];

        if (isset($rawData['wms']) && is_array($rawData['wms'])) {
            foreach ($rawData['wms'] as $wmsOverlay) {
                // WMS data must have label and base URL.
                if (is_array($wmsOverlay) && isset($wmsOverlay['label']) && isset($wmsOverlay['base_url'])) {
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

                    $data['wms'][] = $wmsOverlay;
                }
            }
        }

        return $data;
    }
}
