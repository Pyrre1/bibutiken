<?php
require_once __DIR__ . '/../../app/Core/init.php';

if (Auth::isLoggedIn()) {
    header('Location: /admin/dashboard.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Ogiltig begäran, försök igen.';
    } else {
        $result = Auth::attemptLogin($_POST['username'] ?? '', $_POST['password'] ?? '');
        if ($result['success']) {
            header('Location: /admin/dashboard.php');
            exit;
        }
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Admin Login</title></head>
<body>
    <h1>Admininloggning</h1>
    <?php if ($error): ?>
        <p style="color:red;"><?= Security::e($error) ?></p>
    <?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">
        <label>Användarnamn: <input type="text" name="username" required></label><br>
        <label>Lösenord: <input type="password" name="password" required></label><br>
        <button type="submit">Logga in</button>
    </form>
</body>
</html>