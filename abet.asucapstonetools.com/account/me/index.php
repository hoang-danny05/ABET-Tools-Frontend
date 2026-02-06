<?php
declare(strict_types=1);

require_once __DIR__ . '/../_guard.php';

// Session-based identity
$userId    = (int)($_SESSION['user_id'] ?? 0);
$userEmail = (string)($_SESSION['email'] ?? ($_SESSION['user_email'] ?? ''));
$asuId     = explode('@', $userEmail)[0] ?? 'user';

// Defaults
$profile = [
    'display_name'    => '',
    'department'      => '',
    'phone'           => '',
    'office_location' => '',
    'bio'             => '',
    'updated_at'      => ''
];

// Pull saved profile if service exists
$servicePath = '/home/osburn/abet_private/lib/account_profile_service.php';
if (is_file($servicePath)) {
    require_once $servicePath;
    if (function_exists('profile_get_for_user') && $userId > 0) {
        $dbProfile = profile_get_for_user($userId);
        if (is_array($dbProfile)) {
            $profile = array_merge($profile, $dbProfile);
        }
    }
}

$displayName = trim((string)$profile['display_name']) !== ''
    ? (string)$profile['display_name']
    : $asuId;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>My Profile | ABET Tools</title>
  <style>
    :root {
      --asu-maroon: #8C1D40;
      --asu-gold: #FFC627;
      --asu-black: #191919;
      --asu-gray: #E8E8E8;
      --bg-color: #F9F9F9;
      --text-main: #222;
      --text-muted: #666;
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

    /* --- LAYOUT --- */
    .profile-container {
      width: 100%;
      max-width: 900px;
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
      overflow: hidden;
      display: flex;
      flex-direction: column;
    }

    /* --- HEADER SECTION --- */
    .profile-header {
      background-color: var(--asu-maroon);
      color: #fff;
      padding: 2.5rem 2rem;
      position: relative;
      border-bottom: 4px solid var(--asu-gold);
    }

    .header-content {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 20px;
    }

    .user-info h1 {
      margin: 0;
      font-size: 2rem;
      font-weight: 700;
      letter-spacing: -0.5px;
    }

    .user-info p {
      margin: 5px 0 0 0;
      font-size: 1rem;
      opacity: 0.9;
      font-weight: 400;
    }

    /* --- NAV / BACK BUTTON --- */
    .top-nav {
      position: absolute;
      top: 15px;
      right: 20px;
    }
    
    .btn-back {
      color: rgba(255,255,255,0.85);
      text-decoration: none;
      font-size: 0.85rem;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      transition: color 0.2s;
    }
    .btn-back:hover { color: #fff; }
    .btn-back svg { margin-right: 6px; width: 14px; height: 14px; fill: currentColor; }

    /* --- MAIN CONTENT --- */
    .profile-body {
      padding: 2rem;
    }

    .section-title {
      font-size: 0.85rem;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: var(--text-muted);
      margin-bottom: 1.5rem;
      border-bottom: 1px solid var(--asu-gray);
      padding-bottom: 10px;
      font-weight: 700;
    }

    .data-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 24px;
      margin-bottom: 2rem;
    }

    .data-item {
      display: flex;
      flex-direction: column;
    }

    .data-label {
      font-size: 0.8rem;
      font-weight: 700;
      color: var(--text-muted);
      margin-bottom: 4px;
      text-transform: uppercase;
    }

    .data-value {
      font-size: 1.1rem;
      color: var(--asu-black);
      font-weight: 500;
      word-break: break-word;
    }

    .bio-section {
      grid-column: 1 / -1; /* Spans full width */
      background: #fafafa;
      padding: 20px;
      border-radius: 6px;
      border-left: 4px solid var(--asu-gray);
    }
    
    .bio-text {
      line-height: 1.6;
      color: #444;
    }

    /* --- FOOTER / METADATA --- */
    .profile-footer {
      padding: 1.5rem 2rem;
      background: #fdfdfd;
      border-top: 1px solid #eee;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 15px;
    }

    .last-updated {
      font-size: 0.85rem;
      color: #888;
      font-style: italic;
    }

    /* --- ACTION BUTTONS --- */
    .action-buttons {
      display: flex;
      gap: 12px;
    }

    .btn {
      text-decoration: none;
      padding: 10px 18px;
      border-radius: 30px; /* Pill shape */
      font-weight: 600;
      font-size: 0.9rem;
      transition: all 0.2s ease;
      display: inline-block;
      text-align: center;
    }

    .btn-primary {
      background-color: var(--asu-maroon);
      color: white;
      border: 1px solid var(--asu-maroon);
    }
    .btn-primary:hover {
      background-color: #5c132a;
      border-color: #5c132a;
      transform: translateY(-1px);
    }

    .btn-secondary {
      background-color: transparent;
      color: var(--asu-maroon);
      border: 1px solid var(--asu-gray);
    }
    .btn-secondary:hover {
      border-color: var(--asu-maroon);
      background-color: #fff5f8;
    }

    /* --- RESPONSIVE --- */
    @media (max-width: 600px) {
      body { padding: 0; }
      .profile-container { border-radius: 0; box-shadow: none; }
      .profile-header { padding: 3rem 1.5rem 2rem 1.5rem; } /* Extra top padding for Nav */
      .data-grid { grid-template-columns: 1fr; gap: 20px; }
      .profile-footer { flex-direction: column; align-items: stretch; text-align: center; }
      .action-buttons { flex-direction: column; }
    }
  </style>
</head>
<body>

  <div class="profile-container">
    
    <div class="profile-header">
      <div class="top-nav">
        <a class="btn-back" href="/home">
          <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
          Back to Dashboard
        </a>
      </div>

      <div class="header-content">
        <div class="user-info">
          <h1><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></h1>
          <p><?php echo htmlspecialchars($userEmail !== '' ? $userEmail : 'No email associated', ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
      </div>
    </div>

    <div class="profile-body">
      <div class="section-title">Directory Information</div>
      
      <div class="data-grid">
        <div class="data-item">
          <div class="data-label">ASU Username</div>
          <div class="data-value"><?php echo htmlspecialchars($asuId, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>

        <div class="data-item">
          <div class="data-label">Department</div>
          <div class="data-value"><?php echo htmlspecialchars((string)($profile['department'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></div>
        </div>

        <div class="data-item">
          <div class="data-label">Phone</div>
          <div class="data-value"><?php echo htmlspecialchars((string)($profile['phone'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></div>
        </div>

        <div class="data-item">
          <div class="data-label">Office Location</div>
          <div class="data-value"><?php echo htmlspecialchars((string)($profile['office_location'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></div>
        </div>

        <div class="data-item bio-section">
          <div class="data-label">Professional Bio</div>
          <div class="data-value bio-text"><?php
            $bio = trim((string)($profile['bio'] ?? ''));
            echo nl2br(htmlspecialchars($bio !== '' ? $bio : 'No biography available.', ENT_QUOTES, 'UTF-8'));
          ?></div>
        </div>
      </div>
    </div>

    <div class="profile-footer">
      <div class="last-updated">
        Last synced: <?php
          $updated = trim((string)($profile['updated_at'] ?? ''));
          echo htmlspecialchars($updated !== '' ? $updated : 'Never', ENT_QUOTES, 'UTF-8');
        ?>
      </div>
      
      <div class="action-buttons">
        <a class="btn btn-secondary" href="/account/settings/">Account Settings</a>
        <a class="btn btn-primary" href="/account/profile/">Edit Profile</a>
      </div>
    </div>

  </div>

</body>
</html>