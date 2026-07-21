<h1>Lokalproducerat</h1>

<!-- ── Honung ─────────────────────────────────────────── -->
<section class="lp-section">


    <h2>Egen honung</h2>

    <div class="lp-activity-note">
        <p>Vi brukar kunna ha burkar för provsmakning inför köp. Vill du uppleva smaken i
        kombination med ost och kex och dyka ner i smakkombinationernas värld så finns
        <strong>honungssmakning</strong> som aktivitet — att köpa till dig själv eller ge
        bort som gåva till någon annan honungsintresserad. Plats för 4–8 deltagare.</p>
    </div>

    <?php if (!empty($grouped['Honung'])): ?>
    <table class="lp-table">
        <thead>
            <tr>
                <th>Storlek</th>
                <th>Produkt</th>
                <th>Beskrivning</th>
                <th>Pris</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($grouped['Honung'] as $p): ?>
            <tr>
                <td class="lp-size"><?= Security::e($p['size']) ?></td>
                <td class="lp-name"><?= Security::e($p['name']) ?></td>
                <td class="lp-desc"><?= Security::e($p['description'] ?? '') ?></td>
                <td class="lp-price" data-label="Pris">
                    <?= number_format($p['price_ore'] / 100, 2, ',', '\u{202F}') ?>&nbsp;kr
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p class="lp-empty">Just nu finns ingen honung tillgänglig.</p>
    <?php endif; ?>
</section>

<!-- ── Relaterade produkter ───────────────────────────── -->
<section class="lp-section">
  
    <h2>Relaterade produkter</h2>

    <div class="lp-activity-note">
        <p><strong>Salva och läppcerat</strong> går att göra själv i bihuset som
        bokningsbar aktivitet. 3–5 deltagare.</p>
    </div>

    <?php if (!empty($grouped['Relaterade produkter'])): ?>
    <table class="lp-table">
        <thead>
            <tr>
                <th>Storlek</th>
                <th>Produkt</th>
                <th>Beskrivning</th>
                <th>Pris</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($grouped['Relaterade produkter'] as $p): ?>
            <tr>
                <td class="lp-size"><?= Security::e($p['size']) ?></td>
                <td class="lp-name"><?= Security::e($p['name']) ?></td>
                <td class="lp-desc"><?= Security::e($p['description'] ?? '') ?></td>
                <td class="lp-price">
                    <?= number_format($p['price_ore'] / 100, 0, ',', '\u{202F}') ?>&nbsp;kr
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p class="lp-empty">Just nu finns inga relaterade produkter tillgängliga.</p>
    <?php endif; ?>
</section>

<p class="lp-disclaimer">Jag försöker hålla listan uppdaterad och tanken är att finns
produkten på sidan så ska den finnas i butiken, med reservation för slutförsäljning samma
dag där jag kanske inte hinner uppdatera.</p>