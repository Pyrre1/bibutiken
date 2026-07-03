<?php
require_once __DIR__ . '/../../app/Core/init.php';
require_once __DIR__ . '/../../app/Models/PreOrder.php';
Auth::requireLogin();

$message = null;
$error = null;
$action = $_POST['action'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ogiltig begäran, försök igen.';
    } else {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $itemId  = (int)($_POST['item_id'] ?? 0);

        if ($action === 'set_delivered') {
            PreOrder::setDelivered($orderId, (bool)$_POST['delivered']);
            $message = 'Leveransstatus uppdaterad.';

        } elseif ($action === 'set_manual_status') {
            PreOrder::setManualWorkStatus($itemId, $_POST['status'] ?? '');
            $message = 'Hanteringsstatus uppdaterad.';

        } elseif ($action === 'update_actual_price') {
            $ore = (int)round((float)str_replace(',', '.', $_POST['actual_price_kr'] ?? '0') * 100);
            PreOrder::updateActualPrice($itemId, $ore);
            $message = 'Faktiskt pris uppdaterat.';

        } elseif ($action === 'update_product_price') {
            $productId = (int)($_POST['product_id'] ?? 0);
            $ore = (int)round((float)str_replace(',', '.', $_POST['price_kr'] ?? '0') * 100);
            PreOrder::updateProductPrice($productId, $ore);
            $message = 'Produktpris (nästa säsongs estimat) uppdaterat.';

        } elseif ($action === 'delete_order') {
            $confirmNumber = trim($_POST['confirm_order_number'] ?? '');
            $confirmName   = trim($_POST['confirm_customer_name'] ?? '');
            $order = PreOrder::getOrderWithItems($orderId);
            if (!$order) {
                $error = 'Order hittades inte.';
            } elseif (
                strtoupper($confirmNumber) !== strtoupper($order['order_number']) ||
                mb_strtolower($confirmName) !== mb_strtolower($order['customer_name'])
            ) {
                $error = 'Ordernummer eller namn stämmer inte — ordern togs inte bort.';
            } else {
                PreOrder::deleteOrder($orderId);
                $message = "Order {$order['order_number']} borttagen.";
            }
        }
    }
}

// --- View data ---
$filter = $_GET['filter'] ?? 'all'; // all | pending | delivered | manual
$orders = PreOrder::getAllOrders();

// Filter
$orders = array_filter($orders, function($o) use ($filter) {
    if ($filter === 'pending')   return !$o['is_delivered'];
    if ($filter === 'delivered') return  $o['is_delivered'];
    if ($filter === 'manual')    return  $o['has_manual_work'];
    return true;
});

$summary   = PreOrder::getOrderSummaryByProduct();
$stats     = PreOrder::getOrderStats();

// Detail view
$detailOrder = null;
if (isset($_GET['order'])) {
    $detailOrder = PreOrder::getOrderWithItems((int)$_GET['order']);
}

$pageTitle = 'Beställningar – Admin';
require __DIR__ . '/../../app/Views/admin/_header.php';
require __DIR__ . '/../../app/Views/admin/orders.php';
require __DIR__ . '/../../app/Views/admin/_footer.php';
?>