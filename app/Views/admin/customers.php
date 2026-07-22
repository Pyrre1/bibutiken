<?php if ($message): ?>
    <div class="form-success"><p><?= Security::e($message) ?></p></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="form-error"><p><?= Security::e($error) ?></p></div>
<?php endif; ?>

<h1>Kunder</h1>

<!-- Role filter pills -->
<nav class="admin-filters" style="margin-bottom:var(--space-3)">
    <a href="?" class="<?= $roleFilter === null ? 'active' : '' ?>">Alla</a>
    <?php foreach ($allRoles as $role): ?>
        <a href="?role=<?= urlencode($role['name']) ?>"
            class="<?= $roleFilter === $role['name'] ? 'active' : '' ?>">
            <?= Security::e($role['name']) ?>
        </a>
    <?php endforeach; ?>
</nav>

<!-- Search -->
<form method="get" class="admin-search-row">
    <input type="text" name="q" value="<?= Security::e($searchTerm) ?>" placeholder="Sök namn eller e-post...">
    <button type="submit">Sök</button>
    <?php if ($viewId): ?>
        <input type="hidden" name="id" value="<?= $viewId ?>">
    <?php endif; ?>
    <?php if ($roleFilter): ?>
        <input type="hidden" name="role" value="<?= Security::e($roleFilter) ?>">
    <?php endif; ?>
</form>

<?php if ($searchTerm !== '' && empty($searchResults) && !$customer): ?>
    <p class="muted">Inga kunder hittades för "<?= Security::e($searchTerm) ?>".</p>
<?php endif; ?>

<?php if (!empty($searchResults)): ?>
    <table class="admin-table admin-table--tight">
        <thead>
            <tr>
                <th>Namn</th>
                <th>E-post</th>
                <th>Antal order</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($searchResults as $row): ?>
            <tr>
                <td><?= Security::e($row['name']) ?></td>
                <td><?= Security::e($row['email']) ?></td>
                <td class="center"><?= $row['order_count'] ?></td>
                <td><a href="?id=<?= $row['id'] ?>&q=<?= urlencode($searchTerm) ?>">Visa</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php if (!empty($allCustomers) && !$customer): ?>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:var(--space-3)">
        <span class="muted"><?= count($allCustomers) ?> kunder</span>
        <a href="/admin/export_customers.php<?= $roleFilter ? '?role='.urlencode($roleFilter) : '' ?>">
            Exportera som CSV
        </a>
    </div>
    <table class="admin-table admin-table--tight" id="customers-table">
        <thead>
            <tr>
                <th>Namn</th>
                <th>E-post</th>
                <th>Roller</th>
                <th>Order</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($allCustomers as $row): ?>
            <tr<?= $row['has_ingen_mejl'] ? ' class="row--no-mail"' : '' ?>>
                <td><?= Security::e($row['name']) ?></td>
                <td><?= Security::e($row['email']) ?></td>
                <td class="muted"><?= Security::e($row['role_names'] ?? '') ?></td>
                <td class="center"><?= $row['order_count'] ?></td>
                <td><a href="?id=<?= $row['id'] ?><?= $roleFilter ? '&role='.urlencode($roleFilter) : '' ?>">Visa</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div id="customers-pagination" class="admin-pagination"></div>
<?php endif; ?>

<?php if ($customer): ?>
    <div class="admin-customer-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:var(--space-4)">
            <h2 style="margin:0"><?= Security::e($customer['name']) ?></h2>
            <span class="muted"><?= Security::e($customer['email']) ?></span>
        </div>

        <!-- Edit info -->
        <details>
            <summary>Redigera kundinformation</summary>
            <form method="post" style="margin-top:var(--space-3)">
                <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">
                <input type="hidden" name="action" value="edit_customer">
                <input type="hidden" name="customer_id" value="<?= $customer['id'] ?>">
                <label>Namn <input type="text" name="name" value="<?= Security::e($customer['name']) ?>" required></label>
                <label>E-post <input type="email" name="email" value="<?= Security::e($customer['email']) ?>" required></label>
                <div class="form-submit-row">
                    <button type="submit">Spara</button>
                </div>
            </form>
        </details>

        <!-- Roles -->
        <details style="margin-top:var(--space-3)">
            <summary>Roller</summary>
            <form method="post" style="margin-top:var(--space-3)">
                <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">
                <input type="hidden" name="action" value="set_roles">
                <input type="hidden" name="customer_id" value="<?= $customer['id'] ?>">
                <?php $assignedRoleIds = array_column($customer['roles'], 'id'); ?>
                <?php foreach ($allRoles as $role): ?>
                    <label style="display:flex; gap:var(--space-2); align-items:center; margin-top:var(--space-2)">
                        <input type="checkbox" name="roles[]" value="<?= $role['id'] ?>"
                            <?= in_array($role['id'], $assignedRoleIds) ? 'checked' : '' ?>>
                        <?= Security::e($role['name']) ?>
                    </label>
                <?php endforeach; ?>
                <div class="form-submit-row">
                    <button type="submit">Uppdatera roller</button>
                </div>
            </form>
        </details>

        <!-- Orders -->
        <?php if (!empty($customer['orders'])): ?>
        <h3 style="margin-top:var(--space-5)">Beställningar</h3>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Ordernr</th>
                    <th>Datum</th>
                    <th>Status</th>
                    <th>Hantering</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($customer['orders'] as $order): ?>
                <tr>
                    <td><?= Security::e($order['order_number']) ?></td>
                    <td><?= date('Y-m-d', strtotime($order['created_at'])) ?></td>
                    <td><?= $order['is_delivered'] ? '✅' : ($order['has_manual_work'] ? '🔧' : '📤') ?></td>
                    <td><a href="/admin/orders.php?order=<?= $order['id'] ?>">Öppna</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p class="muted" style="margin-top:var(--space-4)">Inga beställningar.</p>
        <?php endif; ?>

        <!-- GDPR / anonymize -->
        <details style="margin-top:var(--space-6)">
            <summary class="btn-danger-text">GDPR — Radera personuppgifter</summary>
            <div style="margin-top:var(--space-3); display:flex; flex-direction:column; gap:var(--space-4)">

                <!-- Email only -->
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">
                    <input type="hidden" name="action" value="anonymize_email">
                    <input type="hidden" name="customer_id" value="<?= $customer['id'] ?>">
                    <p>Tar bort e-postadressen men behåller namnet för orderhistorik.</p>
                    <button type="submit"
                        onclick="return confirm('Radera e-postadressen för <?= Security::e(addslashes($customer['name'])) ?>?')">
                        Radera e-postadress
                    </button>
                </form>

                <!-- Full anonymize -->
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">
                    <input type="hidden" name="action" value="anonymize_full">
                    <input type="hidden" name="customer_id" value="<?= $customer['id'] ?>">
                    <p>Anonymiserar namn och e-post. Orderhistorik behålls men all personinfo tas bort.</p>
                    <label>Bekräfta namn: <input type="text" name="confirm_name" required></label>
                    <label>Bekräfta e-post: <input type="email" name="confirm_email" required></label>
                    <div class="form-submit-row">
                        <button type="submit" class="btn-danger"
                            onclick="return confirm('Anonymisera kunden permanent?')">
                            Anonymisera helt
                        </button>
                    </div>
                </form>
            </div>
        </details>
    </div>
<?php endif; ?>
