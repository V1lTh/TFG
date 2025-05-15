<?php
// utils.php
if (!function_exists('connectDB')) {
    session_start();
    function connectDB(): mysqli {
        $m = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($m->connect_error) die('Error de conexión');
        return $m;
    }
    function requireLogin(): void {
        if (empty($_SESSION['user_id'])) {
            header('Location: auth.php');
            exit;
        }
    }
    function flash(string $msg, string $type = 'info'): void {
        $_SESSION['flash'] = ['msg'=>$msg,'type'=>$type];
    }
    function getFlash(): ?array {
        if (isset($_SESSION['flash'])) {
            $f = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $f;
        }
        return null;
    }
    function sanitizeFilename(string $name): string {
        $name = str_replace(['\\','/'], '', $name);
        return preg_replace('/[^\w\-. ]+/', '', $name);
    }
    function userDir(string $user): string {
        $d = UPLOAD_BASE . "/$user";
        if (!is_dir($d)) mkdir($d, 0755, true);
        return $d;
    }
    function listUserFiles(string $dir): array {
        return array_values(array_diff(scandir($dir), ['.','..']));
    }
}