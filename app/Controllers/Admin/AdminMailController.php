<?php

class AdminMailController
{
    public static function index(): void
    {
        require_once __DIR__ . '/../../Core/MailService.php';

        Auth::requireLogin();

        $pdo     = Database::getConnection();
        $message = null;
        $error   = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::validateCsrf($_POST['csrf_token'] ?? null)) {
                $error = 'Ogiltig begäran, försök igen.';
            } else {
                $action = $_POST['action'] ?? '';

                if ($action === 'save_template') {
                    $id      = (int)($_POST['template_id'] ?? 0);
                    $namn    = trim($_POST['namn']     ?? '');
                    $amne    = trim($_POST['amne']     ?? '');
                    $brodtext = trim($_POST['brodtext'] ?? '');
                    $roll    = trim($_POST['roll']     ?? '');

                    if ($namn === '' || $amne === '' || $brodtext === '' || $roll === '') {
                        $error = 'Alla fält måste fyllas i.';
                    } elseif ($id > 0) {
                        $stmt = $pdo->prepare(
                            'UPDATE mail_templates
                              SET namn = ?, amne = ?, brodtext = ?, roll = ?, updated_at = NOW()
                              WHERE id = ?'
                        );
                        $stmt->execute([$namn, $amne, $brodtext, $roll, $id]);
                        $message = 'Mall uppdaterad.';
                    } else {
                        $stmt = $pdo->prepare(
                            'INSERT INTO mail_templates (namn, amne, brodtext, roll, created_at, updated_at)
                              VALUES (?, ?, ?, ?, NOW(), NOW())'
                        );
                        $stmt->execute([$namn, $amne, $brodtext, $roll]);
                        $message = 'Mall skapad.';
                    }

                } elseif ($action === 'delete_template') {
                    $id = (int)($_POST['template_id'] ?? 0);
                    if ($id > 0) {
                        $pdo->prepare('DELETE FROM mail_templates WHERE id = ?')->execute([$id]);
                        $message = 'Mall borttagen.';
                    }
                }
            }
        }

        // Fetch all templates, newest first within each roll
        $templates = $pdo->query(
            'SELECT id, namn, amne, brodtext, roll, created_at, updated_at
              FROM mail_templates
              ORDER BY roll ASC, namn ASC'
        )->fetchAll();

        // Distinct roles for the dropdown (existing in DB + known defaults)
        $existingRoles = array_unique(array_column($templates, 'roll'));
        $knownRoles    = ['vinterfoder', 'nyhetsbrev', 'upplevelse', 'generell'];
        $allRoles      = array_unique(array_merge($knownRoles, $existingRoles));
        sort($allRoles);

        $pageTitle    = 'Mejlmallar - Admin';
        $activePage   = 'mail';
        $extraStyles  = ['/assets/css/admin-modal.css'];
        $extraScripts = ['/assets/js/admin-mail.js'];

        require __DIR__ . '/../../Views/admin/_header.php';
        require __DIR__ . '/../../Views/admin/mejl.php';
        require __DIR__ . '/../../Views/admin/_footer.php';
    }
}