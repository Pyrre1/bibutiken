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

    // ── Products ─────────────────────────────────────────────

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

    // ── Customers ─────────────────────────────────────────────

    /**
     * Finds existing customer by email or creates a new one.
     * Also assigns 'customer' role if newly created.
     * Silently updates name if it differs (no error exposed to public).
     */
    public static function findOrCreateCustomer(string $name, string $email): int
    {
        $pdo = Database::getConnection();
        $email = strtolower(trim($email));
        $name  = trim($name);

        $stmt = $pdo->prepare('SELECT id FROM customers WHERE email = ?');
        $stmt->execute([$email]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Update name silently in case it changed
            $pdo->prepare('UPDATE customers SET name = ? WHERE id = ?')
                ->execute([$name, $existing['id']]);
            return (int) $existing['id'];
        }

        $pdo->prepare('INSERT INTO customers (name, email) VALUES (?, ?)')
            ->execute([$name, $email]);
        $customerId = (int) $pdo->lastInsertId();

        // Assign 'customer' role
        $roleStmt = $pdo->prepare('SELECT id FROM customer_roles WHERE name = "customer"');
        $roleStmt->execute();
        $role = $roleStmt->fetch();
        if ($role) {
            $pdo->prepare(
                'INSERT IGNORE INTO customer_role_assignments (customer_id, role_id) VALUES (?, ?)'
            )->execute([$customerId, $role['id']]);
        }

        return $customerId;
    }

    public static function searchCustomers(string $term): array
    {
        $pdo = Database::getConnection();
        $like = '%' . $term . '%';
        $stmt = $pdo->prepare(
            'SELECT c.id, c.name, c.email, c.created_at,
                    COUNT(o.id) AS order_count
            FROM customers c
            LEFT JOIN pre_orders o ON o.customer_id = c.id
            WHERE c.email LIKE ? OR c.name LIKE ?
            GROUP BY c.id
            ORDER BY c.name ASC'
        );
        $stmt->execute([$like, $like]);
        return $stmt->fetchAll();
    }

    // Remove email only (GDPR request - keep name for records)
    public static function anonymizeEmail(int $customerId): void
    {
        $pdo = Database::getConnection();
        $pdo->prepare('UPDATE customers SET email = "raderad@begaran.se" WHERE id = ?')
            ->execute([$customerId]);
    }

    // Full anonymize - keep record but remove all personal info
    public static function anonymizeCustomer(int $customerId): void
    {
        $pdo = Database::getConnection();
        $pdo->prepare('UPDATE customers SET email = "raderad@begaran.se", name = "Raderad på begäran" WHERE id = ?')
            ->execute([$customerId]);
    }

    // Edit customer info
    public static function updateCustomer(int $customerId, string $name, string $email): void
    {
        $pdo = Database::getConnection();
        $pdo->prepare('UPDATE customers SET name = ?, email = ? WHERE id = ?')
            ->execute([trim($name), strtolower(trim($email)), $customerId]);
    }

    // Get customer with roles and orders
    public static function getCustomerById(int $customerId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT c.id, c.name, c.email, c.created_at
            FROM customers c WHERE c.id = ?'
        );
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch();
        if (!$customer) return null;

        // Roles
        $stmt2 = $pdo->prepare(
            'SELECT r.id, r.name FROM customer_roles r
            JOIN customer_role_assignments a ON a.role_id = r.id
            WHERE a.customer_id = ?'
        );
        $stmt2->execute([$customerId]);
        $customer['roles'] = $stmt2->fetchAll();

        // Orders
        $stmt3 = $pdo->prepare(
            'SELECT o.id, o.order_number, o.created_at, o.is_delivered, o.has_manual_work
            FROM pre_orders o
            WHERE o.customer_id = ?
            ORDER BY o.created_at DESC'
        );
        $stmt3->execute([$customerId]);
        $customer['orders'] = $stmt3->fetchAll();

        return $customer;
    }

    // All roles for role assignment UI
    public static function getAllRoles(): array
    {
        $pdo = Database::getConnection();
        return $pdo->query('SELECT id, name FROM customer_roles ORDER BY id')->fetchAll();
    }

    public static function setCustomerRoles(int $customerId, array $roleIds): void
    {
        $pdo = Database::getConnection();
        $pdo->prepare('DELETE FROM customer_role_assignments WHERE customer_id = ?')
            ->execute([$customerId]);
        $stmt = $pdo->prepare(
            'INSERT INTO customer_role_assignments (customer_id, role_id) VALUES (?, ?)'
        );
        foreach ($roleIds as $roleId) {
            $stmt->execute([$customerId, (int)$roleId]);
        }
    }

    // ── Orders ────────────────────────────────────────────────

    public static function insertOrder(string $customerName, string $customerEmail, array $items): array
    {
        $pdo = Database::getConnection();
        $customerId = self::findOrCreateCustomer($customerName, $customerEmail);
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
                    o.created_at, o.is_delivered, o.has_manual_work
            FROM pre_orders o
            JOIN customers c ON c.id = o.customer_id
            ORDER BY o.created_at ASC'
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
            GROUP BY p.id, p.name
            ORDER BY p.sort_order'
        );
        return $stmt->fetchAll();
    }

    public static function getOrderStats(): array
    {
        $pdo = Database::getConnection();
        $total      = (int) $pdo->query('SELECT COUNT(*) FROM pre_orders')->fetchColumn();
        $delivered  = (int) $pdo->query('SELECT COUNT(*) FROM pre_orders WHERE is_delivered = 1')->fetchColumn();
        $manualPending = (int) $pdo->query(
            'SELECT COUNT(*) FROM pre_orders WHERE has_manual_work = 1 AND is_delivered = 0'
        )->fetchColumn();
        return [
            'total_orders'  => $total,
            'delivered'     => $delivered,
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