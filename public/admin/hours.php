<?php
require_once __DIR__ . '/../../app/Core/init.php';
require_once __DIR__ . '/../../app/Models/HoursPlan.php';
Auth::requireLogin();

$dayNames = [1=>'Måndag',2=>'Tisdag',3=>'Onsdag',4=>'Torsdag',5=>'Fredag',6=>'Lördag',7=>'Söndag'];

$mode = $_GET['mode'] ?? 'default';
$editId = $_GET['id'] ?? null;
$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid request, please try again.';
    } else {
        $action = $_POST['action'] ?? 'save_plan';

        if ($action === 'activate_long') {
            HoursPlan::setActiveLongTerm((int) ($_POST['id'] ?? 0));
            $message = 'Periodplan aktiverad.';
            $mode = 'default';
        } elseif ($action === 'deactivate_long') {
            HoursPlan::deactivateAllLongTerm();
            $message = 'Periodplan deaktiverad; standardöppettider gäller nu (om inte en veckospecifik plan skriver över).';
            $mode = 'default';
        } elseif ($action === 'delete_long') {
            HoursPlan::deleteLongTerm((int) ($_POST['id'] ?? 0));
            $message = 'Periodplan raderad.';
            $mode = 'default';
        } elseif ($action === 'save_plan') {
            $postMode = $_POST['mode'] ?? 'default';
            $postId = trim($_POST['plan_id'] ?? '');

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
                    $validationErrors[] = "Glöm inte att sätta både öppet och stängt för {$dayNames[$d]}, eller markera det som stängt.";
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
                $mode = $postMode;
                $editId = $postId;
            } elseif ($postMode === 'default') {
                HoursPlan::savePlanFields((int) $postId, $fields);
                HoursPlan::saveDays((int) $postId, $days);
                $message = 'Standardöppettider sparade.';
                $mode = 'default';
            } elseif ($postMode === 'long') {
                if ($postId === '' || $postId === 'new') {
                    $count = count(HoursPlan::getLongTermOptions());
                    if ($count >= 3) {
                        $error = 'Max 3st periodplaner åt gången. Radera en innan du lägger till en ny.';
                        $mode = 'default';
                    } else {
                        $newId = HoursPlan::createLongTermOption($fields, $count);
                        HoursPlan::saveDays($newId, $days);
                        $message = 'Periodplan skapad.';
                        $mode = 'default';
                    }
                } else {
                    HoursPlan::savePlanFields((int) $postId, $fields);
                    HoursPlan::saveDays((int) $postId, $days);
                    $message = 'Periodplan sparad.';
                    $mode = 'default';
                }
            }
        }
    }
}

if ($mode === 'default') {
    $plan = HoursPlan::getDefault();
} elseif ($mode === 'long') {
    if ($editId === 'new') {
        $existingCount = count(HoursPlan::getLongTermOptions());
        if ($existingCount >= 3) {
            $error = $error ?? 'Max 3st periodplaner åt gången. Radera en innan du lägger till en ny.';
            $plan = null;
        } else {
            $plan = HoursPlan::blankPlan('long_term');
        }
    } elseif ($editId) {
        $plan = HoursPlan::getById((int) $editId);
    } else {
        $plan = null;
    }
} else {
    $plan = null;
}

$longTermOptions = HoursPlan::getLongTermOptions();

$pageTitle = 'Opening Hours Admin';
require __DIR__ . '/../../app/Views/admin/_header.php';
require __DIR__ . '/../../app/Views/admin/hours.php';
require __DIR__ . '/../../app/Views/admin/_footer.php';