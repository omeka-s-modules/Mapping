<?php
namespace Mapping\Form\Fieldset;

use Laminas\Form\Fieldset;

class ItemSetsFieldset extends Fieldset
{
    public function init()
    {
        $this->add([
            'type' => 'Omeka\Form\Element\ItemSetSelect',
            'name' => 'o:block[__blockIndex__][o:data][item_sets][ids]',
            'options' => [
                'label' => 'Item sets', // @translate
                'empty_option' => '',
            ],
            'attributes' => [
                'multiple' => true,
                'class' => 'chosen-select item-set-select',
                'data-placeholder' => 'Select item sets', // @translate
            ]
        ]);
        $this->add([
            'type' => 'select',
            'name' => 'o:block[__blockIndex__][o:data][item_sets][feature_type]',
            'options' => [
                'label' => 'Item set feature type', // @translate
                'info' => 'Select the type of feature to represent each item set. Select "Polygon" for a bounding volume around the outermost features. Select "Point" for the central point of the bounding volume.',
                'value_options' => [
                    'polygon' => 'Polygon', // @translate
                    'point' => 'Point', // @translate
                ],
            ],
        ]);
    }

    public function filterBlockData(array $rawData)
    {
        $data = [
            'item_sets' => [
                'ids' => [],
                'feature_type' => 'polygon',
            ]
        ];

        if (isset($rawData['item_sets']['ids']) && is_array($rawData['item_sets']['ids'])) {
            $data['item_sets']['ids'] = $rawData['item_sets']['ids'];
        }
        if (isset($rawData['item_sets']['feature_type']) && in_array($rawData['item_sets']['feature_type'], ['polygon', 'point'])) {
            $data['item_sets']['feature_type'] = $rawData['item_sets']['feature_type'];
        }

        return $data;
    }
}
