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
    http_response_code(200);
    $pageTitle = 'Wander — One Unified Link For Every Destination';
    $metaDesc = 'The gallery-grade digital identity platform. Claim your root-level username and unify your destinations with zero redirection delay.';
    $canonicalUrl = app_origin() . base_url('');
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($metaDesc) ?>">
<link rel="canonical" href="<?= e($canonicalUrl) ?>">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:description" content="<?= e($metaDesc) ?>">
<meta property="og:url" content="<?= e($canonicalUrl) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($pageTitle) ?>">
<meta name="twitter:description" content="<?= e($metaDesc) ?>">
<meta name="theme-color" content="#06080a">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Inter+Tight:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
  :root {
    --bg: #06080a;
    --card-bg: rgba(18, 22, 29, 0.72);
    --line: rgba(255, 255, 255, 0.08);
    --line-glow: rgba(245, 201, 107, 0.28);
    --fg: #ffffff;
    --fg-80: rgba(255, 255, 255, 0.82);
    --fg-60: rgba(255, 255, 255, 0.62);
    --fg-40: rgba(255, 255, 255, 0.40);
    --gold: #f5c96b;
    --gold-glow: rgba(245, 201, 107, 0.15);
    --cyan: #7fb4ff;
    --sans: "Inter Tight", -apple-system, BlinkMacSystemFont, sans-serif;
    --serif: "Instrument Serif", Georgia, serif;
    --mono: "JetBrains Mono", monospace;
    --ease-out: cubic-bezier(0.16, 1, 0.3, 1);
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  html, body {
    background-color: var(--bg);
    color: var(--fg);
    font-family: var(--sans);
    -webkit-font-smoothing: antialiased;
    text-rendering: optimizeLegibility;
    overflow-x: hidden;
    min-height: 100vh;
  }
  ::selection { background: var(--gold); color: #000; }
  .noise {
    position: fixed; inset: 0; pointer-events: none; z-index: 50; opacity: 0.038;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.8' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E");
  }
  .viewport-frame {
    padding: clamp(12px, 2vw, 24px);
    max-width: 1440px;
    margin: 0 auto;
    position: relative;
    z-index: 10;
  }
  .stage {
    background: radial-gradient(circle at 50% 0%, rgba(245, 201, 107, 0.07) 0%, rgba(10, 13, 18, 0.6) 45%, #06080a 85%);
    border: 1px solid var(--line);
    border-radius: clamp(20px, 3vw, 36px);
    position: relative;
    overflow: hidden;
    box-shadow: 0 32px 80px -20px rgba(0, 0, 0, 0.8);
  }
  .ambient-video {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0.28;
    mix-blend-mode: screen;
    pointer-events: none;
    z-index: 1;
  }
  .video-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(6, 8, 10, 0.2) 0%, rgba(6, 8, 10, 0.7) 60%, #06080a 100%);
    pointer-events: none;
    z-index: 2;
  }
  .stage-inner {
    position: relative;
    z-index: 5;
    padding: clamp(20px, 4vw, 48px);
    display: flex;
    flex-direction: column;
    min-height: calc(100vh - clamp(24px, 4vw, 48px));
  }
  .nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    background: rgba(14, 18, 24, 0.65);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid var(--line);
    border-radius: 999px;
    margin-bottom: clamp(40px, 7vh, 80px);
  }
  .brand {
    font-family: var(--serif);
    font-size: 26px;
    letter-spacing: -0.01em;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #fff;
    text-decoration: none;
  }
  .brand-badge {
    font-family: var(--mono);
    font-size: 10px;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    color: var(--gold);
    background: rgba(245, 201, 107, 0.12);
    border: 1px solid rgba(245, 201, 107, 0.3);
    padding: 2px 7px;
    border-radius: 999px;
  }
  .nav-menu {
    display: flex;
    align-items: center;
    gap: 24px;
  }
  .nav-link {
    color: var(--fg-60);
    text-decoration: none;
    font-size: 13.5px;
    font-weight: 500;
    transition: color 0.2s var(--ease-out);
  }
  .nav-link:hover { color: #fff; }
  .nav-actions {
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .pill-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 18px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.22s var(--ease-out);
    white-space: nowrap;
    border: 1px solid transparent;
  }
  .pill-solid {
    background: #ffffff;
    color: #06080a;
  }
  .pill-solid:hover {
    background: #f0f0ee;
    transform: translateY(-1px);
    box-shadow: 0 8px 24px -6px rgba(255, 255, 255, 0.35);
  }
  .pill-ghost {
    background: rgba(255, 255, 255, 0.05);
    color: var(--fg-80);
    border-color: var(--line);
  }
  .pill-ghost:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
    border-color: rgba(255, 255, 255, 0.2);
  }
  .hero-center {
    max-width: 860px;
    margin: 0 auto;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
  }
  .eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid var(--line);
    font-family: var(--mono);
    font-size: 11px;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: var(--fg-80);
    margin-bottom: 24px;
  }
  .pulse-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 10px #10b981;
    display: inline-block;
  }
  .h1-title {
    font-size: clamp(44px, 7.5vw, 84px);
    font-weight: 600;
    line-height: 0.98;
    letter-spacing: -0.038em;
    margin-bottom: 22px;
    color: #fff;
  }
  .serif-accent {
    font-family: var(--serif);
    font-weight: 400;
    font-style: italic;
    color: var(--gold);
    letter-spacing: -0.01em;
    padding-right: 4px;
  }
  .hero-desc {
    font-size: clamp(15px, 1.8vw, 18px);
    line-height: 1.55;
    color: var(--fg-60);
    max-width: 580px;
    margin-bottom: 36px;
  }
  .claim-pill-form {
    display: flex;
    align-items: center;
    background: rgba(14, 18, 25, 0.85);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 999px;
    padding: 6px 6px 6px 20px;
    width: 100%;
    max-width: 520px;
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.1);
    transition: all 0.25s var(--ease-out);
    margin-bottom: 32px;
  }
  .claim-pill-form:focus-within {
    border-color: var(--gold);
    box-shadow: 0 16px 44px rgba(0, 0, 0, 0.6), 0 0 0 3px rgba(245, 201, 107, 0.18);
  }
  .domain-tag {
    font-family: var(--mono);
    font-size: 13.5px;
    color: var(--fg-40);
    user-select: none;
    letter-spacing: -0.01em;
  }
  .claim-input-field {
    background: transparent;
    border: 0;
    outline: none;
    color: #fff;
    font-family: var(--mono);
    font-size: 14.5px;
    font-weight: 500;
    padding: 8px 10px;
    flex: 1;
    min-width: 0;
  }
  .claim-input-field::placeholder { color: var(--fg-40); }
  .claim-submit-btn {
    background: #ffffff;
    color: #06080a;
    border: 0;
    border-radius: 999px;
    padding: 12px 24px;
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s var(--ease-out);
    white-space: nowrap;
  }
  .claim-submit-btn:hover {
    background: var(--gold);
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(245, 201, 107, 0.35);
  }
  .creators-strip {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: center;
    margin-bottom: clamp(48px, 8vh, 88px);
  }
  .strip-label {
    font-family: var(--mono);
    font-size: 11px;
    color: var(--fg-40);
    letter-spacing: 0.1em;
    text-transform: uppercase;
    margin-right: 4px;
  }
  .creator-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid var(--line);
    color: var(--fg-80);
    text-decoration: none;
    font-size: 12px;
    font-family: var(--mono);
    transition: all 0.2s var(--ease-out);
  }
  .creator-tag:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
    border-color: rgba(255, 255, 255, 0.24);
    transform: translateY(-1px);
  }
  .creator-avatar {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #1e2430;
    display: inline-grid;
    place-items: center;
    font-size: 9px;
    font-weight: 700;
    color: var(--gold);
  }
  .preview-stage {
    perspective: 1200px;
    width: 100%;
    max-width: 440px;
    margin: 0 auto clamp(48px, 8vh, 88px);
  }
  .preview-card {
    background: rgba(18, 22, 30, 0.88);
    backdrop-filter: blur(30px);
    -webkit-backdrop-filter: blur(30px);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 32px;
    padding: 32px 24px;
    text-align: center;
    box-shadow: 0 40px 90px -20px rgba(0, 0, 0, 0.9), 0 0 0 1px rgba(255, 255, 255, 0.06);
    transform-style: preserve-3d;
    transition: transform 0.25s ease-out, box-shadow 0.25s ease-out;
  }
  .preview-card:hover {
    box-shadow: 0 50px 100px -20px rgba(0, 0, 0, 0.95), 0 0 40px rgba(245, 201, 107, 0.1);
  }
  .mock-avatar {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    margin: 0 auto 16px;
    border: 2px solid var(--gold);
    box-shadow: 0 0 24px rgba(245, 201, 107, 0.35);
    background: #1a202c url('/admin/uploads/avatars/d69537ec206c30f7.jpg') center/cover no-repeat;
    display: grid;
    place-items: center;
    font-size: 24px;
  }
  .mock-name {
    font-size: 19px;
    font-weight: 600;
    color: #fff;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
  }
  .verified-icon {
    width: 16px;
    height: 16px;
    fill: #38bdf8;
  }
  .mock-handle {
    font-family: var(--mono);
    font-size: 12.5px;
    color: var(--gold);
    margin-bottom: 12px;
  }
  .mock-bio {
    font-size: 13px;
    line-height: 1.5;
    color: var(--fg-60);
    margin-bottom: 24px;
    max-width: 320px;
    margin-left: auto;
    margin-right: auto;
  }
  .mock-destinations {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 24px;
  }
  .mock-link {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 13px 18px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    color: #fff;
    text-decoration: none;
    font-size: 13.5px;
    font-weight: 500;
    transition: all 0.2s var(--ease-out);
  }
  .mock-link:hover {
    background: rgba(255, 255, 255, 0.09);
    border-color: rgba(255, 255, 255, 0.2);
    transform: translateY(-1px);
  }
  .mock-link-stats {
    font-family: var(--mono);
    font-size: 11px;
    color: var(--fg-40);
  }
  .mock-footer-meta {
    padding-top: 16px;
    border-top: 1px solid var(--line);
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-family: var(--mono);
    font-size: 11px;
    color: var(--fg-40);
  }
  .bento-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    width: 100%;
    margin-bottom: clamp(48px, 8vh, 88px);
  }
  .bento-card {
    background: rgba(14, 18, 25, 0.6);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid var(--line);
    border-radius: 28px;
    padding: 32px 28px;
    text-align: left;
    transition: all 0.25s var(--ease-out);
    position: relative;
    overflow: hidden;
  }
  .bento-card:hover {
    border-color: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
    background: rgba(18, 23, 32, 0.75);
  }
  .card-num {
    font-family: var(--mono);
    font-size: 12px;
    color: var(--gold);
    letter-spacing: 0.15em;
    margin-bottom: 20px;
    display: block;
  }
  .bento-card h3 {
    font-size: 20px;
    font-weight: 600;
    color: #fff;
    margin-bottom: 10px;
    letter-spacing: -0.02em;
  }
  .bento-card p {
    font-size: 14px;
    line-height: 1.6;
    color: var(--fg-60);
  }
  .cta-banner {
    background: radial-gradient(circle at 50% 50%, rgba(245, 201, 107, 0.12) 0%, rgba(14, 18, 26, 0.8) 70%);
    border: 1px solid rgba(245, 201, 107, 0.3);
    border-radius: 32px;
    padding: clamp(36px, 6vw, 64px) 24px;
    text-align: center;
    margin-bottom: clamp(40px, 6vh, 64px);
    box-shadow: 0 24px 60px -12px rgba(0, 0, 0, 0.7);
  }
  .cta-banner h2 {
    font-size: clamp(32px, 5vw, 56px);
    font-weight: 600;
    letter-spacing: -0.03em;
    margin-bottom: 14px;
  }
  .cta-banner p {
    color: var(--fg-60);
    font-size: 16px;
    margin-bottom: 28px;
    max-width: 480px;
    margin-left: auto;
    margin-right: auto;
  }
  .cta-btns {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 14px;
    flex-wrap: wrap;
  }
  .footer-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24px 0 12px;
    border-top: 1px solid var(--line);
    font-size: 13px;
    color: var(--fg-40);
    flex-wrap: wrap;
    gap: 16px;
  }
  .footer-links {
    display: flex;
    align-items: center;
    gap: 20px;
  }
  .footer-links a {
    color: var(--fg-60);
    text-decoration: none;
    transition: color 0.15s;
  }
  .footer-links a:hover { color: #fff; }
  .system-status {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-family: var(--mono);
    font-size: 11px;
    color: var(--fg-60);
  }
  @media (max-width: 900px) {
    .bento-grid { grid-template-columns: 1fr; gap: 14px; }
    .nav-menu { display: none; }
  }
  @media (max-width: 600px) {
    .claim-pill-form {
      flex-direction: column;
      border-radius: 24px;
      padding: 12px;
      gap: 10px;
    }
    .domain-tag { align-self: flex-start; padding-left: 6px; }
    .claim-input-field { width: 100%; padding: 4px 6px; }
    .claim-submit-btn { width: 100%; }
    .footer-row { flex-direction: column; text-align: center; }
  }
  @media (prefers-reduced-motion: reduce) {
    .ambient-video { display: none; }
    .preview-card { transform: none !important; }
  }
</style>
</head>
<body>
<div class="noise" aria-hidden="true"></div>

<div class="viewport-frame">
  <div class="stage">
    <video class="ambient-video" autoplay loop muted playsinline poster="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100%25' height='100%25' fill='%2306080a'/%3E%3C/svg%3E">
      <source src="https://d8j0ntlcm91z4.cloudfront.net/user_38xzZboKViGWJOttwIXH07lWA1P/hf_20260702_135039_b04d00db-6ee2-4e2a-a7f5-b2dfd3d24fd2.mp4" type="video/mp4">
    </video>
    <div class="video-overlay" aria-hidden="true"></div>

    <div class="stage-inner">
      <header class="nav">
        <a href="<?= e(base_url('')) ?>" class="brand">
          Wander <span class="brand-badge">PRO</span>
        </a>
        <nav class="nav-menu">
          <a href="#preview" class="nav-link">Live Preview</a>
          <a href="#architecture" class="nav-link">Architecture</a>
          <a href="#intelligence" class="nav-link">Telemetry</a>
          <a href="#creators" class="nav-link">Directory</a>
        </nav>
        <div class="nav-actions">
          <a href="<?= e(login_url()) ?>" class="pill-btn pill-ghost">Sign In</a>
          <a href="<?= e(register_url()) ?>" class="pill-btn pill-solid">Claim Page ↗</a>
        </div>
      </header>

      <section class="hero-center">
        <div class="eyebrow">
          <span class="pulse-dot"></span>
          Wander 2.0 · The Root-Level Digital Identity
        </div>

        <h1 class="h1-title">
          One unified link<br>
          for every <span class="serif-accent">destination</span>.
        </h1>

        <p class="hero-desc">
          Replace fragmented link aggregators with a gallery-grade personal landing page. Hosted at direct root-level with zero redirection delay.
        </p>

        <form class="claim-pill-form" action="<?= e(register_url()) ?>" method="get">
          <span class="domain-tag"><?= e(preg_replace('#^https?://#', '', rtrim(app_origin() . base_url(''), '/'))) ?>/</span>
          <input type="text" name="claim" class="claim-input-field" placeholder="yourname" autocomplete="off" spellcheck="false" required pattern="[a-zA-Z0-9-]+" autofocus>
          <button type="submit" class="claim-submit-btn">Claim Page ↗</button>
        </form>

        <div class="creators-strip" id="creators">
          <span class="strip-label">Verified Creators:</span>
          <a href="<?= user_url('ammar-ahmed') ?>" class="creator-tag">
            <span class="creator-avatar">A</span>
            @ammar-ahmed
          </a>
          <a href="<?= user_url('medo123') ?>" class="creator-tag">
            <span class="creator-avatar">M</span>
            @medo123
          </a>
          <a href="<?= user_url('mariam') ?>" class="creator-tag">
            <span class="creator-avatar">M</span>
            @mariam
          </a>
          <a href="<?= user_url('omar-qadry') ?>" class="creator-tag">
            <span class="creator-avatar">O</span>
            @omar-qadry
          </a>
        </div>
      </section>

      <div class="preview-stage" id="preview">
        <div class="preview-card" id="tiltCard">
          <div class="mock-avatar">⚡</div>
          <div class="mock-name">
            Ammar Ahmed
            <svg class="verified-icon" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
          </div>
          <div class="mock-handle">@ammar-ahmed</div>
          <p class="mock-bio">Software Architect & Full Stack Designer. Directing digital destinations across platforms.</p>

          <div class="mock-destinations">
            <a href="<?= user_url('ammar-ahmed') ?>" class="mock-link">
              <span>✦ Selected Works & Case Studies</span>
              <span class="mock-link-stats">4.8k taps</span>
            </a>
            <a href="<?= user_url('ammar-ahmed') ?>" class="mock-link">
              <span>⚡ Open Source & Architecture</span>
              <span class="mock-link-stats">3.2k taps</span>
            </a>
            <a href="<?= user_url('ammar-ahmed') ?>" class="mock-link">
              <span>💼 Studio & Advisory Inquiries</span>
              <span class="mock-link-stats">1.9k taps</span>
            </a>
          </div>

          <div class="mock-footer-meta">
            <span>Live Uptime: 99.98%</span>
            <span>0ms Redirect Lag</span>
          </div>
        </div>
      </div>

      <section class="bento-grid" id="architecture">
        <div class="bento-card">
          <span class="card-num">01 / ARCHITECTURE</span>
          <h3>Pure Root Routing</h3>
          <p>Your identity lives cleanly at <code>my-tap.online/{username}</code>. Never compromised by <code>/u/</code> directories, sluggish subdomains, or nested path bloat.</p>
        </div>

        <div class="bento-card" id="intelligence">
          <span class="card-num">02 / INTELLIGENCE</span>
          <h3>Real-Time Telemetry</h3>
          <p>Track unique visitors, destination click attribution, country heatmaps, and referrer channels without invasive tracking scripts.</p>
        </div>

        <div class="bento-card">
          <span class="card-num">03 / CRAFT</span>
          <h3>Gallery-Grade Aesthetics</h3>
          <p>Engineered with deep nocturnal contrast, cinematic ambient motion, and Instrument Serif typography tailored to elevate your presence.</p>
        </div>
      </section>

      <section class="cta-banner">
        <h2>Claim your name today.</h2>
        <p>Join creators and founders publishing their verified destination hubs on Wander.</p>
        <div class="cta-btns">
          <a href="<?= e(register_url()) ?>" class="pill-btn pill-solid" style="padding:13px 28px;font-size:14px">Get Started — Free ↗</a>
          <a href="<?= e(login_url()) ?>" class="pill-btn pill-ghost" style="padding:13px 24px;font-size:14px">Sign In to Dashboard</a>
        </div>
      </section>

      <footer class="footer-row">
        <div>&copy; <?= date('Y') ?> Wander / MY-TAP. The Digital Destination Protocol.</div>
        <div class="system-status">
          <span class="pulse-dot"></span>
          LiteSpeed Edge Active
        </div>
        <div class="footer-links">
          <a href="<?= e(login_url()) ?>">Sign In</a>
          <a href="<?= e(register_url()) ?>">Create Page</a>
          <a href="<?= e(dashboard_url('index.php')) ?>">Workspace</a>
        </div>
      </footer>
    </div>
  </div>
</div>

<script>
(function() {
  const card = document.getElementById('tiltCard');
  if (!card) return;
  const stage = card.parentElement;

  stage.addEventListener('mousemove', function(e) {
    const rect = card.getBoundingClientRect();
    const x = e.clientX - rect.left - rect.width / 2;
    const y = e.clientY - rect.top - rect.height / 2;
    const rotX = -(y / (rect.height / 2)) * 8;
    const rotY = (x / (rect.width / 2)) * 8;
    card.style.transform = `perspective(1200px) rotateX(${rotX.toFixed(2)}deg) rotateY(${rotY.toFixed(2)}deg)`;
  });

  stage.addEventListener('mouseleave', function() {
    card.style.transform = 'perspective(1200px) rotateX(0deg) rotateY(0deg)';
  });
})();
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