<?php

class Router
{
    private const ROUTES = [
        '/'                          => [HomeController::class,               'index'],
        '/bihuset'                   => [BihusetController::class,            'index'],
        '/lokalproducerat'           => [LocalProductController::class,       'index'],
        '/vinterfoder'               => [PreOrderController::class,           'index'],
        '/admin'                     => [DashboardController::class,          'index'],
        '/admin/'                    => [DashboardController::class,          'index'],
        '/admin/login'               => [LoginController::class,              'index'],
        '/admin/logout'              => [LogoutController::class,             'index'],
        '/admin/oppettider'          => [HoursController::class,              'index'],
        '/admin/kunder'              => [CustomerController::class,           'index'],
        '/admin/ordrar'              => [AdminOrderController::class,         'index'],
        '/admin/produkter'           => [AdminProductController::class,       'index'],
        '/admin/lokalproducerat'     => [AdminLocalProductController::class,  'index'],
        '/admin/notiser'             => [NotiserController::class,            'index'],
        '/admin/exportera/ordrar'    => [AdminOrderController::class,         'exportCsv'],
        '/admin/exportera/kunder'    => [CustomerController::class,           'exportCsv'],
    ];

    public function dispatch(): void
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        if (!isset(self::ROUTES[$path])) {
            http_response_code(404);
            echo '404 - Sidan kunde inte hittas.';
            return;
        }

        [$class, $method] = self::ROUTES[$path];
        $class::$method();
    }
}