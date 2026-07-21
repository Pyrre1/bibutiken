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
    <?php if (isset($extraStyles)): ?>
        <?php foreach ($extraStyles as $href): ?>
            <link rel="stylesheet" href="<?= Security::e($href) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <link rel="icon" type="image/png" href="/assets/images/favicon.png">
</head>
<body>
<header class="site-header">
    <div class="site-wordmark">
        <span class="site-wordmark__contact">
            <span class="site-wordmark__contact-label">Kontakt</span>
            <a class="site-wordmark__contact-line" href="tel:+46733201066">☎ 0733-20 10 66</a>
            <a class="site-wordmark__contact-line site-wordmark__contact-email" href="mailto:info@strangnas-biredskap.se">✉ info@strangnas-biredskap.se</a>
        </span>
        <span class="site-wordmark__name">Strängnäs Biredskap AB</span>
        <span class="site-wordmark__eu">
            <span class="site-wordmark__eu-label">I samarbete med</span>
            <span class="site-wordmark__eu-logos">
                <a href="/bihuset.php#leader-project"><img src="/assets/images/Leader.jpg" alt="Leader"></a>
                <a href="/bihuset.php#eu-fonden"><img src="/assets/images/EU-flagga.jpg" alt="EU Jordbruksfond för landsbygdsutveckling"></a>
            </span>
        </span>
    </div>
    <div class="site-header__inner">
        <nav class="site-nav">
            <a href="/index.php">Hem</a>
            <a href="/bihuset.php">Bihuset</a>
            <a href="/preorder.php">Vinterfoder</a>
        </nav>
    </div>
</header>
<main>