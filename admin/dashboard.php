<?php
define('APP_BOOT', true);
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';
require_admin();

$pdo = db();
$tab = $_GET['tab'] ?? 'products';
$allowedTabs = ['products', 'events', 'gallery', 'livestream', 'messages', 'contact', 'orders', 'admins', 'account'];
$isDev = is_developer();
if ($isDev) $allowedTabs[] = 'developer';
if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'products';
}

$me = $pdo->prepare('SELECT * FROM admin_users WHERE id = ?');
$me->execute([$_SESSION['admin_id']]);
$me = $me->fetch();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$products = $pdo->query('SELECT * FROM products ORDER BY sort_order ASC, id ASC')->fetchAll();
$events = $pdo->query('SELECT * FROM events ORDER BY event_date DESC')->fetchAll();
$galleryPhotos = $pdo->query('SELECT * FROM gallery_photos ORDER BY uploaded_at DESC LIMIT 60')->fetchAll();
$livestream = $pdo->query('SELECT * FROM livestreams ORDER BY id DESC LIMIT 1')->fetch();
$streamComments = [];
if ($livestream) {
    $scStmt = $pdo->prepare('SELECT * FROM stream_comments WHERE livestream_id = ? ORDER BY id DESC LIMIT 40');
    $scStmt->execute([$livestream['id']]);
    $streamComments = $scStmt->fetchAll();
}
$overlayUrl = (($_SERVER['HTTPS'] ?? '') && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'yoursite.com') . '/stream-overlay.php';

$adminUsers = $pdo->query('SELECT * FROM admin_users ORDER BY created_at ASC')->fetchAll();

$devStats = null;
if ($isDev && $tab === 'developer') {
    $now = time();
    $devStats = [
        'views_today'   => (int)$pdo->query('SELECT COUNT(*) FROM page_views WHERE created_at >= ' . ($now - 86400))->fetchColumn(),
        'views_week'    => (int)$pdo->query('SELECT COUNT(*) FROM page_views WHERE created_at >= ' . ($now - 7 * 86400))->fetchColumn(),
        'views_total'   => (int)$pdo->query('SELECT COUNT(*) FROM page_views')->fetchColumn(),
        'avg_load_ms'   => (float)$pdo->query('SELECT AVG(load_ms) FROM page_views WHERE created_at >= ' . ($now - 7 * 86400))->fetchColumn(),
        'top_pages'     => $pdo->query("SELECT path, COUNT(*) as hits FROM page_views WHERE created_at >= " . ($now - 7 * 86400) . " GROUP BY path ORDER BY hits DESC LIMIT 8")->fetchAll(),
        'recent'        => $pdo->query('SELECT * FROM page_views ORDER BY id DESC LIMIT 15')->fetchAll(),
        'admin_count'   => (int)$pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn(),
        'product_count' => (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
        'order_count'   => (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
        'event_count'   => (int)$pdo->query('SELECT COUNT(*) FROM events')->fetchColumn(),
        'photo_count'   => (int)$pdo->query('SELECT COUNT(*) FROM gallery_photos')->fetchColumn(),
        'msg_count'     => (int)$pdo->query('SELECT COUNT(*) FROM chat_messages')->fetchColumn(),
    ];
    $uploadsSize = 0;
    foreach (['products', 'events', 'gallery'] as $dir) {
        foreach (glob(UPLOAD_DIR . "/$dir/*") ?: [] as $f) {
            if (is_file($f)) $uploadsSize += filesize($f);
        }
    }
    $devStats['uploads_size_mb'] = round($uploadsSize / 1024 / 1024, 2);
    if (is_mysql()) {
        $devStats['db_version'] = 'MySQL ' . $pdo->query('SELECT VERSION()')->fetchColumn();
    } else {
        $devStats['db_version'] = 'SQLite ' . $pdo->query('SELECT sqlite_version()')->fetchColumn();
    }
}

$threads = $pdo->query('SELECT * FROM chat_threads ORDER BY updated_at DESC')->fetchAll();
$activeThread = null;
$activeThreadMessages = [];
if ($tab === 'messages' && !empty($_GET['thread'])) {
    $tid = (int)$_GET['thread'];
    $stmt = $pdo->prepare('SELECT * FROM chat_threads WHERE id = ?');
    $stmt->execute([$tid]);
    $activeThread = $stmt->fetch();
    if ($activeThread) {
        $stmt = $pdo->prepare('SELECT * FROM chat_messages WHERE thread_id = ? ORDER BY id ASC');
        $stmt->execute([$tid]);
        $activeThreadMessages = $stmt->fetchAll();
    }
}

$contactMessages = $pdo->query('SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 100')->fetchAll();
$orders = $pdo->query('SELECT * FROM orders ORDER BY created_at DESC LIMIT 100')->fetchAll();

$pageTitle = SITE_NAME . ', Admin Dashboard';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,600;0,9..144,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="admin-shell">

<div class="admin-topbar">
  <div class="container">
    <div class="brand">
      <img src="/images/logo.jpg" alt="" style="width:36px;height:36px;border-radius:50%;">
      <div class="brand-text"><strong>Admin dashboard</strong><span style="color:rgba(255,255,255,.6);">Logged in as <?= h($me['username']) ?></span></div>
    </div>
    <div class="admin-topbar-links">
      <a href="/index.php" target="_blank" rel="noopener">View site &#8599;</a>
      <a href="/admin/logout.php">Log out</a>
    </div>
  </div>
</div>

<div class="admin-wrap">

  <?php if (!empty($me['is_default_password'])): ?>
    <div class="alert alert-warn">You're still using the one-time password generated at setup. Please change it now from the <a href="?tab=account" style="text-decoration:underline;">Account</a> tab.</div>
  <?php endif; ?>
  <?php if ($flash): ?>
    <div class="alert alert-success"><?= h($flash) ?></div>
  <?php endif; ?>

  <div class="admin-tabs">
    <a class="admin-tab <?= $tab === 'products' ? 'active' : '' ?>" href="?tab=products">Store products</a>
    <a class="admin-tab <?= $tab === 'events' ? 'active' : '' ?>" href="?tab=events">Events</a>
    <a class="admin-tab <?= $tab === 'gallery' ? 'active' : '' ?>" href="?tab=gallery">Gallery</a>
    <a class="admin-tab <?= $tab === 'livestream' ? 'active' : '' ?>" href="?tab=livestream">Livestream</a>
    <a class="admin-tab <?= $tab === 'messages' ? 'active' : '' ?>" href="?tab=messages">Live chat <?php $unread = count($threads); if ($unread): ?><span class="pill"><?= $unread ?></span><?php endif; ?></a>
    <a class="admin-tab <?= $tab === 'contact' ? 'active' : '' ?>" href="?tab=contact">Contact form</a>
    <a class="admin-tab <?= $tab === 'orders' ? 'active' : '' ?>" href="?tab=orders">Giving / orders</a>
    <a class="admin-tab <?= $tab === 'admins' ? 'active' : '' ?>" href="?tab=admins">Admins</a>
    <?php if ($isDev): ?>
      <a class="admin-tab <?= $tab === 'developer' ? 'active' : '' ?>" href="?tab=developer">&#9881; Developer</a>
    <?php endif; ?>
    <a class="admin-tab <?= $tab === 'account' ? 'active' : '' ?>" href="?tab=account">Account</a>
  </div>

  <?php if ($tab === 'products'): ?>
    <div class="admin-card">
      <h3>Store products</h3>
      <p class="form-note" style="margin-top:-10px;">Changes save immediately and appear live on the Give &amp; Shop page for every visitor.</p>
      <?php foreach ($products as $p): ?>
        <form method="post" action="/admin/product_save.php" class="admin-product-row" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
          <?php if ($p['image']): ?>
            <img src="<?= h(upload_url($p['image'], 'products')) ?>" alt="" class="admin-thumb">
          <?php else: ?>
            <div class="admin-thumb admin-thumb-empty">No image</div>
          <?php endif; ?>
          <div class="fields-col">
            <div class="field-row">
              <div class="field"><label>Name</label><input type="text" name="name" value="<?= h($p['name']) ?>" required></div>
              <div class="field"><label>Tag</label><input type="text" name="tag" value="<?= h($p['tag']) ?>"></div>
            </div>
            <div class="field"><label>Description</label><input type="text" name="description" value="<?= h($p['description']) ?>"></div>
            <div class="field-row">
              <div class="field"><label>Type</label>
                <select name="type">
                  <option value="fixed" <?= $p['type'] === 'fixed' ? 'selected' : '' ?>>Fixed price</option>
                  <option value="amount" <?= $p['type'] === 'amount' ? 'selected' : '' ?>>Giver chooses amount</option>
                </select>
              </div>
              <div class="field"><label>Price (<?= h(CURRENCY_PREFIX) ?>)</label><input type="number" step="0.01" min="0" name="price" value="<?= h($p['price']) ?>"></div>
              <div class="field"><label>Visible</label>
                <select name="is_active">
                  <option value="1" <?= $p['is_active'] ? 'selected' : '' ?>>Yes</option>
                  <option value="0" <?= !$p['is_active'] ? 'selected' : '' ?>>Hidden</option>
                </select>
              </div>
            </div>
            <div class="field-row">
              <div class="field"><label>Replace image</label><input type="file" name="image" accept="image/jpeg,image/png,image/webp"></div>
              <?php if ($p['image']): ?>
                <div class="field" style="display:flex;align-items:flex-end;"><label style="display:flex;align-items:center;gap:6px;font-weight:500;"><input type="checkbox" name="remove_image" value="1" style="width:auto;"> Remove current image</label></div>
              <?php endif; ?>
            </div>
          </div>
          <div class="actions-col">
            <button class="btn btn-ghost btn-sm" type="submit">Save</button>
            <button class="btn btn-danger btn-sm" type="submit" formaction="/admin/product_delete.php" onclick="return confirm('Remove this product?');">Remove</button>
          </div>
        </form>
      <?php endforeach; ?>

      <form method="post" action="/admin/product_save.php" class="admin-product-row" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="0">
        <div class="fields-col">
          <div class="field-row">
            <div class="field"><label>Name</label><input type="text" name="name" placeholder="New item name" required></div>
            <div class="field"><label>Tag</label><input type="text" name="tag" placeholder="e.g. Event"></div>
          </div>
          <div class="field"><label>Description</label><input type="text" name="description" placeholder="Short description"></div>
          <div class="field-row">
            <div class="field"><label>Type</label>
              <select name="type"><option value="fixed">Fixed price</option><option value="amount">Giver chooses amount</option></select>
            </div>
            <div class="field"><label>Price (<?= h(CURRENCY_PREFIX) ?>)</label><input type="number" step="0.01" min="0" name="price" value="0"></div>
            <div class="field"><label>Visible</label><select name="is_active"><option value="1">Yes</option><option value="0">Hidden</option></select></div>
          </div>
          <div class="field"><label>Image</label><input type="file" name="image" accept="image/jpeg,image/png,image/webp"></div>
        </div>
        <div class="actions-col"><button class="btn btn-dark btn-sm" type="submit">+ Add product</button></div>
      </form>
    </div>

  <?php elseif ($tab === 'events'): ?>
    <div class="admin-card">
      <h3>Events</h3>
      <p class="form-note" style="margin-top:-10px;">Events appear on the public Events page, calendar, and year planner automatically once saved.</p>
      <?php foreach ($events as $e): ?>
        <form method="post" action="/admin/event_save.php" class="admin-product-row" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$e['id'] ?>">
          <?php if ($e['poster_image']): ?>
            <img src="<?= h(upload_url($e['poster_image'], 'events')) ?>" alt="" class="admin-thumb">
          <?php else: ?>
            <div class="admin-thumb admin-thumb-empty">No poster</div>
          <?php endif; ?>
          <div class="fields-col">
            <div class="field-row">
              <div class="field"><label>Title</label><input type="text" name="title" value="<?= h($e['title']) ?>" required></div>
              <div class="field"><label>Location</label><input type="text" name="location" value="<?= h($e['location']) ?>"></div>
            </div>
            <div class="field"><label>Description</label><input type="text" name="description" value="<?= h($e['description']) ?>"></div>
            <div class="field-row">
              <div class="field"><label>Date</label><input type="date" name="event_date" value="<?= h($e['event_date']) ?>" required></div>
              <div class="field"><label>Time</label><input type="text" name="event_time" value="<?= h($e['event_time']) ?>" placeholder="e.g. 2:00 PM"></div>
              <div class="field"><label>Visible</label>
                <select name="is_active">
                  <option value="1" <?= $e['is_active'] ? 'selected' : '' ?>>Yes</option>
                  <option value="0" <?= !$e['is_active'] ? 'selected' : '' ?>>Hidden</option>
                </select>
              </div>
            </div>
            <div class="field-row">
              <div class="field"><label>Replace poster</label><input type="file" name="poster_image" accept="image/jpeg,image/png,image/webp"></div>
              <?php if ($e['poster_image']): ?>
                <div class="field" style="display:flex;align-items:flex-end;"><label style="display:flex;align-items:center;gap:6px;font-weight:500;"><input type="checkbox" name="remove_poster" value="1" style="width:auto;"> Remove poster</label></div>
              <?php endif; ?>
            </div>
          </div>
          <div class="actions-col">
            <button class="btn btn-ghost btn-sm" type="submit">Save</button>
            <button class="btn btn-danger btn-sm" type="submit" formaction="/admin/event_delete.php" onclick="return confirm('Delete this event?');">Delete</button>
          </div>
        </form>
      <?php endforeach; ?>

      <form method="post" action="/admin/event_save.php" class="admin-product-row" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="0">
        <div class="fields-col">
          <div class="field-row">
            <div class="field"><label>Title</label><input type="text" name="title" placeholder="Event title" required></div>
            <div class="field"><label>Location</label><input type="text" name="location" placeholder="e.g. Church hall"></div>
          </div>
          <div class="field"><label>Description</label><input type="text" name="description" placeholder="Short description"></div>
          <div class="field-row">
            <div class="field"><label>Date</label><input type="date" name="event_date" required></div>
            <div class="field"><label>Time</label><input type="text" name="event_time" placeholder="e.g. 2:00 PM"></div>
            <div class="field"><label>Visible</label><select name="is_active"><option value="1">Yes</option><option value="0">Hidden</option></select></div>
          </div>
          <div class="field"><label>Poster image</label><input type="file" name="poster_image" accept="image/jpeg,image/png,image/webp"></div>
        </div>
        <div class="actions-col"><button class="btn btn-dark btn-sm" type="submit">+ Add event</button></div>
      </form>
    </div>

  <?php elseif ($tab === 'gallery'): ?>
    <div class="admin-card">
      <h3>Upload photos</h3>
      <p class="form-note" style="margin-top:-10px;">Label the album and pick the service date first, then select all the photos for that album, you can select 20 or more at once.</p>
      <form method="post" action="/admin/gallery_upload.php" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="field-row">
          <div class="field"><label>Service date</label><input type="date" name="service_date" value="<?= h(date('Y-m-d')) ?>" required></div>
          <div class="field"><label>Album / caption</label><input type="text" name="caption" placeholder="e.g. Youth Sunday"></div>
        </div>
        <div class="field"><label>Photos</label><input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required></div>
        <button class="btn btn-dark" type="submit">Upload photos</button>
      </form>
    </div>
    <div class="admin-card">
      <h3>Recent uploads</h3>
      <div class="admin-gallery-grid">
        <?php foreach ($galleryPhotos as $g): ?>
          <div class="admin-gallery-item">
            <img src="<?= h(upload_url($g['filename'], 'gallery')) ?>" alt="<?= h($g['caption']) ?>">
            <form method="post" action="/admin/gallery_delete.php" onsubmit="return confirm('Delete this photo?');">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
              <button type="submit" class="admin-gallery-delete" aria-label="Delete photo">&times;</button>
            </form>
            <span class="admin-gallery-date"><?= h(date('d M', strtotime($g['service_date']))) ?></span>
          </div>
        <?php endforeach; ?>
        <?php if (!$galleryPhotos): ?><p class="form-note">No photos uploaded yet.</p><?php endif; ?>
      </div>
    </div>

  <?php elseif ($tab === 'livestream'): ?>
    <div class="admin-card">
      <h3>Livestream</h3>
      <p class="form-note" style="margin-top:-10px;">This site shows a real <strong>live</strong> stream, it doesn't host video itself. Start your broadcast on YouTube or Facebook first, then paste the link of that <em>live broadcast</em> here.</p>
      <div class="alert alert-warn" style="font-size:13px;">
        <strong>How to get it right:</strong> paste the link of the broadcast you are <em>currently streaming</em>, not an old recorded video. While you're live, visitors see the live picture (with the platform's normal few seconds of delay). When your stream ends, untick "Currently live" and save so the site stops showing it.
      </div>
      <form method="post" action="/admin/livestream_save.php">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)($livestream['id'] ?? 0) ?>">
        <div class="field"><label>Title</label><input type="text" name="title" value="<?= h($livestream['title'] ?? '') ?>"></div>
        <div class="field"><label>YouTube or Facebook Live URL</label><input type="url" name="stream_url" value="<?= h($livestream['stream_url'] ?? '') ?>" placeholder="https://youtube.com/watch?v=..."></div>
        <div class="field"><label style="display:flex;align-items:center;gap:8px;font-weight:600;"><input type="checkbox" name="is_live" value="1" <?= !empty($livestream['is_live']) ? 'checked' : '' ?> style="width:auto;"> Currently live</label></div>
        <div class="field"><label>Next scheduled stream (optional)</label><input type="datetime-local" name="scheduled_at" value="<?= !empty($livestream['scheduled_at']) ? h(date('Y-m-d\TH:i', $livestream['scheduled_at'])) : '' ?>"></div>
        <button class="btn btn-dark" type="submit"><?= !empty($livestream['is_live']) ? 'Update stream' : 'Go live' ?></button>
      </form>
    </div>

    <div class="admin-card">
      <h3>Show comments on your broadcast</h3>
      <p class="form-note" style="margin-top:-10px;">To make viewer comments appear <em>on top of your live video</em> (Twitch style), add this page as a <strong>Browser Source</strong> in OBS, Streamlabs, or similar, sized to your canvas. It has a transparent background, so only the comments show.</p>
      <div class="field"><label>Overlay URL</label><input type="text" readonly value="<?= h($overlayUrl) ?>" onclick="this.select();"></div>
    </div>

    <div class="admin-card">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
        <h3 style="margin:0;">Live comments (<?= count($streamComments) ?> recent)</h3>
        <?php if ($streamComments): ?>
          <form method="post" action="/admin/stream_comment_delete.php" onsubmit="return confirm('Clear all live comments?');" style="margin:0;">
            <?= csrf_field() ?>
            <input type="hidden" name="clear_all" value="1">
            <button class="btn btn-danger btn-sm" type="submit">Clear all</button>
          </form>
        <?php endif; ?>
      </div>
      <?php foreach ($streamComments as $sc): ?>
        <div class="thread-item">
          <div><h5><?= h($sc['name']) ?></h5><p><?= h($sc['message']) ?></p></div>
          <form method="post" action="/admin/stream_comment_delete.php" style="margin:0;">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$sc['id'] ?>">
            <button class="btn btn-danger btn-sm" type="submit">Remove</button>
          </form>
        </div>
      <?php endforeach; ?>
      <?php if (!$streamComments): ?><p class="form-note">No comments yet. They'll appear here as viewers post during a live stream.</p><?php endif; ?>
    </div>

  <?php elseif ($tab === 'messages'): ?>
    <div class="admin-card">
      <h3>Live chat conversations</h3>
      <?php if (!$activeThread): ?>
        <?php if (!$threads): ?>
          <p class="form-note">No conversations yet. They'll show up here as soon as a visitor uses the chat bubble on the site.</p>
        <?php endif; ?>
        <?php foreach ($threads as $t): ?>
          <a class="thread-item" href="?tab=messages&amp;thread=<?= (int)$t['id'] ?>" style="text-decoration:none;">
            <div><h5><?= h($t['visitor_name']) ?></h5><p><?= h(date('d M Y, H:i', $t['updated_at'])) ?></p></div>
            <span>&rsaquo;</span>
          </a>
        <?php endforeach; ?>
      <?php else: ?>
        <a href="?tab=messages" class="btn btn-ghost btn-sm" style="margin-bottom:16px;">&larr; All conversations</a>
        <div class="chat-thread-view" data-thread-id="<?= (int)$activeThread['id'] ?>">
          <div class="chat-messages" id="adminChatMessages">
            <?php foreach ($activeThreadMessages as $m): ?>
              <div class="msg <?= $m['sender'] === 'admin' ? 'admin' : 'visitor' ?>"><?= h($m['message']) ?></div>
            <?php endforeach; ?>
          </div>
          <form method="post" action="/admin/chat_reply.php" class="chat-reply-row" id="adminReplyForm">
            <?= csrf_field() ?>
            <input type="hidden" name="thread_id" value="<?= (int)$activeThread['id'] ?>">
            <input type="text" name="message" id="adminReplyInput" placeholder="Type a reply..." required autocomplete="off">
            <button class="btn btn-dark btn-sm" type="submit">Send</button>
          </form>
        </div>
      <?php endif; ?>
    </div>

  <?php elseif ($tab === 'contact'): ?>
    <div class="admin-card">
      <h3>Contact form submissions</h3>
      <table class="admin-table">
        <thead><tr><th>From</th><th>Reason</th><th>Message</th><th>Received</th></tr></thead>
        <tbody>
          <?php foreach ($contactMessages as $c): ?>
            <tr>
              <td><strong><?= h($c['name']) ?></strong><br><a href="mailto:<?= h($c['email']) ?>"><?= h($c['email']) ?></a></td>
              <td><span class="pill"><?= h($c['reason'] ?: 'General') ?></span></td>
              <td style="max-width:360px;"><?= nl2br(h($c['message'])) ?></td>
              <td style="white-space:nowrap;"><?= h(date('d M Y, H:i', $c['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$contactMessages): ?><tr><td colspan="4" class="form-note">No messages yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>

  <?php elseif ($tab === 'orders'): ?>
    <div class="admin-card">
      <h3>Giving &amp; store orders</h3>
      <p class="form-note" style="margin-top:-10px;">These are references generated at checkout. This template doesn't process live payments, reconcile these manually against your bank statement or in-person gifts.</p>
      <table class="admin-table">
        <thead><tr><th>Ref</th><th>Name</th><th>Items</th><th>Total</th><th>Payment</th><th>Date</th></tr></thead>
        <tbody>
          <?php foreach ($orders as $o): $items = json_decode($o['items_json'], true) ?: []; ?>
            <tr>
              <td>AGE-<?= str_pad((string)$o['id'], 5, '0', STR_PAD_LEFT) ?></td>
              <td><strong><?= h($o['name']) ?></strong><br><?= h($o['email']) ?></td>
              <td><?php foreach ($items as $it): ?><?= h($it['name']) ?> &times;<?= (int)$it['qty'] ?><br><?php endforeach; ?></td>
              <td><?= money($o['total']) ?></td>
              <td><span class="pill"><?= $o['payment_method'] === 'online' ? 'Pay online' : 'In person' ?></span></td>
              <td style="white-space:nowrap;"><?= h(date('d M Y, H:i', $o['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$orders): ?><tr><td colspan="6" class="form-note">No orders yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>

  <?php elseif ($tab === 'admins'): ?>
    <div class="admin-card">
      <h3>Admin accounts</h3>
      <p class="form-note" style="margin-top:-10px;">Anyone in this list can log in and manage the site. The account whose email matches <code>DEVELOPER_EMAIL</code> in <code>config.php</code> also sees the Developer tab.</p>
      <table class="admin-table">
        <thead><tr><th>Username</th><th>Email</th><th>Role</th><th>Last login</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($adminUsers as $a): $accIsDev = $a['email'] && strcasecmp($a['email'], DEVELOPER_EMAIL) === 0; $isSelf = (int)$a['id'] === (int)$_SESSION['admin_id']; ?>
            <tr>
              <td><strong><?= h($a['username']) ?></strong><?= $isSelf ? ' <span class="pill">You</span>' : '' ?></td>
              <td><?= $a['email'] ? h($a['email']) : '<span class="form-note">not set</span>' ?></td>
              <td><?= $accIsDev ? '<span class="pill">Developer</span>' : 'Admin' ?></td>
              <td><?= $a['last_login'] ? h(date('d M Y, H:i', $a['last_login'])) : 'Never' ?></td>
              <td>
                <?php if ($isSelf || $accIsDev): ?>
                  <span class="form-note">Protected</span>
                <?php else: ?>
                  <form method="post" action="/admin/admin_user_delete.php" onsubmit="return confirm('Remove this admin account?');" style="margin:0;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                    <button class="btn btn-danger btn-sm" type="submit">Remove</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="admin-card">
      <h3>Add an admin</h3>
      <form method="post" action="/admin/admin_user_save.php">
        <?= csrf_field() ?>
        <div class="field-row">
          <div class="field"><label>Username</label><input type="text" name="username" required></div>
          <div class="field"><label>Email</label><input type="email" name="email" required placeholder="name@example.com"></div>
        </div>
        <div class="field-row">
          <div class="field"><label>Password (12+ characters)</label><input type="password" name="password" minlength="12" required></div>
          <div class="field"><label>Confirm password</label><input type="password" name="confirm_password" minlength="12" required></div>
        </div>
        <p class="form-note">Tip: using <code><?= h(DEVELOPER_EMAIL) ?></code> as the email grants the Developer tab.</p>
        <button class="btn btn-dark" type="submit">Add admin</button>
      </form>
    </div>

  <?php elseif ($tab === 'developer' && $isDev): ?>
    <div class="admin-card">
      <h3>Site performance</h3>
      <p class="form-note" style="margin-top:-10px;">Self-hosted tracking, no third-party analytics involved. Logged once per public page load.</p>
      <div class="dev-stat-grid">
        <div class="dev-stat"><strong><?= number_format($devStats['views_today']) ?></strong><span>Views today</span></div>
        <div class="dev-stat"><strong><?= number_format($devStats['views_week']) ?></strong><span>Views (7 days)</span></div>
        <div class="dev-stat"><strong><?= number_format($devStats['views_total']) ?></strong><span>Views all-time</span></div>
        <div class="dev-stat"><strong><?= round($devStats['avg_load_ms']) ?>ms</strong><span>Avg. load (7 days)</span></div>
      </div>
      <h4 style="margin-top:26px;font-size:15px;">Top pages (7 days)</h4>
      <table class="admin-table">
        <thead><tr><th>Path</th><th>Views</th></tr></thead>
        <tbody>
          <?php foreach ($devStats['top_pages'] as $tp): ?>
            <tr><td><?= h($tp['path']) ?></td><td><?= (int)$tp['hits'] ?></td></tr>
          <?php endforeach; ?>
          <?php if (!$devStats['top_pages']): ?><tr><td colspan="2" class="form-note">No traffic recorded yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
      <h4 style="margin-top:26px;font-size:15px;">Most recent hits</h4>
      <table class="admin-table">
        <thead><tr><th>Path</th><th>Load time</th><th>When</th></tr></thead>
        <tbody>
          <?php foreach ($devStats['recent'] as $r): ?>
            <tr><td><?= h($r['path']) ?></td><td><?= (int)$r['load_ms'] ?>ms</td><td><?= h(date('d M H:i:s', $r['created_at'])) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="admin-card">
      <h3>System diagnostics</h3>
      <table class="admin-table">
        <tbody>
          <tr><td>PHP version</td><td><?= h(PHP_VERSION) ?></td></tr>
          <tr><td>Database engine</td><td><?= h($devStats['db_version']) ?></td></tr>
          <tr><td>Server software</td><td><?= h($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') ?></td></tr>
          <tr><td>PHP memory limit</td><td><?= h(ini_get('memory_limit')) ?></td></tr>
          <tr><td>Max upload size (this app)</td><td><?= h(round(MAX_UPLOAD_SIZE / 1024 / 1024, 1)) ?>MB</td></tr>
          <tr><td>Uploaded files disk usage</td><td><?= h($devStats['uploads_size_mb']) ?>MB</td></tr>
        </tbody>
      </table>
    </div>
    <div class="admin-card">
      <h3>Content at a glance</h3>
      <div class="dev-stat-grid">
        <div class="dev-stat"><strong><?= $devStats['admin_count'] ?></strong><span>Admins</span></div>
        <div class="dev-stat"><strong><?= $devStats['product_count'] ?></strong><span>Products</span></div>
        <div class="dev-stat"><strong><?= $devStats['order_count'] ?></strong><span>Orders</span></div>
        <div class="dev-stat"><strong><?= $devStats['event_count'] ?></strong><span>Events</span></div>
        <div class="dev-stat"><strong><?= $devStats['photo_count'] ?></strong><span>Gallery photos</span></div>
        <div class="dev-stat"><strong><?= $devStats['msg_count'] ?></strong><span>Chat messages</span></div>
      </div>
    </div>

  <?php elseif ($tab === 'account'): ?>
    <div class="admin-card">
      <h3>Change password</h3>
      <form method="post" action="/admin/change_password.php">
        <?= csrf_field() ?>
        <div class="field"><label>Current password</label><input type="password" name="current_password" required></div>
        <div class="field"><label>New password (12+ characters)</label><input type="password" name="new_password" minlength="12" required></div>
        <div class="field"><label>Confirm new password</label><input type="password" name="confirm_password" minlength="12" required></div>
        <button class="btn btn-dark" type="submit">Update password</button>
      </form>
    </div>
  <?php endif; ?>

</div>

<script>window.CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;</script>
<script src="/assets/js/admin.js"></script>
</body>
</html>
