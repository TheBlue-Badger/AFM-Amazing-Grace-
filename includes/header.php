<?php
if (!defined('APP_BOOT')) { http_response_code(403); exit('No direct access'); }
// Expects (optionally) $pageTitle and $activePage to be set by the including page.
$pageTitle = $pageTitle ?? SITE_NAME;
$activePage = $activePage ?? '';
$cart = cart_summary();
function nav_active($page, $activePage) { return $page === $activePage ? ' class="active"' : ''; }
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle) ?></title>
<meta name="description" content="<?= h(SITE_NAME . ', ' . SITE_TAGLINE . '. ' . SITE_CITY . '.') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;0,9..144,700;1,9..144,500&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<header class="site-header" id="siteHeader">
  <div class="header-inner">
    <a href="/index.php" class="brand">
      <img src="/images/logo.jpg" alt="<?= h(SITE_NAME) ?> crest">
      <div class="brand-text">
        <strong><?= h(SITE_SHORT_NAME) ?></strong>
        <span>AFM OF SOUTH AFRICA</span>
      </div>
    </a>

    <div class="header-right">
      <nav class="main-nav" id="mainNav">
        <a href="/index.php"<?= nav_active('home', $activePage) ?>>Home</a>
        <a href="/about.php"<?= nav_active('about', $activePage) ?>>About</a>
        <a href="/events.php"<?= nav_active('events', $activePage) ?>>Events</a>
        <a href="/gallery.php"<?= nav_active('gallery', $activePage) ?>>Gallery</a>
        <a href="/livestream.php"<?= nav_active('live', $activePage) ?>>Live</a>
        <a href="/shop.php"<?= nav_active('shop', $activePage) ?>>Give &amp; Shop</a>
        <a href="/contact.php"<?= nav_active('contact', $activePage) ?>>Contact</a>
      </nav>
      <div class="header-actions">
        <a href="/shop.php" class="icon-btn" id="cartBtn" aria-label="Open cart">
          <svg viewBox="0 0 24 24" fill="none"><path d="M3 4h2l1.6 9.6a2 2 0 0 0 2 1.7h7.7a2 2 0 0 0 2-1.6L20 8H6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9.5" cy="19.5" r="1.4" fill="currentColor"/><circle cx="17" cy="19.5" r="1.4" fill="currentColor"/></svg>
          <?php if ($cart['count'] > 0): ?><span class="badge-count"><?= (int)$cart['count'] ?></span><?php endif; ?>
        </a>
        <button class="nav-toggle" id="navToggle" aria-label="Toggle menu" aria-expanded="false">
          <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        </button>
      </div>
    </div>
  </div>
</header>
