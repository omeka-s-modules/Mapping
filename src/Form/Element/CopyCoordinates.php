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
}
