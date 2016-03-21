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
        $conn->exec('CREATE TABLE mapping_marker (id INT AUTO_INCREMENT NOT NULL, mapping_id INT NOT NULL, lat DOUBLE PRECISION NOT NULL, lng DOUBLE PRECISION NOT NULL, `label` VARCHAR(255) DEFAULT NULL, INDEX IDX_667C9244FABB77CC (mapping_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;');
        $conn->exec('CREATE TABLE mapping (id INT AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, center_lat DOUBLE PRECISION DEFAULT NULL, center_lng DOUBLE PRECISION DEFAULT NULL, UNIQUE INDEX UNIQ_49E62C8A126F525E (item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;');
        $conn->exec('ALTER TABLE mapping_marker ADD CONSTRAINT FK_667C9244FABB77CC FOREIGN KEY (mapping_id) REFERENCES mapping (id);');
        $conn->exec('ALTER TABLE mapping ADD CONSTRAINT FK_49E62C8A126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE CASCADE;');
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $conn = $serviceLocator->get('Omeka\Connection');
        $conn->exec('DROP TABLE mapping_marker');
        $conn->exec('DROP TABLE mapping');
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
                // @todo Get mapping data.
                $event->setParam('jsonLd', $jsonLd);
            }
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.update.post',
            function (Event $event) {
                $request = $event->getParam('request');
                $jsonLd = $request->getContent();
                if (isset($jsonLd['o-module-mapping:mapping'])) {
                    // @todo Save mapping data
                }
            }
        );
    }
}

