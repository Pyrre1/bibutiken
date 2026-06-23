<?php

require_once __DIR__ . '/../Core/Database.php';

class PreOrder
{
    /**
     * Allowed characters for the random order-number suffix.
     * Excludes visually ambiguous characters (0/O, 1/I/L) since the owner
     * may need to read these aloud or type them while searching.
     */
    private const ORDER_NUMBER_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /**
     * Builds an order number like "2606-7K4X9": YYMM- + 5 random safe chars.
     * Does not guarantee uniqueness by itself — insertOrder() retries on
     * collision, since a UNIQUE constraint already exists on the column.
     */
    private static function generateOrderNumber(): string
    {
        $prefix = date('ym') . '-';
        $suffix = '';
        $alphabetLength = strlen(self::ORDER_NUMBER_ALPHABET);
        for ($i = 0; $i < 5; $i++) {
            $suffix .= self::ORDER_NUMBER_ALPHABET[random_int(0, $alphabetLength - 1)];
        }
        return $prefix . $suffix;
    }

    public static function getActiveProducts(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, name FROM products WHERE active = 1 ORDER BY sort_order ASC');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function productExists(int $productId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id FROM products WHERE id = ? AND active = 1');
        $stmt->execute([$productId]);
        return (bool) $stmt->fetch();
    }

    /**
     * Inserts a pre-order, generating a unique order number.
     * Retries a small, bounded number of times on the rare chance of a
     * random collision with an existing order_number (UNIQUE constraint
     * on the column makes this safe to detect via the DB itself).
     *
     * @throws RuntimeException if it can't find a free order number after several tries.
     */
    public static function insertOrder(int $productId, int $quantity, string $customerEmail): array
    {
        $pdo = Database::getConnection();
        $maxAttempts = 5;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $orderNumber = self::generateOrderNumber();
            try {
                $stmt = $pdo->prepare(
                    'INSERT INTO pre_orders (order_number, product_id, quantity, customer_email) VALUES (?, ?, ?, ?)'
                );
                $stmt->execute([$orderNumber, $productId, $quantity, $customerEmail]);
                return [
                    'id' => (int) $pdo->lastInsertId(),
                    'order_number' => $orderNumber,
                ];
            } catch (PDOException $e) {
                // 23000 = integrity constraint violation (covers the UNIQUE order_number clash).
                // Anything else is a real error, not a collision, so it should bubble up.
                if ($e->getCode() !== '23000' || $attempt === $maxAttempts) {
                    throw $e;
                }
                // Otherwise: collision on this attempt, loop and try a fresh random number.
            }
        }

        // Unreachable in practice (loop above always returns or throws), but keeps static analysis happy.
        throw new RuntimeException('Could not generate a unique order number.');
    }
}