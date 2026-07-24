<?php require_once __DIR__ . '/../admin/_header.php'; ?>

<section class="admin-section">
    <h1>Notiser &amp; Banners</h1>

    <!-- Preorder toggle -->
    <div class="active-plan-preview" style="margin-bottom: var(--space-5);">
        <p class="preview-heading">Förbeställningar (vinterfoder)</p>
        <p class="source-label">Status: <strong><?= $preorderActive ? 'Öppen' : 'Stängd' ?></strong></p>
        <form method="post" action="/admin/notiser">
            <input type="hidden" name="action" value="toggle_preorder">
            <button type="submit" class="btn-toggle <?= $preorderActive ? 'btn-toggle--active' : '' ?>">
                <?= $preorderActive ? '🟢 Förbeställning öppen' : '🔴 Förbeställning stängd' ?>
            </button>
        </form>
    </div>

    <!-- Add / Edit banner -->
    <div class="active-plan-preview" style="margin-bottom: var(--space-5);">
        <p class="preview-heading"><?= $editBanner ? 'Redigera banner' : 'Lägg till banner' ?></p>
        <form method="post" action="/admin/notiser">
            <input type="hidden" name="action" value="<?= $editBanner ? 'update' : 'create' ?>">
            <?php if ($editBanner): ?>
                <input type="hidden" name="id" value="<?= (int) $editBanner['id'] ?>">
            <?php endif; ?>
            <label for="banner-type">Typ</label>
            <select id="banner-type" name="type" style="width:auto; margin-bottom: var(--space-3);">
                <?php foreach (['info' => 'Info (blå)', 'warning' => 'Varning (rödbrun)', 'success' => 'Bekräftelse (grön)'] as $val => $label): ?>
                    <option value="<?= $val ?>" <?= ($editBanner['type'] ?? '') === $val ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <label for="banner-message">Meddelande</label>
            <textarea id="banner-message" name="message" rows="2" required
                placeholder="Skriv ett meddelande som visas på webbplatsen…"
                style="margin-bottom: var(--space-3);"><?= $editBanner ? Security::e($editBanner['message']) : '' ?></textarea>
            <div class="form-submit-row" style="margin-top:0;">
                <?php if ($editBanner): ?>
                    <a href="/admin/notiser" class="btn-secondary-link">Avbryt</a>
                <?php endif; ?>
                <button type="submit"><?= $editBanner ? 'Spara ändringar' : 'Skapa banner' ?></button>
            </div>
        </form>
    </div>

    <!-- Banner table -->
    <?php if (empty($banners)): ?>
        <p class="hint">Inga banners skapade än.</p>
    <?php else: ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Typ</th>
                <th>Meddelande</th>
                <th>Skapad</th>
                <th class="center">Ordning</th>
                <th class="center">Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($banners as $b): ?>
            <tr class="<?= $b['active'] ? '' : 'banner-row--inactive' ?>">
                <td>
                    <span class="banner-pill banner-pill--<?= Security::e($b['type']) ?> <?= $b['active'] ? '' : 'banner-pill--muted' ?>">
                        <?= Security::e($b['type']) ?>
                    </span>
                </td>
                <td><?= Security::e($b['message']) ?></td>
                <td style="white-space:nowrap; font-size: var(--text-sm); color: var(--neutral-500);">
                    <?= Security::e(substr($b['created_at'], 0, 10)) ?>
                </td>
                <td class="center">
                    <form method="post" action="/admin/notiser" style="display:inline;">
                        <input type="hidden" name="action" value="move">
                        <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                        <input type="hidden" name="direction" value="up">
                        <button class="btn-icon" title="Flytta upp">▲</button>
                    </form>
                    <form method="post" action="/admin/notiser" style="display:inline;">
                        <input type="hidden" name="action" value="move">
                        <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                        <input type="hidden" name="direction" value="down">
                        <button class="btn-icon" title="Flytta ned">▼</button>
                    </form>
                </td>
                <td class="center">
                    <form method="post" action="/admin/notiser">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                        <input type="hidden" name="active" value="<?= $b['active'] ? '0' : '1' ?>">
                        <button class="btn-icon <?= $b['active'] ? 'btn-icon--active' : 'btn-icon--paused' ?>"
                            title="<?= $b['active'] ? 'Inaktivera' : 'Aktivera' ?>">
                            <?= $b['active'] ? '✅' : '⏸' ?>
                        </button>
                    </form>
                </td>
                <td style="white-space:nowrap;">
                    <div style="display:flex; gap: var(--space-2);">
                      <a href="/admin/notiser?edit=<?= (int) $b['id'] ?>" class="btn-icon" title="Redigera">✏️</a>
                      <form method="post" action="/admin/notiser"
                          onsubmit="return confirm('Ta bort denna banner?')">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                          <button class="btn-icon btn-icon--danger" title="Radera">🗑</button>
                      </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

</section>

<?php require_once __DIR__ . '/../admin/_footer.php'; ?>