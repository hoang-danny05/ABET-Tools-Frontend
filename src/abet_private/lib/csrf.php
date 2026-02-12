<?php
declare(strict_types=1);

require_once $_ENV['ABET_PRIVATE_DIR'] . '/lib/auth.php';

function csrf_token(string $form = 'default'): string {
    start_session_basic();

    if (!isset($_SESSION['_csrf']) || !is_array($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = [];
    }

    $now = time();
    $ttl = 2 * 60 * 60; // 2 hours

    $entry = $_SESSION['_csrf'][$form] ?? null;
    if (is_array($entry) && isset($entry['token'], $entry['ts'])) {
        if (($now - (int)$entry['ts']) < $ttl) {
            return (string)$entry['token'];
        }
    }

    $token = bin2hex(random_bytes(32));
    $_SESSION['_csrf'][$form] = ['token' => $token, 'ts' => $now];
    return $token;
}

function csrf_validate(string $token, string $form = 'default'): bool {
    start_session_basic();

    $entry = $_SESSION['_csrf'][$form] ?? null;
    if (!is_array($entry) || empty($entry['token']) || empty($entry['ts'])) {
        return false;
    }

    $now = time();
    $ttl = 2 * 60 * 60;
    if (($now - (int)$entry['ts']) > $ttl) {
        unset($_SESSION['_csrf'][$form]);
        return false;
    }

    $ok = hash_equals((string)$entry['token'], $token);

    // one-time token (rotate after use)
    unset($_SESSION['_csrf'][$form]);

    return $ok;
}