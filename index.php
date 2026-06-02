<?php
/**
 * index.php – Entry point
 * Redirect to login or appropriate dashboard.
 */
define('BASE_URL', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'));
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    if (userRole() === 'admin') {
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . '/client/campaigns.php');
    }
} else {
    header('Location: ' . BASE_URL . '/login.php');
}
exit;