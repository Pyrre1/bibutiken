<?php

class AdminOrderController
{
    public static function index(): void
    {
        require_once __DIR__ . '/../../Models/PreOrder.php';
        require_once __DIR__ . '/../../Models/Product.php';
        require_once __DIR__ . '/../../Models/Settings.php';

        Auth::requireLogin();

        $message = null;
        $error   = null;
        $action  = $_POST['action'] ?? null;

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

                } elseif ($action === 'update_order_item') {
                    $productId = (int)($_POST['product_id'] ?? 0);
                    $quantity  = (int)($_POST['quantity'] ?? 0);
                    if ($productId > 0 && $quantity > 0) {
                        PreOrder::updateOrderItem($itemId, $productId, $quantity);
                        $message = 'Orderrad uppdaterad.';
                    } else {
                        $error = 'Ogiltigt produkt eller antal.';
                    }

                } elseif ($action === 'delete_order_item') {
                    PreOrder::deleteOrderItem($itemId);
                    $message = 'Orderrad borttagen.';

                } elseif ($action === 'update_product_price') {
                    $productId = (int)($_POST['product_id'] ?? 0);
                    $ore       = (int)round((float)str_replace(',', '.', $_POST['price_kr'] ?? '0') * 100);
                    PreOrder::updateProductPrice($productId, $ore);
                    $message = 'Produktpris (nästa säsongs estimat) uppdaterat.';

                } elseif ($action === 'add_order_item') {
                    $orderId   = (int)($_POST['order_id'] ?? 0);
                    $productId = (int)($_POST['product_id'] ?? 0);
                    $quantity  = (int)($_POST['quantity'] ?? 0);
                    if ($orderId > 0 && $productId > 0 && $quantity > 0) {
                        PreOrder::addOrderItem($orderId, $productId, $quantity);
                        $message = 'Produkt tillagd i order.';
                    } else {
                        $error = 'Ogiltigt produkt eller antal.';
                    }

                } elseif ($action === 'toggle_preorder') {
                    $current = Settings::get('preorder_enabled', '1');
                    Settings::set('preorder_enabled', $current === '1' ? '0' : '1');
                    $msg = urlencode('Förbeställningsformulär ' . ($current === '1' ? 'dolt' : 'aktiverat') . '.');
                    header('Location: /admin/ordrar?filter=' . urlencode($_GET['filter'] ?? 'all') . '&msg=' . $msg);
                    exit;

                } elseif ($action === 'delete_order') {
                    $confirmNumber = trim($_POST['confirm_order_number'] ?? '');
                    $confirmName   = trim($_POST['confirm_customer_name'] ?? '');
                    $order         = PreOrder::getOrderWithItems($orderId);
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

        $filter = $_GET['filter'] ?? 'all';
        $orders = PreOrder::getAllOrders();
        $orders = array_filter($orders, function($o) use ($filter) {
            if ($filter === 'pending')    return !$o['is_delivered'];
            if ($filter === 'delivered')  return  $o['is_delivered'];
            if ($filter === 'manual')     return  $o['has_manual_work'];
            if ($filter === 'manual_any') return  $o['has_any_manual_item'];
            return true;
        });

        $summary         = PreOrder::getOrderSummaryByProduct();
        $stats           = PreOrder::getOrderStats();
        $preorderEnabled = Settings::get('preorder_enabled', '1') === '1';

        if (!$message && isset($_GET['msg'])) {
            $message = $_GET['msg'];
        }

        $detailOrder = null;
        $allProducts = Product::getAllProducts();
        if (isset($_GET['order'])) {
            $detailOrder = PreOrder::getOrderWithItems((int)$_GET['order']);
        }

        $pageTitle    = 'Beställningar – Admin';
        $activePage   = 'orders';
        $extraScripts = ['/assets/js/admin-orders.js'];
        $extraStyles  = ['/assets/css/admin-orders.css'];

        require __DIR__ . '/../../Views/admin/_header.php';
        require __DIR__ . '/../../Views/admin/orders.php';
        require __DIR__ . '/../../Views/admin/_footer.php';
    }
    
public static function exportCsv(): void
    {
        require_once __DIR__ . '/../../Models/PreOrder.php';

        Auth::requireLogin();

        error_reporting(E_ALL & ~E_DEPRECATED);

        $pdo  = Database::getConnection();
        $type = $_GET['type'] ?? 'all';
        if (!in_array($type, ['all', 'unpicked'])) $type = 'all';

        if ($type === 'unpicked') {
            $stmt = $pdo->query(
                'SELECT o.id, o.order_number, o.created_at, o.is_delivered,
                        c.name AS customer_name, c.email AS customer_email
                FROM pre_orders o
                JOIN customers c ON c.id = o.customer_id
                WHERE o.is_delivered = 0
                ORDER BY o.created_at ASC'
            );
        } else {
            $stmt = $pdo->query(
                'SELECT o.id, o.order_number, o.created_at, o.is_delivered,
                        c.name AS customer_name, c.email AS customer_email
                FROM pre_orders o
                JOIN customers c ON c.id = o.customer_id
                ORDER BY o.created_at ASC'
            );
        }
        $orders   = $stmt->fetchAll();
        $orderIds = array_column($orders, 'id');

        $trackedProducts = [];
        $trackedStmt     = $pdo->query(
            'SELECT DISTINCT l.product_id, p.name
            FROM lagersaldo l
            JOIN products p ON p.id = l.product_id
            ORDER BY p.sort_order ASC'
        );
        foreach ($trackedStmt->fetchAll() as $tp) {
            $trackedProducts[(int)$tp['product_id']] = $tp['name'];
        }

        $itemsByOrder = [];
        if (!empty($orderIds) && !empty($trackedProducts)) {
            $oPlaceholders = implode(',', array_fill(0, count($orderIds), '?'));
            $pPlaceholders = implode(',', array_keys($trackedProducts));
            $itemStmt      = $pdo->prepare(
                "SELECT i.pre_order_id, i.product_id, i.quantity,
                        i.actual_price_ore, i.actual_price_note
                FROM pre_order_items i
                WHERE i.pre_order_id IN ($oPlaceholders)
                    AND i.product_id IN ($pPlaceholders)"
            );
            $itemStmt->execute($orderIds);
            foreach ($itemStmt->fetchAll() as $item) {
                $itemsByOrder[(int)$item['pre_order_id']][(int)$item['product_id']] = $item;
            }
        }

        $saldoEvents     = [];
        $localSaleEvents = [];
        if (!empty($trackedProducts)) {
            $pidList   = implode(',', array_keys($trackedProducts));
            $saldoStmt = $pdo->query(
                "SELECT product_id, quantity, restocked_at AS event_date, calculated_price_ore
                FROM lagersaldo
                WHERE product_id IN ($pidList)
                ORDER BY restocked_at ASC, created_at ASC"
            );
            $saldoEvents = $saldoStmt->fetchAll();

            $lsStmt = $pdo->query(
                "SELECT product_id, quantity, sold_at AS event_date, calculated_price_ore
                FROM local_sales
                WHERE product_id IN ($pidList)
                ORDER BY sold_at ASC, created_at ASC"
            );
            $localSaleEvents = $lsStmt->fetchAll();
        }

        $exportCounts = [];
        if ($type === 'unpicked' && !empty($orderIds)) {
            PreOrder::recordExport($orderIds);
            $exportCounts = PreOrder::getExportCounts($orderIds);
        }

        $timeline = [];
        foreach ($orders as $o) {
            $timeline[] = ['sort_date' => $o['created_at'],               'type' => 'order',      'data' => $o];
        }
        foreach ($saldoEvents as $s) {
            $timeline[] = ['sort_date' => $s['event_date'] . ' 00:00:00', 'type' => 'restock',    'data' => $s];
        }
        foreach ($localSaleEvents as $ls) {
            $timeline[] = ['sort_date' => $ls['event_date'] . ' 00:00:01', 'type' => 'local_sale', 'data' => $ls];
        }
        usort($timeline, fn($a, $b) => strcmp($a['sort_date'], $b['sort_date']));

        $labels   = ['all' => 'alla-ordrar', 'unpicked' => 'ej-hamtat'];
        $filename = 'ordrar-' . $labels[$type] . '-' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");

        $col4header = $type === 'unpicked' ? 'E-post'     : 'Namn';
        $col5header = $type === 'unpicked' ? 'Mejlstatus' : 'Orderstatus';
        $headers    = ['Datum', 'Händelse', 'Ordernummer', $col4header, $col5header];
        foreach ($trackedProducts as $pname) {
            $headers[] = mb_substr($pname, 0, 8) . '…';
        }
        fputcsv($out, $headers, ';', '"', '');

        foreach ($timeline as $row) {
            $rowType = $row['type'];
            $data    = $row['data'];
            $cols    = [];

            if ($rowType === 'order') {
                $orderId   = (int)$data['id'];
                $date      = date('Y-m-d', strtotime($data['created_at']));
                if ($type === 'unpicked') {
                    $count     = $exportCounts[$orderId] ?? 0;
                    $col5value = $count >= 2 ? 'Påminnelse' : ($count === 1 ? 'Info' : '');
                    $col4value = $data['customer_email'];
                } else {
                    $col5value = $data['is_delivered'] ? 'Hämtad' : 'Ej hämtad';
                    $col4value = $data['customer_name'];
                }
                $cols = [$date, 'Beställning', $data['order_number'], $col4value, $col5value];
                foreach (array_keys($trackedProducts) as $pid) {
                    $item   = $itemsByOrder[$orderId][$pid] ?? null;
                    $cols[] = self::formatProductCell($item);
                }

            } elseif ($rowType === 'restock') {
                $date  = $data['event_date'];
                $pid   = (int)$data['product_id'];
                $kr    = number_format($data['calculated_price_ore'] / 100, 2, ',', '');
                $cols  = [$date, 'Lagerpåfyllning', '', '', ''];
                foreach (array_keys($trackedProducts) as $tpid) {
                    $cols[] = $tpid === $pid ? "{$data['quantity']} st à {$kr}kr" : '';
                }

            } elseif ($rowType === 'local_sale') {
                $date  = $data['event_date'];
                $pid   = (int)$data['product_id'];
                $cols  = [$date, 'Butiksförsäljning', '', '', ''];
                foreach (array_keys($trackedProducts) as $tpid) {
                    if ($tpid === $pid) {
                        $kr     = $data['calculated_price_ore'] !== null
                            ? ' à ' . number_format($data['calculated_price_ore'] / 100, 2, ',', '') . 'kr'
                            : '';
                        $cols[] = "{$data['quantity']} st (butik){$kr}";
                    } else {
                        $cols[] = '';
                    }
                }
            }

            fputcsv($out, $cols, ';', '"', '');
        }

        fclose($out);
        exit;
    }

    private static function formatProductCell(?array $item): string
    {
        if (!$item) return '';
        $qty = (int)$item['quantity'];
        if ($item['actual_price_note'] !== null) return $item['actual_price_note'];
        if ($item['actual_price_ore']  !== null) {
            $kr = number_format($item['actual_price_ore'] / 100, 2, ',', '');
            return "{$qty} st à {$kr}kr";
        }
        return "{$qty} st à okänt pris";
    }
}