<?php
/** Expects $pageTitle and $activePage to be set before including. */

$butikPages  = ['lokalproducerat', 'preorder', 'butik'];
$upplevPages = ['bihuset', 'upplev'];
$active      = $activePage ?? '';
$inButik     = in_array($active, $butikPages);
$inUpplev    = in_array($active, $upplevPages);
?>
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

    <!-- Wordmark row -->
    <div class="site-wordmark">
        <button class="site-header__info-toggle"
                aria-expanded="false"
                aria-controls="site-info-panel"
                aria-label="Visa kontaktinformation">ℹ</button>
        <span class="site-wordmark__contact">
            <span class="site-wordmark__contact-label">Kontakt</span>
            <a class="site-wordmark__contact-line" href="tel:+46733201066">☎ 0733-20 10 66</a>
            <a class="site-wordmark__contact-line site-wordmark__contact-email" href="mailto:info@strangnas-biredskap.se">
                <span class="email-full">✉ info@strangnas-biredskap.se</span>
                <span class="email-short">✉ Skicka mejl</span>
            </a>
        </span>
        <span class="site-wordmark__name">Strängnäs Biredskap AB</span>
        <span class="site-wordmark__eu">
            <span class="site-wordmark__eu-label">I samarbete med</span>
            <span class="site-wordmark__eu-logos">
                <a href="/upplev#leader-project"><img src="/assets/images/Leader.jpg" alt="Leader"></a>
                <a href="/upplev#eu-fonden"><img src="/assets/images/EU-flagga.jpg" alt="EU Jordbruksfond för landsbygdsutveckling"></a>
            </span>
        </span>
        <div class="site-header__controls">
            <button class="site-header__hamburger"
                    aria-expanded="false"
                    aria-controls="site-nav-mobile"
                    aria-label="Öppna meny">&#9776;</button>
        </div>
    </div>

    <!-- Main nav row -->
    <div class="site-header__inner">
        <nav class="site-nav" id="site-nav-main" aria-label="Huvudmeny">
            <a href="/"
                <?= $active === 'home' ? 'aria-current="page"' : '' ?>>
                Hem
            </a>
            <a href="/butik"
                <?= $inButik ? 'aria-current="page"' : '' ?>>
                Butik
            </a>
            <a href="/upplev"
                <?= $inUpplev ? 'aria-current="page"' : '' ?>>
                Upplev
            </a>
        </nav>
    </div>

    <!-- Info panel (mobile dropdown) -->
    <div class="site-info-panel" id="site-info-panel" hidden>
        <a href="tel:+46733201066">☎ 0733-20 10 66</a>
        <a href="mailto:info@strangnas-biredskap.se">✉ info@strangnas-biredskap.se</a>
        <span class="site-info-panel__eu">
            <span>I samarbete med</span>
            <span class="site-wordmark__eu-logos">
                <a href="/upplev#leader-project"><img src="/assets/images/Leader.jpg" alt="Leader"></a>
                <a href="/upplev#eu-fonden"><img src="/assets/images/EU-flagga.jpg" alt="EU Jordbruksfond för landsbygdsutveckling"></a>
            </span>
        </span>
    </div>

    <!-- Mobile nav panel -->
    <div class="site-nav-mobile" id="site-nav-mobile" hidden>
        <a href="/" <?= $active === 'home' ? 'aria-current="page"' : '' ?>>Hem</a>
        <div class="site-nav-mobile__group">
            <a href="/butik"
                class="site-nav-mobile__parent <?= $inButik ? 'is-open' : '' ?>"
                <?= $inButik ? 'aria-current="page"' : '' ?>>Butik</a>
            <div class="site-nav-mobile__sub <?= $inButik ? 'is-open' : '' ?>">
                <a href="/lokalproducerat" <?= $active === 'lokalproducerat' ? 'aria-current="page"' : '' ?>>Lokalproducerat</a>
                <a href="/vinterfoder"     <?= $active === 'preorder'        ? 'aria-current="page"' : '' ?>>Vinterfoder</a>
            </div>
        </div>
        <div class="site-nav-mobile__group">
            <a href="/upplev"
                class="site-nav-mobile__parent <?= $inUpplev ? 'is-open' : '' ?>"
                <?= $inUpplev ? 'aria-current="page"' : '' ?>>Upplev</a>
            <div class="site-nav-mobile__sub <?= $inUpplev ? 'is-open' : '' ?>">
                <a href="/bihuset" <?= $active === 'bihuset' ? 'aria-current="page"' : '' ?>>Om bihuset</a>
            </div>
        </div>
    </div>

</header>
<main>
<?php if ($inButik): ?>
<div class="site-subnav site-subnav--inline" aria-label="Butik undermeny">
    <a href="/lokalproducerat" <?= $active === 'lokalproducerat' ? 'aria-current="page"' : '' ?>>Lokalproducerat</a>
    <a href="/vinterfoder"     <?= $active === 'preorder'        ? 'aria-current="page"' : '' ?>>Vinterfoder</a>
</div>
<?php elseif ($inUpplev): ?>
<div class="site-subnav site-subnav--inline" aria-label="Upplev undermeny">
    <a href="/bihuset" <?= $active === 'bihuset' ? 'aria-current="page"' : '' ?>>Om bihuset</a>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/_banners.php'; ?>

<script src="/assets/js/nav.js"></script>