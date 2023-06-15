<?php
namespace Mapping\Form\Element;

use Omeka\Form\Element\PropertySelect;
use Laminas\Form\Element;
use Laminas\ServiceManager\ServiceLocatorInterface;

class CopyCoordinates extends Element
{
    protected $formElements;
    protected $copyActionElement;
    protected $coordinatesPropertyElement;
    protected $coordinatesPropertyLatElement;
    protected $coordinatesPropertyLngElement;
    protected $coordinatesOrderElement;
    protected $coordinatesDelimiterElement;
    protected $coordinatesOnDuplicateElement;
    protected $markerLabelPropertyElement;
    protected $markerLabelPropertySourceElement;
    protected $markerMediaElement;

    public function setFormElementManager(ServiceLocatorInterface  $formElements)
    {
        $this->formElements = $formElements;
    }

    public function init()
    {
        $this->copyActionElement = (new Element\Radio('mapping_copy_coordinates[copy_action]'))
            ->setValue('')
            ->setValueOptions([
                '' => '[No action]',
                [
                    'value' => 'by_property',
                    'label' => 'By one property',
                    'label_attributes' => [
                        'title' => 'By one property containing both latitude and longitude', // @translate'
                    ],
                ],
                [
                    'value' => 'by_properties',
                    'label' => 'By two properties',
                    'label_attributes' => [
                        'title' => 'By separate latitude and longitude properties', // @translate'
                    ],
                ],
            ]);
        $this->coordinatesPropertyElement = $this->formElements->get(PropertySelect::class)
            ->setName('mapping_copy_coordinates[coordinates_property]')
            ->setEmptyOption('')
            ->setAttributes([
                'class' => 'chosen-select',
                'data-placeholder' => 'Select property', // @translate
            ]);
        $this->coordinatesPropertyLatElement = $this->formElements->get(PropertySelect::class)
            ->setName('mapping_copy_coordinates[coordinates_property_lat]')
            ->setEmptyOption('')
            ->setAttributes([
                'class' => 'chosen-select',
                'data-placeholder' => 'Select latitude property', // @translate
            ]);
        $this->coordinatesPropertyLngElement = $this->formElements->get(PropertySelect::class)
            ->setName('mapping_copy_coordinates[coordinates_property_lng]')
            ->setEmptyOption('')
            ->setAttributes([
                'class' => 'chosen-select',
                'data-placeholder' => 'Select longitude property', // @translate
            ]);
        $this->coordinatesOrderElement = (new Element\Radio('mapping_copy_coordinates[coordinates_order]'))
            ->setValue('latlng')
            ->setValueOptions([
                'latlng' => 'Latitude Longitude', // @translate
                'lnglat' => 'Longitude Latitude', // @translate
            ]);
        $this->coordinatesDelimiterElement = (new Element\Radio('mapping_copy_coordinates[coordinates_delimiter]'))
            ->setValue(',')
            ->setValueOptions([
                ',' => 'Comma [,]', // @translate
                ' ' => 'Space [ ]', // @translate
                '/' => 'Slash [/]', // @translate
                ':' => 'Colon [:]', // @translate
            ]);
        $this->coordinatesOnDuplicateElement = (new Element\Radio('mapping_copy_coordinates[coordinates_on_duplicate]'))
            ->setValue('skip')
            ->setValueOptions([
                'skip' => 'Skip', // @translate
                'overwrite' => 'Overwrite', // @translate
            ]);
        $this->markerLabelPropertyElement = $this->formElements->get(PropertySelect::class)
            ->setName('mapping_copy_coordinates[marker_label_property]')
            ->setEmptyOption('')
            ->setAttributes([
                'class' => 'chosen-select',
                'data-placeholder' => 'Select label property', // @translate
            ]);
        $this->markerLabelPropertySourceElement = (new Element\Radio('mapping_copy_coordinates[marker_label_property_source]'))
            ->setValue('item')
            ->setValueOptions([
                'item' => 'Item', // @translate
                'primary_media' => 'Primary media', // @translate
            ]);
        $this->markerMediaElement = (new Element\Radio('mapping_copy_coordinates[marker_media]'))
            ->setValue('none')
            ->setValueOptions([
                'none' => 'None', // @translate
                'primary_media' => 'Primary media', // @translate
            ]);
    }

    public function getCopyActionElement()
    {
        return $this->copyActionElement;
    }

    public function getCoordinatesPropertyElement()
    {
        return $this->coordinatesPropertyElement;
    }

    public function getCoordinatesPropertyLatElement()
    {
        return $this->coordinatesPropertyLatElement;
    }

    public function getCoordinatesPropertyLngElement()
    {
        return $this->coordinatesPropertyLngElement;
    }

    public function getCoordinatesOrderElement()
    {
        return $this->coordinatesOrderElement;
    }

    public function getCoordinatesDelimiterElement()
    {
        return $this->coordinatesDelimiterElement;
    }

    public function getCoordinatesOnDuplicateElement()
    {
        return $this->coordinatesOnDuplicateElement;
    }

    public function getMarkerLabelPropertyElement()
    {
        return $this->markerLabelPropertyElement;
    }

    public function getMarkerLabelPropertySourceElement()
    {
        return $this->markerLabelPropertySourceElement;
    }

    public function getMarkerMediaElement()
    {
        return $this->markerMediaElement;
    }
}
