<?php

class LogoutController
{
    public static function index(): void
    {
        Auth::logout();
        header('Location: /admin/login');
        exit;
    }
}