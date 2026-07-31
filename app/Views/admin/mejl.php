<h1>Mejlmallar</h1>

<?php if ($message): ?>
    <div class="form-success"><p><?= Security::e($message) ?></p></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="form-error"><p><?= Security::e($error) ?></p></div>
<?php endif; ?>

<p style="margin-bottom:var(--space-4)">
    Mallarna används som standard i utskicksmodaler under Beställningar.
    Ändrar du en mall här uppdateras förifyldda värden direkt.
    Variablerna <code>{namn}</code>, <code>{varor}</code> och <code>{ordernr}</code>
    ersätts automatiskt per mottagare vid utskick.
</p>

<!-- Hidden delete form — submitted via JS, avoids nested <form> -->
<form method="post" id="form-delete-template" style="display:none">
    <input type="hidden" name="csrf_token"   value="<?= Security::e(Security::csrfToken()) ?>">
    <input type="hidden" name="action"       value="delete_template">
    <input type="hidden" name="template_id"  id="delete-template-id" value="">
</form>

<?php if (empty($templates)): ?>
    <p><em>Inga mallar skapade ännu.</em></p>
<?php else: ?>
    <?php
    $grouped = [];
    foreach ($templates as $t) {
        $grouped[$t['roll']][] = $t;
    }
    ?>
    <?php foreach ($grouped as $roll => $group): ?>
        <h2 style="margin-top:var(--space-6);margin-bottom:var(--space-2);
                  text-transform:capitalize;font-size:1rem;color:var(--color-muted)">
            Roll: <?= Security::e($roll) ?>
        </h2>

        <?php foreach ($group as $t): ?>
            <details class="mail-template-card" id="tmpl-<?= $t['id'] ?>">
                <summary class="mail-template-summary">
                    <span class="tmpl-name"><?= Security::e($t['namn']) ?></span>
                    <span class="tmpl-meta muted" style="font-size:.85em">
                        Uppdaterad: <?= date('Y-m-d', strtotime($t['updated_at'])) ?>
                    </span>
                </summary>

                <form method="post" class="mail-template-form">
                    <input type="hidden" name="csrf_token"  value="<?= Security::e(Security::csrfToken()) ?>">
                    <input type="hidden" name="action"      value="save_template">
                    <input type="hidden" name="template_id" value="<?= $t['id'] ?>">

                    <label class="modal-label" for="namn-<?= $t['id'] ?>">Mallnamn</label>
                    <input type="text" id="namn-<?= $t['id'] ?>" name="namn"
                          value="<?= Security::e($t['namn']) ?>" required
                          class="modal-subject-input">

                    <label class="modal-label" for="roll-<?= $t['id'] ?>">Roll</label>
                    <select id="roll-<?= $t['id'] ?>" name="roll" class="modal-subject-input">
                        <?php foreach ($allRoles as $r): ?>
                            <option value="<?= Security::e($r) ?>"
                                <?= $r === $t['roll'] ? 'selected' : '' ?>>
                                <?= Security::e($r) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label class="modal-label" for="amne-<?= $t['id'] ?>">Ämnesrad</label>
                    <input type="text" id="amne-<?= $t['id'] ?>" name="amne"
                          value="<?= Security::e($t['amne']) ?>" required
                          class="modal-subject-input">

                    <label class="modal-label" for="brodtext-<?= $t['id'] ?>">
                        Brödtext
                        <span class="modal-hint">— infoga variabel:</span>
                    </label>
                    <div class="modal-var-btns">
                        <button type="button" class="btn-secondary-link insert-var-btn"
                                data-target="brodtext-<?= $t['id'] ?>" data-var="{namn}">+ namn</button>
                        <button type="button" class="btn-secondary-link insert-var-btn"
                                data-target="brodtext-<?= $t['id'] ?>" data-var="{varor}">+ varor</button>
                        <button type="button" class="btn-secondary-link insert-var-btn"
                                data-target="brodtext-<?= $t['id'] ?>" data-var="{ordernr}">+ ordernr</button>
                    </div>
                    <textarea id="brodtext-<?= $t['id'] ?>" name="brodtext"
                              rows="10" required
                              class="modal-textarea"><?= Security::e($t['brodtext']) ?></textarea>

                    <div class="form-submit-row" style="justify-content:space-between;align-items:center">
                        <button type="submit">Spara ändringar</button>
                        <button type="button" class="btn-danger-text delete-template-btn"
                                data-id="<?= $t['id'] ?>"
                                data-namn="<?= Security::e($t['namn']) ?>">
                            Ta bort mall
                        </button>
                    </div>
                </form>
            </details>
        <?php endforeach; ?>
    <?php endforeach; ?>
<?php endif; ?>

<!-- New template -->
<details class="mail-template-card" id="tmpl-new" style="margin-top:var(--space-6)">
    <summary class="mail-template-summary">
        <span class="tmpl-name">+ Ny mall</span>
    </summary>

    <form method="post" class="mail-template-form">
        <input type="hidden" name="csrf_token"  value="<?= Security::e(Security::csrfToken()) ?>">
        <input type="hidden" name="action"      value="save_template">
        <input type="hidden" name="template_id" value="0">

        <label class="modal-label" for="namn-new">Mallnamn</label>
        <input type="text" id="namn-new" name="namn" required class="modal-subject-input"
              placeholder="T.ex. Orderbekräftelse">

        <label class="modal-label" for="roll-new">Roll</label>
        <select id="roll-new" name="roll" class="modal-subject-input">
            <?php foreach ($allRoles as $r): ?>
                <option value="<?= Security::e($r) ?>"><?= Security::e($r) ?></option>
            <?php endforeach; ?>
        </select>

        <label class="modal-label" for="amne-new">Ämnesrad</label>
        <input type="text" id="amne-new" name="amne" required class="modal-subject-input"
              placeholder="T.ex. Din beställning hos Strängnäs Biredskap">

        <label class="modal-label" for="brodtext-new">
            Brödtext
            <span class="modal-hint">— infoga variabel:</span>
        </label>
        <div class="modal-var-btns">
            <button type="button" class="btn-secondary-link insert-var-btn"
                    data-target="brodtext-new" data-var="{namn}">+ namn</button>
            <button type="button" class="btn-secondary-link insert-var-btn"
                    data-target="brodtext-new" data-var="{varor}">+ varor</button>
            <button type="button" class="btn-secondary-link insert-var-btn"
                    data-target="brodtext-new" data-var="{ordernr}">+ ordernr</button>
        </div>
        <textarea id="brodtext-new" name="brodtext" rows="10" required
                  class="modal-textarea"
                  placeholder="Hej {namn}, ..."></textarea>

        <div class="form-submit-row">
            <button type="submit">Skapa mall</button>
        </div>
    </form>
</details>