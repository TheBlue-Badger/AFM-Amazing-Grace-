<?php
define('APP_BOOT', true);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';

$pdo = db();
$filterDate = $_GET['date'] ?? '';

$dates = $pdo->query('SELECT DISTINCT service_date FROM gallery_photos ORDER BY service_date DESC')->fetchAll(PDO::FETCH_COLUMN);

if ($filterDate && in_array($filterDate, $dates, true)) {
    $stmt = $pdo->prepare('SELECT * FROM gallery_photos WHERE service_date = ? ORDER BY uploaded_at DESC');
    $stmt->execute([$filterDate]);
    $photos = $stmt->fetchAll();
} else {
    $filterDate = '';
    $photos = $pdo->query('SELECT * FROM gallery_photos ORDER BY service_date DESC, uploaded_at DESC')->fetchAll();
}

// Group photos by service date for section headings.
$grouped = [];
foreach ($photos as $p) {
    $grouped[$p['service_date']][] = $p;
}

$pageTitle = SITE_NAME . ', Photo Gallery';
$activePage = 'gallery';
$extraScripts = ['/assets/js/gallery.js'];
include __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <span class="eyebrow">Photo booth</span>
    <h1>Moments from our services.</h1>
    <p class="lead">Browse and download photos from Sunday services and church events.</p>
  </div>
</section>

<section>
  <div class="container">
    <?php if ($dates): ?>
      <div class="date-chips">
        <a href="/gallery.php" class="date-chip<?= $filterDate === '' ? ' active' : '' ?>">All</a>
        <?php foreach ($dates as $d): ?>
          <a href="/gallery.php?date=<?= h($d) ?>" class="date-chip<?= $filterDate === $d ? ' active' : '' ?>"><?= h(date('d M Y', strtotime($d))) ?></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!$photos): ?>
      <p>No photos have been uploaded yet, check back after our next service.</p>
    <?php else: ?>
      <?php foreach ($grouped as $date => $group): ?>
        <div class="gallery-date-group">
          <h3>Sunday <?= h(date('d M Y', strtotime($date))) ?><?= $group[0]['caption'] ? ' &middot; ' . h($group[0]['caption']) : '' ?></h3>
          <div class="photo-grid">
            <?php foreach ($group as $p): ?>
              <div class="photo-tile">
                <img src="<?= h(upload_url($p['filename'], 'gallery')) ?>" alt="<?= h($p['caption'] ?: 'Church photo') ?>" data-lightbox-src="<?= h(upload_url($p['filename'], 'gallery')) ?>" data-caption="<?= h($p['caption']) ?>">
                <a class="photo-download" href="<?= h(upload_url($p['filename'], 'gallery')) ?>" download title="Download photo">
                  <svg viewBox="0 0 24 24" fill="none" width="16" height="16"><path d="M12 4v11m0 0l-4-4m4 4l4-4M5 19h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<!-- Lightbox -->
<div class="lightbox" id="lightbox">
  <button class="lightbox-close" id="lightboxClose" aria-label="Close">&times;</button>
  <button class="lightbox-nav lightbox-prev" id="lightboxPrev" aria-label="Previous">&#10094;</button>
  <img id="lightboxImg" src="" alt="">
  <button class="lightbox-nav lightbox-next" id="lightboxNext" aria-label="Next">&#10095;</button>
  <div class="lightbox-caption" id="lightboxCaption"></div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
