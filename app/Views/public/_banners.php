<?php
require_once __DIR__ . '/../../Models/Banner.php';
$siteBanners = Banner::getActiveBanners();
if (empty($siteBanners)) return;
?>
<div class="site-banners" role="region" aria-label="Webbplatsmeddelanden">
<?php foreach ($siteBanners as $b): ?>
    <div class="site-banner site-banner--<?= Security::e($b['type']) ?>" role="alert">
        <?= Security::e($b['message']) ?>
    </div>
<?php endforeach; ?>
</div>