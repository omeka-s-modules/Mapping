<?php
namespace Mapping;

use Osii\Service\ResourceMapper\ResourceMapperFactory;

return [
    'api_adapters' => [
        'invokables' => [
            'mappings' => Api\Adapter\MappingAdapter::class,
            'mapping_features' => Api\Adapter\MappingFeatureAdapter::class,
        ],
    ],
    'entity_manager' => [
        'mapping_classes_paths' => [
            dirname(__DIR__) . '/src/Entity',
        ],
        'proxy_paths' => [
            dirname(__DIR__) . '/data/doctrine-proxies',
        ],
        'data_types' => [
            'geography' => \LongitudeOne\Spatial\DBAL\Types\GeographyType::class,
        ],
        'functions' => [
            'numeric' => [
                'ST_Buffer' => 'Mapping\Spatial\ORM\Query\AST\Functions\StBuffer',
                'ST_Intersects' => 'LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StIntersects',
                'ST_GeomFromText' => 'LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StGeomFromText',
                'ST_GeometryType' => 'LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StGeometryType',
            ],
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
    'view_helpers' => [
        'invokables' => [
            'formPromptMap' => Collecting\FormPromptMap::class,
            'formMappingCopyCoordinates' => View\Helper\CopyCoordinates::class,
            'formMappingUpdateFeatures' => View\Helper\UpdateFeatures::class,
        ],
        'delegators' => [
            'Laminas\Form\View\Helper\FormElement' => [
                Service\Delegator\FormElementDelegatorFactory::class,
            ],
        ],
    ],
    'form_elements' => [
        'factories' => [
            'Mapping\Form\Fieldset\TimelineFieldset' => Service\Form\Fieldset\TimelineFieldsetFactory::class,
            'Mapping\Form\Element\CopyCoordinates' => Service\Form\Element\CopyCoordinatesFactory::class,
            'Mapping\Form\Element\UpdateFeatures' => Service\Form\Element\UpdateFeaturesFactory::class,
        ],
    ],
    'csv_import' => [
        'mappings' => [
            'items' => [ CsvMapping\CsvMapping::class ],
        ],
    ],
    'omeka2_importer_classes' => [
        Omeka2Importer\GeolocationImporter::class,
    ],
    'osii_resource_mappers' => [
        'factories' => [
            Osii\ResourceMapper\ItemMapping::class => ResourceMapperFactory::class,
        ],
    ],
    'block_layouts' => [
        'factories' => [
            'mappingMap' => Service\BlockLayout\MapFactory::class,
            'mappingMapQuery' => Service\BlockLayout\MapFactory::class,
            'mappingMapGroups' => Service\BlockLayout\MapFactory::class,
        ],
    ],
    'navigation_links' => [
        'invokables' => [
            'mapping' => Site\Navigation\Link\MapBrowse::class,
        ],
    ],
    'controllers' => [
        'invokables' => [
            'Mapping\Controller\Admin\Index' => Controller\Admin\IndexController::class,
            'Mapping\Controller\Site\Index' => Controller\Site\IndexController::class,
        ],
    ],
    'collecting_media_types' => [
        'invokables' => [
            'map' => Collecting\Map::class,
        ],
    ],
    'router' => [
        'routes' => [
            'admin' => [
                'child_routes' => [
                    'mapping' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/mapping/:controller[/:action]',
                            'defaults' => [
                                '__NAMESPACE__' => 'Mapping\Controller\Admin',
                                'controller' => 'index',
                                'action' => 'index',
                            ],
                            'constraints' => [
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                        ],
                    ],
                ],
            ],
            'site' => [
                'child_routes' => [
                    'mapping' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/mapping/:controller[/:action]',
                            'defaults' => [
                                '__NAMESPACE__' => 'Mapping\Controller\Site',
                                'controller' => 'index',
                                'action' => 'index',
                            ],
                            'constraints' => [
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => dirname(__DIR__) . '/language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
    'resource_page_block_layouts' => [
        'invokables' => [
            'mapping' => Site\ResourcePageBlockLayout\Mapping::class,
        ],
    ],
    'resource_page_blocks_default' => [
        'items' => [
            'main' => ['mapping'],
        ],
    ],
    'static_site_export' => [
        'vendor_packages' => [
            'omeka-mapping' => sprintf('%s/modules/Mapping/src/StaticSiteExport/omeka-mapping', OMEKA_PATH),
            'leaflet' => sprintf('%s/modules/Mapping/src/StaticSiteExport/leaflet', OMEKA_PATH),
            'leaflet.markercluster' => sprintf('%s/modules/Mapping/src/StaticSiteExport/leaflet.markercluster', OMEKA_PATH),
        ],
        'shortcodes' => [
            'omeka-mapping-features' => sprintf('%s/modules/Mapping/src/StaticSiteExport/omeka-mapping-features.html', OMEKA_PATH),
        ],
        'block_layouts' => [
            'invokables' => [
                'mappingMap' => StaticSiteExport\BlockLayout\Map::class,
                'mappingMapQuery' => StaticSiteExport\BlockLayout\MapQuery::class,
            ],
        ],
        'resource_page_block_layouts' => [
            'invokables' => [
                'mapping' => StaticSiteExport\ResourcePageBlockLayout\Mapping::class,
            ],
        ],
        'navigation_links' => [
            'invokables' => [
                'mapping' => StaticSiteExport\NavigationLink\Mapping::class,
            ],
        ],
    ],
];
