<?php /** Expects $pageTitle to be set before including. */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Security::e($pageTitle ?? 'Admin') ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="admin-nav">
    <strong>Bibutiken Admin</strong>
    <nav>
        <a href="/admin/dashboard.php">Översikt</a>
        <a href="/admin/hours.php">Öppettider</a>
        <a href="/admin/logout.php">Logga ut</a>
    </nav>
</header>
<main>