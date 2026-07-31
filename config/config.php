<?php
return [
    'db' => [
        'host' => getenv('DB_HOST'),
        'name' => getenv('DB_NAME'),
        'user' => getenv('DB_USER'),
        'pass' => getenv('DB_PASS'),
    ],

    'mail' => [
        'from_email'    => getenv('MAIL_FROM'),
        'from_name'     => getenv('MAIL_FROM_NAME'),
        'secret_key'    => getenv('MAIL_SECRET_KEY'),
        'smtp_host'     => getenv('MAIL_SMTP_HOST') ?: 'mailout.one.com',
        'smtp_port'     => (int)(getenv('MAIL_SMTP_PORT') ?: 587),
        'smtp_user'     => getenv('MAIL_FROM'),
        'smtp_pass'     => getenv('MAIL_SMTP_PASS'),
    ],
];