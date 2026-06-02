<?php
/**
 * includes/auth.php
 * AdHub – Authentication helpers
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Helpers ──────────────────────────────────────────────────

/** Returns true when a user is logged in. */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/** Returns current user role or null. */
function userRole(): ?string {
    return $_SESSION['user_role'] ?? null;
}

/** Returns current user id or null. */
function userId(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/** Returns current user name or empty string. */
function userName(): string {
    return $_SESSION['user_name'] ?? '';
}

// ── Guards ───────────────────────────────────────────────────

/**
 * Redirect to login if not authenticated.
 * Optionally restrict to a specific role.
 */
function requireLogin(string $role = ''): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
    if ($role && userRole() !== $role) {
        // Wrong role – send to correct dashboard
        if (userRole() === 'admin') {
            header('Location: ' . BASE_URL . '/admin/dashboard.php');
        } else {
            header('Location: ' . BASE_URL . '/client/dashboard.php');
        }
        exit;
    }
}

/** Redirect already-logged-in users away from login page. */
function redirectIfLoggedIn(): void {
    if (!isLoggedIn()) return;
    if (userRole() === 'admin') {
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . '/client/dashboard.php');
    }
    exit;
}