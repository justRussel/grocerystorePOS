<?php
/**
 * GroceryPOS - AccountController
 */

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/flash.php';

class AccountController
{
    public function index(): void
    {
        $module    = 'account';
        $pageTitle = 'Account Settings';
        $user      = User::findById((int) $_SESSION['user_id']);
        require_once __DIR__ . '/../views/account/index.php';
    }

    public function updateProfile(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?module=account');
            exit;
        }

        $id   = (int) $_SESSION['user_id'];
        $data = [
            'full_name'     => trim($_POST['full_name']     ?? ''),
            'email'         => trim($_POST['email']         ?? ''),
            'phone'         => trim($_POST['phone']         ?? ''),
            'store_name'    => trim($_POST['store_name']    ?? ''),
            'store_address' => trim($_POST['store_address'] ?? ''),
            'tax_id'        => trim($_POST['tax_id']        ?? ''),
        ];

        if (empty($data['full_name'])) {
            setFlash('danger', 'Full name is required.');
            header('Location: ' . BASE_URL . '?module=account');
            exit;
        }

        if (User::updateProfile($id, $data)) {
            $_SESSION['user_name']      = $data['full_name'];
            $_SESSION['store_name']     = $data['store_name'];
            $_SESSION['store_address']  = $data['store_address'];
            setFlash('success', 'Profile updated successfully.');
        } else {
            setFlash('danger', 'Failed to update profile.');
        }

        header('Location: ' . BASE_URL . '?module=account');
        exit;
    }

    public function updatePassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?module=account');
            exit;
        }

        $id          = (int) $_SESSION['user_id'];
        $currentPass = $_POST['current_password'] ?? '';
        $newPass     = $_POST['new_password']      ?? '';
        $confirmPass = $_POST['confirm_password']  ?? '';

        $user = User::findById($id);
        if (!$user || !password_verify($currentPass, $user['password_hash'])) {
            setFlash('danger', 'Current password is incorrect.');
            header('Location: ' . BASE_URL . '?module=account');
            exit;
        }

        if (strlen($newPass) < 8) {
            setFlash('danger', 'New password must be at least 8 characters.');
            header('Location: ' . BASE_URL . '?module=account');
            exit;
        }

        if ($newPass !== $confirmPass) {
            setFlash('danger', 'Passwords do not match.');
            header('Location: ' . BASE_URL . '?module=account');
            exit;
        }

        if (User::updatePassword($id, $newPass)) {
            setFlash('success', 'Password changed successfully.');
        } else {
            setFlash('danger', 'Failed to update password.');
        }

        header('Location: ' . BASE_URL . '?module=account');
        exit;
    }

    public function uploadPhoto(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '?module=account');
            exit;
        }

        $id = (int) $_SESSION['user_id'];

        if (empty($_FILES['photo']['name'])) {
            setFlash('danger', 'No file uploaded.');
            header('Location: ' . BASE_URL . '?module=account');
            exit;
        }

        $file         = $_FILES['photo'];
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $allowedExts  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            setFlash('danger', 'Upload error.');
            header('Location: ' . BASE_URL . '?module=account');
            exit;
        }

        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($mimeType, $allowedMimes, true) || !in_array($ext, $allowedExts, true)) {
            setFlash('danger', 'Invalid image type.');
            header('Location: ' . BASE_URL . '?module=account');
            exit;
        }

        $uploadDir = ROOT_PATH . 'uploads' . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR;
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

        $filename = uniqid('avatar_', true) . '.' . $ext;
        $dest     = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            User::updatePhoto($id, 'avatars/' . $filename);
            $_SESSION['user_photo'] = 'avatars/' . $filename;
            setFlash('success', 'Profile photo updated.');
        } else {
            setFlash('danger', 'Failed to save photo.');
        }

        header('Location: ' . BASE_URL . '?module=account');
        exit;
    }

    public function exportDatabase(): void
    {
        $sqlFile = ROOT_PATH . 'grocerypos.sql';

        if (!file_exists($sqlFile)) {
            setFlash('danger', 'SQL backup file not found.');
            header('Location: ' . BASE_URL . '?module=account');
            exit;
        }

        $filename = 'grocerypos_backup_' . date('Ymd_His') . '.sql';

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($sqlFile));
        header('Cache-Control: no-cache');

        readfile($sqlFile);
        exit;
    }
}
