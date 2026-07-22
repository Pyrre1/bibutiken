<?php

class AdminProductController
{
    public static function index(): void
    {
        require_once __DIR__ . '/../../Models/Product.php';
        require_once __DIR__ . '/../../Models/PreOrder.php';

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
                        Product::createProduct($name, $priceOre, $manual);
                        $message = 'Produkt skapad.';
                    }

                } elseif ($action === 'update') {
                    $name     = trim($_POST['name'] ?? '');
                    $priceOre = (int) round((float) str_replace(',', '.', $_POST['price_kr'] ?? '0') * 100);
                    $manual   = !empty($_POST['needs_manual_work']);
                    if ($name === '' || $priceOre <= 0) {
                        $error = 'Ange namn och ett giltigt pris.';
                    } else {
                        Product::updateProduct($productId, $name, $priceOre, $manual);
                        $message = 'Produkt uppdaterad.';
                    }

                } elseif ($action === 'toggle_active') {
                    $active = (bool)(int)($_POST['active'] ?? 0);
                    Product::setProductActive($productId, $active);

                } elseif ($action === 'delete') {
                    $result  = Product::deleteOrDeprecateProduct($productId);
                    $message = $result === 'deleted'
                        ? 'Produkten togs bort.'
                        : 'Produkten har tidigare beställningar och är nu dold.';

                } elseif ($action === 'reorder') {
                    $ids = array_map('intval', $_POST['ordered_ids'] ?? []);
                    if (!empty($ids)) Product::updateProductSortOrder($ids);
                    header('Content-Type: application/json');
                    echo json_encode(['ok' => true]);
                    exit;

                } elseif ($action === 'lagersaldo_create') {
                    $pid      = (int)($_POST['saldo_product_id'] ?? 0);
                    $qty      = (int)($_POST['saldo_quantity'] ?? 0);
                    $date     = trim($_POST['saldo_date'] ?? '');
                    $priceOre = (int) round((float) str_replace(',', '.', $_POST['saldo_price_kr'] ?? '0') * 100);
                    if ($pid <= 0 || $qty <= 0 || $date === '' || $priceOre <= 0) {
                        $error = 'Fyll i alla fält för lagerpåfyllning.';
                    } else {
                        PreOrder::addLagersaldo($pid, $qty, $date, $priceOre);
                        $message = 'Lagerpåfyllning sparad och priser omräknade.';
                    }

                } elseif ($action === 'lagersaldo_delete') {
                    $sid = (int)($_POST['saldo_id'] ?? 0);
                    if ($sid > 0) {
                        PreOrder::deleteLagersaldo($sid);
                        $message = 'Lagerrad borttagen och priser omräknade.';
                    }

                } elseif ($action === 'local_sale_delete') {
                    $lsid = (int)($_POST['ls_id'] ?? 0);
                    if ($lsid > 0) {
                        PreOrder::deleteLocalSale($lsid);
                        $message = 'Butiksförsäljning borttagen och priser omräknade.';
                    }

                } elseif ($action === 'local_sales_create') {
                    $productIds = array_map('intval', $_POST['ls_product_id'] ?? []);
                    $quantities = array_map('intval', $_POST['ls_quantity']   ?? []);
                    $dates      = $_POST['ls_date'] ?? [];
                    $rows       = [];
                    $valid      = true;
                    foreach ($productIds as $i => $pid) {
                        $qty  = $quantities[$i] ?? 0;
                        $date = trim($dates[$i] ?? '');
                        if ($pid <= 0 || $qty <= 0 || $date === '') { $valid = false; break; }
                        $rows[] = ['product_id' => $pid, 'quantity' => $qty, 'sold_at' => $date];
                    }
                    if (!$valid || empty($rows)) {
                        $error = 'Fyll i alla fält för butiksförsäljning.';
                    } else {
                        PreOrder::addLocalSales($rows);
                        $message = 'Butiksförsäljning sparad och priser omräknade.';
                    }
                }
            }
        }

        $products     = Product::getAllProductsAdmin();
        $lagersaldo   = PreOrder::getLagersaldo();
        $localSales   = PreOrder::getLocalSales();
        $pageTitle    = 'Produkter – Admin';
        $activePage   = 'products';
        $extraScripts = ['/assets/js/admin-products.js'];
        $extraStyles  = ['/assets/css/admin-products.css'];

        require __DIR__ . '/../../Views/admin/_header.php';
        require __DIR__ . '/../../Views/admin/products.php';
        require __DIR__ . '/../../Views/admin/_footer.php';
    }
}