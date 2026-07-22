<?php

require_once __DIR__ . '/../../app/Core/init.php';
require_once __DIR__ . '/../../app/Core/Router.php';
require_once __DIR__ . '/../../app/Controllers/Admin/HoursController.php';

$router = new Router();
$router->dispatch();