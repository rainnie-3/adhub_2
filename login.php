<?php
/**
 * login.php
 * AdHub - Login / Register page (combined)
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
        }

        $login_error = 'Invalid email address or password.';
    }
}

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
    <title>AdHub - Sign In</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --bg: #040916;
            --panel: rgba(15, 22, 39, .9);
            --panel-2: rgba(21, 31, 55, .74);
            --border: rgba(137, 158, 205, .24);
            --border-hi: rgba(91, 128, 237, .45);
            --text: #f7f9ff;
            --muted: #b8c2d8;
            --soft: #7f8ba5;
            --blue: #2e86ff;
            --blue-2: #365bff;
            --violet: #7c3ff2;
            --teal: #19d3bd;
            --orange: #d3842a;
            --danger: #fb7185;
            --success: #34d399;
        }

        * { box-sizing: border-box; }

        body {
            min-height: 100vh;
            margin: 0;
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 15% 16%, rgba(53, 91, 255, .26), transparent 28%),
                radial-gradient(circle at 62% 18%, rgba(34, 121, 255, .18), transparent 30%),
                linear-gradient(135deg, #050b1a 0%, #030711 54%, #071122 100%);
            overflow-x: hidden;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(84, 119, 210, .065) 1px, transparent 1px),
                linear-gradient(90deg, rgba(84, 119, 210, .065) 1px, transparent 1px);
            background-size: 58px 58px;
            mask-image: radial-gradient(circle at center, black, transparent 80%);
            pointer-events: none;
        }

        .page {
            position: relative;
            width: min(1490px, calc(100% - 24px));
            min-height: calc(100vh - 24px);
            margin: 12px auto;
            padding: clamp(26px, 4vw, 66px);
            border: 1px solid rgba(124, 157, 255, .18);
            border-radius: 26px;
            background: rgba(4, 9, 21, .74);
            box-shadow: 0 30px 90px rgba(0, 0, 0, .55), inset 0 1px 0 rgba(255, 255, 255, .04);
            overflow: hidden;
        }

        .layout {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) minmax(390px, 530px);
            gap: clamp(38px, 6vw, 88px);
            align-items: center;
            min-height: calc(100vh - 150px);
        }

        .brand,
        .auth-brand {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .brand { margin-bottom: 40px; }
        .auth-brand { justify-content: center; gap: 14px; margin-bottom: 24px; }

        .mark {
            position: relative;
            width: 64px;
            height: 58px;
            flex: 0 0 auto;
        }

        .auth-brand .mark {
            width: 50px;
            height: 45px;
        }

        .mark span {
            position: absolute;
            left: 8px;
            width: 48px;
            height: 22px;
            border-radius: 7px;
            transform: rotate(30deg) skewX(-16deg);
            background: linear-gradient(135deg, #7a62ff, #168dff);
            box-shadow: 0 14px 26px rgba(45, 100, 255, .36);
        }

        .auth-brand .mark span {
            left: 6px;
            width: 38px;
            height: 17px;
            border-radius: 5px;
        }

        .mark span:nth-child(1) { top: 3px; }
        .mark span:nth-child(2) { top: 18px; background: linear-gradient(135deg, #335dff, #15a9ff); }
        .mark span:nth-child(3) { top: 33px; background: linear-gradient(135deg, #2455d9, #2b7cff); }
        .auth-brand .mark span:nth-child(1) { top: 2px; }
        .auth-brand .mark span:nth-child(2) { top: 14px; }
        .auth-brand .mark span:nth-child(3) { top: 26px; }

        .brand-name {
            font-size: clamp(2.15rem, 4vw, 3rem);
            font-weight: 800;
            letter-spacing: -0.05em;
        }

        .hero h1 {
            max-width: 660px;
            margin: 0;
            font-size: clamp(2.25rem, 4.5vw, 4rem);
            line-height: 1.12;
            letter-spacing: -0.045em;
        }

        .hero h1 span { color: #3f7cff; }

        .hero-lead {
            max-width: 480px;
            margin: 22px 0 44px;
            color: #c8d0e2;
            font-size: 1.08rem;
            line-height: 1.75;
        }

        .hero-lead a {
            color: #69a2ff;
            border-bottom: 2px solid rgba(105, 162, 255, .7);
            text-decoration: none;
        }

        .showcase {
            display: grid;
            grid-template-columns: minmax(265px, 360px) minmax(320px, 1fr);
            gap: 30px;
            align-items: center;
        }

        .features {
            position: relative;
            display: grid;
            gap: 34px;
            padding-left: 42px;
        }

        .features::before {
            content: "";
            position: absolute;
            left: 31px;
            top: 20px;
            bottom: 20px;
            border-left: 1px dashed rgba(68, 142, 255, .52);
        }

        .feature {
            position: relative;
            display: grid;
            grid-template-columns: 74px 1fr;
            gap: 20px;
            align-items: center;
        }

        .feature::before {
            content: "";
            position: absolute;
            left: -14px;
            width: 8px;
            height: 8px;
            border-radius: 99px;
            background: var(--blue);
            box-shadow: 0 0 18px var(--blue);
        }

        .feature-icon {
            width: 74px;
            height: 74px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            color: #fff;
            font-size: 2rem;
            box-shadow: inset 0 0 0 10px rgba(255, 255, 255, .05), 0 14px 34px rgba(0, 0, 0, .36);
        }

        .feature:nth-child(1) .feature-icon { background: linear-gradient(135deg, #7b43ff, #4f42ff); }
        .feature:nth-child(2) .feature-icon { background: linear-gradient(135deg, #1745d9, #238dff); }
        .feature:nth-child(3) .feature-icon { background: linear-gradient(135deg, #16b8a4, #2ee4c0); }
        .feature:nth-child(4) .feature-icon { background: linear-gradient(135deg, #a75e21, #e99a34); }

        .feature h3,
        .value h3 {
            margin: 0 0 8px;
            font-size: 1rem;
        }

        .feature p,
        .value p {
            margin: 0;
            color: var(--muted);
            line-height: 1.55;
            font-size: .9rem;
        }

        .dashboard {
            position: relative;
            min-height: 550px;
        }

        .dash-card {
            position: absolute;
            top: 20px;
            left: 0;
            width: min(530px, 100%);
            padding: 22px;
            border: 1px solid rgba(93, 111, 209, .42);
            border-radius: 18px;
            background: linear-gradient(145deg, rgba(17, 18, 46, .95), rgba(10, 18, 39, .9));
            box-shadow: 0 28px 70px rgba(0, 0, 0, .45), inset 0 1px 0 rgba(255, 255, 255, .05);
            transform: perspective(1200px) rotateY(-14deg) rotateX(3deg);
        }

        .dash-head {
            display: flex;
            justify-content: space-between;
            margin-bottom: 16px;
            color: #dce5ff;
            font-size: .82rem;
            font-weight: 700;
        }

        .dash-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .metric,
        .chart,
        .traffic,
        .bars,
        .map {
            border: 1px solid rgba(114, 137, 232, .2);
            border-radius: 10px;
            background: rgba(18, 26, 52, .78);
        }

        .metric { padding: 14px; }
        .metric small { display: block; color: var(--muted); font-size: .62rem; }
        .metric strong { display: block; margin-top: 8px; font-size: 1.42rem; }
        .metric div { height: 4px; margin-top: 12px; border-radius: 99px; background: linear-gradient(90deg, #375bff, #23d8bf); }

        .chart,
        .traffic,
        .bars,
        .map { padding: 16px; }

        .chart { grid-column: span 2; height: 178px; }
        .chart-lines {
            position: relative;
            height: 118px;
            margin-top: 14px;
            border-radius: 8px;
            overflow: hidden;
            background:
                linear-gradient(rgba(255,255,255,.045) 1px, transparent 1px) 0 0 / 100% 24px,
                linear-gradient(90deg, rgba(255,255,255,.045) 1px, transparent 1px) 0 0 / 64px 100%;
        }

        .chart-lines::after {
            content: "";
            position: absolute;
            inset: 18px 10px 20px;
            background: linear-gradient(135deg, transparent 10%, #2990ff 11%, #2990ff 13%, transparent 14%, transparent 34%, #2990ff 35%, #2990ff 38%, transparent 39%, transparent 62%, #2990ff 63%, #2990ff 66%, transparent 67%);
            filter: drop-shadow(0 0 10px rgba(41, 144, 255, .8));
        }

        .traffic { height: 178px; }
        .donut {
            width: 96px;
            height: 96px;
            margin: 22px auto 0;
            border-radius: 50%;
            background: conic-gradient(#245aff 0 42%, #23a8ff 42% 70%, #fb8235 70% 84%, #1fd0aa 84%);
            box-shadow: 0 0 24px rgba(35, 168, 255, .2);
        }

        .bars {
            grid-column: span 2;
            display: grid;
            gap: 12px;
        }

        .bar {
            display: grid;
            grid-template-columns: 22px 1fr 56px;
            gap: 10px;
            align-items: center;
            font-size: .65rem;
        }

        .bar i { color: #ff7188; }
        .track { height: 5px; border-radius: 99px; background: rgba(255,255,255,.1); overflow: hidden; }
        .track span { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #316dff, #1dd5be); }

        .map {
            position: relative;
            height: 180px;
            overflow: hidden;
        }

        .map::after {
            content: "";
            position: absolute;
            inset: 50px 22px 28px;
            background:
                radial-gradient(circle at 23% 48%, #1b7dff 0 5px, transparent 6px),
                radial-gradient(circle at 56% 39%, #1b7dff 0 5px, transparent 6px),
                radial-gradient(circle at 72% 62%, #1b7dff 0 4px, transparent 5px),
                linear-gradient(135deg, rgba(105, 125, 169, .42), rgba(105, 125, 169, .08));
            clip-path: polygon(2% 35%, 14% 18%, 30% 28%, 42% 12%, 58% 24%, 78% 12%, 98% 34%, 86% 70%, 62% 62%, 49% 84%, 30% 68%, 12% 78%);
            filter: drop-shadow(0 0 14px rgba(30, 120, 255, .62));
        }

        .laptop {
            position: absolute;
            right: 16px;
            bottom: 6px;
            width: 330px;
            height: 130px;
            border-radius: 12px 12px 4px 4px;
            background: linear-gradient(135deg, #68729a, #151b34 58%);
            box-shadow: 0 28px 38px rgba(0, 0, 0, .45);
            transform: rotate(-12deg);
        }

        .laptop::after {
            content: "";
            position: absolute;
            left: -34px;
            right: 16px;
            bottom: -30px;
            height: 34px;
            border-radius: 0 0 18px 18px;
            background: linear-gradient(90deg, #29324f, #a7b0d4 54%, #202741);
        }

        .values {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            width: min(820px, 100%);
            margin-top: 32px;
            border: 1px solid rgba(74, 111, 214, .34);
            border-radius: 16px;
            background: rgba(10, 17, 34, .72);
            overflow: hidden;
        }

        .value {
            display: grid;
            grid-template-columns: 46px 1fr;
            gap: 16px;
            padding: 24px;
            border-right: 1px solid rgba(147, 166, 205, .13);
        }

        .value:last-child { border-right: 0; }
        .value i { color: #5c84ff; font-size: 2rem; }

        .card {
            width: 100%;
            border: 1px solid var(--border-hi);
            border-radius: 18px;
            padding: clamp(28px, 4vw, 42px);
            background: linear-gradient(150deg, rgba(21, 28, 48, .94), rgba(9, 14, 27, .92));
            box-shadow: 0 24px 70px rgba(0, 0, 0, .42), inset 0 1px 0 rgba(255, 255, 255, .05);
        }

        .auth-brand strong {
            font-size: 2.35rem;
            letter-spacing: -.05em;
        }

        .auth-title {
            margin: 0;
            color: #4d87ff;
            text-align: center;
            font-size: 1.32rem;
            font-weight: 800;
        }

        .auth-subtitle {
            margin: 8px 0 34px;
            color: #c2cadb;
            text-align: center;
            font-size: 1rem;
        }

        .tabs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            margin-bottom: 30px;
            border: 1px solid rgba(147, 166, 205, .14);
            border-radius: 10px;
            overflow: hidden;
            background: rgba(24, 33, 55, .75);
        }

        .tab-btn {
            min-height: 56px;
            border: 0;
            color: #eef3ff;
            background: transparent;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            transition: background .18s, box-shadow .18s;
        }

        .tab-btn i {
            margin-right: 10px;
            color: #98a8c6;
            font-size: 1.18rem;
            vertical-align: -2px;
        }

        .tab-btn.active {
            background: linear-gradient(180deg, rgba(49, 76, 132, .35), rgba(27, 41, 76, .9));
            box-shadow: inset 0 -3px 0 #2d7dff;
        }

        .tab-btn.active i { color: #3f8cff; }

        .panel { display: none; }
        .panel.active { display: block; }

        .alert-box {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            margin-bottom: 18px;
            padding: 13px 15px;
            border-radius: 10px;
            font-size: .92rem;
        }

        .alert-error {
            color: #fecdd3;
            background: rgba(251, 113, 133, .1);
            border: 1px solid rgba(251, 113, 133, .28);
        }

        .alert-success {
            color: #bbf7d0;
            background: rgba(34, 197, 94, .1);
            border: 1px solid rgba(34, 197, 94, .26);
        }

        .field { margin-bottom: 24px; }

        .field label {
            display: block;
            margin-bottom: 10px;
            color: #fff;
            font-size: .94rem;
            font-weight: 700;
        }

        .input {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input > i {
            position: absolute;
            left: 18px;
            color: #a9b5cc;
            font-size: 1.2rem;
            pointer-events: none;
        }

        .input input {
            width: 100%;
            height: 56px;
            border: 1px solid rgba(147, 166, 205, .25);
            border-radius: 10px;
            background: rgba(25, 34, 53, .72);
            color: #fff;
            outline: none;
            padding: 0 52px;
            font: inherit;
            font-size: 1rem;
            transition: border-color .18s, box-shadow .18s, background .18s;
        }

        .input input::placeholder { color: #9ca8bf; }

        .input input:focus {
            border-color: rgba(65, 135, 255, .82);
            background: rgba(27, 38, 61, .95);
            box-shadow: 0 0 0 4px rgba(46, 126, 255, .13);
        }

        .toggle-pass {
            position: absolute;
            right: 14px;
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            border: 0;
            border-radius: 8px;
            color: #c6cee0;
            background: transparent;
            cursor: pointer;
        }

        .toggle-pass:hover {
            color: #fff;
            background: rgba(255, 255, 255, .06);
        }

        .form-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin: -2px 0 28px;
            color: #bdc6d9;
        }

        .check {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            user-select: none;
        }

        .check input {
            appearance: none;
            width: 18px;
            height: 18px;
            border: 1px solid #78849e;
            border-radius: 5px;
            background: transparent;
        }

        .check input:checked {
            border-color: #2d83ff;
            background: #2d83ff;
            box-shadow: inset 0 0 0 3px #11192f;
        }

        .forgot {
            color: #4b8bff;
            text-decoration: none;
            font-weight: 500;
        }

        .forgot:hover { text-decoration: underline; }

        .submit {
            width: 100%;
            height: 56px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
            border: 0;
            border-radius: 10px;
            color: #fff;
            background: linear-gradient(90deg, #238cff, #3758ff 55%, #853ff2);
            font: inherit;
            font-size: 1rem;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 18px 34px rgba(42, 119, 255, .2);
            transition: transform .16s, box-shadow .16s, filter .16s;
        }

        .submit:hover {
            transform: translateY(-1px);
            filter: brightness(1.06);
            box-shadow: 0 24px 42px rgba(42, 119, 255, .27);
        }

        .submit span { margin-left: auto; }
        .submit i { margin-left: auto; padding-right: 18px; }

        .role-note {
            margin: 16px 0 0;
            color: #9ca8bf;
            text-align: center;
            font-size: .9rem;
        }

        @media (max-width: 1180px) {
            .layout { grid-template-columns: 1fr; }
            .card { max-width: 560px; margin: 0 auto; }
            .dashboard { display: none; }
            .showcase { grid-template-columns: 1fr; }
            .values { grid-template-columns: 1fr; }
            .value {
                border-right: 0;
                border-bottom: 1px solid rgba(147, 166, 205, .13);
            }
            .value:last-child { border-bottom: 0; }
        }

        @media (max-width: 720px) {
            .page {
                width: 100%;
                min-height: 100vh;
                margin: 0;
                border: 0;
                border-radius: 0;
                padding: 24px 16px;
            }

            .brand { margin-bottom: 28px; }
            .features { padding-left: 0; }
            .features::before,
            .feature::before { display: none; }
            .feature { grid-template-columns: 58px 1fr; gap: 14px; }
            .feature-icon { width: 58px; height: 58px; font-size: 1.55rem; }
            .card { padding: 24px 16px; }
            .auth-brand strong { font-size: 1.9rem; }
            .tab-btn { min-height: 52px; font-size: .94rem; }
            .form-row { align-items: flex-start; flex-direction: column; gap: 12px; }
        }
        /* White login theme */
        :root {
            --bg: #f3f6fa;
            --panel: #ffffff;
            --panel-2: #f4f6f9;
            --border: #dce5f2;
            --border-hi: #ffffff;
            --text: #252a31;
            --muted: #657181;
            --soft: #788390;
            --blue: #0b62bd;
            --blue-2: #0b62bd;
            --violet: #0b62bd;
            --danger: #dc3545;
            --success: #198754;
        }

        body {
            height: 100vh;
            max-height: 100vh;
            color: var(--text);
            background:
                radial-gradient(circle at 50% 20%, rgba(207, 218, 231, .7), transparent 34%),
                linear-gradient(180deg, #f7f9fc 0%, #eef2f6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        body::before {
            display: none;
        }

        .page {
            width: auto;
            min-height: 0;
            max-height: 100vh;
            margin: 0;
            padding: 0;
            border: 0;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
            overflow: visible;
        }

        .layout {
            display: block;
            min-height: 0;
        }

        .hero {
            display: none;
        }

        .card {
            width: min(465px, calc(100vw - 32px));
            max-height: calc(100vh - 32px);
            border: 0;
            border-radius: 0;
            padding: clamp(28px, 4vh, 44px) 40px clamp(30px, 4vh, 46px);
            background: #ffffff;
            box-shadow: 0 28px 70px rgba(24, 37, 56, .24);
        }

        .auth-brand {
            margin-bottom: clamp(18px, 3vh, 28px);
        }

        .auth-brand .mark {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: #0b62bd;
            box-shadow: 0 12px 26px rgba(11, 98, 189, .25);
        }

        .auth-brand .mark span {
            left: 11px;
            width: 20px;
            height: 9px;
            border-radius: 3px;
            background: #ffffff;
            box-shadow: none;
            transform: rotate(30deg) skewX(-18deg);
        }

        .auth-brand .mark span:nth-child(1) {
            top: 13px;
            opacity: .95;
        }

        .auth-brand .mark span:nth-child(2) {
            top: 17px;
            background: #ffffff;
            opacity: .72;
        }

        .auth-brand .mark span:nth-child(3) {
            top: 21px;
            background: #ffffff;
            opacity: .95;
        }

        .auth-brand strong {
            color: #252a31;
            font-size: 1.45rem;
            letter-spacing: -.035em;
        }

        .auth-title {
            margin-top: clamp(22px, 4vh, 38px);
            color: #252a31;
            text-align: left;
            font-size: 1.75rem;
            letter-spacing: -.04em;
        }

        .auth-subtitle {
            margin: 10px 0 clamp(22px, 3.5vh, 34px);
            color: #657181;
            text-align: left;
            font-size: .98rem;
        }

        .tabs {
            margin-bottom: clamp(24px, 4vh, 40px);
            border: 0;
            border-radius: 5px;
            background: #f0f2f5;
            padding: 4px;
        }

        .tab-btn {
            min-height: 42px;
            border-radius: 5px;
            color: #6b7480;
            background: transparent;
            box-shadow: none;
            font-size: .95rem;
        }

        .tab-btn i {
            color: inherit;
            font-size: .95rem;
        }

        .tab-btn.active {
            color: #ffffff;
            background: #0b62bd;
            box-shadow: 0 4px 12px rgba(11, 98, 189, .26);
        }

        .tab-btn.active i {
            color: #ffffff;
        }

        .alert-error {
            color: #842029;
            background: #f8d7da;
            border-color: #f5c2c7;
        }

        .alert-success {
            color: #0f5132;
            background: #d1e7dd;
            border-color: #badbcc;
        }

        .field {
            margin-bottom: clamp(14px, 2.4vh, 20px);
        }

        .field label {
            margin-bottom: 8px;
            color: #6b7480;
            font-size: .76rem;
            font-weight: 700;
            letter-spacing: .14em;
            text-transform: uppercase;
        }

        .input > i,
        .toggle-pass {
            display: none;
        }

        .input input {
            height: clamp(48px, 6vh, 58px);
            border: 1px solid transparent;
            border-radius: 5px;
            background: #f1f3f6;
            color: #252a31;
            padding: 0 18px;
            box-shadow: none;
        }

        .input input::placeholder {
            color: #252a31;
            opacity: 1;
        }

        .input input:focus {
            border-color: #dbe8fb;
            background: #f4f7fb;
            box-shadow: 0 0 0 3px #e8f1ff;
        }

        .form-row {
            display: none;
        }

        .submit {
            height: clamp(48px, 6vh, 55px);
            margin-top: 4px;
            border-radius: 5px;
            background: #0b62bd;
            box-shadow: none;
        }

        .submit:hover {
            filter: none;
            background: #084f9a;
            box-shadow: 0 10px 22px rgba(11, 98, 189, .2);
        }

        .submit span {
            margin-left: auto;
        }

        .submit i {
            margin-left: 0;
            padding-right: 0;
            margin-right: auto;
        }

        .role-note {
            color: #657181;
        }

        @media (max-width: 720px) {
            body {
                align-items: stretch;
            }

            .page {
                width: 100%;
                height: 100vh;
                max-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 16px;
            }

            .card {
                width: 100%;
                max-height: calc(100vh - 32px);
                padding: 24px;
            }
        }

        /* Keep the original two-column design, but use a white color theme. */
        body {
            height: 100vh;
            max-height: 100vh;
            color: #252a31;
            background:
                radial-gradient(circle at 24% 18%, rgba(11, 98, 189, .12), transparent 30%),
                radial-gradient(circle at 72% 22%, rgba(56, 132, 255, .12), transparent 32%),
                linear-gradient(135deg, #f8fafc 0%, #eef3f8 100%);
            display: block;
            overflow: hidden;
        }

        body::before {
            display: block;
            background-image:
                linear-gradient(rgba(11, 98, 189, .05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(11, 98, 189, .05) 1px, transparent 1px);
            background-size: 58px 58px;
            mask-image: radial-gradient(circle at center, black, transparent 80%);
        }

        .page {
            width: min(1490px, calc(100% - 24px));
            height: calc(100vh - 24px);
            min-height: 0;
            max-height: calc(100vh - 24px);
            margin: 12px auto;
            padding: clamp(20px, 3vw, 42px);
            border: 1px solid #dce5f2;
            border-radius: 26px;
            background: rgba(255, 255, 255, .74);
            box-shadow: 0 28px 70px rgba(24, 37, 56, .14);
            overflow: hidden;
        }

        .layout {
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) minmax(390px, 530px);
            gap: clamp(28px, 4vw, 70px);
            align-items: center;
            min-height: 0;
            height: 100%;
        }

        .hero {
            display: block;
        }

        .brand-name,
        .hero h1,
        .feature h3,
        .value h3,
        .dash-card strong,
        .metric strong,
        .auth-brand strong,
        .auth-title,
        .field label {
            color: #252a31;
        }

        .hero h1 span,
        .hero-lead a,
        .forgot {
            color: #0b62bd;
        }

        .hero-lead,
        .feature p,
        .value p,
        .metric small,
        .auth-subtitle,
        .role-note {
            color: #657181;
        }

        .showcase {
            grid-template-columns: minmax(245px, 330px) minmax(300px, 1fr);
            gap: 24px;
        }

        .features {
            gap: 24px;
        }

        .feature {
            grid-template-columns: 64px 1fr;
        }

        .feature-icon {
            width: 64px;
            height: 64px;
            font-size: 1.7rem;
        }

        .dashboard {
            min-height: 430px;
        }

        .dash-card {
            width: min(470px, 100%);
            padding: 18px;
            border-color: #d7e2f1;
            background: rgba(255, 255, 255, .88);
            box-shadow: 0 22px 45px rgba(24, 37, 56, .16);
        }

        .metric,
        .chart,
        .traffic,
        .bars,
        .map {
            border-color: #e1e8f2;
            background: #f8fafc;
        }

        .chart-lines {
            background:
                linear-gradient(rgba(11, 98, 189, .08) 1px, transparent 1px) 0 0 / 100% 24px,
                linear-gradient(90deg, rgba(11, 98, 189, .08) 1px, transparent 1px) 0 0 / 64px 100%;
        }

        .values {
            margin-top: 22px;
            border-color: #dce5f2;
            background: rgba(255, 255, 255, .8);
        }

        .value {
            padding: 18px;
            border-color: #e6edf6;
        }

        .card {
            width: 100%;
            max-height: calc(100vh - 72px);
            border: 1px solid #dce5f2;
            border-radius: 18px;
            padding: clamp(24px, 3vh, 34px);
            background: rgba(255, 255, 255, .95);
            box-shadow: 0 24px 58px rgba(24, 37, 56, .16);
            overflow: hidden;
        }

        .auth-brand .mark {
            background: #0b62bd;
        }

        .tabs {
            border: 0;
            background: #f0f2f5;
        }

        .tab-btn {
            color: #6b7480;
        }

        .tab-btn.active {
            color: #ffffff;
            background: #0b62bd;
            box-shadow: 0 4px 12px rgba(11, 98, 189, .24);
        }

        .input > i {
            display: block;
            color: #8a96a8;
        }

        .toggle-pass {
            display: grid;
            color: #8a96a8;
        }

        .input input {
            border-color: #dce5f2;
            background: #f4f6f9;
            color: #252a31;
            padding: 0 52px;
        }

        .input input::placeholder {
            color: #7b8798;
        }

        .input input:focus {
            border-color: #bcd4f5;
            background: #ffffff;
            box-shadow: 0 0 0 3px #e8f1ff;
        }

        .form-row {
            display: flex;
            color: #657181;
        }

        .submit {
            background: #0b62bd;
        }

        .submit:hover {
            background: #084f9a;
        }

        .laptop {
            background: linear-gradient(135deg, #d9e2ef, #6f7d93 58%);
            box-shadow: 0 18px 28px rgba(24, 37, 56, .18);
        }

        .laptop::after {
            background: linear-gradient(90deg, #8d9bb1, #eef3f8 54%, #9ca9bc);
        }

        @media (max-width: 1180px) {
            .layout {
                grid-template-columns: 1fr;
            }

            .dashboard {
                display: none;
            }
        }

        @media (max-width: 720px) {
            body {
                display: block;
            }

            .page {
                width: 100%;
                height: 100vh;
                max-height: 100vh;
                margin: 0;
                padding: 14px;
                border-radius: 0;
            }

            .layout {
                display: block;
            }

            .hero {
                display: none;
            }

            .card {
                max-height: calc(100vh - 28px);
                padding: 22px;
            }
        }

        /* Spacing cleanup: keep the white design, remove the gray overlap. */
        .hero {
            min-width: 0;
        }

        .brand {
            margin-bottom: clamp(18px, 3vh, 30px);
        }

        .hero h1 {
            font-size: clamp(2rem, 3.6vw, 3.25rem);
            line-height: 1.08;
        }

        .hero-lead {
            margin: clamp(12px, 2vh, 18px) 0 clamp(18px, 3vh, 30px);
            line-height: 1.55;
        }

        .showcase {
            grid-template-columns: minmax(245px, 320px) minmax(360px, 500px);
            gap: clamp(18px, 3vw, 34px);
            align-items: center;
        }

        .features {
            gap: clamp(16px, 2.4vh, 24px);
        }

        .dashboard {
            min-height: auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .dash-card {
            position: relative;
            top: auto;
            left: auto;
            width: min(500px, 100%);
            transform: perspective(1200px) rotateY(-8deg) rotateX(2deg);
            z-index: 2;
        }

        .laptop {
            display: none;
        }

        .values {
            margin-top: clamp(16px, 2.5vh, 24px);
        }

        .value {
            padding: clamp(14px, 2vh, 18px);
        }

        .card {
            align-self: center;
        }

        @media (max-height: 780px) {
            .brand-name {
                font-size: 2.25rem;
            }

            .brand .mark {
                width: 52px;
                height: 48px;
            }

            .brand .mark span {
                left: 7px;
                width: 40px;
                height: 18px;
            }

            .brand .mark span:nth-child(1) { top: 2px; }
            .brand .mark span:nth-child(2) { top: 15px; }
            .brand .mark span:nth-child(3) { top: 28px; }

            .feature {
                grid-template-columns: 56px 1fr;
            }

            .feature-icon {
                width: 56px;
                height: 56px;
                font-size: 1.45rem;
            }

            .feature p,
            .value p {
                font-size: .82rem;
            }

            .dash-card {
                width: min(455px, 100%);
                padding: 14px;
                transform: perspective(1200px) rotateY(-6deg) rotateX(1deg);
            }

            .chart,
            .traffic {
                height: 145px;
            }

            .chart-lines {
                height: 88px;
            }

            .donut {
                width: 74px;
                height: 74px;
                margin-top: 14px;
            }

            .map {
                height: 140px;
            }
        }

        /* Desktop: use the full viewport. */
        @media (min-width: 1181px) {
            .page {
                width: 100vw;
                height: 100vh;
                max-height: 100vh;
                margin: 0;
                border-radius: 0;
                border: 0;
                padding: clamp(22px, 3vw, 46px);
            }

            .layout {
                height: 100%;
                grid-template-columns: minmax(0, 1.42fr) minmax(390px, 520px);
                gap: clamp(34px, 5vw, 82px);
            }

            .card {
                max-height: calc(100vh - 72px);
            }
        }

        /* Final desktop proportion pass */
        @media (min-width: 1181px) {
            body {
                min-height: 100vh;
                height: 100vh;
                overflow: hidden;
            }

            .page {
                width: min(1360px, calc(100vw - 48px));
                height: min(760px, calc(100vh - 48px));
                max-height: calc(100vh - 48px);
                margin: 24px auto;
                padding: clamp(24px, 2.5vw, 36px);
                border: 1px solid #dce5f2;
                border-radius: 18px;
                background: rgba(255, 255, 255, .78);
                box-shadow: 0 22px 56px rgba(24, 37, 56, .14);
            }

            .layout {
                height: 100%;
                grid-template-columns: minmax(0, 1fr) minmax(400px, 460px);
                gap: clamp(28px, 4vw, 56px);
                align-items: center;
            }

            .brand {
                margin-bottom: 20px;
            }

            .brand .mark {
                width: 48px;
                height: 42px;
            }

            .brand .mark span {
                left: 6px;
                width: 38px;
                height: 17px;
                border-radius: 5px;
            }

            .brand .mark span:nth-child(1) { top: 1px; }
            .brand .mark span:nth-child(2) { top: 13px; }
            .brand .mark span:nth-child(3) { top: 25px; }

            .brand-name {
                font-size: 2.15rem;
            }

            .hero h1 {
                max-width: 560px;
                font-size: clamp(2rem, 2.8vw, 2.75rem);
                line-height: 1.12;
            }

            .hero-lead {
                max-width: 430px;
                margin: 12px 0 22px;
                font-size: .98rem;
            }

            .showcase {
                grid-template-columns: minmax(250px, 315px) minmax(330px, 455px);
                gap: 24px;
            }

            .features {
                gap: 18px;
                padding-left: 32px;
            }

            .features::before {
                left: 24px;
            }

            .feature {
                grid-template-columns: 54px 1fr;
                gap: 14px;
            }

            .feature::before {
                left: -12px;
            }

            .feature-icon {
                width: 54px;
                height: 54px;
                font-size: 1.35rem;
            }

            .feature h3,
            .value h3 {
                margin-bottom: 4px;
                font-size: .92rem;
            }

            .feature p,
            .value p {
                font-size: .8rem;
                line-height: 1.45;
            }

            .dash-card {
                width: min(430px, 100%);
                padding: 14px;
                transform: perspective(1200px) rotateY(-5deg) rotateX(1deg);
            }

            .dash-head {
                margin-bottom: 10px;
                font-size: .72rem;
            }

            .dash-grid {
                gap: 8px;
            }

            .metric {
                padding: 10px;
            }

            .metric strong {
                font-size: 1.1rem;
            }

            .metric div {
                margin-top: 8px;
            }

            .chart,
            .traffic {
                height: 132px;
                padding: 10px;
            }

            .chart-lines {
                height: 78px;
                margin-top: 9px;
            }

            .donut {
                width: 66px;
                height: 66px;
                margin-top: 12px;
            }

            .bars {
                gap: 8px;
                padding: 10px;
            }

            .bar {
                grid-template-columns: 18px 1fr 48px;
                gap: 7px;
            }

            .map {
                height: 124px;
                padding: 10px;
            }

            .values {
                width: min(760px, 100%);
                margin-top: 18px;
            }

            .value {
                grid-template-columns: 34px 1fr;
                gap: 12px;
                padding: 14px;
            }

            .value i {
                font-size: 1.45rem;
            }

            .card {
                width: min(460px, 100%);
                max-height: calc(100vh - 96px);
                justify-self: center;
                padding: 28px 30px;
                border-radius: 14px;
                overflow: hidden;
            }

            .auth-brand {
                margin-bottom: 18px;
            }

            .auth-brand .mark {
                width: 38px;
                height: 38px;
                border-radius: 9px;
            }

            .auth-brand strong {
                font-size: 1.35rem;
            }

            .auth-title {
                margin-top: 24px;
                font-size: 1.55rem;
            }

            .auth-subtitle {
                margin-bottom: 22px;
                font-size: .92rem;
            }

            .tabs {
                margin-bottom: 24px;
            }

            .tab-btn {
                min-height: 40px;
                font-size: .9rem;
            }

            .field {
                margin-bottom: 16px;
            }

            .field label {
                margin-bottom: 7px;
                font-size: .72rem;
            }

            .input input {
                height: 48px;
                font-size: .92rem;
            }

            .form-row {
                margin: -2px 0 20px;
                font-size: .9rem;
            }

            .submit {
                height: 48px;
                font-size: .95rem;
            }

            .role-note {
                margin-top: 12px;
                font-size: .82rem;
            }
        }

        @media (min-width: 1181px) and (max-width: 1400px) {
            .page {
                width: min(1240px, calc(100vw - 32px));
                height: min(720px, calc(100vh - 32px));
                margin: 16px auto;
                padding: 24px;
            }

            .layout {
                grid-template-columns: minmax(0, 1fr) minmax(390px, 440px);
                gap: 32px;
            }

            .showcase {
                grid-template-columns: minmax(230px, 295px) minmax(300px, 410px);
            }

            .dash-card {
                width: min(400px, 100%);
            }

            .values {
                width: min(700px, 100%);
            }

            .card {
                width: min(440px, 100%);
                padding: 24px 26px;
            }
        }

        @media (max-height: 740px) and (min-width: 1181px) {
            .page {
                height: calc(100vh - 24px);
                margin-top: 12px;
                margin-bottom: 12px;
                padding-top: 18px;
                padding-bottom: 18px;
            }

            .values {
                display: none;
            }

            .hero-lead {
                margin-bottom: 16px;
            }

            .features {
                gap: 14px;
            }

            .chart,
            .traffic {
                height: 118px;
            }

            .chart-lines {
                height: 66px;
            }

            .map {
                height: 108px;
            }

            .card {
                padding-top: 22px;
                padding-bottom: 22px;
            }
        }

        /* Auth cleanup: no lower feature strip and compact sign-up state. */
        .hero {
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 0;
        }

        .showcase {
            margin-top: 0;
        }

        .card {
            display: flex;
            flex-direction: column;
        }

        .panel.active {
            min-height: 0;
        }

        .card.is-registering {
            padding-top: clamp(20px, 2.5vh, 28px);
            padding-bottom: clamp(20px, 2.5vh, 28px);
        }

        .card.is-registering .auth-brand {
            margin-bottom: 12px;
        }

        .card.is-registering .auth-title {
            margin-top: 14px;
            font-size: 1.42rem;
        }

        .card.is-registering .auth-subtitle {
            margin-bottom: 16px;
        }

        .card.is-registering .tabs {
            margin-bottom: 16px;
        }

        .card.is-registering .field {
            margin-bottom: 12px;
        }

        .card.is-registering .field label {
            margin-bottom: 5px;
            font-size: .68rem;
        }

        .card.is-registering .input input {
            height: 44px;
        }

        .card.is-registering .submit {
            height: 44px;
            margin-top: 2px;
        }

        .card.is-registering .role-note {
            margin-top: 8px;
            font-size: .78rem;
        }

        @media (min-width: 1181px) {
            .page {
                height: min(700px, calc(100vh - 48px));
            }

            .layout {
                grid-template-columns: minmax(0, 1fr) minmax(390px, 450px);
            }

            .brand {
                margin-bottom: 18px;
            }

            .hero-lead {
                margin-bottom: 18px;
            }

            .showcase {
                grid-template-columns: minmax(235px, 300px) minmax(315px, 430px);
                align-items: center;
            }

            .features {
                gap: 16px;
            }

            .dashboard {
                align-self: center;
            }

            .dash-card {
                width: min(410px, 100%);
            }

            .card {
                width: min(440px, 100%);
                max-height: calc(100vh - 72px);
            }

            .card.is-registering {
                width: min(430px, 100%);
            }
        }

        @media (min-width: 1181px) and (max-width: 1400px) {
            .page {
                height: min(680px, calc(100vh - 32px));
            }

            .showcase {
                grid-template-columns: minmax(220px, 280px) minmax(290px, 390px);
            }

            .dash-card {
                width: min(380px, 100%);
            }
        }

        @media (max-width: 1180px) {
            .values {
                display: none;
            }

            .card.is-registering {
                max-height: calc(100vh - 32px);
            }
        }

        @media (max-height: 700px) and (min-width: 1181px) {
            .page {
                height: calc(100vh - 20px);
                margin-top: 10px;
                margin-bottom: 10px;
            }

            .hero h1 {
                font-size: clamp(1.85rem, 2.5vw, 2.35rem);
            }

            .feature-icon {
                width: 48px;
                height: 48px;
                font-size: 1.2rem;
            }

            .feature {
                grid-template-columns: 48px 1fr;
            }

            .chart,
            .traffic {
                height: 108px;
            }

            .chart-lines {
                height: 58px;
            }

            .map {
                height: 96px;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <div class="layout">
            <section class="hero" aria-label="AdHub platform overview">
                <div class="brand">
                    <div class="mark" aria-hidden="true"><span></span><span></span><span></span></div>
                    <div class="brand-name">AdHub</div>
                </div>

                <h1>All-in-one platform for <span>smarter advertising.</span></h1>
                <p class="hero-lead">Create, manage and optimize ad campaigns that drive <a href="#panel-login">real results.</a></p>

                <div class="showcase">
                    <div class="features">
                        <article class="feature">
                            <div class="feature-icon"><i class="bi bi-bar-chart-fill"></i></div>
                            <div><h3>Campaigns</h3><p>Create and manage powerful ad campaigns in one place.</p></div>
                        </article>
                        <article class="feature">
                            <div class="feature-icon"><i class="bi bi-crosshair"></i></div>
                            <div><h3>Targeting</h3><p>Reach the right audience with advanced targeting tools.</p></div>
                        </article>
                        <article class="feature">
                            <div class="feature-icon"><i class="bi bi-pie-chart-fill"></i></div>
                            <div><h3>Analytics</h3><p>Track performance and get insights that help you grow.</p></div>
                        </article>
                        <article class="feature">
                            <div class="feature-icon"><i class="bi bi-people-fill"></i></div>
                            <div><h3>Clients</h3><p>Manage clients, budgets and collaborations seamlessly.</p></div>
                        </article>
                    </div>

                    <div class="dashboard" aria-hidden="true">
                        <div class="dash-card">
                            <div class="dash-head">
                                <span><i class="bi bi-layers-fill"></i> AdHub Dashboard</span>
                                <span><i class="bi bi-circle"></i> <i class="bi bi-circle"></i> <i class="bi bi-circle"></i></span>
                            </div>
                            <div class="dash-grid">
                                <div class="metric"><small>Total Campaigns</small><strong>128</strong><div></div></div>
                                <div class="metric"><small>Active Campaigns</small><strong>32</strong><div></div></div>
                                <div class="metric"><small>Total Spend</small><strong>$24,860</strong><div></div></div>
                                <div class="chart"><strong>Performance Overview</strong><div class="chart-lines"></div></div>
                                <div class="traffic"><strong>Traffic Source</strong><div class="donut"></div></div>
                                <div class="bars">
                                    <strong>Top Campaigns</strong>
                                    <div class="bar"><i class="bi bi-badge-ad-fill"></i><div class="track"><span style="width:82%"></span></div><span>$8,450</span></div>
                                    <div class="bar"><i class="bi bi-badge-ad-fill"></i><div class="track"><span style="width:72%"></span></div><span>$6,230</span></div>
                                    <div class="bar"><i class="bi bi-badge-ad-fill"></i><div class="track"><span style="width:58%"></span></div><span>$4,120</span></div>
                                    <div class="bar"><i class="bi bi-badge-ad-fill"></i><div class="track"><span style="width:45%"></span></div><span>$3,860</span></div>
                                </div>
                                <div class="map"><strong>Audience Location</strong></div>
                            </div>
                        </div>
                        <div class="laptop"></div>
                    </div>
                </div>

            </section>

            <section class="card <?= $active_tab === 'register' ? 'is-registering' : '' ?>" aria-label="Account access">
                <div class="auth-brand">
                    <div class="mark" aria-hidden="true"><span></span><span></span><span></span></div>
                    <strong>AdHub</strong>
                </div>

                <p class="auth-title" id="auth-title"><?= $active_tab === 'register' ? 'Create your account' : 'Welcome back!' ?></p>
                <p class="auth-subtitle" id="auth-subtitle"><?= $active_tab === 'register' ? 'Sign up to get started with AdHub' : 'Sign in to your account to continue' ?></p>

                <div class="tabs" role="tablist" aria-label="Authentication tabs">
                    <button type="button" class="tab-btn <?= $active_tab === 'login' ? 'active' : '' ?>" onclick="switchTab('login')" role="tab" aria-controls="panel-login">
                        <i class="bi bi-box-arrow-in-right"></i>Sign In
                    </button>
                    <button type="button" class="tab-btn <?= $active_tab === 'register' ? 'active' : '' ?>" onclick="switchTab('register')" role="tab" aria-controls="panel-register">
                        <i class="bi bi-person-plus"></i>Sign Up
                    </button>
                </div>

                <div id="panel-login" class="panel <?= $active_tab === 'login' ? 'active' : '' ?>">
                    <?php if ($register_ok): ?>
                        <div class="alert-box alert-success"><i class="bi bi-check-circle"></i><?= htmlspecialchars($register_ok) ?></div>
                    <?php endif; ?>

                    <?php if ($login_error): ?>
                        <div class="alert-box alert-error"><i class="bi bi-exclamation-circle"></i><?= htmlspecialchars($login_error) ?></div>
                    <?php endif; ?>

                    <form method="POST" novalidate autocomplete="off">
                        <input type="hidden" name="_form" value="login">
                        <div class="field">
                            <label for="email">Email address</label>
                            <div class="input">
                                <i class="bi bi-envelope"></i>
                                <input id="email" type="email" name="email" placeholder="you@company.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
                            </div>
                        </div>

                        <div class="field">
                            <label for="password">Password</label>
                            <div class="input">
                                <i class="bi bi-lock"></i>
                                <input id="password" type="password" name="password" placeholder="••••••••" required>
                                <button class="toggle-pass" type="button" onclick="togglePassword('password', this)" aria-label="Show password"><i class="bi bi-eye-slash"></i></button>
                            </div>
                        </div>

                        <div class="form-row">
                            <label class="check"><input type="checkbox" name="remember" value="1">Remember me</label>
                            <a class="forgot" href="#">Forgot password?</a>
                        </div>

                        <button type="submit" class="submit"><span>Sign In</span><i class="bi bi-arrow-right"></i></button>
                    </form>
                </div>

                <div id="panel-register" class="panel <?= $active_tab === 'register' ? 'active' : '' ?>">
                    <?php if ($register_error): ?>
                        <div class="alert-box alert-error"><i class="bi bi-exclamation-circle"></i><?= htmlspecialchars($register_error) ?></div>
                    <?php endif; ?>

                    <form method="POST" novalidate autocomplete="off">
                        <input type="hidden" name="_form" value="register">
                        <div class="field">
                            <label for="reg_name">Full name</label>
                            <div class="input">
                                <i class="bi bi-person"></i>
                                <input id="reg_name" type="text" name="reg_name" placeholder="Marcus Webb" value="<?= htmlspecialchars($_POST['reg_name'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="field">
                            <label for="reg_email">Email address</label>
                            <div class="input">
                                <i class="bi bi-envelope"></i>
                                <input id="reg_email" type="email" name="reg_email" placeholder="you@company.com" value="<?= htmlspecialchars($_POST['reg_email'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="field">
                            <label for="reg_password">Password</label>
                            <div class="input">
                                <i class="bi bi-lock"></i>
                                <input id="reg_password" type="password" name="reg_password" placeholder="Min. 8 characters" required>
                                <button class="toggle-pass" type="button" onclick="togglePassword('reg_password', this)" aria-label="Show password"><i class="bi bi-eye-slash"></i></button>
                            </div>
                        </div>

                        <div class="field">
                            <label for="reg_confirm_password">Confirm password</label>
                            <div class="input">
                                <i class="bi bi-shield-lock"></i>
                                <input id="reg_confirm_password" type="password" name="reg_confirm_password" placeholder="Re-enter password" required>
                                <button class="toggle-pass" type="button" onclick="togglePassword('reg_confirm_password', this)" aria-label="Show password"><i class="bi bi-eye-slash"></i></button>
                            </div>
                        </div>

                        <button type="submit" class="submit"><span>Create account</span><i class="bi bi-arrow-right"></i></button>
                        <p class="role-note"><i class="bi bi-info-circle"></i> New accounts are registered as client role.</p>
                    </form>
                </div>
            </section>
        </div>
    </main>

    <script>
        function switchTab(tab) {
            const login = tab === 'login';
            document.querySelectorAll('.tab-btn').forEach((btn, index) => {
                btn.classList.toggle('active', (login && index === 0) || (!login && index === 1));
            });
            document.getElementById('panel-login').classList.toggle('active', login);
            document.getElementById('panel-register').classList.toggle('active', !login);
            document.querySelector('.card').classList.toggle('is-registering', !login);
            document.getElementById('auth-title').textContent = login ? 'Welcome back!' : 'Create your account';
            document.getElementById('auth-subtitle').textContent = login ? 'Sign in to your account to continue' : 'Sign up to get started with AdHub';
        }

        function togglePassword(id, button) {
            const input = document.getElementById(id);
            const icon = button.querySelector('i');
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            icon.className = show ? 'bi bi-eye' : 'bi bi-eye-slash';
            button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        }
    </script>
</body>
</html>
