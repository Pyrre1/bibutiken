<?php /** Expects $pageTitle to be set before including. */ ?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Security::e($pageTitle ?? 'Bibutiken') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Skranji:wght@400;700&family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/tokens.css">
    <link rel="stylesheet" href="/assets/css/shared.css">
    <link rel="stylesheet" href="/assets/css/public.css">
    <link rel="icon" type="image/png" href="/assets/images/favicon.png">
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