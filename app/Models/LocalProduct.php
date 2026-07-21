<?php

require_once __DIR__ . '/../Core/Database.php';

class LocalProduct
{
    // ── Types ────────────────────────────────────────────────

    public static function getAllTypes(): array
    {
        $pdo = Database::getConnection();
        return $pdo->query(
            'SELECT id, name, sort_order
            FROM local_product_types
            ORDER BY sort_order ASC'
        )->fetchAll();
    }

    // ── Products (admin) ─────────────────────────────────────

    public static function getAllAdmin(): array
    {
        $pdo = Database::getConnection();
        return $pdo->query(
            'SELECT lp.id, lp.type_id, lpt.name AS type_name,
                    lp.size, lp.name, lp.description,
                    lp.price_ore, lp.active, lp.sort_order
            FROM local_products lp
            JOIN local_product_types lpt ON lpt.id = lp.type_id
            ORDER BY lpt.sort_order ASC, lp.sort_order ASC'
        )->fetchAll();
    }

    // ── Products (public) ────────────────────────────────────

    public static function getActiveGroupedByType(): array
    {
        $pdo  = Database::getConnection();
        $rows = $pdo->query(
            'SELECT lp.id, lp.type_id, lpt.name AS type_name,
                    lp.size, lp.name, lp.description, lp.price_ore
            FROM local_products lp
            JOIN local_product_types lpt ON lpt.id = lp.type_id
            WHERE lp.active = 1
            ORDER BY lpt.sort_order ASC, lp.sort_order ASC'
        )->fetchAll();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['type_name']][] = $row;
        }
        return $grouped;
    }

    // ── CRUD ─────────────────────────────────────────────────

    public static function create(
        int $typeId, string $size, string $name,
        ?string $description, int $priceOre
    ): int {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT COALESCE(MAX(sort_order), 0) + 1
            FROM local_products WHERE type_id = ?'
        );
        $stmt->execute([$typeId]);
        $nextSort = (int) $stmt->fetchColumn();

        $pdo->prepare(
            'INSERT INTO local_products
                (type_id, size, name, description, price_ore, active, sort_order)
            VALUES (?, ?, ?, ?, ?, 1, ?)'
        )->execute([$typeId, $size, $name, $description ?: null, $priceOre, $nextSort]);

        return (int) $pdo->lastInsertId();
    }

    public static function update(
        int $id, int $typeId, string $size, string $name,
        ?string $description, int $priceOre
    ): void {
        Database::getConnection()->prepare(
            'UPDATE local_products
            SET type_id = ?, size = ?, name = ?, description = ?, price_ore = ?
            WHERE id = ?'
        )->execute([$typeId, $size, $name, $description ?: null, $priceOre, $id]);
    }

    public static function setActive(int $id, bool $active): void
    {
        Database::getConnection()->prepare(
            'UPDATE local_products SET active = ? WHERE id = ?'
        )->execute([(int) $active, $id]);
    }

    public static function delete(int $id): void
    {
        Database::getConnection()->prepare(
            'DELETE FROM local_products WHERE id = ?'
        )->execute([$id]);
    }

    public static function updateSortOrder(array $orderedIds): void
    {
        $pdo  = Database::getConnection();
        $stmt = $pdo->prepare(
            'UPDATE local_products SET sort_order = ? WHERE id = ?'
        );
        foreach ($orderedIds as $position => $id) {
            $stmt->execute([$position + 1, (int) $id]);
        }
    }
}