<?php
/**
 * register.php
 * AdHub – Sign Up page
 */

session_start();

define('BASE_URL', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'));

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

redirectIfLoggedIn();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name     = trim($_POST['name']             ?? '');
    $email    = trim($_POST['email']            ?? '');
    $password = trim($_POST['password']         ?? '');
    $confirm  = trim($_POST['confirm_password'] ?? '');

    if (!$name || !$email || !$password || !$confirm) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = 'That email address is already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("
                INSERT INTO users (name, email, password, role)
                VALUES (?, ?, ?, 'client')
            ");
            $stmt->execute([$name, $email, $hash]);
            $success = 'Account created! You can now sign in.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up – AdHub</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Mono:wght@300;400;500&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        body {
            background: var(--bg-base);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.015) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none;
        }

        body::after {
            content: '';
            position: fixed;
            top: -20%;
            left: 50%;
            transform: translateX(-50%);
            width: 600px;
            height: 400px;
            background: radial-gradient(ellipse, rgba(79,142,247,0.06) 0%, transparent 70%);
            pointer-events: none;
        }

        .login-wrap {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 400px;
            padding: 1.5rem;
        }

        .login-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-strong);
            border-radius: var(--radius-xl);
            padding: 2.25rem 2rem;
            box-shadow: 0 24px 64px rgba(0,0,0,0.5), 0 1px 0 rgba(255,255,255,0.05) inset;
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0; left: 10%; right: 10%;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(79,142,247,0.4), transparent);
        }

        .login-header { margin-bottom: 1.85rem; }

        .login-logo {
            display: flex;
            align-items: center;
            gap: .6rem;
            margin-bottom: 1.35rem;
        }

        .logo-icon {
            width: 34px; height: 34px;
            background: var(--primary);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem;
            color: #fff;
            box-shadow: 0 0 20px rgba(79,142,247,0.4);
            flex-shrink: 0;
        }

        .logo-text {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 1.15rem;
            color: var(--text-primary);
            letter-spacing: -0.04em;
        }

        .login-heading {
            font-family: var(--font-display);
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 .3rem;
            letter-spacing: -0.04em;
            line-height: 1.2;
        }

        .login-sub {
            font-size: .78rem;
            color: var(--text-muted);
            font-family: var(--font-mono);
            margin: 0;
        }

        .field-group { margin-bottom: 1rem; }

        .field-label {
            display: block;
            font-size: .65rem;
            font-weight: 600;
            color: var(--text-muted);
            letter-spacing: .1em;
            text-transform: uppercase;
            margin-bottom: .45rem;
            font-family: var(--font-mono);
        }

        .field-input {
            width: 100%;
            background: var(--bg-base);
            border: 1px solid var(--border-strong);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            font-size: .875rem;
            font-family: var(--font-body);
            padding: .65rem .9rem;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .field-input::placeholder { color: var(--text-dim); }

        .field-input:focus {
            border-color: rgba(79,142,247,0.5);
            box-shadow: 0 0 0 3px rgba(79,142,247,0.1);
        }

        .btn-sign-in {
            width: 100%;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: var(--radius-sm);
            padding: .7rem 1.25rem;
            font-size: .875rem;
            font-weight: 600;
            font-family: var(--font-body);
            cursor: pointer;
            transition: background 0.15s, box-shadow 0.15s, transform 0.1s;
            display: flex; align-items: center; justify-content: center; gap: .5rem;
            margin-top: 1.5rem;
            letter-spacing: 0.01em;
            text-decoration: none;
        }

        .btn-sign-in:hover {
            background: var(--primary-dark);
            box-shadow: 0 0 24px rgba(79,142,247,0.35);
            transform: translateY(-1px);
        }

        .btn-sign-in:active { transform: translateY(0); }

        .error-box {
            background: rgba(248,113,113,0.06);
            border: 1px solid rgba(248,113,113,0.2);
            border-radius: var(--radius-sm);
            padding: .65rem .9rem;
            font-size: .8rem;
            color: #FCA5A5;
            display: flex;
            align-items: center;
            gap: .5rem;
            margin-bottom: 1.25rem;
            font-family: var(--font-body);
        }

        .success-box {
            background: rgba(52,211,153,0.06);
            border: 1px solid rgba(52,211,153,0.2);
            border-radius: var(--radius-sm);
            padding: .65rem .9rem;
            font-size: .8rem;
            color: #6EE7B7;
            display: flex;
            align-items: center;
            gap: .5rem;
            margin-bottom: 1.25rem;
            font-family: var(--font-body);
        }

        .divider-line {
            border: none;
            border-top: 1px solid var(--border);
            margin: 1.5rem 0;
        }

        .signin-link {
            text-align: center;
            font-size: .78rem;
            color: var(--text-muted);
            font-family: var(--font-mono);
        }

        .signin-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .signin-link a:hover { text-decoration: underline; }

        .role-note {
            font-size: .72rem;
            color: var(--text-muted);
            font-family: var(--font-mono);
            text-align: center;
            margin-top: .75rem;
        }
        /* Compact authentication layout */
        :root {
            --primary: #0b62bd;
            --primary-dark: #084f9a;
            --bg-base: #f3f6fa;
            --bg-surface: #ffffff;
            --border-strong: #dce5f2;
            --text-primary: #252a31;
            --text-muted: #657181;
            --text-dim: #8a96a8;
            --radius-sm: 5px;
            --radius-xl: 14px;
            --font-display: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            --font-body: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            --font-mono: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        body {
            height: 100vh;
            min-height: 100vh;
            background:
                radial-gradient(circle at 50% 20%, rgba(207, 218, 231, .72), transparent 34%),
                linear-gradient(180deg, #f7f9fc 0%, #eef2f6 100%);
            overflow: hidden;
        }

        body::before,
        body::after {
            display: none;
        }

        .login-wrap {
            max-width: 450px;
            max-height: calc(100vh - 32px);
            padding: 16px;
        }

        .login-card {
            max-height: calc(100vh - 32px);
            padding: clamp(24px, 4vh, 32px);
            border-radius: 14px;
            box-shadow: 0 22px 56px rgba(24, 37, 56, .14);
            overflow: hidden;
        }

        .login-card::before {
            display: none;
        }

        .login-header {
            margin-bottom: 18px;
        }

        .login-logo {
            margin-bottom: 14px;
        }

        .logo-icon {
            width: 38px;
            height: 38px;
            border-radius: 9px;
            box-shadow: 0 12px 26px rgba(11, 98, 189, .25);
        }

        .logo-text {
            font-size: 1.35rem;
        }

        .login-heading {
            font-size: 1.45rem;
        }

        .login-sub {
            color: var(--text-muted);
            font-size: .9rem;
        }

        .field-group {
            margin-bottom: 12px;
        }

        .field-label {
            margin-bottom: 5px;
            font-size: .68rem;
            letter-spacing: .12em;
        }

        .field-input {
            height: 44px;
            padding: 0 14px;
            border-radius: 5px;
            background: #f4f6f9;
            font-size: .92rem;
        }

        .btn-sign-in {
            height: 44px;
            margin-top: 16px;
            padding: 0 18px;
            border-radius: 5px;
            box-shadow: none;
        }

        .btn-sign-in:hover {
            box-shadow: 0 10px 22px rgba(11, 98, 189, .2);
        }

        .role-note,
        .login-footer {
            margin-top: 10px;
            font-size: .8rem;
        }

        .error-box,
        .success-box {
            margin-bottom: 12px;
            padding: 10px 12px;
            font-size: .85rem;
        }

        @media (max-height: 700px) {
            .login-card {
                padding: 20px 24px;
            }

            .login-header {
                margin-bottom: 12px;
            }

            .login-logo {
                margin-bottom: 10px;
            }

            .login-heading {
                font-size: 1.32rem;
            }

            .field-input,
            .btn-sign-in {
                height: 40px;
            }
        }
    </style>
</head>
<body>
<div class="login-wrap">
    <div class="login-card">

        <div class="login-header">
            <div class="login-logo">
                <div class="logo-icon"><i class="bi bi-layers-fill"></i></div>
                <span class="logo-text">AdHub</span>
            </div>
            <h1 class="login-heading">Create account</h1>
            <p class="login-sub">Sign up to get started with AdHub</p>
        </div>

        <?php if ($error): ?>
            <div class="error-box">
                <i class="bi bi-exclamation-circle" style="flex-shrink:0;"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-box">
                <i class="bi bi-check-circle" style="flex-shrink:0;"></i>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST" novalidate autocomplete="off">

            <div class="field-group">
                <label class="field-label" for="name">Full name</label>
                <input
                    id="name"
                    type="text"
                    name="name"
                    class="field-input"
                    placeholder="Marcus Webb"
                    value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                    required
                    autofocus
                >
            </div>

            <div class="field-group">
                <label class="field-label" for="email">Email address</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    class="field-input"
                    placeholder="you@company.com"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required
                >
            </div>

            <div class="field-group">
                <label class="field-label" for="password">Password</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    class="field-input"
                    placeholder="Min. 8 characters"
                    required
                >
            </div>

            <div class="field-group">
                <label class="field-label" for="confirm_password">Confirm password</label>
                <input
                    id="confirm_password"
                    type="password"
                    name="confirm_password"
                    class="field-input"
                    placeholder="Re-enter password"
                    required
                >
            </div>

            <button type="submit" class="btn-sign-in">
                Create account <i class="bi bi-arrow-right"></i>
            </button>

            <p class="role-note">
                <i class="bi bi-info-circle"></i> New accounts are registered as client role.
            </p>

        </form>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/login.php" class="btn-sign-in">
                Go to Sign In <i class="bi bi-arrow-right"></i>
            </a>
        <?php endif; ?>

        <hr class="divider-line">

        <p class="signin-link">
            Already have an account? <a href="<?= BASE_URL ?>/login.php">Sign in</a>
        </p>

    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
