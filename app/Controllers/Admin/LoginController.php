<?php

class LoginController
{
    public static function index(): void
    {
        if (Auth::isLoggedIn()) {
            header('Location: /admin');
            exit;
        }

        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::validateCsrf($_POST['csrf_token'] ?? null)) {
                $error = 'Ogiltig begäran, försök igen.';
            } else {
                $result = Auth::attemptLogin($_POST['username'] ?? '', $_POST['password'] ?? '');
                if ($result['success']) {
                    header('Location: /admin');
                    exit;
                }
                $error = $result['message'];
            }
        }

        require __DIR__ . '/../../Views/admin/login.php';
    }
}