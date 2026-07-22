<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Logga in – Bibutiken Admin</title>
    <link rel="stylesheet" href="/assets/css/tokens.css">
    <link rel="stylesheet" href="/assets/css/shared.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin">

<div class="admin-login-wrap">
    <div class="admin-login-card">
        <h1>Admininloggning</h1>

        <?php if ($error): ?>
            <p class="error"><?= Security::e($error) ?></p>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= Security::e(Security::csrfToken()) ?>">

            <label for="username">Användarnamn</label>
            <input type="text" id="username" name="username" required autocomplete="username">

            <label for="password">Lösenord</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">

            <button type="submit">Logga in</button>
        </form>
    </div>
</div>

</body>
</html>