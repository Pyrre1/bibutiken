<?php

class BeeProductController
{
    public static function index(): void
    {
        require_once __DIR__ . '/../../Models/BeeProduct.php';

        $grouped     = BeeProduct::getActiveGroupedByType();
        $pageTitle   = 'Biprodukter';
        $activePage  = 'biprodukter';
        $extraStyles = ['/assets/css/biprodukter.css'];

        require __DIR__ . '/../../Views/public/_header.php';
        require __DIR__ . '/../../Views/public/biprodukter.php';
        require __DIR__ . '/../../Views/public/_footer.php';
    }
}