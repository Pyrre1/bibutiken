<?php

class LocalProductController
{
    public static function index(): void
    {
        require_once __DIR__ . '/../../Models/LocalProduct.php';

        $grouped     = LocalProduct::getActiveGroupedByType();
        $pageTitle   = 'Lokalproducerat';
        $activePage  = 'lokalproducerat';
        $extraStyles = ['/assets/css/lokalproducerat.css'];

        require __DIR__ . '/../../Views/public/_header.php';
        require __DIR__ . '/../../Views/public/lokalproducerat.php';
        require __DIR__ . '/../../Views/public/_footer.php';
    }
}