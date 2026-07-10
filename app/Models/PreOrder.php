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

    // ── Orders ────────────────────────────────────────────────

    public static function insertOrder(string $customerName, string $customerEmail, array $items): array
    {
        $pdo = Database::getConnection();
        require_once __DIR__ . '/Customer.php';
        $customerId = Customer::findOrCreateCustomer($customerName, $customerEmail);
        $maxAttempts = 5;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $orderNumber = self::generateOrderNumber();
            $pdo->beginTransaction();
            try {
                $hasManualWork = (int) !empty(array_filter($items, fn($i) => !empty($i['needs_manual_work'])));

                $stmt = $pdo->prepare(
                    'INSERT INTO pre_orders (order_number, customer_id, has_manual_work) VALUES (?, ?, ?)'
                );
                $stmt->execute([$orderNumber, $customerId, $hasManualWork]);
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
                return ['id' => $orderId, 'order_number' => $orderNumber];

            } catch (PDOException $e) {
                $pdo->rollBack();
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
            'SELECT o.id, o.order_number, c.name AS customer_name, c.email AS customer_email,
                    o.created_at, o.is_delivered, o.has_manual_work,
                    EXISTS (
                        SELECT 1 FROM pre_order_items i
                        WHERE i.pre_order_id = o.id AND i.needs_manual_work = 1
                    ) AS has_any_manual_item
            FROM pre_orders o
            JOIN customers c ON c.id = o.customer_id
            ORDER BY o.is_delivered ASC, o.created_at ASC'
        );
        return $stmt->fetchAll();
    }

    public static function getOrderWithItems(int $orderId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT o.id, o.order_number, c.name AS customer_name, c.email AS customer_email,
                    o.created_at, o.is_delivered, o.has_manual_work
            FROM pre_orders o
            JOIN customers c ON c.id = o.customer_id
            WHERE o.id = ?'
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

    public static function getOrderSummaryByProduct(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query(
            'SELECT p.name, SUM(i.quantity) AS total_qty
            FROM pre_order_items i
            JOIN products p ON p.id = i.product_id
            JOIN pre_orders po ON po.id = i.pre_order_id
            WHERE YEAR(po.created_at) = YEAR(NOW())
            GROUP BY p.id, p.name
            ORDER BY p.sort_order'
        );
        return $stmt->fetchAll();
    }

    public static function getOrderStats(): array
    {
        $pdo = Database::getConnection();
        $total = (int) $pdo->query(
            'SELECT COUNT(*) FROM pre_orders WHERE YEAR(created_at) = YEAR(NOW())'
        )->fetchColumn();
        $delivered = (int) $pdo->query(
            'SELECT COUNT(*) FROM pre_orders WHERE is_delivered = 1 AND YEAR(created_at) = YEAR(NOW())'
        )->fetchColumn();
        $manualPending = (int) $pdo->query(
            'SELECT COUNT(*) FROM pre_orders WHERE has_manual_work = 1 AND is_delivered = 0 AND YEAR(created_at) = YEAR(NOW())'
        )->fetchColumn();
        return [
            'total_orders'   => $total,
            'delivered'      => $delivered,
            'manual_pending' => $manualPending,
        ];
    }

    public static function setDelivered(int $orderId, bool $delivered): void
    {
        $pdo = Database::getConnection();
        $pdo->prepare('UPDATE pre_orders SET is_delivered = ? WHERE id = ?')
            ->execute([(int) $delivered, $orderId]);
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

    public static function deleteOrder(int $orderId): void
    {
        $pdo = Database::getConnection();
        $pdo->prepare('DELETE FROM pre_orders WHERE id = ?')->execute([$orderId]);
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
            SET product_id = ?, quantity = ?, unit_price_ore = ?, needs_manual_work = ?,
                manual_work_status = ?
            WHERE id = ?'
        )->execute([
            $productId,
            $quantity,
            $product['price_ore'],
            $product['needs_manual_work'],
            $product['needs_manual_work'] ? 'ej_behandlad' : 'ej_tillämplig',
            $itemId,
        ]);

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

        $count = $pdo->prepare(
            'SELECT COUNT(*) FROM pre_order_items
            WHERE pre_order_id = ? AND needs_manual_work = 1 AND manual_work_status = "ej_behandlad"'
        );
        $count->execute([$orderId]);
        $pending = (int) $count->fetchColumn();

        $pdo->prepare('UPDATE pre_orders SET has_manual_work = ? WHERE id = ?')
            ->execute([$pending > 0 ? 1 : 0, $orderId]);
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

        $count = $pdo->prepare(
            'SELECT COUNT(*) FROM pre_order_items
            WHERE pre_order_id = ? AND needs_manual_work = 1 AND manual_work_status = "ej_behandlad"'
        );
        $count->execute([$orderId]);
        $pdo->prepare('UPDATE pre_orders SET has_manual_work = ? WHERE id = ?')
            ->execute([(int) $count->fetchColumn() > 0 ? 1 : 0, $orderId]);
    }
}