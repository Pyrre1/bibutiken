<?php

require_once __DIR__ . '/../Core/Database.php';

class Banner
{
    public static function getActiveBanners(): array
    {
        $pdo  = Database::getConnection();
        $stmt = $pdo->query(
            'SELECT id, message, type, sort_order
            FROM site_banners
            WHERE active = 1
            ORDER BY sort_order ASC, created_at ASC'
        );
        return $stmt->fetchAll();
    }

    public static function getAllBanners(): array
    {
        $pdo  = Database::getConnection();
        $stmt = $pdo->query(
            'SELECT id, message, type, active, sort_order, created_at
            FROM site_banners
            ORDER BY active DESC, sort_order ASC, created_at ASC'
        );
        return $stmt->fetchAll();
    }

    public static function create(string $message, string $type): void
    {
        $pdo = Database::getConnection();
        $max = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM site_banners')->fetchColumn();
        $pdo->prepare(
            'INSERT INTO site_banners (message, type, sort_order) VALUES (?, ?, ?)'
        )->execute([$message, $type, $max + 1]);
    }

    public static function setActive(int $id, bool $active): void
    {
        $pdo = Database::getConnection();
        $pdo->prepare(
            'UPDATE site_banners SET active = ? WHERE id = ?'
        )->execute([(int) $active, $id]);
    }

    public static function delete(int $id): void
    {
        $pdo = Database::getConnection();
        $pdo->prepare('DELETE FROM site_banners WHERE id = ?')->execute([$id]);
    }

    public static function getById(int $id): ?array
    {
        $pdo  = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, message, type, active, sort_order FROM site_banners WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function update(int $id, string $message, string $type): void
    {
        $pdo = Database::getConnection();
        $pdo->prepare('UPDATE site_banners SET message = ?, type = ? WHERE id = ?')
            ->execute([$message, $type, $id]);
    }

    public static function move(int $id, string $direction): void
    {
        $pdo  = Database::getConnection();
        $row  = $pdo->prepare('SELECT sort_order FROM site_banners WHERE id = ?');
        $row->execute([$id]);
        $current = (int) $row->fetchColumn();

        if ($direction === 'up') {
            $swap = $pdo->prepare(
                'SELECT id, sort_order FROM site_banners
                  WHERE sort_order < ? ORDER BY sort_order DESC LIMIT 1'
            );
        } else {
            $swap = $pdo->prepare(
                'SELECT id, sort_order FROM site_banners
                  WHERE sort_order > ? ORDER BY sort_order ASC LIMIT 1'
            );
        }
        $swap->execute([$current]);
        $other = $swap->fetch();
        if (!$other) return;

        $pdo->prepare('UPDATE site_banners SET sort_order = ? WHERE id = ?')
            ->execute([$other['sort_order'], $id]);
        $pdo->prepare('UPDATE site_banners SET sort_order = ? WHERE id = ?')
            ->execute([$current, $other['id']]);
    }
}