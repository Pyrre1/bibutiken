<?php

require_once __DIR__ . '/../../Models/Customer.php';

class UnsubscribeController
{
    public static function index(): void
    {
        $config = require __DIR__ . '/../../../config/config.php';
        $secretKey = $config['mail']['secret_key'];

        $customerId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $role       = isset($_GET['roll']) ? trim($_GET['roll']) : '';
        $token      = isset($_GET['token']) ? trim($_GET['token']) : '';

        // Basic input validation
        if ($customerId <= 0 || $role === '' || $token === '') {
            self::showError('Ogiltig länk.');
            return;
        }

        // Allowed roles that can be unsubscribed via link — driven by DB, not hardcoded
        $allowedRoles = Customer::getUnsubscribableRoles();
        if (!in_array($role, $allowedRoles, true)) {
            self::showError('Ogiltig roll.');
            return;
        }

        // Verify token
        $expected = hash_hmac('sha256', $customerId . $role, $secretKey);
        if (!hash_equals($expected, $token)) {
            self::showError('Ogiltig eller utgången länk.');
            return;
        }

        // Check customer exists and has the role
        $customer = Customer::getCustomerWithRole($customerId, $role);
        if (!$customer) {
            // Already removed or never had role — show success anyway (no info leakage)
            self::showConfirmation();
            return;
        }

        // Remove the role
        Customer::removeRole($customerId, $role);

        self::showConfirmation();
    }

    private static function showConfirmation(): void
    {
        http_response_code(200);
        require __DIR__ . '/../../Views/public/avregistrerad.php';
    }

    private static function showError(string $message): void
    {
        http_response_code(400);
        $errorMessage = $message;
        require __DIR__ . '/../../Views/public/avregistrerad_fel.php';
    }
}