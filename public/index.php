<?php

require_once __DIR__ . '/../app/Core/init.php';
require_once __DIR__ . '/../app/Core/Router.php';
require_once __DIR__ . '/../app/Controllers/Public/HomeController.php';
require_once __DIR__ . '/../app/Controllers/Public/BeehouseController.php';
require_once __DIR__ . '/../app/Controllers/Public/BeeProductController.php';
require_once __DIR__ . '/../app/Controllers/Public/PreOrderController.php';
require_once __DIR__ . '/../app/Controllers/Public/PrivacyController.php';
require_once __DIR__ . '/../app/Controllers/Admin/LoginController.php';
require_once __DIR__ . '/../app/Controllers/Admin/LogoutController.php';
require_once __DIR__ . '/../app/Controllers/Admin/DashboardController.php';
require_once __DIR__ . '/../app/Controllers/Admin/HoursController.php';
require_once __DIR__ . '/../app/Controllers/Admin/CustomerController.php';
require_once __DIR__ . '/../app/Controllers/Admin/AdminBeeProductController.php';
require_once __DIR__ . '/../app/Controllers/Admin/AdminOrderController.php';
require_once __DIR__ . '/../app/Controllers/Admin/AdminProductController.php';
require_once __DIR__ . '/../app/Controllers/Admin/BannerController.php';

$router = new Router();
$router->dispatch();
return;
