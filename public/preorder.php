<?php
require_once __DIR__ . '/../app/Core/init.php';
require_once __DIR__ . '/../app/Models/PreOrder.php';
require_once __DIR__ . '/../app/Core/Security.php';

$error = null;
$successOrderNumber = null;

// Re-populated on validation failure so the customer doesn't have to retype everything.
$formValues = [
    'product_id' => '',
    'quantity' => '',
    'customer_email' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ogiltig begäran, försök igen.';
    } else {
        $formValues['product_id'] = trim($_POST['product_id'] ?? '');
        $formValues['quantity'] = trim($_POST['quantity'] ?? '');
        $formValues['customer_email'] = trim($_POST['customer_email'] ?? '');

        $productId = (int) $formValues['product_id'];
        $quantity = (int) $formValues['quantity'];
        $email = $formValues['customer_email'];

        $validationErrors = [];

        if (!PreOrder::productExists($productId)) {
            $validationErrors[] = 'Välj en giltig produkt.';
        }

        if ($quantity < 1 || $quantity > 9999) {
            $validationErrors[] = 'Ange ett giltigt antal (1-9999).';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $validationErrors[] = 'Ange en giltig e-postadress.';
        }

        if ($validationErrors) {
            $error = implode(' ', $validationErrors);
        } else {
            $order = PreOrder::insertOrder($productId, $quantity, $email);
            $successOrderNumber = $order['order_number'];

            // Notify owner by email. Failure here must not break the customer's
            // experience — the order is already safely stored in the DB regardless.
            try {
                self_notifyOwner($order['order_number'], $productId, $quantity, $email);
            } catch (Throwable $e) {
                error_log('Pre-order owner notification failed: ' . $e->getMessage());
            }

            // Reset form for a clean success state.
            $formValues = ['product_id' => '', 'quantity' => '', 'customer_email' => ''];
        }
    }
}

/**
 * Sends the owner a plain mail() notification about a new pre-order.
 * Uses one.com's documented approach: From must be a real mailbox on the
 * domain. No raw user input goes into headers — only into the body — to
 * avoid header injection via the email field.
 */
function self_notifyOwner(string $orderNumber, int $productId, int $quantity, string $customerEmail): void
{
    $products = PreOrder::getActiveProducts();
    $productName = 'Okänd produkt';
    foreach ($products as $p) {
        if ((int) $p['id'] === $productId) {
            $productName = $p['name'];
            break;
        }
    }

    $mailConfig = require __DIR__ . '/../config/config.php';
    $to = $mailConfig['mail']['owner_notify_email'];
    $from = $mailConfig['mail']['site_from_email'];

    $subject = 'Ny förbeställning: ' . $orderNumber;

    $body = "Ny förbeställning har kommit in.\n\n"
        . "Ordernummer: {$orderNumber}\n"
        . "Produkt: {$productName}\n"
        . "Antal: {$quantity}\n"
        . "Kundens e-post: {$customerEmail}\n";

    $headers = "From: {$from}\r\n"
        . "Reply-To: {$from}\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n";

    mail($to, $subject, $body, $headers);
}

$products = PreOrder::getActiveProducts();
$pageTitle = 'Förbeställning – Bibutiken';

require __DIR__ . '/../app/Views/public/_header.php';
require __DIR__ . '/../app/Views/public/preorder.php';
require __DIR__ . '/../app/Views/public/_footer.php';