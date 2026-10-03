/* Shared site behaviour: mobile nav, header scroll state, toast helper,
   and a small fetch wrapper that always sends the CSRF token. */

const header = document.getElementById('siteHeader');
if (header) {
  window.addEventListener('scroll', () => {
    header.classList.toggle('scrolled', window.scrollY > 30);
  });
}

const navToggle = document.getElementById('navToggle');
const mainNav = document.getElementById('mainNav');
if (navToggle && mainNav) {
  navToggle.addEventListener('click', () => {
    const open = mainNav.classList.toggle('open');
    navToggle.setAttribute('aria-expanded', open);
  });
  mainNav.querySelectorAll('a').forEach(a => a.addEventListener('click', () => mainNav.classList.remove('open')));
}

function showToast(msg, isError) {
  const t = document.getElementById('toast');
  if (!t) return;
  document.getElementById('toastMsg').textContent = msg;
  t.classList.toggle('error', !!isError);
  t.classList.add('show');
  clearTimeout(t._hideTimer);
  t._hideTimer = setTimeout(() => t.classList.remove('show'), 3000);
}

/** POST JSON-ish form data to an endpoint, always including the CSRF token. */
async function postForm(url, data) {
  const body = new URLSearchParams({ ...data, csrf_token: window.CSRF_TOKEN || '' });
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body,
  });
  let json;
  try { json = await res.json(); } catch (e) { json = { error: 'Unexpected server response.' }; }
  if (!res.ok) {
    throw new Error(json.error || 'Something went wrong. Please try again.');
  }
  return json;
}

function escapeHtml(s) {
  const d = document.createElement('div');
  d.textContent = s;
  return d.innerHTML;
}

function fmtMoney(n) {
  n = Number(n) || 0;
  return 'R' + n.toLocaleString('en-ZA', { minimumFractionDigits: n % 1 === 0 ? 0 : 2 });
}
