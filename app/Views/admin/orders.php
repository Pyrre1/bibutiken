<?php if ($message): ?>
    <div class="form-success"><p><?= Security::e($message) ?></p></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="form-error"><p><?= Security::e($error) ?></p></div>
<?php endif; ?>

<?php if ($detailOrder): ?>
    <!-- DETAIL VIEW -->
    <h1>Order <?= Security::e($detailOrder['order_number']) ?></h1>
    <p><?= Security::e($detailOrder['customer_name']) ?> — <?= Security::e($detailOrder['customer_email']) ?></p>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:var(--space-4)">
        <span>Lagd: <?= date('Y-m-d H:i', strtotime($detailOrder['created_at'])) ?></span>
        <a href="/admin/orders.php" class="btn-secondary-link">← Tillbaka till alla beställningar</a>
    </div>

    <table class="admin-table" id="orders-table">
        <thead>
            <tr>
                <th>Produkt</th>
                <th>Antal</th>
                <th>Estimat/st</th>
                <th>Hantering</th>
                <th></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($detailOrder['items'] as $item): ?>
        <tr data-item-id="<?= $item['id'] ?>">
            <td>
                <span class="item-display"><?= Security::e($item['product_name']) ?></span>
                <select name="product_id" class="item-edit" style="display:none">
                    <?php foreach ($allProducts as $p): 
                        $isCurrentItem = $p['id'] === $item['product_id'];
                        $alreadyInOrder = in_array($p['id'], array_column($detailOrder['items'], 'product_id'));
                    ?>
                        <option value="<?= $p['id'] ?>" 
                            <?= $isCurrentItem ? 'selected' : '' ?>
                            <?= (!$isCurrentItem && $alreadyInOrder) ? 'disabled' : '' ?>>
                            <?= Security::e($p['name']) ?><?= (!$isCurrentItem && $alreadyInOrder) ? ' (redan i order)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <span class="item-display"><?= $item['quantity'] ?></span>
                <input type="number" class="item-edit" style="display:none;width:70px" 
                      value="<?= $item['quantity'] ?>" min="1" max="9999">
            </td>
            <td><?= number_format($item['unit_price_ore'] / 100, 2, ',', ' ') ?> kr</td>
            <td>
                <?php if ($item['needs_manual_work']): ?>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">
                        <input type="hidden" name="action" value="set_manual_status">
                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                        <select name="status" onchange="this.form.submit()">
                            <option value="ej_behandlad" <?= $item['manual_work_status']==='ej_behandlad'?'selected':'' ?>>Ej behandlad</option>
                            <option value="fardig" <?= $item['manual_work_status']==='fardig'?'selected':'' ?>>Färdig</option>
                        </select>
                    </form>
                <?php else: ?>
                    <span class="muted">–</span>
                <?php endif; ?>
            </td>
            <td>
                <!-- Edit/Save/Cancel -->
                <button type="button" class="btn-edit-row">Redigera</button>
                <form method="post" class="item-edit" style="display:none">
                    <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">
                    <input type="hidden" name="action" value="update_order_item">
                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                    <input type="hidden" name="product_id" class="save-product-id" value="<?= $item['product_id'] ?>">
                    <input type="hidden" name="quantity" class="save-quantity" value="<?= $item['quantity'] ?>">
                    <button type="submit">Spara</button>
                    <button type="button" class="btn-cancel-row">Avbryt</button>
                </form>
            </td>
            <td>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">
                    <input type="hidden" name="action" value="delete_order_item">
                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                    <button type="submit" class="btn-icon btn-icon--danger"
                        onclick="return confirm('Ta bort <?= Security::e(addslashes($item['product_name'])) ?> från ordern?')">✕</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>

        <!-- Add item row -->
        <tr>
            <td colspan="6">
                <form method="post" class="add-item-form">
                    <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">
                    <input type="hidden" name="action" value="add_order_item">
                    <input type="hidden" name="order_id" value="<?= $detailOrder['id'] ?>">
                    <select name="product_id" required>
                        <option value="">Välj produkt att lägga till...</option>
                        <?php foreach ($allProducts as $p): 
                            $alreadyInOrder = in_array($p['id'], array_column($detailOrder['items'], 'product_id'));
                        ?>
                            <option value="<?= $p['id'] ?>" <?= $alreadyInOrder ? 'disabled' : '' ?>>
                                <?= Security::e($p['name']) ?><?= $alreadyInOrder ? ' (redan i order)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="quantity" value="1" min="1" max="9999" style="width:70px">
                    <button type="submit">+ Lägg till rad</button>
                </form>
            </td>
        </tr>
        </tbody>
    </table>

    <!-- Delivered toggle -->
    <form method="post" style="margin-top:1rem">
        <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">
        <input type="hidden" name="action" value="set_delivered">
        <input type="hidden" name="order_id" value="<?= $detailOrder['id'] ?>">
        <input type="hidden" name="delivered" value="<?= $detailOrder['is_delivered'] ? '0' : '1' ?>">
        <button type="submit" class="btn-<?= $detailOrder['is_delivered'] ? 'warning' : 'success' ?>">
            <?= $detailOrder['is_delivered'] ? 'Markera som ej levererad' : 'Markera som levererad' ?>
        </button>
    </form>

    <!-- Delete order -->
    <details style="margin-top:2rem">
        <summary class="btn-danger-text">Ta bort order</summary>
        <form method="post" style="margin-top:.75rem">
            <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">
            <input type="hidden" name="action" value="delete_order">
            <input type="hidden" name="order_id" value="<?= $detailOrder['id'] ?>">
            <label>Bekräfta ordernummer: <input type="text" name="confirm_order_number" required></label><br><br>
            <label>Bekräfta kundnamn: <input type="text" name="confirm_customer_name" required></label><br><br>
            <button type="submit" class="btn-danger">Ta bort order permanent</button>
        </form>
    </details>

<?php else: ?>
    <!-- LIST VIEW -->
    <h1>Beställningar</h1>

    <!-- Summary + preorder toggle -->
    <div class="admin-orders-header">
        <div class="admin-stats-row">
            <span>Totalt: <?= $stats['total_orders'] ?></span>
            <span>Levererade: <?= $stats['delivered'] ?></span>
            <span>Manuell hantering väntar: <?= $stats['manual_pending'] ?></span>
            <button type="button" id="stats-reload-btn" class="btn-icon" style="display:none"
                title="Uppdatera statistik" onclick="location.reload()">🔄</button>
        </div>
        <form method="post" class="admin-preorder-toggle">
            <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">
            <input type="hidden" name="action" value="toggle_preorder">
            <button type="submit"
                class="<?= $preorderEnabled ? 'btn-toggle-active' : 'btn-toggle-inactive' ?>">
                <?= $preorderEnabled ? '🟢 Förbeställning aktiv' : '🔴 Förbeställning dold' ?>
            </button>
        </form>
    </div>

    <!-- Product summary -->
    <div class="admin-summary-row">
        <table class="admin-summary-table">
            <thead><tr><th>Produkt</th><th>Totalt beställt</th></tr></thead>
            <tbody>
            <?php foreach ($summary as $row): ?>
                <tr>
                    <td><?= Security::e($row['name']) ?></td>
                    <td><?= $row['total_qty'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <aside class="admin-legend">
            <p><strong>Knappar/Symboler:</strong></p>
            <ul>
                <li>📤: Utleverera</li>
                <li>🔧: Manuell hantering</li>
                <li>✅: Status klar</li>
                <li>❌: Status ej klar</li>
            </ul>
            <p><strong>I tabellen:</strong></p>
            <ul>
                <li>"Öppna": öppnar upp ordern i detaljvy</li>
            </ul>
            <p><strong>Exportering av filer:</strong></p>
            <ul>
                <li>Alla: Exporterar alla ordrar, hämtade eller ej med status.</li>
                <li>Separerad: En fil uppdelad på vad kunder beställt, för personligare mejl.</li>
                <li>Ej hämtat: Alla ordrar med status "Ej hämtat"</li>
            </ul>
        </aside>
    </div>

    <!-- Filters -->
    <nav class="admin-filters">
        <a href="?filter=all"        class="<?= $filter==='all'        ?'active':'' ?>">Alla</a>
        <a href="?filter=pending"    class="<?= $filter==='pending'    ?'active':'' ?>">📤:❌</a>
        <a href="?filter=delivered"  class="<?= $filter==='delivered'  ?'active':'' ?>">📤:✅</a>
        <a href="?filter=manual"     class="<?= $filter==='manual'     ?'active':'' ?>">🔧:❌</a>
        <a href="?filter=manual_any" class="<?= $filter==='manual_any' ?'active':'' ?>">🔧:✅+❌</a>
    </nav>

    <!-- CSV export -->
    <div class="admin-export-row">
        <span>Exportera e-postlista:</span>
        <a href="/admin/export_orders.php?type=all">Alla</a>
        <a href="/admin/export_orders.php?type=separated">Separerad (Bifor / Dulcofruct / Båda)</a>
        <a href="/admin/export_orders.php?type=unpicked">Ej hämtat</a>
    </div>

    <!-- Orders table -->
    <table class="admin-table">
        <thead>
            <tr>
                <th>Ordernr</th>
                <th>Namn</th>
                <th>E-post</th>
                <th>Datum</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td><?= Security::e($order['order_number']) ?></td>
                <td><?= Security::e($order['customer_name']) ?></td>
                <td><?= Security::e($order['customer_email']) ?></td>
                <td data-sort="<?= $order['created_at'] ?>"><?= date('Y-m-d', strtotime($order['created_at'])) ?></td>
                <td class="center">
                    <?php if ($order['has_manual_work']): ?>
                        <a href="?order=<?= $order['id'] ?>&filter=<?= Security::e($filter) ?>"
                          class="badge-manual" title="Öppna för manuell hantering">🔧</a>
                    <?php elseif (!$order['is_delivered']): ?>
                        <button type="button" class="btn-icon status-deliver-btn"
                            data-order-id="<?= $order['id'] ?>"
                            data-csrf="<?= Security::e(Security::csrfToken()) ?>">📤</button>
                    <?php else: ?>
                        <button type="button" class="btn-icon status-undeliver-btn"
                            data-order-id="<?= $order['id'] ?>"
                            data-csrf="<?= Security::e(Security::csrfToken()) ?>">✅</button>
                    <?php endif; ?>
                </td>
                <td><a href="?order=<?= $order['id'] ?>&filter=<?= Security::e($filter) ?>">Öppna</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($orders)): ?>
            <tr><td colspan="6"><em>Inga beställningar.</em></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <div id="orders-pagination" class="admin-pagination"></div>
<?php endif; ?>