<?php
$htPath = __DIR__ . '/.htaccess';
$htExpected = "<IfModule mod_rewrite.c>\nRewriteEngine On\n\nRewriteRule ^index\\.php$ - [L]\n\nRewriteCond %{REQUEST_FILENAME} -f [OR]\nRewriteCond %{REQUEST_FILENAME} -d\nRewriteRule ^ - [L]\n\nRewriteRule ^login/?$ admin/auth/login.php [QSA,L]\nRewriteRule ^register/?$ admin/auth/signup.php [QSA,L]\nRewriteRule ^signup/?$ admin/auth/signup.php [QSA,L]\nRewriteRule ^logout/?$ admin/auth/logout.php [QSA,L]\nRewriteRule ^dashboard/?$ admin/index.php [QSA,L]\nRewriteRule ^auth/?$ admin/auth/login.php [QSA,L]\n\nRewriteRule ^u/([a-zA-Z0-9-]+)/?$ index.php?username=$1 [QSA,L]\nRewriteRule ^([a-zA-Z0-9-]+)/?$ index.php?username=$1 [QSA,L]\n</IfModule>\n\nErrorDocument 404 /index.php\n";
if (!file_exists($htPath) || @file_get_contents($htPath) !== $htExpected) {
    @file_put_contents($htPath, $htExpected);
}

require_once __DIR__ . '/admin/includes/auth.php';
require_once __DIR__ . '/admin/includes/permissions.php';
require_once __DIR__ . '/admin/includes/functions.php';

$rawUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$basePath = project_base_url();
if ($basePath !== '' && str_starts_with($rawUri, $basePath)) {
    $rawUri = substr($rawUri, strlen($basePath));
}
$rawUri = trim((string)$rawUri, '/');
if (str_starts_with($rawUri, 'u/')) {
    $legacyUser = trim(substr($rawUri, 2), '/');
    if ($legacyUser !== '') {
        header('Location: ' . user_url($legacyUser), true, 301);
        exit;
    }
}

$reqUser = trim($_GET['username'] ?? $_GET['u'] ?? '');
if ($reqUser === '') {
    if ($rawUri !== '' && $rawUri !== 'index.php') {
        $parts = explode('/', $rawUri);
        if ($parts[0] === 'index.php' && isset($parts[1])) {
            array_shift($parts);
        }
        $firstSeg = $parts[0] ?? '';
        if (preg_match('/^[a-zA-Z0-9-]+$/', $firstSeg)) {
            $reqUser = strtolower($firstSeg);
        }
    }
} else {
    $reqUser = strtolower($reqUser);
}

if ($reqUser === 'login') {
    include __DIR__ . '/admin/auth/login.php';
    exit;
}
if ($reqUser === 'register' || $reqUser === 'signup') {
    include __DIR__ . '/admin/auth/signup.php';
    exit;
}
if ($reqUser === 'logout') {
    include __DIR__ . '/admin/auth/logout.php';
    exit;
}
if ($reqUser === 'dashboard') {
    include __DIR__ . '/dashboard.php';
    exit;
}
if ($reqUser === 'auth') {
    include __DIR__ . '/admin/auth/login.php';
    exit;
}

if ($reqUser === '') {
    if (is_logged_in()) {
        header('Location: ' . dashboard_url('index.php'));
        exit;
    }
    $pageTitle = 'MY-TAP — One Link. Your Entire Digital Identity.';
    $displayDomain = 'my-tap.online/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="Bring your social links, content, and online presence together in one beautiful page. Claim your unique username today.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --color-white: #FFFFFF;
            --color-soft-pink: #F8D9E8;
            --color-premium-pink: #F2A7CE;
            --color-deep-pink: #E879B9;
            --color-lavender: #E9DFFF;
            --color-soft-blue: #DCEBFF;
            --color-text-dark: #17151C;
            --color-text-secondary: #77717F;
            --glass-bg: rgba(255, 255, 255, 0.75);
            --glass-bg-subtle: rgba(255, 255, 255, 0.5);
            --glass-border: rgba(255, 255, 255, 0.9);
            --glass-border-pink: rgba(242, 167, 206, 0.45);
            --glass-shadow: 0 16px 40px rgba(232, 121, 185, 0.12), 0 2px 8px rgba(23, 21, 28, 0.04);
            --glass-shadow-hover: 0 24px 60px rgba(232, 121, 185, 0.22), 0 4px 16px rgba(23, 21, 28, 0.06);
            --glass-inset: inset 0 1px 1px 0 rgba(255, 255, 255, 0.95);
            --ease-expo: cubic-bezier(0.16, 1, 0.3, 1);
            --ease-quint: cubic-bezier(0.22, 1, 0.36, 1);
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
            overflow-x: hidden;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #FAF8FC;
            color: var(--color-text-dark);
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        ::selection {
            background-color: var(--color-soft-pink);
            color: var(--color-deep-pink);
        }

        .ambient-orbs {
            position: fixed;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }

        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(90px);
            opacity: 0.7;
            will-change: transform;
        }

        .orb-1 {
            width: 540px;
            height: 540px;
            background: radial-gradient(circle, #F8D9E8 0%, rgba(248, 217, 232, 0) 70%);
            top: -120px;
            left: -100px;
            animation: floatOrb1 20s ease-in-out infinite alternate;
        }

        .orb-2 {
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, #E9DFFF 0%, rgba(233, 223, 255, 0) 70%);
            top: 15%;
            right: -150px;
            animation: floatOrb2 24s ease-in-out infinite alternate;
        }

        .orb-3 {
            width: 520px;
            height: 520px;
            background: radial-gradient(circle, #DCEBFF 0%, rgba(220, 235, 255, 0) 70%);
            top: 50%;
            left: -80px;
            animation: floatOrb3 22s ease-in-out infinite alternate;
        }

        .orb-4 {
            width: 560px;
            height: 560px;
            background: radial-gradient(circle, #F2A7CE 0%, rgba(242, 167, 206, 0) 70%);
            bottom: 5%;
            right: -60px;
            animation: floatOrb4 26s ease-in-out infinite alternate;
        }

        @keyframes floatOrb1 {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(70px, 90px) scale(1.08); }
            100% { transform: translate(130px, 40px) scale(0.96); }
        }

        @keyframes floatOrb2 {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(-90px, 70px) scale(1.06); }
            100% { transform: translate(-50px, 130px) scale(0.93); }
        }

        @keyframes floatOrb3 {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(90px, -70px) scale(1.07); }
            100% { transform: translate(50px, 70px) scale(0.95); }
        }

        @keyframes floatOrb4 {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(-70px, -90px) scale(1.1); }
            100% { transform: translate(-120px, -30px) scale(0.94); }
        }

        .grain-overlay {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 1;
            opacity: 0.035;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.8' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E");
        }

        .page-content {
            position: relative;
            z-index: 2;
            width: 100%;
            overflow-x: hidden;
        }

        .liquid-glass {
            background: var(--glass-bg);
            backdrop-filter: blur(28px) saturate(190%);
            -webkit-backdrop-filter: blur(28px) saturate(190%);
            border: 1px solid var(--glass-border);
            box-shadow: var(--glass-shadow), var(--glass-inset);
            border-radius: 24px;
            transition: transform 0.4s var(--ease-expo), box-shadow 0.4s var(--ease-expo), border-color 0.4s var(--ease-expo);
        }

        .liquid-glass:hover {
            border-color: var(--glass-border-pink);
            box-shadow: var(--glass-shadow-hover), var(--glass-inset);
        }

        .liquid-btn-primary {
            background: linear-gradient(135deg, #E879B9 0%, #F2A7CE 100%);
            color: #FFFFFF;
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 8px 24px rgba(232, 121, 185, 0.35), inset 0 1px 1px rgba(255, 255, 255, 0.6);
            transition: all 0.3s var(--ease-expo);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            text-decoration: none;
            border-radius: 9999px;
            font-size: 15px;
            padding: 12px 24px;
        }

        .liquid-btn-primary:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 12px 32px rgba(232, 121, 185, 0.5), inset 0 1px 1px rgba(255, 255, 255, 0.8);
            color: #FFFFFF;
        }

        .liquid-btn-secondary {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            color: var(--color-text-dark);
            border: 1px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 4px 16px rgba(23, 21, 28, 0.05), inset 0 1px 1px rgba(255, 255, 255, 0.9);
            transition: all 0.3s var(--ease-expo);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            text-decoration: none;
            border-radius: 9999px;
            font-size: 15px;
            padding: 12px 24px;
        }

        .liquid-btn-secondary:hover {
            background: rgba(255, 255, 255, 0.95);
            border-color: var(--color-premium-pink);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(232, 121, 185, 0.18);
            color: var(--color-deep-pink);
        }

        .nav-wrapper {
            position: sticky;
            top: 14px;
            z-index: 100;
            max-width: 1200px;
            margin: 14px auto 0;
            padding: 0 16px;
            width: 100%;
        }

        .liquid-navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 20px;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.78);
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 10px 30px rgba(232, 121, 185, 0.12), inset 0 1px 1px rgba(255, 255, 255, 0.95);
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--color-text-dark);
            flex-shrink: 0;
        }

        .brand-icon-box {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            background: linear-gradient(135deg, #E879B9 0%, #F2A7CE 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FFFFFF;
            font-size: 16px;
            box-shadow: 0 4px 12px rgba(232, 121, 185, 0.35);
        }

        .brand-title {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.03em;
            background: linear-gradient(135deg, #17151C 0%, #E879B9 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 28px;
            list-style: none;
        }

        .nav-link {
            text-decoration: none;
            color: var(--color-text-secondary);
            font-size: 14px;
            font-weight: 600;
            transition: color 0.25s var(--ease-expo);
        }

        .nav-link:hover {
            color: var(--color-deep-pink);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nav-signin-link {
            text-decoration: none;
            color: var(--color-text-dark);
            font-size: 14px;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 9999px;
            transition: all 0.25s var(--ease-expo);
        }

        .nav-signin-link:hover {
            color: var(--color-deep-pink);
            background: rgba(248, 217, 232, 0.4);
        }

        .nav-create-btn {
            padding: 9px 20px;
            font-size: 14px;
        }

        .mobile-toggle-btn {
            display: none;
            background: transparent;
            border: none;
            color: var(--color-text-dark);
            font-size: 20px;
            cursor: pointer;
            padding: 6px;
            margin-left: auto;
        }

        .mobile-dropdown {
            display: none;
            margin-top: 10px;
            padding: 20px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 16px 40px rgba(232, 121, 185, 0.15);
        }

        .mobile-menu-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .mobile-menu-actions {
            margin-top: 18px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .hero-section {
            max-width: 1240px;
            margin: 0 auto;
            padding: 50px 20px 80px;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 40px;
            align-items: center;
        }

        .hero-badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            border-radius: 9999px;
            background: rgba(248, 217, 232, 0.6);
            border: 1px solid rgba(242, 167, 206, 0.4);
            color: var(--color-deep-pink);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.02em;
            margin-bottom: 20px;
        }

        .hero-badge-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--color-deep-pink);
            box-shadow: 0 0 10px var(--color-deep-pink);
            animation: pulseGlow 2s infinite;
        }

        @keyframes pulseGlow {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(0.85); }
        }

        .hero-title {
            font-size: clamp(34px, 4.8vw, 64px);
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -0.035em;
            color: var(--color-text-dark);
            margin-bottom: 20px;
            word-break: break-word;
        }

        .hero-gradient-text {
            background: linear-gradient(135deg, #E879B9 0%, #A27BFF 50%, #4B88FF 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline;
        }

        .hero-description {
            font-size: clamp(16px, 1.8vw, 20px);
            color: var(--color-text-secondary);
            line-height: 1.6;
            margin-bottom: 32px;
            max-width: 540px;
        }

        .claim-card-wrapper {
            margin-bottom: 28px;
            max-width: 540px;
            width: 100%;
        }

        .claim-form {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(28px);
            -webkit-backdrop-filter: blur(28px);
            border: 1px solid rgba(242, 167, 206, 0.4);
            box-shadow: 0 12px 36px rgba(232, 121, 185, 0.16), inset 0 1px 1px rgba(255, 255, 255, 0.95);
            border-radius: 9999px;
            padding: 6px 6px 6px 20px;
            transition: all 0.3s var(--ease-expo);
            width: 100%;
        }

        .claim-form:focus-within {
            border-color: var(--color-deep-pink);
            box-shadow: 0 16px 44px rgba(232, 121, 185, 0.25), inset 0 1px 1px rgba(255, 255, 255, 0.95);
        }

        .claim-input-box {
            display: flex;
            align-items: center;
            flex: 1;
            min-width: 0;
        }

        .claim-prefix {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 15px;
            font-weight: 700;
            color: var(--color-deep-pink);
            white-space: nowrap;
            user-select: none;
            flex-shrink: 0;
        }

        .claim-input {
            flex: 1;
            min-width: 0;
            background: transparent;
            border: none;
            outline: none;
            font-size: 16px;
            font-weight: 600;
            color: var(--color-text-dark);
            padding: 8px 10px;
            font-family: inherit;
        }

        .claim-input::placeholder {
            color: #B4AEBC;
            font-weight: 500;
        }

        .claim-btn {
            padding: 12px 26px;
            font-size: 15px;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .claim-feedback {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--color-text-secondary);
            margin-top: 10px;
            padding-left: 16px;
            word-break: break-all;
        }

        .claim-feedback-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #10B981;
            flex-shrink: 0;
        }

        .trust-strip {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .trust-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 600;
            color: var(--color-text-secondary);
        }

        .trust-icon {
            color: var(--color-deep-pink);
            font-size: 14px;
        }

        .hero-visual-stage {
            position: relative;
            perspective: 1200px;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px 10px;
            width: 100%;
        }

        .hero-glow-disc {
            position: absolute;
            width: 360px;
            height: 360px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(242, 167, 206, 0.45) 0%, rgba(233, 223, 255, 0.2) 50%, rgba(255, 255, 255, 0) 70%);
            filter: blur(40px);
            z-index: 1;
        }

        .hero-main-card {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 350px;
            padding: 32px 24px;
            background: rgba(255, 255, 255, 0.78);
            backdrop-filter: blur(32px) saturate(200%);
            -webkit-backdrop-filter: blur(32px) saturate(200%);
            border: 1px solid rgba(255, 255, 255, 0.95);
            box-shadow: 0 24px 60px rgba(232, 121, 185, 0.18), 0 4px 16px rgba(23, 21, 28, 0.04), inset 0 1px 1px rgba(255, 255, 255, 0.95);
            border-radius: 32px;
            text-align: center;
            transform-style: preserve-3d;
            transition: transform 0.2s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        .hero-avatar-box {
            position: relative;
            width: 88px;
            height: 88px;
            margin: 0 auto 16px;
        }

        .hero-avatar-ring {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            padding: 3px;
            background: linear-gradient(135deg, #E879B9 0%, #E9DFFF 50%, #DCEBFF 100%);
            box-shadow: 0 8px 24px rgba(232, 121, 185, 0.25);
        }

        .hero-avatar-inner {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: linear-gradient(135deg, #FAF8FC 0%, #F8D9E8 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 800;
            color: var(--color-deep-pink);
        }

        .hero-card-name {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--color-text-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .verified-tick {
            color: #3B82F6;
            font-size: 15px;
        }

        .hero-card-handle {
            font-size: 14px;
            font-weight: 600;
            color: var(--color-deep-pink);
            margin-bottom: 6px;
        }

        .hero-card-bio {
            font-size: 13px;
            color: var(--color-text-secondary);
            margin-bottom: 22px;
            line-height: 1.4;
        }

        .hero-dest-pills {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .hero-dest-pill {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 18px;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 4px 12px rgba(23, 21, 28, 0.03), inset 0 1px 1px rgba(255, 255, 255, 0.95);
            text-decoration: none;
            color: var(--color-text-dark);
            font-size: 14px;
            font-weight: 600;
            transition: all 0.25s var(--ease-expo);
        }

        .hero-dest-pill:hover {
            background: #FFFFFF;
            border-color: var(--color-premium-pink);
            transform: translateX(4px);
            box-shadow: 0 6px 20px rgba(232, 121, 185, 0.16);
            color: var(--color-deep-pink);
        }

        .dest-icon-name {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dest-icon-box {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: rgba(248, 217, 232, 0.5);
            color: var(--color-deep-pink);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
        }

        .sat-badge {
            position: absolute;
            z-index: 3;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.95);
            box-shadow: 0 12px 30px rgba(232, 121, 185, 0.18), inset 0 1px 1px rgba(255, 255, 255, 0.95);
            border-radius: 9999px;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: satFloat 6s ease-in-out infinite alternate;
        }

        .sat-1 {
            top: 20px;
            right: -10px;
            animation-delay: 0s;
        }

        .sat-2 {
            bottom: 30px;
            left: -15px;
            animation-delay: 1.5s;
            border-radius: 20px;
            padding: 12px 18px;
        }

        .sat-3 {
            bottom: -15px;
            right: 10px;
            animation-delay: 3s;
        }

        @keyframes satFloat {
            0% { transform: translateY(0); }
            100% { transform: translateY(-12px); }
        }

        .pulse-active-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10B981;
            box-shadow: 0 0 8px #10B981;
        }

        .sat-text-title {
            font-size: 12px;
            font-weight: 700;
            color: var(--color-text-dark);
        }

        .sat-text-sub {
            font-size: 11px;
            color: var(--color-text-secondary);
        }

        .section-header {
            text-align: center;
            max-width: 680px;
            margin: 0 auto 50px;
            padding: 0 10px;
        }

        .section-eyebrow {
            display: inline-block;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--color-deep-pink);
            margin-bottom: 12px;
        }

        .section-title {
            font-size: clamp(28px, 3.2vw, 42px);
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -0.025em;
            color: var(--color-text-dark);
            margin-bottom: 16px;
        }

        .section-desc {
            font-size: 16px;
            color: var(--color-text-secondary);
            line-height: 1.6;
        }

        .creators-section {
            max-width: 1240px;
            margin: 0 auto;
            padding: 80px 20px;
        }

        .creators-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin-bottom: 48px;
        }

        .creator-card {
            padding: 26px 20px;
            text-align: center;
            text-decoration: none;
            color: var(--color-text-dark);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .creator-avatar-wrap {
            width: 68px;
            height: 68px;
            border-radius: 50%;
            padding: 2px;
            background: linear-gradient(135deg, #E879B9 0%, #DCEBFF 100%);
            margin-bottom: 14px;
            box-shadow: 0 6px 18px rgba(232, 121, 185, 0.2);
        }

        .creator-avatar-inner {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: #FFFFFF;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: 800;
            color: var(--color-deep-pink);
        }

        .creator-name {
            font-size: 17px;
            font-weight: 700;
            letter-spacing: -0.015em;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 4px;
        }

        .creator-handle {
            font-size: 13px;
            font-weight: 600;
            color: var(--color-deep-pink);
            margin-bottom: 8px;
        }

        .creator-role {
            font-size: 12px;
            color: var(--color-text-secondary);
            margin-bottom: 16px;
        }

        .creator-view-btn {
            margin-top: auto;
            font-size: 13px;
            font-weight: 700;
            color: var(--color-deep-pink);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: transform 0.2s var(--ease-expo);
        }

        .creator-card:hover .creator-view-btn {
            transform: translateX(4px);
        }

        .metrics-banner {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            padding: 32px 40px;
            border-radius: 24px;
            text-align: center;
        }

        .metric-number {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--color-deep-pink);
            margin-bottom: 4px;
        }

        .metric-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--color-text-secondary);
        }

        .features-section {
            max-width: 1240px;
            margin: 0 auto;
            padding: 80px 20px;
        }

        .bento-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }

        .bento-col-span-2 {
            grid-column: span 2;
        }

        .bento-card {
            padding: 36px 32px;
            display: flex;
            flex-direction: column;
        }

        .bento-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: linear-gradient(135deg, rgba(232, 121, 185, 0.15) 0%, rgba(242, 167, 206, 0.25) 100%);
            border: 1px solid rgba(242, 167, 206, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: var(--color-deep-pink);
            margin-bottom: 22px;
        }

        .bento-card-title {
            font-size: 21px;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--color-text-dark);
            margin-bottom: 12px;
        }

        .bento-card-desc {
            font-size: 15px;
            color: var(--color-text-secondary);
            line-height: 1.6;
        }

        .bento-preview-chips {
            display: flex;
            gap: 10px;
            margin-top: 24px;
            flex-wrap: wrap;
        }

        .bento-chip {
            padding: 8px 14px;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.9);
            font-size: 12px;
            font-weight: 600;
            color: var(--color-text-dark);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .how-section {
            max-width: 1240px;
            margin: 0 auto;
            padding: 80px 20px;
        }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            position: relative;
        }

        .step-card {
            padding: 38px 30px;
            position: relative;
        }

        .step-num-badge {
            font-size: 44px;
            font-weight: 900;
            letter-spacing: -0.04em;
            background: linear-gradient(135deg, #E879B9 0%, #DCEBFF 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 16px;
            display: inline-block;
        }

        .step-title {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--color-text-dark);
            margin-bottom: 12px;
        }

        .step-desc {
            font-size: 14px;
            color: var(--color-text-secondary);
            line-height: 1.6;
        }

        .showcase-section {
            max-width: 1240px;
            margin: 0 auto;
            padding: 80px 20px;
        }

        .showcase-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 48px;
            align-items: center;
        }

        .phone-mockup-wrapper {
            display: flex;
            justify-content: center;
        }

        .liquid-phone {
            width: 310px;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(32px);
            -webkit-backdrop-filter: blur(32px);
            border: 4px solid rgba(255, 255, 255, 0.95);
            box-shadow: 0 28px 70px rgba(232, 121, 185, 0.22), inset 0 1px 2px rgba(255, 255, 255, 0.95);
            border-radius: 44px;
            padding: 16px;
            position: relative;
        }

        .phone-notch-pill {
            width: 90px;
            height: 18px;
            background: #17151C;
            border-radius: 9999px;
            margin: 0 auto 18px;
        }

        .phone-screen-content {
            text-align: center;
        }

        .phone-avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            margin: 0 auto 10px;
            background: linear-gradient(135deg, #E879B9 0%, #DCEBFF 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: 800;
            color: #FFFFFF;
        }

        .phone-user-name {
            font-size: 16px;
            font-weight: 800;
            color: var(--color-text-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }

        .phone-handle {
            font-size: 12px;
            font-weight: 600;
            color: var(--color-deep-pink);
            margin-bottom: 16px;
        }

        .phone-links-stack {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .phone-link-pill {
            padding: 10px 14px;
            border-radius: 12px;
            background: #FFFFFF;
            border: 1px solid rgba(242, 167, 206, 0.3);
            font-size: 12px;
            font-weight: 600;
            color: var(--color-text-dark);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .showcase-telemetry-stack {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .telemetry-card {
            padding: 24px 28px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .telemetry-icon-box {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            background: rgba(248, 217, 232, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: var(--color-deep-pink);
            flex-shrink: 0;
        }

        .telemetry-title {
            font-size: 17px;
            font-weight: 800;
            color: var(--color-text-dark);
            margin-bottom: 4px;
        }

        .telemetry-desc {
            font-size: 13px;
            color: var(--color-text-secondary);
        }

        .cta-section {
            max-width: 1240px;
            margin: 0 auto;
            padding: 80px 20px 100px;
        }

        .cta-card {
            padding: 60px 36px;
            border-radius: 36px;
            text-align: center;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(248, 217, 232, 0.65) 50%, rgba(233, 223, 255, 0.7) 100%);
            border: 1px solid rgba(255, 255, 255, 0.95);
            box-shadow: 0 28px 70px rgba(232, 121, 185, 0.2), inset 0 1px 2px rgba(255, 255, 255, 0.95);
            position: relative;
            overflow: hidden;
        }

        .cta-badge {
            display: inline-block;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--color-deep-pink);
            margin-bottom: 16px;
        }

        .cta-title {
            font-size: clamp(30px, 3.8vw, 52px);
            font-weight: 800;
            letter-spacing: -0.03em;
            color: var(--color-text-dark);
            margin-bottom: 16px;
            line-height: 1.15;
            word-break: break-word;
        }

        .cta-desc {
            font-size: 17px;
            color: var(--color-text-secondary);
            margin-bottom: 36px;
            max-width: 580px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-claim-wrapper {
            max-width: 520px;
            margin: 0 auto 20px;
            width: 100%;
        }

        .cta-reassurance {
            font-size: 13px;
            font-weight: 600;
            color: var(--color-text-secondary);
        }

        .footer-wrapper {
            max-width: 1200px;
            margin: 0 auto 30px;
            padding: 0 16px;
            width: 100%;
        }

        .liquid-footer {
            padding: 36px 40px;
            border-radius: 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 24px;
        }

        .footer-brand-side {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .footer-links {
            display: flex;
            align-items: center;
            gap: 24px;
            list-style: none;
            flex-wrap: wrap;
        }

        .footer-link {
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            color: var(--color-text-secondary);
            transition: color 0.2s var(--ease-expo);
        }

        .footer-link:hover {
            color: var(--color-deep-pink);
        }

        .footer-copy {
            font-size: 13px;
            color: var(--color-text-secondary);
            font-weight: 500;
        }

        .scroll-fade {
            opacity: 1;
            transform: none;
        }

        .js-animated .scroll-fade {
            opacity: 0;
            transform: translateY(28px);
            transition: opacity 0.7s var(--ease-expo), transform 0.7s var(--ease-expo);
        }

        .js-animated .scroll-fade.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        @media (max-width: 1024px) {
            .hero-grid {
                grid-template-columns: 1fr;
                gap: 50px;
                text-align: center;
            }
            .hero-description {
                margin-left: auto;
                margin-right: auto;
            }
            .claim-card-wrapper {
                margin-left: auto;
                margin-right: auto;
            }
            .trust-strip {
                justify-content: center;
            }
            .claim-feedback {
                justify-content: center;
            }
            .creators-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .bento-grid {
                grid-template-columns: 1fr;
            }
            .bento-col-span-2 {
                grid-column: span 1;
            }
            .steps-grid {
                grid-template-columns: 1fr;
            }
            .showcase-grid {
                grid-template-columns: 1fr;
            }
            .metrics-banner {
                grid-template-columns: repeat(2, 1fr);
                gap: 24px;
            }
        }

        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }
            .nav-actions {
                display: none;
            }
            .mobile-toggle-btn {
                display: block;
            }
            .creators-grid {
                grid-template-columns: 1fr;
            }
            .metrics-banner {
                grid-template-columns: 1fr;
                gap: 20px;
                padding: 24px 20px;
            }
            .liquid-footer {
                flex-direction: column;
                text-align: center;
                gap: 20px;
                padding: 28px 20px;
            }
            .footer-links {
                justify-content: center;
                gap: 16px;
            }
            .sat-badge {
                display: none;
            }
            .hero-main-card {
                max-width: 100%;
            }
        }

        @media (max-width: 600px) {
            .hero-title {
                font-size: 30px;
                line-height: 1.15;
            }
            .claim-form {
                flex-direction: column;
                border-radius: 24px;
                padding: 12px;
                gap: 10px;
            }
            .claim-input-box {
                width: 100%;
                padding: 4px 6px;
            }
            .claim-btn {
                width: 100%;
                justify-content: center;
            }
            .bento-card {
                padding: 24px 20px;
            }
            .step-card {
                padding: 24px 20px;
            }
            .cta-card {
                padding: 40px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="ambient-orbs">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
        <div class="orb orb-4"></div>
    </div>
    <div class="grain-overlay"></div>

    <div class="page-content">
        <div class="nav-wrapper">
            <nav class="liquid-navbar">
                <a href="<?= e(base_url()) ?>" class="nav-brand">
                    <div class="brand-icon-box">
                        <i class="fa-solid fa-bolt"></i>
                    </div>
                    <span class="brand-title">MY-TAP</span>
                </a>
                <ul class="nav-menu">
                    <li><a href="#features" class="nav-link">Features</a></li>
                    <li><a href="#creators" class="nav-link">Creators</a></li>
                    <li><a href="#how-it-works" class="nav-link">How It Works</a></li>
                    <li><a href="#showcase" class="nav-link">Showcase</a></li>
                </ul>
                <div class="nav-actions">
                    <a href="<?= e(login_url()) ?>" class="nav-signin-link">Sign In</a>
                    <a href="<?= e(register_url()) ?>" class="liquid-btn-primary nav-create-btn">
                        <span>Create Page</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
                <button type="button" class="mobile-toggle-btn" id="mobile-toggle-btn" aria-label="Toggle Navigation">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </nav>
            <div class="mobile-dropdown" id="mobile-dropdown">
                <ul class="mobile-menu-list">
                    <li><a href="#features" class="nav-link mobile-link">Features</a></li>
                    <li><a href="#creators" class="nav-link mobile-link">Creators</a></li>
                    <li><a href="#how-it-works" class="nav-link mobile-link">How It Works</a></li>
                    <li><a href="#showcase" class="nav-link mobile-link">Showcase</a></li>
                </ul>
                <div class="mobile-menu-actions">
                    <a href="<?= e(login_url()) ?>" class="liquid-btn-secondary" style="width: 100%;">Sign In</a>
                    <a href="<?= e(register_url()) ?>" class="liquid-btn-primary" style="width: 100%;">Create Your Page</a>
                </div>
            </div>
        </div>

        <section class="hero-section">
            <div class="hero-grid">
                <div class="hero-text-side">
                    <div class="hero-badge-pill">
                        <span class="hero-badge-dot"></span>
                        <span>The Liquid Glass Experience</span>
                        <i class="fa-solid fa-sparkles"></i>
                    </div>
                    <h1 class="hero-title">
                        One Link. <br>
                        <span class="hero-gradient-text">Your Entire Digital Identity.</span>
                    </h1>
                    <p class="hero-description">
                        Bring your social links, content, and online presence together in one beautiful page.
                    </p>
                    <div class="claim-card-wrapper">
                        <form class="claim-form" action="<?= e(register_url()) ?>" method="GET">
                            <div class="claim-input-box">
                                <div class="claim-prefix">
                                    <i class="fa-solid fa-link"></i>
                                    <span><?= e($displayDomain) ?></span>
                                </div>
                                <input type="text" name="claim" id="hero-claim-input" class="claim-input" placeholder="yourname" autocomplete="off" spellcheck="false" required pattern="[a-zA-Z0-9_-]+" title="Letters, numbers, dashes and underscores only">
                                <input type="hidden" name="username" id="hero-claim-hidden">
                            </div>
                            <button type="submit" class="liquid-btn-primary claim-btn">
                                <span>Claim My Link</span>
                                <i class="fa-solid fa-arrow-right"></i>
                            </button>
                        </form>
                        <div class="claim-feedback">
                            <span class="claim-feedback-dot"></span>
                            <span>Live preview: <strong><?= e($displayDomain) ?><span id="hero-preview-handle">yourname</span></strong></span>
                        </div>
                    </div>
                    <div class="trust-strip">
                        <div class="trust-item">
                            <i class="fa-solid fa-bolt trust-icon"></i>
                            <span>60-Second Setup</span>
                        </div>
                        <div class="trust-item">
                            <i class="fa-solid fa-circle-check trust-icon"></i>
                            <span>Free Forever Tier</span>
                        </div>
                        <div class="trust-item">
                            <i class="fa-solid fa-shield-halved trust-icon"></i>
                            <span>No Coding Required</span>
                        </div>
                    </div>
                </div>

                <div class="hero-visual-stage" id="hero-visual-stage">
                    <div class="hero-glow-disc"></div>

                    <div class="sat-badge sat-1">
                        <span class="pulse-active-dot"></span>
                        <div>
                            <div class="sat-text-title">New Link Tapped</div>
                            <div class="sat-text-sub">Just now from Instagram</div>
                        </div>
                    </div>

                    <div class="hero-main-card" id="hero-main-card">
                        <div class="hero-avatar-box">
                            <div class="hero-avatar-ring">
                                <div class="hero-avatar-inner">
                                    <span>AA</span>
                                </div>
                            </div>
                        </div>
                        <div class="hero-card-name">
                            <span>Ammar Ahmed</span>
                            <i class="fa-solid fa-circle-check verified-tick"></i>
                        </div>
                        <div class="hero-card-handle">@ammar-ahmed</div>
                        <div class="hero-card-bio">Tech Founder & UI/UX Designer</div>

                        <div class="hero-dest-pills">
                            <a href="<?= e(user_url('ammar-ahmed')) ?>" class="hero-dest-pill">
                                <div class="dest-icon-name">
                                    <div class="dest-icon-box"><i class="fa-solid fa-globe"></i></div>
                                    <span>Portfolio & Case Studies</span>
                                </div>
                                <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 12px; opacity: 0.6;"></i>
                            </a>
                            <a href="<?= e(user_url('ammar-ahmed')) ?>" class="hero-dest-pill">
                                <div class="dest-icon-name">
                                    <div class="dest-icon-box"><i class="fa-brands fa-github"></i></div>
                                    <span>Open Source Repositories</span>
                                </div>
                                <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 12px; opacity: 0.6;"></i>
                            </a>
                            <a href="<?= e(user_url('ammar-ahmed')) ?>" class="hero-dest-pill">
                                <div class="dest-icon-name">
                                    <div class="dest-icon-box"><i class="fa-brands fa-instagram"></i></div>
                                    <span>Design Inspirations</span>
                                </div>
                                <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 12px; opacity: 0.6;"></i>
                            </a>
                        </div>
                    </div>

                    <div class="sat-badge sat-2">
                        <div class="dest-icon-box" style="background: rgba(16, 185, 129, 0.15); color: #10B981;">
                            <i class="fa-solid fa-arrow-trend-up"></i>
                        </div>
                        <div>
                            <div class="sat-text-title">14.8k Profile Visits</div>
                            <div class="sat-text-sub">+28.4% this week</div>
                        </div>
                    </div>

                    <div class="sat-badge sat-3">
                        <i class="fa-solid fa-qrcode" style="color: var(--color-deep-pink);"></i>
                        <span class="sat-text-title">Instant NFC & QR Ready</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="creators-section scroll-fade" id="creators">
            <div class="section-header">
                <span class="section-eyebrow">Verified Creators</span>
                <h2 class="section-title">Trusted by Modern Creators, Artists & Entrepreneurs</h2>
                <p class="section-desc">
                    Discover active creators on MY-TAP. Each public link is rooted directly on our domain for instant memorability.
                </p>
            </div>

            <div class="creators-grid">
                <a href="<?= e(user_url('ammar-ahmed')) ?>" class="creator-card liquid-glass">
                    <div class="creator-avatar-wrap">
                        <div class="creator-avatar-inner">AA</div>
                    </div>
                    <div class="creator-name">
                        <span>Ammar Ahmed</span>
                        <i class="fa-solid fa-circle-check verified-tick"></i>
                    </div>
                    <div class="creator-handle">@ammar-ahmed</div>
                    <div class="creator-role">Tech Founder & Designer</div>
                    <div class="creator-view-btn">
                        <span>View Profile</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </div>
                </a>

                <a href="<?= e(user_url('medo123')) ?>" class="creator-card liquid-glass">
                    <div class="creator-avatar-wrap">
                        <div class="creator-avatar-inner" style="color: #6366F1;">MS</div>
                    </div>
                    <div class="creator-name">
                        <span>Mohamed Shaban</span>
                    </div>
                    <div class="creator-handle">@medo123</div>
                    <div class="creator-role">Software Engineer</div>
                    <div class="creator-view-btn">
                        <span>View Profile</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </div>
                </a>

                <a href="<?= e(user_url('mariam')) ?>" class="creator-card liquid-glass">
                    <div class="creator-avatar-wrap">
                        <div class="creator-avatar-inner" style="color: #EC4899;">M</div>
                    </div>
                    <div class="creator-name">
                        <span>Mariam</span>
                        <i class="fa-solid fa-circle-check verified-tick"></i>
                    </div>
                    <div class="creator-handle">@mariam</div>
                    <div class="creator-role">Visual Artist & Curator</div>
                    <div class="creator-view-btn">
                        <span>View Profile</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </div>
                </a>

                <a href="<?= e(user_url('omar-qadry')) ?>" class="creator-card liquid-glass">
                    <div class="creator-avatar-wrap">
                        <div class="creator-avatar-inner" style="color: #8B5CF6;">OQ</div>
                    </div>
                    <div class="creator-name">
                        <span>Omar Qadry</span>
                    </div>
                    <div class="creator-handle">@omar-qadry</div>
                    <div class="creator-role">Creative Director</div>
                    <div class="creator-view-btn">
                        <span>View Profile</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </div>
                </a>
            </div>

            <div class="metrics-banner liquid-glass">
                <div class="metric-item">
                    <div class="metric-number">50,000+</div>
                    <div class="metric-label">Active Links Shared</div>
                </div>
                <div class="metric-item">
                    <div class="metric-number">99.9%</div>
                    <div class="metric-label">Platform Uptime</div>
                </div>
                <div class="metric-item">
                    <div class="metric-number">&lt; 100ms</div>
                    <div class="metric-label">Global Edge Delivery</div>
                </div>
                <div class="metric-item">
                    <div class="metric-number">100%</div>
                    <div class="metric-label">Responsive Fidelity</div>
                </div>
            </div>
        </section>

        <section class="features-section scroll-fade" id="features">
            <div class="section-header">
                <span class="section-eyebrow">Platform Features</span>
                <h2 class="section-title">Crafted for Distinction, Built for Performance</h2>
                <p class="section-desc">
                    Every element in MY-TAP is designed to give you complete ownership over your digital presence with high elegance.
                </p>
            </div>

            <div class="bento-grid">
                <div class="bento-card bento-col-span-2 liquid-glass">
                    <div class="bento-icon-box">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                    <h3 class="bento-card-title">All Your Links in One Place</h3>
                    <p class="bento-card-desc">
                        Consolidate social channels, portfolios, streaming tracks, stores, and contact methods into a single unified destination. Eliminate bio-link clutter and maintain full control over where your audience lands.
                    </p>
                    <div class="bento-preview-chips">
                        <div class="bento-chip"><i class="fa-brands fa-instagram" style="color: #E1306C;"></i> Instagram</div>
                        <div class="bento-chip"><i class="fa-brands fa-youtube" style="color: #FF0000;"></i> YouTube</div>
                        <div class="bento-chip"><i class="fa-brands fa-spotify" style="color: #1DB954;"></i> Spotify</div>
                        <div class="bento-chip"><i class="fa-brands fa-github"></i> GitHub</div>
                        <div class="bento-chip"><i class="fa-solid fa-envelope" style="color: #3B82F6;"></i> Contact</div>
                    </div>
                </div>

                <div class="bento-card liquid-glass">
                    <div class="bento-icon-box">
                        <i class="fa-solid fa-id-card-clip"></i>
                    </div>
                    <h3 class="bento-card-title">Personal Digital Identity</h3>
                    <p class="bento-card-desc">
                        Your username is your direct public identity. Claim your personal root-level URL without bulky subdirectories or confusing redirects.
                    </p>
                </div>

                <div class="bento-card liquid-glass">
                    <div class="bento-icon-box">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                    </div>
                    <h3 class="bento-card-title">Beautiful Custom Profiles</h3>
                    <p class="bento-card-desc">
                        Liquid glass aesthetics, fluid typography, translucent card textures, and soft ambient illumination make your profile look like a work of art.
                    </p>
                </div>

                <div class="bento-card liquid-glass">
                    <div class="bento-icon-box">
                        <i class="fa-solid fa-share-nodes"></i>
                    </div>
                    <h3 class="bento-card-title">One Link to Share Everywhere</h3>
                    <p class="bento-card-desc">
                        Use your MY-TAP URL in your social bios, email signatures, messaging cards, and NFC hardware tags for effortless one-tap sharing.
                    </p>
                </div>

                <div class="bento-card liquid-glass">
                    <div class="bento-icon-box">
                        <i class="fa-solid fa-sliders"></i>
                    </div>
                    <h3 class="bento-card-title">Easy Profile Management</h3>
                    <p class="bento-card-desc">
                        Update your links, headline, and bio in seconds from your dashboard with immediate real-time synchronization.
                    </p>
                </div>

                <div class="bento-card bento-col-span-2 liquid-glass">
                    <div class="bento-icon-box">
                        <i class="fa-solid fa-mobile-screen-button"></i>
                    </div>
                    <h3 class="bento-card-title">Responsive on Every Device</h3>
                    <p class="bento-card-desc">
                        Engineered with sub-pixel precision to render with pixel-perfect clarity on high-density mobile screens, tablets, laptops, and ultra-wide desktop monitors alike.
                    </p>
                    <div class="bento-preview-chips">
                        <div class="bento-chip"><i class="fa-solid fa-mobile"></i> iOS & Android</div>
                        <div class="bento-chip"><i class="fa-solid fa-tablet-screen-button"></i> Tablets & iPads</div>
                        <div class="bento-chip"><i class="fa-solid fa-laptop"></i> Laptops</div>
                        <div class="bento-chip"><i class="fa-solid fa-desktop"></i> Desktop Displays</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="how-section scroll-fade" id="how-it-works">
            <div class="section-header">
                <span class="section-eyebrow">How It Works</span>
                <h2 class="section-title">Live in Less Than Sixty Seconds</h2>
                <p class="section-desc">
                    Three simple steps to claim your handle, build your digital hub, and connect with your audience.
                </p>
            </div>

            <div class="steps-grid">
                <div class="step-card liquid-glass">
                    <div class="step-num-badge">01</div>
                    <h3 class="step-title">Claim Your Username</h3>
                    <p class="step-desc">
                        Choose your distinct handle. It becomes your root-level link directly at <?= e($displayDomain) ?>yourname with instant reservation.
                    </p>
                </div>

                <div class="step-card liquid-glass">
                    <div class="step-num-badge">02</div>
                    <h3 class="step-title">Curate Your Links</h3>
                    <p class="step-desc">
                        Add your social profiles, digital portfolio, music streams, projects, and custom buttons with customized icons and labels.
                    </p>
                </div>

                <div class="step-card liquid-glass">
                    <div class="step-num-badge">03</div>
                    <h3 class="step-title">Share Everywhere</h3>
                    <p class="step-desc">
                        Paste your link in your social bios, send it in chats, or write it directly onto an NFC tap card to share your identity in physical spaces.
                    </p>
                </div>
            </div>
        </section>

        <section class="showcase-section scroll-fade" id="showcase">
            <div class="section-header">
                <span class="section-eyebrow">Device Showcase</span>
                <h2 class="section-title">Engineered for the Modern Web</h2>
                <p class="section-desc">
                    A refined liquid glass experience calibrated for fluid performance and instant engagement.
                </p>
            </div>

            <div class="showcase-grid">
                <div class="phone-mockup-wrapper">
                    <div class="liquid-phone">
                        <div class="phone-notch-pill"></div>
                        <div class="phone-screen-content">
                            <div class="phone-avatar">AA</div>
                            <div class="phone-user-name">
                                <span>Ammar Ahmed</span>
                                <i class="fa-solid fa-circle-check verified-tick"></i>
                            </div>
                            <div class="phone-handle">@ammar-ahmed</div>

                            <div class="phone-links-stack">
                                <div class="phone-link-pill">
                                    <span>Portfolio & Work</span>
                                    <i class="fa-solid fa-arrow-right" style="font-size: 11px; color: var(--color-deep-pink);"></i>
                                </div>
                                <div class="phone-link-pill">
                                    <span>GitHub Repositories</span>
                                    <i class="fa-solid fa-arrow-right" style="font-size: 11px; color: var(--color-deep-pink);"></i>
                                </div>
                                <div class="phone-link-pill">
                                    <span>Instagram Inspiration</span>
                                    <i class="fa-solid fa-arrow-right" style="font-size: 11px; color: var(--color-deep-pink);"></i>
                                </div>
                                <div class="phone-link-pill">
                                    <span>Schedule a Meeting</span>
                                    <i class="fa-solid fa-arrow-right" style="font-size: 11px; color: var(--color-deep-pink);"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="showcase-telemetry-stack">
                    <div class="telemetry-card liquid-glass">
                        <div class="telemetry-icon-box">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                        <div>
                            <div class="telemetry-title">Real-Time Click Telemetry</div>
                            <div class="telemetry-desc">
                                Track every tap and visit with accurate analytics to understand which links resonate most with your audience.
                            </div>
                        </div>
                    </div>

                    <div class="telemetry-card liquid-glass">
                        <div class="telemetry-icon-box">
                            <i class="fa-solid fa-qrcode"></i>
                        </div>
                        <div>
                            <div class="telemetry-title">Built-In Dynamic QR Engine</div>
                            <div class="telemetry-desc">
                                Generate high-resolution scannable QR codes matched to your profile palette for print, packaging, and presentations.
                            </div>
                        </div>
                    </div>

                    <div class="telemetry-card liquid-glass">
                        <div class="telemetry-icon-box">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <div>
                            <div class="telemetry-title">Zero Bloat & Edge Caching</div>
                            <div class="telemetry-desc">
                                Clean semantic markup ensures sub-100 millisecond response times worldwide across every connection type.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="cta-section scroll-fade">
            <div class="cta-card">
                <span class="cta-badge">Get Started Today</span>
                <h2 class="cta-title">Your Digital World. <br>One Beautiful Link.</h2>
                <p class="cta-desc">
                    Claim your personalized handle today and connect your audience to everything you create across the web.
                </p>
                <div class="cta-claim-wrapper">
                    <form class="claim-form" action="<?= e(register_url()) ?>" method="GET">
                        <div class="claim-input-box">
                            <div class="claim-prefix">
                                <i class="fa-solid fa-link"></i>
                                <span><?= e($displayDomain) ?></span>
                            </div>
                            <input type="text" name="claim" id="cta-claim-input" class="claim-input" placeholder="yourname" autocomplete="off" spellcheck="false" required pattern="[a-zA-Z0-9_-]+">
                            <input type="hidden" name="username" id="cta-claim-hidden">
                        </div>
                        <button type="submit" class="liquid-btn-primary claim-btn">
                            <span>Claim My Link</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </form>
                </div>
                <div class="cta-reassurance">
                    <span>100% Free Tier · Instant 60-Second Setup · No Credit Card Required</span>
                </div>
            </div>
        </section>

        <div class="footer-wrapper">
            <footer class="liquid-footer liquid-glass">
                <div class="footer-brand-side">
                    <div class="brand-icon-box" style="width: 32px; height: 32px; font-size: 14px;">
                        <i class="fa-solid fa-bolt"></i>
                    </div>
                    <span class="brand-title" style="font-size: 18px;">MY-TAP</span>
                </div>

                <ul class="footer-links">
                    <li><a href="#features" class="footer-link">Features</a></li>
                    <li><a href="#creators" class="footer-link">Creators</a></li>
                    <li><a href="#how-it-works" class="footer-link">How It Works</a></li>
                    <li><a href="#showcase" class="footer-link">Showcase</a></li>
                    <li><a href="<?= e(login_url()) ?>" class="footer-link">Sign In</a></li>
                    <li><a href="<?= e(register_url()) ?>" class="footer-link">Register</a></li>
                    <li><a href="<?= e(dashboard_url('index.php')) ?>" class="footer-link">Dashboard</a></li>
                </ul>

                <div class="footer-copy">
                    &copy; <?= date('Y') ?> MY-TAP. All rights reserved.
                </div>
            </footer>
        </div>
    </div>

    <script>
        document.documentElement.classList.add('js-animated');

        const heroClaimInput = document.getElementById('hero-claim-input');
        const heroPreviewHandle = document.getElementById('hero-preview-handle');
        const ctaClaimInput = document.getElementById('cta-claim-input');
        const mobileToggleBtn = document.getElementById('mobile-toggle-btn');
        const mobileDropdown = document.getElementById('mobile-dropdown');

        function sanitizeHandle(val) {
            return val.toLowerCase().replace(/[^a-z0-9_-]/g, '');
        }

        const heroClaimHidden = document.getElementById('hero-claim-hidden');
        const ctaClaimHidden = document.getElementById('cta-claim-hidden');

        if (heroClaimInput && heroPreviewHandle) {
            heroClaimInput.addEventListener('input', function() {
                const clean = sanitizeHandle(this.value);
                this.value = clean;
                if (heroClaimHidden) heroClaimHidden.value = clean;
                heroPreviewHandle.textContent = clean.length > 0 ? clean : 'yourname';
                if (ctaClaimInput && !ctaClaimInput.matches(':focus')) {
                    ctaClaimInput.value = clean;
                    if (ctaClaimHidden) ctaClaimHidden.value = clean;
                }
            });
        }

        if (ctaClaimInput) {
            ctaClaimInput.addEventListener('input', function() {
                const clean = sanitizeHandle(this.value);
                this.value = clean;
                if (ctaClaimHidden) ctaClaimHidden.value = clean;
                if (heroClaimInput && !heroClaimInput.matches(':focus')) {
                    heroClaimInput.value = clean;
                    if (heroClaimHidden) heroClaimHidden.value = clean;
                    if (heroPreviewHandle) {
                        heroPreviewHandle.textContent = clean.length > 0 ? clean : 'yourname';
                    }
                }
            });
        }

        if (mobileToggleBtn && mobileDropdown) {
            mobileToggleBtn.addEventListener('click', function() {
                const isOpen = mobileDropdown.style.display === 'block';
                mobileDropdown.style.display = isOpen ? 'none' : 'block';
            });

            document.querySelectorAll('.mobile-link').forEach(function(link) {
                link.addEventListener('click', function() {
                    mobileDropdown.style.display = 'none';
                });
            });
        }

        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -20px 0px'
        };

        const revealObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, observerOptions);

        document.querySelectorAll('.scroll-fade').forEach(function(elem) {
            revealObserver.observe(elem);
        });

        const stage = document.getElementById('hero-visual-stage');
        const card = document.getElementById('hero-main-card');

        if (stage && card && window.matchMedia('(pointer: fine)').matches) {
            stage.addEventListener('mousemove', function(e) {
                const rect = stage.getBoundingClientRect();
                const x = e.clientX - rect.left - (rect.width / 2);
                const y = e.clientY - rect.top - (rect.height / 2);
                const rotateX = (-y / (rect.height / 2)) * 8;
                const rotateY = (x / (rect.width / 2)) * 8;
                card.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateZ(10px)`;
            });

            stage.addEventListener('mouseleave', function() {
                card.style.transform = 'rotateX(0deg) rotateY(0deg) translateZ(0)';
            });
        }
    </script>
</body>
</html>
    <?php
    exit;
}

if (is_username_reserved($reqUser)) {
    $user = null;
} else {
    $stmtUser = $pdo->prepare('SELECT id, name, username, handle, bio, photo, status FROM users WHERE username = ? LIMIT 1');
    $stmtUser->execute([$reqUser]);
    $user = $stmtUser->fetch();
    if (!$user) {
        try {
            $stmtAlias = $pdo->prepare('SELECT u.username FROM username_aliases a JOIN users u ON u.id = a.user_id WHERE a.old_username = ? LIMIT 1');
            $stmtAlias->execute([$reqUser]);
            $canonicalName = $stmtAlias->fetchColumn();
            if ($canonicalName) {
                header('Location: ' . user_url($canonicalName), true, 301);
                exit;
            }
        } catch (Throwable $e) {}
    }
}

if (!$user || $user['status'] !== 'active') {
    http_response_code(404);
    $pageTitle = 'Profile Not Found — Wander';
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Profile Not Found — Wander</title>
<link rel="stylesheet" href="<?= asset_url('css/main.css') ?>">
</head>
<body style="min-height:100vh;display:grid;place-items:center;background:#0e1116;color:#f8fafc;font-family:system-ui,-apple-system,sans-serif">
  <div style="text-align:center;padding:32px;max-width:440px">
    <h1 style="font-size:32px;margin-bottom:10px">Profile Not Found</h1>
    <p style="color:rgba(255,255,255,0.6);margin-bottom:24px">The profile you requested does not exist or has been suspended.</p>
    <a href="<?= auth_url('signup.php') ?>" class="btn" style="padding:10px 20px;border-radius:99px;background:#fff;color:#111;text-decoration:none;font-weight:600;display:inline-block">Create Your Own Page</a>
    <p style="margin-top:16px"><a href="<?= auth_url('login.php') ?>" style="color:rgba(255,255,255,0.5);font-size:13px;text-decoration:underline">Sign in to account</a></p>
  </div>
</body>
</html>
    <?php
    exit;
}

http_response_code(200);

$userId = (int)$user['id'];
$ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
$visitor = $_COOKIE['wv'] ?? '';
if (!preg_match('/^[a-f0-9]{32}$/', $visitor)) {
    $visitor = bin2hex(random_bytes(16));
}
setcookie('wv', $visitor, ['expires' => time() + 31536000, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
if (!isset($_COOKIE['wvs_' . $userId]) && !preg_match('/bot|crawl|spider|slurp|preview|facebookexternalhit/i', $ua)) {
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    $country = strtoupper(substr($_SERVER['HTTP_CF_IPCOUNTRY'] ?? '', 0, 2));
    if (!preg_match('/^[A-Z]{2}$/', $country) || $country === 'XX') $country = null;
    try {
        $pdo->prepare('INSERT INTO visits (user_id, visitor, ip_hash, country, referrer, ua) VALUES (?,?,?,?,?,?)')
            ->execute([$userId, $visitor, hash('sha256', $ip . 'wander-salt'), $country,
                       substr($_SERVER['HTTP_REFERER'] ?? '', 0, 255), $ua]);
    } catch (Throwable $e) {}
    setcookie('wvs_' . $userId, '1', ['expires' => time() + 1800, 'path' => '/', 'samesite' => 'Lax']);
}

$stmtLinks = $pdo->prepare('SELECT id, name, url FROM links WHERE user_id = ? ORDER BY sort_order, id');
$stmtLinks->execute([$userId]);
$links = $stmtLinks->fetchAll();

$profile = [
    'name'   => $user['name']   ?? 'Your Name',
    'handle' => $user['handle'] ? $user['handle'] : ('@' . ($user['username'] ?? 'username')),
    'bio'    => $user['bio']    ?? '',
    'photo'  => photo_url($user['photo'] ?? ''),
];
$canonicalUrl = full_user_url($user['username']);
$pageTitle = $profile['name'] . ' (@' . $user['username'] . ') — Wander';
$metaDesc = $profile['bio'] !== '' ? $profile['bio'] : ('Explore links and social destinations for ' . $profile['name'] . ' on Wander.');
$ogImage = $user['photo'] ? photo_url($user['photo']) : '';
if ($ogImage && !preg_match('#^https?://#i', $ogImage)) {
    $ogImage = app_origin() . $ogImage;
}
$J = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($metaDesc) ?>">
<link rel="canonical" href="<?= e($canonicalUrl) ?>">
<meta property="og:type" content="profile">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:description" content="<?= e($metaDesc) ?>">
<meta property="og:url" content="<?= e($canonicalUrl) ?>">
<?php if ($ogImage): ?>
<meta property="og:image" content="<?= e($ogImage) ?>">
<?php endif; ?>
<meta name="twitter:card" content="<?= $ogImage ? 'summary_large_image' : 'summary' ?>">
<meta name="twitter:title" content="<?= e($pageTitle) ?>">
<meta name="twitter:description" content="<?= e($metaDesc) ?>">
<?php if ($ogImage): ?>
<meta name="twitter:image" content="<?= e($ogImage) ?>">
<?php endif; ?>
<meta name="theme-color" content="#2a2f38">
<link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@500;600;700&family=Instrument+Serif&display=swap" rel="stylesheet">
<style>
  :root{
    --bg:#2a2f38;
    --gl-font-sans:"Inter Tight",sans-serif;
    --gl-font-city:"Instrument Serif",serif;
    --ease-out:cubic-bezier(.16,1,.3,1);
    --ease-io:cubic-bezier(.65,0,.35,1);
  }
  *{box-sizing:border-box;-webkit-tap-highlight-color:transparent}
  [hidden]{display:none!important}
  button{font:inherit;color:inherit;background:none;border:0;padding:0;margin:0;cursor:pointer}
  h1,h2,p{margin:0}
  html,body{height:100%;margin:0;overflow:hidden;overscroll-behavior:none;-webkit-text-size-adjust:100%;
    background:var(--bg);color:#f3f1ec;font-family:var(--gl-font-sans)}
  #app{position:fixed;inset:0;height:100dvh;touch-action:none;user-select:none;-webkit-user-select:none;overflow:hidden;
    background:radial-gradient(120% 90% at 30% 40%,#323843 0%,#2a2f38 55%,#22262d 100%)}

  .top{position:absolute;top:0;left:0;right:0;z-index:10;display:flex;align-items:flex-start;justify-content:space-between;
    pointer-events:none;padding:max(22px,env(safe-area-inset-top)) max(22px,4.5vw) 0}
  .top-title{margin-top:8px}
  .top-sub{font-size:clamp(11px,1.3vw,15px);color:#ffffffb3}
  .top h1{margin-top:4px;font-weight:600;letter-spacing:-.03em;line-height:.95;font-size:clamp(26px,min(7.2vw,6vh),64px)}
  .top-btns{display:flex;gap:8px;pointer-events:auto}

  .city{position:absolute;left:0;top:0;white-space:nowrap;transform-origin:0 50%;cursor:pointer;line-height:1;
    font-family:var(--gl-font-city);will-change:transform,opacity}
  .arc{position:absolute;border:1.5px solid #ffffff59;border-radius:50%;pointer-events:none;
    -webkit-mask-image:linear-gradient(to bottom,transparent 12%,#000 50%,transparent 88%);
    mask-image:linear-gradient(to bottom,transparent 12%,#000 50%,transparent 88%)}
  .track{position:absolute;right:max(20px,4.5vw);top:20vh;bottom:24vh;width:4px;border-radius:4px;background:#ffffff1f}
  .thumb{position:absolute;left:50%;width:24px;height:44px;margin-left:-12px;border-radius:14px;background:#fff;display:grid;place-items:center;cursor:grab}
  .glass{background:#ffffff1f;backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px)}

  .dock{width:54px;height:54px;border-radius:50%;display:grid;place-items:center;flex:none;line-height:1;color:#fff}
  .dock-home{background:#fff;color:#222}
  .dock-plus{font-size:24px}
  .ibtn{width:42px;height:42px;border-radius:50%;display:grid;place-items:center;color:#fff}
  .bottom{position:absolute;z-index:10;left:0;right:0;display:flex;align-items:center;justify-content:center;gap:12px;
    bottom:max(22px,env(safe-area-inset-bottom))}
  .pbtn{display:flex;align-items:center;gap:10px;flex:none;height:54px;padding:0 20px 0 5px;border-radius:999px;
    font-size:14px;font-weight:600;color:#fff;transition:opacity .22s ease}
  #soundBtn[aria-pressed=true] .off,#soundBtn[aria-pressed=false] .on{display:none}
  .in{opacity:0;animation:rise .9s cubic-bezier(.2,.7,.2,1) forwards}
  @keyframes rise{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:none}}
  :focus-visible{outline:2px solid #fff;outline-offset:3px}

  .nav-av{position:relative;display:block;width:44px;height:44px;flex:none;border-radius:50%;overflow:hidden;
    background:linear-gradient(135deg,#f5c96b,#ea7c8c)}
  .nav-av img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;border-radius:50%}

  .pf-bg{position:absolute;inset:0;z-index:30;background:#12151acc;
    backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);
    opacity:0;pointer-events:none;transition:opacity .5s ease;will-change:opacity}
  .pf-bg.open{opacity:1;pointer-events:auto}

  .pf-wrap{position:absolute;z-index:31;left:50%;bottom:max(22px,env(safe-area-inset-bottom));width:min(86vw,330px);
    transform:translateX(-50%);pointer-events:none}

  .pf-wrap::before{content:"";position:absolute;inset:0;border-radius:24px;box-shadow:0 24px 48px -10px #000c;
    opacity:0;transition:opacity .35s ease}
  .pf-wrap.open::before{opacity:1;transition:opacity .6s ease .1s}

  .pf-card{position:relative;width:100%;max-height:calc(100dvh - 44px);overflow-y:auto;scrollbar-width:none;outline:none;
    padding:16px 16px 16px;text-align:left;
    background:linear-gradient(160deg,#3d4451 0%,#2c313a 60%,#252931 100%);
    clip-path:inset(var(--ct,100%) var(--cr,0px) 0 var(--cl,0px) round 24px);
    opacity:0;pointer-events:none;will-change:clip-path,opacity;
    transition:clip-path .55s var(--ease-io),opacity .1s linear .45s}
  .pf-card::-webkit-scrollbar{display:none}
  .pf-card.open{clip-path:inset(0 0 0 0 round 24px);opacity:1;pointer-events:auto;
    transition:clip-path .75s var(--ease-out),opacity .05s linear 0s}

  .pf-head{display:flex;flex-direction:row;align-items:center;gap:14px;}
  .pf-photo{position:relative;flex:none;width:72px;height:72px;border-radius:50%;padding:3px;
    background:conic-gradient(from 200deg,#f5c96b,#ea7c8c,#7fb4ff,#f5c96b);
    box-shadow:0 10px 24px -10px #ea7c8c88}
  .pf-inner{width:100%;height:100%;border-radius:50%;overflow:hidden;position:relative;
    display:grid;place-items:center;font-family:var(--gl-font-city);font-size:30px;color:#fff;
    background:linear-gradient(135deg,#f5c96b,#ea7c8c)}
  .pf-inner img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
  .pf-info{min-width:0;flex:1;text-align:left}
  .pf-name{font-family:var(--gl-font-city);font-weight:400;font-size:25px;line-height:1.05;letter-spacing:-.01em;color:#fff;overflow-wrap:anywhere}
  .pf-handle{margin-top:3px;font-size:12.5px;color:#ffffff99;overflow-wrap:anywhere}
  .pf-bio{margin-top:12px;font-size:13px;line-height:1.5;color:#e6e3dd}
  .pf-bio:empty{display:none}
  .pf-x{position:absolute;right:10px;top:10px;width:28px;height:28px;border-radius:50%;background:#ffffff1f;color:#fff;display:grid;place-items:center;z-index:2}
  .pf-stats{display:grid;grid-template-columns:repeat(3,1fr);margin-top:12px;border-radius:14px;background:#00000026;border:1px solid #ffffff1a;padding:8px 4px;text-align:center}
  .pf-stats.off{display:none}
  .pf-stat+.pf-stat{border-left:1px solid #ffffff1f}
  .pf-stat b{display:block;font:600 16px var(--gl-font-sans);color:#fff;font-variant-numeric:tabular-nums}
  .pf-stat span{font-size:11px;color:#ffffff99}
  .pf-live{display:inline-block;width:7px;height:7px;border-radius:50%;background:#5bd68a;margin-right:6px;vertical-align:middle}

  .st{opacity:0;transform:translateY(14px);
    transition:opacity .22s ease,transform .3s ease}
  .pf-card.open .st{opacity:1;transform:none;
    transition:opacity .55s ease calc(.18s + var(--i)*.06s),
               transform .7s var(--ease-out) calc(.18s + var(--i)*.06s)}
  .pf-photo.st{transform:scale(.7)}
  .pf-card.open .pf-photo.st{transform:none}

  .toast{position:absolute;z-index:40;left:50%;transform:translateX(-50%);bottom:112px;padding:12px 20px;border-radius:999px;
    font-size:14px;background:#000a;white-space:nowrap}

  @media (max-width:380px){.pf-photo{width:64px;height:64px}.pf-name{font-size:22px}.pf-head{gap:12px}}
  @media (prefers-reduced-motion:reduce){*{animation:none!important;transition:none!important}.in{opacity:1}.st{opacity:1;transform:none}}
</style>
</head>
<body>
<main id="app">

  <header class="top">
    <div class="in top-title" style="animation-delay:.1s">
      <div class="top-sub">Select a social</div>
      <h1>Choose your<br>destination</h1>
    </div>
    <div class="top-btns in" style="animation-delay:.3s">
      <button id="soundBtn" class="ibtn glass" aria-pressed="true" aria-label="Toggle sound">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <path d="M4 9v6h4l5 4V5L8 9z"/><path class="on" d="M16 9a4 4 0 010 6M18.5 6.5a8 8 0 010 11"/><path class="off" d="M17 9l5 6M22 9l-5 6"/></svg>
      </button>
      <?php if (is_logged_in()): ?>
        <a href="<?= dashboard_url('index.php') ?>" class="ibtn glass" title="Go to Dashboard" style="display:grid;place-items:center;text-decoration:none">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11 12 3l9 8v10H3z"/></svg>
        </a>
      <?php else: ?>
        <a href="<?= auth_url('login.php') ?>" class="ibtn glass" title="Sign In" style="display:grid;place-items:center;text-decoration:none">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
        </a>
      <?php endif; ?>
    </div>
  </header>

  <div class="arc" id="arc"></div>
  <div id="cities"></div>

  <div class="track" id="track">
    <div class="thumb" id="thumb" role="slider" tabindex="0" aria-label="Social selector" aria-valuemin="0">
      <svg width="12" height="26" viewBox="0 0 12 26" fill="none" stroke="#222" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M2 7l4-4 4 4M2 19l4 4 4-4M2 12h8M2 15h8"/></svg>
    </div>
  </div>

  <nav class="bottom in" style="animation-delay:.5s">
    <button class="dock dock-home" aria-label="Home">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"><path d="M3 11 12 3l9 8v10H3z"/></svg></button>
    <button id="plusBtn" class="dock dock-plus glass" aria-label="Open profile" aria-haspopup="dialog">+</button>
    <button id="profileBtn" class="pbtn glass" aria-label="Profile" aria-haspopup="dialog">
      <span class="nav-av"><img id="navImg" alt="" hidden></span>Profile</button>
  </nav>

  <div class="pf-bg" id="pfBg"></div>
  <div class="pf-wrap" id="pfWrap">
    <section class="pf-card" id="pfCard" role="dialog" aria-modal="true" aria-labelledby="pfName" aria-hidden="true" tabindex="-1">
      <button class="pf-x" id="pfClose" aria-label="Close">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
      <div class="pf-head">
        <div class="pf-photo st" style="--i:0">
          <div class="pf-inner"><span id="pfInitial"></span><img id="pfImg" alt="" hidden></div>
        </div>
        <div class="pf-info">
          <h2 class="pf-name st" id="pfName" style="--i:1"></h2>
          <p class="pf-handle st" id="pfHandle" style="--i:2"></p>
        </div>
      </div>
      <p class="pf-bio st" id="pfBio" style="--i:3"></p>
      <div class="pf-stats off st" id="pfStats" style="--i:4">
        <div class="pf-stat"><b data-k="followers_count">—</b><span>Followers</span></div>
        <div class="pf-stat"><b data-k="follows_count">—</b><span>Following</span></div>
        <div class="pf-stat"><b data-k="media_count">—</b><span>Posts</span></div>
      </div>
    </section>
  </div>

  <div id="toast" class="toast" hidden></div>
</main>

<script>
const API_URL="";
const PROFILE=<?= json_encode($profile, $J) ?>;

const LINKS_DATA=<?= json_encode($links, $J) ?>;
const LINKS={},IDS={};
LINKS_DATA.forEach(l=>{LINKS[l.name]=l.url;IDS[l.name]=l.id});
const CITIES=Object.keys(LINKS);
const N=CITIES.length, STEP=14*Math.PI/180, clamp=v=>Math.max(0,Math.min(N-1,v));
let target=Math.max(0,Math.min(4,N-1)),cur=target,hapIdx=target,soundOn=true,ctx=null,noiseBuf=null,W,H,R,cx,cy,big;

const app=document.getElementById('app'),wrap=document.getElementById('cities'),arc=document.getElementById('arc'),
 track=document.getElementById('track'),thumb=document.getElementById('thumb');
const els=CITIES.map((c,i)=>{const s=document.createElement('span');s.className='city';s.textContent=c;
 s.onclick=()=>{unlock();if(Math.abs(i-cur)<.5)openCity(c);else setT(i)};wrap.appendChild(s);return s});
thumb.setAttribute('aria-valuemax',Math.max(0,N-1));

function layout(){
 W=innerWidth;H=innerHeight;cy=H*.52;
 if(W<700){cx=-W*.1;R=W*.52;big=W*.12}
 else if(H>W&&W<1100){cx=-W*.06;R=W*.5;big=W*.085}
 else if(H<520){cx=W*.08;R=Math.min(W*.3,H*.9);big=H*.13}
 else{cx=W*.1;R=Math.min(W*.32,H*.62);big=Math.min(W*.05,H*.1)}
 const r=R*.8;
 Object.assign(arc.style,{width:r*2+'px',height:r*2+'px',left:(cx-r)+'px',top:(cy-r)+'px'});
}

function render(){
 cur+=(target-cur)*.16;if(Math.abs(target-cur)<.001)cur=target;
 els.forEach((el,i)=>{
  const d=i-cur,a=d*STEP,ad=Math.abs(d);
  const x=cx+R*Math.cos(a),y=cy+R*Math.sin(a);
  const size=ad<1?big+(big*.46-big)*ad:Math.max(big*.28,big*.46-(ad-1)*big*.05);
  const act=ad<.5;
  el.style.fontSize=size+'px';
  el.style.opacity=Math.max(0,1-ad*.28);
  el.style.color=act?'#fff':'#aeb3bb';
  el.style.textTransform=act?'uppercase':'none';
  el.style.transform=`translate(${x}px,${y}px) translateY(-50%) rotate(${a}rad)`;
  el.style.pointerEvents=ad>3.5?'none':'auto';
 });
 thumb.style.top=(N>1?(cur/(N-1)):0)*(track.clientHeight-44)+'px';
 thumb.setAttribute('aria-valuenow',Math.round(cur));
 requestAnimationFrame(render);
}

function unlock(){
 if(!ctx){try{ctx=new(window.AudioContext||window.webkitAudioContext)()}catch(e){}}
 if(ctx&&ctx.state!=='running')ctx.resume();
}
['pointerdown','touchstart','keydown','wheel','click'].forEach(ev=>addEventListener(ev,unlock,{passive:true}));
try{unlock()}catch(e){}
function sound(){
 if(!soundOn||!ctx)return;const t=ctx.currentTime;
 if(!noiseBuf){noiseBuf=ctx.createBuffer(1,Math.floor(ctx.sampleRate*.03),ctx.sampleRate);
  const d=noiseBuf.getChannelData(0);for(let i=0;i<d.length;i++)d[i]=Math.random()*2-1}
 const n=ctx.createBufferSource();n.buffer=noiseBuf;
 const bp=ctx.createBiquadFilter();bp.type='bandpass';bp.frequency.value=3200;bp.Q.value=1.2;
 const g=ctx.createGain();g.gain.setValueAtTime(.55,t);g.gain.exponentialRampToValueAtTime(.001,t+.012);
 n.connect(bp).connect(g).connect(ctx.destination);n.start(t);n.stop(t+.02);
 const o=ctx.createOscillator(),og=ctx.createGain();
 o.type='sine';o.frequency.setValueAtTime(260,t);o.frequency.exponentialRampToValueAtTime(120,t+.02);
 og.gain.setValueAtTime(.22,t);og.gain.exponentialRampToValueAtTime(.001,t+.025);
 o.connect(og).connect(ctx.destination);o.start(t);o.stop(t+.03);
}
function haptic(){
 try{
  if(navigator.vibrate&&navigator.vibrate(10))return;
  const l=document.createElement('label');l.setAttribute('aria-hidden','true');l.style.display='none';
  const i=document.createElement('input');i.type='checkbox';i.setAttribute('switch','');
  l.appendChild(i);document.body.appendChild(l);l.click();setTimeout(()=>l.remove(),60);
 }catch(e){}
}
function setT(v){
 target=clamp(v);const i=Math.round(target);
 if(i!==hapIdx){hapIdx=i;haptic();sound()}
}

const sb=document.getElementById('soundBtn');
sb.onclick=()=>{unlock();soundOn=!soundOn;sb.setAttribute('aria-pressed',soundOn);if(soundOn){sound()}haptic()};

app.addEventListener('wheel',e=>{e.preventDefault();unlock();setT(target+Math.sign(e.deltaY)*.45);
 clearTimeout(app._w);app._w=setTimeout(()=>target=Math.round(target),140)},{passive:false});
let dragY=null,startT=0,sliding=false;
app.addEventListener('pointerdown',e=>{unlock();if(e.target.closest('button,a,.track,label,.pf-card'))return;dragY=e.clientY;startT=target});
addEventListener('pointermove',e=>{
 if(dragY!==null)setT(startT-(e.clientY-dragY)/42);
 if(sliding){const r=track.getBoundingClientRect();setT(((e.clientY-r.top)/r.height)*(N-1))}});
addEventListener('pointerup',()=>{if(dragY!==null||sliding)target=Math.round(target);dragY=null;sliding=false});
addEventListener('pointercancel',()=>{dragY=null;sliding=false});
thumb.addEventListener('pointerdown',e=>{sliding=true;unlock();e.preventDefault()});
track.addEventListener('pointerdown',e=>{if(e.target.closest('.thumb'))return;unlock();const r=track.getBoundingClientRect();setT(Math.round(((e.clientY-r.top)/r.height)*(N-1)))});
addEventListener('keydown',e=>{
 if(pfOpen){if(e.key==='Escape')closeProfile();return}
 if(['ArrowDown','ArrowRight'].includes(e.key))setT(Math.round(target)+1);
 if(['ArrowUp','ArrowLeft'].includes(e.key))setT(Math.round(target)-1)});
function toast(msg,ms){
 const t=document.getElementById('toast');t.textContent=msg;t.hidden=false;
 clearTimeout(t._h);t._h=setTimeout(()=>t.hidden=true,ms);
}

function openCity(name){
 toast('Opening '+name+'…',1600);
 try{
  const fd=new FormData();fd.append('id',IDS[name]);
  if(!(navigator.sendBeacon&&navigator.sendBeacon('api.php',fd)))
   fetch('api.php',{method:'POST',body:fd,keepalive:true});
 }catch(e){}
 window.open(LINKS[name],'_blank','noopener');
}

const pfBg=document.getElementById('pfBg'),pfCard=document.getElementById('pfCard'),pfWrap=document.getElementById('pfWrap'),
 pfImg=document.getElementById('pfImg'),pfInitial=document.getElementById('pfInitial'),
 navImg=document.getElementById('navImg'),
 profileBtn=document.getElementById('profileBtn');
let pfOpen=false,restoreT=null;

document.getElementById('pfName').textContent=PROFILE.name;
document.getElementById('pfHandle').textContent=PROFILE.handle;
document.getElementById('pfBio').textContent=PROFILE.bio;
pfInitial.textContent=(PROFILE.name.trim()[0]||'?').toUpperCase();

function setPhoto(src){
 if(!src){
  pfImg.hidden=true;pfInitial.hidden=false;
  navImg.hidden=true;navImg.removeAttribute('src');
  return;
 }
 pfImg.onload=()=>{pfImg.hidden=false;pfInitial.hidden=true};
 pfImg.onerror=()=>{pfImg.hidden=true;pfInitial.hidden=false};
 navImg.onload=()=>{navImg.hidden=false};
 navImg.onerror=()=>{navImg.hidden=true};
 pfImg.src=src;
 navImg.src=src;
}
setPhoto(PROFILE.photo);

const fmt=n=>n>=1e6?(n/1e6).toFixed(1).replace('.0','')+'M':n>=1e4?(n/1e3).toFixed(1).replace('.0','')+'K':String(n);
function countUp(el,to){
 const t0=performance.now(),D=900;
 (function f(t){const k=Math.min(1,(t-t0)/D),e=1-Math.pow(1-k,3);
  el.textContent=fmt(Math.round(to*e));if(k<1)requestAnimationFrame(f)})(t0);
}
let liveAt=0;
async function loadLive(){
 if(!API_URL||Date.now()-liveAt<60000)return;
 try{
  const r=await fetch(API_URL,{cache:'no-store'});if(!r.ok)throw 0;
  const d=await r.json();liveAt=Date.now();
  if(d.name||d.username){document.getElementById('pfName').textContent=d.name||d.username;
   pfInitial.textContent=(d.name||d.username)[0].toUpperCase()}
  if(d.username)document.getElementById('pfHandle').innerHTML='<i class="pf-live"></i>@'+String(d.username).replace(/[<>&"']/g,'');
  if(d.biography)document.getElementById('pfBio').textContent=d.biography;
  if(d.profile_picture_url)setPhoto(d.profile_picture_url);
  const stats=document.getElementById('pfStats');
  if(['followers_count','follows_count','media_count'].some(k=>typeof d[k]==='number')){
   stats.classList.remove('off');
   stats.querySelectorAll('[data-k]').forEach(b=>{
    if(typeof d[b.dataset.k]==='number')setTimeout(()=>countUp(b,d[b.dataset.k]),700)});
  }
 }catch(e){
  toast('Could not load live data',2200);
 }
}

function morphFrom(btn){
 const b=btn.getBoundingClientRect(),c=pfCard.getBoundingClientRect();
 pfCard.style.setProperty('--cl',Math.max(0,b.left-c.left)+'px');
 pfCard.style.setProperty('--cr',Math.max(0,c.right-b.right)+'px');
 pfCard.style.setProperty('--ct',Math.max(0,c.height-b.height)+'px');
}

function openProfile(){
 if(pfOpen)return;
 clearTimeout(restoreT);
 unlock();haptic();loadLive();
 morphFrom(profileBtn);
 void pfCard.offsetWidth;
 pfOpen=true;
 profileBtn.style.opacity='0';
 pfBg.classList.add('open');pfWrap.classList.add('open');
 pfCard.classList.add('open');pfCard.setAttribute('aria-hidden','false');
 setTimeout(()=>{if(pfOpen)pfCard.focus({preventScroll:true})},450);
}
function closeProfile(){
 if(!pfOpen)return;pfOpen=false;haptic();
 morphFrom(profileBtn);
 pfBg.classList.remove('open');pfWrap.classList.remove('open');
 pfCard.classList.remove('open');pfCard.setAttribute('aria-hidden','true');

 restoreT=setTimeout(()=>{profileBtn.style.opacity=''},380);
 profileBtn.focus({preventScroll:true});
}
document.getElementById('plusBtn').onclick=openProfile;
profileBtn.onclick=openProfile;
document.getElementById('pfClose').onclick=closeProfile;
pfBg.onclick=closeProfile;

layout();addEventListener('resize',layout);
addEventListener('orientationchange',()=>setTimeout(layout,200));
requestAnimationFrame(render);
</script>
</body>
</html>