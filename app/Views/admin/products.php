<?php if ($message): ?>
    <div class="form-success"><p><?= Security::e($message) ?></p></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="form-error"><p><?= Security::e($error) ?></p></div>
<?php endif; ?>

<h1>Produkter</h1>

<div class="products-layout">

    <!-- Live list -->
    <div class="products-list" id="products-list">
        <div id="sort-apply-row" style="display:none">
            <button type="button" id="apply-sort-btn">Tillämpa ordning</button>
            <button type="button" id="cancel-sort-btn" class="btn-secondary-link" style="margin-left:var(--space-3)">Avbryt</button>
        </div>
        <table class="admin-table" id="products-table">
            <thead>
                <tr>
                    <th>Namn</th>
                    <th>Pris</th>
                    <th>🔧</th>
                    <th>Ordning</th>
                    <th>Status</th>
                    <th></th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="products-tbody">
            <?php foreach ($products as $p): ?>
                <tr data-id="<?= $p['id'] ?>" class="product-row">
                    <!-- Display mode -->
                    <td>
                        <span class="item-display"><?= Security::e($p['name']) ?></span>
                        <input class="item-edit" type="text" name="name"
                            value="<?= Security::e($p['name']) ?>" style="display:none">
                    </td>
                    <td>
                        <span class="item-display"><?= number_format($p['price_ore'] / 100, 2, ',', ' ') ?> kr</span>
                        <input class="item-edit" type="number" name="price_kr" step="0.01" min="0"
                            value="<?= number_format($p['price_ore'] / 100, 2, '.', '') ?>"
                            style="display:none; width:90px">
                    </td>
                    <td>
                        <span class="item-display"><?= $p['needs_manual_work'] ? 'Ja' : 'Nej' ?></span>
                        <select class="item-edit" name="needs_manual_work" style="display:none">
                            <option value="0" <?= !$p['needs_manual_work'] ? 'selected' : '' ?>>Nej</option>
                            <option value="1" <?= $p['needs_manual_work'] ? 'selected' : '' ?>>Ja</option>
                        </select>
                    </td>
                    <td class="center">
                        <button type="button" class="btn-icon btn-sort-up" data-id="<?= $p['id'] ?>">▲</button>
                        <button type="button" class="btn-icon btn-sort-down" data-id="<?= $p['id'] ?>">▼</button>
                    </td>
                    <td class="center">
                        <form method="post" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">
                            <input type="hidden" name="action" value="toggle_active">
                            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                            <input type="hidden" name="active" value="<?= $p['active'] ? '0' : '1' ?>">
                            <button type="submit" class="btn-icon" title="<?= $p['active'] ? 'Dölj' : 'Visa' ?>">
                                <?= $p['active'] ? '✅' : '⏸️' ?>
                            </button>
                        </form>
                    </td>
                    <td style="white-space:nowrap; vertical-align:middle;">
                        <div style="display:flex; gap:var(--space-2); align-items:center;">
                            <!-- Edit button -->
                            <button type="button" class="btn-icon btn-edit-row" title="Redigera">✏️</button>

                            <!-- Save/cancel (edit mode) -->
                            <form method="post" class="item-edit" style="display:none">
                                <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="name" class="save-name" value="<?= Security::e($p['name']) ?>">
                                <input type="hidden" name="price_kr" class="save-price" value="<?= number_format($p['price_ore'] / 100, 2, '.', '') ?>">
                                <input type="hidden" name="needs_manual_work" class="save-manual" value="<?= $p['needs_manual_work'] ?>">
                                <button type="submit" class="btn-icon" title="Spara">💾</button>
                            </form>
                            <button type="button" class="btn-icon btn-cancel-row item-edit" style="display:none" title="Avbryt">✕</button>

                            <!-- Delete -->
                            <form method="post" class="item-display" style="display:inline">
                                <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn-icon btn-icon--danger" title="Ta bort"
                                    onclick="return confirm('Ta bort <?= Security::e(addslashes($p['name'])) ?>?')">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Add product form -->
    <div class="products-add-form">
        <h2>Lägg till produkt</h2>
        <form method="post" class="product-add-row">
            <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">
            <input type="hidden" name="action" value="create">
            <div class="product-add-field product-add-field--name">
                <label for="new-name">Namn</label>
                <input type="text" id="new-name" name="name" required>
            </div>
            <div class="product-add-field">
                <label for="new-price">Pris (kr)</label>
                <input type="number" id="new-price" name="price_kr" step="0.01" min="0" required style="width:110px">
            </div>
            <div class="product-add-field product-add-field--check">
                <label>Manuell hantering?</label>
                <input type="checkbox" name="needs_manual_work" value="1">
            </div>
            <div class="product-add-field product-add-field--submit">
                <button type="submit">Skapa produkt</button>
            </div>
        </form>
    </div>

</div>

<!-- ═══════════════════════════════════════════════════════
    LAGERSALDO
    ═══════════════════════════════════════════════════════ -->
<div class="saldo-section">
    <h2>Lagerpåfyllning</h2>

    <form method="post" class="saldo-add-form">
        <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">
        <input type="hidden" name="action" value="lagersaldo_create">
        <div class="saldo-add-row">
            <div class="saldo-field">
                <label for="saldo-product">Produkt</label>
                <select id="saldo-product" name="saldo_product_id" required>
                    <option value="">– välj –</option>
                    <?php foreach ($products as $p): ?>
                        <?php if ($p['active'] && !$p['deprecated']): ?>
                        <option value="<?= $p['id'] ?>"><?= Security::e($p['name']) ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="saldo-field">
                <label for="saldo-qty">Antal</label>
                <input type="number" id="saldo-qty" name="saldo_quantity" min="1" required style="width:90px">
            </div>
            <div class="saldo-field">
                <label for="saldo-date">Datum för påfyllning</label>
                <input type="date" id="saldo-date" name="saldo_date" required
                    value="<?= date('Y-m-d') ?>">
            </div>
            <div class="saldo-field">
                <label for="saldo-price">Styckpris till kund (kr)</label>
                <input type="number" id="saldo-price" name="saldo_price_kr" min="1" required style="width:110px">
            </div>
            <div class="saldo-field saldo-field--submit">
                <button type="submit">Spara påfyllning</button>
            </div>
        </div>
    </form>

    <?php
    $hasHidden = false;
    $visibleSaldo = array_filter($lagersaldo, fn($r) => !$r['hidden']);
    $hiddenSaldo  = array_filter($lagersaldo, fn($r) => $r['hidden']);
    $hasHidden = !empty($hiddenSaldo);
    ?>

    <?php if (!empty($visibleSaldo)): ?>
    <table class="admin-table saldo-table">
        <thead>
            <tr>
                <th>Produkt</th>
                <th>Antal</th>
                <th>Kvar</th>
                <th>Datum</th>
                <th>Pris/st</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($visibleSaldo as $s): ?>
            <tr>
                <td><?= Security::e($s['product_name']) ?></td>
                <td><?= (int)$s['quantity'] ?></td>
                <td><?= (int)$s['remaining'] ?></td>
                <td><?= Security::e($s['restocked_at']) ?></td>
                <td><?= number_format($s['calculated_price_ore'] / 100, 2, ',', ' ') ?> kr</td>
                <td>
                    <form method="post" style="display:inline">
                        <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">
                        <input type="hidden" name="action" value="lagersaldo_delete">
                        <input type="hidden" name="saldo_id" value="<?= (int)$s['id'] ?>">
                        <button type="submit" class="btn-icon btn-icon--danger" title="Ta bort"
                            onclick="return confirm('Ta bort denna lagerrad? Priser räknas om.')">🗑️</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <?php if ($hasHidden): ?>
    <p style="margin-top:var(--space-3)">
        <button type="button" class="btn-secondary-link" id="show-old-saldo-btn">
            Visa gamla lagerrader (<?= count($hiddenSaldo) ?>)
        </button>
    </p>
    <table class="admin-table saldo-table" id="old-saldo-table" style="display:none">
        <thead>
            <tr><th>Produkt</th><th>Antal</th><th>Kvar</th><th>Datum</th><th>Pris/st</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($hiddenSaldo as $s): ?>
            <tr>
                <td><?= Security::e($s['product_name']) ?></td>
                <td><?= (int)$s['quantity'] ?></td>
                <td><?= (int)$s['remaining'] ?></td>
                <td><?= Security::e($s['restocked_at']) ?></td>
                <td><?= number_format($s['calculated_price_ore'] / 100, 2, ',', ' ') ?> kr</td>
                <td>
                    <form method="post" style="display:inline">
                        <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">
                        <input type="hidden" name="action" value="lagersaldo_delete">
                        <input type="hidden" name="saldo_id" value="<?= (int)$s['id'] ?>">
                        <button type="submit" class="btn-icon btn-icon--danger" title="Ta bort"
                            onclick="return confirm('Ta bort denna lagerrad? Priser räknas om.')">🗑️</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- ═══════════════════════════════════════════════════════
    BUTIKSFÖRSÄLJNING (local sales)
    ═══════════════════════════════════════════════════════ -->
<div class="saldo-section">
    <h2>Butiksförsäljning</h2>
    <p class="section-hint">Lägg till en eller flera rader och skicka allt på en gång.</p>

    <form method="post" id="local-sales-form">
        <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">
        <input type="hidden" name="action" value="local_sales_create">

        <div id="local-sales-rows">
            <!-- JS adds rows here; first row rendered server-side as template -->
            <div class="ls-row">
                <div class="saldo-field">
                    <label>Produkt</label>
                    <select name="ls_product_id[]" required>
                        <option value="">– välj –</option>
                        <?php foreach ($products as $p): ?>
                            <?php if ($p['active'] && !$p['deprecated']): ?>
                            <option value="<?= $p['id'] ?>"><?= Security::e($p['name']) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="saldo-field">
                    <label>Antal</label>
                    <input type="number" name="ls_quantity[]" min="1" required style="width:80px">
                </div>
                <div class="saldo-field">
                    <label>Datum</label>
                    <input type="date" name="ls_date[]" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="saldo-field ls-remove-col">
                    <label>&nbsp;</label>
                    <button type="button" class="btn-icon btn-icon--danger ls-remove-btn" title="Ta bort rad" style="visibility:hidden">✕</button>
                </div>
            </div>
        </div>

        <div style="display:flex; gap:var(--space-3); margin-top:var(--space-3); align-items:center">
            <button type="button" id="ls-add-row-btn" class="btn-secondary-link">+ Lägg till rad</button>
            <button type="submit">Spara butiksförsäljning</button>
        </div>
    </form>

    <?php if (!empty($localSales)): ?>
    <table class="admin-table saldo-table" style="margin-top:var(--space-5)">
        <thead>
            <tr><th>Produkt</th><th>Antal</th><th>Datum</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($localSales as $ls): ?>
            <tr>
                <td><?= Security::e($ls['product_name']) ?></td>
                <td><?= (int)$ls['quantity'] ?></td>
                <td><?= Security::e($ls['sold_at']) ?></td>
                <td>
                    <form method="post" style="display:inline">
                        <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">
                        <input type="hidden" name="action" value="local_sale_delete">
                        <input type="hidden" name="ls_id" value="<?= (int)$ls['id'] ?>">
                        <button type="submit" class="btn-icon btn-icon--danger" title="Ta bort"
                            onclick="return confirm('Ta bort denna butiksförsäljning? Priser räknas om.')">🗑️</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>