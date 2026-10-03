<?php
if (!defined('APP_BOOT')) { http_response_code(403); exit('No direct access'); }

/**
 * Convert a YouTube or Facebook "watch" URL into an embeddable iframe URL.
 * Returns null if the URL isn't recognised, so callers can fall back to a
 * plain "watch on YouTube/Facebook" link instead of a broken embed.
 */
function get_embed_url(string $url): ?string {
    $url = trim($url);
    if ($url === '') return null;

    // youtu.be/VIDEOID
    if (preg_match('~^https?://youtu\.be/([A-Za-z0-9_-]{6,})~i', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1];
    }
    // youtube.com/watch?v=VIDEOID
    if (preg_match('~youtube\.com/watch\?[^ ]*v=([A-Za-z0-9_-]{6,})~i', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1];
    }
    // youtube.com/live/VIDEOID
    if (preg_match('~youtube\.com/live/([A-Za-z0-9_-]{6,})~i', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1];
    }
    // already an embed URL
    if (preg_match('~youtube\.com/embed/[A-Za-z0-9_-]{6,}~i', $url)) {
        return $url;
    }
    // youtube.com/channel or /@handle/live -> can't resolve a video id, use the plugin embed for the channel's live page
    if (preg_match('~youtube\.com/(channel/[A-Za-z0-9_-]+|@[A-Za-z0-9_.-]+)/live~i', $url, $m)) {
        return 'https://www.youtube.com/embed/live_stream?channel=' . rawurlencode($m[1]);
    }

    // Facebook video/watch links -> use Facebook's official plugin embed
    if (preg_match('~^https?://(www\.)?facebook\.com/~i', $url)) {
        return 'https://www.facebook.com/plugins/video.php?href=' . rawurlencode($url) . '&show_text=0';
    }

    return null;
}

/** True if the given embed source is a Facebook plugin embed (needs a taller/different aspect wrapper in some layouts). */
function is_facebook_embed(string $embedUrl): bool {
    return str_contains($embedUrl, 'facebook.com/plugins');
}
