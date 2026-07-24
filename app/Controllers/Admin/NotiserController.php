<?php

require_once __DIR__ . '/../../Models/Banner.php';
require_once __DIR__ . '/../../Core/Database.php';
require_once __DIR__ . '/../../Models/Settings.php';

class NotiserController
{
    public static function index(): void
    {
        Auth::requireLogin();

        // Handle banner actions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'create') {
                $message = trim($_POST['message'] ?? '');
                $type    = $_POST['type'] ?? 'info';
                if ($message !== '' && in_array($type, ['info', 'warning', 'success'], true)) {
                    Banner::create($message, $type);
                }
            } elseif ($action === 'toggle') {
                $id     = (int) ($_POST['id'] ?? 0);
                $active = (int) ($_POST['active'] ?? 0);
                if ($id > 0) {
                    Banner::setActive($id, (bool) $active);
                }
            } elseif ($action === 'delete') {
                $id = (int) ($_POST['id'] ?? 0);
                if ($id > 0) {
                    Banner::delete($id);
                }
            } elseif ($action === 'move') {
                $id        = (int) ($_POST['id'] ?? 0);
                $direction = $_POST['direction'] ?? '';
                if ($id > 0 && in_array($direction, ['up', 'down'], true)) {
                    Banner::move($id, $direction);
                }
            } elseif ($action === 'update') {
                $id      = (int) ($_POST['id'] ?? 0);
                $message = trim($_POST['message'] ?? '');
                $type    = $_POST['type'] ?? 'info';
                if ($id > 0 && $message !== '' && in_array($type, ['info', 'warning', 'success'], true)) {
                    Banner::update($id, $message, $type);
                }
            } elseif ($action === 'toggle_preorder') {
                $current = Settings::get('preorder_enabled', '1');
                Settings::set('preorder_enabled', $current === '1' ? '0' : '1');
            }
            header('Location: /admin/notiser');
            exit;
        }

        $banners        = Banner::getAllBanners();
        $preorderActive = Settings::get('preorder_enabled', '1') === '1';
        $editBanner     = null;
        $editId         = (int) ($_GET['edit'] ?? 0);
        if ($editId > 0) {
            $editBanner = Banner::getById($editId);
        }
        $pageTitle   = 'Notiser';
        $activePage  = 'notiser';
        $extraStyles = ['/assets/css/admin-notice.css'];

        require_once __DIR__ . '/../../Views/admin/notiser.php';
    }
}