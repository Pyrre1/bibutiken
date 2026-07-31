<!-- BANNER IMAGE — full width, decorative -->
<div class="home-hero">
    <img src="/assets/images/Bibutik-med-bi.jpg" alt="Bibutiken med bi i förgrunden" class="home-hero__image">
</div>

<!-- ÖPPETTIDER -->
<section class="opening-hours">
    <h2>Öppettider</h2>

    <div class="hours-week-block">
        <p class="hours-period-label">Aktuella öppettider för denna vecka (vecka <?= $thisWeek ?>)</p>
        <?php $planForBlock = $thisWeekPlan; ?>
        <?php $weekDates = $thisWeekDates; ?>
        <?php include __DIR__ . '/_hours_plan.php'; ?>
    </div>

    <div class="hours-week-block">
        <p class="hours-period-label">Nästa vecka (vecka <?= $nextWeek ?>) gäller dessa tider:</p>
        <?php $planForBlock = $nextWeekPlan; ?>
        <?php $weekDates = $nextWeekDates; ?>
        <?php include __DIR__ . '/_hours_plan.php'; ?>
    </div>
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
    <p>Vi som bor på Rosenborg och driver bibutiken heter Hanne och Andreas. Vi började med bin 2008. </p>
    <p>Strängnäs Biredskap öppnade 2012. I butiken säljer vi biredskap från Biredskapsfabriken i Töreboda samt böcker 
        och andra biprylar från olika leverantörer. Butiken har inga fasta öppettider utan vi har öppet när vi är hemma.</p>
</section>

<!-- VAD KAN JAG KÖPA? -->
<section class="info-section" id="vad-kan-jag-kopa">
    <h2>Vad kan jag köpa här?</h2>
    <ul class="content-list">
        <h3>Är du inte biodlare, men är intresserad av biprodukter?</h3>
        <li> För köp av honung (pressad, slungad eller kankse samlad från framträdande blommor), se <a href="/biprodukter">tillgängliga produkter</a>.</li>
        <li> Förutom honung pruducerar bin även pollen, vax och propolis som vi också tar hand om och säljer eller förädlar till schampo, läppcerat och salva. 
            Det finns även vax, bibröd och böcker m.m. se <a href="/biprodukter#biprodukter">relaterade produkter</a>.</li>
        <h3 class="biodlare-intresse">Är du biodlare och vill köpa biredskap?</h3>
        <li>Under säsong går det att köpa invintringsfoder. För att säkerställa att vi beställer tillräckligt mycket foder går det att fylla i en förbeställning på <a href="/vinterfoder">vinterfoder</a>. När fodret kommit till butiken får du mejl om att hämta och fakturan kommer efter att produkterna hämtats.</li>
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
        <h3 id="faq-priser">Gäller samma priser som i Törebodas katalog?</h3>
        <p>Nej, priset ligger något högre för att täcka transportkostnader.</p>
    </div>

    <div class="faq-item">
        <h3 id="faq-vax">Kan man lämna in vax i butiken?</h3>
        <p>Ja, utsmält vax som är grovrensat och där pollen och skräp är bortskuret kan man lämna året om. Du kan välja mellan
            att byta till motsvarande mängd mellanväggar (du betalar för valsningen) eller sälja vaxet (till aktuellt dagspris). <br>
            Täckvax och vax i ram kan man lämna in från <strong>första oktober till och med första mars (1/10-1/3)</strong>. Då betalar du för rengöring av ramar, rensning och valsning 
            av vax och en mindre peng för transporten. <br>
            Du kommer inte få tillbaka just ditt vax, men om du märker vaxet med ”Särbehandlas” samt
            lämnar ett intyg så hanteras ditt vax tillsammans med annat vax som också har intyg.
        </p>
    </div>

    <div class="faq-item">
        <h3 id="faq-drottningar">Kan man köpa drottningar eller bisamhällen i butiken?</h3>
        <p>Nej inte drottningar, men ibland finns avläggare att köpa. </p>
    </div>
</section>
