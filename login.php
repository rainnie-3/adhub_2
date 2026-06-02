<?php
/**
 * login.php
 * AdHub – Login / Register page (combined)
 */

session_start();

define('BASE_URL', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'));

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

redirectIfLoggedIn();

$login_error    = '';
$register_error = '';
$register_ok    = '';
$active_tab     = 'login';

// ── SIGN IN ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_form'] ?? '') === 'login') {
    $active_tab = 'login';
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        $login_error = 'Please enter your email and password.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            header('Location: ' . BASE_URL . ($user['role'] === 'admin' ? '/admin/dashboard.php' : '/client/campaigns.php'));
            exit;
        } else {
            $login_error = 'Invalid email address or password.';
        }
    }
}

// ── SIGN UP ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_form'] ?? '') === 'register') {
    $active_tab = 'register';
    $name     = trim($_POST['reg_name']             ?? '');
    $email    = trim($_POST['reg_email']            ?? '');
    $password = trim($_POST['reg_password']         ?? '');
    $confirm  = trim($_POST['reg_confirm_password'] ?? '');

    if (!$name || !$email || !$password || !$confirm) {
        $register_error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $register_error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $register_error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $register_error = 'Passwords do not match.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $register_error = 'That email address is already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'client')");
            $stmt->execute([$name, $email, $hash]);
            $register_ok = 'Account created! You can now sign in.';
            $active_tab  = 'login';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdHub – Sign In</title>
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
            top: -20%; left: 50%;
            transform: translateX(-50%);
            width: 600px; height: 400px;
            background: radial-gradient(ellipse, rgba(79,142,247,0.06) 0%, transparent 70%);
            pointer-events: none;
        }
        .login-wrap {
            position: relative; z-index: 1;
            width: 100%; max-width: 420px;
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
        .login-logo {
            display: flex; align-items: center; gap: .6rem;
            margin-bottom: 1.5rem;
        }
        .logo-icon {
            width: 34px; height: 34px;
            background: var(--primary);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; color: #fff;
            box-shadow: 0 0 20px rgba(79,142,247,0.4);
            flex-shrink: 0;
        }
        .logo-text {
            font-family: var(--font-display);
            font-weight: 700; font-size: 1.15rem;
            color: var(--text-primary);
            letter-spacing: -0.04em;
        }

        /* ── Tab switcher ── */
        .tab-bar {
            display: flex;
            background: var(--bg-base);
            border: 1px solid var(--border-strong);
            border-radius: var(--radius-sm);
            padding: 3px;
            margin-bottom: 1.75rem;
        }
        .tab-btn {
            flex: 1; text-align: center;
            padding: .45rem;
            font-size: .8rem; font-weight: 600;
            font-family: var(--font-body);
            border: none; background: none;
            border-radius: 5px;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.15s;
        }
        .tab-btn.active {
            background: var(--primary);
            color: #fff;
            box-shadow: 0 2px 8px rgba(79,142,247,0.3);
        }

        /* ── Panel ── */
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        .panel-heading {
            font-family: var(--font-display);
            font-size: 1.4rem; font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 .25rem;
            letter-spacing: -0.04em;
        }
        .panel-sub {
            font-size: .78rem; color: var(--text-muted);
            font-family: var(--font-mono);
            margin: 0 0 1.5rem;
        }

        .field-group { margin-bottom: 1rem; }
        .field-label {
            display: block;
            font-size: .65rem; font-weight: 600;
            color: var(--text-muted);
            letter-spacing: .1em; text-transform: uppercase;
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

        .btn-submit {
            width: 100%;
            background: var(--primary); color: #fff;
            border: none;
            border-radius: var(--radius-sm);
            padding: .7rem 1.25rem;
            font-size: .875rem; font-weight: 600;
            font-family: var(--font-body);
            cursor: pointer;
            transition: background 0.15s, box-shadow 0.15s, transform 0.1s;
            display: flex; align-items: center; justify-content: center; gap: .5rem;
            margin-top: 1.25rem;
        }
        .btn-submit:hover {
            background: var(--primary-dark);
            box-shadow: 0 0 24px rgba(79,142,247,0.35);
            transform: translateY(-1px);
        }
        .btn-submit:active { transform: translateY(0); }

        .alert-box {
            border-radius: var(--radius-sm);
            padding: .65rem .9rem;
            font-size: .8rem;
            display: flex; align-items: center; gap: .5rem;
            margin-bottom: 1.25rem;
            font-family: var(--font-body);
        }
        .alert-error {
            background: rgba(248,113,113,0.06);
            border: 1px solid rgba(248,113,113,0.2);
            color: #FCA5A5;
        }
        .alert-success {
            background: rgba(52,211,153,0.06);
            border: 1px solid rgba(52,211,153,0.2);
            color: #6EE7B7;
        }

        .divider-line {
            border: none;
            border-top: 1px solid var(--border);
            margin: 1.5rem 0;
        }
        .demo-box {
            background: rgba(79,142,247,0.04);
            border: 1px solid rgba(79,142,247,0.12);
            border-radius: var(--radius-sm);
            padding: .85rem 1rem;
        }
        .demo-label {
            font-size: .62rem; font-weight: 600;
            text-transform: uppercase; letter-spacing: .1em;
            color: var(--text-muted); font-family: var(--font-mono);
            margin-bottom: .55rem;
        }
        .demo-row {
            display: flex; align-items: center;
            justify-content: space-between;
            gap: .5rem; padding: .3rem 0;
        }
        .demo-row + .demo-row { border-top: 1px solid var(--border-subtle); }
        .demo-role { font-size: .7rem; color: var(--text-muted); font-family: var(--font-mono); min-width: 40px; }
        .demo-creds { font-size: .72rem; font-family: var(--font-mono); color: var(--primary); text-align: right; }
        .demo-creds span { color: var(--text-muted); margin: 0 .2rem; }

        .role-note {
            font-size: .72rem; color: var(--text-muted);
            font-family: var(--font-mono);
            text-align: center; margin-top: .75rem;
        }
    </style>
</head>
<body>
<div class="login-wrap">
    <div class="login-card">

        <div class="login-logo">
            <div class="logo-icon"><i class="bi bi-layers-fill"></i></div>
            <span class="logo-text">AdHub</span>
        </div>

        <!-- Tab buttons -->
        <div class="tab-bar">
            <button class="tab-btn <?= $active_tab === 'login'    ? 'active' : '' ?>" onclick="switchTab('login')">
                <i class="bi bi-box-arrow-in-right"></i> Sign In
            </button>
            <button class="tab-btn <?= $active_tab === 'register' ? 'active' : '' ?>" onclick="switchTab('register')">
                <i class="bi bi-person-plus"></i> Sign Up
            </button>
        </div>

        <!-- ── SIGN IN PANEL ── -->
        <div id="panel-login" class="tab-panel <?= $active_tab === 'login' ? 'active' : '' ?>">

            <p class="panel-heading">Welcome back</p>
            <p class="panel-sub">Sign in to your account to continue</p>

            <?php if ($register_ok): ?>
                <div class="alert-box alert-success">
                    <i class="bi bi-check-circle" style="flex-shrink:0"></i>
                    <?= htmlspecialchars($register_ok) ?>
                </div>
            <?php endif; ?>

            <?php if ($login_error): ?>
                <div class="alert-box alert-error">
                    <i class="bi bi-exclamation-circle" style="flex-shrink:0"></i>
                    <?= htmlspecialchars($login_error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate autocomplete="off">
                <input type="hidden" name="_form" value="login">
                <div class="field-group">
                    <label class="field-label" for="email">Email address</label>
                    <input id="email" type="email" name="email" class="field-input"
                        placeholder="you@company.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        required autofocus>
                </div>
                <div class="field-group">
                    <label class="field-label" for="password">Password</label>
                    <input id="password" type="password" name="password" class="field-input"
                        placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn-submit">
                    Sign In <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            <hr class="divider-line">

            <div class="demo-box">
                <div class="demo-label">Demo credentials</div>
                <div class="demo-row">
                    <span class="demo-role">Admin</span>
                    <span class="demo-creds">admin@adhub.com <span>/</span> Admin@1234</span>
                </div>
                <div class="demo-row">
                    <span class="demo-role">Client</span>
                    <span class="demo-creds">marcus@techcorp.com <span>/</span> Client@1234</span>
                </div>
            </div>
        </div>

        <!-- ── SIGN UP PANEL ── -->
        <div id="panel-register" class="tab-panel <?= $active_tab === 'register' ? 'active' : '' ?>">

            <p class="panel-heading">Create account</p>
            <p class="panel-sub">Sign up to get started with AdHub</p>

            <?php if ($register_error): ?>
                <div class="alert-box alert-error">
                    <i class="bi bi-exclamation-circle" style="flex-shrink:0"></i>
                    <?= htmlspecialchars($register_error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate autocomplete="off">
                <input type="hidden" name="_form" value="register">
                <div class="field-group">
                    <label class="field-label" for="reg_name">Full name</label>
                    <input id="reg_name" type="text" name="reg_name" class="field-input"
                        placeholder="Marcus Webb"
                        value="<?= htmlspecialchars($_POST['reg_name'] ?? '') ?>"
                        required>
                </div>
                <div class="field-group">
                    <label class="field-label" for="reg_email">Email address</label>
                    <input id="reg_email" type="email" name="reg_email" class="field-input"
                        placeholder="you@company.com"
                        value="<?= htmlspecialchars($_POST['reg_email'] ?? '') ?>"
                        required>
                </div>
                <div class="field-group">
                    <label class="field-label" for="reg_password">Password</label>
                    <input id="reg_password" type="password" name="reg_password" class="field-input"
                        placeholder="Min. 8 characters" required>
                </div>
                <div class="field-group">
                    <label class="field-label" for="reg_confirm_password">Confirm password</label>
                    <input id="reg_confirm_password" type="password" name="reg_confirm_password" class="field-input"
                        placeholder="Re-enter password" required>
                </div>
                <button type="submit" class="btn-submit">
                    Create account <i class="bi bi-arrow-right"></i>
                </button>
                <p class="role-note">
                    <i class="bi bi-info-circle"></i> New accounts are registered as client role.
                </p>
            </form>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach((btn, i) => {
        btn.classList.toggle('active', (tab === 'login' && i === 0) || (tab === 'register' && i === 1));
    });
    document.getElementById('panel-login').classList.toggle('active', tab === 'login');
    document.getElementById('panel-register').classList.toggle('active', tab === 'register');
}
</script>
</body>
</html>
