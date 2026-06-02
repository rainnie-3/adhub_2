<?php
/**
 * logout.php
 * Destroys session and redirects to login.
 */
define('BASE_URL', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'));
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
header('Location: ' . BASE_URL . '/login.php');
exit;