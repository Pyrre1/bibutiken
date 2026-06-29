<?php
require_once __DIR__ . '/../../app/Core/init.php';
require_once __DIR__ . '/../../app/Models/HoursPlan.php';
require_once __DIR__ . '/../../app/Core/HoursResolver.php';
Auth::requireLogin();

HoursPlan::pruneExpiredWeekSpecific();

$dayNames = [1=>'Måndag',2=>'Tisdag',3=>'Onsdag',4=>'Torsdag',5=>'Fredag',6=>'Lördag',7=>'Söndag'];

$mode = $_GET['mode'] ?? 'view';
$editId = $_GET['id'] ?? null;
$message = null;
$error = null;
$plan = null;
$activePlan = null;
$skipReload = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ogiltig begäran, försök igen.';
    } else {
        $action = $_POST['action'] ?? 'save_plan';

        if ($action === 'activate_long') {
            $id = (int) ($_POST['id'] ?? 0);
            HoursPlan::setActiveLongTerm($id);
            $activated = HoursPlan::getById($id);
            $name = $activated['header_text'] !== '' ? $activated['header_text'] : 'periodplan';
            $message = "Periodplan \"{$name}\" aktiverad.";
            $mode = 'view';
        } elseif ($action === 'deactivate_long') {
            HoursPlan::deactivateAllLongTerm();
            $message = 'Periodplan deaktiverad; standardöppettider gäller nu (om inte en veckospecifik plan skriver över).';
            $mode = 'view';
        } elseif ($action === 'delete_long') {
            HoursPlan::deleteLongTerm((int) ($_POST['id'] ?? 0));
            $message = 'Periodplanen togs bort.';
            $mode = 'view';
        } elseif ($action === 'delete_week') {
            HoursPlan::deleteWeekSpecific((int) ($_POST['id'] ?? 0));
            $message = 'Veckoplanen togs bort.';
            $mode = 'view';
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
                    $validationErrors[] = "Ange både öppnings- och stängningstid för {$dayNames[$d]}, eller markera dagen som stängd.";
                }

                $days[] = [
                    'day_of_week' => $d,
                    'closed' => !$isOpen,
                    'open_time' => $isOpen ? $openTime : null,
                    'close_time' => $isOpen ? $closeTime : null,
                ];
            }

            // Hard block: completely empty plan
            $allDaysClosed = !array_filter($days, fn($d) => !$d['closed']);
            $hasText = $fields['header_text'] !== '' || $fields['free_text_1'] !== '' || $fields['free_text_2'] !== '';
            if ($allDaysClosed && !$hasText) {
                $validationErrors[] = 'Planen är helt tom — ange minst en öppningsdag eller fyll i ett textfält innan du sparar.';
            }

            $weekNumber = $postMode === 'week' ? (int) ($_POST['week_number'] ?? 0) : null;
            $weekYear = $postMode === 'week' ? (int) ($_POST['week_year'] ?? 0) : null;

            if ($postMode === 'week' && ($weekNumber < 1 || $weekNumber > 53 || $weekYear < 2000)) {
                $validationErrors[] = 'Ange ett giltigt veckonummer och år.';
            }

            if ($validationErrors) {
                $error = implode(' ', $validationErrors);
                $mode = $postMode;
                $typeMap = ['default' => 'default', 'long' => 'long_term', 'week' => 'week_specific'];
                $plan = [
                    'id' => ($postId !== '' && $postId !== 'new') ? (int) $postId : null,
                    'type' => $typeMap[$postMode] ?? 'default',
                    'header_text' => $fields['header_text'],
                    'free_text_1' => $fields['free_text_1'],
                    'free_text_2' => $fields['free_text_2'],
                    'is_active' => 0,
                    'week_number' => $weekNumber,
                    'year' => $weekYear,
                    'days' => $days,
                ];
                $skipReload = true;
            } elseif ($postMode === 'default') {
                HoursPlan::savePlanFields((int) $postId, $fields);
                HoursPlan::saveDays((int) $postId, $days);
                $message = 'Standardöppettiderna sparades.';
                $mode = 'view';
            } elseif ($postMode === 'long') {
                if ($postId === '' || $postId === 'new') {
                    $count = count(HoursPlan::getLongTermOptions());
                    if ($count >= 3) {
                        $error = 'Max antal periodplaner (3) är uppnått.';
                        $mode = 'view';
                    } else {
                        $newId = HoursPlan::createLongTermOption($fields, $count);
                        HoursPlan::saveDays($newId, $days);
                        $message = 'Periodplanen skapades.';
                        $mode = 'view';
                    }
                } else {
                    HoursPlan::savePlanFields((int) $postId, $fields);
                    HoursPlan::saveDays((int) $postId, $days);
                    $message = 'Periodplanen sparades.';
                    $mode = 'view';
                }
            } elseif ($postMode === 'week') {
                if ($postId === '' || $postId === 'new') {
                    try {
                        $newId = HoursPlan::createWeekSpecific($weekNumber, $weekYear, $fields);
                        HoursPlan::saveDays($newId, $days);
                        $message = "Veckoplan för vecka {$weekNumber}, {$weekYear} skapades.";
                        $mode = 'view';
                    } catch (PDOException $e) {
                        if ($e->getCode() === '23000') {
                            $error = "En veckoplan för vecka {$weekNumber}, {$weekYear} finns redan.";
                            $mode = 'week';
                            $plan = HoursPlan::blankPlan('week_specific');
                            $plan['header_text'] = $fields['header_text'];
                            $plan['free_text_1'] = $fields['free_text_1'];
                            $plan['free_text_2'] = $fields['free_text_2'];
                            $plan['week_number'] = $weekNumber;
                            $plan['year'] = $weekYear;
                            $plan['days'] = $days;
                            $skipReload = true;
                        } else {
                            throw $e;
                        }
                    }
                } else {
                    HoursPlan::savePlanFields((int) $postId, $fields);
                    HoursPlan::saveWeekSpecificMeta((int) $postId, $weekNumber, $weekYear);
                    HoursPlan::saveDays((int) $postId, $days);
                    $message = 'Veckoplanen sparades.';
                    $mode = 'view';
                }
            }
        }
    }
}

if (!$skipReload) {
    if ($mode === 'view') {
        $activePlan = HoursResolver::resolveForWeek((int) date('W'), (int) date('Y'));
    } elseif ($mode === 'default') {
        $plan = HoursPlan::getDefault();
    } elseif ($mode === 'long') {
        if ($editId === 'new') {
            $existingCount = count(HoursPlan::getLongTermOptions());
            if ($existingCount >= 3) {
                $error = $error ?? 'Max antal periodplaner (3) är uppnått. Ta bort en innan du lägger till en ny.';
                $mode = 'view';
                $activePlan = HoursResolver::resolveForWeek((int) date('W'), (int) date('Y'));
            } else {
                $plan = HoursPlan::blankPlan('long_term');
            }
        } elseif ($editId) {
            $plan = HoursPlan::getById((int) $editId);
        }
    } elseif ($mode === 'week') {
        if ($editId === 'new') {
            $plan = HoursPlan::blankPlan('week_specific');
            $plan['week_number'] = (int) date('W');
            $plan['year'] = (int) date('Y');
        } elseif ($editId) {
            $plan = HoursPlan::getById((int) $editId);
        }
    }
}

$longTermOptions = HoursPlan::getLongTermOptions();
$weekSpecificPlans = HoursPlan::getAllWeekSpecific();

$pageTitle = 'Öppettider – Admin';
require __DIR__ . '/../../app/Views/admin/_header.php';
require __DIR__ . '/../../app/Views/admin/hours.php';
require __DIR__ . '/../../app/Views/admin/_footer.php';