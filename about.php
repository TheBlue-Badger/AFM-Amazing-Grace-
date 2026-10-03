<?php
define('APP_BOOT', true);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';

$pageTitle = SITE_NAME . ', About Us';
$activePage = 'about';
include __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <span class="eyebrow">Who we are</span>
    <h1>Faith, family and service in Eersteriver.</h1>
    <p class="lead"><?= h(SITE_NAME) ?> is a Shona-speaking congregation of the Apostolic Faith Mission of South Africa, meeting at Forest Heights High School since our earliest gatherings.</p>
  </div>
</section>

<section>
  <div class="container">
    <div class="mv-grid">
      <div class="mv-card">
        <span class="eyebrow">Our mission</span>
        <h3>Growing a church that shows up for its city</h3>
        <p>We exist to help people grow spiritually, build real unity across our community, and offer practical support to anyone who needs it, serving with love, compassion and humility in everything we do.</p>
      </div>
      <div class="mv-card">
        <span class="eyebrow">Our vision</span>
        <h3>A church family that extends grace beyond its walls</h3>
        <p>We're working toward a strong, connected church family that carries grace, hope and practical help into Eersteriver and the communities beyond it.</p>
      </div>
    </div>

    <div class="section-head" style="margin-top:60px;">
      <span class="eyebrow">What we hold to</span>
      <h2>Our values</h2>
      <p>Eight commitments that shape how we do church together.</p>
    </div>
    <div class="values-grid" style="grid-template-columns:repeat(4,1fr);">
      <div class="value-card"><h4>Faith</h4><p>Taking God at His word.</p></div>
      <div class="value-card"><h4>Compassion</h4><p>Meeting people where they are.</p></div>
      <div class="value-card"><h4>Inclusivity</h4><p>Everyone is welcome here.</p></div>
      <div class="value-card"><h4>Service</h4><p>Faith shown through action.</p></div>
      <div class="value-card"><h4>Fellowship</h4><p>Doing life together, not alone.</p></div>
      <div class="value-card"><h4>Unity</h4><p>One family, one mission.</p></div>
      <div class="value-card"><h4>Spiritual growth</h4><p>Never standing still in faith.</p></div>
      <div class="value-card"><h4>Love for all</h4><p>No exceptions, no conditions.</p></div>
    </div>
  </div>
</section>

<section id="pastor" class="bg-mist">
  <div class="container pastor-grid">
    <div class="pastor-photo">
      <img src="/images/pastor.jpg" alt="Rev G. K. Muwunganirwa ministering the Word">
    </div>
    <div>
      <span class="eyebrow">From the pulpit</span>
      <h2>A word from our pastor</h2>
      <p class="pastor-quote">"Church was never meant to be a building you visit. It's a family you belong to, and a mission you carry with you."</p>
      <p class="pastor-name"><?= h(PASTOR_NAME) ?></p>
      <p class="pastor-role">Resident Pastor, <?= h(SITE_NAME) ?></p>
      <p>Rev Muwunganirwa leads Amazing Grace together with Mrs. Muwunganirwa (Amai), shepherding the congregation in worship, teaching and pastoral care. Add a fuller biography here covering years in ministry, calling, and heart for Eersteriver whenever you're ready.</p>
      <a href="/contact.php" class="btn btn-dark btn-sm">Request a meeting</a>
    </div>
  </div>
</section>

<section id="leadership">
  <div class="container">
    <div class="section-head center">
      <span class="eyebrow">Our team</span>
      <h2>Leadership at Amazing Grace</h2>
      <p>The people who serve and shepherd this congregation week to week.</p>
    </div>
    <div class="leader-grid">
      <div class="leader-card">
        <img src="/images/couple.jpg" alt="Rev G. K. Muwunganirwa and Amai" class="leader-avatar" style="object-fit:cover;">
        <h4>Rev &amp; Mrs. Muwunganirwa</h4>
        <p>Pastor &amp; Amai</p>
      </div>
      <div class="leader-card">
        <div class="leader-avatar">M</div>
        <h4>Mr &amp; Mrs. Marimo</h4>
        <p>Elders / Church Advisors</p>
      </div>
      <div class="leader-card">
        <div class="leader-avatar">T</div>
        <h4>Mr &amp; Mrs. Tembo</h4>
        <p>Deacons / Secretary</p>
      </div>
      <div class="leader-card">
        <div class="leader-avatar">M</div>
        <h4>Mrs. Muzunze</h4>
        <p>Deaconess / Administrator</p>
      </div>
      <div class="leader-card">
        <div class="leader-avatar">M</div>
        <h4>Miss Marimo</h4>
        <p>Youth Leader</p>
      </div>
      <div class="leader-card">
        <div class="leader-avatar placeholder">?</div>
        <h4 class="tbd">[Add Name]</h4>
        <p>Deputy Youth Leader</p>
      </div>
      <div class="leader-card">
        <div class="leader-avatar placeholder">?</div>
        <h4 class="tbd">[Add Name]</h4>
        <p>Men's Fellowship Leader</p>
      </div>
      <div class="leader-card">
        <div class="leader-avatar placeholder">?</div>
        <h4 class="tbd">[Add Name]</h4>
        <p>Women's Fellowship Leader</p>
      </div>
      <div class="leader-card">
        <div class="leader-avatar placeholder">?</div>
        <h4 class="tbd">[Add Name]</h4>
        <p>Treasurer</p>
      </div>
      <div class="leader-card">
        <div class="leader-avatar placeholder">?</div>
        <h4 class="tbd">[Add Name]</h4>
        <p>Sunday School Leader</p>
      </div>
    </div>
    <p class="form-note" style="text-align:center;margin-top:22px;">Cards marked <strong>[Add Name]</strong> are placeholders, edit them directly in <code>about.php</code> once those roles are filled.</p>
  </div>
</section>

<section id="heritage">
  <div class="container">
    <div class="section-head center">
      <span class="eyebrow">Where we come from</span>
      <h2>A movement, not just a building</h2>
      <p>Amazing Grace carries a heritage that stretches from Los Angeles to Cape Town to Eersteriver.</p>
    </div>
    <div class="timeline">
      <div class="timeline-row">
        <div class="timeline-year">1906</div>
        <div><h4>Azusa Street, Los Angeles</h4><p>A prayer meeting led by William Seymour grows into the Azusa Street Revival, widely regarded as the spark behind the modern Pentecostal movement.</p></div>
      </div>
      <div class="timeline-row">
        <div class="timeline-year">1908</div>
        <div><h4>The message reaches Cape Town</h4><p>John G. Lake and Thomas Hezmalhalch arrive in Cape Town and begin preaching, planting what would grow into the Apostolic Faith Mission of South Africa, the church our own assembly is part of today.</p></div>
      </div>
      <div class="timeline-row">
        <div class="timeline-year">1915</div>
        <div><h4>Carried north by migrant workers</h4><p>Southern Rhodesian mineworkers in South Africa encounter the same message and carry it home, planting the first Shona-speaking AFM assemblies in what is now Zimbabwe.</p></div>
      </div>
      <div class="timeline-row">
        <div class="timeline-year">Today</div>
        <div><h4>Back where it started</h4><p>Amazing Grace is a Shona-speaking AFM assembly meeting in the very city the movement first landed in, carrying that same heritage forward for Eersteriver.</p></div>
      </div>
    </div>
  </div>
</section>

<section class="bg-navy">
  <div class="container" style="text-align:center;">
    <span class="eyebrow" style="color:var(--gold-soft);">Part of something bigger</span>
    <h2 style="max-width:24ch;margin:0 auto 14px;">A branch of the AFM of South Africa</h2>
    <p style="max-width:60ch;margin:0 auto 26px;">Amazing Grace is one local expression of a national movement that has been planting Pentecostal churches in South Africa since 1908. Read the national confession of faith to see what we hold to as a movement.</p>
    <a href="https://afm-ags.org/confession-of-faith-afm/" target="_blank" rel="noopener" class="btn btn-outline">Read the Confession of Faith</a>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
