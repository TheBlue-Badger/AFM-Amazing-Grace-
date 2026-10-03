<?php
define('APP_BOOT', true);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';

$errors = [];
$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try submitting again.';
    } else {
        $name = trim(mb_substr((string)($_POST['name'] ?? ''), 0, 120));
        $email = trim(mb_substr((string)($_POST['email'] ?? ''), 0, 160));
        $reason = trim(mb_substr((string)($_POST['reason'] ?? ''), 0, 120));
        $message = trim(mb_substr((string)($_POST['message'] ?? ''), 0, 2000));

        if ($name === '') $errors[] = 'Please enter your name.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
        if ($message === '') $errors[] = 'Please enter a message.';

        if (!$errors) {
            $pdo = db();
            $stmt = $pdo->prepare('INSERT INTO contact_messages (name, email, reason, message, created_at) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$name, $email, $reason, $message, time()]);
            $sent = true;
        }
    }
}

$pageTitle = SITE_NAME . ', Contact';
$activePage = 'contact';
include __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <span class="eyebrow">We'd love to hear from you</span>
    <h1>Visit, call, or send us a message.</h1>
    <p class="lead">Whether you're planning your first visit or already part of the family, here's how to reach <?= h(SITE_NAME) ?>.</p>
  </div>
</section>

<section>
  <div class="container about-grid">
    <div>
      <span class="eyebrow">Send a message</span>
      <h2>Get in touch</h2>

      <?php if ($sent): ?>
        <div class="alert alert-success">Thank you, your message has been sent. We'll get back to you soon.</div>
      <?php endif; ?>
      <?php foreach ($errors as $e): ?>
        <div class="alert alert-error"><?= h($e) ?></div>
      <?php endforeach; ?>

      <form method="post" action="/contact.php">
        <?= csrf_field() ?>
        <div class="field-row">
          <div class="field"><label>Your name</label><input type="text" name="name" required value="<?= h($_POST['name'] ?? '') ?>"></div>
          <div class="field"><label>Email</label><input type="email" name="email" required value="<?= h($_POST['email'] ?? '') ?>"></div>
        </div>
        <div class="field"><label>Reason for contact</label>
          <select name="reason">
            <option>General enquiry</option>
            <option>Plan a visit</option>
            <option>Prayer request</option>
            <option>Ministry / volunteering</option>
            <option>Pastoral care</option>
          </select>
        </div>
        <div class="field"><label>Message</label><textarea name="message" rows="5" required><?= h($_POST['message'] ?? '') ?></textarea></div>
        <button type="submit" class="btn btn-dark">Send Message</button>
      </form>
      <p class="form-note" style="margin-top:18px;">Prefer something instant? Use the chat bubble in the corner, a real person reads every conversation.</p>
    </div>

    <div>
      <span class="eyebrow">Details</span>
      <h2>Reach us directly</h2>
      <ul style="list-style:none;padding:0;margin:0 0 24px;display:flex;flex-direction:column;gap:14px;">
        <li><strong style="color:var(--navy-deep);">Address</strong><br><?= h(SITE_ADDRESS) ?></li>
        <li><strong style="color:var(--navy-deep);">Phone</strong><br><a href="tel:<?= h(SITE_PHONE) ?>"><?= h(SITE_PHONE_DISPLAY) ?></a></li>
        <li><strong style="color:var(--navy-deep);">Email</strong><br><a href="mailto:<?= h(SITE_EMAIL) ?>"><?= h(SITE_EMAIL) ?></a></li>
        <li><strong style="color:var(--navy-deep);">Sunday Service</strong><br>9:00 AM, Forest Heights High School</li>
      </ul>
      <div class="map-frame">
        <iframe loading="lazy" src="https://www.google.com/maps?q=<?= urlencode(SITE_ADDRESS) ?>&output=embed" title="Map to <?= h(SITE_NAME) ?>" style="height:320px;"></iframe>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
