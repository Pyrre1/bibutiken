<h1 class="form-title">Förbeställning av vinterfoder</h1>

<?php if ($successOrderNumber): ?>
    <div class="form-success">
        <p>Tack för din förbeställning! Ditt ordernummer är: <strong><?= Security::e($successOrderNumber) ?></strong></p>
        <p>Om du inte får ett bekräftelsemejl inom kort har beställningen <strong>inte</strong> gått igenom — vänligen kontakta oss direkt via e-post eller sms.</p>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="form-error">
        <p><?= Security::e($error) ?></p>
    </div>
<?php endif; ?>

<div class="info-banner" id="preorder-info-banner">
    <button class="info-banner__close" id="close-info-banner" aria-label="Stäng meddelande">✕</button>
    <p><strong>Observera:</strong> priser på vinterfoder är uppskattade från förra årets leverans och är bara ungefärliga tills dess att årets priser blivit beräknade exakt. Du kommer bli kontaktad när dina varor kommit och då få årets aktuella priser. Det brukar inte vara några större skillnader, men värt att notera. Betalning sker efter leverans och detta formulär är avsett som en avsiktsförklaring för att kunna ta hem rätt mängd vinterfoder.</p>
    <p>När du skickar in din beställning av vinterfoder godkänner du att Strängnäs Biredskap AB använder e-postadressen för att kontakta dig när din order går att hämta. Självklart följer vi GDPR och vill du få din e-post raderad, kontakta butiken så raderar vi den.</p>
</div>

<form method="post" action="/preorder.php" class="preorder-form" id="preorder-form">
    <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">
    <input type="hidden" name="form_loaded_at" id="form_loaded_at" value="">
    <div aria-hidden="true" style="position:absolute;left:-9999px;top:-9999px;">
        <label for="website">Lämna detta fält tomt</label>
        <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
    </div>

    <label for="customer_name">Namn</label>
    <input type="text" name="customer_name" id="customer_name" required
        value="<?= Security::e($formValues['customer_name']) ?>">

    <label for="customer_email">E-postadress</label>
    <input type="email" name="customer_email" id="customer_email" required
        value="<?= Security::e($formValues['customer_email']) ?>">

    <fieldset class="cart-add-row cart-fieldset-gap">
        <legend>Produkt</legend>

        <div class="cart-add-field">
            <label for="staging_product_id">Produkt</label>
            <select id="staging_product_id">
                <option value="">Välj produkt</option>
                <?php foreach ($products as $product): ?>
                    <option
                        value="<?= (int) $product['id'] ?>"
                        data-name="<?= Security::e($product['name']) ?>"
                        data-price-ore="<?= (int) $product['price_ore'] ?>"
                    >
                        <?= Security::e($product['name']) ?> (<?= number_format($product['price_ore'] / 100, 2, ',', ' ') ?> kr)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="cart-add-field cart-add-field--qty">
            <label for="staging_quantity">Antal</label>
            <input type="number" id="staging_quantity" min="1" max="9999" step="1" value="1">
        </div>

        <div class="cart-add-actions">
            <button type="button" id="add-item-btn">Lägg till</button>
        </div>
        
    </fieldset>

    <h2>Tillagda produkter</h2>
    <table class="cart-table" id="cart-table">
        <thead>
            <tr>
                <th>Produkt</th>
                <th>Antal</th>
                <th>Styck</th>
                <th>Summa</th>
                <th></th>
                <th></th>
            </tr>
        </thead>
        <tbody id="cart-table-body">
            <!-- rows inserted by JS -->
        </tbody>
    </table>
    <p id="cart-empty-message">Inga produkter tillagda ännu.</p>

    <div id="cart-hidden-inputs">
        <!-- product_id[] / quantity[] hidden inputs mirrored here by JS for submission -->
    </div>

    <p class="cart-totals">Totalt antal produkter: <span id="cart-total-qty">0</span></p>
    <p class="cart-totals">Total summa (uppskattad): <span id="cart-total-sum">0,00 kr</span></p>

    <button type="submit" id="submit-order-btn" disabled>Skicka in beställning</button>
</form>

<div id="confirm-modal" class="modal-overlay" hidden>
    <div class="modal-box">
        <p id="modal-message"></p>
        <div class="modal-actions">
        <button type="button" id="modal-cancel">Avbryt</button>
            <button type="button" id="modal-confirm">Skicka beställning</button>
        </div>
    </div>
</div>