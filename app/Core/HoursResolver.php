<?php

require_once __DIR__ . '/../Models/HoursPlan.php';

class HoursResolver
{
    /**
     * Priority: week-specific > active long-term > default.
     */
    public static function resolveForWeek(int $week, int $year): array
    {
        $weekSpecific = HoursPlan::getWeekSpecific($week, $year);
        if ($weekSpecific) {
            return $weekSpecific;
        }

        $longTerm = HoursPlan::getActiveLongTerm();
        if ($longTerm) {
            return $longTerm;
        }

        $default = HoursPlan::getDefault();
        if (!$default) {
            throw new RuntimeException('No default hours plan found — one must always exist.');
        }

        return $default;
    }
}