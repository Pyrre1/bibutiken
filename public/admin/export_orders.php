<?php
require_once __DIR__ . '/../../app/Core/init.php';
Auth::requireLogin();

error_reporting(E_ALL & ~E_DEPRECATED);

$pdo = Database::getConnection();
$type = $_GET['type'] ?? 'all';

$biforIds = $pdo->query("SELECT id FROM products WHERE name LIKE '%Bifor%'")->fetchAll(PDO::FETCH_COLUMN);
$dulcoIds = $pdo->query("SELECT id FROM products WHERE name LIKE '%Ideal Api%'")->fetchAll(PDO::FETCH_COLUMN);

function buildQuery(PDO $pdo, string $type, array $biforIds, array $dulcoIds): array
{
    $base = "SELECT DISTINCT c.name AS customer_name, c.email AS customer_email,
                    o.order_number, o.created_at, o.is_delivered
            FROM pre_orders o
            JOIN customers c ON c.id = o.customer_id";

    if ($type === 'separated') {
        // Returns all three groups
        $results = [];

        // Bifor only
        $bPH = implode(',', array_fill(0, count($biforIds), '?'));
        $dPH = implode(',', array_fill(0, count($dulcoIds), '?'));
        $stmt = $pdo->prepare("$base
            WHERE EXISTS (SELECT 1 FROM pre_order_items i WHERE i.pre_order_id = o.id AND i.product_id IN ($bPH))
            AND NOT EXISTS (SELECT 1 FROM pre_order_items i WHERE i.pre_order_id = o.id AND i.product_id IN ($dPH))
            AND o.is_delivered = 0
            ORDER BY o.created_at ASC");
        $stmt->execute(array_merge($biforIds, $dulcoIds));
        $results['bifor'] = $stmt->fetchAll();

        // Dulco only
        $stmt = $pdo->prepare("$base
            WHERE EXISTS (SELECT 1 FROM pre_order_items i WHERE i.pre_order_id = o.id AND i.product_id IN ($dPH))
            AND NOT EXISTS (SELECT 1 FROM pre_order_items i WHERE i.pre_order_id = o.id AND i.product_id IN ($bPH))
            AND o.is_delivered = 0
            ORDER BY o.created_at ASC");
        $stmt->execute(array_merge($dulcoIds, $biforIds));
        $results['dulco'] = $stmt->fetchAll();

        // Both
        $stmt = $pdo->prepare("$base
            WHERE EXISTS (SELECT 1 FROM pre_order_items i WHERE i.pre_order_id = o.id AND i.product_id IN ($bPH))
            AND EXISTS (SELECT 1 FROM pre_order_items i WHERE i.pre_order_id = o.id AND i.product_id IN ($dPH))
            AND o.is_delivered = 0
            ORDER BY o.created_at ASC");
        $stmt->execute(array_merge($biforIds, $dulcoIds));
        $results['both'] = $stmt->fetchAll();

        return $results;
    }

    if ($type === 'unpicked') {
        $stmt = $pdo->query("$base WHERE o.is_delivered = 0 ORDER BY o.created_at ASC");
        return $stmt->fetchAll();
    }

    // all
    $stmt = $pdo->query("$base ORDER BY o.created_at ASC");
    return $stmt->fetchAll();
}

$labels = [
    'all'       => 'alla-kunder',
    'separated' => 'separerad',
    'unpicked'  => 'ej-hamtat',
];

$filename = 'epostlista-' . ($labels[$type] ?? 'export') . '-' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");

$results = buildQuery($pdo, $type, $biforIds, $dulcoIds);

if ($type === 'separated') {
    $sections = [
        'bifor' => 'Endast Bifor (ej Dulcofruct)',
        'dulco' => 'Endast Dulcofruct (ej Bifor)',
        'both'  => 'Bifor och Dulcofruct',
    ];
    foreach ($sections as $key => $label) {
        fputcsv($out, [$label], ';', '"', '');
        fputcsv($out, ['Namn', 'E-post', 'Ordernummer', 'Datum'], ';', '"', '');
        foreach ($results[$key] as $row) {
            fputcsv($out, [
                $row['customer_name'],
                $row['customer_email'],
                $row['order_number'],
                date('Y-m-d', strtotime($row['created_at'])),
            ], ';', '"', '');
        }
        fputcsv($out, [], ';', '"', ''); // empty row between sections
    }
} else {
    $showStatus = $type === 'all';
    $headers = ['Namn', 'E-post', 'Ordernummer', 'Datum'];
    if ($showStatus) $headers[] = 'Status';
    fputcsv($out, $headers, ';', '"', '');
    foreach ($results as $row) {
        $line = [
            $row['customer_name'],
            $row['customer_email'],
            $row['order_number'],
            date('Y-m-d', strtotime($row['created_at'])),
        ];
        if ($showStatus) $line[] = $row['is_delivered'] ? 'Hämtad' : 'Ej hämtad';
        fputcsv($out, $line, ';', '"', '');
    }
}

fclose($out);
exit;