<?php

require_once __DIR__ . '/../Core/Database.php';

class Product
{
    public static function getActiveProducts(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT id, name, price_ore, needs_manual_work
            FROM products
            WHERE active = 1 AND deprecated = 0
            ORDER BY sort_order ASC'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getAllProductsAdmin(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query(
            'SELECT id, name, active, sort_order, price_ore, needs_manual_work, deprecated
            FROM products
            WHERE deprecated = 0
            ORDER BY sort_order ASC'
        );
        return $stmt->fetchAll();
    }

    public static function productHasOrders(int $productId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM pre_order_items WHERE product_id = ?');
        $stmt->execute([$productId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function createProduct(string $name, int $priceOre, bool $needsManualWork): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM products WHERE deprecated = 0');
        $nextSort = (int) $stmt->fetchColumn();

        $pdo->prepare(
            'INSERT INTO products (name, price_ore, needs_manual_work, active, sort_order, deprecated)
            VALUES (?, ?, ?, 1, ?, 0)'
        )->execute([$name, $priceOre, (int) $needsManualWork, $nextSort]);

        return (int) $pdo->lastInsertId();
    }

    public static function updateProduct(int $productId, string $name, int $priceOre, bool $needsManualWork): void
    {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare('SELECT name, sort_order FROM products WHERE id = ?');
        $stmt->execute([$productId]);
        $current = $stmt->fetch();

        if ($current['name'] !== $name && self::productHasOrders($productId)) {
            $pdo->prepare('UPDATE products SET deprecated = 1, active = 0 WHERE id = ?')
                ->execute([$productId]);
            $pdo->prepare(
                'INSERT INTO products (name, price_ore, needs_manual_work, active, sort_order, deprecated)
                VALUES (?, ?, ?, 1, ?, 0)'
            )->execute([$name, $priceOre, (int) $needsManualWork, $current['sort_order']]);
        } else {
            $pdo->prepare(
                'UPDATE products SET name = ?, price_ore = ?, needs_manual_work = ? WHERE id = ?'
            )->execute([$name, $priceOre, (int) $needsManualWork, $productId]);
        }
    }

    public static function setProductActive(int $productId, bool $active): void
    {
        $pdo = Database::getConnection();
        $pdo->prepare('UPDATE products SET active = ? WHERE id = ?')
            ->execute([(int) $active, $productId]);
    }

    public static function deleteOrDeprecateProduct(int $productId): string
    {
        $pdo = Database::getConnection();
        if (self::productHasOrders($productId)) {
            $pdo->prepare('UPDATE products SET deprecated = 1, active = 0 WHERE id = ?')
                ->execute([$productId]);
            return 'deprecated';
        }
        $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$productId]);
        return 'deleted';
    }

    public static function updateProductSortOrder(array $orderedIds): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE products SET sort_order = ? WHERE id = ?');
        foreach ($orderedIds as $position => $productId) {
            $stmt->execute([$position + 1, (int) $productId]);
        }
    }

    /**
     * Fetches active products as id => row, for quick lookup/validation
     * when processing a submitted cart (avoids one query per line item).
     */
    public static function getActiveProductsById(): array
    {
        $byId = [];
        foreach (self::getActiveProducts() as $product) {
            $byId[(int) $product['id']] = $product;
        }
        return $byId;
    }

    public static function getAllProducts(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query(
            'SELECT id, name, needs_manual_work
            FROM products
            WHERE active = 1 AND deprecated = 0
            ORDER BY sort_order ASC'
        );
        return $stmt->fetchAll();
    }

    public static function updateProductPrice(int $productId, int $priceOre): void
    {
        $pdo = Database::getConnection();
        $pdo->prepare('UPDATE products SET price_ore = ? WHERE id = ?')
            ->execute([$priceOre, $productId]);
    }
}