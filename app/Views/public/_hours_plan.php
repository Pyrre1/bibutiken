<?php
/**
 * Renders one resolved hours plan ($planForBlock) for the public page.
 * Expects $planForBlock (array from HoursResolver::resolveForWeek) and
 * $dayNames (array, day_of_week => Swedish day name) to be set by the caller.
 */
?>
<?php if (!empty($planForBlock['header_text'])): ?>
    <p class="hours-header-text"><?= htmlspecialchars($planForBlock['header_text']) ?></p>
<?php endif; ?>

<?php if (!empty($planForBlock['free_text_1'])): ?>
    <p class="hours-free-text"><?= nl2br(htmlspecialchars($planForBlock['free_text_1'])) ?></p>
<?php endif; ?>

<ul class="hours-day-list">
    <?php foreach ($planForBlock['days'] as $day): ?>
        <li>
            <span class="hours-day-name"><?= htmlspecialchars($dayNames[$day['day_of_week']]) ?></span>
            <span class="hours-day-value">
                <?php if ($day['closed']): ?>
                    Stängt
                <?php else: ?>
                    <?= htmlspecialchars(substr($day['open_time'], 0, 5)) ?>–<?= htmlspecialchars(substr($day['close_time'], 0, 5)) ?>
                <?php endif; ?>
            </span>
        </li>
    <?php endforeach; ?>
</ul>

<?php if (!empty($planForBlock['free_text_2'])): ?>
    <p class="hours-free-text hours-free-text-2"><?= nl2br(htmlspecialchars($planForBlock['free_text_2'])) ?></p>
<?php endif; ?>