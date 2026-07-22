<?php

require_once __DIR__ . '/../app/Core/init.php';
require_once __DIR__ . '/../app/Core/Router.php';
require_once __DIR__ . '/../app/Controllers/Public/HomeController.php';
require_once __DIR__ . '/../app/Controllers/Public/BihusetController.php';
require_once __DIR__ . '/../app/Controllers/Public/LocalProductController.php';
require_once __DIR__ . '/../app/Controllers/Public/PreOrderController.php';
require_once __DIR__ . '/../app/Controllers/Admin/LoginController.php';

$router = new Router();
$router->dispatch();
return;
