<?php
return [
    'db' => [
        'host' => getenv('DB_HOST') ?: 'mariadb',
        'name' => getenv('DB_NAME') ?: 'shop_db',
        'user' => getenv('DB_USER') ?: 'shop_user',
        'pass' => getenv('DB_PASS') ?: 'shop_pass',
    ],
];