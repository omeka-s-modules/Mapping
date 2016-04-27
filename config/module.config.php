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
    ],
    'view_manager' => [
        'template_path_stack' => [
            OMEKA_PATH . '/modules/Mapping/view',
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
];
