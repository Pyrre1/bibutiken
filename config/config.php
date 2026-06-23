<?php
return [
    'db' => [
        'host' => getenv('DB_HOST') ?: 'mariadb',
        'name' => getenv('DB_NAME') ?: 'shop_db',
        'user' => getenv('DB_USER') ?: 'shop_user',
        'pass' => getenv('DB_PASS') ?: 'shop_pass',
    ],

    'mail' => [
    'owner_notify_email' => 'your-existing-mailbox@yourdomain.se', //TODO add the actual email.
    'site_from_email' => 'your-existing-mailbox@yourdomain.se', // swap to dedicated mailbox later, no code changes needed
    ],
];