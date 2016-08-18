<?php
namespace Mapping\Collecting;

use Mapping\Collecting\Map;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class MapFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $mediaTypes)
    {
        $helpers = $mediaTypes->getServiceLocator()->get('ViewHelperManager');
        return new Map($helpers);
    }
}
