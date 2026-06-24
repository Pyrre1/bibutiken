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
        $stmt = $pdo->prepare('SELECT id, name, price_ore FROM products WHERE active = 1 ORDER BY sort_order ASC');
        $stmt->execute();
        return $stmt->fetchAll();
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

    /**
     * Inserts a full cart order: one pre_orders row plus one pre_order_items
     * row per cart line. Prices are copied onto the line item at order time
     * (not looked up fresh later) so historical orders stay accurate even if
     * product prices change afterward.
     *
     * @param array $items List of ['product_id' => int, 'quantity' => int, 'unit_price_ore' => int]
     * @throws RuntimeException if it can't find a free order number after several tries.
     */
    public static function insertOrder(string $customerName, string $customerEmail, array $items): array
    {
        $pdo = Database::getConnection();
        $maxAttempts = 5;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $orderNumber = self::generateOrderNumber();
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare(
                    'INSERT INTO pre_orders (order_number, customer_name, customer_email) VALUES (?, ?, ?)'
                );
                $stmt->execute([$orderNumber, $customerName, $customerEmail]);
                $orderId = (int) $pdo->lastInsertId();

                $itemStmt = $pdo->prepare(
                    'INSERT INTO pre_order_items (pre_order_id, product_id, quantity, unit_price_ore) VALUES (?, ?, ?, ?)'
                );
                foreach ($items as $item) {
                    $itemStmt->execute([
                        $orderId,
                        $item['product_id'],
                        $item['quantity'],
                        $item['unit_price_ore'],
                    ]);
                }

                $pdo->commit();
                return [
                    'id' => $orderId,
                    'order_number' => $orderNumber,
                ];
            } catch (PDOException $e) {
                $pdo->rollBack();
                // Product IDs are validated by the caller before this is reached,
                // so any 23000 here is the UNIQUE order_number constraint —
                // safe to retry with a freshly generated number.
                if ($e->getCode() !== '23000' || $attempt === $maxAttempts) {
                    throw $e;
                }
            }
        }

        throw new RuntimeException('Could not generate a unique order number.');
    }
}