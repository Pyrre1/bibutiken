<?php
require_once __DIR__ . '/../app/Core/init.php';
require_once __DIR__ . '/../app/Core/Security.php';

$pageTitle = 'Bihuset';
$activePage = 'bihuset';
require __DIR__ . '/../app/Views/public/_header.php';
?>
    <h1>Bihuset</h1>
    <p>2023 byggdes Bihuset i Vansö. Det är bibutikens kurslokal där besökare är välkomna på honungsprovningar, föreläsningar om honung och biodling eller träffar där vi tillverkar salva, bivaxdukar eller bivaxljus. </p>
    <h2>Aktiviteter i Bihuset</h2>
    <ul>
        <li>Tillverkning av vaxljus.</li>
        <li>Tillverkning av salva.</li>
        <li>Mjödtillverkning</li>
        <li>Honungsprovning</li>
        <li>Prova på att slunga honung</li>
    </ul>
    <h2>EU-projektet</h2>
    <p>Bihuset byggdes som ett projekt inom LEADER för utveckling av företag på landsbygden. </p>

    <div id="leader-project">
        <img src="/assets/images/Leader.jpg" alt="Leader" style="max-width: 200px;">
        <p>Ansökan om projektstöd till Leader-projektet vilket Jordbruksverket har beviljat för byggnaden av Strängnäs Biredskap bihus. 
            Projektets syfte är att kunna underlätta utbildning om och information kring biodling och produkter tillverkade av biodlingen.
            Målet med projektet är att skapa en kursgård för att hålla kurser och öka kunskapen om bin.</p>
    </div>
    <div id="eu-fonden">
        <img src="/assets/images/EU-flagga.jpg" alt="EU Jordbruksfond för landsbygdsutveckling" style="max-width: 200px;">
        <p>och med den Europeiska jordbruksfonden</p>
    </div>
<?php
require __DIR__ . '/../app/Views/public/_footer.php';