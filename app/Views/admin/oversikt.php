<?php if ($stats['manual_pending'] > 0): ?>
<div class="admin-alert-banner" id="manual-alert">
    <span>⚠️ <?= $stats['manual_pending'] ?> order(ar) väntar på manuell hantering.</span>
    <button type="button" onclick="document.getElementById('manual-alert').style.display='none'" aria-label="Stäng">✕</button>
</div>
<?php endif; ?>

<h1>Översikt</h1>

<div class="dashboard-grid">

    <!-- LEFT COLUMN: Orders + Customers -->
    <div class="dashboard-col">

        <div class="dashboard-section">
            <div class="dashboard-section__label">Beställningar</div>
            <div class="dashboard-tiles">

                <a href="/admin/ordrar" class="dash-card <?= $stats['new_since_login'] > 0 ? 'dash-card--highlight' : '' ?>">
                    <div class="dash-card__value"><?= $stats['new_since_login'] ?></div>
                    <div class="dash-card__label">Nya sedan senaste inloggning</div>
                </a>

                <a href="/admin/ordrar" class="dash-card">
                    <div class="dash-card__value"><?= $stats['total_this_year'] ?></div>
                    <div class="dash-card__label">Totala order <?= date('Y') ?></div>
                </a>

                <a href="/admin/ordrar?filter=manual" class="dash-card <?= $stats['manual_pending'] > 0 ? 'dash-card--warning' : '' ?>">
                    <div class="dash-card__value"><?= $stats['manual_pending'] ?></div>
                    <div class="dash-card__label">Manuell hantering väntar</div>
                </a>

                <a href="/admin/ordrar?filter=delivered" class="dash-card">
                    <div class="dash-card__value"><?= $stats['delivered'] ?></div>
                    <div class="dash-card__label">Levererade denna säsong</div>
                </a>

            </div>
        </div>

        <div class="dashboard-section">
            <div class="dashboard-section__label">Kunder</div>
            <div class="dashboard-tiles">

                <a href="/admin/kunder" class="dash-card">
                    <div class="dash-card__value"><?= $stats['total_customers'] ?></div>
                    <div class="dash-card__label">Totalt antal kunder</div>
                </a>

                <div class="dash-card">
                    <div class="dash-card__value"><?= $stats['newsletter_count'] ?></div>
                    <div class="dash-card__label">Prenumeranter nyhetsbrev</div>
                </div>

            </div>
        </div>

    </div><!-- /dashboard-col left -->

    <!-- RIGHT COLUMN: Produktinfo + Hemsidan -->
    <div class="dashboard-col">

        <div class="dashboard-section">
            <div class="dashboard-section__label">Produktinfo</div>
            <div class="dashboard-tiles">

                <!-- TODO: Replace hardcoded product tiles with a "Show on dashboard"
                    checkbox per product in Produkter, so owner controls this without code changes.
                    Later addition after initial publish -->
                <a href="/admin/ordrar" class="dash-card">
                    <div class="dash-card__value"><?= $stats['product_totals']['bifor'] ?></div>
                    <div class="dash-card__label">Totalt sålda Bifor denna säsong</div>
                </a>

                <a href="/admin/ordrar" class="dash-card">
                    <div class="dash-card__value"><?= $stats['product_totals']['dulco'] ?></div>
                    <div class="dash-card__label">Totalt sålda Dulcofruct denna säsong</div>
                </a>

                <a href="/admin/ordrar" class="dash-card">
                    <div class="dash-card__value"><?= $stats['product_totals']['lackad'] ?></div>
                    <div class="dash-card__label">Totalt sålda Lackade lådor denna säsong</div>
                </a>

                <a href="/admin/produkter" class="dash-card">
                    <div class="dash-card__value"><?= $stats['active_products'] ?></div>
                    <div class="dash-card__label">Aktiva produkter på hemsidan</div>
                </a>

            </div>
        </div>

        <div class="dashboard-section">
            <div class="dashboard-section__label">Hemsidan</div>
            <div class="dashboard-tiles">

                <a href="/admin/oppettider" class="dash-card">
                    <div class="dash-card__label">Denna vecka (v.<?= $thisWeekNum ?>)</div>
                    <div class="dash-card__sub"><?= $thisWeekPlan ? Security::e($thisWeekPlan['header_text'] ?: 'Standard') : 'Ingen plan' ?></div>
                    <div class="dash-card__sub muted"><?= $thisWeekPlan ? Security::e($thisWeekPlan['type']) : '' ?></div>
                </a>

                <a href="/admin/oppettider?preview=next" class="dash-card">
                    <div class="dash-card__label">Nästa vecka (v.<?= $nextWeekNum ?>)</div>
                    <div class="dash-card__sub"><?= $nextWeekPlan ? Security::e($nextWeekPlan['header_text'] ?: 'Standard') : 'Ingen plan' ?></div>
                    <div class="dash-card__sub muted"><?= $nextWeekPlan ? Security::e($nextWeekPlan['type']) : '' ?></div>
                </a>

                <a href="/admin/ordrar" class="dash-card dash-card--status">
                    <div class="dash-card__label">Vinterfoder status:</div>
                    <div class="dash-card__sub">
                        <?php if ($preorderEnabled): ?>
                            <span class="badge-active">🟢 Aktiv</span>
                        <?php else: ?>
                            <span class="badge-inactive">🔴 Dold</span>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
        </div>
    </div><!-- /dashboard-col right -->
</div><!-- /dashboard-grid -->
