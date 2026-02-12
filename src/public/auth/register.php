<?php
require_once $_ENV['ABET_PRIVATE_DIR'] . '/config/config.php';
require_once $_ENV['ABET_PRIVATE_DIR'] . '/lib/db.php';
require_once $_ENV['ABET_PRIVATE_DIR'] . '/lib/auth.php';

$errors = [];
$success = false;

/**
 * Password policy:
 * - at least 10 chars
 * - at least 1 number
 * - at least 1 lowercase
 * - at least 1 uppercase
 * - at least 1 special (non-alphanumeric)
 */
function password_policy_check(string $password): array {
  $issues = [];

  if (strlen($password) < 10) {
    $issues[] = 'at least 10 characters';
  }
  if (!preg_match('/[0-9]/', $password)) {
    $issues[] = 'at least 1 number';
  }
  if (!preg_match('/[a-z]/', $password)) {
    $issues[] = 'at least 1 lowercase letter';
  }
  if (!preg_match('/[A-Z]/', $password)) {
    $issues[] = 'at least 1 uppercase letter';
  }
  if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
    $issues[] = 'at least 1 special character';
  }

  return [
    'ok' => count($issues) === 0,
    'issues' => $issues
  ];
}

$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = strtolower(trim($_POST['email'] ?? ''));
  $password = (string)($_POST['password'] ?? '');
  $confirm = (string)($_POST['confirm_password'] ?? '');

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
  }

  // Optional: restrict to ASU emails
  // if (!str_ends_with($email, '@asu.edu')) {
  //   $errors[] = 'Use your ASU email address.';
  // }

  $policy = password_policy_check($password);
  if (!$policy['ok']) {
    $errors[] = 'Password is too weak. It must include: ' . implode(', ', $policy['issues']) . '.';
  }

  if ($password !== $confirm) {
    $errors[] = 'Passwords do not match.';
  }

  if (!$errors) {
    $pdo = db();

    // Check if email exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
      $errors[] = 'An account with that email already exists.';
    } else {
      $hash = password_hash($password, PASSWORD_DEFAULT);

      // Default role is faculty, active by default
      $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role, is_active) VALUES (?, ?, 'faculty', 1)");
      $stmt->execute([$email, $hash]);

      $success = true;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ASU ABET Tools | Create Account</title>
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
      --success-green: #2e7d32;
      --error-red: #d32f2f;
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

    .register-container {
      display: flex;
      width: 100%;
      max-width: 1000px;
      min-height: 650px;
      background: var(--asu-white);
      border-radius: 12px;
      box-shadow: 0 15px 35px rgba(0,0,0,0.15);
      overflow: hidden;
      margin: 20px;
    }

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

    .brand-section::before {
      content: "";
      position: absolute;
      top: -50px;
      left: -50px;
      width: 300px;
      height: 300px;
      background: rgba(255, 198, 39, 0.1);
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

    .form-section {
      flex: 1;
      padding: 50px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      position: relative;
    }

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

    .form-header { margin-bottom: 25px; }
    .form-header h1 { font-size: 1.8rem; color: var(--text-dark); margin-bottom: 8px; font-weight: 700; }
    .form-header p { color: var(--text-light); font-size: 0.95rem; }

    .msg {
      padding: 12px 16px;
      border-radius: 4px;
      margin-bottom: 20px;
      font-size: 0.9rem;
      line-height: 1.5;
    }
    .msg.error {
      background: #fdedf0;
      border-left: 4px solid var(--asu-maroon);
      color: #8C1D40;
    }
    .msg.success {
      background: #e7f4e4;
      border-left: 4px solid #1f7a1f;
      color: #1f7a1f;
    }

    .form-group { margin-bottom: 18px; }

    label {
      display: block;
      color: var(--text-dark);
      font-weight: 600;
      margin-bottom: 6px;
      font-size: 0.9rem;
    }

    .password-wrapper {
      position: relative;
      width: 100%;
    }

    input[type="email"], input[type="password"], input[type="text"] {
      width: 100%;
      padding: 12px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 1rem;
      transition: all 0.3s ease;
      background: #fafafa;
    }

    input[type="email"]:focus, input[type="password"]:focus, input[type="text"]:focus {
      outline: none;
      border-color: var(--asu-maroon);
      background: var(--asu-white);
      box-shadow: 0 0 0 3px rgba(140, 29, 64, 0.1);
    }

    .toggle-password {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: #999;
      background: none;
      border: none;
      padding: 0;
    }
    .toggle-password:hover { color: var(--asu-maroon); }

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

    .strength-meter-container {
      margin-top: 5px;
      margin-bottom: 10px;
    }

    .strength-bar {
      height: 4px;
      width: 100%;
      background-color: #e0e0e0;
      border-radius: 2px;
      margin-bottom: 12px;
      overflow: hidden;
    }

    .strength-fill {
      height: 100%;
      width: 0%;
      background-color: #e0e0e0;
      transition: width 0.3s ease, background-color 0.3s ease;
    }

    .strength-title {
      font-size: 1rem;
      font-weight: 600;
      color: var(--text-dark);
      margin-bottom: 8px;
    }

    .strength-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .strength-list li {
      display: flex;
      align-items: center;
      font-size: 0.95rem;
      color: #888;
      margin-bottom: 6px;
    }

    .strength-list li.valid {
      color: var(--success-green);
    }

    .strength-list li .icon {
      margin-right: 10px;
      font-size: 1.1rem;
      width: 20px;
      text-align: center;
    }

    .strength-list li .icon::before {
      content: "✕";
    }

    .strength-list li.valid .icon::before {
      content: "✓";
    }

    @media (max-width: 768px) {
      .register-container { flex-direction: column; height: auto; }
      .brand-section { padding: 40px 30px; }
      .brand-section::before, .brand-section::after { display: none; }
      .form-section { padding: 40px 30px; }
    }
  </style>
</head>
<body>

  <div class="register-container">

    <div class="brand-section">
      <div class="brand-content">
        <h2>Join the Community</h2>
        <p>Create your account to start managing ABET accreditation data and tools.</p>
        <div style="width: 60px; height: 4px; background: var(--asu-gold); margin-top: 20px;"></div>
      </div>
    </div>

    <div class="form-section">
      <a href="#" class="help-link">Need Help?</a>

      <div class="form-header">
        <h1>Create Account</h1>
        <p>Please fill in your details below.</p>
      </div>

      <?php if ($success): ?>
        <div class="msg success">
          <strong>Success!</strong> Account created. You can now sign in.
        </div>
        <a href="/login" class="btn-submit" style="display:block; text-align:center; text-decoration:none;">Go to Sign In</a>
      <?php else: ?>

        <?php if ($errors): ?>
          <div class="msg error">
            <?php foreach ($errors as $e): ?>
              <?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?><br>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form id="registerForm" method="post" autocomplete="off" novalidate>
          <div class="form-group">
            <label for="email">Email Address</label>
            <input
              id="email"
              name="email"
              type="email"
              placeholder="asurite@asu.edu"
              required
              value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
            />
          </div>

          <div class="form-group">
            <label for="password">Password</label>
            <div class="password-wrapper">
              <input id="password" name="password" type="password" placeholder="At least 10 characters" required />
              <button type="button" class="toggle-password" onclick="togglePasswordVisibility()" aria-label="Show or hide password">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </button>
            </div>
          </div>

          <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input id="confirm_password" name="confirm_password" type="password" placeholder="Re-enter password" required />
          </div>

          <div class="strength-meter-container" id="strengthContainer" style="display:none;">
            <div class="strength-bar">
              <div class="strength-fill" id="strengthFill"></div>
            </div>

            <div class="strength-title" id="strengthTitle">Weak password. Must contain:</div>

            <ul class="strength-list">
              <li id="req-length"><span class="icon"></span> At least 10 characters</li>
              <li id="req-number"><span class="icon"></span> At least 1 number</li>
              <li id="req-lower"><span class="icon"></span> At least 1 lowercase letter</li>
              <li id="req-upper"><span class="icon"></span> At least 1 uppercase letter</li>
              <li id="req-special"><span class="icon"></span> At least 1 special character</li>
            </ul>
          </div>

          <button id="submitBtn" class="btn-submit" type="submit">Create Account</button>

          <div class="footer-links">
            <span>Already have an account?</span>
            <a href="/login">Sign In</a>
          </div>
        </form>

      <?php endif; ?>
    </div>

  </div>

<script>
  function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password');
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);
  }

  document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerForm');
    const passwordInput = document.getElementById('password');
    const container = document.getElementById('strengthContainer');
    const fill = document.getElementById('strengthFill');
    const title = document.getElementById('strengthTitle');

    if (!form || !passwordInput) return;

    const reqs = {
      length: document.getElementById('req-length'),
      number: document.getElementById('req-number'),
      lower: document.getElementById('req-lower'),
      upper: document.getElementById('req-upper'),
      special: document.getElementById('req-special')
    };

    function evaluatePassword(val) {
      const checks = {
        length: val.length >= 10,
        number: /[0-9]/.test(val),
        lower: /[a-z]/.test(val),
        upper: /[A-Z]/.test(val),
        special: /[^A-Za-z0-9]/.test(val)
      };

      let passedCount = 0;
      for (const [key, element] of Object.entries(reqs)) {
        if (checks[key]) {
          element.classList.add('valid');
          passedCount++;
        } else {
          element.classList.remove('valid');
        }
      }

      const strengthPercent = (passedCount / 5) * 100;
      fill.style.width = strengthPercent + '%';

      if (passedCount <= 2) {
        fill.style.backgroundColor = '#d32f2f';
        title.textContent = 'Weak password. Must contain:';
      } else if (passedCount < 5) {
        fill.style.backgroundColor = '#FFC627';
        title.textContent = 'Medium password. Must contain:';
      } else {
        fill.style.backgroundColor = '#2e7d32';
        title.textContent = 'Strong password.';
      }

      return passedCount === 5;
    }

    passwordInput.addEventListener('input', function() {
      const val = passwordInput.value;
      if (val.length > 0) {
        container.style.display = 'block';
      } else {
        container.style.display = 'none';
      }
      evaluatePassword(val);
    });

    form.addEventListener('submit', function(e) {
      const isStrong = evaluatePassword(passwordInput.value);
      if (!isStrong) {
        e.preventDefault();
        container.style.display = 'block';
        passwordInput.focus();
      }
    });
  });
</script>

</body>
</html>