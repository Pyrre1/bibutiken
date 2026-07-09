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
        <a href="/admin/dashboard.php">Översikt</a>
        <a href="/admin/hours.php">Öppettider</a>
        <a href="/admin/orders.php">Beställningar</a>
        <a href="/admin/products.php">Produkter</a>
        <a href="/admin/customers.php">Kunder</a>
        <a href="/admin/logout.php">Logga ut</a>
    </nav>
</header>
<main>