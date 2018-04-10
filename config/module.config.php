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
            'items' => [
                CsvMapping\CsvMapping::class,
            ],
        ],
        'automapping' => [
            'mapping_latitude' => [
                'name' => 'map-lat',
                'value' => 1,
                'label' => 'Latitude' ,
                'class' => 'mapping-module',
            ],
            'mapping_longitude' => [
                'name' => 'map-lng',
                'value' => 1,
                'label' => 'Longitude' ,
                'class' => 'mapping-module',
            ],
            'mapping_latitude_longitude' => [
                'name' => 'map-latlng',
                'value' => 1,
                'label' => 'Latitude/Longitude' ,
                'class' => 'mapping-module',
            ],
            'mapping_default_latitude' => [
                'name' => 'default-lat',
                'value' => 1,
                'label' => 'Default latitude' ,
                'class' => 'mapping-module-defaults',
            ],
            'mapping_default_longitude' => [
                'name' => 'default-lng',
                'value' => 1,
                'label' => 'Default longitude' ,
                'class' => 'mapping-module-defaults',
            ],
            'mapping_default_zoom' => [
                'name' => 'default-zoom',
                'value' => 1,
                'label' => 'Default zoom' ,
                'class' => 'mapping-module-defaults',
            ],
        ],
        'user_settings' => [
            'csv_import_automap_user_list' => [
                'latitude' => 'mapping_latitude',
                'longitude' => 'mapping_longitude',
                'latitude/longitude' => 'mapping_latitude_longitude',
                'default latitude' => 'mapping_default_latitude',
                'default longitude' => 'mapping_default_longitude',
                'default zoom' => 'mapping_default_zoom',
            ],
        ],
    ],
    'omeka2_importer_classes' => [
        Omeka2Importer\GeolocationImporter::class,
    ],
    'block_layouts' => [
        'invokables' => [
            'mappingMap' => Site\BlockLayout\Map::class,
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
