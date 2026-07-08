<?php

require_once __DIR__ . '/../Core/Database.php';

class Dashboard
{
    public static function getDashboardStats(?string $previousLoginAt): array
    {
        // Direct queries — Dashboard owns these stats, no Product/Customer model methods used intentionally.
        $pdo = Database::getConnection();
        $thisYear = date('Y');

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM pre_orders WHERE YEAR(created_at) = ?');
        $stmt->execute([$thisYear]);
        $totalThisYear = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM pre_orders WHERE is_delivered = 1 AND YEAR(created_at) = ?');
        $stmt->execute([$thisYear]);
        $delivered = (int) $stmt->fetchColumn();

        $stmt = $pdo->query('SELECT COUNT(*) FROM pre_orders WHERE has_manual_work = 1 AND is_delivered = 0');
        $manualPending = (int) $stmt->fetchColumn();

        $newSinceLogin = 0;
        if ($previousLoginAt) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM pre_orders WHERE created_at > ?');
            $stmt->execute([$previousLoginAt]);
            $newSinceLogin = (int) $stmt->fetchColumn();
        }

        $stmt = $pdo->query('SELECT COUNT(*) FROM customers');
        $totalCustomers = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM customer_role_assignments a
            JOIN customer_roles r ON r.id = a.role_id WHERE r.name = "newsletter"'
        );
        $stmt->execute();
        $newsletterCount = (int) $stmt->fetchColumn();

        $productTotals = [];
        $patterns = [
            'bifor'   => '%Bifor%',
            'dulco'   => '%Ideal Api%',
            'lackad'  => '%Färdiglackad%',
        ];
        foreach ($patterns as $key => $pattern) {
            $stmt = $pdo->prepare(
                'SELECT COALESCE(SUM(i.quantity), 0)
                FROM pre_order_items i
                JOIN products p ON p.id = i.product_id
                JOIN pre_orders o ON o.id = i.pre_order_id
                WHERE p.name LIKE ? AND YEAR(o.created_at) = ?'
            );
            $stmt->execute([$pattern, $thisYear]);
            $productTotals[$key] = (int) $stmt->fetchColumn();
        }

        $stmt = $pdo->query('SELECT COUNT(*) FROM products WHERE active = 1 AND deprecated = 0');
        $activeProducts = (int) $stmt->fetchColumn();

        return [
            'new_since_login'  => $newSinceLogin,
            'total_this_year'  => $totalThisYear,
            'delivered'        => $delivered,
            'manual_pending'   => $manualPending,
            'total_customers'  => $totalCustomers,
            'newsletter_count' => $newsletterCount,
            'product_totals'   => $productTotals,
            'active_products'  => $activeProducts,
        ];
    }
}