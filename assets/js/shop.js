/* Give & Shop page: add-to-cart, cart drawer, checkout modal. All cart
   state actually lives in the PHP session — this file just reflects it. */

const overlay = document.getElementById('overlay');
const cartDrawer = document.getElementById('cartDrawer');

function openCart() { cartDrawer.classList.add('show'); overlay.classList.add('show'); }
function closeCart() { cartDrawer.classList.remove('show'); overlay.classList.remove('show'); }

document.getElementById('cartBtn').addEventListener('click', (e) => {
  e.preventDefault();
  openCart();
});
document.getElementById('closeCart').addEventListener('click', closeCart);
overlay.addEventListener('click', () => { closeCart(); closeAllModals(); });

function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }
function closeAllModals() { document.querySelectorAll('.modal').forEach(m => m.classList.remove('show')); }
document.querySelectorAll('[data-close-modal]').forEach(el => {
  el.addEventListener('click', () => closeModal(el.dataset.closeModal));
});

function renderCartFromServer(cart) {
  const body = document.getElementById('cartBody');
  const countEl = document.getElementById('cartCount');
  if (countEl) {
    countEl.textContent = cart.count;
    countEl.style.display = cart.count ? 'flex' : 'none';
  }
  const headerBadge = document.querySelector('#cartBtn .badge-count');
  if (headerBadge) {
    headerBadge.textContent = cart.count;
    headerBadge.style.display = cart.count ? 'flex' : 'none';
  } else if (cart.count) {
    const b = document.createElement('span');
    b.className = 'badge-count';
    b.textContent = cart.count;
    document.getElementById('cartBtn').appendChild(b);
  }

  if (cart.items.length === 0) {
    body.innerHTML = '<div class="empty-note">Your cart is empty.<br>Browse the items below to add something.</div>';
  } else {
    body.innerHTML = cart.items.map(item => `
      <div class="cart-item">
        <div class="cart-item-info">
          <h5>${escapeHtml(item.name)}</h5>
          <p>${fmtMoney(item.price)} ${item.type === 'fixed' ? 'each' : ''}</p>
          <div class="qty-row">
            ${item.type === 'fixed' ? `
              <button class="qty-btn" data-dec="${item.line_id}">&minus;</button>
              <span>${item.qty}</span>
              <button class="qty-btn" data-inc="${item.line_id}">+</button>
            ` : ''}
            <button class="remove-link" data-remove="${item.line_id}">Remove</button>
          </div>
        </div>
      </div>
    `).join('');
  }
  document.getElementById('cartTotal').textContent = fmtMoney(cart.total);

  body.querySelectorAll('[data-inc]').forEach(b => b.addEventListener('click', () => changeQty(b.dataset.inc, 1)));
  body.querySelectorAll('[data-dec]').forEach(b => b.addEventListener('click', () => changeQty(b.dataset.dec, -1)));
  body.querySelectorAll('[data-remove]').forEach(b => b.addEventListener('click', () => removeLine(b.dataset.remove)));
}

async function changeQty(lineId, delta) {
  try {
    const json = await postForm('/api/cart_update.php', { line_id: lineId, delta });
    renderCartFromServer(json.cart);
  } catch (e) { showToast(e.message, true); }
}
async function removeLine(lineId) {
  try {
    const json = await postForm('/api/cart_remove.php', { line_id: lineId });
    renderCartFromServer(json.cart);
  } catch (e) { showToast(e.message, true); }
}

document.querySelectorAll('[data-add]').forEach(btn => {
  btn.addEventListener('click', async () => {
    const productId = btn.dataset.add;
    const amountInput = document.getElementById('amt-' + productId);
    const payload = { product_id: productId };
    if (amountInput) {
      const val = parseFloat(amountInput.value);
      if (!val || val <= 0) { showToast('Enter an amount first', true); return; }
      payload.amount = val;
    }
    try {
      const json = await postForm('/api/cart_add.php', payload);
      renderCartFromServer(json.cart);
      showToast('Added to cart');
      openCart();
      if (amountInput) amountInput.value = '';
    } catch (e) {
      showToast(e.message, true);
    }
  });
});

document.getElementById('checkoutBtn').addEventListener('click', () => {
  const summary = document.getElementById('checkoutSummary');
  const rows = Array.from(document.querySelectorAll('#cartBody .cart-item'));
  if (rows.length === 0) { showToast('Your cart is empty', true); return; }
  summary.innerHTML = document.getElementById('cartBody').innerHTML.replace(/<div class="qty-row">[\s\S]*?<\/div>/g, '')
    + `<div style="display:flex;justify-content:space-between;font-weight:700;margin-top:14px;padding-top:12px;border-top:1px solid var(--mist);"><span>Total</span><span>${document.getElementById('cartTotal').textContent}</span></div>`;
  document.getElementById('checkoutResult').innerHTML = '';
  openModal('checkoutModal');
});

document.getElementById('confirmOrder').addEventListener('click', async () => {
  const name = document.getElementById('ckName').value.trim();
  const email = document.getElementById('ckEmail').value.trim();
  const paymentMethod = (document.querySelector('input[name="ckPaymentMethod"]:checked') || {}).value || 'online';
  if (!name) { showToast('Please enter your name', true); return; }
  const btn = document.getElementById('confirmOrder');
  btn.disabled = true;
  try {
    const json = await postForm('/api/checkout.php', { name, email, payment_method: paymentMethod });
    let instructions;
    if (json.payment_method === 'online') {
      instructions = `
        Pay via EFT / online banking to:
        <br><strong>${escapeHtml(json.bank.holder)}</strong>
        <br>${escapeHtml(json.bank.bank)} &middot; Acc ${escapeHtml(json.bank.account)} &middot; Branch ${escapeHtml(json.bank.branch)} &middot; ${escapeHtml(json.bank.type)}
        <br>Use reference <strong>${json.reference}</strong> so we can match your payment.`;
    } else {
      instructions = `
        Bring reference <strong>${json.reference}</strong> and pay by card or cash at the
        church office after any service &mdash; or WhatsApp/email us if you'd like a reminder.`;
    }
    document.getElementById('checkoutResult').innerHTML = `
      <div style="margin-top:6px;padding:16px;background:var(--ivory-2);border-radius:10px;font-size:13.5px;">
        Thank you, ${escapeHtml(name)}. Your reference is <strong>${json.reference}</strong> for ${fmtMoney(json.total)}.
        ${instructions}
      </div>`;
    renderCartFromServer({ items: [], total: 0, count: 0 });
  } catch (e) {
    showToast(e.message, true);
  } finally {
    btn.disabled = false;
  }
});
