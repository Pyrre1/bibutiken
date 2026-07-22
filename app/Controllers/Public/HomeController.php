<?php

class HomeController
{
    public static function index(): void
    {
        require_once __DIR__ . '/../../Models/HoursPlan.php';
        require_once __DIR__ . '/../../Core/HoursResolver.php';
        require_once __DIR__ . '/../../Core/DateHelper.php';

        $dayNames = [
            1 => 'Måndag',
            2 => 'Tisdag',
            3 => 'Onsdag',
            4 => 'Torsdag',
            5 => 'Fredag',
            6 => 'Lördag',
            7 => 'Söndag'
        ];

        $today = new DateTime();

        [$thisYear, $thisWeek] = DateHelper::resolveWeekYear($today);
        [$nextYear, $nextWeek] = DateHelper::resolveWeekYear((clone $today)->modify('+7 days'));

        $thisWeekDates = DateHelper::getWeekDates($today);
        $nextWeekDates = DateHelper::getWeekDates((clone $today)->modify('+7 days'));

        $thisWeekPlan = HoursResolver::resolveForWeek($thisWeek, $thisYear);
        $nextWeekPlan = HoursResolver::resolveForWeek($nextWeek, $nextYear);

        $pageTitle = 'Bibutiken';
        $activePage = 'home';
        $extraStyles = ['/assets/css/home.css'];

        require __DIR__ . '/../../Views/public/_header.php';
        require __DIR__ . '/../../Views/public/home.php';
        require __DIR__ . '/../../Views/public/_footer.php';
    }
}
