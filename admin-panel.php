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

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Generate CSRF for forms
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

// Stats
$totalRevenue  = array_sum(array_map(fn($b) => (float)($b['price_chf'] ?? 0), $paid));
$totalPaid     = count($paid);
$totalPending  = count($pending);

// Format date helper
function fmtDate(string $iso): string {
    try {
        $d = new DateTime($iso);
        return $d->format('d.m.Y H:i');
    } catch (Exception $e) {
        return $iso;
    }
}

$statusColors = [
    'confirmed'  => '#2ecc71',
    'completed'  => '#4693e8',
    'cancelled'  => '#e74c3c',
    'pending'    => '#f39c12',
    ''           => '#aaa',
];
$statusLabels = [
    'confirmed' => 'Confirmed',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
    'pending'   => 'Pending',
];
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
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --dark: #0a0e14;
      --blue: #4693e8;
      --green: #2ecc71;
      --red: #e74c3c;
      --bg: #f4f6f9;
      --card: #fff;
      --border: #e8ecf0;
      --text: #1a1a2e;
      --muted: #888;
    }
    body {
      font-family: "Manrope", system-ui, sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
    }

    /* ── Topbar ── */
    .topbar {
      background: var(--dark);
      color: #fff;
      padding: 0 32px;
      height: 60px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 100;
    }
    .topbar-brand {
      font-family: "Cormorant Garamond", serif;
      font-size: 18px;
      font-weight: 500;
    }
    .topbar-brand span {
      font-size: 11px;
      font-family: "Manrope", sans-serif;
      color: rgba(255,255,255,.5);
      margin-left: 10px;
      letter-spacing: .08em;
      text-transform: uppercase;
    }
    .topbar-actions { display: flex; gap: 16px; align-items: center; }
    .topbar-actions a {
      color: rgba(255,255,255,.6);
      text-decoration: none;
      font-size: 13px;
      transition: color .2s;
    }
    .topbar-actions a:hover { color: #fff; }
    .btn-logout {
      background: rgba(255,255,255,.1);
      color: #fff !important;
      padding: 7px 16px;
      border-radius: 8px;
      transition: background .2s !important;
    }
    .btn-logout:hover { background: rgba(255,255,255,.2) !important; }

    /* ── Layout ── */
    .container { max-width: 1100px; margin: 0 auto; padding: 32px 24px 64px; }

    /* ── Stats ── */
    .stats {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 16px;
      margin-bottom: 36px;
    }
    .stat-card {
      background: var(--card);
      border-radius: 14px;
      padding: 24px;
      border: 1px solid var(--border);
    }
    .stat-label {
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: .08em;
      color: var(--muted);
      margin-bottom: 8px;
    }
    .stat-value {
      font-family: "Cormorant Garamond", serif;
      font-size: 36px;
      font-weight: 500;
      color: var(--text);
      line-height: 1;
    }
    .stat-value.green { color: var(--green); }
    .stat-value.blue  { color: var(--blue); }

    /* ── Section ── */
    .section { margin-bottom: 48px; }
    .section-title {
      font-family: "Cormorant Garamond", serif;
      font-size: 26px;
      font-weight: 500;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .badge {
      font-family: "Manrope", sans-serif;
      font-size: 12px;
      background: var(--dark);
      color: #fff;
      border-radius: 20px;
      padding: 3px 10px;
      font-weight: 600;
    }
    .badge.orange { background: #f39c12; }

    /* ── Booking cards ── */
    .booking-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 14px;
      margin-bottom: 16px;
      overflow: hidden;
    }
    .booking-header {
      padding: 18px 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      cursor: pointer;
      user-select: none;
      gap: 16px;
    }
    .booking-header:hover { background: #fafbfc; }
    .booking-main {
      display: flex;
      align-items: center;
      gap: 20px;
      flex: 1;
      min-width: 0;
    }
    .status-dot {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      flex-shrink: 0;
    }
    .booking-name {
      font-weight: 600;
      font-size: 15px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .booking-sub {
      font-size: 13px;
      color: var(--muted);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .booking-meta {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-shrink: 0;
    }
    .package-tag {
      background: #f0f4ff;
      color: var(--blue);
      border-radius: 6px;
      padding: 4px 10px;
      font-size: 12px;
      font-weight: 600;
    }
    .price-tag {
      font-weight: 700;
      font-size: 15px;
      color: var(--text);
    }
    .date-tag {
      font-size: 12px;
      color: var(--muted);
    }
    .chevron {
      font-size: 12px;
      color: var(--muted);
      transition: transform .2s;
      flex-shrink: 0;
    }
    .booking-card.open .chevron { transform: rotate(180deg); }

    /* ── Booking body ── */
    .booking-body {
      display: none;
      border-top: 1px solid var(--border);
      padding: 24px;
    }
    .booking-card.open .booking-body { display: block; }
    .details-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 16px;
      margin-bottom: 24px;
    }
    .detail-item label {
      display: block;
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: .08em;
      color: var(--muted);
      margin-bottom: 4px;
    }
    .detail-item .val {
      font-size: 14px;
      color: var(--text);
      word-break: break-word;
    }
    .detail-item .val a { color: var(--blue); text-decoration: none; }
    .detail-item .val a:hover { text-decoration: underline; }

    /* ── Termin form ── */
    .admin-form {
      background: #f8f9fb;
      border-radius: 10px;
      padding: 20px;
      display: grid;
      grid-template-columns: 1fr 1fr 2fr;
      gap: 12px;
      align-items: end;
    }
    .form-group label {
      display: block;
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: .08em;
      color: var(--muted);
      margin-bottom: 6px;
      font-weight: 600;
    }
    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 10px 12px;
      border: 1.5px solid var(--border);
      border-radius: 8px;
      font-size: 13px;
      font-family: inherit;
      background: #fff;
      outline: none;
      transition: border-color .2s;
    }
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus { border-color: var(--blue); }
    .form-group textarea { resize: vertical; min-height: 70px; }
    .form-actions {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-top: 4px;
    }
    .btn-save {
      padding: 10px 20px;
      background: var(--dark);
      color: #fff;
      border: none;
      border-radius: 8px;
      font-size: 13px;
      font-family: inherit;
      font-weight: 600;
      cursor: pointer;
      transition: background .2s;
    }
    .btn-save:hover { background: #1a2030; }
    .btn-delete {
      padding: 10px 16px;
      background: transparent;
      color: var(--red);
      border: 1.5px solid var(--red);
      border-radius: 8px;
      font-size: 13px;
      font-family: inherit;
      font-weight: 600;
      cursor: pointer;
      transition: all .2s;
    }
    .btn-delete:hover { background: var(--red); color: #fff; }

    /* ── Termin badge ── */
    .termin-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #eef6ff;
      color: var(--blue);
      border-radius: 6px;
      padding: 4px 10px;
      font-size: 12px;
      font-weight: 600;
    }

    /* ── Empty state ── */
    .empty {
      background: var(--card);
      border: 1px dashed var(--border);
      border-radius: 14px;
      padding: 40px;
      text-align: center;
      color: var(--muted);
      font-size: 14px;
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
    Easy Help Switzerland
    <span>Admin</span>
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

  <!-- Paid Bookings -->
  <div class="section">
    <div class="section-title">
      Paid Bookings
      <span class="badge"><?= $totalPaid ?></span>
    </div>

    <?php if (empty($paid)): ?>
      <div class="empty">No paid bookings yet.</div>
    <?php else: ?>
      <?php foreach ($paid as $b):
        $id   = $b['internal_booking_id'] ?? '';
        $note = loadNote($id);
        $st   = $note['status'] ?? 'confirmed';
        $dot  = $statusColors[$st] ?? '#aaa';
        $termin = $note['termin'] ?? '';
      ?>
      <div class="booking-card" id="card-<?= htmlspecialchars($id) ?>">
        <div class="booking-header" onclick="toggleCard('<?= htmlspecialchars($id) ?>')">
          <div class="booking-main">
            <div class="status-dot" style="background:<?= $dot ?>"></div>
            <div>
              <div class="booking-name"><?= htmlspecialchars($b['name'] ?? '—') ?></div>
              <div class="booking-sub"><?= htmlspecialchars($b['email'] ?? '') ?><?= $termin ? ' · 📅 ' . htmlspecialchars($termin) : '' ?></div>
            </div>
          </div>
          <div class="booking-meta">
            <span class="package-tag"><?= htmlspecialchars($b['package_name'] ?? $b['package'] ?? '') ?></span>
            <span class="price-tag">CHF <?= htmlspecialchars($b['price_chf'] ?? '0') ?></span>
            <span class="date-tag"><?= fmtDate($b['created_at'] ?? '') ?></span>
          </div>
          <span class="chevron">▼</span>
        </div>
        <div class="booking-body">
          <div class="details-grid">
            <div class="detail-item">
              <label>Name</label>
              <div class="val"><?= htmlspecialchars($b['name'] ?? '—') ?></div>
            </div>
            <div class="detail-item">
              <label>Email</label>
              <div class="val"><a href="mailto:<?= htmlspecialchars($b['email'] ?? '') ?>"><?= htmlspecialchars($b['email'] ?? '—') ?></a></div>
            </div>
            <div class="detail-item">
              <label>Phone</label>
              <div class="val"><?= htmlspecialchars($b['phone'] ?? '—') ?: '—' ?></div>
            </div>
            <div class="detail-item">
              <label>Location</label>
              <div class="val"><?= htmlspecialchars($b['location'] ?? '—') ?: '—' ?></div>
            </div>
            <div class="detail-item">
              <label>Format</label>
              <div class="val"><?= htmlspecialchars($b['preferred'] ?? '—') ?: '—' ?></div>
            </div>
            <div class="detail-item">
              <label>Payment</label>
              <div class="val"><?= htmlspecialchars($b['payment_status'] ?? '—') ?></div>
            </div>
            <div class="detail-item">
              <label>Paid at</label>
              <div class="val"><?= $b['paid_at'] ? fmtDate($b['paid_at']) : '—' ?></div>
            </div>
            <?php if (!empty($b['message'])): ?>
            <div class="detail-item" style="grid-column: 1/-1">
              <label>Client message</label>
              <div class="val"><?= nl2br(htmlspecialchars($b['message'])) ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($note['admin_note'])): ?>
            <div class="detail-item" style="grid-column: 1/-1">
              <label>Your note</label>
              <div class="val"><?= nl2br(htmlspecialchars($note['admin_note'])) ?></div>
            </div>
            <?php endif; ?>
          </div>

          <!-- Admin form: Termin + Status + Note -->
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
            <div class="form-actions" style="margin-top:12px">
              <button type="submit" class="btn-save">Save</button>
              <button type="submit" class="btn-delete"
                formaction="admin-action.php"
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

  <!-- Pending Bookings -->
  <div class="section">
    <div class="section-title">
      Pending (not paid)
      <span class="badge orange"><?= $totalPending ?></span>
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
          <span class="chevron">▼</span>
        </div>
        <div class="booking-body">
          <div class="details-grid">
            <div class="detail-item">
              <label>Name</label>
              <div class="val"><?= htmlspecialchars($b['name'] ?? '—') ?></div>
            </div>
            <div class="detail-item">
              <label>Email</label>
              <div class="val"><a href="mailto:<?= htmlspecialchars($b['email'] ?? '') ?>"><?= htmlspecialchars($b['email'] ?? '—') ?></a></div>
            </div>
            <div class="detail-item">
              <label>Phone</label>
              <div class="val"><?= htmlspecialchars($b['phone'] ?? '—') ?: '—' ?></div>
            </div>
            <div class="detail-item">
              <label>Package</label>
              <div class="val"><?= htmlspecialchars($b['package_name'] ?? '—') ?></div>
            </div>
            <div class="detail-item">
              <label>Created</label>
              <div class="val"><?= fmtDate($b['created_at'] ?? '') ?></div>
            </div>
          </div>
          <form method="POST" action="admin-action.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="booking_id" value="<?= htmlspecialchars($id) ?>">
            <input type="hidden" name="booking_type" value="pending">
            <div class="form-actions">
              <button type="submit" class="btn-delete"
                onclick="return confirm('Delete this pending booking?')">
                Delete
              </button>
            </div>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div>

<script>
function toggleCard(id) {
  const card = document.getElementById('card-' + id);
  card.classList.toggle('open');
}
</script>
</body>
</html>
