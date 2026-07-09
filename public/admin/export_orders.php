<?php
require_once __DIR__ . '/../../app/Core/init.php';
Auth::requireLogin();

error_reporting(E_ALL & ~E_DEPRECATED);

$pdo = Database::getConnection();
$type = $_GET['type'] ?? 'all';
if (!in_array($type, ['all', 'unpicked'])) $type = 'all';

$base = "SELECT c.name AS customer_name, c.email AS customer_email,
                o.order_number, o.created_at, o.is_delivered
        FROM pre_orders o
        JOIN customers c ON c.id = o.customer_id";

if ($type === 'unpicked') {
    $stmt = $pdo->query("$base WHERE o.is_delivered = 0 ORDER BY o.created_at ASC");
} else {
    $stmt = $pdo->query("$base ORDER BY o.created_at ASC");
}
$results = $stmt->fetchAll();

$labels = ['all' => 'alla-ordrar', 'unpicked' => 'ej-hamtat'];
$filename = 'ordrar-' . $labels[$type] . '-' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");

fputcsv($out, ['Namn', 'E-post', 'Ordernummer', 'Datum', 'Status'], ';', '"', '');
foreach ($results as $row) {
    fputcsv($out, [
        $row['customer_name'],
        $row['customer_email'],
        $row['order_number'],
        date('Y-m-d', strtotime($row['created_at'])),
        $row['is_delivered'] ? 'Hämtad' : 'Ej hämtad',
    ], ';', '"', '');
}

fclose($out);
exit;