<?php
namespace Mapping\Form\Fieldset;

use Laminas\Form\Fieldset;

class GroupsFieldset extends Fieldset
{
    public function init()
    {
        $this->add([
            'type' => 'select',
            'name' => 'o:block[__blockIndex__][o:data][groups][type]',
            'options' => [
                'label' => 'Groups type',
                'empty_option' => 'Select a type…', // @translate
                'value_options' => [
                    'item_sets' => 'Item sets', // @translate
                    'resource_classes' => 'Resource classes', // @translate
                ],
            ],
            'attributes' => [
                'class' => 'groups-type',
            ],
        ]);
        $this->add([
            'type' => 'select',
            'name' => 'o:block[__blockIndex__][o:data][groups][feature_type]',
            'options' => [
                'label' => 'Feature type', // @translate
                'info' => 'Select the type of feature to represent each group. Select "Polygon" for a bounding volume around the outermost features. Select "Point" for the central point of the bounding volume.',
                'value_options' => [
                    'polygon' => 'Polygon', // @translate
                    'point' => 'Point', // @translate
                ],
            ],
        ]);
        $this->add([
            'type' => 'Omeka\Form\Element\ItemSetSelect',
            'name' => 'o:block[__blockIndex__][o:data][groups][type_data][item_set_ids]',
            'options' => [
                'label' => 'Item sets', // @translate
                'empty_option' => '',
            ],
            'attributes' => [
                'multiple' => true,
                'class' => 'chosen-select item_set_ids',
                'data-placeholder' => 'Select item sets', // @translate
            ]
        ]);
        $this->add([
            'type' => 'Omeka\Form\Element\ResourceClassSelect',
            'name' => 'o:block[__blockIndex__][o:data][groups][type_data][resource_class_ids]',
            'options' => [
                'label' => 'Resource classes', // @translate
                'empty_option' => '',
            ],
            'attributes' => [
                'multiple' => true,
                'class' => 'chosen-select resource_class_ids',
                'data-placeholder' => 'Select resource classes', // @translate
            ]
        ]);
    }

    public function filterBlockData(array $rawData)
    {
        $data = [
            'groups' => [
                'type' => null,
                'feature_type' => 'polygon',
                'type_data' => [
                    'item_set_ids' => [],
                    'resource_class_ids' => [],
                ],
            ]
        ];

        if (isset($rawData['groups']['type']) && in_array($rawData['groups']['type'], ['item_sets', 'resource_classes'])) {
            $data['groups']['type'] = $rawData['groups']['type'];
        }
        if (isset($rawData['groups']['feature_type']) && in_array($rawData['groups']['feature_type'], ['polygon', 'point'])) {
            $data['groups']['feature_type'] = $rawData['groups']['feature_type'];
        }
        if (isset($rawData['groups']['type_data']['item_set_ids']) && is_array($rawData['groups']['type_data']['item_set_ids'])) {
            $data['groups']['type_data']['item_set_ids'] = $rawData['groups']['type_data']['item_set_ids'];
        }
        if (isset($rawData['groups']['type_data']['resource_class_ids']) && is_array($rawData['groups']['type_data']['resource_class_ids'])) {
            $data['groups']['type_data']['resource_class_ids'] = $rawData['groups']['type_data']['resource_class_ids'];
        }

        return $data;
    }
}
