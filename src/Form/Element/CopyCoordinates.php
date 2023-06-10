<?php
namespace Mapping\Form\Element;

use Omeka\Form\Element\PropertySelect;
use Laminas\Form\Element;
use Laminas\ServiceManager\ServiceLocatorInterface;

class CopyCoordinates extends Element
{
    protected $formElements;
    protected $coordinatesPropertyElement;
    protected $coordinatesOrderElement;
    protected $coordinatesDelimiterElement;
    protected $markerLabelPropertyElement;
    protected $markerLabelPropertySourceElement;
    protected $markerMediaElement;

    public function setFormElementManager(ServiceLocatorInterface  $formElements)
    {
        $this->formElements = $formElements;
    }

    public function init()
    {
        $this->setAttribute('data-collection-action', 'replace');
        $this->setLabel('Copy coordinates to markers'); // @translate
        $this->coordinatesPropertyElement = $this->formElements->get(PropertySelect::class)
            ->setName('mapping_copy_coordinates[coordinates_property]')
            ->setEmptyOption('')
            ->setAttributes([
                'class' => 'chosen-select',
                'data-placeholder' => 'Select coordinates property', // @translate
            ]);
        $this->coordinatesOrderElement = (new Element\Select('mapping_copy_coordinates[coordinates_order]'))
            ->setEmptyOption('Select coordinates order') // @translate
            ->setValueOptions([
                'latlng' => 'latitude longitude', // @translate
                'lnglat' => 'longitude latitude', // @translate
            ]);
        $this->coordinatesDelimiterElement = (new Element\Select('mapping_copy_coordinates[coordinates_delimiter]'))
            ->setEmptyOption('Select coordinates delimiter') // @translate
            ->setValueOptions([
                ',' => 'Comma (,)', // @translate
                ' ' => 'Space ( )', // @translate
                '/' => 'Slash (/)', // @translate
                ':' => 'Colon (:)', // @translate
            ]);
        $this->markerLabelPropertyElement = $this->formElements->get(PropertySelect::class)
            ->setName('mapping_copy_coordinates[marker_label_property]')
            ->setEmptyOption('')
            ->setAttributes([
                'class' => 'chosen-select',
                'data-placeholder' => 'Select marker label property', // @translate
            ]);
        $this->markerLabelPropertySourceElement = (new Element\Select('mapping_copy_coordinates[marker_label_property_source]'))
            ->setEmptyOption('Select marker label property source') // @translate
            ->setValueOptions([
                'item' => 'Item (default)', // @translate
                'media' => 'Primary media', // @translate
            ]);
        $this->markerMediaElement = (new Element\Select('mapping_copy_coordinates[marker_media]'))
            ->setEmptyOption('Select marker media') // @translate
            ->setValueOptions([
                'none' => 'No media (default)', // @translate
                'primary' => 'Primary media', // @translate
            ]);
    }

    public function getCoordinatesPropertyElement()
    {
        return $this->coordinatesPropertyElement;
    }

    public function getCoordinatesOrderElement()
    {
        return $this->coordinatesOrderElement;
    }

    public function getCoordinatesDelimiterElement()
    {
        return $this->coordinatesDelimiterElement;
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
