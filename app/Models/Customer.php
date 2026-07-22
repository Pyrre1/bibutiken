<?php

require_once __DIR__ . '/../Core/Database.php';

class Customer
{
    /**
     * Finds existing customer by email or creates a new one.
     * Also assigns 'customer' role if newly created.
     * Silently updates name if it differs.
     */
    public static function findOrCreateCustomer(string $name, string $email, string $assignRole = 'vinterfoder'): int
    {
        $pdo = Database::getConnection();
        $email = strtolower(trim($email));
        $name  = trim($name);

        // Resolve target role ID
        $roleStmt = $pdo->prepare('SELECT id FROM customer_roles WHERE name = ?');
        $roleStmt->execute([$assignRole]);
        $role = $roleStmt->fetch();
        $roleId = $role ? (int) $role['id'] : null;

        // Resolve ingen_mejl role ID
        $nmStmt = $pdo->prepare('SELECT id FROM customer_roles WHERE name = ?');
        $nmStmt->execute(['ingen_mejl']);
        $nm = $nmStmt->fetch();
        $ingenMejlId = $nm ? (int) $nm['id'] : null;

        $stmt = $pdo->prepare('SELECT id FROM customers WHERE email = ?');
        $stmt->execute([$email]);
        $existing = $stmt->fetch();

        if ($existing) {
            $customerId = (int) $existing['id'];
            $pdo->prepare('UPDATE customers SET name = ? WHERE id = ?')
                ->execute([$name, $customerId]);

            if ($roleId) {
                // If customer has ingen_mejl, strip all roles first
                if ($ingenMejlId) {
                    $hasNm = $pdo->prepare(
                        'SELECT 1 FROM customer_role_assignments WHERE customer_id = ? AND role_id = ?'
                    );
                    $hasNm->execute([$customerId, $ingenMejlId]);
                    if ($hasNm->fetch()) {
                        $pdo->prepare('DELETE FROM customer_role_assignments WHERE customer_id = ?')
                            ->execute([$customerId]);
                    }
                }
                $pdo->prepare(
                    'INSERT IGNORE INTO customer_role_assignments (customer_id, role_id) VALUES (?, ?)'
                )->execute([$customerId, $roleId]);
            }
            return $customerId;
        }

        // New customer
        $pdo->prepare('INSERT INTO customers (name, email) VALUES (?, ?)')
            ->execute([$name, $email]);
        $customerId = (int) $pdo->lastInsertId();

        if ($roleId) {
            $pdo->prepare(
                'INSERT IGNORE INTO customer_role_assignments (customer_id, role_id) VALUES (?, ?)'
            )->execute([$customerId, $roleId]);
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

        $pdo->prepare('UPDATE customers SET email = CONCAT("raderad+", ?, "@begaran.se") WHERE id = ?')
            ->execute([$customerId, $customerId]);

        $pdo->prepare('DELETE FROM customer_role_assignments WHERE customer_id = ?')
            ->execute([$customerId]);

        $pdo->prepare('INSERT INTO customer_role_assignments (customer_id, role_id) VALUES (?, 5)')
            ->execute([$customerId]);
    }

    // Full anonymize - keep record but remove all personal info
    public static function anonymizeCustomer(int $customerId): void
    {
        $pdo = Database::getConnection();

        $pdo->prepare('UPDATE customers SET email = CONCAT("raderad+", ?, "@begaran.se"), name = "Raderad på begäran" WHERE id = ?')
            ->execute([$customerId, $customerId]);

        $pdo->prepare('DELETE FROM customer_role_assignments WHERE customer_id = ?')
            ->execute([$customerId]);

        $pdo->prepare('INSERT INTO customer_role_assignments (customer_id, role_id) VALUES (?, 5)')
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

        $stmt2 = $pdo->prepare(
            'SELECT r.id, r.name FROM customer_roles r
            JOIN customer_role_assignments a ON a.role_id = r.id
            WHERE a.customer_id = ?'
        );
        $stmt2->execute([$customerId]);
        $customer['roles'] = $stmt2->fetchAll();

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

    /**
     * Returns all customers, optionally filtered by role name.
     * Each row includes order_count and has_ingen_mejl flag.
     */
    public static function getAllCustomers(?string $roleFilter = null): array
    {
        $pdo = Database::getConnection();

        $noMailId = $pdo->query(
            'SELECT id FROM customer_roles WHERE name = "ingen_mejl"'
        )->fetchColumn();

        $sql = 'SELECT c.id, c.name, c.email, c.created_at,
                    COUNT(DISTINCT o.id) AS order_count,
                    MAX(CASE WHEN ra.role_id = ' . (int)$noMailId . ' THEN 1 ELSE 0 END) AS has_ingen_mejl,
                    GROUP_CONCAT(DISTINCT r.name ORDER BY r.id SEPARATOR ", ") AS role_names
                FROM customers c
                LEFT JOIN pre_orders o ON o.customer_id = c.id
                LEFT JOIN customer_role_assignments ra ON ra.customer_id = c.id
                LEFT JOIN customer_roles r ON r.id = ra.role_id';

        if ($roleFilter !== null) {
            $sql .= ' WHERE c.id IN (
                        SELECT customer_id FROM customer_role_assignments
                        JOIN customer_roles ON customer_roles.id = customer_role_assignments.role_id
                        WHERE customer_roles.name = ?
                    )';
        }

        $sql .= ' GROUP BY c.id ORDER BY c.name ASC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($roleFilter !== null ? [$roleFilter] : []);
        return $stmt->fetchAll();
    }
}