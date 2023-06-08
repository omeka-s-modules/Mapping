<?php
namespace mapping\Service\Delegator;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\DelegatorFactoryInterface;
use Mapping\Form\Element\CopyCoordinates;

class FormElementDelegatorFactory implements DelegatorFactoryInterface
{
    public function __invoke(ContainerInterface $container, $name, callable $callback, array $options = null) {
        $formElement = $callback();
        $formElement->addClass(CopyCoordinates::class, 'formMappingCopyCoordinates');
        return $formElement;
    }
}
