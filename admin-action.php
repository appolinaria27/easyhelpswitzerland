<?php
require_once __DIR__ . '/security.php';
session_start();

if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    header('Location: admin.php');
    exit;
}

// Validate session integrity — detect session hijacking
if (empty($_SESSION['admin_ip']) || $_SESSION['admin_ip'] !== ($_SERVER['REMOTE_ADDR'] ?? '')) {
    session_destroy();
    header('Location: admin.php?error=session_invalid');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin-panel.php');
    exit;
}

// CSRF
if (empty($_POST['csrf_token']) || empty($_SESSION['admin_csrf']) ||
    !hash_equals($_SESSION['admin_csrf'], $_POST['csrf_token'])) {
    header('Location: admin-panel.php?error=csrf');
    exit;
}

$action  = $_POST['action'] ?? '';
$id      = preg_replace('/[^a-f0-9]/', '', $_POST['booking_id'] ?? '');

// Whitelist booking_type — never trust user input directly
$type = $_POST['booking_type'] ?? 'paid';
if (!in_array($type, ['paid', 'pending', 'free'], true)) {
    header('Location: admin-panel.php?error=invalid_type');
    exit;
}
$dataDir = __DIR__ . '/admin-data';

if (!is_dir($dataDir)) mkdir($dataDir, 0700, true);

$noteFile = $dataDir . '/' . $id . '.json';

function loadNote(string $file): array {
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?? [];
}

function saveNote(string $file, array $data): void {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    chmod($file, 0600);
}

function auditLog(string $action, string $id, string $type): void {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) mkdir($logDir, 0700, true);
    $entry = json_encode([
        'time'   => date('c'),
        'ip'     => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'action' => $action,
        'id'     => $id,
        'type'   => $type,
    ]) . "\n";
    file_put_contents($logDir . '/admin-actions.log', $entry, FILE_APPEND | LOCK_EX);
}

if ($action === 'save_note') {
    $note = loadNote($noteFile);
    $termin     = trim($_POST['termin'] ?? '');
    $status     = trim($_POST['status'] ?? '');
    $admin_note = trim($_POST['admin_note'] ?? '');

    // Sanitize
    if (mb_strlen($admin_note) > 1000) $admin_note = mb_substr($admin_note, 0, 1000);
    $allowed_statuses = ['confirmed', 'completed', 'cancelled', 'pending'];
    if (!in_array($status, $allowed_statuses, true)) $status = 'confirmed';

    $note['termin']     = $termin;
    $note['status']     = $status;
    $note['admin_note'] = $admin_note;
    $note['updated_at'] = date('c');
    saveNote($noteFile, $note);
    auditLog('save_note', $id, $type);

} elseif ($action === 'delete') {
    // Delete the booking JSON file
    if ($type === 'paid') {
        $bookingDir = __DIR__ . '/bookings';
        // Find file with this internal_booking_id
        foreach (glob($bookingDir . '/booking-*.json') as $f) {
            $data = json_decode(file_get_contents($f), true);
            if (($data['internal_booking_id'] ?? '') === $id) {
                unlink($f);
                break;
            }
        }
    } elseif ($type === 'pending') {
        $pendingDir = __DIR__ . '/pending-bookings';
        $f = $pendingDir . '/booking-' . $id . '.json';
        if (file_exists($f)) unlink($f);
    } elseif ($type === 'free') {
        $consultDir = __DIR__ . '/free-consultations';
        $f = $consultDir . '/consult-' . $id . '.json';
        if (file_exists($f)) unlink($f);
    }
    // Also delete admin note
    if (file_exists($noteFile)) unlink($noteFile);
    auditLog('delete', $id, $type);
}

header('Location: admin-panel.php');
exit;
