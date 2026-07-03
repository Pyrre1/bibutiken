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
                <th>Faktiskt pris/st</th>
                <th>Hantering</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($detailOrder['items'] as $item): ?>
            <tr>
                <td><?= Security::e($item['product_name']) ?></td>
                <td><?= $item['quantity'] ?></td>
                <td><?= number_format($item['unit_price_ore'] / 100, 2, ',', ' ') ?> kr</td>
                <td>
                    <?php if ($item['actual_price_ore'] !== null): ?>
                        <?= number_format($item['actual_price_ore'] / 100, 2, ',', ' ') ?> kr
                    <?php else: ?>
                        <em>Ej satt</em>
                    <?php endif; ?>
                    <form method="post" style="display:inline-block; margin-left:.5rem">
                        <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">
                        <input type="hidden" name="action" value="update_actual_price">
                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                        <input type="number" name="actual_price_kr" step="0.01" min="0"
                            placeholder="kr" style="width:80px"
                              value="<?= $item['actual_price_ore'] !== null ? number_format($item['actual_price_ore']/100,2,'.','') : '' ?>">
                        <button type="submit">Spara</button>
                    </form>
                </td>
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
            </tr>
        <?php endforeach; ?>
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

    <!-- Summary -->
    <div class="admin-stats-row">
        <span>Totalt: <?= $stats['total_orders'] ?></span>
        <span>Levererade: <?= $stats['delivered'] ?></span>
        <span>Manuell hantering väntar: <?= $stats['manual_pending'] ?></span>
    </div>

    <!-- Product summary -->
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

    <!-- Filters -->
    <nav class="admin-filters">
        <a href="?filter=all"       class="<?= $filter==='all'       ?'active':'' ?>">Alla</a>
        <a href="?filter=pending"   class="<?= $filter==='pending'   ?'active':'' ?>">Ej levererade</a>
        <a href="?filter=delivered" class="<?= $filter==='delivered' ?'active':'' ?>">Levererade</a>
        <a href="?filter=manual"    class="<?= $filter==='manual'    ?'active':'' ?>">Manuell hantering</a>
    </nav>

    <!-- CSV export -->
    <div class="admin-export-row">
        <a href="/admin/export_orders.php?type=all"       class="btn-secondary">Exportera alla e-post</a>
        <a href="/admin/export_orders.php?type=bifor"     class="btn-secondary">Endast Bifor</a>
        <a href="/admin/export_orders.php?type=dulco"     class="btn-secondary">Endast Dulcofruct</a>
        <a href="/admin/export_orders.php?type=both"      class="btn-secondary">Bifor + Dulcofruct</a>
        <a href="/admin/export_orders.php?type=unpicked" class="btn-secondary">Ej hämtat</a>
    </div>

    <!-- Orders table -->
    <table class="admin-table">
        <thead>
            <tr>
                <th>Ordernr</th>
                <th>Namn</th>
                <th>E-post</th>
                <th>Datum</th>
                <th>Levererad</th>
                <th>Hantering</th>
                <th>Utleverans</th>
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
                <td class="center"><?= $order['is_delivered'] ? '✓' : '–' ?></td>
                <td class="center"><?= $order['has_manual_work'] ? '<span class="badge-manual">🔧</span>' : '–' ?></td>
                <td>
                    <?php if (!$order['is_delivered']): ?>
                        <?php if ($order['has_manual_work']): ?>
                            <span class="muted" title="Manuell hantering ej klar">🔒</span>
                        <?php else: ?>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">
                                <input type="hidden" name="action" value="set_delivered">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <input type="hidden" name="delivered" value="1">
                                <button type="submit" class="btn-deliver"
                                    data-name="<?= Security::e($order['customer_name']) ?>"
                                    onclick="return confirmDeliver(this)">
                                    Utleverera
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
                <td><a href="?order=<?= $order['id'] ?>&filter=<?= Security::e($filter) ?>">Öppna</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($orders)): ?>
            <tr><td colspan="8"><em>Inga beställningar.</em></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <div id="orders-pagination" class="admin-pagination"></div>
<?php endif; ?>