<?php

require_once __DIR__ . '/../Core/Database.php';

class HoursPlan
{
    public static function getDefault(): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM hours_plans WHERE type = 'default' LIMIT 1");
        $stmt->execute();
        $plan = $stmt->fetch();
        return $plan ? self::attachDays($plan) : null;
    }

    public static function getLongTermOptions(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM hours_plans WHERE type = 'long_term' ORDER BY sort_order ASC");
        $stmt->execute();
        return array_map([self::class, 'attachDays'], $stmt->fetchAll());
    }

    public static function getActiveLongTerm(): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM hours_plans WHERE type = 'long_term' AND is_active = 1 LIMIT 1");
        $stmt->execute();
        $plan = $stmt->fetch();
        return $plan ? self::attachDays($plan) : null;
    }

    public static function getWeekSpecific(int $week, int $year): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM hours_plans WHERE type = 'week_specific' AND week_number = ? AND year = ? LIMIT 1");
        $stmt->execute([$week, $year]);
        $plan = $stmt->fetch();
        return $plan ? self::attachDays($plan) : null;
    }

    public static function getAllWeekSpecific(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM hours_plans WHERE type = 'week_specific' ORDER BY year ASC, week_number ASC");
        $stmt->execute();
        return array_map([self::class, 'attachDays'], $stmt->fetchAll());
    }

    public static function getById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM hours_plans WHERE id = ?');
        $stmt->execute([$id]);
        $plan = $stmt->fetch();
        return $plan ? self::attachDays($plan) : null;
    }

    public static function blankPlan(string $type): array
    {
        $days = [];
        for ($d = 1; $d <= 7; $d++) {
            $days[] = ['day_of_week' => $d, 'open_time' => null, 'close_time' => null, 'closed' => 1];
        }

        return [
            'id' => null,
            'type' => $type,
            'header_text' => '',
            'free_text_1' => '',
            'free_text_2' => '',
            'is_active' => 0,
            'week_number' => null,
            'year' => null,
            'days' => $days,
        ];
    }

    private static function attachDays(array $plan): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM hours_plan_days WHERE plan_id = ? ORDER BY day_of_week ASC');
        $stmt->execute([$plan['id']]);
        $existing = $stmt->fetchAll();

        $byDay = [];
        foreach ($existing as $row) {
            $byDay[$row['day_of_week']] = $row;
        }

        $days = [];
        for ($d = 1; $d <= 7; $d++) {
            $days[] = $byDay[$d] ?? ['day_of_week' => $d, 'open_time' => null, 'close_time' => null, 'closed' => 1];
        }

        $plan['days'] = $days;
        return $plan;
    }

    public static function savePlanFields(int $id, array $fields): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE hours_plans SET header_text = ?, free_text_1 = ?, free_text_2 = ? WHERE id = ?');
        $stmt->execute([$fields['header_text'] ?? null, $fields['free_text_1'] ?? null, $fields['free_text_2'] ?? null, $id]);
    }

    public static function saveWeekSpecificMeta(int $id, int $week, int $year): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE hours_plans SET week_number = ?, year = ? WHERE id = ?');
        $stmt->execute([$week, $year, $id]);
    }

    public static function saveDays(int $planId, array $days): void
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM hours_plan_days WHERE plan_id = ?')->execute([$planId]);
            $insert = $pdo->prepare(
                'INSERT INTO hours_plan_days (plan_id, day_of_week, open_time, close_time, closed) VALUES (?, ?, ?, ?, ?)'
            );
            foreach ($days as $day) {
                $insert->execute([
                    $planId,
                    $day['day_of_week'],
                    $day['closed'] ? null : $day['open_time'],
                    $day['closed'] ? null : $day['close_time'],
                    $day['closed'] ? 1 : 0,
                ]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function setActiveLongTerm(int $planId): void
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $pdo->prepare("UPDATE hours_plans SET is_active = 0 WHERE type = 'long_term'")->execute();
            $pdo->prepare("UPDATE hours_plans SET is_active = 1 WHERE id = ? AND type = 'long_term'")->execute([$planId]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function deactivateAllLongTerm(): void
    {
        Database::getConnection()->prepare("UPDATE hours_plans SET is_active = 0 WHERE type = 'long_term'")->execute();
    }

    public static function createWeekSpecific(int $week, int $year, array $fields): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "INSERT INTO hours_plans (type, header_text, free_text_1, free_text_2, week_number, year) VALUES ('week_specific', ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$fields['header_text'] ?? null, $fields['free_text_1'] ?? null, $fields['free_text_2'] ?? null, $week, $year]);
        return (int) $pdo->lastInsertId();
    }

    public static function createLongTermOption(array $fields, int $sortOrder): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "INSERT INTO hours_plans (type, header_text, free_text_1, free_text_2, sort_order) VALUES ('long_term', ?, ?, ?, ?)"
        );
        $stmt->execute([$fields['header_text'] ?? null, $fields['free_text_1'] ?? null, $fields['free_text_2'] ?? null, $sortOrder]);
        return (int) $pdo->lastInsertId();
    }

    public static function deleteWeekSpecific(int $id): void
    {
        Database::getConnection()->prepare("DELETE FROM hours_plans WHERE id = ? AND type = 'week_specific'")->execute([$id]);
    }

    public static function deleteLongTerm(int $id): void
    {
        Database::getConnection()->prepare("DELETE FROM hours_plans WHERE id = ? AND type = 'long_term'")->execute([$id]);
    }

    public static function pruneExpiredWeekSpecific(): void
    {
        $currentWeek = (int) date('W');
        $currentYear = (int) date('Y');

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "DELETE FROM hours_plans
              WHERE type = 'week_specific'
                AND (year < ? OR (year = ? AND week_number < ?))"
        );
        $stmt->execute([$currentYear, $currentYear, $currentWeek]);
    }
}