<?php
define('APP_BOOT', true);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/embed.php';

$pdo = db();
$stream = $pdo->query('SELECT * FROM livestreams ORDER BY id DESC LIMIT 1')->fetch();
$homeEmbedUrl = ($stream && $stream['is_live'] && $stream['stream_url']) ? get_embed_url($stream['stream_url']) : null;

$pageTitle = SITE_NAME . ', Eersteriver, Cape Town';
$activePage = 'home';
include __DIR__ . '/includes/header.php';
?>

<section class="hero" id="home">
  <div class="hero-rays"></div>
  <div class="container hero-grid">
    <div class="hero-copy">
      <span class="eyebrow"><?= h(SITE_NAME) ?></span>
      <h1>Grace has a home in Eersteriver.</h1>
      <p class="lead">We're a Shona-speaking, Spirit-filled church family walking out the Great Commission together, through worship, the Word, and the way we show up for our neighbours. Come as you are.</p>
      <div class="hero-cta">
        <a href="/index.php#events" class="btn btn-primary">Plan Your Visit</a>
        <a href="/about.php#pastor" class="btn btn-outline">Meet the Pastor</a>
      </div>
      <div class="hero-chip"><span class="dot"></span> Sunday Worship &middot; 9:00 AM &middot; Forest Heights High School</div>
    </div>
    <div class="hero-visual">
      <div class="ring"></div>
      <div class="hero-photo-wrap">
        <img src="/images/pastor.jpg" alt="Pastor preaching at AFM Amazing Grace Center">
        <div class="hero-photo-caption">
          <div>
            <strong>This Sunday</strong><br>
            "Rooted in the Word, led by the Spirit."
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="about-teaser">
  <div class="container about-grid">
    <div>
      <span class="eyebrow">Who we are</span>
      <h2>Part of a story that began in 1908.</h2>
      <p class="lead"><?= h(SITE_NAME) ?> is a local assembly of the Apostolic Faith Mission of South Africa, the country's first Pentecostal movement. We believe the same Spirit that filled the early church is still at work today, and we exist to help Eersteriver meet Him.</p>
      <p>As an apostolic community, every member here is an agent of the gospel, not a spectator. Whatever season you're in, there's a place for you at Amazing Grace, in a pew, on a serving team, or simply asking questions in our WhatsApp group.</p>
      <a href="/about.php" class="btn btn-ghost btn-sm">More about our church</a>
    </div>
    <div class="values-grid">
      <div class="value-card">
        <div class="mark"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 3l7 4v5c0 5-3.5 7.5-7 9-3.5-1.5-7-4-7-9V7l7-4z" stroke="currentColor" stroke-width="1.6"/></svg></div>
        <h4>Faith</h4>
        <p>We take God at His word, and we build every ministry on it, not on trend or preference.</p>
      </div>
      <div class="value-card">
        <div class="mark"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="9" cy="9" r="3" stroke="currentColor" stroke-width="1.6"/><circle cx="17" cy="10" r="2.4" stroke="currentColor" stroke-width="1.6"/><path d="M3 19c0-3 2.7-5 6-5s6 2 6 5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg></div>
        <h4>Fellowship</h4>
        <p>Church is a family here, across every generation, walking through life together.</p>
      </div>
      <div class="value-card">
        <div class="mark"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/></svg></div>
        <h4>Service</h4>
        <p>We show up for Eersteriver, not just for each other, with practical, everyday help.</p>
      </div>
      <div class="value-card">
        <div class="mark"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 2l2.6 6.2L21 9l-5 4.5L17.4 21 12 17.3 6.6 21 8 13.5 3 9l6.4-.8L12 2z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg></div>
        <h4>Unity</h4>
        <p>One church family, one mission, whatever language, background, or season of life.</p>
      </div>
    </div>
  </div>
</section>

<section id="ministries" class="bg-mist">
  <div class="container">
    <div class="section-head center">
      <span class="eyebrow">Get plugged in</span>
      <h2>Ministries &amp; fellowships</h2>
      <p>There's a group for every season of life at Amazing Grace.</p>
    </div>
    <div class="ministry-grid">
      <div class="ministry-card">
        <div class="ministry-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M12 21s-7-4.35-9.5-8.5C.7 8.9 2.6 5 6.2 5c2 0 3.4 1.1 4.3 2.4C11.4 6.1 12.8 5 14.8 5c3.6 0 5.5 3.9 3.7 7.5C15 16.65 12 21 12 21z" stroke="currentColor" stroke-width="1.5"/></svg></div>
        <h4>Sisters' Fellowship</h4>
        <p>Prayer, mentorship and practical support for the women of the congregation.</p>
      </div>
      <div class="ministry-card">
        <div class="ministry-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M12 12a4 4 0 100-8 4 4 0 000 8zM5 21c0-3.6 3-6 7-6s7 2.4 7 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></div>
        <h4>Men's Fellowship</h4>
        <p>Straight-talk fellowship, accountability and service projects for the men.</p>
      </div>
      <div class="ministry-card">
        <div class="ministry-icon"><svg viewBox="0 0 24 24" fill="none"><path d="M12 3v6M8 6l4 3 4-3M4 21h16M6 21v-6a2 2 0 012-2h8a2 2 0 012 2v6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
        <h4>Young Adults Ministry</h4>
        <p>YAM meets for worship, prayer and honest conversation at Van Riebeeck &amp; Station Road.</p>
      </div>
      <div class="ministry-card">
        <div class="ministry-icon"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="3" stroke="currentColor" stroke-width="1.5"/><path d="M6 20c0-3 2.7-5 6-5s6 2 6 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M3 8l1.5 1.5M21 8l-1.5 1.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></div>
        <h4>Children's Church</h4>
        <p>A safe, joyful space where kids learn the Bible through story, song and play.</p>
      </div>
    </div>
  </div>
</section>

<section id="watch-live">
  <div class="container" style="text-align:center;">
    <?php if ($homeEmbedUrl): ?>
      <div class="live-badge" style="margin-left:auto;margin-right:auto;"><span class="pulse"></span> LIVE NOW</div>
      <h2 style="max-width:22ch;margin-left:auto;margin-right:auto;">We're live, join the service now</h2>
      <div class="stream-embed" style="max-width:820px;margin:24px auto 0;">
        <iframe src="<?= h($homeEmbedUrl) ?>" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen title="Live service"></iframe>
      </div>
      <a href="/livestream.php" class="btn btn-primary" style="margin-top:22px;">Watch &amp; join the live comments</a>
    <?php else: ?>
      <span class="eyebrow">Watch live</span>
      <h2 style="max-width:22ch;margin:0 auto 14px;">Can't be there in person? Join online.</h2>
      <p style="max-width:56ch;margin:0 auto 26px;">We're not live right now, but you can catch our Sunday service live when it starts, or watch a recording any time.</p>
      <a href="/livestream.php" class="btn btn-dark">Go to Live Page</a>
    <?php endif; ?>
  </div>
</section>

<section id="gallery">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow">Life at Amazing Grace</span>
      <h2>Moments from our church family</h2>
    </div>
    <div class="gallery-grid">
      <div class="gallery-tall"><img src="/images/couple.jpg" alt="Family from the Amazing Grace congregation"></div>
      <div class="gallery-small"><img src="/images/pastor.jpg" alt="Sunday service at Amazing Grace"></div>
      <div class="gallery-caption">
        <h4>Baba naMai</h4>
        <p>Every family that walks through our doors becomes part of this one. See more on our <a href="<?= h(SITE_FACEBOOK) ?>" target="_blank" rel="noopener" style="color:var(--gold-soft);text-decoration:underline;">Facebook page</a>.</p>
      </div>
    </div>
  </div>
</section>

<section id="events" class="bg-mist">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow">What's on</span>
      <h2>Services &amp; upcoming events</h2>
      <p>Join us in person at Forest Heights High School, Eersteriver, or follow along on <a href="<?= h(SITE_YOUTUBE) ?>" target="_blank" rel="noopener">YouTube</a>.</p>
    </div>
    <div class="event-list">
      <div class="event-row">
        <div class="event-date"><div class="d">SUN</div><div class="m">WEEKLY</div></div>
        <div><h4>Sunday Worship Service</h4><p>Praise, the Word, and altar ministry for the whole family, in Shona and English.</p></div>
        <div class="event-time">9:00 AM</div>
      </div>
      <div class="event-row">
        <div class="event-date"><div class="d">FRI</div><div class="m">WEEKLY</div></div>
        <div><h4>Young Adults Ministry (YAM)</h4><p>Worship, prayer and the Word for young adults at Van Riebeeck &amp; Station Road.</p></div>
        <div class="event-time">Evening</div>
      </div>
      <div class="event-row">
        <div class="event-date"><div class="d">&#8226;</div><div class="m">MONTHLY</div></div>
        <div><h4>Sisters' Fellowship Meeting</h4><p>Prayer and fellowship for the women of the congregation.</p></div>
        <div class="event-time">Details in service</div>
      </div>
      <div class="event-row">
        <div class="event-date"><div class="d">&#9733;</div><div class="m">SEASONAL</div></div>
        <div><h4>Youth Camp</h4><p>An annual weekend away for our young people, register through the church office.</p></div>
        <div class="event-time"><a href="/shop.php">Register in Shop</a></div>
      </div>
    </div>
  </div>
</section>

<section id="give-teaser" class="bg-navy">
  <div class="container" style="text-align:center;">
    <span class="eyebrow" style="color:var(--gold-soft);">Give &amp; shop</span>
    <span class="eyebrow" style="color:var(--gold-soft);"></span>
    <h2 style="max-width:20ch;margin-left:auto;margin-right:auto;">Support the ministry, wherever you are</h2>
    <p style="max-width:56ch;margin:0 auto 28px;">Give a tithe or offering, register for an event, or pick up something from the church store.</p>
    <a href="/shop.php" class="btn btn-primary">Go to Give &amp; Shop</a>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
