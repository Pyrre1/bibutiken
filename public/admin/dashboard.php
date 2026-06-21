<?php
require_once __DIR__ . '/../../app/Core/init.php';
Auth::requireLogin();

$pageTitle = 'Admin Dashboard';
require __DIR__ . '/../../app/Views/admin/_header.php';
?>
<h1>Welcome, <?= Security::e($_SESSION['admin_username']) ?></h1>
<p>Use the nav above to manage opening hours. Pre-order management is added in a later step.</p>
<?php
require __DIR__ . '/../../app/Views/admin/_footer.php';