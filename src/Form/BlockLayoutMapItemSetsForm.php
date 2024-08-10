<?php
namespace Mapping\Form;

use Laminas\Form\Form;
use Mapping\Form\Fieldset;

class BlockLayoutMapItemSetsForm extends Form
{
    public function init()
    {
        $this->add([
            'type' => Fieldset\DefaultViewFieldset::class,
            'name' => 'default_view',
        ]);
        $this->add([
            'type' => Fieldset\WmsOverlaysFieldset::class,
            'name' => 'wms_overlays',
        ]);
        $this->add([
            'type' => Fieldset\ItemSetsFieldset::class,
            'name' => 'item_sets',
        ]);
    }

    public function prepareBlockData(array $rawData)
    {
        $data = array_merge(
            $this->get('default_view')->filterBlockData($rawData),
            $this->get('wms_overlays')->filterBlockData($rawData),
            $this->get('item_sets')->filterBlockData($rawData),
        );
        $this->setData([
            'default_view' => [
                'o:block[__blockIndex__][o:data][basemap_provider]' => $data['basemap_provider'],
                'o:block[__blockIndex__][o:data][min_zoom]' => $data['min_zoom'],
                'o:block[__blockIndex__][o:data][max_zoom]' => $data['max_zoom'],
                'o:block[__blockIndex__][o:data][scroll_wheel_zoom]' => $data['scroll_wheel_zoom'],
            ],
            'item_sets' => [
                'o:block[__blockIndex__][o:data][item_sets][ids]' => $data['item_sets']['ids'],
                'o:block[__blockIndex__][o:data][item_sets][feature_type]' => $data['item_sets']['feature_type'],
            ],
        ]);
        return $data;
    }
}
