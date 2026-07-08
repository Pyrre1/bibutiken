<?php
require_once __DIR__ . '/../../app/Core/init.php';
require_once __DIR__ . '/../../app/Models/Dashboard.php';
require_once __DIR__ . '/../../app/Models/Product.php';
require_once __DIR__ . '/../../app/Models/HoursPlan.php';
require_once __DIR__ . '/../../app/Core/HoursResolver.php';
Auth::requireLogin();

$previousLoginAt = $_SESSION['previous_login_at'] ?? null;
$stats = Dashboard::getDashboardStats($previousLoginAt);

$thisWeekNum  = (int) date('W');
$thisYear     = (int) date('Y');
$nextWeekNum  = $thisWeekNum + 1;
$nextYear     = $thisYear;
if ($nextWeekNum > 52) { $nextWeekNum = 1; $nextYear++; }

$thisWeekPlan = HoursResolver::resolveForWeek($thisWeekNum, $thisYear);
$nextWeekPlan = HoursResolver::resolveForWeek($nextWeekNum, $nextYear);

$pageTitle = 'Översikt – Admin';
require __DIR__ . '/../../app/Views/admin/_header.php';
?>

<?php if ($stats['manual_pending'] > 0): ?>
<div class="admin-alert-banner" id="manual-alert">
    <span>⚠️ <?= $stats['manual_pending'] ?> order(ar) väntar på manuell hantering.</span>
    <button type="button" onclick="document.getElementById('manual-alert').style.display='none'" aria-label="Stäng">✕</button>
</div>
<?php endif; ?>

<h1>Översikt</h1>

<div class="dashboard-section">
    <div class="dashboard-section__label">Beställningar</div>
    <div class="dashboard-tiles">

        <a href="/admin/orders.php" class="dash-card <?= $stats['new_since_login'] > 0 ? 'dash-card--highlight' : '' ?>">
            <div class="dash-card__value"><?= $stats['new_since_login'] ?></div>
            <div class="dash-card__label">Nya sedan senaste inloggning</div>
        </a>

        <a href="/admin/orders.php" class="dash-card">
            <div class="dash-card__value"><?= $stats['total_this_year'] ?></div>
            <div class="dash-card__label">Totalt <?= date('Y') ?></div>
        </a>

        <a href="/admin/orders.php?filter=manual" class="dash-card <?= $stats['manual_pending'] > 0 ? 'dash-card--warning' : '' ?>">
            <div class="dash-card__value"><?= $stats['manual_pending'] ?></div>
            <div class="dash-card__label">Manuell hantering väntar</div>
        </a>

        <a href="/admin/orders.php?filter=delivered" class="dash-card">
            <div class="dash-card__value"><?= $stats['delivered'] ?></div>
            <div class="dash-card__label">Levererade <?= date('Y') ?></div>
        </a>

        <a href="/admin/orders.php" class="dash-card">
            <div class="dash-card__value"><?= $stats['product_totals']['bifor'] ?></div>
            <div class="dash-card__label">Totalt Bifor</div>
        </a>

        <a href="/admin/orders.php" class="dash-card">
            <div class="dash-card__value"><?= $stats['product_totals']['dulco'] ?></div>
            <div class="dash-card__label">Totalt Dulcofruct</div>
        </a>

        <a href="/admin/orders.php?filter=manual" class="dash-card">
            <div class="dash-card__value"><?= $stats['product_totals']['lackad'] ?></div>
            <div class="dash-card__label">Totalt Färdiglackad låda</div>
        </a>

    </div>
</div>

<div class="dashboard-section">
    <div class="dashboard-section__label">Hemsidan</div>
    <div class="dashboard-tiles">

        <a href="/admin/hours.php" class="dash-card">
            <div class="dash-card__label">Denna vecka (v.<?= $thisWeekNum ?>)</div>
            <div class="dash-card__sub"><?= $thisWeekPlan ? Security::e($thisWeekPlan['header_text'] ?: 'Standard') : 'Ingen plan' ?></div>
            <div class="dash-card__sub muted"><?= $thisWeekPlan ? Security::e($thisWeekPlan['type']) : '' ?></div>
        </a>

        <a href="/admin/hours.php?preview=next" class="dash-card">
            <div class="dash-card__label">Nästa vecka (v.<?= $nextWeekNum ?>)</div>
            <div class="dash-card__sub"><?= $nextWeekPlan ? Security::e($nextWeekPlan['header_text'] ?: 'Standard') : 'Ingen plan' ?></div>
            <div class="dash-card__sub muted"><?= $nextWeekPlan ? Security::e($nextWeekPlan['type']) : '' ?></div>
        </a>

        <a href="/admin/products.php" class="dash-card">
            <div class="dash-card__value"><?= $stats['active_products'] ?></div>
            <div class="dash-card__label">Aktiva produkter på hemsidan</div>
        </a>        

    </div>
</div>

<div class="dashboard-section">
    <div class="dashboard-section__label">Kunder</div>
    <div class="dashboard-tiles">

        <a href="/admin/customers.php" class="dash-card">
            <div class="dash-card__value"><?= $stats['total_customers'] ?></div>
            <div class="dash-card__label">Totalt antal kunder</div>
        </a>

        <div class="dash-card">
            <div class="dash-card__value"><?= $stats['newsletter_count'] ?></div>
            <div class="dash-card__label">Prenumeranter nyhetsbrev</div>
        </div>

    </div>
</div>

<?php require __DIR__ . '/../../app/Views/admin/_footer.php'; ?>