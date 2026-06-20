<?php
require_once __DIR__ . '/../../app/Core/init.php';
header('Location: ' . (Auth::isLoggedIn() ? '/admin/dashboard.php' : '/admin/login.php'));
exit;