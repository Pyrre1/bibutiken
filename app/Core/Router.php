<?php

class Router
{
    public function dispatch(): void
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        switch ($path) {
            case '/':
            case '/index.php':
                HomeController::index();
                return;

            case '/bihuset.php':
                BihusetController::index();
                return;

            case '/lokalproducerat.php':
                LocalProductController::index();
                return;

            case '/preorder.php':
                PreOrderController::index();
                return;

            case '/admin/login.php':
                LoginController::index();
                return;

            case '/admin/logout.php':
                LogoutController::index();
                return;

            default:
                http_response_code(404);
                echo '404 - Sidan kunde inte hittas.';
        }
    }
}