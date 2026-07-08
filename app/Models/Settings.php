<?php

class Settings
{
    public static function get(string $key, string $default = ''): string
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT value FROM settings WHERE `key` = ?');
        $stmt->execute([$key]);
        $result = $stmt->fetchColumn();
        return $result !== false ? $result : $default;
    }

    public static function set(string $key, string $value): void
    {
        $pdo = Database::getConnection();
        $pdo->prepare('INSERT INTO settings (`key`, value) VALUES (?, ?)
                      ON DUPLICATE KEY UPDATE value = VALUES(value)')
            ->execute([$key, $value]);
    }
}