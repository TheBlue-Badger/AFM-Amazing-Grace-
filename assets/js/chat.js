/* Visitor-facing live chat widget. Talks to /api/chat_start.php,
   /api/chat_send.php and /api/chat_poll.php. The active thread is tracked
   server-side via the PHP session, so no visitor identifiers are exposed
   in the page markup. */

(function () {
  const bubble = document.getElementById('chatBubble');
  const panel = document.getElementById('chatPanel');
  if (!bubble || !panel) return;

  const prestart = document.getElementById('chatPrestart');
  const active = document.getElementById('chatActive');
  const messagesBox = document.getElementById('chatMessages');
  let pollTimer = null;
  let hasThread = false;

  bubble.addEventListener('click', () => {
    panel.classList.toggle('show');
    if (panel.classList.contains('show') && hasThread) startPolling();
  });
  document.getElementById('chatCloseBtn').addEventListener('click', () => {
    panel.classList.remove('show');
  });

  function renderMessages(messages) {
    messagesBox.innerHTML = messages.map(m =>
      `<div class="msg ${m.sender === 'admin' ? 'admin' : 'visitor'}">${escapeHtml(m.message)}</div>`
    ).join('');
    messagesBox.scrollTop = messagesBox.scrollHeight;
  }

  function startPolling() {
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(async () => {
      try {
        const res = await fetch('/api/chat_poll.php');
        const json = await res.json();
        if (json.messages) renderMessages(json.messages);
      } catch (e) { /* silent: transient network hiccup */ }
    }, 4000);
  }

  document.getElementById('chatStartBtn').addEventListener('click', async () => {
    const name = document.getElementById('chatName').value.trim();
    const msg = document.getElementById('chatFirstMsg').value.trim();
    if (!name || !msg) { showToast('Please add your name and a message', true); return; }
    try {
      const json = await postForm('/api/chat_start.php', { name, message: msg });
      hasThread = true;
      prestart.style.display = 'none';
      active.style.display = 'flex';
      renderMessages(json.messages);
      startPolling();
    } catch (e) {
      showToast(e.message, true);
    }
  });

  async function sendMessage() {
    const input = document.getElementById('chatReplyInput');
    const text = input.value.trim();
    if (!text) return;
    input.value = '';
    try {
      const json = await postForm('/api/chat_send.php', { message: text });
      renderMessages(json.messages);
    } catch (e) {
      showToast(e.message, true);
    }
  }
  document.getElementById('chatSendBtn').addEventListener('click', sendMessage);
  document.getElementById('chatReplyInput').addEventListener('keydown', e => {
    if (e.key === 'Enter') sendMessage();
  });

  // If a conversation is already in progress this session, resume it.
  (async function resume() {
    try {
      const res = await fetch('/api/chat_poll.php');
      const json = await res.json();
      if (json.messages && json.messages.length) {
        hasThread = true;
        prestart.style.display = 'none';
        active.style.display = 'flex';
        renderMessages(json.messages);
      }
    } catch (e) { /* no active thread yet */ }
  })();
})();
