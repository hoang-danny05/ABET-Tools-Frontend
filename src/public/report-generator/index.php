<?php
require_once $_ENV['ABET_PRIVATE_DIR'] . '/lib/auth.php';
require_login();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['csrf_report_token'])) {
    $_SESSION['csrf_report_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_report_token'];

$displayName = $_SESSION['display_name']
    ?? $_SESSION['name']
    ?? $_SESSION['email']
    ?? 'Account';

function e(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Report Generation</title>
  <style>
    :root {
      /* Official ASU Brand Colors */
      --asu-maroon: #8C1D40;
      --asu-gold: #FFC627;
      --asu-rich-black: #191919;
      --asu-dark-gray: #484848;
      
      /* UI Variables */
      --bg-body: #F4F5F7;
      --bg-card: #FFFFFF;
      --text-main: #191919;
      --text-muted: #5C6670;
      --border-light: #E0E0E0;
      
      --state-success: #1f8f4e;
      --state-success-bg: #e8f5e9;
      --state-error: #b42318;
      --state-error-bg: #fdf2f2;
      --state-info: #00558C;
      --state-info-bg: #eef7fc;
    }

    * { box-sizing: border-box; }

    body {
      margin: 0;
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      color: var(--text-main);
      background-color: var(--bg-body);
      min-height: 100vh;
      line-height: 1.5;
    }

    /* --- Top Navigation Bar --- */
    .topbar {
      height: 72px;
      border-bottom: 4px solid var(--asu-gold);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 24px;
      background-color: var(--asu-maroon);
      color: white;
      position: sticky;
      top: 0;
      z-index: 50;
      box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }

    .back-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      text-decoration: none;
      color: #fff;
      font-size: 14px;
      font-weight: 500;
      opacity: 0.9;
      transition: opacity 0.2s;
    }
    .back-btn:hover { opacity: 1; text-decoration: underline; }

    /* --- Profile Menu --- */
    .profile-wrap { position: relative; }
    
    .profile-btn {
      border: 1px solid rgba(255,255,255,0.3);
      background: rgba(0,0,0,0.2);
      color: #fff;
      padding: 8px 16px;
      border-radius: 4px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 500;
      font-size: 14px;
      transition: background 0.2s;
    }
    .profile-btn:hover { background: rgba(0,0,0,0.4); }

    .avatar {
      width: 28px; height: 28px;
      border-radius: 50%;
      background-color: var(--asu-gold);
      color: var(--asu-maroon);
      display: grid;
      place-items: center;
      font-size: 13px;
      font-weight: 700;
    }

    .menu {
      position: absolute;
      right: 0;
      top: 50px;
      min-width: 240px;
      background: var(--bg-card);
      border: 1px solid var(--border-light);
      border-radius: 4px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
      padding: 8px 0;
      display: none;
      z-index: 100;
    }
    .menu.show { display: block; }
    
    .menu .menu-user {
      color: var(--text-muted);
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      padding: 12px 20px;
      border-bottom: 1px solid var(--border-light);
      margin-bottom: 4px;
      font-weight: 600;
    }
    
    .menu a {
      display: block;
      color: var(--text-main);
      text-decoration: none;
      padding: 10px 20px;
      font-size: 14px;
      transition: background 0.1s;
    }
    .menu a:hover { background-color: #F0F0F0; color: var(--asu-maroon); }

    /* --- Main Content --- */
    .container {
      max-width: 850px;
      margin: 48px auto;
      padding: 0 20px;
    }

    .card {
      background: var(--bg-card);
      border: 1px solid var(--border-light);
      border-radius: 6px;
      padding: 40px;
      box-shadow: 0 2px 15px rgba(0,0,0,0.05);
      position: relative;
    }
    .card::before {
      content: "";
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 6px;
      background: linear-gradient(90deg, var(--asu-maroon) 0%, var(--asu-maroon) 85%, var(--asu-gold) 85%, var(--asu-gold) 100%);
      border-radius: 6px 6px 0 0;
    }

    h1 {
      margin: 0 0 8px;
      font-size: 28px;
      color: var(--asu-maroon);
      font-weight: 700;
      letter-spacing: -0.5px;
    }
    .sub {
      margin: 0 0 32px;
      color: var(--text-muted);
      font-size: 15px;
      max-width: 600px;
      line-height: 1.6;
    }

    /* --- Dropzone Fixed --- */
    .dropzone {
      display: block; /* Ensures label behaves like a box, fixing the border issue */
      width: 100%;
      border: 2px dashed #CBD5E0;
      border-radius: 6px;
      padding: 48px;
      text-align: center;
      background: #FAFAFA;
      transition: all 0.2s ease-in-out;
      cursor: pointer;
      position: relative;
    }
    .dropzone:hover, .dropzone.dragover {
      border-color: var(--asu-maroon);
      background: #FDF2F5;
    }
    .dropzone input { display: none; }
    
    .dropzone div strong { color: var(--asu-maroon); }
    
    .file-name {
      margin-top: 12px;
      color: var(--text-main);
      font-size: 15px;
      font-weight: 600;
      background: white;
      display: inline-block;
      padding: 4px 12px;
      border-radius: 4px;
      border: 1px solid var(--border-light);
    }

    .hint {
      color: var(--text-muted);
      font-size: 13px;
      margin-top: 10px;
    }

    /* --- Buttons & Actions --- */
    .actions {
      margin-top: 32px;
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      align-items: center;
    }

    .btn {
      border: 1px solid transparent;
      padding: 12px 24px;
      border-radius: 4px;
      font-weight: 600;
      font-size: 15px;
      text-decoration: none;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: all 0.2s;
    }
    .btn:disabled { opacity: 0.6; cursor: not-allowed; filter: grayscale(1); }

    .btn.primary {
      background: var(--asu-maroon);
      color: #fff;
      box-shadow: 0 2px 4px rgba(140, 29, 64, 0.2);
    }
    .btn.primary:hover { background: #60132C; transform: translateY(-1px); }

    .btn.success {
      background: var(--asu-gold);
      color: #000;
      border: 1px solid #e0ac22;
    }
    .btn.success:hover { background: #eeb300; }

    .btn.outline {
      background: white;
      border: 1px solid #CCC;
      color: var(--text-main);
    }
    .btn.outline:hover { border-color: var(--asu-maroon); color: var(--asu-maroon); }

    /* --- Status Messages --- */
    .status {
      margin-top: 24px;
      padding: 16px;
      border-radius: 4px;
      font-size: 14px;
      display: none;
      border-left: 4px solid transparent;
    }
    .status.info {
      display: block;
      background: var(--state-info-bg);
      color: #0c3d5d;
      border-left-color: var(--state-info);
    }
    .status.ok {
      display: block;
      background: var(--state-success-bg);
      color: #0e4e2a;
      border-left-color: var(--state-success);
    }
    .status.err {
      display: block;
      background: var(--state-error-bg);
      color: #7a160e;
      border-left-color: var(--state-error);
    }

    .results {
      margin-top: 24px;
      display: none;
      padding-top: 24px;
      border-top: 1px solid var(--border-light);
    }
    .results.show { display: block; }

    @media (max-width: 600px) {
        .card { padding: 20px; }
        .topbar { padding: 0 16px; }
    }
  </style>
</head>
<body>
  <header class="topbar">
    <a class="back-btn" href="/index.php">
        <span>&larr;</span> Back to Home
    </a>

    <div class="profile-wrap">
      <button class="profile-btn" id="profileBtn" type="button" aria-haspopup="true" aria-expanded="false">
        <span><?php echo e($displayName); ?></span>
        <span>&#9662;</span>
      </button>

      <div class="menu" id="profileMenu">
        <div class="menu-user"><?php echo e($displayName); ?></div>
        <a href="/index.php">Dashboard</a>
        <a href="/logout">Sign out</a>
      </div>
    </div>
  </header>

  <main class="container">
    <section class="card">
      <h1>ABET Report Generation</h1>
      <p class="sub">Upload a Canvas JSON export to standardize accreditation data. Generate the report, then access the PDF preview or download the raw DOCX file.</p>

      <label id="dropzone" class="dropzone">
        <input id="jsonFile" type="file" accept=".json,application/json">
        <div><strong>Drag & drop JSON here</strong> or click to browse</div>
        <div class="hint">Only .json files (max 2MB)</div>
        <div id="fileName" class="file-name">No file selected</div>
      </label>

      <div class="actions">
        <button id="generateBtn" class="btn primary" type="button">Generate Report</button>
      </div>

      <div id="status" class="status"></div>

      <div id="results" class="results">
        <div class="actions">
          <a id="openPdfBtn" class="btn success" href="#" target="_blank" rel="noopener" style="display:none;">
            Open PDF in New Tab
          </a>
          <a id="downloadDocxBtn" class="btn outline" href="#" style="display:none;">
            Download DOCX
          </a>
        </div>
        <div id="pdfHint" class="hint" style="display:none;"></div>
      </div>
    </section>
  </main>

  <script>
    // ===== Profile dropdown =====
    const profileBtn = document.getElementById('profileBtn');
    const profileMenu = document.getElementById('profileMenu');

    profileBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      const isOpen = profileMenu.classList.toggle('show');
      profileBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    document.addEventListener('click', () => {
      profileMenu.classList.remove('show');
      profileBtn.setAttribute('aria-expanded', 'false');
    });

    // ===== Upload + Generate =====
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('jsonFile');
    const fileNameEl = document.getElementById('fileName');
    const generateBtn = document.getElementById('generateBtn');
    const statusEl = document.getElementById('status');

    const resultsEl = document.getElementById('results');
    const openPdfBtn = document.getElementById('openPdfBtn');
    const downloadDocxBtn = document.getElementById('downloadDocxBtn');
    const pdfHint = document.getElementById('pdfHint');

    let selectedFile = null;

    function setStatus(type, msg) {
      statusEl.className = 'status ' + type;
      statusEl.textContent = msg;
    }

    function resetResults() {
      resultsEl.classList.remove('show');
      openPdfBtn.style.display = 'none';
      downloadDocxBtn.style.display = 'none';
      pdfHint.style.display = 'none';
      pdfHint.textContent = '';
      openPdfBtn.href = '#';
      downloadDocxBtn.href = '#';
    }

    function isJsonFile(file) {
      if (!file) return false;
      const name = (file.name || '').toLowerCase();
      return name.endsWith('.json');
    }

    function setFile(file) {
      if (!file) return;
      if (!isJsonFile(file)) {
        selectedFile = null;
        fileInput.value = '';
        fileNameEl.textContent = 'Invalid file. Please upload a .json file.';
        setStatus('err', 'Only .json files are allowed.');
        resetResults();
        return;
      }
      selectedFile = file;
      fileNameEl.textContent = `${file.name} (${Math.round(file.size / 1024)} KB)`;
      setStatus('info', 'Ready to generate.');
      resetResults();
    }

    // Click upload
    dropzone.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', () => {
      const f = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
      setFile(f);
    });

    // Drag/drop behavior
    ['dragenter', 'dragover'].forEach(evt => {
      dropzone.addEventListener(evt, (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropzone.classList.add('dragover');
      });
    });

    ['dragleave', 'drop'].forEach(evt => {
      dropzone.addEventListener(evt, (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropzone.classList.remove('dragover');
      });
    });

    dropzone.addEventListener('drop', (e) => {
      const files = e.dataTransfer.files;
      if (files && files.length > 0) {
        setFile(files[0]);
      }
    });

    generateBtn.addEventListener('click', async () => {
      resetResults();

      if (!selectedFile) {
        setStatus('err', 'Please choose a JSON file first.');
        return;
      }

      const fd = new FormData();
      fd.append('csrf_token', '<?php echo e($csrfToken); ?>');
      fd.append('json_file', selectedFile);

      generateBtn.disabled = true;
      setStatus('info', 'Generating report...');

      try {
        const res = await fetch('/report-generator/generate.php', {
          method: 'POST',
          body: fd,
          credentials: 'same-origin',
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        const ct = (res.headers.get('content-type') || '').toLowerCase();
        let data = null;

        if (ct.includes('application/json')) {
          data = await res.json();
        } else {
          const text = await res.text();
          throw new Error(`Server returned non-JSON response (HTTP ${res.status}).`);
        }

        if (!res.ok || !data.ok) {
          throw new Error(data?.error || `Request failed (HTTP ${res.status})`);
        }

        if (data.docx_url) {
          downloadDocxBtn.href = data.docx_url;
          downloadDocxBtn.style.display = 'inline-flex';
        }

        if (data.pdf_ready && data.pdf_url) {
          openPdfBtn.href = data.pdf_url;
          openPdfBtn.style.display = 'inline-flex';
          pdfHint.style.display = 'none';
        } else {
          pdfHint.textContent = 'PDF preview not available';
          pdfHint.style.display = 'block';
        }

        resultsEl.classList.add('show');
        setStatus('ok', 'Report generated successfully.');
      } catch (err) {
        setStatus('err', err.message || 'Generation failed.');
      } finally {
        generateBtn.disabled = false;
      }
    });
  </script>
</body>
</html>