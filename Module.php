<?php
namespace Mapping;

use Omeka\Module\AbstractModule;
use Zend\EventManager\Event;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorInterface;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);

        $acl = $this->getServiceLocator()->get('Omeka\Acl');
    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $conn = $serviceLocator->get('Omeka\Connection');
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $conn = $serviceLocator->get('Omeka\Connection');
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            ['view.add.form.after', 'view.edit.form.after'],
            function (Event $event) {
                echo $event->getTarget()->partial('mapping/index/index.phtml');
            }
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            ['view.add.section_nav', 'view.edit.section_nav'],
            function (Event $event) {
                $sectionNav = $event->getParam('section_nav');
                $sectionNav['mapping-section'] = 'Mapping';
                $event->setParam('section_nav', $sectionNav);
            }
        );
        $sharedEventManager->attach(
            'Omeka\Api\Representation\ItemRepresentation',
            'rep.resource.json',
            function (Event $event) {
                $itemRepresentation = $event->getTarget();
                $jsonLd = $event->getParam('jsonLd');
                $jsonLd['@context']['o-module-mapping'] = 'http://schema.org/';

                // @todo Get the geographical data needed to place saved markers
                // on the map. Using test data for now.
                $jsonLd['o-module-mapping:geo'] = [
                    [
                        '@type' => 'o-module-mapping:GeoCoordinates',
                        'o-module-mapping:latitude' => '39.36827914916014',
                        'o-module-mapping:longitude' => '-105.809326171875',
                        'o-module-mapping:name' => 'this is a marker label',
                    ],
                    [
                        '@type' => 'o-module-mapping:GeoCoordinates',
                        'o-module-mapping:latitude' => '25.16517336866393',
                        'o-module-mapping:longitude' => '14.425048828125',
                        'o-module-mapping:name' => 'this is yet another marker label',
                    ],
                ];
                $event->setParam('jsonLd', $jsonLd);
            }
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.update.post',
            function (Event $event) {
                $request = $event->getParam('request');
                $jsonLd = $request->getContent();
                if (isset($jsonLd['o-module-mapping:geo'])) {
                    // @todo Save marker data to the database.
                }
            }
        );
    }
}

