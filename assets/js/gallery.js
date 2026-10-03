/* Photo gallery: lightbox with prev/next + keyboard nav, and download
   buttons work natively via the anchor's download attribute (no JS needed
   for that part). */

(function () {
  const lightbox = document.getElementById('lightbox');
  if (!lightbox) return;

  const imgEl = document.getElementById('lightboxImg');
  const captionEl = document.getElementById('lightboxCaption');
  const tiles = Array.from(document.querySelectorAll('.photo-tile img[data-lightbox-src]'));
  let currentIndex = -1;

  function openAt(index) {
    if (index < 0 || index >= tiles.length) return;
    currentIndex = index;
    const el = tiles[index];
    imgEl.src = el.dataset.lightboxSrc;
    imgEl.alt = el.alt;
    captionEl.textContent = el.dataset.caption || '';
    lightbox.classList.add('show');
    document.body.style.overflow = 'hidden';
  }
  function close() {
    lightbox.classList.remove('show');
    document.body.style.overflow = '';
  }
  function next() { openAt((currentIndex + 1) % tiles.length); }
  function prev() { openAt((currentIndex - 1 + tiles.length) % tiles.length); }

  tiles.forEach((img, i) => {
    img.style.cursor = 'zoom-in';
    img.addEventListener('click', () => openAt(i));
  });

  document.getElementById('lightboxClose').addEventListener('click', close);
  document.getElementById('lightboxNext').addEventListener('click', next);
  document.getElementById('lightboxPrev').addEventListener('click', prev);
  lightbox.addEventListener('click', (e) => { if (e.target === lightbox) close(); });

  document.addEventListener('keydown', (e) => {
    if (!lightbox.classList.contains('show')) return;
    if (e.key === 'Escape') close();
    if (e.key === 'ArrowRight') next();
    if (e.key === 'ArrowLeft') prev();
  });
})();
