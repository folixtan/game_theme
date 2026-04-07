<?php
return [
    'cache_types' => [
        'compiled_config' => 1,
        'config' => 1,
        'layout' => 1,
        'block_html' => 1,
        'collections' => 1,
        'reflection' => 1,
        'db_ddl' => 1,
        'eav' => 1,
        'customer_notification' => 1,
        'config_integration' => 1,
        'config_integration_api' => 1,
        'graphql_query_resolver_result' => 1,
        'full_page' => 1,
        'config_webservice' => 1,
        'translate' => 1
    ],
    'remote_storage' => [
        'driver' => 'file'
    ],
    'backend' => [
        'frontName' => 'admin_zdqpp5t'
    ],
    'cache' => [
        'graphql' => [
            'id_salt' => 'ujNpxrHTUm2DZpsJxgwUliOXds4bWM20'
        ],
        'frontend' => [
            'default' => [
                'id_prefix' => '70e_'
            ],
            'page_cache' => [
                'id_prefix' => '70e_'
            ]
        ],
        'allow_parallel_generation' => false
    ],
    'config' => [
        'async' => 0
    ],
    'queue' => [
        'consumers_wait_for_messages' => 1
    ],
    'crypt' => [
        'key' => 'base64VIX6J+DJUFEf6+EPq0t9TbfLEwK46DMldd/eavDTAWU='
    ],
    'db' => [
        'table_prefix' => '',
        'connection' => [
            'default' => [
                'host' => 'db.eco.io',
                'dbname' => 'game_dev',
                'username' => 'root',
                'password' => 'rootpassword',
                'model' => 'mysql4',
                'engine' => 'innodb',
                'initStatements' => 'SET NAMES utf8;',
                'active' => '1',
                'driver_options' => [
                    1014 => false
                ]
            ]
        ]
    ],
    'resource' => [
        'default_setup' => [
            'connection' => 'default'
        ]
    ],
    'x-frame-options' => 'SAMEORIGIN',
    'MAGE_MODE' => 'developer',
    'session' => [
        'save' => 'db'
    ],
    'lock' => [
        'provider' => 'db'
    ],
    'directories' => [
        'document_root_is_pub' => true
    ],
    'downloadable_domains' => [
        'game.eco.io'
    ],
    'install' => [
        'date' => 'Mon, 06 Apr 2026 12:10:56 +0000'
    ]
];
