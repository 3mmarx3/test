<?php
$activePage = $activePage ?? '';
$userObj = current_user();
$isAdmin = is_admin();
$brandSubtitle = $isAdmin ? 'Dashboard' : 'Workspace';
$unreadCount = $userObj ? unread_notifications_count($userObj['id']) : 0;
?>
<header class="mobile-bar">
  <div class="brand">Wander<small><?= e($brandSubtitle) ?></small></div>
  <button type="button" class="mobile-toggle topbar-toggle" aria-label="Toggle navigation menu">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
  </button>
</header>
<div class="side-overlay sidebar-overlay"></div>
<aside class="side">
  <div class="side-header-mobile">
    <div class="brand">Wander<small><?= e($brandSubtitle) ?></small></div>
    <button type="button" class="side-close sidebar-close" aria-label="Close navigation menu">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
    </button>
  </div>
  <div class="brand">Wander<small><?= e($brandSubtitle) ?></small></div>
  <div class="side-search">
    <div class="search-box">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      <input type="text" id="globalSearchInput" data-search-url="<?= page_url('search.php') ?>" placeholder="Search..." autocomplete="off">
    </div>
    <div id="globalSearchResults" class="search-dropdown" style="display:none"></div>
  </div>
  <div class="side-nav-group">
    <a class="nav <?= $activePage === 'overview' ? 'on' : '' ?>" href="<?= dashboard_url('index.php') ?>">
      <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg>
      <span>Overview</span>
    </a>
    <a class="nav <?= in_array($activePage, ['links', 'pages'], true) ? 'on' : '' ?>" href="<?= page_url('links.php') ?>">
      <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10 14a5 5 0 007 0l3-3a5 5 0 00-7-7l-1 1M14 10a5 5 0 00-7 0l-3 3a5 5 0 007 7l1-1"/></svg>
      <span>Pages</span>
    </a>
    <?php if ($isAdmin): ?>
      <a class="nav <?= $activePage === 'users' ? 'on' : '' ?>" href="<?= page_url('users.php') ?>">
        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <span>Users</span>
      </a>
      <a class="nav <?= $activePage === 'features' ? 'on' : '' ?>" href="<?= page_url('feature-suggestions.php') ?>">
        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a7 7 0 0 0-7 7c0 2.38 1.19 4.47 3 5.74V17a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2v-2.26c1.81-1.27 3-3.36 3-5.74a7 7 0 0 0-7-7z"/><path d="M9 21h6"/></svg>
        <span>Insights</span>
      </a>
    <?php endif; ?>
    <a class="nav <?= $activePage === 'notifications' ? 'on' : '' ?>" href="<?= page_url('notifications.php') ?>">
      <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
      <span>Notifications</span>
      <?php if ($unreadCount > 0): ?>
        <span class="nav-badge"><?= (int)$unreadCount ?></span>
      <?php endif; ?>
    </a>
    <a class="nav <?= $activePage === 'profile' ? 'on' : '' ?>" href="<?= page_url('profile.php') ?>">
      <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0116 0"/></svg>
      <span>Profile</span>
    </a>
    <a class="nav <?= $activePage === 'settings' ? 'on' : '' ?>" href="<?= page_url('settings.php') ?>">
      <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 01-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
      <span>Settings</span>
    </a>
  </div>
  <div class="side-end">
    <a class="nav" href="<?= user_url($userObj['username'] ?? '') ?>" target="_blank" rel="noopener">
      <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 01-1 1H5a1 1 0 01-1-1V7a1 1 0 011-1h5"/></svg>
      <span>View profile</span>
    </a>
    <form method="post" action="<?= auth_url('logout.php') ?>">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <button class="nav">
        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a1 1 0 01-1-1V4a1 1 0 011-1h4M16 17l5-5-5-5M21 12H9"/></svg>
        <span>Log out</span>
      </button>
    </form>
  </div>
</aside>
