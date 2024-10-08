<?php
namespace Mapping\Form;

use Laminas\Form\Form;
use Mapping\Form\Fieldset;

class BlockLayoutMapQueryForm extends Form
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
            'type' => Fieldset\GeojsonFieldset::class,
            'name' => 'geojson',
        ]);
        $this->add([
            'type' => Fieldset\TimelineFieldset::class,
            'name' => 'timeline',
        ]);
        $this->add([
            'type' => Fieldset\QueryFieldset::class,
            'name' => 'query',
        ]);
    }

    public function prepareBlockData(array $rawData)
    {
        $data = array_merge(
            $this->get('default_view')->filterBlockData($rawData),
            $this->get('wms_overlays')->filterBlockData($rawData),
            $this->get('geojson')->filterBlockData($rawData),
            $this->get('timeline')->filterBlockData($rawData),
            $this->get('query')->filterBlockData($rawData),
        );
        $this->setData([
            'default_view' => [
                'o:block[__blockIndex__][o:data][basemap_provider]' => $data['basemap_provider'],
                'o:block[__blockIndex__][o:data][min_zoom]' => $data['min_zoom'],
                'o:block[__blockIndex__][o:data][max_zoom]' => $data['max_zoom'],
                'o:block[__blockIndex__][o:data][scroll_wheel_zoom]' => $data['scroll_wheel_zoom'],
            ],
            'geojson' => [
                'o:block[__blockIndex__][o:data][geojson][property_key_label]' => $data['geojson']['property_key_label'],
                'o:block[__blockIndex__][o:data][geojson][property_key_comment]' => $data['geojson']['property_key_comment'],
                'o:block[__blockIndex__][o:data][geojson][show_property_list]' => $data['geojson']['show_property_list'],
                'o:block[__blockIndex__][o:data][geojson][geojson]' => $data['geojson']['geojson'],
            ],
            'timeline' => [
                'o:block[__blockIndex__][o:data][timeline][title_headline]' => $data['timeline']['title_headline'],
                'o:block[__blockIndex__][o:data][timeline][title_text]' => $data['timeline']['title_text'],
                'o:block[__blockIndex__][o:data][timeline][fly_to]' => $data['timeline']['fly_to'],
                'o:block[__blockIndex__][o:data][timeline][show_contemporaneous]' => $data['timeline']['show_contemporaneous'],
                'o:block[__blockIndex__][o:data][timeline][timenav_position]' => $data['timeline']['timenav_position'],
                'o:block[__blockIndex__][o:data][timeline][data_type_properties]' => $data['timeline']['data_type_properties'][0] ?? '',
            ],
            'query' => [
                'o:block[__blockIndex__][o:data][query]' => $data['query'],
            ],
        ]);
        return $data;
    }
}
