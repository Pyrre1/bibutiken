<div class="hours-admin-layout">
    <div class="col-left">
        <div class="actions">
            <a href="/admin/hours.php?mode=default" class="button">Redigera standard</a>
            <?php if (count($longTermOptions) < 3): ?>
                <a href="/admin/hours.php?mode=long&id=new" class="button">Lägg till periodplan</a>
            <?php endif; ?>
            <a href="/admin/hours.php?mode=week&id=new" class="button">Lägg till veckoplan</a>
        </div>

        <?php if ($message): ?><p class="success"><?= Security::e($message) ?></p><?php endif; ?>
        <?php if ($error): ?><p class="error"><?= Security::e($error) ?></p><?php endif; ?>

        <?php if ($mode === 'view' && $activePlan): ?>
            <div class="active-plan-preview">
                <p class="preview-heading">Öppettider publicerade på hemsidan nu:</p>
                <p class="source-label">
                    <?php
                    $sourceLabels = [
                        'default' => 'Källa: Standardöppettider',
                        'long_term' => 'Källa: Periodplan',
                        'week_specific' => 'Källa: Veckoplan',
                    ];
                    echo Security::e($sourceLabels[$activePlan['type']] ?? '');
                    ?>
                </p>

                <?php if ($activePlan['header_text']): ?><h3><?= Security::e($activePlan['header_text']) ?></h3><?php endif; ?>
                <?php if ($activePlan['free_text_1']): ?><p><?= nl2br(Security::e($activePlan['free_text_1'])) ?></p><?php endif; ?>

                <?php
                $anyOpenDay = false;
                foreach ($activePlan['days'] as $day) { if (!$day['closed']) { $anyOpenDay = true; break; } }
                ?>
                <?php if ($anyOpenDay): ?>
                    <ul class="hours-preview-list">
                        <?php foreach ($activePlan['days'] as $day): $d = $day['day_of_week']; ?>
                            <li>
                                <?= $dayNames[$d] ?>:
                                <?php if ($day['closed']): ?>Stängt<?php else: ?>
                                    <?= Security::e(substr($day['open_time'], 0, 5)) ?>–<?= Security::e(substr($day['close_time'], 0, 5)) ?>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if ($activePlan['free_text_2']): ?><p><?= nl2br(Security::e($activePlan['free_text_2'])) ?></p><?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($plan): ?>
            <form method="post" id="hours-form">
                <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">
                <input type="hidden" name="action" value="save_plan">
                <input type="hidden" name="mode" value="<?= Security::e($mode) ?>">
                <input type="hidden" name="plan_id" value="<?= Security::e((string) ($plan['id'] ?? '')) ?>">

                <p class="form-mode-indicator">
                    <?php if ($mode === 'default'): ?>
                        Redigerar: Standardöppettider
                    <?php elseif ($mode === 'long' && $plan['id'] === null): ?>
                        Ny periodplan
                    <?php elseif ($mode === 'long'): ?>
                        Redigerar: <?= Security::e($plan['header_text'] ?: 'Periodplan') ?>
                    <?php elseif ($mode === 'week' && $plan['id'] === null): ?>
                        Ny veckoplan
                    <?php elseif ($mode === 'week'): ?>
                        Redigerar: Vecka <?= (int) $plan['week_number'] ?>, <?= (int) $plan['year'] ?>
                    <?php endif; ?>
                </p>

                <?php if ($mode === 'week'): ?>
                    <label>Vecka
                        <input type="number" name="week_number" min="1" max="53" value="<?= (int) ($plan['week_number'] ?? date('W')) ?>">
                    </label>
                    <label>År
                        <input type="number" name="week_year" min="2024" value="<?= (int) ($plan['year'] ?? date('Y')) ?>">
                    </label>
                <?php endif; ?>

                <label>Namn / Rubrik
                    <input type="text" name="header_text" value="<?= Security::e($plan['header_text'] ?? '') ?>">
                </label>
                <br>
                <label>Fritext 1
                    <textarea name="free_text_1"><?= Security::e($plan['free_text_1'] ?? '') ?></textarea>
                </label>

                <table class="hours-days-table">
                    <thead><tr><th>Dag</th><th>Öppet?</th><th>Öppnar</th><th>Stänger</th></tr></thead>
                    <tbody>
                    <?php foreach ($plan['days'] as $day): $d = $day['day_of_week']; $isOpen = !$day['closed'];
                        $openHour = $day['open_time'] ? substr($day['open_time'], 0, 2) : '09';
                        $openMinute = $day['open_time'] ? substr($day['open_time'], 3, 2) : '00';
                        $closeHour = $day['close_time'] ? substr($day['close_time'], 0, 2) : '17';
                        $closeMinute = $day['close_time'] ? substr($day['close_time'], 3, 2) : '00';
                    ?>
                        <tr>
                            <td><?= $dayNames[$d] ?></td>
                            <td><input type="checkbox" name="open_<?= $d ?>" <?= $isOpen ? 'checked' : '' ?>></td>
                            <td>
                                <select name="open_hour_<?= $d ?>">
                                    <?php for ($h = 0; $h < 24; $h++): $hh = sprintf('%02d', $h); ?>
                                        <option value="<?= $hh ?>" <?= $openHour === $hh ? 'selected' : '' ?>><?= $hh ?></option>
                                    <?php endfor; ?>
                                </select>
                                :
                                <select name="open_minute_<?= $d ?>">
                                    <?php foreach (['00','15','30','45'] as $mm): ?>
                                        <option value="<?= $mm ?>" <?= $openMinute === $mm ? 'selected' : '' ?>><?= $mm ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select name="close_hour_<?= $d ?>">
                                    <?php for ($h = 0; $h < 24; $h++): $hh = sprintf('%02d', $h); ?>
                                        <option value="<?= $hh ?>" <?= $closeHour === $hh ? 'selected' : '' ?>><?= $hh ?></option>
                                    <?php endfor; ?>
                                </select>
                                :
                                <select name="close_minute_<?= $d ?>">
                                    <?php foreach (['00','15','30','45'] as $mm): ?>
                                        <option value="<?= $mm ?>" <?= $closeMinute === $mm ? 'selected' : '' ?>><?= $mm ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="hint"><em>Lämna alla dagar omarkerade för en plan utan fasta tider (t.ex. "ring oss") — fritext 2 nedan kan förklara det på den publika sidan.</em></p>

                <label>Fritext 2
                    <textarea name="free_text_2"><?= Security::e($plan['free_text_2'] ?? '') ?></textarea>
                </label>

                <button type="submit">Spara</button>
            </form>
        <?php endif; ?>

        <section class="long-term-list">
            <h2>Längre periodplaner</h2>
            <?php if (empty($longTermOptions)): ?>
                <p><em>Inga skapade ännu.</em></p>
            <?php else: ?>
                <ul>
                <?php foreach ($longTermOptions as $opt): ?>
                    <li>
                        <strong><?= Security::e($opt['header_text'] ?: '(namnlös)') ?></strong>

                        <?php if ($opt['is_active']): ?>
                            <span class="badge-active">Aktiv</span>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">
                                <input type="hidden" name="action" value="deactivate_long">
                                <button type="submit">Inaktivera</button>
                            </form>
                        <?php else: ?>
                            <span class="badge-inactive">Inaktiv</span>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">
                                <input type="hidden" name="action" value="activate_long">
                                <input type="hidden" name="id" value="<?= (int) $opt['id'] ?>">
                                <button type="submit">Aktivera</button>
                            </form>
                        <?php endif; ?>

                        <a href="/admin/hours.php?mode=long&id=<?= (int) $opt['id'] ?>">Redigera</a>

                        <form method="post" style="display:inline" onsubmit="return confirm('Ta bort denna periodplan?');">
                            <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">
                            <input type="hidden" name="action" value="delete_long">
                            <input type="hidden" name="id" value="<?= (int) $opt['id'] ?>">
                            <button type="submit">Ta bort</button>
                        </form>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>

    <div class="col-right">
        <p class="current-week-indicator">Innevarande vecka: <?= (int) date('W') ?>, <?= (int) date('Y') ?></p>
        <section class="week-specific-list">
            <h2>Veckospecifika planer</h2>
            <?php if (empty($weekSpecificPlans)): ?>
                <p><em>Inga kommande veckoplaner.</em></p>
            <?php else: ?>
                <ul>
                <?php foreach ($weekSpecificPlans as $wp): ?>
                    <li>
                        <strong>Vecka <?= (int) $wp['week_number'] ?>, <?= (int) $wp['year'] ?></strong>
                        <?php if ($wp['header_text']): ?> – <?= Security::e($wp['header_text']) ?><?php endif; ?>

                        <a href="/admin/hours.php?mode=week&id=<?= (int) $wp['id'] ?>">Redigera</a>

                        <form method="post" style="display:inline" onsubmit="return confirm('Ta bort denna veckoplan?');">
                            <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">
                            <input type="hidden" name="action" value="delete_week">
                            <input type="hidden" name="id" value="<?= (int) $wp['id'] ?>">
                            <button type="submit">Ta bort</button>
                        </form>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>
</div>