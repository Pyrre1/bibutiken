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
        $stmt = $pdo->prepare('SELECT id, name, price_ore, needs_manual_work FROM products WHERE active = 1 ORDER BY sort_order ASC');
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
                $hasManualWork = (int) !empty(array_filter($items, fn($i) => !empty($i['needs_manual_work'])));
                $stmt = $pdo->prepare(
                    'INSERT INTO pre_orders (order_number, customer_name, customer_email, has_manual_work) VALUES (?, ?, ?, ?)'
                );
                $stmt->execute([$orderNumber, $customerName, $customerEmail, $hasManualWork]);
                $orderId = (int) $pdo->lastInsertId();
                $itemStmt = $pdo->prepare(
                    'INSERT INTO pre_order_items (pre_order_id, product_id, quantity, unit_price_ore, needs_manual_work, manual_work_status)
                    VALUES (?, ?, ?, ?, ?, ?)'
                );
                foreach ($items as $item) {
                    $itemStmt->execute([
                        $orderId,
                        $item['product_id'],
                        $item['quantity'],
                        $item['unit_price_ore'],
                        $item['needs_manual_work'] ?? 0,
                        !empty($item['needs_manual_work']) ? 'ej_behandlad' : 'ej_tillämplig',
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
    public static function getAllOrders(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query(
            'SELECT o.id, o.order_number, o.customer_name, o.customer_email,
                    o.created_at, o.is_delivered, o.has_manual_work
            FROM pre_orders o
            ORDER BY o.created_at DESC'
        );
        return $stmt->fetchAll();
    }

    public static function getOrderWithItems(int $orderId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT o.id, o.order_number, o.customer_name, o.customer_email,
                    o.created_at, o.is_delivered, o.has_manual_work
            FROM pre_orders o WHERE o.id = ?'
        );
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        if (!$order) return null;

        $stmt2 = $pdo->prepare(
            'SELECT i.id, i.product_id, p.name AS product_name,
                    i.quantity, i.unit_price_ore, i.actual_price_ore,
                    i.needs_manual_work, i.manual_work_status
            FROM pre_order_items i
            JOIN products p ON p.id = i.product_id
            WHERE i.pre_order_id = ?
            ORDER BY p.sort_order'
        );
        $stmt2->execute([$orderId]);
        $order['items'] = $stmt2->fetchAll();
        return $order;
    }

    public static function setDelivered(int $orderId, bool $delivered): void
    {
        $pdo = Database::getConnection();
        $pdo->prepare('UPDATE pre_orders SET is_delivered = ? WHERE id = ?')
            ->execute([(int)$delivered, $orderId]);
    }

    public static function setManualWorkStatus(int $itemId, string $status): void
    {
        if (!in_array($status, ['ej_tillämplig', 'ej_behandlad', 'fardig'])) return;
        $pdo = Database::getConnection();

        $pdo->prepare('UPDATE pre_order_items SET manual_work_status = ? WHERE id = ?')
            ->execute([$status, $itemId]);

        $row = $pdo->prepare('SELECT pre_order_id FROM pre_order_items WHERE id = ?');
        $row->execute([$itemId]);
        $orderId = (int) $row->fetchColumn();

        $count = $pdo->prepare(
            'SELECT COUNT(*) FROM pre_order_items
            WHERE pre_order_id = ? AND needs_manual_work = 1 AND manual_work_status = "ej_behandlad"'
        );
        $count->execute([$orderId]);
        $pending = (int) $count->fetchColumn();

        $pdo->prepare('UPDATE pre_orders SET has_manual_work = ? WHERE id = ?')
            ->execute([$pending > 0 ? 1 : 0, $orderId]);
    }

    public static function updateProductPrice(int $productId, int $priceOre): void
    {
        $pdo = Database::getConnection();
        $pdo->prepare('UPDATE products SET price_ore = ? WHERE id = ?')
            ->execute([$priceOre, $productId]);
    }

    public static function deleteOrder(int $orderId): void
    {
        $pdo = Database::getConnection();
        // pre_order_items cascade deletes via FK
        $pdo->prepare('DELETE FROM pre_orders WHERE id = ?')->execute([$orderId]);
    }

    public static function anonymizeEmail(string $email): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'UPDATE pre_orders SET customer_email = "Raderad på begäran" WHERE customer_email = ?'
        );
        $stmt->execute([$email]);
        return $stmt->rowCount();
    }

    public static function getOrderSummaryByProduct(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query(
            'SELECT p.name, SUM(i.quantity) AS total_qty
            FROM pre_order_items i
            JOIN products p ON p.id = i.product_id
            GROUP BY p.id, p.name
            ORDER BY p.sort_order'
        );
        return $stmt->fetchAll();
    }

    public static function getOrderStats(): array
    {
        $pdo = Database::getConnection();
        $total = $pdo->query('SELECT COUNT(*) FROM pre_orders')->fetchColumn();
        $delivered = $pdo->query('SELECT COUNT(*) FROM pre_orders WHERE is_delivered = 1')->fetchColumn();
        $manualPending = $pdo->query(
            'SELECT COUNT(*) FROM pre_orders WHERE has_manual_work = 1 AND is_delivered = 0'
        )->fetchColumn();
        return [
            'total_orders' => (int)$total,
            'delivered' => (int)$delivered,
            'manual_pending' => (int)$manualPending,
        ];
    }

    public static function updateOrderItem(int $itemId, int $productId, int $quantity): void
    {
        $pdo = Database::getConnection();
        
        // Get new product details
        $prod = $pdo->prepare('SELECT price_ore, needs_manual_work FROM products WHERE id = ?');
        $prod->execute([$productId]);
        $product = $prod->fetch();
        if (!$product) return;

        $pdo->prepare(
            'UPDATE pre_order_items 
            SET product_id = ?, quantity = ?, unit_price_ore = ?, needs_manual_work = ?
            WHERE id = ?'
        )->execute([
            $productId,
            $quantity,
            $product['price_ore'],
            $product['needs_manual_work'],
            $itemId,
        ]);

        // Recalculate has_manual_work on parent order
        $row = $pdo->prepare('SELECT pre_order_id FROM pre_order_items WHERE id = ?');
        $row->execute([$itemId]);
        $orderId = (int) $row->fetchColumn();

        $count = $pdo->prepare(
            'SELECT COUNT(*) FROM pre_order_items
            WHERE pre_order_id = ? AND needs_manual_work = 1 AND manual_work_status = "ej_behandlad"'
        );
        $count->execute([$orderId]);
        $pending = (int) $count->fetchColumn();

        $pdo->prepare('UPDATE pre_orders SET has_manual_work = ? WHERE id = ?')
            ->execute([$pending > 0 ? 1 : 0, $orderId]);
    }

    public static function deleteOrderItem(int $itemId): void
    {
        $pdo = Database::getConnection();
        
        $row = $pdo->prepare('SELECT pre_order_id FROM pre_order_items WHERE id = ?');
        $row->execute([$itemId]);
        $orderId = (int) $row->fetchColumn();

        $pdo->prepare('DELETE FROM pre_order_items WHERE id = ?')->execute([$itemId]);

        // Recalculate has_manual_work
        $count = $pdo->prepare(
            'SELECT COUNT(*) FROM pre_order_items
            WHERE pre_order_id = ? AND needs_manual_work = 1 AND manual_work_status = "ej_behandlad"'
        );
        $count->execute([$orderId]);
        $pending = (int) $count->fetchColumn();

        $pdo->prepare('UPDATE pre_orders SET has_manual_work = ? WHERE id = ?')
            ->execute([$pending > 0 ? 1 : 0, $orderId]);
    }

    public static function getAllProducts(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT id, name FROM products WHERE active = 1 ORDER BY sort_order ASC');
        return $stmt->fetchAll();
    }

    public static function addOrderItem(int $orderId, int $productId, int $quantity): void
    {
        $pdo = Database::getConnection();

        $prod = $pdo->prepare('SELECT price_ore, needs_manual_work FROM products WHERE id = ?');
        $prod->execute([$productId]);
        $product = $prod->fetch();
        if (!$product) return;

        $pdo->prepare(
            'INSERT INTO pre_order_items (pre_order_id, product_id, quantity, unit_price_ore, needs_manual_work, manual_work_status)
            VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([
            $orderId, 
            $productId, 
            $quantity, 
            $product['price_ore'], 
            $product['needs_manual_work'],
            $product['needs_manual_work'] ? 'ej_behandlad' : 'ej_tillämplig',
        ]);

        // Recalculate has_manual_work
        $count = $pdo->prepare(
            'SELECT COUNT(*) FROM pre_order_items
            WHERE pre_order_id = ? AND needs_manual_work = 1 AND manual_work_status = "ej_behandlad"'
        );
        $count->execute([$orderId]);
        $pdo->prepare('UPDATE pre_orders SET has_manual_work = ? WHERE id = ?')
            ->execute([(int)$count->fetchColumn() > 0 ? 1 : 0, $orderId]);
    }
}
