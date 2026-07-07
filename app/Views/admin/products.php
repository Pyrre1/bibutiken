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
                    <td style="display:flex; gap:var(--space-2); align-items:center">
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