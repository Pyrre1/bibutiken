<?php

class PreOrderController
{
    public static function index(): void
    {
        require_once __DIR__ . '/../../Core/Database.php';
        require_once __DIR__ . '/../../Models/PreOrder.php';
        require_once __DIR__ . '/../../Models/Product.php';
        require_once __DIR__ . '/../../Models/Settings.php';
        require_once __DIR__ . '/../../Models/Customer.php';
        require_once __DIR__ . '/../../Core/Security.php';

        $pageTitle    = 'Förbeställning - Bibutiken';
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
            require __DIR__ . '/../../Views/public/vinterfoder_stangd.php';
            require __DIR__ . '/../../Views/public/_footer.php';
            return;
        }

        $error             = null;
        $successOrderNumber = null;
        $formValues        = ['customer_name' => '', 'customer_email' => ''];
        $cartItems         = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $hashedIp      = PreOrder::hashIp();
            $attemptedEmail = strtolower(trim($_POST['customer_email'] ?? ''));

            if (!Security::validateCsrf($_POST['csrf_token'] ?? null)) {
                PreOrder::logRejected($hashedIp, 'csrf');
                $error = 'Ogiltig begäran, försök igen.';
            } elseif (!empty($_POST['website'])) {
                PreOrder::logRejected($hashedIp, 'honeypot', $attemptedEmail ?: null);
                // Silent fake success — do not reveal the honeypot
                $successOrderNumber = null;
            } elseif ((int)($_POST['form_loaded_at'] ?? 0) > 0 && (time() - (int)$_POST['form_loaded_at']) < 10) {
                PreOrder::logRejected($hashedIp, 'timing');
                $error = 'Formuläret skickades för snabbt. Försök igen.';
            } elseif (!PreOrder::checkRateLimit($hashedIp)) {
                PreOrder::logRejected($hashedIp, 'rate_limit');
                $error = 'Du har redan skickat en förbeställning nyligen. Vänta en stund och försök igen.';
            } else {
                PreOrder::recordAttempt($hashedIp);
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
                        self::sendConfirmation(
                            $order['customer_id'],
                            $formValues['customer_name'],
                            $formValues['customer_email'],
                            $order['id'],
                            $orderItems,
                            $activeProducts,
                            $order
                        );
                    } catch (Throwable $e) {
                        error_log('Pre-order confirmation email failed: ' . $e->getMessage());
                    }

                    $formValues = ['customer_name' => '', 'customer_email' => ''];
                    $cartItems  = [];
                }
            }
        }

        $products = Product::getActiveProducts();

        require __DIR__ . '/../../Views/public/_header.php';
        require __DIR__ . '/../../Views/public/vinterfoder.php';
        require __DIR__ . '/../../Views/public/_footer.php';
    }

    private static function sendConfirmation(
        int $customerId,
        string $customerName,
        string $customerEmail,
        int $orderId,
        array $orderItems,
        array $activeProducts,
        array $order = []
    ): void {
        // Respect ingen_mejl master opt-out
        $customer = Customer::getCustomerById($customerId);
        if (!$customer) return;
        $roles = array_column($customer['roles'], 'name');
        if (in_array('ingen_mejl', $roles, true)) return;

        // Build {varor} — product list lines
        $lines    = [];
        $totalOre = 0;
        foreach ($orderItems as $item) {
            $name      = $activeProducts[$item['product_id']]['name'] ?? 'Okänd produkt';
            $lineTotal = $item['quantity'] * $item['unit_price_ore'];
            $totalOre += $lineTotal;
            $lines[]   = sprintf('%s × %d st', $name, $item['quantity']);
        }
        $varaPlain = implode("\n", $lines);
        $varaHtml  = implode('<br>', array_map('htmlspecialchars', $lines));

        // Fetch confirmation template from DB
        $pdo      = Database::getConnection();
        $tmplStmt = $pdo->prepare(
            "SELECT amne, brodtext FROM mail_templates
            WHERE namn = 'Orderbekräftelse' AND roll = 'vinterfoder'
            LIMIT 1"
        );
        $tmplStmt->execute();
        $tmpl = $tmplStmt->fetch();

        if (!$tmpl) {
            error_log('sendConfirmation: Orderbekräftelse template not found in mail_templates');
            return;
        }

        $vars = [
            'namn'    => $customerName,
            'vara'    => $varaPlain,
            'pris'    => number_format($totalOre / 100, 2, ',', ' '),
            'ordernr' => $order['order_number'] ?? '',
        ];

        // For HTML body, swap plain newline product list for <br> separated version
        $bodyHtml  = nl2br(htmlspecialchars($tmpl['brodtext']));
        $bodyHtml  = str_replace('{varor}', $varaHtml, $bodyHtml);
        $bodyPlain = $tmpl['brodtext'];

        require_once __DIR__ . '/../../Core/MailService.php';
        $mailer = new MailService();
        $mailer->send(
            $customerEmail,
            $customerName,
            $tmpl['amne'],
            $bodyHtml,
            $bodyPlain,
            $vars,
            'vinterfoder',
            $customerId,
            $orderId
        );
    }
}