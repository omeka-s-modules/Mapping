<?php
namespace Mapping\Service\Form\Element;

use Interop\Container\ContainerInterface;
use Mapping\Form\Element\UpdateMarkers;
use Laminas\ServiceManager\Factory\FactoryInterface;

class UpdateMarkersFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $element = new UpdateMarkers;
        $element->setFormElementManager($services->get('FormElementManager'));
        return $element;
    }
}
