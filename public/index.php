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
$activePage = 'home';
$extraStyles = ['/assets/css/home.css'];
require __DIR__ . '/../app/Views/public/_header.php';
?>
    <!-- BANNER IMAGE — full width, decorative -->
    <div class="site-banner">
        <img src="/assets/images/Bibutik-med-bi.jpg" alt="Bibutiken med bi i förgrunden" class="site-banner__image">
    </div>

    <!-- ÖPPETTIDER -->
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

    <!-- HITTA HIT -->
    <section class="info-section" id="hitta-hit">
        <h2>Hitta hit</h2>
        <div class="hitta-hit-row">
            <p>Strängnäs Biredskap<br>645 92 Strängnäs</p>
            <a class="btn hitta-hit-btn" href="https://maps.google.com/?q=Strängnäs+Biredskap,+645+92+Strängnäs" target="_blank" rel="noopener">
                Öppna i Google Maps
            </a>
        </div>
    </section>

    <!-- VANLIGA FRÅGOR -->
    <section class="info-section" id="faq-menu">
        <h2>Vanliga frågor</h2>

        <div class="faq-item">
            <a href="#faq-priser">
                <p class="faq-question">Gäller samma priser som i Törebodas katalog?</p>
            </a>
            <a href="#faq-vax">
                <p class="faq-question">Kan man lämna in vax i butiken?</p>
            </a>
            <a href="#faq-drottningar">
                <p class="faq-question">Kan man köpa drottningar eller bisamhällen i butiken?</p>
            </a>
        </div>

    <!-- OM BUTIKEN -->
    <section class="info-section" id="om-butiken">
        <h2>Om butiken</h2>
        <p>Vi som bor på Rosenborg och driver bibutiken heter Hanne och Andreas. Hanne har varit biodlare sedan 2008. </p>
        <p>Strängnäs Biredskap öppnade 2012. I butiken säljer vi biredskap från Biredskapsfabriken i Töreboda samt böcker 
            och andra biprylar från olika leverantörer. Butiken har inga fasta öppettider utan vi har öppet när vi är hemma.</p>
    </section>

    <!-- VAD KAN JAG KÖPA? -->
    <section class="info-section" id="vad-kan-jag-kopa">
        <h2>Vad kan jag köpa här?</h2>
        <ul class="content-list">
            <h3>Är du inte biodlare, utan är intresserad av bigårdens produkter?</h3>
            <li> För köp av honung, pollen eller andra produkter från biodlingen, se <a href="/lokalproducerat.php">tillgängliga produkter</a>.</li>
            <h3>Är du biodlare och vill köpa biredskap?</h3>
            <li>Vi har nästan alltid honungsburkar i de vanligaste storlekarna hemma. Vax, kupor och ramar till LN och HLS är också sådant vi alltid försöker ha hemma. Om du vill ha isolerade träkupor eller ramar/vax i andra storlekar kan vi ta hem efter önskemål. <br>Du kan se sortimentet på Biredskapsfabrikens hemsida <a href="https://www.biredskapsfabriken.se/" target="_blank" rel="noopener noreferrer">https://www.biredskapsfabriken.se/</a></li>
            <li>I bibutiken kan du också hyra en tvåramars slunga (som tar 4 HLS ramar) samt en ångvaxsmältare. Här finns en refraktometer om du vill ta med dig honung för att kolla vattnehalten i honungen.</li>
        </ul>
    </section>

    <!-- HUR HANDLAR JAG? -->
    <section class="info-section" id="hur-handlar-jag">
        <h2>Hur handlar jag här?</h2>
        <p>Börja med att titta på våra öppettider på hemsidan. Om ingen tid passar kan du maila oss och meddela vad du vill ha och när du vill hämta. Eftersom vi inte alltid är hemma är det säkrast att maila <strong>senast</strong> dagen innan du vill komma. Vi bekräftar via mail om/när du kan hämta varorna. När du har handlat får du sedan en faktura på mailen. Betalningstid 20 dagar och du kan välja mellan att swisha eller betala via bankgiro. </p>
    </section>

    <!-- VANLIGA FRÅGOR -->
    <section class="info-section" id="faq">
        <h2>Vanliga frågor</h2>

        <div class="faq-item">
            <h3>Gäller samma priser som i Törebodas katalog?</h3>
            <p id="faq-priser">Nej, priset ligger något högre för att täcka transportkostnader.</p>
        </div>

        <div class="faq-item">
            <h3>Kan man lämna in vax i butiken?</h3>
            <p id="faq-vax">Ja, utsmält vax som är grovrensat och där pollen och skräp är bortskuret kan man lämna året om. Du kan välja mellan
                att byta till motsvarande mängd mellanväggar (du betalar för valsningen) eller sälja vaxet (till aktuellt dagspris). 
                Täckvax och vax i ram kan man lämna in 1 oktober-1 mars, <strong>utanför dessa tider  får du inte flytta ramar som inte är besiktigade av bitillsyningsman.</strong>. Då betalar du för rengöring av ramar, rensning och valsning 
                av vax och en mindre peng för transporten. 
            </p>
        </div>

        <div class="faq-item">
            <h3>Kan man köpa drottningar eller bisamhällen i butiken?</h3>
            <p id="faq-drottningar">Nej inte drottningar, men ibland finns avläggare att köpa. </p>
        </div>
    </section>
<?php require __DIR__ . '/../app/Views/public/_footer.php'; ?>