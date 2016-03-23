<?php
return [
    'api_adapters' => array(
        'invokables' => array(
            'mapping_markers' => 'Mapping\Api\Adapter\MappingMarkerAdapter',
        ),
    ),
    'entity_manager' => array(
        'mapping_classes_paths' => array(
            OMEKA_PATH . '/modules/Mapping/src/Entity',
        ),
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            OMEKA_PATH . '/modules/Mapping/view',
        ),
    ),
];
