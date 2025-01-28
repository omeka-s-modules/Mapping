<?php
namespace Mapping\Form;

use Laminas\Form\Form;

class BlockLayoutMapGroupsForm extends Form
{
    public function init()
    {
        $this->add([
            'type' => Fieldset\DefaultViewFieldset::class,
            'name' => 'default_view',
        ]);
        $this->add([
            'type' => Fieldset\GroupsFieldset::class,
            'name' => 'groups',
        ]);
    }

    public function prepareBlockData(array $rawData)
    {
        $data = array_merge(
            $this->get('default_view')->filterBlockData($rawData),
            $this->get('groups')->filterBlockData($rawData),
        );
        $this->setData([
            'default_view' => [
                'o:block[__blockIndex__][o:data][basemap_provider]' => $data['basemap_provider'],
                'o:block[__blockIndex__][o:data][min_zoom]' => $data['min_zoom'],
                'o:block[__blockIndex__][o:data][max_zoom]' => $data['max_zoom'],
                'o:block[__blockIndex__][o:data][scroll_wheel_zoom]' => $data['scroll_wheel_zoom'],
            ],
            'groups' => [
                'o:block[__blockIndex__][o:data][groups][type]' => $data['groups']['type'],
                'o:block[__blockIndex__][o:data][groups][feature_type]' => $data['groups']['feature_type'],
                'o:block[__blockIndex__][o:data][groups][filter_data][item_set_id]' => $data['groups']['filter_data']['item_set_id'],
                'o:block[__blockIndex__][o:data][groups][filter_data][resource_class_id]' => $data['groups']['filter_data']['resource_class_id'],
                'o:block[__blockIndex__][o:data][groups][type_data][item_set_ids]' => $data['groups']['type_data']['item_set_ids'],
                'o:block[__blockIndex__][o:data][groups][type_data][resource_class_ids]' => $data['groups']['type_data']['resource_class_ids'],
                'o:block[__blockIndex__][o:data][groups][type_data][property_ids]' => $data['groups']['type_data']['property_ids'],
                'o:block[__blockIndex__][o:data][groups][type_data][property_id]' => $data['groups']['type_data']['property_id'],
                'o:block[__blockIndex__][o:data][groups][type_data][values]' => $data['groups']['type_data']['values'],
            ],
        ]);
        return $data;
    }
}
