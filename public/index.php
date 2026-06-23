<?php
require_once __DIR__ . '/../app/Core/init.php';
require_once __DIR__ . '/../app/Models/HoursPlan.php';
require_once __DIR__ . '/../app/Core/HoursResolver.php';

$dayNames = [1=>'Måndag',2=>'Tisdag',3=>'Onsdag',4=>'Torsdag',5=>'Fredag',6=>'Lördag',7=>'Söndag'];

/**
 * Returns [year, week] for "today" and "today + 7 days", using ISO-8601
 * week/year semantics throughout (consistent with HoursPlan::pruneExpiredWeekSpecific
 * and with how week-specific plans are looked up). Using DateTime rather than
 * manual +1 arithmetic so week 52/53 -> week 1 year rollovers are handled
 * automatically instead of needing special-casing here.
 */
function resolveWeekYear(DateTime $date): array
{
    return [(int) $date->format('o'), (int) $date->format('W')];
}

// "isoWeekDateRange" not in use right now as week labels are kept to "vecka N". Might be useful if later date spec is needed in future.
/**
 * Monday-Sunday calendar date range for a given ISO week/year, formatted as "dd-dd"
 * when within the same month, or "dd/mm-dd/mm" if the week spans two months.
 */
function isoWeekDateRange(int $year, int $week): string
{
    $monday = new DateTime();
    $monday->setISODate($year, $week, 1);
    $sunday = (clone $monday)->modify('+6 days');

    if ($monday->format('n') === $sunday->format('n')) {
        return $monday->format('d') . '-' . $sunday->format('d');
    }
    return $monday->format('d/m') . '-' . $sunday->format('d/m');
}

/**
 * Compares two resolved plans on customer-visible content only (header, both
 * free-text fields, and the day-by-day open/closed/time grid). Deliberately
 * ignores id/week_number/year/timestamps, since two different week-specific
 * plans (or a week-specific plan vs the default) with identical hours should
 * be treated as "the same" for display purposes.
 */
function plansHaveSameContent(array $a, array $b): bool
{
    if ($a['free_text_1'] !== $b['free_text_1']
        || $a['free_text_2'] !== $b['free_text_2']) {
        return false;
    }

    foreach ($a['days'] as $i => $dayA) {
        $dayB = $b['days'][$i];
        if ((int) $dayA['closed'] !== (int) $dayB['closed']
            || $dayA['open_time'] !== $dayB['open_time']
            || $dayA['close_time'] !== $dayB['close_time']) {
            return false;
        }
    }

    return true;
}

$today = new DateTime();
[$thisYear, $thisWeek] = resolveWeekYear($today);
[$nextYear, $nextWeek] = resolveWeekYear((clone $today)->modify('+7 days'));

$thisWeekPlan = HoursResolver::resolveForWeek($thisWeek, $thisYear);
$nextWeekPlan = HoursResolver::resolveForWeek($nextWeek, $nextYear);

$sameContent = plansHaveSameContent($thisWeekPlan, $nextWeekPlan);

$pageTitle = 'Bibutiken';
require __DIR__ . '/../app/Views/public/_header.php';
?>
    <!-- TODO: add address, GPS/map links, remaining homepage content -->

    <section class="opening-hours">
        <?php if ($sameContent): ?>
            <h2>Öppettider</h2>
            <p class="hours-period-label">Aktuella öppettider nu (gäller vecka <?= $thisWeek ?> och vecka <?= $nextWeek ?>)</p>
            <?php $planForBlock = $thisWeekPlan; ?>
            <?php include __DIR__ . '/../app/Views/public/_hours_plan.php'; ?>
        <?php else: ?>
            <h2>Öppettider</h2>

            <div class="hours-week-block">
                <p class="hours-period-label">Aktuella öppettider för denna vecka (vecka <?= $thisWeek ?>)</p>
                <?php $planForBlock = $thisWeekPlan; ?>
                <?php include __DIR__ . '/../app/Views/public/_hours_plan.php'; ?>
            </div>

            <div class="hours-week-block">
                <p class="hours-period-label">Nästa vecka (vecka <?= $nextWeek ?>) gäller dessa tider:</p>
                <?php $planForBlock = $nextWeekPlan; ?>
                <?php include __DIR__ . '/../app/Views/public/_hours_plan.php'; ?>
            </div>
        <?php endif; ?>
    </section>
<?php require __DIR__ . '/../app/Views/public/_footer.php'; ?>