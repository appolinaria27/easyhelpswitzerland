<?php
require_once __DIR__ . '/security.php';
session_start();

// Load .env
$dotenv = __DIR__ . '/.env';
if (file_exists($dotenv)) {
    foreach (file($dotenv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v);
    }
}

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: admin.php');
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['admin_csrf'];

// ── Load bookings ──────────────────────────────────────────────────────────
$bookingsDir = __DIR__ . '/bookings';
$pendingDir  = __DIR__ . '/pending-bookings';
$dataDir     = __DIR__ . '/admin-data';

function loadBookings(string $dir, string $type): array {
    $items = [];
    foreach (glob($dir . '/booking-*.json') as $f) {
        $data = json_decode(file_get_contents($f), true);
        if (!$data) continue;
        $data['_type'] = $type;
        $data['_file'] = $f;
        $items[] = $data;
    }
    usort($items, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
    return $items;
}

function loadNote(string $id): array {
    global $dataDir;
    $f = $dataDir . '/' . preg_replace('/[^a-f0-9]/', '', $id) . '.json';
    if (!file_exists($f)) return [];
    return json_decode(file_get_contents($f), true) ?? [];
}

$paid    = loadBookings($bookingsDir, 'paid');
$pending = loadBookings($pendingDir, 'pending');

$totalRevenue = array_sum(array_map(fn($b) => (float)($b['price_chf'] ?? 0), $paid));
$totalPaid    = count($paid);
$totalPending = count($pending);

function fmtDate(string $iso): string {
    try { return (new DateTime($iso))->format('d.m.Y H:i'); }
    catch (Exception $e) { return $iso; }
}

$statusColors = [
    'confirmed' => '#2ecc71', 'completed' => '#4693e8',
    'cancelled' => '#e74c3c', 'pending'   => '#f39c12', '' => '#aaa',
];
$statusLabels = [
    'confirmed' => 'Confirmed', 'completed' => 'Completed',
    'cancelled' => 'Cancelled', 'pending'   => 'Pending',
];

// ── Build calendar data ────────────────────────────────────────────────────
$calendarEvents  = [];
$unscheduled     = [];

foreach (array_merge($paid, $pending) as $b) {
    $id    = $b['internal_booking_id'] ?? '';
    $note  = loadNote($id);
    $termin = $note['termin'] ?? '';
    $name  = $b['name'] ?? 'Unknown';
    $color = $b['_type'] === 'paid' ? '#4693e8' : '#f39c12';

    if ($termin) {
        try {
            $dt  = new DateTime($termin);
            $end = (clone $dt)->modify('+60 minutes');
            $calendarEvents[] = [
                'id'    => $id,
                'title' => $name,
                'start' => $dt->format('c'),
                'end'   => $end->format('c'),
                'color' => $color,
                'extendedProps' => [
                    'email'   => $b['email'] ?? '',
                    'package' => $b['package_name'] ?? $b['package'] ?? '',
                    'status'  => $note['status'] ?? '',
                    'type'    => $b['_type'],
                ],
            ];
        } catch (Exception $e) {}
    } else {
        $unscheduled[] = [
            'id'      => $id,
            'name'    => $name,
            'email'   => $b['email'] ?? '',
            'package' => $b['package_name'] ?? $b['package'] ?? '',
            'type'    => $b['_type'],
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Admin Panel — Easy Help Switzerland</title>
  <meta name="robots" content="noindex,nofollow"/>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Manrope:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="fullcalendar.min.js"></script>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --dark: #0a0e14; --blue: #4693e8; --green: #2ecc71;
      --red: #e74c3c; --bg: #f2f4f7; --card: #fff;
      --border: #e4e8ee; --text: #111; --muted: rgba(17,17,17,.45);
    }
    body { font-family: "Manrope", system-ui, sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; font-weight: 300; font-size: 15px; line-height: 1.65; }

    /* Topbar */
    .topbar { background: var(--dark); color: #fff; padding: 0 36px; height: 64px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 200; }
    .topbar-brand { font-family: "Cormorant Garamond", serif; font-size: 20px; font-weight: 500; letter-spacing: -.02em; }
    .topbar-brand span { font-size: 10px; font-family: "Manrope", sans-serif; color: rgba(255,255,255,.4); margin-left: 12px; letter-spacing: .16em; text-transform: uppercase; font-weight: 400; }
    .topbar-actions { display: flex; gap: 16px; align-items: center; }
    .topbar-actions a { color: rgba(255,255,255,.5); text-decoration: none; font-size: 12px; letter-spacing: .04em; transition: color .2s; font-weight: 400; }
    .topbar-actions a:hover { color: #fff; }
    .btn-logout { background: rgba(255,255,255,.08); color: #fff !important; padding: 7px 18px; border-radius: 6px; border: 1px solid rgba(255,255,255,.12); }
    .btn-logout:hover { background: rgba(255,255,255,.16) !important; }

    /* Container */
    .container { max-width: 1240px; margin: 0 auto; padding: 36px 28px 80px; }

    /* Stats */
    .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 36px; }
    .stat-card { background: var(--card); border-radius: 16px; padding: 28px 28px 24px; border: 1px solid var(--border); }
    .stat-label { font-size: 11px; text-transform: uppercase; letter-spacing: .16em; color: var(--muted); margin-bottom: 10px; font-weight: 500; }
    .stat-value { font-family: "Cormorant Garamond", serif; font-size: 48px; font-weight: 300; color: var(--text); line-height: 1; letter-spacing: -.04em; }
    .stat-value.green { color: var(--green); }
    .stat-value.blue  { color: var(--blue); }

    /* Tabs */
    .tabs { display: flex; gap: 0; margin-bottom: 28px; border-bottom: 1px solid var(--border); padding-bottom: 0; }
    .tab-btn { background: none; border: none; font-family: "Manrope", inherit; font-size: 12px; font-weight: 500; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); cursor: pointer; padding: 12px 24px; transition: all .18s; position: relative; bottom: -1px; border-bottom: 2px solid transparent; }
    .tab-btn:hover { color: var(--text); }
    .tab-btn.active { color: var(--text); border-bottom: 2px solid var(--text); }

    /* Tab panels */
    .tab-panel { display: none; }
    .tab-panel.active { display: block; }

    /* ── Calendar layout ── */
    .cal-layout { display: grid; grid-template-columns: 256px 1fr; gap: 20px; align-items: start; }
    .cal-sidebar { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 20px; min-height: 400px; }
    .sidebar-label { font-size: 10px; text-transform: uppercase; letter-spacing: .16em; color: var(--muted); font-weight: 500; margin-bottom: 14px; display: flex; align-items: center; justify-content: space-between; }
    .sidebar-label .count { background: var(--dark); color: #fff; border-radius: 20px; padding: 2px 9px; font-size: 10px; letter-spacing: 0; }
    .ext-event {
      background: #f0f5ff;
      border: 1px solid #d0e2f8;
      border-left: 3px solid var(--blue);
      border-radius: 10px;
      padding: 11px 13px;
      margin-bottom: 8px;
      cursor: grab;
      transition: box-shadow .15s, transform .15s;
      user-select: none;
    }
    .ext-event.pending-type { background: #fffaf0; border-color: #f5e0b0; border-left-color: #f39c12; }
    .ext-event:hover { box-shadow: 0 4px 16px rgba(70,147,232,.15); transform: translateY(-1px); }
    .ext-event:active { cursor: grabbing; }
    .ext-name { font-size: 13px; font-weight: 500; color: var(--text); margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; letter-spacing: -.01em; }
    .ext-sub { font-size: 11px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ext-badge { display: inline-block; font-size: 10px; font-weight: 500; padding: 2px 8px; border-radius: 4px; margin-top: 5px; background: #e0ecff; color: var(--blue); letter-spacing: .02em; }
    .ext-badge.pending-type { background: #fdefc7; color: #946500; }
    .no-unscheduled { font-size: 13px; color: var(--muted); text-align: center; padding: 28px 0; }

    /* FullCalendar wrapper */
    .cal-main { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 20px; }
    .cal-main .fc { font-family: "Manrope", sans-serif; font-size: 13px; font-weight: 400; }
    .cal-main .fc-toolbar-title { font-family: "Cormorant Garamond", serif; font-size: 26px; font-weight: 300; letter-spacing: -.04em; }
    .cal-main .fc-col-header-cell-cushion { font-size: 11px; text-transform: uppercase; letter-spacing: .1em; font-weight: 500; color: var(--muted); }
    .cal-main .fc-button { background: var(--dark) !important; border-color: var(--dark) !important; font-family: "Manrope", inherit !important; font-size: 11px !important; letter-spacing: .06em !important; border-radius: 6px !important; font-weight: 500 !important; }
    .cal-main .fc-button:hover { opacity: .8; }
    .cal-main .fc-button-active { background: #1a2030 !important; border-color: #1a2030 !important; }
    .cal-main .fc-event { border-radius: 6px !important; font-size: 12px !important; font-weight: 500 !important; cursor: pointer; }
    .cal-main .fc-timegrid-slot-label { font-size: 11px; color: var(--muted); letter-spacing: .04em; }

    /* Drag-over highlight */
    .fc-day:hover, .fc-timegrid-col:hover { background: rgba(70,147,232,.04) !important; }

    /* Tooltip / popup */
    .cal-tooltip {
      position: fixed; z-index: 9999;
      background: #fff; border: 1px solid var(--border);
      border-radius: 12px; padding: 16px 20px; min-width: 220px;
      box-shadow: 0 8px 32px rgba(0,0,0,.12);
      font-size: 13px; pointer-events: none;
      transition: opacity .15s;
    }
    .cal-tooltip strong { font-size: 14px; display: block; margin-bottom: 4px; }
    .cal-tooltip .tt-row { color: var(--muted); margin-top: 2px; }

    /* Confirm modal */
    .modal-overlay {
      position: fixed; inset: 0; background: rgba(0,0,0,.4);
      z-index: 10000; display: flex; align-items: center; justify-content: center;
      opacity: 0; pointer-events: none; transition: opacity .2s;
    }
    .modal-overlay.visible { opacity: 1; pointer-events: all; }
    .modal {
      background: #fff; border-radius: 18px; padding: 36px 40px;
      max-width: 420px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,.15);
      transform: translateY(14px); transition: transform .2s;
    }
    .modal-overlay.visible .modal { transform: translateY(0); }
    .modal h3 { font-family: "Cormorant Garamond", serif; font-size: 32px; font-weight: 300; letter-spacing: -.05em; margin-bottom: 10px; }
    .modal p { font-size: 14px; color: rgba(17,17,17,.55); line-height: 1.7; margin-bottom: 20px; font-weight: 300; }
    .modal .highlight { color: var(--dark); font-weight: 500; }
    .modal-actions { display: flex; gap: 10px; }
    .btn-confirm { flex: 1; padding: 13px; background: var(--dark); color: #fff; border: none; border-radius: 8px; font-family: inherit; font-size: 12px; font-weight: 500; letter-spacing: .08em; text-transform: uppercase; cursor: pointer; transition: opacity .2s; }
    .btn-confirm:hover { opacity: .82; }
    .btn-cancel-modal { flex: 1; padding: 13px; background: transparent; color: var(--muted); border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 12px; letter-spacing: .06em; text-transform: uppercase; cursor: pointer; transition: all .2s; }
    .btn-cancel-modal:hover { border-color: #bbb; color: var(--text); }
    .mail-toggle { display: flex; align-items: center; gap: 8px; margin-bottom: 22px; font-size: 13px; color: rgba(17,17,17,.55); font-weight: 300; }
    .mail-toggle input { width: 16px; height: 16px; cursor: pointer; accent-color: var(--dark); }

    /* ── Booking cards (Bookings tab) ── */
    .section { margin-bottom: 52px; }
    .section-title { font-family: "Cormorant Garamond", serif; font-size: 38px; font-weight: 300; letter-spacing: -.05em; margin-bottom: 20px; display: flex; align-items: center; gap: 14px; line-height: 1; }
    .badge { font-family: "Manrope", sans-serif; font-size: 10px; background: var(--dark); color: #fff; border-radius: 20px; padding: 3px 11px; font-weight: 500; letter-spacing: .06em; }
    .badge.orange { background: #f39c12; }
    .booking-card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; margin-bottom: 12px; overflow: hidden; }
    .booking-header { padding: 20px 26px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; user-select: none; gap: 16px; }
    .booking-header:hover { background: #fafbfc; }
    .booking-main { display: flex; align-items: center; gap: 18px; flex: 1; min-width: 0; }
    .status-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .booking-name { font-weight: 500; font-size: 15px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; letter-spacing: -.01em; }
    .booking-sub { font-size: 12px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px; }
    .booking-meta { display: flex; align-items: center; gap: 12px; flex-shrink: 0; }
    .package-tag { background: #f0f5ff; color: var(--blue); border-radius: 5px; padding: 3px 10px; font-size: 11px; font-weight: 500; letter-spacing: .02em; }
    .price-tag { font-family: "Cormorant Garamond", serif; font-weight: 400; font-size: 18px; color: var(--text); letter-spacing: -.02em; }
    .date-tag { font-size: 11px; color: var(--muted); letter-spacing: .02em; }
    .chevron { font-size: 10px; color: var(--muted); transition: transform .2s; flex-shrink: 0; }
    .booking-card.open .chevron { transform: rotate(180deg); }
    .booking-body { display: none; border-top: 1px solid var(--border); padding: 26px; }
    .booking-card.open .booking-body { display: block; }
    .details-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px; margin-bottom: 24px; }
    .detail-item label { display: block; font-size: 10px; text-transform: uppercase; letter-spacing: .14em; color: var(--muted); margin-bottom: 5px; font-weight: 500; }
    .detail-item .val { font-size: 14px; color: var(--text); word-break: break-word; font-weight: 400; }
    .detail-item .val a { color: var(--blue); text-decoration: none; }
    .admin-form { background: #f8f9fb; border-radius: 12px; padding: 20px; display: grid; grid-template-columns: 1fr 1fr 2fr; gap: 14px; align-items: end; border: 1px solid var(--border); }
    .form-group label { display: block; font-size: 10px; text-transform: uppercase; letter-spacing: .14em; color: var(--muted); margin-bottom: 7px; font-weight: 500; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; font-family: inherit; background: #fff; outline: none; transition: border-color .2s; color: var(--text); font-weight: 400; }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: rgba(17,17,17,.4); }
    .form-group textarea { resize: vertical; min-height: 70px; }
    .form-actions { display: flex; align-items: center; gap: 10px; margin-top: 14px; }
    .btn-save { padding: 10px 22px; background: var(--dark); color: #fff; border: none; border-radius: 8px; font-size: 12px; font-family: inherit; font-weight: 500; letter-spacing: .06em; cursor: pointer; transition: opacity .2s; text-transform: uppercase; }
    .btn-save:hover { opacity: .82; }
    .btn-delete { padding: 10px 18px; background: transparent; color: var(--red); border: 1px solid var(--red); border-radius: 8px; font-size: 12px; font-family: inherit; font-weight: 500; letter-spacing: .06em; cursor: pointer; text-transform: uppercase; transition: all .2s; }
    .btn-delete:hover { background: var(--red); color: #fff; }
    .empty { background: var(--card); border: 1px dashed var(--border); border-radius: 16px; padding: 48px; text-align: center; color: var(--muted); font-size: 13px; letter-spacing: .02em; }

    /* Search/filter toolbar */
    .filter-bar { display: flex; gap: 12px; align-items: center; margin-bottom: 20px; flex-wrap: wrap; }
    .filter-input { flex: 1; min-width: 200px; padding: 10px 16px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 13px; font-weight: 400; outline: none; color: var(--text); background: var(--card); transition: border-color .2s; }
    .filter-input:focus { border-color: rgba(17,17,17,.4); }
    .filter-status { display: flex; gap: 6px; flex-wrap: wrap; }
    .filter-btn { padding: 8px 14px; border: 1px solid var(--border); border-radius: 6px; font-family: inherit; font-size: 11px; font-weight: 500; letter-spacing: .06em; text-transform: uppercase; cursor: pointer; background: var(--card); color: var(--muted); transition: all .15s; }
    .filter-btn:hover { border-color: #bbb; color: var(--text); }
    .filter-btn.active { background: var(--dark); color: #fff; border-color: var(--dark); }
    .export-btn { padding: 9px 18px; border: 1px solid var(--border); border-radius: 6px; font-family: inherit; font-size: 11px; font-weight: 500; letter-spacing: .08em; text-transform: uppercase; cursor: pointer; background: var(--card); color: var(--muted); transition: all .15s; white-space: nowrap; }
    .export-btn:hover { border-color: #bbb; color: var(--text); }
    .btn-cancel-booking { padding: 10px 18px; background: transparent; color: #c0392b; border: 1px solid #c0392b; border-radius: 8px; font-size: 12px; font-family: inherit; font-weight: 500; letter-spacing: .06em; cursor: pointer; text-transform: uppercase; transition: all .2s; }
    .btn-cancel-booking:hover { background: #c0392b; color: #fff; }

    /* Toast */
    .toast {
      position: fixed; bottom: 28px; left: 50%; transform: translateX(-50%) translateY(12px);
      background: #111; color: #fff; padding: 12px 28px;
      border-radius: 8px; font-size: 12px; font-weight: 500; letter-spacing: .04em;
      opacity: 0; pointer-events: none; z-index: 99999;
      transition: all .3s; white-space: nowrap;
    }
    .toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
    .toast.success { background: #1a4a2e; }
    .toast.error   { background: #5c1010; }

    @media (max-width: 900px) {
      .cal-layout { grid-template-columns: 1fr; }
      .cal-sidebar { min-height: auto; }
    }
    @media (max-width: 700px) {
      .stats { grid-template-columns: 1fr; }
      .admin-form { grid-template-columns: 1fr; }
      .booking-main { flex-direction: column; align-items: flex-start; gap: 4px; }
      .booking-meta { flex-wrap: wrap; }
      .topbar { padding: 0 16px; }
      .container { padding: 20px 16px 48px; }
    }
  </style>
</head>
<body>

<div class="topbar">
  <div class="topbar-brand">
    Easy Help Switzerland <span>Admin</span>
  </div>
  <div class="topbar-actions">
    <a href="/">← Website</a>
    <a href="admin-panel.php?logout=1" class="btn-logout">Sign out</a>
  </div>
</div>

<div class="container">

  <!-- Stats -->
  <div class="stats">
    <div class="stat-card">
      <div class="stat-label">Paid bookings</div>
      <div class="stat-value green"><?= $totalPaid ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Total revenue</div>
      <div class="stat-value blue">CHF <?= number_format($totalRevenue, 0) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Pending (not paid)</div>
      <div class="stat-value"><?= $totalPending ?></div>
    </div>
  </div>

  <!-- Tabs -->
  <div class="tabs">
    <button class="tab-btn active" onclick="switchTab('calendar')">&#128197; Calendar</button>
    <button class="tab-btn" onclick="switchTab('bookings')">&#128203; Bookings</button>
  </div>

  <!-- ── CALENDAR TAB ── -->
  <div id="tab-calendar" class="tab-panel active">
    <div class="cal-layout">

      <!-- Sidebar: unscheduled bookings -->
      <div class="cal-sidebar">
        <div class="sidebar-label">
          Unscheduled
          <span class="count"><?= count($unscheduled) ?></span>
        </div>
        <div id="external-events">
          <?php if (empty($unscheduled)): ?>
            <div class="no-unscheduled">All bookings have a Termin &#10003;</div>
          <?php else: ?>
            <?php foreach ($unscheduled as $u): ?>
            <div class="ext-event <?= $u['type'] === 'pending' ? 'pending-type' : '' ?>"
                 data-id="<?= htmlspecialchars($u['id']) ?>"
                 data-name="<?= htmlspecialchars($u['name']) ?>"
                 data-email="<?= htmlspecialchars($u['email']) ?>"
                 data-package="<?= htmlspecialchars($u['package']) ?>"
                 data-type="<?= htmlspecialchars($u['type']) ?>">
              <div class="ext-name"><?= htmlspecialchars($u['name']) ?></div>
              <div class="ext-sub"><?= htmlspecialchars($u['email']) ?></div>
              <?php if ($u['package']): ?>
              <span class="ext-badge <?= $u['type'] === 'pending' ? 'pending-type' : '' ?>"><?= htmlspecialchars($u['package']) ?></span>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Calendar -->
      <div class="cal-main">
        <div id="calendar"></div>
      </div>

    </div>
  </div>

  <!-- ── BOOKINGS TAB ── -->
  <div id="tab-bookings" class="tab-panel">

    <!-- Filter bar -->
    <div class="filter-bar">
      <input class="filter-input" type="search" id="searchInput" placeholder="Search by name, email or package…" oninput="applyFilters()">
      <div class="filter-status">
        <button class="filter-btn active" onclick="setStatus(this,'all')">All</button>
        <button class="filter-btn" onclick="setStatus(this,'confirmed')">Confirmed</button>
        <button class="filter-btn" onclick="setStatus(this,'completed')">Completed</button>
        <button class="filter-btn" onclick="setStatus(this,'pending')">Pending</button>
        <button class="filter-btn" onclick="setStatus(this,'cancelled')">Cancelled</button>
      </div>
    </div>

    <!-- Paid -->
    <div class="section">
      <div class="section-title" style="justify-content:space-between">
        <span>Paid Bookings <span class="badge"><?= $totalPaid ?></span></span>
        <button class="export-btn" onclick="exportCSV()">↓ Export CSV</button>
      </div>
      <?php if (empty($paid)): ?>
        <div class="empty">No paid bookings yet.</div>
      <?php else: ?>
        <?php foreach ($paid as $b):
          $id     = $b['internal_booking_id'] ?? '';
          $note   = loadNote($id);
          $st     = $note['status'] ?? 'confirmed';
          $dot    = $statusColors[$st] ?? '#aaa';
          $termin = $note['termin'] ?? '';
        ?>
        <div class="booking-card" id="card-<?= htmlspecialchars($id) ?>">
          <div class="booking-header" onclick="toggleCard('<?= htmlspecialchars($id) ?>')">
            <div class="booking-main">
              <div class="status-dot" style="background:<?= $dot ?>" data-status="<?= htmlspecialchars($st) ?>"></div>
              <div>
                <div class="booking-name"><?= htmlspecialchars($b['name'] ?? '—') ?></div>
                <div class="booking-sub"><?= htmlspecialchars($b['email'] ?? '') ?><?= $termin ? ' · &#128197; ' . htmlspecialchars(fmtDate($termin)) : '' ?></div>
              </div>
            </div>
            <div class="booking-meta">
              <span class="package-tag"><?= htmlspecialchars($b['package_name'] ?? $b['package'] ?? '') ?></span>
              <span class="price-tag">CHF <?= htmlspecialchars($b['price_chf'] ?? '0') ?></span>
              <span class="date-tag"><?= fmtDate($b['created_at'] ?? '') ?></span>
            </div>
            <span class="chevron">&#9660;</span>
          </div>
          <div class="booking-body">
            <div class="details-grid">
              <div class="detail-item"><label>Name</label><div class="val"><?= htmlspecialchars($b['name'] ?? '—') ?></div></div>
              <div class="detail-item"><label>Email</label><div class="val"><a href="mailto:<?= htmlspecialchars($b['email'] ?? '') ?>"><?= htmlspecialchars($b['email'] ?? '—') ?></a></div></div>
              <div class="detail-item"><label>Phone</label><div class="val"><?= htmlspecialchars($b['phone'] ?? '—') ?: '—' ?></div></div>
              <div class="detail-item"><label>Location</label><div class="val"><?= htmlspecialchars($b['location'] ?? '—') ?: '—' ?></div></div>
              <div class="detail-item"><label>Format</label><div class="val"><?= htmlspecialchars($b['preferred'] ?? '—') ?: '—' ?></div></div>
              <div class="detail-item"><label>Payment</label><div class="val"><?= htmlspecialchars($b['payment_status'] ?? '—') ?></div></div>
              <div class="detail-item"><label>Paid at</label><div class="val"><?= $b['paid_at'] ? fmtDate($b['paid_at']) : '—' ?></div></div>
              <?php if (!empty($b['message'])): ?>
              <div class="detail-item" style="grid-column:1/-1"><label>Client message</label><div class="val"><?= nl2br(htmlspecialchars($b['message'])) ?></div></div>
              <?php endif; ?>
              <?php if (!empty($note['admin_note'])): ?>
              <div class="detail-item" style="grid-column:1/-1"><label>Your note</label><div class="val"><?= nl2br(htmlspecialchars($note['admin_note'])) ?></div></div>
              <?php endif; ?>
            </div>
            <form method="POST" action="admin-action.php">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="action" value="save_note">
              <input type="hidden" name="booking_id" value="<?= htmlspecialchars($id) ?>">
              <input type="hidden" name="booking_type" value="paid">
              <div class="admin-form">
                <div class="form-group">
                  <label>Termin</label>
                  <input type="datetime-local" name="termin"
                    value="<?= htmlspecialchars($termin ? (new DateTime($termin))->format('Y-m-d\TH:i') : '') ?>">
                </div>
                <div class="form-group">
                  <label>Status</label>
                  <select name="status">
                    <?php foreach ($statusLabels as $val => $lbl): ?>
                      <option value="<?= $val ?>" <?= ($st === $val) ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label>Notes</label>
                  <textarea name="admin_note" placeholder="Add notes..."><?= htmlspecialchars($note['admin_note'] ?? '') ?></textarea>
                </div>
              </div>
              <div class="form-actions">
                <button type="submit" class="btn-save">Save</button>
                <button type="button" class="btn-cancel-booking"
                  onclick="cancelBooking('<?= htmlspecialchars($id) ?>','<?= htmlspecialchars(addslashes($b['name'] ?? '')) ?>')">
                  Cancel &amp; Email
                </button>
                <button type="submit" class="btn-delete"
                  onclick="this.form.querySelector('[name=action]').value='delete'; return confirm('Delete this booking?')">
                  Delete
                </button>
              </div>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Pending -->
    <div class="section">
      <div class="section-title">
        Pending (not paid) <span class="badge orange"><?= $totalPending ?></span>
      </div>
      <?php if (empty($pending)): ?>
        <div class="empty">No pending bookings.</div>
      <?php else: ?>
        <?php foreach ($pending as $b):
          $id   = $b['internal_booking_id'] ?? '';
          $note = loadNote($id);
          $st   = $note['status'] ?? 'pending';
        ?>
        <div class="booking-card" id="card-<?= htmlspecialchars($id) ?>">
          <div class="booking-header" onclick="toggleCard('<?= htmlspecialchars($id) ?>')">
            <div class="booking-main">
              <div class="status-dot" style="background:#f39c12"></div>
              <div>
                <div class="booking-name"><?= htmlspecialchars($b['name'] ?? '—') ?></div>
                <div class="booking-sub"><?= htmlspecialchars($b['email'] ?? '') ?> · Pending payment</div>
              </div>
            </div>
            <div class="booking-meta">
              <span class="package-tag"><?= htmlspecialchars($b['package_name'] ?? $b['package'] ?? '') ?></span>
              <span class="price-tag">CHF <?= htmlspecialchars($b['price_chf'] ?? '0') ?></span>
              <span class="date-tag"><?= fmtDate($b['created_at'] ?? '') ?></span>
            </div>
            <span class="chevron">&#9660;</span>
          </div>
          <div class="booking-body">
            <div class="details-grid">
              <div class="detail-item"><label>Name</label><div class="val"><?= htmlspecialchars($b['name'] ?? '—') ?></div></div>
              <div class="detail-item"><label>Email</label><div class="val"><a href="mailto:<?= htmlspecialchars($b['email'] ?? '') ?>"><?= htmlspecialchars($b['email'] ?? '—') ?></a></div></div>
              <div class="detail-item"><label>Phone</label><div class="val"><?= htmlspecialchars($b['phone'] ?? '—') ?: '—' ?></div></div>
              <div class="detail-item"><label>Package</label><div class="val"><?= htmlspecialchars($b['package_name'] ?? '—') ?></div></div>
              <div class="detail-item"><label>Created</label><div class="val"><?= fmtDate($b['created_at'] ?? '') ?></div></div>
            </div>
            <form method="POST" action="admin-action.php">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="booking_id" value="<?= htmlspecialchars($id) ?>">
              <input type="hidden" name="booking_type" value="pending">
              <div class="form-actions">
                <button type="submit" class="btn-delete" onclick="return confirm('Delete this pending booking?')">Delete</button>
              </div>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div><!-- /tab-bookings -->

</div><!-- /container -->

<!-- Confirm modal -->
<div class="modal-overlay" id="scheduleModal">
  <div class="modal">
    <h3>Confirm appointment</h3>
    <p id="modalText">Schedule this consultation?</p>
    <label class="mail-toggle">
      <input type="checkbox" id="sendMailCheck" checked>
      Send confirmation email to client
    </label>
    <div class="modal-actions">
      <button class="btn-confirm" id="modalConfirm">Confirm &amp; Schedule</button>
      <button class="btn-cancel-modal" id="modalCancel">Cancel</button>
    </div>
  </div>
</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script>
const CSRF = <?= json_encode($csrf) ?>;
const CALENDAR_EVENTS = <?= json_encode($calendarEvents, JSON_UNESCAPED_UNICODE) ?>;

// ── Tab switching ──────────────────────────────────────────────
function switchTab(name) {
  document.querySelectorAll('.tab-btn').forEach((b, i) => {
    b.classList.toggle('active', ['calendar','bookings'][i] === name);
  });
  document.querySelectorAll('.tab-panel').forEach(p => {
    p.classList.toggle('active', p.id === 'tab-' + name);
  });
  if (name === 'calendar') calendar.updateSize();
}

// ── Booking cards ──────────────────────────────────────────────
function toggleCard(id) {
  document.getElementById('card-' + id).classList.toggle('open');
}

// ── Toast ──────────────────────────────────────────────────────
function showToast(msg, type) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = 'toast ' + (type || '');
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 3500);
}

// ── Modal ──────────────────────────────────────────────────────
let pendingDrop = null;
let pendingEl   = null;
let pendingRevert = null;

document.getElementById('modalCancel').addEventListener('click', () => {
  closeModal(true);
});

document.getElementById('modalConfirm').addEventListener('click', () => {
  if (!pendingDrop) return;
  const sendMail = document.getElementById('sendMailCheck').checked;
  closeModal(false);
  scheduleBooking(pendingDrop.id, pendingDrop.datetime, pendingEl, sendMail);
});

function openModal(name, datetimeStr, el, revertFn) {
  const dt = new Date(datetimeStr);
  const formatted = dt.toLocaleDateString('de-CH', { weekday: 'long', day: '2-digit', month: '2-digit', year: 'numeric' })
    + ' at ' + dt.toLocaleTimeString('de-CH', { hour: '2-digit', minute: '2-digit' });
  document.getElementById('modalText').innerHTML =
    'Schedule <span class="highlight">' + name + '</span> for<br><span class="highlight">' + formatted + '</span>?';
  pendingDrop = { id: el.dataset.id, datetime: datetimeStr };
  pendingEl   = el;
  pendingRevert = revertFn;
  document.getElementById('scheduleModal').classList.add('visible');
}

function closeModal(revert) {
  document.getElementById('scheduleModal').classList.remove('visible');
  if (revert && pendingRevert) pendingRevert();
  pendingDrop = pendingEl = pendingRevert = null;
}

// ── Schedule booking via AJAX ──────────────────────────────────
function scheduleBooking(id, datetime, el, sendMail) {
  fetch('schedule-booking.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id, datetime, csrf: CSRF, send_mail: sendMail })
  })
  .then(r => r.json())
  .then(data => {
    if (data.ok) {
      if (el && el.parentNode) el.remove();
      const count = document.querySelector('.sidebar-label .count');
      if (count) count.textContent = parseInt(count.textContent) - 1;
      if (data.email_sent) {
        showToast('Scheduled & confirmation email sent!', 'success');
      } else {
        showToast('Scheduled. ' + (sendMail ? 'Email failed: ' + (data.email_error || '?') : 'No email sent.'), sendMail ? 'error' : 'success');
      }
    } else {
      showToast('Error: ' + (data.error || 'Unknown'), 'error');
    }
  })
  .catch(() => showToast('Network error — please retry.', 'error'));
}

// ── FullCalendar ───────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {

  // Make sidebar items draggable
  new FullCalendar.Draggable(document.getElementById('external-events'), {
    itemSelector: '.ext-event',
    eventData: function (el) {
      return {
        title:    el.dataset.name,
        id:       el.dataset.id,
        duration: '01:00',
        color:    el.dataset.type === 'pending' ? '#f39c12' : '#4693e8',
      };
    }
  });

  calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
    initialView: 'timeGridWeek',
    locale: 'de',
    firstDay: 1,
    slotMinTime: '07:00:00',
    slotMaxTime: '21:00:00',
    slotDuration: '00:30:00',
    headerToolbar: {
      left:   'prev,next today',
      center: 'title',
      right:  'dayGridMonth,timeGridWeek,timeGridDay'
    },
    editable: true,
    droppable: true,
    events: CALENDAR_EVENTS,

    // Drop from sidebar onto calendar
    drop: function (info) {
      const el = info.draggedEl;
      const dt = info.date.toISOString();
      openModal(el.dataset.name, dt, el, null);
    },

    // Move existing event on calendar
    eventDrop: function (info) {
      const id = info.event.id;
      const dt = info.event.start.toISOString();
      if (!confirm('Move appointment to ' + info.event.start.toLocaleString('de-CH') + '?')) {
        info.revert();
        return;
      }
      fetch('schedule-booking.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, datetime: dt, csrf: CSRF, send_mail: false })
      })
      .then(r => r.json())
      .then(d => {
        if (d.ok) showToast('Appointment moved.', 'success');
        else { info.revert(); showToast('Error: ' + d.error, 'error'); }
      });
    },

    // Click event to see details
    eventClick: function (info) {
      const p = info.event.extendedProps;
      const start = info.event.start;
      showToast(info.event.title + ' · ' + start.toLocaleDateString('de-CH') + ' ' + start.toLocaleTimeString('de-CH', { hour: '2-digit', minute: '2-digit' }), '');
    },

    // Styling
    eventDidMount: function (info) {
      info.el.title = info.event.title + '\n' + (info.event.extendedProps.email || '');
    },

    height: 680,
    nowIndicator: true,
    weekNumbers: true,
  });

  calendar.render();
});

// ── Search / filter ────────────────────────────────────────────
let activeStatus = 'all';

function setStatus(btn, status) {
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  activeStatus = status;
  applyFilters();
}

function applyFilters() {
  const q = document.getElementById('searchInput').value.toLowerCase().trim();
  document.querySelectorAll('.booking-card').forEach(card => {
    const text = card.textContent.toLowerCase();
    const statusDot = card.querySelector('.status-dot');
    const cardStatus = statusDot ? statusDot.dataset.status || '' : '';
    const matchesText = !q || text.includes(q);
    const matchesStatus = activeStatus === 'all' || cardStatus === activeStatus;
    card.style.display = (matchesText && matchesStatus) ? '' : 'none';
  });
}

// ── CSV Export ─────────────────────────────────────────────────
function exportCSV() {
  const rows = [['Name','Email','Phone','Package','Price CHF','Status','Termin','Created','Paid at','Location','Format','Message']];
  document.querySelectorAll('.booking-card').forEach(card => {
    if (card.style.display === 'none') return;
    const get = label => {
      const items = card.querySelectorAll('.detail-item');
      for (const item of items) {
        if (item.querySelector('label')?.textContent.trim().toLowerCase() === label.toLowerCase()) {
          return item.querySelector('.val')?.textContent.trim() || '';
        }
      }
      return '';
    };
    const name    = card.querySelector('.booking-name')?.textContent.trim() || '';
    const sub     = card.querySelector('.booking-sub')?.textContent.trim() || '';
    const pkg     = card.querySelector('.package-tag')?.textContent.trim() || '';
    const price   = card.querySelector('.price-tag')?.textContent.replace('CHF','').trim() || '';
    const date    = card.querySelector('.date-tag')?.textContent.trim() || '';
    const status  = card.querySelector('.status-dot')?.dataset.status || '';
    rows.push([name, get('email'), get('phone'), pkg, price, status, get('termin') || sub, date, get('paid at'), get('location'), get('format'), get('client message')]);
  });
  const csv = rows.map(r => r.map(v => '"' + String(v).replace(/"/g,'""') + '"').join(',')).join('\n');
  const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'bookings-' + new Date().toISOString().slice(0,10) + '.csv';
  a.click();
}

// ── Cancel & Email ─────────────────────────────────────────────
function cancelBooking(id, name) {
  if (!confirm('Cancel booking for "' + name + '" and send a cancellation email to the client?')) return;
  fetch('cancel-booking.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id, csrf: CSRF })
  })
  .then(r => r.json())
  .then(d => {
    if (d.ok) {
      showToast('Booking cancelled' + (d.email_sent ? ' & email sent.' : ' (email failed).'), d.email_sent ? 'success' : 'error');
      // Update status dot color
      const card = document.getElementById('card-' + id);
      if (card) {
        const dot = card.querySelector('.status-dot');
        if (dot) { dot.style.background = '#e74c3c'; dot.dataset.status = 'cancelled'; }
      }
    } else {
      showToast('Error: ' + (d.error || 'Unknown'), 'error');
    }
  })
  .catch(() => showToast('Network error', 'error'));
}
</script>
</body>
</html>
