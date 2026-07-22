<?php

class DashboardController
{
    public static function index(): void
    {
        require_once __DIR__ . '/../../Models/Dashboard.php';
        require_once __DIR__ . '/../../Models/Product.php';
        require_once __DIR__ . '/../../Models/HoursPlan.php';
        require_once __DIR__ . '/../../Models/Settings.php';
        require_once __DIR__ . '/../../Core/HoursResolver.php';

        Auth::requireLogin();

        $previousLoginAt = $_SESSION['previous_login_at'] ?? null;
        $stats = Dashboard::getDashboardStats($previousLoginAt);

        $thisWeekNum = (int) date('W');
        $thisYear    = (int) date('Y');
        $nextWeekNum = $thisWeekNum + 1;
        $nextYear    = $thisYear;
        if ($nextWeekNum > 52) { $nextWeekNum = 1; $nextYear++; }

        $thisWeekPlan    = HoursResolver::resolveForWeek($thisWeekNum, $thisYear);
        $nextWeekPlan    = HoursResolver::resolveForWeek($nextWeekNum, $nextYear);
        $preorderEnabled = Settings::get('preorder_enabled', '1') === '1';

        $pageTitle   = 'Översikt – Admin';
        $activePage  = 'dashboard';
        $extraStyles = ['/assets/css/admin-dashboard.css'];

        require __DIR__ . '/../../Views/admin/_header.php';
        require __DIR__ . '/../../Views/admin/dashboard.php';
        require __DIR__ . '/../../Views/admin/_footer.php';
    }
}