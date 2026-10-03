/* Admin dashboard: keeps an open chat thread live-updating, and submits
   replies over fetch so the admin doesn't lose scroll position on every
   reply. Falls back to a normal form POST if JS fails, since the reply
   form has a real action/method. */

(function () {
  const view = document.querySelector('.chat-thread-view');
  if (!view) return;

  const threadId = view.dataset.threadId;
  const messagesBox = document.getElementById('adminChatMessages');
  const form = document.getElementById('adminReplyForm');
  const input = document.getElementById('adminReplyInput');

  function renderMessages(messages) {
    messagesBox.innerHTML = messages.map(m =>
      `<div class="msg ${m.sender === 'admin' ? 'admin' : 'visitor'}">${escapeHtml(m.message)}</div>`
    ).join('');
    messagesBox.scrollTop = messagesBox.scrollHeight;
  }
  messagesBox.scrollTop = messagesBox.scrollHeight;

  setInterval(async () => {
    try {
      const res = await fetch('/admin/messages_poll.php?thread=' + encodeURIComponent(threadId));
      const json = await res.json();
      if (json.messages) renderMessages(json.messages);
    } catch (e) { /* transient network hiccup */ }
  }, 4000);

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const message = input.value.trim();
    if (!message) return;
    input.value = '';
    try {
      const body = new URLSearchParams({
        csrf_token: window.CSRF_TOKEN || '',
        thread_id: threadId,
        message,
      });
      const res = await fetch('/admin/chat_reply.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'fetch',
        },
        body,
      });
      const json = await res.json();
      if (json.messages) renderMessages(json.messages);
    } catch (e) {
      form.submit(); // fall back to a normal page reload if fetch failed
    }
  });
})();
