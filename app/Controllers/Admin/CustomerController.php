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
}