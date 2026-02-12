<?php
declare(strict_types=1);

require_once $_ENV['ABET_PRIVATE_DIR'] . '/lib/db.php';
require_once $_ENV['ABET_PRIVATE_DIR'] . '/lib/auth.php';

start_session();

function e(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function client_ip(): ?string {
  // Keep simple/trusted source. If behind proxy later, handle X-Forwarded-For safely.
  return $_SERVER['REMOTE_ADDR'] ?? null;
}

function user_agent(): ?string {
  return $_SERVER['HTTP_USER_AGENT'] ?? null;
}

function log_login_event(?int $userId, ?string $emailAttempted, string $result, ?string $reason = null): void {
  try {
    db()->prepare(
      'INSERT INTO login_events (user_id, email_attempted, result, reason, ip_address, user_agent)
       VALUES (:user_id, :email_attempted, :result, :reason, :ip, :ua)'
    )->execute([
      ':user_id' => $userId,
      ':email_attempted' => $emailAttempted,
      ':result' => $result, // success | failed_password | failed_mfa | locked
      ':reason' => $reason,
      ':ip' => client_ip(),
      ':ua' => user_agent(),
    ]);
  } catch (Throwable $e) {
    // Fail-open: never break login flow if logging table/insert fails.
  }
}

function log_audit(?int $actorUserId, string $action, ?string $targetType = null, ?string $targetId = null, ?array $metadata = null): void {
  try {
    db()->prepare(
      'INSERT INTO audit_log (actor_user_id, action, target_type, target_id, metadata, ip_address)
       VALUES (:actor, :action, :target_type, :target_id, :metadata, :ip)'
    )->execute([
      ':actor' => $actorUserId,
      ':action' => $action,
      ':target_type' => $targetType,
      ':target_id' => $targetId,
      ':metadata' => $metadata ? json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
      ':ip' => client_ip(),
    ]);
  } catch (Throwable $e) {
    // Fail-open
  }
}

$error = '';

if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $csrf = $_POST['csrf'] ?? '';
  if (!hash_equals($_SESSION['csrf'], $csrf)) {
    $error = 'Invalid session. Please refresh and try again.';
    log_login_event(null, $_POST['email'] ?? null, 'failed_password', 'csrf_invalid');
  } else {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $error = 'Please enter a valid email.';
      log_login_event(null, $email, 'failed_password', 'invalid_email_format');
    } else {
      $stmt = db()->prepare('SELECT id, email, password_hash, role, is_active FROM users WHERE email = :email LIMIT 1');
      $stmt->execute([':email' => $email]);
      $user = $stmt->fetch();

      if (!$user || (int)$user['is_active'] !== 1 || !password_verify($password, (string)$user['password_hash'])) {
        $error = 'Invalid email or password.';
        log_login_event(isset($user['id']) ? (int)$user['id'] : null, $email, 'failed_password', 'bad_credentials_or_inactive');
      } else {
        // ---- Future Duo hook point ----
        // If you add Duo challenge later, place it HERE before finalizing session.
        // Example:
        // if (duo_required_for_user((int)$user['id'])) { redirect to duo challenge; exit; }

        session_regenerate_id(true);

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_email'] = (string)$user['email'];
        $_SESSION['user_role'] = (string)$user['role'];
        $_SESSION['created_at'] = time();
        $_SESSION['last_activity'] = time();

        db()->prepare('UPDATE users SET last_login = NOW() WHERE id = :id')->execute([':id' => (int)$user['id']]);

        log_login_event((int)$user['id'], (string)$user['email'], 'success', null);
        log_audit((int)$user['id'], 'login_success', 'user', (string)$user['id'], [
          'role' => (string)$user['role']
        ]);

        header('Location: /home');
        exit;
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ASU ABET Tools | Login</title>
  <style>
    :root {
      --asu-maroon: #8C1D40;
      --asu-dark-maroon: #5c132a;
      --asu-gold: #FFC627;
      --asu-black: #000000;
      --asu-white: #FFFFFF;
      --gray-bg: #F5F5F5;
      --text-dark: #222222;
      --text-light: #666666;
    }

    * { margin:0; padding:0; box-sizing:border-box; }

    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      background: var(--gray-bg);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    /* --- Main Container (Split Layout) --- */
    .login-container {
      display: flex;
      width: 100%;
      max-width: 1000px;
      height: 600px;
      background: var(--asu-white);
      border-radius: 12px;
      box-shadow: 0 15px 35px rgba(0,0,0,0.15);
      overflow: hidden;
      margin: 20px;
    }

    /* --- Left Side: Visual Branding --- */
    .brand-section {
      flex: 1;
      background: linear-gradient(135deg, var(--asu-maroon), var(--asu-dark-maroon));
      position: relative;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 40px;
      color: var(--asu-white);
      overflow: hidden;
    }

    /* Abstract Circle Effect */
    .brand-section::before {
      content: "";
      position: absolute;
      top: -50px;
      left: -50px;
      width: 300px;
      height: 300px;
      background: rgba(255, 198, 39, 0.1); /* Gold tint */
      border-radius: 50%;
      z-index: 1;
    }
    
    .brand-section::after {
      content: "";
      position: absolute;
      bottom: -80px;
      right: -80px;
      width: 400px;
      height: 400px;
      background: rgba(0, 0, 0, 0.1);
      border-radius: 50%;
      z-index: 1;
    }

    .brand-content {
      position: relative;
      z-index: 2;
    }

    .brand-content h2 {
      font-size: 2.5rem;
      font-weight: 800;
      margin-bottom: 1rem;
      line-height: 1.2;
    }

    .brand-content p {
      font-size: 1.1rem;
      opacity: 0.9;
      line-height: 1.6;
    }

    /* --- Right Side: Login Form --- */
    .form-section {
      flex: 1;
      padding: 50px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      position: relative;
    }

    /* Help Link (Top Right) */
    .help-link {
      position: absolute;
      top: 25px;
      right: 30px;
      font-size: 0.9rem;
      color: var(--text-light);
      text-decoration: none;
      transition: color 0.2s;
    }
    .help-link:hover { color: var(--asu-maroon); }

    .form-header { margin-bottom: 30px; }
    .form-header h1 { font-size: 1.8rem; color: var(--text-dark); margin-bottom: 8px; font-weight: 700; }
    .form-header p { color: var(--text-light); font-size: 0.95rem; }

    .error-box {
      background: #fdedf0;
      border-left: 4px solid var(--asu-maroon);
      color: #8C1D40;
      padding: 12px 16px;
      border-radius: 4px;
      margin-bottom: 25px;
      font-size: 0.9rem;
    }

    .form-group { margin-bottom: 20px; }
    
    label {
      display: block;
      color: var(--text-dark);
      font-weight: 600;
      margin-bottom: 8px;
      font-size: 0.9rem;
    }

    input[type="email"], input[type="password"] {
      width: 100%;
      padding: 14px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 1rem;
      transition: all 0.3s ease;
      background: #fafafa;
    }

    input[type="email"]:focus, input[type="password"]:focus {
      outline: none;
      border-color: var(--asu-maroon);
      background: var(--asu-white);
      box-shadow: 0 0 0 3px rgba(140, 29, 64, 0.1);
    }

    .btn-submit {
      width: 100%;
      padding: 14px;
      background-color: var(--asu-maroon);
      color: var(--asu-white);
      border: none;
      border-radius: 6px;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.3s ease;
      margin-top: 10px;
    }

    .btn-submit:hover {
      background-color: var(--asu-dark-maroon);
      transform: translateY(-1px);
    }

    .footer-links {
      margin-top: 25px;
      text-align: center;
      font-size: 0.9rem;
      color: var(--text-light);
    }

    .footer-links a {
      color: var(--asu-maroon);
      text-decoration: none;
      font-weight: 600;
      margin-left: 5px;
    }
    
    .footer-links a:hover {
      text-decoration: underline;
      color: var(--asu-dark-maroon);
    }

    /* Mobile Responsiveness */
    @media (max-width: 768px) {
      .login-container { flex-direction: column; height: auto; }
      .brand-section { padding: 40px 30px; }
      .brand-section::before, .brand-section::after { display: none; } /* Simplified for mobile */
      .form-section { padding: 40px 30px; }
    }
  </style>
</head>
<body>

  <div class="login-container">
    
    <!-- Left Side: Branding -->
    <div class="brand-section">
      <div class="brand-content">
        <h2>Arizona State University</h2>
        <p>Enterprise Technology & ABET Accreditation Tools.</p>
        <div style="width: 60px; height: 4px; background: var(--asu-gold); margin-top: 20px;"></div>
      </div>
    </div>

    <!-- Right Side: Login Form -->
    <div class="form-section">
      <!-- Helper Link Placeholder -->
      <a href="https://www.asu.edu/about/contact" class="help-link">Need Help?</a>

      <div class="form-header">
        <h1>Welcome Back</h1>
        <p>Please sign in to access your dashboard.</p>
      </div>

      <?php if ($error): ?>
        <div class="error-box">
          <strong>Error:</strong> <?php echo e($error); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="/login" autocomplete="on">
        <input type="hidden" name="csrf" value="<?php echo e($_SESSION['csrf']); ?>">

        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" placeholder="asurite@asu.edu" required />
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="••••••••" required />
        </div>

        <button type="submit" class="btn-submit">Sign In</button>

        <div class="footer-links">
          <span>Don't have an account?</span>
          <a href="/auth/register.php">Create Account</a>
        </div>
      </form>
    </div>

  </div>

</body>
</html>