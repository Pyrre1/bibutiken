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
        <button class="site-header__info-toggle" aria-expanded="false" aria-controls="site-info-panel" aria-label="Visa kontaktinformation">ℹ</button>
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
                <a href="/bihuset#leader-project"><img src="/assets/images/Leader.jpg" alt="Leader"></a>
                <a href="/bihuset#eu-fonden"><img src="/assets/images/EU-flagga.jpg" alt="EU Jordbruksfond för landsbygdsutveckling"></a>
            </span>
        </span>
    </div>
    <div class="site-header__inner">
        <nav class="site-nav">
            <?php
            $publicNav = [
                'home'            => ['/',                'Hem'],
                'lokalproducerat' => ['/lokalproducerat', 'Lokalproducerat'],
                'bihuset'         => ['/bihuset',         'Bihuset'],
                'preorder'        => ['/vinterfoder',     'Vinterfoder'],
            ];
            foreach ($publicNav as $key => [$href, $label]):
                $isCurrent = ($activePage ?? '') === $key;
            ?>
                <a href="<?= Security::e($href) ?>"
                <?= $isCurrent ? 'aria-current="page"' : '' ?>>
                    <?= Security::e($label) ?>
                </a>
            <?php endforeach; ?>
        </nav>
        </div>
    <div class="site-info-panel" id="site-info-panel" hidden>
        <a href="tel:+46733201066">☎ 0733-20 10 66</a>
        <a href="mailto:info@strangnas-biredskap.se">✉ info@strangnas-biredskap.se</a>
        <span class="site-info-panel__eu">
            <span>I samarbete med</span>
            <span class="site-wordmark__eu-logos">
                <a href="/bihuset#leader-project"><img src="/assets/images/Leader.jpg" alt="Leader"></a>
                <a href="/bihuset#eu-fonden"><img src="/assets/images/EU-flagga.jpg" alt="EU Jordbruksfond för landsbygdsutveckling"></a>
            </span>
        </span>
    </div>
</header>
<main>
<?php require_once __DIR__ . '/_banners.php'; ?>

<script>
(function () {
    const btn = document.querySelector('.site-header__info-toggle');
    const panel = document.getElementById('site-info-panel');
    if (!btn || !panel) return;

    btn.addEventListener('click', function () {
        const open = btn.getAttribute('aria-expanded') === 'true';
        btn.setAttribute('aria-expanded', String(!open));
        panel.hidden = open;
    });

    document.addEventListener('click', function (e) {
        if (!panel.hidden && !panel.contains(e.target) && !btn.contains(e.target)) {
            btn.setAttribute('aria-expanded', 'false');
            panel.hidden = true;
        }
    });
})();
</script>