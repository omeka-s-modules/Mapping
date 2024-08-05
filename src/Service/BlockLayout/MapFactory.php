<?php
namespace Mapping\Service\BlockLayout;

use Interop\Container\ContainerInterface;
use Mapping\Site\BlockLayout\Map;
use Mapping\Site\BlockLayout\MapItemSets;
use Mapping\Site\BlockLayout\MapQuery;
use Laminas\ServiceManager\Factory\FactoryInterface;

class MapFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        switch ($requestedName) {
            case 'mappingMapQuery':
                $blockLayout = new MapQuery;
                $blockLayout->setHtmlPurifier($services->get('Omeka\HtmlPurifier'));
                $blockLayout->setModuleManager($services->get('Omeka\ModuleManager'));
                $blockLayout->setFormElementManager($services->get('FormElementManager'));
                break;
            case 'mappingMap':
                $blockLayout = new Map;
                $blockLayout->setHtmlPurifier($services->get('Omeka\HtmlPurifier'));
                $blockLayout->setModuleManager($services->get('Omeka\ModuleManager'));
                $blockLayout->setFormElementManager($services->get('FormElementManager'));
                break;
        }
        return $blockLayout;
    }
}
