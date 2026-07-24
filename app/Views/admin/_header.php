<?php /** Expects $pageTitle to be set before including. */ ?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Security::e($pageTitle ?? 'Admin') ?> – Bibutiken Admin</title>
    <link rel="stylesheet" href="/assets/css/tokens.css">
    <link rel="stylesheet" href="/assets/css/shared.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <?php if (isset($extraStyles)): ?>
        <?php foreach ($extraStyles as $href): ?>
            <link rel="stylesheet" href="<?= Security::e($href) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body class="admin">
<header class="admin-nav">
    <strong>Bibutiken Admin</strong>
    <nav>
    <?php
    $adminNav = [
        'dashboard'  => ['/admin',                    'Översikt'],
        'hours'      => ['/admin/oppettider',         'Öppettider'],
        'notiser'    => ['/admin/notiser',            'Notiser'],
        'orders'     => ['/admin/ordrar',             'Beställningar'],
        'products'   => ['/admin/produkter',          'Produkter'],
        'local'      => ['/admin/lokalproducerat',    'Egna produkter'],
        'customers'  => ['/admin/kunder',             'Kunder'],
        'logout'     => ['/admin/logout',             'Logga ut'],
    ];
    foreach ($adminNav as $key => [$href, $label]):
        $isCurrent = ($activePage ?? '') === $key;
    ?>
        <a href="<?= Security::e($href) ?>"
            <?= $isCurrent ? 'aria-current="page"' : '' ?>>
            <?= Security::e($label) ?>
        </a>
    <?php endforeach; ?>
</nav>
</header>
<main>