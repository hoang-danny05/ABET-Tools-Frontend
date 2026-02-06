<?php
declare(strict_types=1);
require_once __DIR__ . '/../_guard.php';

require_once '/home/osburn/abet_private/lib/csrf.php';
require_once '/home/osburn/abet_private/lib/account_profile_service.php';

$userId = (int)($_SESSION['user_id'] ?? 0);
$profileRaw = profile_get_for_user($userId);
$profile = is_array($profileRaw) ? $profileRaw : [];

// Safe defaults to avoid undefined index notices
$profile = array_merge([
  'display_name'    => '',
  'department'      => '',
  'phone'           => '',
  'office_location' => '',
  'bio'             => '',
], $profile);

$token = csrf_token('profile_update');

$saved = isset($_GET['saved']) && $_GET['saved'] === '1';
$error = isset($_GET['error']) ? (string)$_GET['error'] : '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Edit Profile | ABET Tools</title>
  <style>
    :root {
      --asu-maroon: #8C1D40;
      --asu-gold: #FFC627;
      --bg-color: #F9F9F9;
      --text-main: #222;
      --text-muted: #555;
      --border-color: #ddd;
    }

    * { box-sizing: border-box; }

    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      margin: 0;
      background: var(--bg-color);
      color: var(--text-main);
      display: flex;
      justify-content: center;
      padding: 40px 20px;
      min-height: 100vh;
    }

    .profile-container {
      width: 100%;
      max-width: 800px;
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.06);
      overflow: hidden;
      display: flex;
      flex-direction: column;
    }

    /* --- HEADER --- */
    .profile-header {
      background-color: var(--asu-maroon);
      color: #fff;
      padding: 2rem 2.5rem;
      border-bottom: 4px solid var(--asu-gold);
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
    }

    .header-content h1 {
      margin: 0;
      font-size: 1.5rem;
      font-weight: 700;
    }

    .header-content p {
      margin: 4px 0 0 0;
      font-size: 0.9rem;
      opacity: 0.85;
    }

    .header-actions {
      display: flex;
      gap: 10px;
      align-items: center;
      flex-wrap: wrap;
    }

    .btn-back {
      color: rgba(255,255,255,0.95);
      text-decoration: none;
      font-size: 0.85rem;
      font-weight: 600;
      border: 1px solid rgba(255,255,255,0.35);
      padding: 6px 12px;
      border-radius: 4px;
      transition: all 0.2s;
      display: inline-block;
      white-space: nowrap;
    }

    .btn-back:hover {
      background: rgba(255,255,255,0.12);
      color: #fff;
      border-color: #fff;
    }

    .btn-secondary {
      border-color: rgba(255,255,255,0.25);
      color: rgba(255,255,255,0.85);
    }

    /* --- BODY & FORM --- */
    .profile-body {
      padding: 2.5rem;
    }

    .alert {
      padding: 12px 16px;
      border-radius: 6px;
      margin-bottom: 24px;
      font-size: 0.9rem;
      font-weight: 500;
    }
    .alert-error { background: #fce8e8; color: #b00020; border-left: 4px solid #b00020; }

    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 24px;
    }

    .form-group { margin-bottom: 0; }
    .full-width { grid-column: 1 / -1; }

    label {
      display: block;
      font-size: 0.75rem;
      font-weight: 700;
      color: var(--text-muted);
      margin-bottom: 6px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    input[type="text"], textarea {
      width: 100%;
      padding: 12px;
      border: 1px solid var(--border-color);
      border-radius: 6px;
      font-size: 0.95rem;
      font-family: inherit;
      background: #fafafa;
      transition: border 0.2s, box-shadow 0.2s;
    }

    input[type="text"]:focus, textarea:focus {
      outline: none;
      border-color: var(--asu-maroon);
      background: #fff;
      box-shadow: 0 0 0 3px rgba(140, 29, 64, 0.1);
    }

    textarea {
      resize: vertical;
      min-height: 100px;
      line-height: 1.5;
    }

    .char-count {
      text-align: right;
      font-size: 0.75rem;
      color: #999;
      margin-top: 4px;
    }

    /* --- FOOTER --- */
    .profile-footer {
      padding: 1.5rem 2.5rem;
      background: #fcfcfc;
      border-top: 1px solid #eee;
      text-align: right;
    }

    .btn-save {
      background-color: var(--asu-maroon);
      color: white;
      border: none;
      padding: 12px 32px;
      border-radius: 30px;
      font-weight: 700;
      font-size: 0.95rem;
      cursor: pointer;
      transition: background 0.2s, transform 0.1s;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .btn-save:hover {
      background-color: #5c132a;
      transform: translateY(-1px);
    }

    /* --- Tiny top-right save toast --- */
    .save-toast {
      position: fixed;
      top: 18px;
      right: 18px;
      background: #1e7a34;
      color: #fff;
      font-size: 0.85rem;
      font-weight: 600;
      padding: 10px 14px;
      border-radius: 8px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.15);
      opacity: 0;
      transform: translateY(-8px);
      pointer-events: none;
      z-index: 2000;
      transition: opacity 0.25s ease, transform 0.25s ease;
    }

    .save-toast.show {
      opacity: 1;
      transform: translateY(0);
    }

    @media (max-width: 600px) {
      body { padding: 0; }
      .profile-container { border-radius: 0; box-shadow: none; }
      .form-grid { grid-template-columns: 1fr; }
      .profile-header {
        padding: 1.5rem;
        flex-direction: column;
        align-items: flex-start;
      }
      .header-actions {
        width: 100%;
        justify-content: flex-end;
      }
      .profile-body, .profile-footer { padding: 1.5rem; }
      .btn-save { width: 100%; }
      .save-toast { right: 10px; left: 10px; top: 10px; text-align: center; }
    }
  </style>
</head>
<body>

  <div class="profile-container">

    <div class="profile-header">
      <div class="header-content">
        <h1>Edit Profile</h1>
        <p>Update your information visible to other users.</p>
      </div>

      <div class="header-actions">
        <!-- Back now ALWAYS goes to homepage -->
        <a class="btn-back btn-secondary" href="/home">Back</a>

        <!-- Keep Cancel behavior as-is (change to /home too if you want both same) -->
        <a class="btn-back" href="/account/me/">Cancel</a>
      </div>
    </div>

    <form method="post" action="/account/profile/update/">
      <div class="profile-body">

        <?php if ($error !== ''): ?>
          <div class="alert alert-error">Error: <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

        <div class="form-grid">

          <div class="form-group full-width">
            <label>Display Name</label>
            <input type="text" name="display_name" maxlength="120" value="<?php echo htmlspecialchars((string)$profile['display_name'], ENT_QUOTES, 'UTF-8'); ?>">
          </div>

          <div class="form-group">
            <label>Department</label>
            <input type="text" name="department" maxlength="120" value="<?php echo htmlspecialchars((string)$profile['department'], ENT_QUOTES, 'UTF-8'); ?>">
          </div>

          <div class="form-group">
            <label>Phone Number</label>
            <input type="text" name="phone" maxlength="30" value="<?php echo htmlspecialchars((string)$profile['phone'], ENT_QUOTES, 'UTF-8'); ?>">
          </div>

          <div class="form-group full-width">
            <label>Office Location</label>
            <input type="text" name="office_location" maxlength="120" value="<?php echo htmlspecialchars((string)$profile['office_location'], ENT_QUOTES, 'UTF-8'); ?>">
          </div>

          <div class="form-group full-width">
            <label>Professional Bio</label>
            <textarea name="bio" maxlength="500" rows="5"><?php echo htmlspecialchars((string)$profile['bio'], ENT_QUOTES, 'UTF-8'); ?></textarea>
            <div class="char-count">Max 500 characters</div>
          </div>

        </div>
      </div>

      <div class="profile-footer">
        <button type="submit" class="btn-save">Save Changes</button>
      </div>
    </form>

  </div>

  <?php if ($saved): ?>
    <div id="saveToast" class="save-toast">Changes saved</div>
    <script>
      (function () {
        const t = document.getElementById('saveToast');
        if (!t) return;
        requestAnimationFrame(() => t.classList.add('show'));
        setTimeout(() => t.classList.remove('show'), 2200);
      })();
    </script>
  <?php endif; ?>

</body>
</html>