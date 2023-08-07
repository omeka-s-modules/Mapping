<?php
namespace Mapping;

use Doctrine\ORM\Events;
use Mapping\Db\Event\Listener\DetachOrphanMappings;
use Omeka\Api\Exception as ApiException;
use Omeka\Api\Request;
use Mapping\Entity\MappingMarker;
use Mapping\Form\Element\CopyCoordinates;
use Mapping\Form\Element\UpdateMarkers;
use Omeka\Module\AbstractModule;
use Omeka\Permissions\Acl;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceLocatorInterface;

class Module extends AbstractModule
{
    /**
     * Excludes providers that require API keys, access tokens, etc. Excludes
     * providers with limited bounds.
     */
    const BASEMAP_PROVIDERS = [
        'OpenStreetMap.Mapnik' => 'OpenStreetMap.Mapnik',
        'OpenStreetMap.DE' => 'OpenStreetMap.DE',
        'OpenStreetMap.France' => 'OpenStreetMap.France',
        'OpenStreetMap.HOT' => 'OpenStreetMap.HOT',
        'OpenTopoMap' => 'OpenTopoMap',
        'CyclOSM' => 'CyclOSM',
        'OpenMapSurfer.Roads' => 'OpenMapSurfer.Roads',
        'OpenMapSurfer.Hybrid' => 'OpenMapSurfer.Hybrid',
        'OpenMapSurfer.AdminBounds' => 'OpenMapSurfer.AdminBounds',
        'OpenMapSurfer.Hillshade' => 'OpenMapSurfer.Hillshade',
        'Stamen.Toner' => 'Stamen.Toner',
        'Stamen.TonerBackground' => 'Stamen.TonerBackground',
        'Stamen.TonerHybrid' => 'Stamen.TonerHybrid',
        'Stamen.TonerLines' => 'Stamen.TonerLines',
        'Stamen.TonerLabels' => 'Stamen.TonerLabels',
        'Stamen.TonerLite' => 'Stamen.TonerLite',
        'Stamen.Watercolor' => 'Stamen.Watercolor',
        'Stamen.Terrain' => 'Stamen.Terrain',
        'Stamen.TerrainBackground' => 'Stamen.TerrainBackground',
        'Stamen.TerrainLabels' => 'Stamen.TerrainLabels',
        'Esri.WorldStreetMap' => 'Esri.WorldStreetMap',
        'Esri.DeLorme' => 'Esri.DeLorme',
        'Esri.WorldTopoMap' => 'Esri.WorldTopoMap',
        'Esri.WorldImagery' => 'Esri.WorldImagery',
        'Esri.WorldTerrain' => 'Esri.WorldTerrain',
        'Esri.WorldShadedRelief' => 'Esri.WorldShadedRelief',
        'Esri.WorldPhysical' => 'Esri.WorldPhysical',
        'Esri.OceanBasemap' => 'Esri.OceanBasemap',
        'Esri.NatGeoWorldMap' => 'Esri.NatGeoWorldMap',
        'Esri.WorldGrayCanvas' => 'Esri.WorldGrayCanvas',
        'MtbMap' => 'MtbMap',
        'CartoDB.Positron' => 'CartoDB.Positron',
        'CartoDB.PositronNoLabels' => 'CartoDB.PositronNoLabels',
        'CartoDB.PositronOnlyLabels' => 'CartoDB.PositronOnlyLabels',
        'CartoDB.DarkMatter' => 'CartoDB.DarkMatter',
        'CartoDB.DarkMatterNoLabels' => 'CartoDB.DarkMatterNoLabels',
        'CartoDB.DarkMatterOnlyLabels' => 'CartoDB.DarkMatterOnlyLabels',
        'CartoDB.Voyager' => 'CartoDB.Voyager',
        'CartoDB.VoyagerNoLabels' => 'CartoDB.VoyagerNoLabels',
        'CartoDB.VoyagerOnlyLabels' => 'CartoDB.VoyagerOnlyLabels',
        'CartoDB.VoyagerLabelsUnder' => 'CartoDB.VoyagerLabelsUnder',
        'HikeBike.HikeBike' => 'HikeBike.HikeBike',
        'HikeBike.HillShading' => 'HikeBike.HillShading',
        'Wikimedia' => 'Wikimedia',
    ];

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);

        // Set the corresponding visibility rules on Mapping resources.
        $em = $this->getServiceLocator()->get('Omeka\EntityManager');
        $filter = $em->getFilters()->getFilter('resource_visibility');
        $filter->addRelatedEntity('Mapping\Entity\Mapping', 'item_id');
        $filter->addRelatedEntity('Mapping\Entity\MappingMarker', 'item_id');

        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        $acl->allow(
            null,
            'Mapping\Controller\Site\Index'
        );
        $acl->allow(
            [Acl::ROLE_AUTHOR,
                Acl::ROLE_EDITOR,
                Acl::ROLE_GLOBAL_ADMIN,
                Acl::ROLE_REVIEWER,
                Acl::ROLE_SITE_ADMIN,
            ],
            ['Mapping\Api\Adapter\MappingMarkerAdapter',
             'Mapping\Api\Adapter\MappingAdapter',
             'Mapping\Entity\MappingMarker',
             'Mapping\Entity\Mapping',
            ]
        );

        $acl->allow(
            null,
            ['Mapping\Api\Adapter\MappingMarkerAdapter',
                'Mapping\Api\Adapter\MappingAdapter',
                'Mapping\Entity\MappingMarker',
            ],
            ['show', 'browse', 'read', 'search']
            );

        $em = $this->getServiceLocator()->get('Omeka\EntityManager');
        $em->getEventManager()->addEventListener(
            Events::preFlush,
            new DetachOrphanMappings
        );
    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $conn = $serviceLocator->get('Omeka\Connection');
        $conn->exec('CREATE TABLE mapping_marker (id INT AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, media_id INT DEFAULT NULL, lat DOUBLE PRECISION NOT NULL, lng DOUBLE PRECISION NOT NULL, `label` VARCHAR(255) DEFAULT NULL, INDEX IDX_667C9244126F525E (item_id), INDEX IDX_667C9244EA9FDD75 (media_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;');
        $conn->exec('CREATE TABLE mapping (id INT AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, bounds VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_49E62C8A126F525E (item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;');
        $conn->exec('ALTER TABLE mapping_marker ADD CONSTRAINT FK_667C9244126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE CASCADE;');
        $conn->exec('ALTER TABLE mapping_marker ADD CONSTRAINT FK_667C9244EA9FDD75 FOREIGN KEY (media_id) REFERENCES media (id) ON DELETE SET NULL;');
        $conn->exec('ALTER TABLE mapping ADD CONSTRAINT FK_49E62C8A126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE CASCADE;');
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $conn = $serviceLocator->get('Omeka\Connection');
        $conn->exec('DROP TABLE IF EXISTS mapping;');
        $conn->exec('DROP TABLE IF EXISTS mapping_marker');
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        // Add the map form to the item add and edit pages.
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.add.form.after',
            [$this, 'handleViewFormAfter']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.edit.form.after',
            [$this, 'handleViewFormAfter']
        );
        // Add the map to the item show page.
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.show.after',
            [$this, 'handleViewShowAfter']
        );
        // Add the mapping fields to advanced search pages.
        $sharedEventManager->attach(
            'Mapping\Controller\Site\Index',
            'view.advanced_search',
            [$this, 'filterMapBrowseAdvancedSearch']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.advanced_search',
            [$this, 'filterItemAdvancedSearch']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Site\Item',
            'view.advanced_search',
            [$this, 'filterItemAdvancedSearch']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Query',
            'view.advanced_search',
            [$this, 'filterItemAdvancedSearch']
        );
        $sharedEventManager->attach(
            '*',
            'view.search.filters',
            [$this, 'filterSearchFilters']
         );
        // Add the "has_markers" filter to item search.
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.search.query',
            [$this, 'handleApiSearchQuery']
        );
        // Add the Mapping term definition.
        $sharedEventManager->attach(
            '*',
            'api.context',
            [$this, 'filterApiContext']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.add.section_nav',
            [$this, 'addMapTab']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.edit.section_nav',
            [$this, 'addMapTab']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.show.section_nav',
            [$this, 'addMapTab']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Representation\ItemRepresentation',
            'rep.resource.json',
            [$this, 'filterItemJsonLd']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.hydrate.post',
            [$this, 'handleMapping']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.hydrate.post',
            [$this, 'handleMarkers']
        );
        $sharedEventManager->attach(
            'Omeka\Form\SiteSettingsForm',
            'form.add_elements',
            [$this, 'addSiteSettings']
        );
        $sharedEventManager->attach(
            'Omeka\Form\ResourceBatchUpdateForm',
            'form.add_elements',
            function (Event $event) {
                $form = $event->getTarget();
                if ('item' !== $form->getOption('resource_type')) {
                    return; // Include elements only on item batch edit.
                }

                $groups = $form->getOption('element_groups');
                $groups['mapping'] = 'Mapping'; // @translate
                $form->setOption('element_groups', $groups);

                $form->add([
                    'type' => 'checkbox',
                    'name' => 'mapping_delete_markers',
                    'options' => [
                        'element_group' => 'mapping',
                        'label' => 'Delete markers', // @translate
                    ],
                ]);
                $form->add([
                    'type' => CopyCoordinates::class,
                    'name' => 'mapping_copy_coordinates',
                    'options' => [
                        'element_group' => 'mapping',
                        'label' => 'Copy coordinates to markers', // @translate
                    ],
                ]);
                $form->add([
                    'type' => UpdateMarkers::class,
                    'name' => 'mapping_update_markers',
                    'options' => [
                        'element_group' => 'mapping',
                        'label' => 'Update markers', // @translate
                    ],
                ]);
            }
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.preprocess_batch_update',
            function (Event $event) {
                $data = $event->getParam('data');
                $rawData = $event->getParam('request')->getContent();
                if (isset($rawData['mapping_delete_markers'])) {
                    $data['mapping_delete_markers'] = $rawData['mapping_delete_markers'];
                }
                if ($this->copyCoordinatesDataIsValid($rawData)) {
                    $data['mapping_copy_coordinates'] = $rawData['mapping_copy_coordinates'];
                }
                if ($this->updateMarkersDataIsValid($rawData)) {
                    $data['mapping_update_markers'] = $rawData['mapping_update_markers'];
                }
                $event->setParam('data', $data);
            }
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.update.post',
            [$this, 'deleteMarkers'],
            30
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.update.post',
            [$this, 'copyCoordinates'],
            20
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.update.post',
            [$this, 'updateMarkers'],
            10
        );
    }

    public function addSiteSettings(Event $event)
    {
        $services = $this->getServiceLocator();
        $siteSettings = $services->get('Omeka\Settings\Site');
        $form = $event->getTarget();

        $groups = $form->getOption('element_groups');
        $groups['mapping'] = 'Mapping'; // @translate
        $form->setOption('element_groups', $groups);

        $form->add([
            'type' => 'checkbox',
            'name' => 'mapping_advanced_search_add_marker_presence',
            'options' => [
                'element_group' => 'mapping',
                'label' => 'Add marker presence to advanced search',
            ],
            'attributes' => [
                'value' => $siteSettings->get('mapping_advanced_search_add_marker_presence'),
            ],
        ]);
        $form->add([
            'type' => 'checkbox',
            'name' => 'mapping_advanced_search_add_geographic_location',
            'options' => [
                'element_group' => 'mapping',
                'label' => 'Add geographic location to advanced search',
            ],
            'attributes' => [
                'value' => $siteSettings->get('mapping_advanced_search_add_geographic_location'),
            ],
        ]);
    }

    public function handleViewFormAfter(Event $event)
    {
        echo $event->getTarget()->partial('mapping/index/form');
    }

    public function handleViewShowAfter(Event $event)
    {
        echo $event->getTarget()->partial('mapping/index/show');
    }

    public function filterMapBrowseAdvancedSearch(Event $event)
    {
        $partials = $event->getParam('partials');
        // Remove any unneeded partials.
        $removePartials = ['common/advanced-search/sort'];
        $partials = array_diff($partials, $removePartials);
        // Put geographic location fields at the beginning of the form.
        array_unshift($partials, 'common/advanced-search/mapping-item-geographic-location');
        $event->setParam('partials', $partials);
    }

    public function filterItemAdvancedSearch(Event $event)
    {
        $services = $this->getServiceLocator();
        $status = $services->get('Omeka\Status');
        $siteSettings = $services->get('Omeka\Settings\Site');
        $partials = $event->getParam('partials');

        // Conditionally add the marker presence field.
        if ($status->isAdminRequest() || ($status->isSiteRequest() && $siteSettings->get('mapping_advanced_search_add_marker_presence'))) {
            $partials[] = 'common/advanced-search/mapping-item-marker-presence';
        }
        // Conditionally add the geographic location fields.
        if ($status->isAdminRequest() || ($status->isSiteRequest() && $siteSettings->get('mapping_advanced_search_add_geographic_location'))) {
            $partials[] = 'common/advanced-search/mapping-item-geographic-location';
        }
        $event->setParam('partials', $partials);
    }

    public function filterSearchFilters(Event $event)
    {
        $view = $event->getTarget();
        $query = $event->getParam('query');
        $filters = $event->getParam('filters');

        // Add the marker presence search filter label.
        if (isset($query['has_markers']) && in_array($query['has_markers'], ['0', '1'])) {
            $filterLabel = $view->translate('Map marker presence');
            $filters[$filterLabel][] = $query['has_markers'] ? $view->translate('Has map markers') : $view->translate('Has no map markers');
        }
        // Add the geographic location search filter label.
        $address = $query['mapping_address'] ?? null;
        $radius = $query['mapping_radius'] ?? null;
        $radiusUnit = $query['mapping_radius_unit'] ?? null;
        if (isset($address) && '' !== trim($address) && isset($radius) && is_numeric($radius)) {
            $filterLabel = $view->translate('Geographic location');
            $filters[$filterLabel][] = sprintf('%s (%s %s)', $address, $radius, $radiusUnit);
        }
        $event->setParam('filters', $filters);
    }

    public function handleApiSearchQuery(Event $event)
    {
        $itemAdapter = $event->getTarget();
        $qb = $event->getParam('queryBuilder');
        $query = $event->getParam('request')->getContent();
        if (isset($query['has_markers']) && (is_numeric($query['has_markers']) || is_bool($query['has_markers']))) {
            $mappingMarkerAlias = $itemAdapter->createAlias();
            if ($query['has_markers']) {
                $qb->innerJoin(
                    'Mapping\Entity\MappingMarker', $mappingMarkerAlias,
                    'WITH', "$mappingMarkerAlias.item = omeka_root.id"
                );
            } else {
                $qb->leftJoin(
                    'Mapping\Entity\MappingMarker', $mappingMarkerAlias,
                    'WITH', "$mappingMarkerAlias.item = omeka_root.id"
                );
                $qb->andWhere($qb->expr()->isNull($mappingMarkerAlias));
            }
        }
        $address = $query['mapping_address'] ?? null;
        $radius = $query['mapping_radius'] ?? null;
        $radiusUnit = $query['mapping_radius_unit'] ?? null;
        if (isset($address) && '' !== trim($address) && isset($radius) && is_numeric($radius)) {
            $mappingMarkerAdapter = $itemAdapter->getAdapter('mapping_markers');
            $mappingMarkerAdapter->buildGeographicLocationQuery($qb, $address, $radius, $radiusUnit, $itemAdapter);
        }
    }

    public function filterApiContext(Event $event)
    {
        $context = $event->getParam('context');
        $context['o-module-mapping'] = 'http://omeka.org/s/vocabs/module/mapping#';
        $event->setParam('context', $context);
    }

    /**
     * Add the map tab to section navigations.
     *
     * Event $event
     */
    public function addMapTab(Event $event)
    {
        $view = $event->getTarget();
        if ('view.show.section_nav' === $event->getName()) {
            // Don't render the mapping tab if there is no mapping data.
            $itemJson = $event->getParam('resource')->jsonSerialize();
            if (!isset($itemJson['o-module-mapping:marker'])
                && !isset($itemJson['o-module-mapping:mapping'])
            ) {
                return;
            }
        }
        $sectionNav = $event->getParam('section_nav');
        $sectionNav['mapping-section'] = $view->translate('Mapping');
        $event->setParam('section_nav', $sectionNav);
    }

    /**
     * Add the mapping and marker data to the item JSON-LD.
     *
     * Event $event
     */
    public function filterItemJsonLd(Event $event)
    {
        $item = $event->getTarget();
        $jsonLd = $event->getParam('jsonLd');
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        // Add mapping data.
        $response = $api->search('mappings', ['item_id' => $item->id()]);
        foreach ($response->getContent() as $mapping) {
            // There's zero or one mapping per item.
            $jsonLd['o-module-mapping:mapping'] = $mapping;
        }
        // Add marker data.
        $response = $api->search('mapping_markers', ['item_id' => $item->id()]);
        foreach ($response->getContent() as $marker) {
            // There's zero or more markers per item.
            $jsonLd['o-module-mapping:marker'][] = $marker;
        }

        $event->setParam('jsonLd', $jsonLd);
    }

    /**
     * Handle hydration for mapping data.
     *
     * @param Event $event
     */
    public function handleMapping(Event $event)
    {
        $itemAdapter = $event->getTarget();
        $request = $event->getParam('request');
        $item = $event->getParam('entity');

        if (!$itemAdapter->shouldHydrate($request, 'o-module-mapping:mapping')) {
            return;
        }

        $mappingsAdapter = $itemAdapter->getAdapter('mappings');
        $mappingData = $request->getValue('o-module-mapping:mapping', []);

        $bounds = null;

        if (isset($mappingData['o-module-mapping:bounds'])
            && '' !== trim($mappingData['o-module-mapping:bounds'])
        ) {
            $bounds = $mappingData['o-module-mapping:bounds'];
        }

        $mapping = null;
        if (Request::CREATE !== $request->getOperation()) {
            try {
                $mapping = $mappingsAdapter->findEntity(['item' => $item]);
            } catch (ApiException\NotFoundException $e) {
                // no action
            }
        }

        if (null === $bounds) {
            // This request has no mapping data. If a mapping for this item
            // exists, delete it. If no mapping for this item exists, do nothing.
            if ($mapping) {
                $subRequest = new \Omeka\Api\Request('delete', 'mappings');
                $subRequest->setId($mapping->getId());
                $mappingsAdapter->deleteEntity($subRequest);
            }
            return;
        }

        // This request has mapping data. If a mapping for this item exists,
        // update it. If no mapping for this item exists, create it.
        if ($mapping) {
            // Update mapping
            $subRequest = new \Omeka\Api\Request('update', 'mappings');
            $subRequest->setId($mappingData['o:id']);
            $subRequest->setContent($mappingData);
            $mappingsAdapter->hydrateEntity($subRequest, $mapping, new \Omeka\Stdlib\ErrorStore);
        } else {
            // Create mapping
            $subRequest = new \Omeka\Api\Request('create', 'mappings');
            $subRequest->setContent($mappingData);
            $mapping = new \Mapping\Entity\Mapping;
            $mapping->setItem($event->getParam('entity'));
            $mappingsAdapter->hydrateEntity($subRequest, $mapping, new \Omeka\Stdlib\ErrorStore);
            $mappingsAdapter->getEntityManager()->persist($mapping);
        }
    }

    /**
     * Handle hydration for marker data.
     *
     * @param Event $event
     */
    public function handleMarkers(Event $event)
    {
        $itemAdapter = $event->getTarget();
        $request = $event->getParam('request');

        if (!$itemAdapter->shouldHydrate($request, 'o-module-mapping:marker')) {
            return;
        }

        $item = $event->getParam('entity');
        $entityManager = $itemAdapter->getEntityManager();
        $markersAdapter = $itemAdapter->getAdapter('mapping_markers');
        $retainMarkerIds = [];

        $existingMarkers = [];
        if ($item->getId()) {
            $dql = 'SELECT mm FROM Mapping\Entity\MappingMarker mm INDEX BY mm.id WHERE mm.item = ?1';
            $query = $entityManager->createQuery($dql)->setParameter(1, $item->getId());
            $existingMarkers = $query->getResult();
        }

        // Create/update markers passed in the request.
        foreach ($request->getValue('o-module-mapping:marker', []) as $markerData) {
            if (isset($markerData['o:id'])) {
                if (!isset($existingMarkers[$markerData['o:id']])) {
                    // This marker belongs to another item. Ignore it.
                    continue;
                }
                $subRequest = new \Omeka\Api\Request('update', 'mapping_markers');
                $subRequest->setId($markerData['o:id']);
                $subRequest->setContent($markerData);
                $marker = $markersAdapter->findEntity($markerData['o:id'], $subRequest);
                $markersAdapter->hydrateEntity($subRequest, $marker, new \Omeka\Stdlib\ErrorStore);
                $retainMarkerIds[] = $marker->getId();
            } else {
                $subRequest = new \Omeka\Api\Request('create', 'mapping_markers');
                $subRequest->setContent($markerData);
                $marker = new \Mapping\Entity\MappingMarker;
                $marker->setItem($item);
                $markersAdapter->hydrateEntity($subRequest, $marker, new \Omeka\Stdlib\ErrorStore);
                $entityManager->persist($marker);
            }
        }

        // Delete existing markers not passed in the request.
        foreach ($existingMarkers as $existingMarkerId => $existingMarker) {
            if (!in_array($existingMarkerId, $retainMarkerIds)) {
                $entityManager->remove($existingMarker);
            }
        }
    }

    /**
     * Does the passed data contain valid copy-coordinates data?
     *
     * @param array $data
     * return bool
     */
    public function copyCoordinatesDataIsValid(array $data)
    {
        $coordinatesData = $data['mapping_copy_coordinates'] ?? null;
        if (!is_array($coordinatesData)) {
            return false;
        }
        if (!(isset($coordinatesData['copy_action']) && in_array($coordinatesData['copy_action'], ['by_item_property', 'by_item_properties', 'by_media_property', 'by_media_properties']))) {
            return false;
        }
        if (in_array($coordinatesData['copy_action'], ['by_item_property', 'by_media_property'])) {
            if (!(isset($coordinatesData['property']) && is_numeric($coordinatesData['property']))) {
                return false;
            }
            if (!(isset($coordinatesData['order']) && in_array($coordinatesData['order'], ['latlng', 'lnglat']))) {
                return false;
            }
            if (!(isset($coordinatesData['delimiter']) && in_array($coordinatesData['delimiter'], [',', ' ', '/', ':']))) {
                return false;
            }
        } elseif (in_array($coordinatesData['copy_action'], ['by_item_properties', 'by_media_properties'])) {
            if (!(isset($coordinatesData['property_lat']) && is_numeric($coordinatesData['property_lat']) && isset($coordinatesData['property_lng']) && is_numeric($coordinatesData['property_lng']))) {
                return false;
            }
        }
        if (isset($coordinatesData['assign_media']) && !in_array($coordinatesData['assign_media'], ['1', '0'])) {
            return false;
        }
        return true;
    }

    /**
     * Does the passed data contain valid update-markers data?
     *
     * @param array $data
     * return bool
     */
    public function updateMarkersDataIsValid(array $data)
    {
        $markersData = $data['mapping_update_markers'] ?? null;
        if (!is_array($markersData)) {
            return false;
        }
        if (isset($coordinatesData['label_property_source']) && !in_array($coordinatesData['label_property_source'], ['item', 'primary_media', 'assigned_media'])) {
            return false;
        }
        if (isset($coordinatesData['image']) && !in_array($coordinatesData['image'], ['', 'unassign', 'primary_media'])) {
            return false;
        }
        return true;
    }

    /**
     * Delete markers.
     *
     * @param Event $event
     */
    public function deleteMarkers(Event $event)
    {
        $data = $event->getParam('request')->getContent();
        $item = $event->getParam('response')->getContent();

        if (!(isset($data['mapping_delete_markers']) && $data['mapping_delete_markers'])) {
            return;
        }

        $services = $this->getServiceLocator();
        $entityManager = $services->get('Omeka\EntityManager');

        $dql = 'DELETE FROM Mapping\Entity\MappingMarker m WHERE m.item = :item_id';
        $entityManager->createQuery($dql)
            ->setParameter('item_id', $item->getId())
            ->execute();
    }

    /**
     * Copy coordinates from property values to mapping markers.
     *
     * @param Event $event
     */
    public function copyCoordinates(Event $event)
    {
        $data = $event->getParam('request')->getContent();
        $item = $event->getParam('response')->getContent();

        if (!$this->copyCoordinatesDataIsValid($data)) {
            return;
        }

        $data = $data['mapping_copy_coordinates'];

        $copyAction = $data['copy_action'];
        $propertyId = $data['property'] ?? null;
        $propertyLatId = $data['property_lat'] ?? null;
        $propertyLngId = $data['property_lng'] ?? null;
        $order = $data['order'] ?? null;
        $delimiter = $data['delimiter'] ?? null;
        $assignMedia = $data['assign_media'] ?? null;

        $services = $this->getServiceLocator();
        $entityManager = $services->get('Omeka\EntityManager');

        $dqlValues = 'SELECT v
            FROM Omeka\Entity\Value v
            WHERE v.resource = :resource_id
            AND v.property = :property_id
            AND v.value IS NOT NULL';
        $dqlMedia = 'SELECT m
            FROM Omeka\Entity\Media m
            WHERE m.item = :item_id';
        $dqlMarker = 'SELECT m
            FROM Mapping\Entity\MappingMarker m
            WHERE m.item = :item_id
            AND ABS(m.lat - :lat) < 0.000001
            AND ABS(m.lng - :lng) < 0.000001';

        $allCoordinates = [];
        // By one item property containing both latitude and longitude
        if ('by_item_property' === $copyAction) {
            $values = $entityManager->createQuery($dqlValues)
                ->setParameter('resource_id', $item->getId())
                ->setParameter('property_id', $propertyId)
                ->getResult();
            if (!$values) {
                return; // Relevant values don't exist. Do nothing.
            }
            foreach ($values as $value) {
                $allCoordinates[] = $value->getValue();
            }
            // By two item properties, one latitude and the other longitude
        } elseif ('by_item_properties' === $copyAction) {
            $latValues = $entityManager->createQuery($dqlValues)
                ->setParameter('resource_id', $item->getId())
                ->setParameter('property_id', $propertyLatId)
                ->getResult();
            $lngValues = $entityManager->createQuery($dqlValues)
                ->setParameter('resource_id', $item->getId())
                ->setParameter('property_id', $propertyLngId)
                ->getResult();
            if (!($latValues && $lngValues)) {
                return; // Relevant values don't exist. Do nothing.
            }
            if (count($latValues) !== count($lngValues)) {
                return; // Missing latitudes or longitudes. Do nothing.
            }
            foreach ($latValues as $index => $latValue) {
                $lat = $latValue->getValue();
                $lng = $lngValues[$index]->getValue();
                $allCoordinates[] = sprintf('%s,%s', $lat, $lng);
            }
            $delimiter = ',';
        // By one media property containing both latitude and longitude
        } elseif ('by_media_property' === $copyAction) {
            $medias = $entityManager->createQuery($dqlMedia)
                ->setParameter('item_id', $item->getId())
                ->getResult();
            foreach ($medias as $media) {
                $values = $entityManager->createQuery($dqlValues)
                    ->setParameter('resource_id', $media->getId())
                    ->setParameter('property_id', $propertyId)
                    ->getResult();
                if (!$values) {
                    continue; // Relevant values don't exist. Do nothing.
                }
                foreach ($values as $value) {
                    $allCoordinates[$media->getId()] = $value->getValue();
                }
            }
            // By two media properties, one latitude and the other longitude
        } elseif ('by_media_properties' === $copyAction) {
            $medias = $entityManager->createQuery($dqlMedia)
                ->setParameter('item_id', $item->getId())
                ->getResult();
            foreach ($medias as $media) {
                $latValues = $entityManager->createQuery($dqlValues)
                    ->setParameter('resource_id', $media->getId())
                    ->setParameter('property_id', $propertyLatId)
                    ->getResult();
                $lngValues = $entityManager->createQuery($dqlValues)
                    ->setParameter('resource_id', $media->getId())
                    ->setParameter('property_id', $propertyLngId)
                    ->getResult();
                if (!($latValues && $lngValues)) {
                    continue; // Relevant values don't exist. Do nothing.
                }
                if (count($latValues) !== count($lngValues)) {
                    continue; // Missing latitudes or longitudes. Do nothing.
                }
                foreach ($latValues as $index => $latValue) {
                    $lat = $latValue->getValue();
                    $lng = $lngValues[$index]->getValue();
                    $allCoordinates[$media->getId()] = sprintf('%s,%s', $lat, $lng);
                }
                $delimiter = ',';
            }
        }

        // @see: https://stackoverflow.com/a/31408260
        $latRegex = '^(\+|-)?(?:90(?:(?:\.0{1,6})?)|(?:[0-9]|[1-8][0-9])(?:(?:\.[0-9]+)?))$';
        $lngRegex = '^(\+|-)?(?:180(?:(?:\.0{1,6})?)|(?:[0-9]|[1-9][0-9]|1[0-7][0-9])(?:(?:\.[0-9]+)?))$';
        foreach ($allCoordinates as $key => $coordinates) {
            $coordinates = explode($delimiter, $coordinates);
            if (2 !== count($coordinates)) {
                continue; // Coordinates must have latitude and longitude. Skip.
            }
            $coordinates = array_map('trim', $coordinates);
            $lat = ('latlng' === $order) ? $coordinates[0] : $coordinates[1];
            $lng = ('lnglat' === $order) ? $coordinates[0] : $coordinates[1];
            if (!preg_match(sprintf('/%s/', $latRegex), $lat)) {
                continue; // Invalid latitude. Skip.
            }
            if (!preg_match(sprintf('/%s/', $lngRegex), $lng)) {
                continue; // Invalid longitude. Skip.
            }

            // Handle duplicate coordinates. For this purpose, duplicates are
            // those with a difference of less than 0.000001. That's a difference
            // of 0° 00′ 0.0036″ DMS, or 11.1-43.5 mm. At this precision there is
            // no noticable difference between coordinates on map on maximum zoom.
            // @see https://en.wikipedia.org/wiki/Decimal_degrees#Precision
            $marker = $entityManager->createQuery($dqlMarker)
                ->setParameter('item_id', $item->getId())
                ->setParameter('lat', $lat)
                ->setParameter('lng', $lng)
                ->setMaxResults(1)
                ->getOneOrNullResult();
            if ($marker) {
                // This marker already exists.
            } else {
                // This marker does not exist. Create it.
                $marker = new MappingMarker;
                $marker->setLat($lat);
                $marker->setLng($lng);
                $marker->setItem($item);
                $entityManager->persist($marker);
            }
            // Assign media to marker if directed to do so.
            if (in_array($copyAction, ['by_media_property', 'by_media_properties']) && $assignMedia) {
                $media = $entityManager->find('Omeka\Entity\Media', $key);
                $marker->setMedia($media);
            }
        }
        $entityManager->flush();
    }

    /**
     * Update markers.
     *
     * @param Event $event
     */
    public function updateMarkers(Event $event)
    {
        $data = $event->getParam('request')->getContent();
        $item = $event->getParam('response')->getContent();

        if (!$this->updateMarkersDataIsValid($data)) {
            return;
        }

        $data = $data['mapping_update_markers'];

        $labelPropertyId = $data['label_property'] ?? null;
        $labelPropertySource = $data['label_property_source'] ?? null;
        $image = $data['image'] ?? null;

        $services = $this->getServiceLocator();
        $entityManager = $services->get('Omeka\EntityManager');

        $dql = 'SELECT m
            FROM Mapping\Entity\MappingMarker m
            WHERE m.item = :item_id';
        $markers = $entityManager->createQuery($dql)
            ->setParameter('item_id', $item->getId())
            ->getResult();
        if (!$markers) {
            return; // Markers don't exist. Do nothing.
        }
        foreach ($markers as $marker) {
            // Handle marker image.
            if ('primary_media' === $image) {
                $marker->setMedia($this->getPrimaryMedia($item));
            } elseif ('unassign' === $image) {
                $marker->setMedia(null);
            }
            // Handle maker label.
            if ('-1' === $labelPropertyId) {
                $marker->setLabel(null);
            } elseif (is_numeric($labelPropertyId)) {
                $resourceId = null;
                if ('primary_media' === $labelPropertySource) {
                    $media = $this->getPrimaryMedia($item);
                    if ($media) {
                        $resourceId = $media->getId();
                    }
                } elseif ('assigned_media' === $labelPropertySource) {
                    $media = $marker->getMedia();
                    if ($media) {
                        $resourceId = $media->getId();
                    }
                } else {
                    $resourceId = $item->getId();
                }
                if ($resourceId) {
                    $dql = 'SELECT v
                        FROM Omeka\Entity\Value v
                        WHERE v.resource = :resource_id
                        AND v.property = :property_id
                        AND v.value IS NOT NULL
                        ORDER BY v.id ASC';
                    $value = $entityManager->createQuery($dql)
                        ->setParameter('resource_id', $resourceId)
                        ->setParameter('property_id', $labelPropertyId)
                        ->setMaxResults(1)
                        ->getOneOrNullResult();
                    if ($value) {
                        $marker->setLabel(mb_substr($value->getValue(), 0, 255));
                    }
                }
            }
            $entityManager->persist($marker);
        }
        $entityManager->flush();
    }

    public function getPrimaryMedia(\Omeka\Entity\Item $item)
    {
        $services = $this->getServiceLocator();
        $entityManager = $services->get('Omeka\EntityManager');
        // Prioritize the item's primary media, if any.
        $media = $item->getPrimaryMedia();
        if (!$media) {
            // Fall back on the item's first media, if any.
            $dql = 'SELECT m
                FROM Omeka\Entity\Media m
                WHERE m.item = :item
                ORDER BY m.position ASC';
            $media = $entityManager->createQuery($dql)
                ->setParameter('item', $item)
                ->setMaxResults(1)
                ->getOneOrNullResult();
        }
        return $media;
    }
}
