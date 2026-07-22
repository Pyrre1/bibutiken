<?php

class BihusetController
{
    public static function index(): void
    {
        $pageTitle = 'Bihuset';
        $activePage = 'bihuset';

        require __DIR__ . '/../../Views/public/_header.php';
        require __DIR__ . '/../../Views/public/bihuset.php';
        require __DIR__ . '/../../Views/public/_footer.php';
    }
}