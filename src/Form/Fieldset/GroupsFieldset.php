<?php
namespace Mapping\Form\Fieldset;

use Laminas\Form\Fieldset;

class GroupsFieldset extends Fieldset
{
    protected $groupByTypes = [
        'item_sets' => 'Item sets', // @translate
        'resource_classes' => 'Resource classes', // @translate
        'property_values_eq' => 'Property values (is exactly)', // @translate
        'property_values_in' => 'Property values (contains)', // @translate
        'property_values_res' => 'Property values (is resource with ID)', // @translate
        'properties_ex' => 'Properties (has any value)', // @translate
    ];

    public function init()
    {
        $this->add([
            'type' => 'select',
            'name' => 'o:block[__blockIndex__][o:data][groups][type]',
            'options' => [
                'label' => 'Group by',
                'empty_option' => 'Select a type…', // @translate
                'value_options' => $this->groupByTypes,
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
            'name' => 'o:block[__blockIndex__][o:data][groups][filter_data][item_set_id]',
            'options' => [
                'label' => 'Filter by item set', // @translate
                'empty_option' => '',
            ],
            'attributes' => [
                'class' => 'chosen-select item_set_id hidden_by_default',
                'data-placeholder' => 'Select item set', // @translate
            ],
        ]);
        $this->add([
            'type' => 'Omeka\Form\Element\ResourceClassSelect',
            'name' => 'o:block[__blockIndex__][o:data][groups][filter_data][resource_class_id]',
            'options' => [
                'label' => 'Filter by resource class', // @translate
                'empty_option' => '',
            ],
            'attributes' => [
                'class' => 'chosen-select resource_class_id hidden_by_default',
                'data-placeholder' => 'Select resource class', // @translate
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
                'class' => 'chosen-select item_set_ids hidden_by_default',
                'data-placeholder' => 'Select item sets', // @translate
            ],
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
                'class' => 'chosen-select resource_class_ids hidden_by_default',
                'data-placeholder' => 'Select resource classes', // @translate
            ],
        ]);
        $this->add([
            'type' => 'Omeka\Form\Element\PropertySelect',
            'name' => 'o:block[__blockIndex__][o:data][groups][type_data][property_ids]',
            'options' => [
                'label' => 'Properties', // @translate
                'empty_option' => '',
            ],
            'attributes' => [
                'multiple' => true,
                'class' => 'chosen-select property_ids hidden_by_default',
                'data-placeholder' => 'Select properties', // @translate
            ],
        ]);
        $this->add([
            'type' => 'Omeka\Form\Element\PropertySelect',
            'name' => 'o:block[__blockIndex__][o:data][groups][type_data][property_id]',
            'options' => [
                'label' => 'Property', // @translate
                'empty_option' => '',
            ],
            'attributes' => [
                'class' => 'chosen-select property_id hidden_by_default',
                'data-placeholder' => 'Select property', // @translate
            ],
        ]);
        $this->add([
            'type' => 'textarea',
            'name' => 'o:block[__blockIndex__][o:data][groups][type_data][values]',
            'options' => [
                'label' => 'Values', // @translate
                'info' => '
                <p>Enter the values, separated by new lines. The format of each value depends on the query type:</p>
                <ul>
                    <li>"Is exactly": enter a value that is an exact match to the property value.</li>
                    <li>"Contains": enter a value that matches any part of the property value.</li>
                    <li>"Is resource with ID": enter the resource ID (item, item set, media).</li>
                </ul>', // @translate
                'escape_info' => false,
            ],
            'attributes' => [
                'class' => 'values hidden_by_default',
            ],
        ]);
    }

    public function filterBlockData(array $rawData)
    {
        $data = [
            'groups' => [
                'type' => null,
                'feature_type' => 'polygon',
                'filter_data' => [
                    'item_set_id' => null,
                    'resource_class_id' => null,
                ],
                'type_data' => [
                    'item_set_ids' => [],
                    'resource_class_ids' => [],
                    'property_ids' => [],
                    'property_id' => null,
                    'values' => null,
                ],
            ],
        ];

        if (isset($rawData['groups']['type']) && in_array($rawData['groups']['type'], array_keys($this->groupByTypes))) {
            $data['groups']['type'] = $rawData['groups']['type'];
        }
        if (isset($rawData['groups']['feature_type']) && in_array($rawData['groups']['feature_type'], ['polygon', 'point'])) {
            $data['groups']['feature_type'] = $rawData['groups']['feature_type'];
        }
        if (isset($rawData['groups']['filter_data']['item_set_id']) && is_numeric($rawData['groups']['filter_data']['item_set_id'])) {
            $data['groups']['filter_data']['item_set_id'] = $rawData['groups']['filter_data']['item_set_id'];
        }
        if (isset($rawData['groups']['filter_data']['resource_class_id']) && is_numeric($rawData['groups']['filter_data']['resource_class_id'])) {
            $data['groups']['filter_data']['resource_class_id'] = $rawData['groups']['filter_data']['resource_class_id'];
        }
        if (isset($rawData['groups']['type_data']['item_set_ids']) && is_array($rawData['groups']['type_data']['item_set_ids'])) {
            $data['groups']['type_data']['item_set_ids'] = $rawData['groups']['type_data']['item_set_ids'];
        }
        if (isset($rawData['groups']['type_data']['resource_class_ids']) && is_array($rawData['groups']['type_data']['resource_class_ids'])) {
            $data['groups']['type_data']['resource_class_ids'] = $rawData['groups']['type_data']['resource_class_ids'];
        }
        if (isset($rawData['groups']['type_data']['property_ids']) && is_array($rawData['groups']['type_data']['property_ids'])) {
            $data['groups']['type_data']['property_ids'] = $rawData['groups']['type_data']['property_ids'];
        }
        if (isset($rawData['groups']['type_data']['property_id']) && is_numeric($rawData['groups']['type_data']['property_id'])) {
            $data['groups']['type_data']['property_id'] = $rawData['groups']['type_data']['property_id'];
        }
        if (isset($rawData['groups']['type_data']['values']) && is_string($rawData['groups']['type_data']['values'])) {
            $data['groups']['type_data']['values'] = $rawData['groups']['type_data']['values'];
        }

        return $data;
    }
}
