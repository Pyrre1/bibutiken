<h1>Förbeställning</h1>

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

<form method="post" action="/preorder.php" class="preorder-form" id="preorder-form">
    <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">

    <label for="customer_name">Namn</label>
    <input type="text" name="customer_name" id="customer_name" required
           value="<?= Security::e($formValues['customer_name']) ?>">

    <label for="customer_email">E-postadress</label>
    <input type="email" name="customer_email" id="customer_email" required
           value="<?= Security::e($formValues['customer_email']) ?>">

    <fieldset class="cart-add-row">
        <legend>Produkt/Vara</legend>

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

        <label for="staging_quantity">Antal</label>
        <input type="number" id="staging_quantity" min="1" max="9999" step="1" value="1">

        <button type="button" id="add-item-btn">Lägg till</button>
    </fieldset>

    <h2>Tillagda produkter</h2>
    <table class="cart-table" id="cart-table">
        <thead>
            <tr>
                <th>Produkt</th>
                <th>Antal</th>
                <th>Styckpris</th>
                <th>Radsumma</th>
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
    <p class="cart-totals">Totalt summa: <span id="cart-total-sum">0,00 kr</span></p>

    <button type="submit" id="submit-order-btn" disabled>Skicka in beställning</button>
</form>