<?php
declare(strict_types=1);

/**
 * Auth/session helpers
 * Path: /home/osburn/abet_private/lib/auth.php
 */

function safe_redirect(string $redirectTo = '/login'): void {
  $redirectTo = trim($redirectTo);
  $redirectTo = str_replace(array("\r", "\n"), '', $redirectTo);

  if ($redirectTo === '') {
    $redirectTo = '/home';
  }

  // Block external/full URLs
  if (preg_match('#^(https?:)?//#i', $redirectTo)) {
    $redirectTo = '/home';
  }

  // Block filesystem-path leakage
  $lower = strtolower($redirectTo);
  if (
    strpos($lower, 'public_html') !== false ||
    strpos($lower, 'home/osburn') !== false ||
    strpos($lower, '/home/osburn') !== false
  ) {
    $redirectTo = '/home';
  }

  // Force absolute site path
  if ($redirectTo[0] !== '/') {
    $redirectTo = '/' . ltrim($redirectTo, '/');
  }

  header('Location: ' . $redirectTo, true, 302);
  exit;
}

function start_session_basic(): void {
  if (session_status() === PHP_SESSION_ACTIVE) return;

  ini_set('session.use_strict_mode', '1');
  ini_set('session.use_only_cookies', '1');
  ini_set('session.use_trans_sid', '0');
  ini_set('session.cookie_httponly', '1');

  $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

  $params = session_get_cookie_params();
  session_set_cookie_params(
    0,
    $params['path'] ?? '/',
    $params['domain'] ?? '',
    $isHttps,
    true
  );

  session_start();
}

function start_session(): void {
  start_session_basic();
  enforce_session_timeouts();
}

function enforce_session_timeouts(): void {
  // Do not call start_session() here (prevents recursion)
  if (session_status() !== PHP_SESSION_ACTIVE) return;

  $now = time();
  $idle = defined('SESSION_IDLE_TIMEOUT') ? (int)SESSION_IDLE_TIMEOUT : (30 * 60);
  $absolute = defined('SESSION_ABSOLUTE_TIMEOUT') ? (int)SESSION_ABSOLUTE_TIMEOUT : (8 * 60 * 60);

  if (!isset($_SESSION['created_at'])) $_SESSION['created_at'] = $now;
  if (!isset($_SESSION['last_activity'])) $_SESSION['last_activity'] = $now;

  // Absolute timeout
  if (($now - (int)$_SESSION['created_at']) > $absolute) {
    logout('/login?reason=timeout');
  }

  // Idle timeout
  if (($now - (int)$_SESSION['last_activity']) > $idle) {
    logout('/login?reason=idle');
  }

  $_SESSION['last_activity'] = $now;
}

function is_logged_in(): bool {
  start_session();
  return !empty($_SESSION['user_id']);
}

function require_login(string $redirectTo = '/login'): void {
  start_session();

  if (empty($_SESSION['user_id'])) {
    safe_redirect($redirectTo);
  }
}

function require_role(string $role): void {
  require_login();

  $current = (string)($_SESSION['user_role'] ?? '');
  if ($current !== $role) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
  }
}

function logout(string $redirectTo = '/login'): void {
  start_session();

  $_SESSION = array();

  if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
      session_name(),
      '',
      time() - 42000,
      $params['path'] ?? '/',
      $params['domain'] ?? '',
      (bool)($params['secure'] ?? false),
      (bool)($params['httponly'] ?? true)
    );
  }

  session_destroy();

  safe_redirect($redirectTo);
}