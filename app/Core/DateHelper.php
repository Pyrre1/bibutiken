<?php

class DateHelper
{
    public static function resolveWeekYear(DateTime $date): array
    {
        return [
            (int) $date->format('o'),
            (int) $date->format('W')
        ];
    }

    public static function getWeekDates(DateTime $date): array
    {
        $monday = clone $date;
        $monday->modify('monday this week');

        $dates = [];

        for ($i = 0; $i < 7; $i++) {
            $day = clone $monday;
            $day->modify("+{$i} days");

            $dates[$i + 1] = $day->format('d/m');
        }

        return $dates;
    }
}