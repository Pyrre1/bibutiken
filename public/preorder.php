<?php
require_once __DIR__ . '/../app/Core/init.php';
require_once __DIR__ . '/../app/Models/PreOrder.php';
require_once __DIR__ . '/../app/Core/Security.php';

$error = null;
$successOrderNumber = null;

// Re-populated on validation failure so the customer doesn't lose their cart.
$formValues = [
    'customer_name' => '',
    'customer_email' => '',
];
$cartItems = []; // [['product_id' => int, 'quantity' => int], ...] — as submitted by the customer

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ogiltig begäran, försök igen.';
    } else {
        $formValues['customer_name'] = trim($_POST['customer_name'] ?? '');
        $formValues['customer_email'] = trim($_POST['customer_email'] ?? '');

        // Cart arrives as parallel arrays: product_id[] and quantity[].
        $submittedProductIds = $_POST['product_id'] ?? [];
        $submittedQuantities = $_POST['quantity'] ?? [];

        $validationErrors = [];

        if ($formValues['customer_name'] === '') {
            $validationErrors[] = 'Ange ditt namn.';
        }

        if (!filter_var($formValues['customer_email'], FILTER_VALIDATE_EMAIL)) {
            $validationErrors[] = 'Ange en giltig e-postadress.';
        }

        $activeProducts = PreOrder::getActiveProductsById();
        $orderItems = []; // built fresh from validated data, with server-trusted prices

        if (empty($submittedProductIds)) {
            $validationErrors[] = 'Lägg till minst en produkt i beställningen.';
        } else {
            foreach ($submittedProductIds as $index => $rawProductId) {
                $productId = (int) $rawProductId;
                $quantity = (int) ($submittedQuantities[$index] ?? 0);

                // Keep what the customer typed for re-display, even if invalid.
                $cartItems[] = ['product_id' => $productId, 'quantity' => $submittedQuantities[$index] ?? ''];

                if (!isset($activeProducts[$productId])) {
                    $validationErrors[] = 'En vald produkt finns inte längre. Kontrollera din beställning.';
                    continue;
                }

                if ($quantity < 1 || $quantity > 9999) {
                    $validationErrors[] = 'Ange ett giltigt antal (1-9999) för ' . $activeProducts[$productId]['name'] . '.';
                    continue;
                }

                // Price comes from the server's own product data, never from the submitted form,
                // so a tampered/stale client-side price can't affect what gets stored.
                $orderItems[] = [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'unit_price_ore' => (int) $activeProducts[$productId]['price_ore'],
                    'needs_manual_work' => (int) $activeProducts[$productId]['needs_manual_work'],
                ];
            }
        }

        if ($validationErrors) {
            $error = implode(' ', array_unique($validationErrors));
        } else {
            $order = PreOrder::insertOrder($formValues['customer_name'], $formValues['customer_email'], $orderItems);
            $successOrderNumber = $order['order_number'];

            try {
                self_notifyOwner($order['order_number'], $formValues['customer_name'], $formValues['customer_email'], $orderItems, $activeProducts);
            } catch (Throwable $e) {
                error_log('Pre-order owner notification failed: ' . $e->getMessage());
            }

            $formValues = ['customer_name' => '', 'customer_email' => ''];
            $cartItems = [];
        }
    }
}

/**
 * Sends the owner a plain mail() notification about a new multi-item pre-order.
 * Uses one.com's documented approach: From must be a real mailbox on the domain.
 * No raw user input goes into headers — only into the body — to avoid header injection.
 */
function self_notifyOwner(string $orderNumber, string $customerName, string $customerEmail, array $orderItems, array $activeProducts): void
{
    $config = require __DIR__ . '/../config/config.php';
    $to = $config['mail']['owner_notify_email'];
    $from = $config['mail']['site_from_email'];

    $subject = 'Ny förbeställning: ' . $orderNumber;

    $lines = [];
    $totalOre = 0;
    foreach ($orderItems as $item) {
        $name = $activeProducts[$item['product_id']]['name'] ?? 'Okänd produkt';
        $lineTotal = $item['quantity'] * $item['unit_price_ore'];
        $totalOre += $lineTotal;
        $lines[] = sprintf('- %s x%d (%.2f kr/st)', $name, $item['quantity'], $item['unit_price_ore'] / 100);
    }

    $body = "Ny förbeställning har kommit in.\n\n"
        . "Ordernummer: {$orderNumber}\n"
        . "Namn: {$customerName}\n"
        . "E-post: {$customerEmail}\n\n"
        . "Produkter:\n" . implode("\n", $lines) . "\n\n"
        . sprintf("Totalt: %.2f kr\n", $totalOre / 100);

    $headers = "From: {$from}\r\n"
        . "Reply-To: {$from}\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n";

    @mail($to, $subject, $body, $headers);
}

$products = PreOrder::getActiveProducts();
$pageTitle = 'Förbeställning – Bibutiken';

require __DIR__ . '/../app/Views/public/_header.php';
require __DIR__ . '/../app/Views/public/preorder.php';
require __DIR__ . '/../app/Views/public/_footer.php';