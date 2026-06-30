<?php /** Expects $pageTitle to be set before including. */ ?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Security::e($pageTitle ?? 'Bibutiken') ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="site-wordmark">Strängnäs Biredskap AB</div>
    <div class="site-header__inner">
        <nav class="site-nav">
            <a href="/index.php">Hem</a>
            <a href="/bihuset.php">Bihuset</a>
            <a href="/preorder.php">Vinterfoder</a>
        </nav>
    </div>
</header>
<main>