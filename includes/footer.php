<?php if (!defined('APP_BOOT')) { http_response_code(403); exit('No direct access'); } track_page_view(); ?>
<footer id="contact">
  <div class="container">
    <div class="footer-grid">
      <div>
        <div class="footer-brand">
          <img src="/images/logo.jpg" alt="<?= h(SITE_NAME) ?> crest">
          <strong><?= h(SITE_SHORT_NAME) ?></strong>
        </div>
        <p style="max-width:34ch;">A Shona-speaking assembly of the Apostolic Faith Mission of South Africa, a home for the whole Eersteriver family.</p>
        <div class="social-row">
          <a href="<?= h(SITE_FACEBOOK) ?>" target="_blank" rel="noopener" aria-label="Facebook" class="social-icon"><svg viewBox="0 0 24 24" fill="currentColor" width="17" height="17"><path d="M13.5 21v-7.5h2.5l.5-3H13.5V8.5c0-.9.2-1.5 1.6-1.5H16.5V4.3C16.2 4.2 15.2 4 14 4c-2.5 0-4.2 1.5-4.2 4.3v2.2H7.3v3H9.8V21h3.7z"/></svg></a>
          <a href="<?= h(SITE_TIKTOK) ?>" target="_blank" rel="noopener" aria-label="TikTok" class="social-icon"><svg viewBox="0 0 24 24" fill="currentColor" width="17" height="17"><path d="M16.6 5.8c-.9-.6-1.5-1.6-1.6-2.8h-3v12.4c0 1.2-1 2.2-2.2 2.2a2.2 2.2 0 01-2.2-2.2 2.2 2.2 0 012.2-2.2c.2 0 .4 0 .6.1v-3.1c-.2 0-.4 0-.6 0a5.3 5.3 0 00-5.3 5.3A5.3 5.3 0 009.8 21a5.3 5.3 0 005.3-5.3V9.1a7.9 7.9 0 004.5 1.4V7.5c-1 0-2.1-.4-3-.9z"/></svg></a>
          <a href="<?= h(SITE_YOUTUBE) ?>" target="_blank" rel="noopener" aria-label="YouTube" class="social-icon"><svg viewBox="0 0 24 24" fill="currentColor" width="17" height="17"><path d="M21.5 7.7a2.8 2.8 0 00-2-2C17.8 5.3 12 5.3 12 5.3s-5.8 0-7.5.4a2.8 2.8 0 00-2 2A29 29 0 002 12a29 29 0 00.5 4.3 2.8 2.8 0 002 2c1.7.4 7.5.4 7.5.4s5.8 0 7.5-.4a2.8 2.8 0 002-2 29 29 0 00.5-4.3 29 29 0 00-.5-4.3zM10 15.3V8.7l5.7 3.3-5.7 3.3z"/></svg></a>
        </div>
        <div class="map-frame">
          <iframe loading="lazy" src="https://www.google.com/maps?q=<?= urlencode(SITE_ADDRESS) ?>&output=embed" title="Map to <?= h(SITE_NAME) ?>"></iframe>
        </div>
      </div>
      <div>
        <h5>Visit</h5>
        <ul>
          <li><?= h(SITE_ADDRESS) ?></li>
          <li>Sunday Service &middot; 9:00 AM</li>
          <li>Young Adults Ministry &middot; Fridays</li>
        </ul>
      </div>
      <div>
        <h5>Contact</h5>
        <ul>
          <li><a href="tel:<?= h(SITE_PHONE) ?>"><?= h(SITE_PHONE_DISPLAY) ?></a></li>
          <li><a href="mailto:<?= h(SITE_EMAIL) ?>"><?= h(SITE_EMAIL) ?></a></li>
          <li><a href="/livestream.php">Watch Live</a></li>
          <li><a href="/gallery.php">Photo Gallery</a></li>
        </ul>
      </div>
      <div>
        <h5>AFM National</h5>
        <img src="/images/afm_logo.jpg" alt="AFM of South Africa crest" style="width:50px;height:50px;margin-bottom:14px;border-radius:50%;">
        <ul>
          <li><a href="https://afm-ags.org/" target="_blank" rel="noopener">afm-ags.org</a></li>
          <li><a href="https://afm-ags.org/confession-of-faith-afm/" target="_blank" rel="noopener">Confession of Faith</a></li>
          <li><a href="https://afm-ags.org/upcoming-events/" target="_blank" rel="noopener">National Events</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>&copy; <?= date('Y') ?> <?= h(SITE_NAME) ?>. Content is editable in the source, this is a starting template.</span>
      <a href="/admin/login.php">Staff / Admin login</a>
    </div>
  </div>
</footer>

<?php include __DIR__ . '/chat_widget.php'; ?>

<div class="toast" id="toast"><span class="dot"></span><span id="toastMsg">Saved</span></div>

<script>
window.CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;
window.SITE_NAME = <?= json_encode(SITE_NAME) ?>;
</script>
<script src="/assets/js/site.js"></script>
<script src="/assets/js/chat.js"></script>
<?php if (!empty($extraScripts)) foreach ($extraScripts as $s): ?>
<script src="<?= h($s) ?>"></script>
<?php endforeach; ?>
</body>
</html>
