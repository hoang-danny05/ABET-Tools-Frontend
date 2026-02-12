<?php
declare(strict_types=1);
require_once __DIR__ . '/../_guard.php';

require_once $_ENV['ABET_PRIVATE_DIR'] . '/lib/auth.php';
require_once $_ENV['ABET_PRIVATE_DIR'] . '/lib/csrf.php';
require_once $_ENV['ABET_PRIVATE_DIR'] . '/lib/validators.php';
require_once $_ENV['ABET_PRIVATE_DIR'] . '/lib/account_profile_service.php';
require_once $_ENV['ABET_PRIVATE_DIR'] . '/lib/db.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method Not Allowed');
}

$token = (string)($_POST['csrf_token'] ?? '');
if (!csrf_validate($token, 'profile_update')) {
    safe_redirect('/account/profile/?error=csrf');
}

$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    safe_redirect('/login');
}

[$data, $errors] = validate_profile_input($_POST);
if (!empty($errors)) {
    safe_redirect('/account/profile/?error=validation');
}

$ok = profile_save_for_user($userId, $data);

try {
    db()->prepare(
        'INSERT INTO audit_log (actor_user_id, action, target_type, target_id, metadata, ip_address)
         VALUES (:actor, :action, :target_type, :target_id, :metadata, :ip)'
    )->execute([
        ':actor' => $userId,
        ':action' => $ok ? 'profile_update' : 'profile_update_failed',
        ':target_type' => 'user_profile',
        ':target_id' => (string)$userId,
        ':metadata' => json_encode(['fields' => array_keys($data)], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
} catch (Throwable $e) {
    // don't block user flow on logging failure
}

if (!$ok) {
    safe_redirect('/account/profile/?error=save');
}

safe_redirect('/account/profile/?saved=1');