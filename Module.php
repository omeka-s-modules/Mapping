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
        $acl->allow(
            null,
            'Mapping\Api\Adapter\MappingMarkerAdapter',
            ['search', 'read']
        );

    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $conn = $serviceLocator->get('Omeka\Connection');
        $conn->exec('CREATE TABLE mapping_marker (id INT AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, lat DOUBLE PRECISION NOT NULL, lng DOUBLE PRECISION NOT NULL, `label` VARCHAR(255) DEFAULT NULL, INDEX IDX_667C9244126F525E (item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;');
        $conn->exec('ALTER TABLE mapping_marker ADD CONSTRAINT FK_667C9244126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE CASCADE;');
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $conn = $serviceLocator->get('Omeka\Connection');
        $conn->exec('DROP TABLE mapping_marker');
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
                $item = $event->getTarget();
                $jsonLd = $event->getParam('jsonLd');
                $response = $event->getParam('services')
                    ->get('Omeka\ApiManager')
                    ->search('mapping_markers', ['item_id' => $item->id()]);
                $jsonLd['o-module-mapping:marker'] = $response->getContent();
                $event->setParam('jsonLd', $jsonLd);
            }
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            ['api.create.post', 'api.update.post'],
            [$this, 'handleMarkers']
        );
    }

    public function handleMarkers(Event $event)
    {
        $request = $event->getParam('request');
        $itemAdapter = $event->getTarget();
        if ($itemAdapter->shouldHydrate($request, 'o-module-mapping:marker')) {

            $item = $event->getParam('response')->getContent();
            $em = $this->getServiceLocator()->get('Omeka\EntityManager');
            $api = $this->getServiceLocator()->get('Omeka\ApiManager');

            // Get all marker IDs already assigned to this item.
            $sql = 'SELECT mm.id FROM Mapping\Entity\MappingMarker mm WHERE mm.item = ?1';
            $query = $em->createQuery($sql)->setParameter(1, $item->id());
            $existingMarkerIds = array_map('current', $query->getResult());
            $retainMarkerIds = [];

            // Update and create markers passed in the request.
            $markersData = $request->getValue('o-module-mapping:marker', []);
            foreach ($markersData as $markerData) {
                $markerData['o:item']['o:id'] = $item->id();
                if (isset($markerData['o:id'])) {
                    $response = $api->update('mapping_markers', $markerData['o:id'], $markerData);
                    $retainMarkerIds[] = $markerData['o:id'];
                } else {
                    $response = $api->create('mapping_markers', $markerData);
                }
                if ($response->isError()) {
                    // @todo fail silently?
                }
            }

            // Delete markers not passed in the request.
            foreach ($existingMarkerIds as $existingMarkerId) {
                if (!in_array($existingMarkerId, $retainMarkerIds)) {
                    $response = $api->delete('mapping_markers', $existingMarkerId);
                    if ($response->isError()) {
                        // @todo fail silently?
                    }
                }
            }
        }
    }
}

