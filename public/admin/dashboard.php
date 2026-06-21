<?php
require_once __DIR__ . '/../../app/Core/init.php';
Auth::requireLogin();

$pageTitle = 'Admin Dashboard';
require __DIR__ . '/../../app/Views/admin/_header.php';
?>
<h1>Välkommen, <?= Security::e($_SESSION['admin_username']) ?></h1>
<p>Använd navigationsmenyn ovan för att hantera öppettider. Förbeställningshantering läggs till i en senare steg.</p>
<?php
require __DIR__ . '/../../app/Views/admin/_footer.php';