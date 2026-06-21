<?php

require_once __DIR__ . '/Database.php';

class Auth
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 15;

    public static function attemptLogin(string $username, string $password): array
    {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'Fel användarnamn eller lösenord.'];
        }

        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            return ['success' => false, 'message' => 'Kontot är temporärt låst. Försök igen senare.'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            self::registerFailedAttempt($pdo, $user);
            return ['success' => false, 'message' => 'Fel användarnamn eller lösenord.'];
        }

        $stmt = $pdo->prepare('UPDATE admin_users SET failed_attempts = 0, locked_until = NULL WHERE id = ?');
        $stmt->execute([$user['id']]);

        session_regenerate_id(true);
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];

        return ['success' => true];
    }

    private static function registerFailedAttempt(PDO $pdo, array $user): void
    {
        $attempts = $user['failed_attempts'] + 1;
        $lockedUntil = null;

        if ($attempts >= self::MAX_ATTEMPTS) {
            $lockedUntil = date('Y-m-d H:i:s', time() + self::LOCKOUT_MINUTES * 60);
        }

        $stmt = $pdo->prepare('UPDATE admin_users SET failed_attempts = ?, locked_until = ? WHERE id = ?');
        $stmt->execute([$attempts, $lockedUntil, $user['id']]);
    }

    public static function isLoggedIn(): bool
    {
        return !empty($_SESSION['admin_id']);
    }

    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            header('Location: /admin/login.php');
            exit;
        }
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }
}