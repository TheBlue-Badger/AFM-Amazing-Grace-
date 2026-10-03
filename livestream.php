<?php
define('APP_BOOT', true);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/embed.php';

$pdo = db();
$stream = $pdo->query('SELECT * FROM livestreams ORDER BY id DESC LIMIT 1')->fetch();
$embedUrl = ($stream && $stream['is_live'] && $stream['stream_url']) ? get_embed_url($stream['stream_url']) : null;

$pageTitle = SITE_NAME . ', Watch Live';
$activePage = 'live';
$extraScripts = $embedUrl ? ['/assets/js/stream_chat.js'] : [];
include __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <span class="eyebrow">Livestream</span>
    <h1>Join the service from wherever you are.</h1>
    <p class="lead">Can't make it in person? Watch along live and chat with the congregation, or catch the recording after on our YouTube channel.</p>
  </div>
</section>

<section>
  <div class="container">
    <?php if ($embedUrl): ?>
      <div class="live-badge"><span class="pulse"></span> LIVE NOW</div>
      <h2 style="margin-bottom:20px;"><?= h($stream['title']) ?></h2>
      <div class="stream-layout">
        <div class="stream-embed">
          <iframe src="<?= h($embedUrl) ?>" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen title="<?= h($stream['title']) ?>"></iframe>
        </div>
        <aside class="stream-chat">
          <div class="stream-chat-head">Live comments</div>
          <div class="stream-comments-feed" id="streamCommentsFeed"></div>
          <form class="stream-chat-form" id="streamCommentForm">
            <input type="text" id="streamCommentName" placeholder="Your name" maxlength="40" required>
            <div class="stream-chat-row">
              <input type="text" id="streamCommentMessage" placeholder="Say something..." maxlength="200" required autocomplete="off">
              <button type="submit" class="btn btn-dark btn-sm">Send</button>
            </div>
          </form>
        </aside>
      </div>
    <?php else: ?>
      <div class="stream-offline">
        <svg viewBox="0 0 24 24" fill="none" width="46" height="46"><path d="M15 10l4.5-3v10L15 14M4 6h9a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2z" stroke="currentColor" stroke-width="1.5"/></svg>
        <h2>No live stream right now</h2>
        <?php if ($stream && $stream['scheduled_at']): ?>
          <p>Our next stream is scheduled for <strong><?= h(date('D, d M Y \a\t H:i', $stream['scheduled_at'])) ?></strong>.</p>
        <?php else: ?>
          <p>Check back on Sunday morning, or catch up any time on our YouTube channel.</p>
        <?php endif; ?>
        <a href="<?= h(SITE_YOUTUBE) ?>" target="_blank" rel="noopener" class="btn btn-dark" style="margin-top:14px;">Watch on YouTube</a>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
