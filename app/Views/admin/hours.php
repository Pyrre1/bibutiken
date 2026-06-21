<div class="hours-admin-layout">
    <div class="col-left">
        <div class="actions">
            <a href="/admin/hours.php?mode=default" class="button">Edit Default</a>
        </div>

        <?php if ($message): ?><p class="success"><?= Security::e($message) ?></p><?php endif; ?>
        <?php if ($error): ?><p class="error"><?= Security::e($error) ?></p><?php endif; ?>

        <?php if ($plan): ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">
                <input type="hidden" name="mode" value="<?= Security::e($mode) ?>">
                <input type="hidden" name="plan_id" value="<?= (int) $plan['id'] ?>">

                <p class="form-mode-indicator">
                    Editing: <?= $mode === 'default' ? 'Default Hours' : Security::e($plan['header_text'] ?? '') ?>
                </p>

                <label>Name / Header
                    <input type="text" name="header_text" value="<?= Security::e($plan['header_text'] ?? '') ?>">
                </label>
                <br>
                <label>Free text 1
                    <textarea name="free_text_1"><?= Security::e($plan['free_text_1'] ?? '') ?></textarea>
                </label>

                <?php if (!empty($plan['days'])): ?>
                    <table class="hours-days-table">
                        <thead><tr><th>Day</th><th>Open?</th><th>Open time</th><th>Close time</th></tr></thead>
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
                <?php endif; ?>

                <label>Free text 2
                    <textarea name="free_text_2"><?= Security::e($plan['free_text_2'] ?? '') ?></textarea>
                </label>

                <button type="submit">Save</button>
            </form>
        <?php endif; ?>

        <section class="long-term-list">
            <h2>Long-term plans</h2>
            <p><em>Coming in the next step.</em></p>
        </section>
    </div>

    <div class="col-right">
        <p class="current-week-indicator">Current week: <?= (int) date('W') ?>, <?= (int) date('Y') ?></p>
        <section class="week-specific-list">
            <h2>Week-specific plans</h2>
            <p><em>Coming in the next step.</em></p>
        </section>
    </div>
</div>