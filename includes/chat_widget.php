<?php if (!defined('APP_BOOT')) { http_response_code(403); exit('No direct access'); } ?>
<button class="chat-bubble" id="chatBubble" aria-label="Open chat">
  <svg viewBox="0 0 24 24" fill="none"><path d="M21 11.5a8.5 8.5 0 01-8.5 8.5c-1.2 0-2.34-.26-3.36-.73L3 21l1.87-5.6A8.5 8.5 0 1121 11.5z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
</button>
<div class="chat-panel" id="chatPanel">
  <div class="chat-panel-head">
    <div>
      <h4>Chat with us</h4>
      <p><?= h(SITE_SHORT_NAME) ?> &middot; usually replies within a day</p>
    </div>
    <button id="chatCloseBtn" style="background:none;border:none;color:#fff;font-size:20px;">&times;</button>
  </div>
  <div id="chatPrestart" class="chat-prestart">
    <div class="field"><label>Your name</label><input type="text" id="chatName" maxlength="80" placeholder="e.g. Tendai"></div>
    <div class="field"><label>How can we help?</label><textarea id="chatFirstMsg" rows="3" maxlength="1000" placeholder="Type your message..."></textarea></div>
    <button class="btn btn-dark btn-block btn-sm" id="chatStartBtn">Start conversation</button>
  </div>
  <div id="chatActive" style="display:none;flex-direction:column;flex:1;min-height:0;">
    <div class="chat-panel-body" id="chatMessages"></div>
    <div class="chat-panel-foot">
      <input type="text" id="chatReplyInput" maxlength="1000" placeholder="Type a message...">
      <button class="chat-send" id="chatSendBtn" aria-label="Send"><svg viewBox="0 0 24 24" fill="none"><path d="M4 12l16-7-6 16-3-6-7-3z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" stroke-linecap="round"/></svg></button>
    </div>
  </div>
</div>
