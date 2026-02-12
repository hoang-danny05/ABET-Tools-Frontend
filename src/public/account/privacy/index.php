<?php
declare(strict_types=1);
require_once __DIR__ . '/../_guard.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Privacy | ABET Tools</title>
  <style>
    body { font-family: Arial, sans-serif; background:#f4f4f4; margin:0; padding:24px; }
    .card { max-width:760px; margin:0 auto; background:#fff; border-radius:8px; padding:24px; box-shadow:0 8px 24px rgba(0,0,0,.08); }
    .topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; }
    h1 { margin:0; color:#8C1D40; }
    .back-btn { text-decoration:none; background:#8C1D40; color:#fff; padding:10px 14px; border-radius:6px; font-weight:700; }
    .back-btn:hover { background:#5c132a; }
    ul { margin:14px 0 0 18px; }
    a { color:#8C1D40; }
  </style>
</head>
<body>
  <div class="card">
    <div class="topbar">
      <h1>Privacy</h1>
      <a class="back-btn" href="/home">← Back to ABET Tools</a>
    </div>
    <p>Privacy placeholder page.</p>
    <ul>
      <li><a href="https://www.asu.edu/about/privacy" target="_blank" rel="noopener noreferrer">ASU Privacy</a></li>
      <li><a href="https://registrar.asu.edu/policies/ferpa" target="_blank" rel="noopener noreferrer">Grades and Records</a></li>
      <li><a href="/account/privacy/export-data/">Export Data</a></li>
      <li><a href="/account/privacy/delete-request/">Delete Request</a></li>
    </ul>
  </div>
</body>
</html>