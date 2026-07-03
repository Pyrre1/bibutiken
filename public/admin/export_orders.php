<?php
error_reporting(E_ALL & ~E_DEPRECATED);
require_once __DIR__ . '/../../app/Core/init.php';
require_once __DIR__ . '/../../app/Models/PreOrder.php';
Auth::requireLogin();

$type = $_GET['type'] ?? 'all'; // all | bifor | dulco | both

$pdo = \Database::getConnection();

// Identify products by name pattern
$biforIds = $pdo->query(
    "SELECT id FROM products WHERE name LIKE '%Bifor%'"
)->fetchAll(PDO::FETCH_COLUMN);

$dulcoIds = $pdo->query(
    "SELECT id FROM products WHERE name LIKE '%Ideal Api%'"
)->fetchAll(PDO::FETCH_COLUMN);

// Build query based on filter type
if ($type === 'bifor') {
    $bPH = implode(',', array_fill(0, count($biforIds), '?'));
    $dPH = implode(',', array_fill(0, count($dulcoIds), '?'));
    $stmt = $pdo->prepare(
        "SELECT DISTINCT o.customer_name, o.customer_email, o.order_number, o.created_at
        FROM pre_orders o
        WHERE EXISTS (
            SELECT 1 FROM pre_order_items i WHERE i.pre_order_id = o.id AND i.product_id IN ($bPH)
        )
        AND NOT EXISTS (
            SELECT 1 FROM pre_order_items i WHERE i.pre_order_id = o.id AND i.product_id IN ($dPH)
        )
        ORDER BY o.created_at ASC"
    );
    $stmt->execute(array_merge($biforIds, $dulcoIds));

} elseif ($type === 'dulco') {
    $bPH = implode(',', array_fill(0, count($biforIds), '?'));
    $dPH = implode(',', array_fill(0, count($dulcoIds), '?'));
    $stmt = $pdo->prepare(
        "SELECT DISTINCT o.customer_name, o.customer_email, o.order_number, o.created_at
        FROM pre_orders o
        WHERE EXISTS (
            SELECT 1 FROM pre_order_items i WHERE i.pre_order_id = o.id AND i.product_id IN ($dPH)
        )
        AND NOT EXISTS (
            SELECT 1 FROM pre_order_items i WHERE i.pre_order_id = o.id AND i.product_id IN ($bPH)
        )
        ORDER BY o.created_at ASC"
    );
    $stmt->execute(array_merge($dulcoIds, $biforIds));

} elseif ($type === 'both') {
    // Orders containing at least one bifor AND at least one dulco item
    $bPH = implode(',', array_fill(0, count($biforIds), '?'));
    $dPH = implode(',', array_fill(0, count($dulcoIds), '?'));
    $stmt = $pdo->prepare(
        "SELECT DISTINCT o.customer_name, o.customer_email, o.order_number, o.created_at
        FROM pre_orders o
        WHERE EXISTS (
            SELECT 1 FROM pre_order_items i WHERE i.pre_order_id = o.id AND i.product_id IN ($bPH)
        )
        AND EXISTS (
            SELECT 1 FROM pre_order_items i WHERE i.pre_order_id = o.id AND i.product_id IN ($dPH)
        )
        ORDER BY o.created_at ASC"
    );
    $stmt->execute(array_merge($biforIds, $dulcoIds));

} elseif ($type === 'unpicked') {
    $stmt = $pdo->query(
        "SELECT o.customer_name, o.customer_email, o.order_number, o.created_at
        FROM pre_orders o
        WHERE o.is_delivered = 0
        ORDER BY o.created_at ASC"
    );

} else {
    // all
    $stmt = $pdo->query(
        "SELECT DISTINCT o.customer_name, o.customer_email, o.order_number, o.created_at
        FROM pre_orders o
        ORDER BY o.created_at ASC"
    );
}

$rows = $stmt->fetchAll();

$labels = [
    'all'   => 'alla-kunder',
    'bifor' => 'endast-bifor',
    'dulco' => 'endast-dulcofruct',
    'both'  => 'bifor-och-dulcofruct',
    'unpicked' => 'ej-hamtat',
];
$filename = 'epostlista-' . ($labels[$type] ?? 'export') . '-' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
// BOM for Excel UTF-8 compatibility
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, ['Namn', 'E-post', 'Ordernummer', 'Datum'], ';', '"', '');

foreach ($rows as $row) {
    fputcsv($out, [
        $row['customer_name'],
        $row['customer_email'],
        $row['order_number'],
        date('Y-m-d', strtotime($row['created_at'])),
    ], ';', '"', '');
}

fclose($out);
exit;