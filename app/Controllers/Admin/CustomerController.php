<?php

class CustomerController
{
    public static function index(): void
    {
        require_once __DIR__ . '/../../Models/Customer.php';

        Auth::requireLogin();

        $message    = null;
        $error      = null;
        $searchTerm = trim($_GET['q'] ?? '');
        $viewId     = isset($_GET['id']) ? (int)$_GET['id'] : null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::validateCsrf($_POST['csrf_token'] ?? null)) {
                $error = 'Ogiltig begäran, försök igen.';
            } else {
                $action     = $_POST['action'] ?? '';
                $customerId = (int)($_POST['customer_id'] ?? 0);

                if ($action === 'edit_customer') {
                    $name  = trim($_POST['name'] ?? '');
                    $email = trim($_POST['email'] ?? '');
                    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $error = 'Ange ett giltigt namn och e-postadress.';
                    } else {
                        Customer::updateCustomer($customerId, $name, $email);
                        $message = 'Kundinformation uppdaterad.';
                    }
                    $viewId = $customerId;

                } elseif ($action === 'anonymize_email') {
                    Customer::anonymizeEmail($customerId);
                    $message = 'E-postadressen anonymiserad.';
                    $viewId  = $customerId;

                } elseif ($action === 'anonymize_full') {
                    $confirmName  = trim($_POST['confirm_name'] ?? '');
                    $confirmEmail = trim($_POST['confirm_email'] ?? '');
                    $customer     = Customer::getCustomerById($customerId);
                    if (
                        mb_strtolower($confirmName)  !== mb_strtolower($customer['name']) ||
                        strtolower($confirmEmail) !== strtolower($customer['email'])
                    ) {
                        $error  = 'Namn eller e-post stämmer inte — ingen ändring gjordes.';
                        $viewId = $customerId;
                    } else {
                        Customer::anonymizeCustomer($customerId);
                        $message = 'Kunden är helt anonymiserad.';
                        $viewId  = $customerId;
                    }

                } elseif ($action === 'set_roles') {
                    $roleIds = array_map('intval', $_POST['roles'] ?? []);
                    Customer::setCustomerRoles($customerId, $roleIds);
                    $message = 'Roller uppdaterade.';
                    $viewId  = $customerId;
                }
            }
        }

        $roleFilter    = $_GET['role'] ?? null;
        $searchResults = [];

        if ($searchTerm !== '') {
            $searchResults = Customer::searchCustomers($searchTerm);
        }

        $allCustomers = (!$viewId && $searchTerm === '')
            ? Customer::getAllCustomers($roleFilter)
            : [];

        $customer = null;
        $allRoles = Customer::getAllRoles();

        if ($viewId) {
            $customer = Customer::getCustomerById($viewId);
        }

        $pageTitle    = 'Kunder – Admin';
        $activePage   = 'customers';
        $extraScripts = ['/assets/js/admin-customers.js'];
        $extraStyles  = ['/assets/css/admin-customers.css'];

        require __DIR__ . '/../../Views/admin/_header.php';
        require __DIR__ . '/../../Views/admin/customers.php';
        require __DIR__ . '/../../Views/admin/_footer.php';
    }
    
public static function exportCsv(): void
    {
        require_once __DIR__ . '/../../Models/Customer.php';

        Auth::requireLogin();

        error_reporting(E_ALL & ~E_DEPRECATED);

        $roleFilter = $_GET['role'] ?? null;
        $customers  = Customer::getAllCustomers($roleFilter);
        $customers  = array_filter($customers, fn($c) => !$c['has_ingen_mejl']);

        $rolePart = $roleFilter
            ? '-' . preg_replace('/[^a-z0-9_]/', '', $roleFilter)
            : '-alla';
        $filename = 'kunder' . $rolePart . '-' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, ['Namn', 'Inga mejl', 'E-post', 'Roller', 'Antal order', 'Kund sedan'], ';', '"', '');
        foreach ($customers as $row) {
            fputcsv($out, [
                $row['name'],
                $row['has_ingen_mejl'] ? 'JA - skicka ej mejl' : '',
                $row['email'],
                $row['role_names'] ?? '',
                $row['order_count'],
                date('Y-m-d', strtotime($row['created_at'])),
            ], ';', '"', '');
        }

        fclose($out);
        exit;
    }
}