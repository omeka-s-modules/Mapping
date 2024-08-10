<?php
namespace Mapping\Service\Form\Fieldset;

use Interop\Container\ContainerInterface;
use Mapping\Form\Fieldset\TimelineFieldset;
use Laminas\ServiceManager\Factory\FactoryInterface;

class TimelineFieldsetFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $fieldset = new TimelineFieldset;
        $fieldset->setHtmlPurifier($services->get('Omeka\HtmlPurifier'));
        return $fieldset;
    }
}
