<?php

require_once __DIR__ . '/../Core/Database.php';

class Customer
{
    /**
     * Finds existing customer by email or creates a new one.
     * Also assigns 'customer' role if newly created.
     * Silently updates name if it differs.
     */
    public static function findOrCreateCustomer(string $name, string $email, string $assignRole = 'customer'): int
    {
        $pdo = Database::getConnection();
        $email = strtolower(trim($email));
        $name  = trim($name);

        $stmt = $pdo->prepare('SELECT id FROM customers WHERE email = ?');
        $stmt->execute([$email]);
        $existing = $stmt->fetch();

        if ($existing) {
            $pdo->prepare('UPDATE customers SET name = ? WHERE id = ?')
                ->execute([$name, $existing['id']]);
            return (int) $existing['id'];
        }

        $pdo->prepare('INSERT INTO customers (name, email) VALUES (?, ?)')
            ->execute([$name, $email]);
        $customerId = (int) $pdo->lastInsertId();

        $roleStmt = $pdo->prepare('SELECT id FROM customer_roles WHERE name = ?');
        $roleStmt->execute([$assignRole]);
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
}