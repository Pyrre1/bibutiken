<?php
require_once __DIR__ . '/../../app/Core/init.php';
require_once __DIR__ . '/../../app/Models/Customer.php';
Auth::requireLogin();

error_reporting(E_ALL & ~E_DEPRECATED);

$roleFilter = $_GET['role'] ?? null;
$customers  = Customer::getAllCustomers($roleFilter);

// Exclude ingen_mejl customers from all exports
$customers = array_filter($customers, fn($c) => !$c['has_ingen_mejl']);

$rolePart  = $roleFilter ? '-' . preg_replace('/[^a-z0-9_]/', '', $roleFilter) : '-alla';
$filename  = 'kunder' . $rolePart . '-' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");

fputcsv($out, ['Namn', 'Inga mejl', 'E-post', 'Roller', 'Antal order', 'Kund sedan'], ';', '"', '');
foreach ($customers as $row) {
    fputcsv($out, [
        $row['name'],
        $row['has_ingen_mejl'] ? 'JA - skicka ej mejl' : '',
        $row['email'],
        $row['role_names'] ?? '',
        $row['order_count'],
        date('Y-m-d', strtotime($row['created_at'])),
    ], ';', '"', '');
}

fclose($out);
exit;