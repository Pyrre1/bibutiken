<?php
require_once __DIR__ . '/../../app/Core/init.php';
require_once __DIR__ . '/../../app/Models/LocalProduct.php';
Auth::requireLogin();

$message = null;
$error   = null;
$action  = $_POST['action'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ogiltig begäran, försök igen.';
    } else {
        $id = (int) ($_POST['product_id'] ?? 0);

        if ($action === 'create') {
            $typeId      = (int) ($_POST['type_id'] ?? 0);
            $size        = trim($_POST['size'] ?? '');
            $name        = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '') ?: null;
            $priceOre    = (int) round(
                (float) str_replace(',', '.', $_POST['price_kr'] ?? '0') * 100
            );
            if ($typeId <= 0 || $size === '' || $name === '' || $priceOre <= 0) {
                $error = 'Fyll i typ, storlek, namn och ett giltigt pris.';
            } else {
                LocalProduct::create($typeId, $size, $name, $description, $priceOre);
                $message = 'Produkt skapad.';
            }

        } elseif ($action === 'update') {
            $typeId      = (int) ($_POST['type_id'] ?? 0);
            $size        = trim($_POST['size'] ?? '');
            $name        = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '') ?: null;
            $priceOre    = (int) round(
                (float) str_replace(',', '.', $_POST['price_kr'] ?? '0') * 100
            );
            if ($id <= 0 || $typeId <= 0 || $size === '' || $name === '' || $priceOre <= 0) {
                $error = 'Fyll i alla fält.';
            } else {
                LocalProduct::update($id, $typeId, $size, $name, $description, $priceOre);
                $message = 'Produkt uppdaterad.';
            }

        } elseif ($action === 'toggle_active') {
            $active = (bool) (int) ($_POST['active'] ?? 0);
            LocalProduct::setActive($id, $active);

        } elseif ($action === 'delete') {
            LocalProduct::delete($id);
            $message = 'Produkt borttagen.';

        } elseif ($action === 'reorder') {
            $ids = array_map('intval', $_POST['ordered_ids'] ?? []);
            if (!empty($ids)) {
                LocalProduct::updateSortOrder($ids);
            }
            header('Content-Type: application/json');
            echo json_encode(['ok' => true]);
            exit;
        }
    }
}

$products    = LocalProduct::getAllAdmin();
$types       = LocalProduct::getAllTypes();
$pageTitle   = 'Egna produkter – Admin';
$activePage  = 'local';
$extraStyles = ['/assets/css/admin-local-products.css'];
$extraScripts = ['/assets/js/admin-local-products.js'];
require __DIR__ . '/../../app/Views/admin/_header.php';
require __DIR__ . '/../../app/Views/admin/local-products.php';
require __DIR__ . '/../../app/Views/admin/_footer.php';