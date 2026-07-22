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

            case '/admin/dashboard.php':
                DashboardController::index();
                return;

            case '/admin/hours.php':
                HoursController::index();
                return;

            case '/admin/customers.php':
                CustomerController::index();
                return;

            case '/admin/local-products.php':
                AdminLocalProductController::index();
                return;

            case '/admin/orders.php':
                AdminOrderController::index();
                return;

            case '/admin/products.php':
                AdminProductController::index();
                return;
            
            default:
                http_response_code(404);
                echo '404 - Sidan kunde inte hittas.';
        }
    }
}