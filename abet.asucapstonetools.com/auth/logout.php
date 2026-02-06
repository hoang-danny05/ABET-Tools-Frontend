<?php
declare(strict_types=1);

require_once '/home/osburn/abet_private/lib/db.php';
require_once '/home/osburn/abet_private/lib/auth.php';

start_session_basic();

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$ip = $_SERVER['REMOTE_ADDR'] ?? null;
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

//Audit log best effort
try {
    if ($userId) {
        db()->prepare(
            'INSERT INTO audit_log (actor_user_id, action, target_type, target_id, metadata, ip_address)
             VALUES (:actor, :action, :target_type, :target_id, :metadata, :ip)'
        )->execute([
            ':actor' => $userId,
            ':action' => 'logout',
            ':target_type' => 'user',
            ':target_id' => (string)$userId,
            ':metadata' => json_encode(
                ['user_agent' => $userAgent],
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            ),
            ':ip' => $ip,
        ]);
    }
} catch (Throwable $e) {
    //do not block logout
}

//Destroy session
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Logged Out | ASU ABET Tools</title>
  <style>
    :root { --asu-maroon:#8C1D40; --success-green:#4CAF50; --gray-bg:#F4F4F4; }
    body { font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif; background:var(--gray-bg); display:flex; align-items:center; justify-content:center; height:100vh; margin:0; }
    .logout-card { background:#fff; padding:3rem 2.5rem; border-radius:8px; box-shadow:0 10px 25px rgba(0,0,0,.08); text-align:center; max-width:400px; width:90%; border-top:5px solid var(--asu-maroon); }
    .asu-logo { height:45px; width:auto; margin-bottom:2rem; display:inline-block; }
    h1 { font-size:1.5rem; color:#333; margin-bottom:.5rem; font-weight:600; }
    p { color:#666; margin-bottom:2rem; }
    .btn-signin { display:inline-block; background-color:var(--asu-maroon); color:#fff; text-decoration:none; padding:.8rem 1.5rem; border-radius:4px; font-weight:700; width:100%; box-sizing:border-box; }
    .btn-signin:hover { background-color:#5c132a; }
    .checkmark-container { width:80px; height:80px; margin:0 auto 1.5rem auto; }
    .checkmark-svg { width:80px; height:80px; display:block; stroke-width:2; stroke:var(--success-green); stroke-miterlimit:10; }
    .checkmark-circle { fill:none; stroke-width:3; stroke-dasharray:166; stroke-dashoffset:166; animation:stroke .6s cubic-bezier(.65,0,.45,1) forwards; }
    .checkmark-check { transform-origin:50% 50%; stroke-dasharray:48; stroke-dashoffset:48; animation:stroke .3s cubic-bezier(.65,0,.45,1) .6s forwards; }
    @keyframes stroke { 100% { stroke-dashoffset:0; } }
    .fade-in-content { opacity:0; animation:fadeIn .8s ease-out .5s forwards; }
    @keyframes fadeIn { from { opacity:0; transform:translateY(10px);} to { opacity:1; transform:translateY(0);} }
  </style>
</head>
<body>
  <div class="logout-card">
    <img src="https://cms.asuonline.asu.edu/sites/g/files/litvpz1971/files/asu-vertical-logo.png" alt="ASU Logo" class="asu-logo">
    <div class="checkmark-container">
      <svg class="checkmark-svg" viewBox="0 0 52 52">
        <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
        <path class="checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
      </svg>
    </div>
    <div class="fade-in-content">
      <h1>You have been logged out</h1>
      <p>Thank you for using ABET Tools.</p>
      <a href="/login" class="btn-signin">Sign back in</a>
    </div>
  </div>
</body>
</html>