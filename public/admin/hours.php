<?php
require_once __DIR__ . '/../../app/Core/init.php';
require_once __DIR__ . '/../../app/Models/HoursPlan.php';
Auth::requireLogin();

$dayNames = [1=>'Monday',2=>'Tuesday',3=>'Wednesday',4=>'Thursday',5=>'Friday',6=>'Saturday',7=>'Sunday'];

$mode = $_GET['mode'] ?? 'default';
$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid request, please try again.';
    } else {
        $postMode = $_POST['mode'] ?? 'default';
        $postId = isset($_POST['plan_id']) ? (int) $_POST['plan_id'] : null;

        $fields = [
            'header_text' => trim($_POST['header_text'] ?? ''),
            'free_text_1' => trim($_POST['free_text_1'] ?? ''),
            'free_text_2' => trim($_POST['free_text_2'] ?? ''),
        ];

        $days = [];
        $validationErrors = [];

        for ($d = 1; $d <= 7; $d++) {
            $isOpen = isset($_POST["open_{$d}"]);

            $openHour = $_POST["open_hour_{$d}"] ?? '';
            $openMinute = $_POST["open_minute_{$d}"] ?? '';
            $closeHour = $_POST["close_hour_{$d}"] ?? '';
            $closeMinute = $_POST["close_minute_{$d}"] ?? '';

            $openTime = ($openHour !== '' && $openMinute !== '') ? "{$openHour}:{$openMinute}" : '';
            $closeTime = ($closeHour !== '' && $closeMinute !== '') ? "{$closeHour}:{$closeMinute}" : '';

            if ($isOpen && ($openTime === '' || $closeTime === '')) {
                $validationErrors[] = "Please set both open and close time for {$dayNames[$d]}, or mark it as closed.";
            }

            $days[] = [
                'day_of_week' => $d,
                'closed' => !$isOpen,
                'open_time' => $isOpen ? $openTime : null,
                'close_time' => $isOpen ? $closeTime : null,
            ];
        }

        if ($validationErrors) {
            $error = implode(' ', $validationErrors);
        } elseif ($postMode === 'default' && $postId) {
            HoursPlan::savePlanFields($postId, $fields);
            HoursPlan::saveDays($postId, $days);
            $message = 'Default hours saved.';
        }

        $mode = $postMode;
    }
}

$plan = ($mode === 'default') ? HoursPlan::getDefault() : null;

$pageTitle = 'Opening Hours Admin';
require __DIR__ . '/../../app/Views/admin/_header.php';
require __DIR__ . '/../../app/Views/admin/hours.php';
require __DIR__ . '/../../app/Views/admin/_footer.php';