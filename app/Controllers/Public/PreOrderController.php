<?php

class PreOrderController
{
    public static function index(): void
    {
        require_once __DIR__ . '/../../Models/PreOrder.php';
        require_once __DIR__ . '/../../Models/Product.php';
        require_once __DIR__ . '/../../Models/Settings.php';
        require_once __DIR__ . '/../../Models/Customer.php';
        require_once __DIR__ . '/../../Core/Security.php';

        $pageTitle    = 'Förbeställning – Bibutiken';
        $activePage   = 'preorder';
        $extraStyles  = ['/assets/css/preorder.css'];
        $extraScripts = ['/assets/js/preorder.js'];

        if (Settings::get('preorder_enabled', '1') !== '1') {
            $reminderMessage = null;
            $reminderError   = null;

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reminder_submit'])) {
                Security::validateCsrf($_POST['csrf_token'] ?? '');
                $rName  = trim($_POST['reminder_name'] ?? '');
                $rEmail = strtolower(trim($_POST['reminder_email'] ?? ''));
                if (!$rName || !$rEmail || !filter_var($rEmail, FILTER_VALIDATE_EMAIL)) {
                    $reminderError = 'Fyll i både namn och en giltig e-postadress.';
                } else {
                    Customer::findOrCreateCustomer($rName, $rEmail, 'vinterfoder');
                    $reminderMessage = 'Du kommer få ett mejl när beställningen öppnar.';
                }
            }

            require __DIR__ . '/../../Views/public/_header.php';
            require __DIR__ . '/../../Views/public/preorder_closed.php';
            require __DIR__ . '/../../Views/public/_footer.php';
            return;
        }

        $error             = null;
        $successOrderNumber = null;
        $formValues        = ['customer_name' => '', 'customer_email' => ''];
        $cartItems         = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::validateCsrf($_POST['csrf_token'] ?? null)) {
                $error = 'Ogiltig begäran, försök igen.';
            } else {
                $formValues['customer_name']  = trim($_POST['customer_name'] ?? '');
                $formValues['customer_email'] = trim($_POST['customer_email'] ?? '');

                $submittedProductIds = $_POST['product_id'] ?? [];
                $submittedQuantities = $_POST['quantity'] ?? [];

                $validationErrors = [];

                if ($formValues['customer_name'] === '') {
                    $validationErrors[] = 'Ange ditt namn.';
                }

                if (!filter_var($formValues['customer_email'], FILTER_VALIDATE_EMAIL)) {
                    $validationErrors[] = 'Ange en giltig e-postadress.';
                }

                $activeProducts = Product::getActiveProductsById();
                $orderItems     = [];

                if (empty($submittedProductIds)) {
                    $validationErrors[] = 'Lägg till minst en produkt i beställningen.';
                } else {
                    foreach ($submittedProductIds as $index => $rawProductId) {
                        $productId = (int) $rawProductId;
                        $quantity  = (int) ($submittedQuantities[$index] ?? 0);

                        $cartItems[] = [
                            'product_id' => $productId,
                            'quantity'   => $submittedQuantities[$index] ?? '',
                        ];

                        if (!isset($activeProducts[$productId])) {
                            $validationErrors[] = 'En vald produkt finns inte längre. Kontrollera din beställning.';
                            continue;
                        }

                        if ($quantity < 1 || $quantity > 9999) {
                            $validationErrors[] = 'Ange ett giltigt antal (1-9999) för ' . $activeProducts[$productId]['name'] . '.';
                            continue;
                        }

                        $orderItems[] = [
                            'product_id'       => $productId,
                            'quantity'         => $quantity,
                            'unit_price_ore'   => (int) $activeProducts[$productId]['price_ore'],
                            'needs_manual_work' => (int) $activeProducts[$productId]['needs_manual_work'],
                        ];
                    }
                }

                if ($validationErrors) {
                    $error = implode(' ', array_unique($validationErrors));
                } else {
                    $order = PreOrder::insertOrder(
                        $formValues['customer_name'],
                        $formValues['customer_email'],
                        $orderItems
                    );
                    $successOrderNumber = $order['order_number'];

                    try {
                        self::notifyOwner(
                            $order['order_number'],
                            $formValues['customer_name'],
                            $formValues['customer_email'],
                            $orderItems,
                            $activeProducts
                        );
                    } catch (Throwable $e) {
                        error_log('Pre-order owner notification failed: ' . $e->getMessage());
                    }

                    $formValues = ['customer_name' => '', 'customer_email' => ''];
                    $cartItems  = [];
                }
            }
        }

        $products = Product::getActiveProducts();

        require __DIR__ . '/../../Views/public/_header.php';
        require __DIR__ . '/../../Views/public/preorder.php';
        require __DIR__ . '/../../Views/public/_footer.php';
    }

    private static function notifyOwner(
        string $orderNumber,
        string $customerName,
        string $customerEmail,
        array $orderItems,
        array $activeProducts
    ): void {
        $config = require __DIR__ . '/../../../config/config.php';
        $to     = $config['mail']['owner_notify_email'];
        $from   = $config['mail']['site_from_email'];

        $subject = 'Ny förbeställning: ' . $orderNumber;

        $lines    = [];
        $totalOre = 0;
        foreach ($orderItems as $item) {
            $name      = $activeProducts[$item['product_id']]['name'] ?? 'Okänd produkt';
            $lineTotal = $item['quantity'] * $item['unit_price_ore'];
            $totalOre += $lineTotal;
            $lines[]   = sprintf('- %s x%d (%.2f kr/st)', $name, $item['quantity'], $item['unit_price_ore'] / 100);
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
}