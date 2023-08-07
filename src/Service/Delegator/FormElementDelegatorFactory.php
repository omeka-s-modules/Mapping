<?php
namespace mapping\Service\Delegator;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\DelegatorFactoryInterface;
use Mapping\Form\Element\CopyCoordinates;
use Mapping\Form\Element\UpdateMarkers;

class FormElementDelegatorFactory implements DelegatorFactoryInterface
{
    public function __invoke(ContainerInterface $container, $name, callable $callback, array $options = null)
    {
        $formElement = $callback();
        $formElement->addClass(CopyCoordinates::class, 'formMappingCopyCoordinates');
        $formElement->addClass(UpdateMarkers::class, 'formMappingUpdateMarkers');
        return $formElement;
    }
}
