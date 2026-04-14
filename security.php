<?php
/**
 * security.php — include at the very top of every PHP file, before session_start().
 * Sets secure session cookie flags and HTTP security headers.
 */

// Detect HTTPS (works behind reverse proxies too)
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (($_SERVER['SERVER_PORT'] ?? 80) == 443);

// Secure session cookie flags
ini_set('session.cookie_httponly', '1');            // JS cannot read the cookie
ini_set('session.cookie_secure', $isHttps ? '1' : '0'); // HTTPS-only in production, works on HTTP locally
ini_set('session.cookie_samesite', 'Lax');          // Blocks cross-site request forgery
ini_set('session.use_strict_mode', '1');   // Reject unrecognised session IDs
ini_set('session.gc_maxlifetime', '3600'); // Sessions expire after 1 hour

// Prevent clickjacking
header('X-Frame-Options: DENY');

// Stop browsers guessing MIME types (MIME-sniffing attacks)
header('X-Content-Type-Options: nosniff');

// Enforce HTTPS for 1 year, including sub-domains
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Control referrer information
header('Referrer-Policy: strict-origin-when-cross-origin');

// Content Security Policy
// - Allows inline scripts/styles (required by current pages)
// - Allows Google Fonts
// - Allows images from HTTPS sources (Unsplash backgrounds etc.)
// - Blocks <object>, <embed>, <base> overrides
header(
    "Content-Security-Policy: " .
    "default-src 'self'; " .
    "script-src 'self' 'unsafe-inline'; " .
    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
    "font-src https://fonts.gstatic.com; " .
    "img-src 'self' data: https:; " .
    "connect-src 'self'; " .
    "object-src 'none'; " .
    "base-uri 'self'"
);
