<?php
require_once __DIR__ . '/security.php';
session_start();

$dotenv = __DIR__ . '/.env';
if (file_exists($dotenv)) {
    foreach (file($dotenv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v);
    }
}

// Already logged in
if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: admin-panel.php');
    exit;
}

$error = '';
$rateLimitDir = __DIR__ . '/rate-limit';
if (!is_dir($rateLimitDir)) mkdir($rateLimitDir, 0700, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF
    if (empty($_POST['csrf_token']) || empty($_SESSION['admin_csrf']) ||
        !hash_equals($_SESSION['admin_csrf'], $_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        // Rate limit: 3 attempts per 30 min per IP
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $rlKey = hash('sha256', 'admin_login:' . $ip);
        $rlFile = $rateLimitDir . '/' . $rlKey . '.json';
        $now = time();
        $window = 30 * 60;
        $attempts = [];
        if (file_exists($rlFile)) {
            $decoded = json_decode(file_get_contents($rlFile), true);
            $attempts = is_array($decoded) ? $decoded : [];
            $attempts = array_filter($attempts, fn($t) => is_int($t) && ($now - $t) < $window);
        }
        if (count($attempts) >= 3) {
            $error = 'Too many attempts. Please wait 30 minutes.';
        } else {
            $attempts[] = $now;
            file_put_contents($rlFile, json_encode(array_values($attempts)));

            $password = $_POST['password'] ?? '';
            $hash = $_ENV['ADMIN_PASSWORD_HASH'] ?? '';
            if ($hash && password_verify($password, $hash)) {
                session_regenerate_id(true);
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_ip'] = $ip;
                // Clear rate limit on success
                @unlink($rlFile);
                header('Location: admin-panel.php');
                exit;
            } else {
                $error = 'Wrong password.';
            }
        }
    }
}

// Generate CSRF token
$_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['admin_csrf'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Admin — Easy Help Switzerland</title>
  <meta name="robots" content="noindex,nofollow"/>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Manrope:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: "Manrope", system-ui, sans-serif;
      background: #0a0e14;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
    }
    .card {
      background: #fff;
      border-radius: 16px;
      padding: 48px 40px;
      width: 100%;
      max-width: 400px;
      box-shadow: 0 24px 64px rgba(0,0,0,.4);
    }
    .logo {
      font-family: "Cormorant Garamond", serif;
      font-size: 22px;
      font-weight: 500;
      color: #0a0e14;
      margin-bottom: 8px;
    }
    .subtitle {
      font-size: 13px;
      color: #888;
      margin-bottom: 36px;
    }
    label {
      display: block;
      font-size: 13px;
      font-weight: 600;
      color: #333;
      margin-bottom: 6px;
      letter-spacing: .03em;
      text-transform: uppercase;
    }
    input[type="password"] {
      width: 100%;
      padding: 14px 16px;
      border: 1.5px solid #e0e0e0;
      border-radius: 10px;
      font-size: 15px;
      font-family: inherit;
      outline: none;
      transition: border-color .2s;
    }
    input[type="password"]:focus { border-color: #4693e8; }
    .error {
      background: #fff0f0;
      color: #c0392b;
      border-radius: 8px;
      padding: 10px 14px;
      font-size: 13px;
      margin-bottom: 20px;
    }
    button {
      width: 100%;
      margin-top: 20px;
      padding: 15px;
      background: #0a0e14;
      color: #fff;
      border: none;
      border-radius: 10px;
      font-size: 15px;
      font-family: inherit;
      font-weight: 600;
      cursor: pointer;
      transition: background .2s;
    }
    button:hover { background: #1a2030; }
    .back {
      display: block;
      text-align: center;
      margin-top: 20px;
      font-size: 13px;
      color: #aaa;
      text-decoration: none;
    }
    .back:hover { color: #555; }
  </style>
</head>
<body>
<div class="card">
  <div class="logo">Easy Help Switzerland</div>
  <div class="subtitle">Admin panel — restricted access</div>
  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="POST" action="admin.php" autocomplete="off">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <label for="password">Password</label>
    <input type="password" id="password" name="password" autofocus required>
    <button type="submit">Sign in</button>
  </form>
  <a href="/" class="back">← Back to website</a>
</div>
</body>
</html>
