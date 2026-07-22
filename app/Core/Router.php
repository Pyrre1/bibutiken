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

            default:
                http_response_code(404);
                echo '404 - Sidan kunde inte hittas.';
        }
    }
}