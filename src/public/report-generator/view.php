<?php
require_once '/home/osburn/abet_private/lib/auth.php';
require_login();

$job = $_GET['job'] ?? '';
$file = $_GET['file'] ?? '';

if (!preg_match('/^[A-Za-z0-9_-]{10,80}$/', $job)) {
    http_response_code(400);
    exit('Invalid job id');
}

$allowed = ['report.pdf', 'report.docx'];
if (!in_array($file, $allowed, true)) {
    http_response_code(400);
    exit('Invalid file');
}

$base = '/home/osburn/abet_private/report_jobs/' . $job . '/generated_pdfs/';
$path = realpath($base . $file);
$baseReal = realpath($base);

if (!$path || !$baseReal || strpos($path, $baseReal) !== 0 || !is_file($path)) {
    http_response_code(404);
    exit('File not found');
}

header('X-Content-Type-Options: nosniff');

if ($file === 'report.pdf') {
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="report.pdf"');
} else {
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="report.docx"');
}

header('Content-Length: ' . filesize($path));
readfile($path);
exit;