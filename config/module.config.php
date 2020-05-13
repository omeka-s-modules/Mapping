<?php
namespace Mapping;

return [
    'api_adapters' => [
        'invokables' => [
            'mappings' => Api\Adapter\MappingAdapter::class,
            'mapping_markers' => Api\Adapter\MappingMarkerAdapter::class,
        ],
    ],
    'entity_manager' => [
        'mapping_classes_paths' => [
            dirname(__DIR__) . '/src/Entity',
        ],
        'proxy_paths' => [
            dirname(__DIR__) . '/data/doctrine-proxies',
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],
    'view_helpers' => [
        'invokables' => [
            'formPromptMap' => Collecting\FormPromptMap::class,
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
    'block_layouts' => [
        'factories' => [
            'mappingMap' => Service\BlockLayout\MapFactory::class,
            'mappingMapQuery' => Service\BlockLayout\MapFactory::class,
        ],
    ],
    'navigation_links' => [
        'invokables' => [
            'mapping' => Site\Navigation\Link\MapBrowse::class,
        ],
    ],
    'controllers' => [
        'invokables' => [
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
            'site' => [
                'child_routes' => [
                    'mapping-map-browse' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/map-browse',
                            'defaults' => [
                                '__NAMESPACE__' => 'Mapping\Controller\Site',
                                'controller' => 'index',
                                'action' => 'browse',
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
];
