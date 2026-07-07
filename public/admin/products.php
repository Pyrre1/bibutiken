<?php
require_once __DIR__ . '/../../app/Core/init.php';
require_once __DIR__ . '/../../app/Models/PreOrder.php';
Auth::requireLogin();

$message = null;
$error   = null;
$action  = $_POST['action'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ogiltig begäran, försök igen.';
    } else {
        $productId = (int)($_POST['product_id'] ?? 0);

        if ($action === 'create') {
            $name     = trim($_POST['name'] ?? '');
            $priceOre = (int) round((float) str_replace(',', '.', $_POST['price_kr'] ?? '0') * 100);
            $manual   = !empty($_POST['needs_manual_work']);
            if ($name === '' || $priceOre <= 0) {
                $error = 'Ange namn och ett giltigt pris.';
            } else {
                PreOrder::createProduct($name, $priceOre, $manual);
                $message = 'Produkt skapad.';
            }

        } elseif ($action === 'update') {
            $name     = trim($_POST['name'] ?? '');
            $priceOre = (int) round((float) str_replace(',', '.', $_POST['price_kr'] ?? '0') * 100);
            $manual   = !empty($_POST['needs_manual_work']);
            if ($name === '' || $priceOre <= 0) {
                $error = 'Ange namn och ett giltigt pris.';
            } else {
                PreOrder::updateProduct($productId, $name, $priceOre, $manual);
                $message = 'Produkt uppdaterad.';
            }

        } elseif ($action === 'toggle_active') {
            $active = (bool)(int)($_POST['active'] ?? 0);
            PreOrder::setProductActive($productId, $active);

        } elseif ($action === 'delete') {
            $result = PreOrder::deleteOrDeprecateProduct($productId);
            $message = $result === 'deleted'
                ? 'Produkten togs bort.'
                : 'Produkten har tidigare beställningar och är nu dold.';

        } elseif ($action === 'reorder') {
            $ids = array_map('intval', $_POST['ordered_ids'] ?? []);
            if (!empty($ids)) {
                PreOrder::updateProductSortOrder($ids);
            }
            // Silent — called via JS fetch
            header('Content-Type: application/json');
            echo json_encode(['ok' => true]);
            exit;
        }
    }
}

$products  = PreOrder::getAllProductsAdmin();

$pageTitle = 'Produkter – Admin';
require __DIR__ . '/../../app/Views/admin/_header.php';
require __DIR__ . '/../../app/Views/admin/products.php';
require __DIR__ . '/../../app/Views/admin/_footer.php';