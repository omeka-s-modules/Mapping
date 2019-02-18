<?php
namespace Mapping\Service\BlockLayout;

use Interop\Container\ContainerInterface;
use Mapping\Site\BlockLayout\Map;
use Zend\ServiceManager\Factory\FactoryInterface;

class MapFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Map($services->get('Omeka\HtmlPurifier'));
    }
}
