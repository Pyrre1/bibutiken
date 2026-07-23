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

    
    // ── FIFO price calculation ─────────────────────────────────

    /**
     * Recalculates actual_price_ore and actual_price_note for all
     * pre_order_items of a given product. Call after any lagersaldo
     * or local_sales change for that product.
     */
    public static function recalcProductPrices(int $productId): void
    {
        $pdo = Database::getConnection();

        // 1. Reset existing calculations for this product
        $pdo->prepare(
            'UPDATE pre_order_items
            SET actual_price_ore = NULL, actual_price_note = NULL
            WHERE product_id = ?'
        )->execute([$productId]);

        // 2. Load lagersaldo (stock in) — sorted by restocked_at, then created_at
        $stockStmt = $pdo->prepare(
            'SELECT quantity, calculated_price_ore, restocked_at
            FROM lagersaldo
            WHERE product_id = ?
            ORDER BY restocked_at ASC, created_at ASC'
        );
        $stockStmt->execute([$productId]);
        $batches = $stockStmt->fetchAll(); // each: [quantity, calculated_price_ore, restocked_at]

        if (empty($batches)) return; // no stock entered yet, nothing to calculate

        // 3. Build unified consumption event queue sorted by date
        // local_sales
        $lsStmt = $pdo->prepare(
            'SELECT id, quantity, sold_at AS event_date
            FROM local_sales
            WHERE product_id = ?'
        );
        $lsStmt->execute([$productId]);
        $localSales = $lsStmt->fetchAll();

        // pre_order_items (joined to get the order date)
        $poStmt = $pdo->prepare(
            'SELECT i.id AS item_id, i.quantity, o.created_at AS event_date,
                    "preorder" AS event_type, i.id AS ls_id
            FROM pre_order_items i
            JOIN pre_orders o ON o.id = i.pre_order_id
            WHERE i.product_id = ?'
        );
        $poStmt->execute([$productId]);
        $preorderItems = $poStmt->fetchAll();

        // Merge and sort by event_date ASC, preorders before local_sales on same date
        $events = [];
        foreach ($localSales as $ls) {
            $events[] = [
                'type'     => 'local_sale',
                'ls_id'    => (int)$ls['id'],
                'item_id'  => null,
                'quantity' => (int)$ls['quantity'],
                'date'     => $ls['event_date'],
            ];
        }
        foreach ($preorderItems as $pi) {
            $events[] = [
                'type'     => 'preorder',
                'item_id'  => (int)$pi['item_id'],
                'quantity' => (int)$pi['quantity'],
                'date'     => $pi['event_date'],
            ];
        }
        usort($events, fn($a, $b) => strcmp($a['date'], $b['date']));

        // 4. Build mutable batch queue (FIFO)
        // Each entry: ['remaining' => int, 'price_ore' => int, 'date' => string]
        $queue = [];
        foreach ($batches as $b) {
            $queue[] = [
                'remaining' => (int)$b['quantity'],
                'price_ore' => (int)$b['calculated_price_ore'],
                'date'      => $b['restocked_at'],
            ];
        }

        // 5. Walk events, deplete queue
        $updateStmt = $pdo->prepare(
            'UPDATE pre_order_items
            SET actual_price_ore = ?, actual_price_note = ?
            WHERE id = ?'
        );

        foreach ($events as $event) {
            $needed = $event['quantity'];

            // Consume from queue for local_sales — also record price per unit
            if ($event['type'] === 'local_sale') {
                $lsSegments = [];
                while ($needed > 0 && !empty($queue)) {
                    $take = min($needed, $queue[0]['remaining']);
                    $lsSegments[] = ['qty' => $take, 'price_ore' => $queue[0]['price_ore']];
                    $queue[0]['remaining'] -= $take;
                    $needed -= $take;
                    if ($queue[0]['remaining'] === 0) array_shift($queue);
                }
                if ($needed > 0) {
                    $lsSegments[] = ['qty' => $needed, 'price_ore' => null];
                }
                // Store price (first segment price — straddle local sales are rare,
                // store the dominant price or null if unknown)
                $lsPrice = count($lsSegments) === 1
                    ? $lsSegments[0]['price_ore']
                    : null;
                $pdo->prepare(
                    'UPDATE local_sales SET calculated_price_ore = ? WHERE id = ?'
                )->execute([$lsPrice, $event['ls_id']]);
                continue;
            }

            // preorder item — consume from FIFO queue regardless of order date.
            // Orders are already sorted by date in the event queue, so position
            // in the queue is correct. Batch eligibility is not date-gated.
            $segments  = [];
            $remaining = $needed;

            while ($remaining > 0) {
                if (!empty($queue)) {
                    $take = min($remaining, $queue[0]['remaining']);
                    $segments[] = ['qty' => $take, 'price_ore' => $queue[0]['price_ore']];
                    $queue[0]['remaining'] -= $take;
                    $remaining -= $take;
                    if ($queue[0]['remaining'] === 0) array_shift($queue);
                } else {
                    // Stock exhausted — no restock entered yet for remaining units
                    $segments[] = ['qty' => $remaining, 'price_ore' => null];
                    $remaining = 0;
                }
            }

            // Determine what to write
            if (count($segments) === 1) {
                $priceOre  = $segments[0]['price_ore'];
                $note      = null;
            } else {
                // Straddle — build note like "3 à 123kr / 2 à okänt pris"
                $parts = [];
                $priceOre = null;
                foreach ($segments as $seg) {
                    if ($seg['price_ore'] !== null) {
                        $parts[] = $seg['qty'] . ' à ' . ($seg['price_ore'] / 100) . 'kr';
                    } else {
                        $parts[] = $seg['qty'] . ' à okänt pris';
                    }
                }
                $note = implode(' / ', $parts);
            }

            $updateStmt->execute([$priceOre, $note, $event['item_id']]);
        }
    }

    // ── Lagersaldo ─────────────────────────────────────────────

    public static function getLagersaldo(): array
    {
        $pdo = Database::getConnection();
        // Show entries newer than 1 year, OR entries from older batches
        // that still have stock remaining (consumed < quantity).
        // "Consumed" = local_sales + pre_order_items for that product after restocked_at.
        $stmt = $pdo->query(
            'SELECT l.id, l.product_id, p.name AS product_name,
                    l.quantity, l.restocked_at, l.calculated_price_ore,
                    COALESCE(
                        (SELECT SUM(ls.quantity)
                        FROM local_sales ls
                        WHERE ls.product_id = l.product_id
                            AND ls.sold_at >= l.restocked_at),
                    0) +
                    COALESCE(
                        (SELECT SUM(i.quantity)
                        FROM pre_order_items i
                        JOIN pre_orders o ON o.id = i.pre_order_id
                        WHERE i.product_id = l.product_id
                            AND DATE(o.created_at) >= l.restocked_at),
                    0) AS consumed
            FROM lagersaldo l
            JOIN products p ON p.id = l.product_id
            ORDER BY l.restocked_at DESC, l.created_at DESC'
        );
        $rows = $stmt->fetchAll();

        $cutoff = date('Y-m-d', strtotime('-1 year'));
        foreach ($rows as &$row) {
            $remaining = $row['quantity'] - $row['consumed'];
            $row['remaining'] = max(0, $remaining);
            $row['hidden'] = ($row['restocked_at'] < $cutoff && $row['remaining'] <= 0);
        }
        unset($row);
        return $rows;
    }

    public static function addLagersaldo(int $productId, int $quantity, string $date, int $priceOre): int
    {
        $pdo = Database::getConnection();
        $pdo->prepare(
            'INSERT INTO lagersaldo (product_id, quantity, restocked_at, calculated_price_ore)
            VALUES (?, ?, ?, ?)'
        )->execute([$productId, $quantity, $date, $priceOre]);
        $id = (int)$pdo->lastInsertId();
        self::recalcProductPrices($productId);
        return $id;
    }

    public static function deleteLagersaldo(int $id): void
    {
        $pdo = Database::getConnection();
        $row = $pdo->prepare('SELECT product_id FROM lagersaldo WHERE id = ?');
        $row->execute([$id]);
        $productId = (int)$row->fetchColumn();
        $pdo->prepare('DELETE FROM lagersaldo WHERE id = ?')->execute([$id]);
        if ($productId) self::recalcProductPrices($productId);
    }

    // ── Local sales ────────────────────────────────────────────

    public static function addLocalSales(array $rows): void
    {
        // $rows: array of ['product_id'=>int, 'quantity'=>int, 'sold_at'=>'YYYY-MM-DD']
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO local_sales (product_id, quantity, sold_at) VALUES (?, ?, ?)'
        );
        $affectedProducts = [];
        foreach ($rows as $r) {
            $stmt->execute([(int)$r['product_id'], (int)$r['quantity'], $r['sold_at']]);
            $affectedProducts[(int)$r['product_id']] = true;
        }
        foreach (array_keys($affectedProducts) as $productId) {
            self::recalcProductPrices($productId);
        }
    }

    public static function getLocalSales(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query(
            'SELECT ls.id, ls.product_id, p.name AS product_name,
                    ls.quantity, ls.sold_at
            FROM local_sales ls
            JOIN products p ON p.id = ls.product_id
            ORDER BY ls.sold_at DESC, ls.created_at DESC'
        );
        return $stmt->fetchAll();
    }

    public static function deleteLocalSale(int $id): void
    {
        $pdo = Database::getConnection();
        $row = $pdo->prepare('SELECT product_id FROM local_sales WHERE id = ?');
        $row->execute([$id]);
        $productId = (int)$row->fetchColumn();
        $pdo->prepare('DELETE FROM local_sales WHERE id = ?')->execute([$id]);
        if ($productId) self::recalcProductPrices($productId);
    }

    // ── Export tracking ────────────────────────────────────────

    public static function recordExport(array $orderIds): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO orders_exported_at (pre_order_id) VALUES (?)'
        );
        foreach ($orderIds as $id) {
            $stmt->execute([(int)$id]);
        }
    }

    public static function getExportCounts(array $orderIds): array
    {
        // Returns [order_id => count] for how many times each has been exported
        if (empty($orderIds)) return [];
        $pdo = Database::getConnection();
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $stmt = $pdo->prepare(
            "SELECT pre_order_id, COUNT(*) AS cnt
            FROM orders_exported_at
            WHERE pre_order_id IN ($placeholders)
            GROUP BY pre_order_id"
        );
        $stmt->execute($orderIds);
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[(int)$row['pre_order_id']] = (int)$row['cnt'];
        }
        return $result;
    }

    // ── Spam protection ────────────────────────────────────────

    public static function hashIp(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        return hash('sha256', $ip . 'bibutiken-salt-2025');
    }

    public static function checkRateLimit(string $hashedIp): bool
    {
        $pdo = Database::getConnection();

        // Lazy cleanup — remove entries older than 2 hours
        $pdo->prepare(
            'DELETE FROM preorder_rate_limit WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 2 HOUR)'
        )->execute();

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM preorder_rate_limit
            WHERE hashed_ip = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)'
        );
        $stmt->execute([$hashedIp]);
        return (int) $stmt->fetchColumn() === 0; // true = allowed
    }

    public static function recordAttempt(string $hashedIp): void
    {
        $pdo = Database::getConnection();
        $pdo->prepare(
            'INSERT INTO preorder_rate_limit (hashed_ip) VALUES (?)'
        )->execute([$hashedIp]);
    }

    public static function logRejected(string $hashedIp, string $reason, ?string $email = null): void
    {
        $pdo = Database::getConnection();

        // Lazy cleanup — remove entries older than 30 days
        $pdo->prepare(
            'DELETE FROM preorder_rejected_log WHERE rejected_at < DATE_SUB(NOW(), INTERVAL 30 DAY)'
        )->execute();

        $pdo->prepare(
            'INSERT INTO preorder_rejected_log (hashed_ip, attempted_email, reason) VALUES (?, ?, ?)'
        )->execute([$hashedIp, $email, $reason]);
    } 
}
