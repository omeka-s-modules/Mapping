<?php
return [
    'api_adapters' => [
        'invokables' => [
            'mappings' => 'Mapping\Api\Adapter\MappingAdapter',
            'mapping_markers' => 'Mapping\Api\Adapter\MappingMarkerAdapter',
        ],
    ],
    'entity_manager' => [
        'mapping_classes_paths' => [
            OMEKA_PATH . '/modules/Mapping/src/Entity',
        ],
        'proxy_paths' => [
            OMEKA_PATH . '/modules/Mapping/data/doctrine-proxies',
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            OMEKA_PATH . '/modules/Mapping/view',
        ],
    ],
    'view_helpers' => [
        'invokables' => [
            'formPromptMap' => 'Mapping\Collecting\FormPromptMap',
        ],
    ],
    'csv_import_mappings' => [
        'items' => [
            'Mapping\CsvMapping\CsvMapping',
        ],
    ],
    'block_layouts' => [
        'invokables' => [
            'mappingMap' => 'Mapping\Site\BlockLayout\Map',
        ],
    ],
    'navigation_links' => [
        'invokables' => [
            'mapping' => 'Mapping\Site\Navigation\Link\MapBrowse',
        ],
    ],
    'controllers' => [
        'invokables' => [
            'Mapping\Controller\Site\Index' => 'Mapping\Controller\Site\IndexController',
        ],
    ],
    'collecting_media_types' => [
        'invokables' => [
            'map' => 'Mapping\Collecting\Map',
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
];
