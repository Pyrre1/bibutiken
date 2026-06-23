<h1>Förbeställning</h1>

<?php if ($successOrderNumber): ?>
    <div class="form-success">
        <p>Tack för din förbeställning! Ditt ordernummer är: <strong><?= Security::e($successOrderNumber) ?></strong></p>
        <p>Spara ordernumret. Om du inte får ett bekräftelsemejl (glöm inte att kolla skräpposten) inom kort har beställningen <strong>inte</strong> gått igenom — vänligen kontakta oss direkt via e-post eller sms.</p>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="form-error">
        <p><?= Security::e($error) ?></p>
    </div>
<?php endif; ?>

<form method="post" action="/preorder.php" class="preorder-form">
    <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">

    <label for="product_id">Produkt</label>
    <select name="product_id" id="product_id" required>
        <option value="">Välj produkt</option>
        <?php foreach ($products as $product): ?>
            <option value="<?= (int) $product['id'] ?>" <?= ((string) $product['id'] === $formValues['product_id']) ? 'selected' : '' ?>>
                <?= Security::e($product['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="quantity">Antal</label>
    <input type="number" name="quantity" id="quantity" min="1" max="9999" step="1" required
          value="<?= Security::e($formValues['quantity']) ?>">

    <label for="customer_email">E-post</label>
    <input type="email" name="customer_email" id="customer_email" required
          value="<?= Security::e($formValues['customer_email']) ?>">

    <button type="submit">Skicka förbeställning</button>
</form>