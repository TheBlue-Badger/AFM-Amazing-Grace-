<?php
define('APP_BOOT', true);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';

$pdo = db();
$today = date('Y-m-d');

$upcoming = $pdo->prepare('SELECT * FROM events WHERE is_active = 1 AND event_date >= ? ORDER BY event_date ASC, event_time ASC');
$upcoming->execute([$today]);
$upcoming = $upcoming->fetchAll();

// All active events for the current year, for the year planner (grouped by month).
$yearStart = date('Y') . '-01-01';
$yearEnd = date('Y') . '-12-31';
$yearStmt = $pdo->prepare('SELECT * FROM events WHERE is_active = 1 AND event_date BETWEEN ? AND ? ORDER BY event_date ASC');
$yearStmt->execute([$yearStart, $yearEnd]);
$yearEvents = $yearStmt->fetchAll();
$byMonth = [];
foreach ($yearEvents as $e) {
    $m = date('n', strtotime($e['event_date']));
    $byMonth[$m][] = $e;
}

// ---- Calendar month grid (server-rendered, navigable via ?month=YYYY-MM) ----
$monthParam = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
    $monthParam = date('Y-m');
}
$monthStart = $monthParam . '-01';
$monthTs = strtotime($monthStart);
$daysInMonth = (int)date('t', $monthTs);
$firstWeekday = (int)date('N', $monthTs); // 1 (Mon) - 7 (Sun)
$prevMonth = date('Y-m', strtotime('-1 month', $monthTs));
$nextMonth = date('Y-m', strtotime('+1 month', $monthTs));

$eventsByDay = [];
$monthEventsStmt = $pdo->prepare("SELECT * FROM events WHERE is_active = 1 AND event_date LIKE ? ORDER BY event_time ASC");
$monthEventsStmt->execute([$monthParam . '-%']);
foreach ($monthEventsStmt->fetchAll() as $e) {
    $day = (int)date('j', strtotime($e['event_date']));
    $eventsByDay[$day][] = $e;
}

$pageTitle = SITE_NAME . ', Events';
$activePage = 'events';
include __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <span class="eyebrow">What's on</span>
    <h1>Events, services and the year ahead.</h1>
    <p class="lead">Everything happening at <?= h(SITE_SHORT_NAME) ?>, from weekly services to seasonal gatherings.</p>
  </div>
</section>

<section>
  <div class="container">
    <div class="section-head">
      <span class="eyebrow">Coming up</span>
      <h2>Upcoming events</h2>
    </div>
    <?php if (!$upcoming): ?>
      <p>No upcoming events are listed right now, check back soon or see our weekly services on the <a href="/index.php#events">home page</a>.</p>
    <?php else: ?>
      <div class="event-card-grid">
        <?php foreach ($upcoming as $e): ?>
          <div class="event-card" id="event-<?= (int)$e['id'] ?>">
            <?php if ($e['poster_image']): ?>
              <div class="event-card-image"><img src="<?= h(upload_url($e['poster_image'], 'events')) ?>" alt="<?= h($e['title']) ?>"></div>
            <?php else: ?>
              <div class="event-card-image placeholder-image"><span><?= h($e['title']) ?></span></div>
            <?php endif; ?>
            <div class="event-card-body">
              <div class="event-card-date"><?= h(date('d M Y', strtotime($e['event_date']))) ?><?= $e['event_time'] ? ' &middot; ' . h($e['event_time']) : '' ?></div>
              <h4><?= h($e['title']) ?></h4>
              <?php if ($e['location']): ?><p class="event-card-location">&#128205; <?= h($e['location']) ?></p><?php endif; ?>
              <?php if ($e['description']): ?><p><?= nl2br(h($e['description'])) ?></p><?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="bg-mist">
  <div class="container">
    <div class="section-head">
      <span class="eyebrow">Calendar</span>
      <h2><?= h(date('F Y', $monthTs)) ?></h2>
      <p>Days with an event are marked, hover or tap a day to see what's on.</p>
    </div>

    <div class="calendar-nav">
      <a href="?month=<?= h($prevMonth) ?>" class="btn btn-ghost btn-sm">&larr; Prev</a>
      <a href="?month=<?= h(date('Y-m')) ?>" class="btn btn-ghost btn-sm">Today</a>
      <a href="?month=<?= h($nextMonth) ?>" class="btn btn-ghost btn-sm">Next &rarr;</a>
    </div>

    <div class="calendar-grid">
      <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $dow): ?>
        <div class="calendar-dow"><?= $dow ?></div>
      <?php endforeach; ?>

      <?php for ($i = 1; $i < $firstWeekday; $i++): ?>
        <div class="calendar-cell empty"></div>
      <?php endfor; ?>

      <?php for ($d = 1; $d <= $daysInMonth; $d++):
        $hasEvents = !empty($eventsByDay[$d]);
        $isToday = ($monthParam . '-' . str_pad((string)$d, 2, '0', STR_PAD_LEFT)) === $today;
        $titles = $hasEvents ? implode(' | ', array_map(fn($e) => $e['title'], $eventsByDay[$d])) : '';
      ?>
        <div class="calendar-cell<?= $isToday ? ' is-today' : '' ?><?= $hasEvents ? ' has-events' : '' ?>" <?= $hasEvents ? 'title="' . h($titles) . '"' : '' ?>>
          <span class="calendar-daynum"><?= $d ?></span>
          <?php if ($hasEvents): ?><span class="calendar-dot"></span><?php endif; ?>
        </div>
      <?php endfor; ?>
    </div>
  </div>
</section>

<section>
  <div class="container">
    <div class="section-head">
      <span class="eyebrow"><?= h(date('Y')) ?> year planner</span>
      <h2>The year at a glance</h2>
      <p>Every event on the calendar this year, grouped by month.</p>
    </div>
    <?php if (!$byMonth): ?>
      <p>No events have been scheduled for <?= h(date('Y')) ?> yet.</p>
    <?php else: ?>
      <div class="planner">
        <?php for ($m = 1; $m <= 12; $m++): if (empty($byMonth[$m])) continue; ?>
          <div class="planner-month">
            <h4><?= h(date('F', mktime(0,0,0,$m,1))) ?></h4>
            <ul>
              <?php foreach ($byMonth[$m] as $e): ?>
                <li><a href="#event-<?= (int)$e['id'] ?>"><?= h(date('d', strtotime($e['event_date']))) ?> &middot; <?= h($e['title']) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
