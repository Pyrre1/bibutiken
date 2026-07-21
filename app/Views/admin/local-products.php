<?php if ($message): ?>
    <div class="form-success"><p><?= Security::e($message) ?></p></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="form-error"><p><?= Security::e($error) ?></p></div>
<?php endif; ?>

<h1>Egna produkter</h1>

<?php
// Group products by type for display
$byType = [];
foreach ($products as $p) {
    $byType[$p['type_id']][] = $p;
}
?>

<div class="local-products-layout">

    <!-- ── Live list, one table per type ── -->
    <div class="local-products-list">

        <?php foreach ($types as $type): ?>
            <?php $typeProducts = $byType[$type['id']] ?? []; ?>
            <h2><?= Security::e($type['name']) ?></h2>

            <?php if (empty($typeProducts)): ?>
                <p class="hint">Inga produkter av den här typen ännu.</p>
            <?php else: ?>
            <table class="admin-table local-products-table"
                  data-type-id="<?= $type['id'] ?>">
                <thead>
                    <tr>
                        <th>Namn</th>
                        <th>Storlek</th>
                        <th>Beskrivning</th>
                        <th>Pris</th>
                        <th>Ordning</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="lp-tbody" data-type-id="<?= $type['id'] ?>">
                <?php foreach ($typeProducts as $p): ?>
                    <tr data-id="<?= $p['id'] ?>" class="lp-row">
                        <!-- Name -->
                        <td>
                            <span class="item-display"><?= Security::e($p['name']) ?></span>
                            <input class="item-edit" type="text" name="name"
                                  value="<?= Security::e($p['name']) ?>" style="display:none">
                        </td>
                        <!-- Size -->
                        <td>
                            <span class="item-display"><?= Security::e($p['size']) ?></span>
                            <input class="item-edit" type="text" name="size"
                                  value="<?= Security::e($p['size']) ?>" style="display:none; width:90px">
                        </td>
                        <!-- Description -->
                        <td class="lp-desc-cell">
                            <span class="item-display lp-desc-display">
                                <?= Security::e($p['description'] ?? '–') ?>
                            </span>
                            <textarea class="item-edit" name="description"
                                      rows="2" style="display:none"><?= Security::e($p['description'] ?? '') ?></textarea>
                        </td>
                        <!-- Price -->
                        <td>
                            <span class="item-display">
                                <?= number_format($p['price_ore'] / 100, 2, ',', ' ') ?> kr
                            </span>
                            <input class="item-edit" type="number" name="price_kr"
                                  step="0.01" min="0"
                                  value="<?= number_format($p['price_ore'] / 100, 2, '.', '') ?>"
                                  style="display:none; width:90px">
                        </td>
                        <!-- Sort -->
                        <td class="center">
                            <button type="button" class="btn-icon btn-sort-up"
                                    data-id="<?= $p['id'] ?>">▲</button>
                            <button type="button" class="btn-sort-down btn-icon"
                                    data-id="<?= $p['id'] ?>">▼</button>
                        </td>
                        <!-- Toggle active -->
                        <td class="center">
                            <form method="post" style="display:inline">
                                <input type="hidden" name="csrf_token"
                                      value="<?= Security::e(Security::csrfToken()) ?>">
                                <input type="hidden" name="action" value="toggle_active">
                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="active"
                                      value="<?= $p['active'] ? '0' : '1' ?>">
                                <button type="submit" class="btn-icon"
                                        title="<?= $p['active'] ? 'Dölj' : 'Visa' ?>">
                                    <?= $p['active'] ? '✅' : '⏸️' ?>
                                </button>
                            </form>
                        </td>
                        <!-- Edit / Save / Delete -->
                        <td style="display:flex; gap:var(--space-2); align-items:center">
                            <button type="button" class="btn-icon btn-edit-lp"
                                    title="Redigera">✏️</button>

                            <form method="post" class="item-edit lp-save-form"
                                  style="display:none">
                                <input type="hidden" name="csrf_token"
                                      value="<?= Security::e(Security::csrfToken()) ?>">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="type_id"
                                      value="<?= $p['type_id'] ?>">
                                <input type="hidden" name="name"     class="save-name">
                                <input type="hidden" name="size"     class="save-size">
                                <input type="hidden" name="description" class="save-desc">
                                <input type="hidden" name="price_kr" class="save-price">
                                <button type="submit" class="btn-icon" title="Spara">💾</button>
                            </form>
                            <button type="button"
                                    class="btn-icon btn-cancel-lp item-edit"
                                    style="display:none" title="Avbryt">✕</button>

                            <form method="post" class="item-display" style="display:inline">
                                <input type="hidden" name="csrf_token"
                                      value="<?= Security::e(Security::csrfToken()) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn-icon btn-icon--danger"
                                        title="Ta bort"
                                        onclick="return confirm('Ta bort <?= Security::e(addslashes($p['name'])) ?>?')">
                                    🗑️
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <!-- ── Add product form ── -->
    <div class="local-products-add-form">
        <h2>Lägg till produkt</h2>
        <form method="post" class="lp-add-form">
            <input type="hidden" name="csrf_token"
                  value="<?= Security::e(Security::csrfToken()) ?>">
            <input type="hidden" name="action" value="create">

            <div class="lp-add-field">
                <label for="lp-type">Typ av produkt</label>
                <select id="lp-type" name="type_id" required>
                    <option value="">– välj –</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= Security::e($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="lp-add-field">
                <label for="lp-size">Storlek</label>
                <input type="text" id="lp-size" name="size"
                      placeholder="t.ex. 500 g" required>
            </div>
            <div class="lp-add-field lp-add-field--name">
                <label for="lp-name">Namn</label>
                <input type="text" id="lp-name" name="name"
                      placeholder="t.ex. Sommarhonung" required>
            </div>
            <div class="lp-add-field lp-add-field--desc">
                <label for="lp-desc">Beskrivning <span class="hint-inline">(valfri)</span></label>
                <textarea id="lp-desc" name="description" rows="2"></textarea>
            </div>
            <div class="lp-add-field">
                <label for="lp-price">Pris (kr)</label>
                <input type="number" id="lp-price" name="price_kr"
                      step="0.01" min="0" required style="width:110px">
            </div>
            <div class="lp-add-field lp-add-field--submit">
                <button type="submit">Skapa produkt</button>
            </div>
        </form>
    </div>

</div>