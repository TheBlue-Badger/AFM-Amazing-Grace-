<?php
define('APP_BOOT', true);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';

$pdo = db();
$products = $pdo->query('SELECT * FROM products WHERE is_active = 1 ORDER BY sort_order ASC, id ASC')->fetchAll();
$cart = cart_summary();

$pageTitle = SITE_NAME . ', Give & Shop';
$activePage = 'shop';
$extraScripts = ['/assets/js/shop.js'];
include __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <span class="eyebrow">Give &amp; shop</span>
    <h1>Support the ministry, wherever you are.</h1>
    <p class="lead">Give a tithe or offering, register for an event, or pick up something from the church store. This is managed live by our admin team, so it always reflects what's currently available.</p>
  </div>
</section>

<section>
  <div class="container">
    <div class="shop-grid" id="shopGrid">
      <?php foreach ($products as $p): ?>
        <div class="product-card">
          <?php if ($p['image']): ?>
            <div class="product-card-image"><img src="<?= h(upload_url($p['image'], 'products')) ?>" alt="<?= h($p['name']) ?>"></div>
          <?php else: ?>
            <div class="product-card-image placeholder-image"><span><?= h($p['tag'] ?: $p['name']) ?></span></div>
          <?php endif; ?>
          <div class="product-card-body">
          <span class="product-tag"><?= h($p['tag'] ?: 'Item') ?></span>
          <h4><?= h($p['name']) ?></h4>
          <p class="desc"><?= h($p['description']) ?></p>
          <?php if ($p['type'] === 'amount'): ?>
            <div class="amount-input"><span><?= h(CURRENCY_PREFIX) ?></span><input type="number" min="1" step="1" placeholder="Enter amount" id="amt-<?= (int)$p['id'] ?>"></div>
          <?php else: ?>
            <div class="product-price"><?= money($p['price']) ?></div>
          <?php endif; ?>
          <button class="btn btn-dark btn-block btn-sm" data-add="<?= (int)$p['id'] ?>">Add to cart</button>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (!$products): ?>
        <p>Nothing is available to give toward right now, check back soon.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- Cart Drawer -->
<div class="overlay" id="overlay"></div>
<div class="drawer" id="cartDrawer" aria-label="Shopping cart">
  <div class="drawer-head">
    <h3>Your cart</h3>
    <button class="drawer-close" id="closeCart" aria-label="Close cart">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </button>
  </div>
  <div class="drawer-body" id="cartBody">
    <?php if (!$cart['items']): ?>
      <div class="empty-note">Your cart is empty.<br>Browse the items above to add something.</div>
    <?php else: ?>
      <?php foreach ($cart['items'] as $item): ?>
        <div class="cart-item">
          <div class="cart-item-info">
            <h5><?= h($item['name']) ?></h5>
            <p><?= money($item['price']) ?> <?= $item['type'] === 'fixed' ? 'each' : '' ?></p>
            <div class="qty-row">
              <?php if ($item['type'] === 'fixed'): ?>
                <button class="qty-btn" data-dec="<?= h($item['line_id']) ?>">&minus;</button>
                <span><?= (int)$item['qty'] ?></span>
                <button class="qty-btn" data-inc="<?= h($item['line_id']) ?>">+</button>
              <?php endif; ?>
              <button class="remove-link" data-remove="<?= h($item['line_id']) ?>">Remove</button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <div class="drawer-foot">
    <div class="total-row"><span>Total</span><strong id="cartTotal"><?= money($cart['total']) ?></strong></div>
    <button class="btn btn-primary btn-block" id="checkoutBtn">Proceed to Give / Checkout</button>
  </div>
</div>

<!-- Checkout Modal -->
<div class="modal" id="checkoutModal">
  <div class="modal-backdrop" data-close-modal="checkoutModal"></div>
  <div class="modal-card">
    <button class="modal-close" data-close-modal="checkoutModal">&times;</button>
    <span class="eyebrow">Order summary</span>
    <h3>Complete your gift</h3>
    <div id="checkoutSummary" style="margin-bottom:18px;"></div>
    <div class="field"><label>Full name</label><input type="text" id="ckName" placeholder="Your name"></div>
    <div class="field"><label>Email</label><input type="email" id="ckEmail" placeholder="you@example.com"></div>
    <div class="field">
      <label>How would you like to pay?</label>
      <div style="display:flex;flex-direction:column;gap:10px;margin-top:6px;">
        <label style="display:flex;align-items:flex-start;gap:10px;font-weight:500;font-size:14.5px;color:var(--ink);cursor:pointer;">
          <input type="radio" name="ckPaymentMethod" value="online" checked style="margin-top:3px;width:auto;">
          <span><strong style="color:var(--navy-deep);">Pay online now</strong><br><span style="color:var(--ink-soft);font-size:13px;">We'll give you our banking details for an EFT / online bank transfer.</span></span>
        </label>
        <label style="display:flex;align-items:flex-start;gap:10px;font-weight:500;font-size:14.5px;color:var(--ink);cursor:pointer;">
          <input type="radio" name="ckPaymentMethod" value="in_person" style="margin-top:3px;width:auto;">
          <span><strong style="color:var(--navy-deep);">Pay in person after service</strong><br><span style="color:var(--ink-soft);font-size:13px;">Bring cash, card, or your reference to the church office on Sunday.</span></span>
        </label>
      </div>
    </div>
    <p class="form-note">This site doesn't process live card payments yet, ask us about connecting a real payment gateway (like PayFast) when you're ready to go live.</p>
    <button class="btn btn-primary btn-block" id="confirmOrder">Get reference &amp; instructions</button>
    <div id="checkoutResult"></div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
