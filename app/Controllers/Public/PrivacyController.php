<?php

class PrivacyController
{
    public static function index(): void
    {
        $title = 'Integritetspolicy';
        require_once __DIR__ . '/../../Views/public/_header.php';
        require_once __DIR__ . '/../../Views/public/integritetspolicy.php';
        require_once __DIR__ . '/../../Views/public/_footer.php';
    }
}