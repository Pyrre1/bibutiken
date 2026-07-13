<?php
require_once __DIR__ . '/../../app/Core/init.php';
require_once __DIR__ . '/../../app/Models/PreOrder.php';
Auth::requireLogin();

error_reporting(E_ALL & ~E_DEPRECATED);

$pdo  = Database::getConnection();
$type = $_GET['type'] ?? 'all';
if (!in_array($type, ['all', 'unpicked'])) $type = 'all';

// ── Fetch orders ───────────────────────────────────────────
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
$orders = $stmt->fetchAll();
$orderIds = array_column($orders, 'id');

// ── Tracked products (any product with lagersaldo entries) ─
$trackedProducts = [];
$trackedStmt = $pdo->query(
    'SELECT DISTINCT l.product_id, p.name
    FROM lagersaldo l
    JOIN products p ON p.id = l.product_id
    ORDER BY p.sort_order ASC'
);
foreach ($trackedStmt->fetchAll() as $tp) {
    $trackedProducts[(int)$tp['product_id']] = $tp['name'];
}

// Short header label: first 8 chars + …
function shortProductName(string $name): string {
    return mb_substr($name, 0, 8) . '…';
}

// ── Fetch items for tracked products ──────────────────────
$itemsByOrder = [];
if (!empty($orderIds) && !empty($trackedProducts)) {
    $oPlaceholders = implode(',', array_fill(0, count($orderIds), '?'));
    $pPlaceholders = implode(',', array_keys($trackedProducts));
    $itemStmt = $pdo->prepare(
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

// ── Lagersaldo + local sale events ────────────────────────
$saldoEvents     = [];
$localSaleEvents = [];
if (!empty($trackedProducts)) {
    $pidList = implode(',', array_keys($trackedProducts));

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

// ── Record export + get counts for unpicked ───────────────
$exportCounts = [];
if ($type === 'unpicked' && !empty($orderIds)) {
    PreOrder::recordExport($orderIds);
    $exportCounts = PreOrder::getExportCounts($orderIds);
}

// ── Build unified timeline ─────────────────────────────────
$timeline = [];
foreach ($orders as $o) {
    $timeline[] = ['sort_date' => $o['created_at'],            'type' => 'order',      'data' => $o];
}
foreach ($saldoEvents as $s) {
    $timeline[] = ['sort_date' => $s['event_date'] . ' 00:00:00', 'type' => 'restock',    'data' => $s];
}
foreach ($localSaleEvents as $ls) {
    $timeline[] = ['sort_date' => $ls['event_date'] . ' 00:00:01', 'type' => 'local_sale', 'data' => $ls];
}
usort($timeline, fn($a, $b) => strcmp($a['sort_date'], $b['sort_date']));

// ── Helper: format product cell ───────────────────────────
function formatProductCell(?array $item): string {
    if (!$item) return '';
    $qty = (int)$item['quantity'];
    // Straddle — note contains the full breakdown
    if ($item['actual_price_note'] !== null) {
        return $item['actual_price_note'];
    }
    if ($item['actual_price_ore'] !== null) {
        $kr = number_format($item['actual_price_ore'] / 100, 2, ',', '');
        return "{$qty} st à {$kr}kr";
    }
    // Price not yet calculated (no restock entered yet)
    return "{$qty} st à okänt pris";
}

// ── Output CSV ────────────────────────────────────────────
$labels   = ['all' => 'alla-ordrar', 'unpicked' => 'ej-hamtat'];
$filename = 'ordrar-' . $labels[$type] . '-' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");

// Header row — 7 columns
// Ej hämtade: Datum | Händelse | Ordernummer | E-post      | Mejlstatus | ProdA | ProdB…
// Alla:       Datum | Händelse | Ordernummer | Namn        | Status     | ProdA | ProdB…
$col4header = $type === 'unpicked' ? 'E-post'       : 'Namn';
$col5header = $type === 'unpicked' ? 'Mejlstatus'   : 'Orderstatus';

$headers = ['Datum', 'Händelse', 'Ordernummer', $col4header, $col5header];
foreach ($trackedProducts as $pname) {
    $headers[] = shortProductName($pname);
}
fputcsv($out, $headers, ';', '"', '');

// Data rows
foreach ($timeline as $row) {
    $type_  = $row['type'];
    $data   = $row['data'];
    $cols   = [];

    if ($type_ === 'order') {
        $orderId = (int)$data['id'];
        $date    = date('Y-m-d', strtotime($data['created_at']));

        if ($type === 'unpicked') {
            $count      = $exportCounts[$orderId] ?? 0;
            $col5value  = $count >= 2 ? 'Påminnelse' : ($count === 1 ? 'Info' : '');
            $col4value  = $data['customer_email'];
        } else {
            $col5value  = $data['is_delivered'] ? 'Hämtad' : 'Ej hämtad';
            $col4value  = $data['customer_name'];
        }

        $cols = [$date, 'Beställning', $data['order_number'], $col4value, $col5value];

        foreach (array_keys($trackedProducts) as $pid) {
            $item   = $itemsByOrder[$orderId][$pid] ?? null;
            $cols[] = formatProductCell($item);
        }

    } elseif ($type_ === 'restock') {
        $date  = $data['event_date'];
        $pid   = (int)$data['product_id'];
        $kr    = number_format($data['calculated_price_ore'] / 100, 2, ',', '');

        $cols = [$date, 'Lagerpåfyllning', '', '', ''];

        foreach (array_keys($trackedProducts) as $tpid) {
            $cols[] = $tpid === $pid
                ? "{$data['quantity']} st à {$kr}kr"
                : '';
        }

    } elseif ($type_ === 'local_sale') {
        $date = $data['event_date'];
        $pid  = (int)$data['product_id'];

        $cols = [$date, 'Butiksförsäljning', '', '', ''];

        foreach (array_keys($trackedProducts) as $tpid) {
            if ($tpid === $pid) {
                $kr = $data['calculated_price_ore'] !== null
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