<?php
require_once __DIR__ . '/../app/Core/init.php';
require_once __DIR__ . '/../app/Models/LocalProduct.php';

$grouped    = LocalProduct::getActiveGroupedByType();
$pageTitle  = 'Lokalproducerat';
$activePage = 'lokalproducerat';
$extraStyles = ['/assets/css/lokalproducerat.css'];
require __DIR__ . '/../app/Views/public/_header.php';
require __DIR__ . '/../app/Views/public/lokalproducerat.php';
require __DIR__ . '/../app/Views/public/_footer.php';