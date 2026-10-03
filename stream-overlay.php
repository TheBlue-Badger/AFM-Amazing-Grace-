<?php
define('APP_BOOT', true);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/functions.php';
// No DB access needed here: the page just loads the JS, which polls the comments API.
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Stream comments overlay</title>
<style>
  /* Transparent so OBS composites only the comment text on top of the video. */
  html, body { background: transparent !important; margin: 0; padding: 0; height: 100%; overflow: hidden; font-family: 'Inter', 'Segoe UI', Arial, sans-serif; }
  #streamCommentsFeed {
    position: absolute; left: 24px; bottom: 24px; width: min(520px, 46vw);
    max-height: 60vh; overflow: hidden; display: flex; flex-direction: column; justify-content: flex-end; gap: 8px;
  }
  .stream-comment {
    display: inline-block; align-self: flex-start; max-width: 100%;
    background: rgba(8, 20, 40, 0.62); color: #fff; border-radius: 12px; padding: 8px 14px;
    font-size: 22px; line-height: 1.3; text-shadow: 0 1px 3px rgba(0,0,0,.7);
    animation: slidein .28s ease-out; word-wrap: break-word; overflow-wrap: anywhere;
  }
  .stream-comment-name { color: #e4c877; font-weight: 700; margin-right: 8px; }
  .stream-comment-text { font-weight: 500; }
  .stream-comment.fade-out { opacity: 0; transition: opacity .6s ease; }
  @keyframes slidein { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
</style>
</head>
<body class="is-overlay">
  <div id="streamCommentsFeed"></div>
  <script src="/assets/js/stream_chat.js"></script>
</body>
</html>
