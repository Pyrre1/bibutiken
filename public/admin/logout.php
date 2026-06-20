<?php
require_once __DIR__ . '/../../app/Core/init.php';
Auth::logout();
header('Location: /admin/login.php');
exit;