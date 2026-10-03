/* Twitch-style live comment wall for the stream. Works in two contexts:
   - livestream.php: full panel with a name+message form (elements below exist)
   - stream-overlay.php: read-only scrolling feed meant for an OBS Browser
     Source, so comments are composited straight into the outgoing broadcast
     (no form there, it just polls and renders)
*/
(function () {
  const feed = document.getElementById('streamCommentsFeed');
  if (!feed) return;

  function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }
  async function postComment(name, message) {
    const body = new URLSearchParams({ name, message, csrf_token: window.CSRF_TOKEN || '' });
    const res = await fetch('/api/stream_comment_post.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
    const json = await res.json();
    if (!res.ok) throw new Error(json.error || 'Could not post your comment.');
    return json;
  }

  const form = document.getElementById('streamCommentForm');
  const isOverlay = document.body.classList.contains('is-overlay');
  let lastId = 0;
  let storedName = '';

  function addComment(c) {
    const row = document.createElement('div');
    row.className = 'stream-comment';
    row.innerHTML = `<span class="stream-comment-name">${escapeHtml(c.name)}</span><span class="stream-comment-text">${escapeHtml(c.message)}</span>`;
    feed.appendChild(row);
    feed.scrollTop = feed.scrollHeight;

    // On the OBS overlay, fade and remove old comments so the wall doesn't
    // grow forever across a multi-hour stream sitting in a browser source.
    if (isOverlay) {
      setTimeout(() => {
        row.classList.add('fade-out');
        setTimeout(() => row.remove(), 600);
      }, 12000);
    }
    // Cap how many rows we keep on-site too, to avoid unbounded DOM growth.
    while (feed.children.length > 150) {
      feed.removeChild(feed.firstChild);
    }
  }

  async function poll() {
    try {
      const res = await fetch('/api/stream_comment_poll.php?since=' + lastId);
      const json = await res.json();
      json.comments.forEach(addComment);
      lastId = json.last_id;
      document.dispatchEvent(new CustomEvent('stream-live-state', { detail: { isLive: json.is_live } }));
    } catch (e) { /* transient network hiccup, next poll will catch up */ }
  }

  poll();
  setInterval(poll, isOverlay ? 2000 : 3000);

  if (form) {
    const nameInput = document.getElementById('streamCommentName');
    const msgInput = document.getElementById('streamCommentMessage');
    storedName = sessionStorage.getItem('streamCommentName') || '';
    if (storedName) nameInput.value = storedName;

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const name = nameInput.value.trim();
      const message = msgInput.value.trim();
      if (!name || !message) return;
      sessionStorage.setItem('streamCommentName', name);
      msgInput.value = '';
      try {
        await postComment(name, message);
        poll();
      } catch (err) {
        alert(err.message);
      }
    });
  }
})();
