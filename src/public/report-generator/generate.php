<?php
declare(strict_types=1);

/**
 * report-generator/generate.php
 * Secure upload + isolated Python run + optional DOCX->PDF conversion
 */

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

$privateLogDir = $_ENV['ABET_PRIVATE_DIR'] . '/logs';
if (!is_dir($privateLogDir)) {
    @mkdir($privateLogDir, 0700, true);
}
ini_set('error_log', $privateLogDir . '/report-generator-php.log');

register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        error_log("FATAL: {$e['message']} @ {$e['file']}:{$e['line']}");
    }
});

require_once $_ENV['ABET_PRIVATE_DIR'] . '/lib/auth.php';
require_login();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function json_response(int $code, array $payload): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

function fn_available(string $name): bool
{
    if (!function_exists($name)) {
        return false;
    }
    $disabled = array_filter(array_map('trim', explode(',', (string)ini_get('disable_functions'))));
    return !in_array($name, $disabled, true);
}

function generate_suffix(int $bytes = 4): string
{
    if (fn_available('random_bytes')) {
        return bin2hex(random_bytes($bytes));
    }
    if (fn_available('openssl_random_pseudo_bytes')) {
        $strong = false;
        $raw = openssl_random_pseudo_bytes($bytes, $strong);
        if ($raw !== false) {
            return bin2hex($raw);
        }
    }
    return dechex(mt_rand(0, PHP_INT_MAX));
}

/**
 * Run command with proc_open (preferred; preserves cwd + separates stdout/stderr)
 * @return array{exit:int,stdout:string,stderr:string,runner:string}
 */
function run_with_proc_open(string $cmd, string $cwd, array $env): array
{
    $descriptorspec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $pipes = [];
    $proc = proc_open($cmd, $descriptorspec, $pipes, $cwd, $env);

    if (!is_resource($proc)) {
        return ['exit' => 999, 'stdout' => '', 'stderr' => 'proc_open failed to start process', 'runner' => 'proc_open'];
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    $exit = proc_close($proc);

    return [
        'exit'   => (int)$exit,
        'stdout' => (string)$stdout,
        'stderr' => (string)$stderr,
        'runner' => 'proc_open',
    ];
}

/**
 * Run command with exec fallback (stdout+stderr merged)
 * @return array{exit:int,stdout:string,stderr:string,runner:string}
 */
function run_with_exec(string $pythonBin, string $scriptPath, string $cwd): array
{
    $cmd = 'cd ' . escapeshellarg($cwd)
        . ' && ' . escapeshellarg($pythonBin)
        . ' ' . escapeshellarg($scriptPath)
        . ' 2>&1';

    $out = [];
    $exit = 0;
    exec($cmd, $out, $exit);

    return [
        'exit'   => (int)$exit,
        'stdout' => implode("\n", $out),
        'stderr' => '',
        'runner' => 'exec',
    ];
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(405, ['ok' => false, 'error' => 'POST required']);
    }

    // CSRF
    $csrf = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_report_token']) || !hash_equals((string)$_SESSION['csrf_report_token'], (string)$csrf)) {
        json_response(403, ['ok' => false, 'error' => 'Invalid CSRF token']);
    }

    // Upload checks
    if (!isset($_FILES['json_file'])) {
        json_response(400, ['ok' => false, 'error' => 'No file uploaded']);
    }

    $file = $_FILES['json_file'];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        json_response(400, ['ok' => false, 'error' => 'Upload failed (code ' . ($file['error'] ?? 'unknown') . ')']);
    }

    $maxBytes = 2 * 1024 * 1024; // 2MB
    if (($file['size'] ?? 0) > $maxBytes) {
        json_response(400, ['ok' => false, 'error' => 'File too large (max 2MB)']);
    }

    $originalName = $file['name'] ?? 'upload.json';
    $ext = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
    if ($ext !== 'json') {
        json_response(400, ['ok' => false, 'error' => 'Only .json files are allowed']);
    }

    $tmpPath = $file['tmp_name'] ?? '';
    if (!$tmpPath || !is_uploaded_file($tmpPath)) {
        json_response(400, ['ok' => false, 'error' => 'Invalid uploaded file']);
    }

    $raw = file_get_contents($tmpPath);
    if ($raw === false) {
        json_response(400, ['ok' => false, 'error' => 'Could not read uploaded file']);
    }

    $decoded = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        json_response(400, ['ok' => false, 'error' => 'Invalid JSON: ' . json_last_error_msg()]);
    }

    // Paths
    $jobsRoot      = $_ENV['ABET_PRIVATE_DIR'] . '/report_jobs';
    $generatorPath = '../cgi-bin/abetReportGenerator.py';
    $pythonBin     = '/bin/python3';

    if (!is_dir($jobsRoot) && !mkdir($jobsRoot, 0700, true)) {
        json_response(500, ['ok' => false, 'error' => 'Cannot create jobs directory']);
    }

    if (!file_exists($generatorPath)) {
        json_response(500, ['ok' => false, 'error' => 'Generator script not found']);
    }

    if (!file_exists($pythonBin)) {
        json_response(500, ['ok' => false, 'error' => 'Python binary not found']);
    }

    $jobId   = date('Ymd_His') . '_' . generate_suffix(4);
    $jobDir  = $jobsRoot . '/' . $jobId;
    $inputDir = $jobDir . '/input_jsons';
    $outDir   = $jobDir . '/generated_pdfs';
    $logDir   = $jobDir . '/logs';

    foreach ([$jobDir, $inputDir, $outDir, $logDir] as $dir) {
        if (!is_dir($dir) && !mkdir($dir, 0700, true)) {
            json_response(500, ['ok' => false, 'error' => 'Cannot create job folder']);
        }
    }

    // Save normalized JSON as input_jsons/input.json
    $normalizeFlags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;
    if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
        $normalizeFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
    }

    $normalizedJson = json_encode($decoded, $normalizeFlags);
    if ($normalizedJson === false) {
        json_response(400, ['ok' => false, 'error' => 'Could not normalize JSON']);
    }

    $inputJsonPath = $inputDir . '/input.json';
    if (file_put_contents($inputJsonPath, $normalizedJson) === false) {
        json_response(500, ['ok' => false, 'error' => 'Failed to save JSON']);
    }

    // Run Python generator from isolated cwd = $jobDir
    $cmd = escapeshellarg($pythonBin) . ' ' . escapeshellarg($generatorPath);
    $env = [
        'HOME' => '/home/osburn',
        'PATH' => '/usr/local/bin:/usr/bin:/bin',
        'PYTHONUNBUFFERED' => '1',
    ];

    $runResult = null;

    if (fn_available('proc_open')) {
        $runResult = run_with_proc_open($cmd, $jobDir, $env);
    } elseif (fn_available('exec')) {
        $runResult = run_with_exec($pythonBin, $generatorPath, $jobDir);
    } else {
        json_response(500, ['ok' => false, 'error' => 'Server cannot execute external commands (proc_open/exec disabled)']);
    }

    $runLog = "RUNNER: {$runResult['runner']}\n"
        . "CMD: {$cmd}\n"
        . "CWD: {$jobDir}\n"
        . "EXIT: {$runResult['exit']}\n\n"
        . "STDOUT:\n{$runResult['stdout']}\n\n"
        . "STDERR:\n{$runResult['stderr']}\n";
    @file_put_contents($logDir . '/run.log', $runLog);

    if ((int)$runResult['exit'] !== 0) {
        json_response(500, [
            'ok' => false,
            'error' => 'Generator failed',
            'job_id' => $jobId
        ]);
    }

    // Find generated DOCX
    $docxFiles = glob($outDir . '/*_ABET_Report.docx') ?: [];
    if (count($docxFiles) === 0) {
        json_response(500, ['ok' => false, 'error' => 'No DOCX generated', 'job_id' => $jobId]);
    }

    usort($docxFiles, static function ($a, $b) {
        return filemtime($b) <=> filemtime($a);
    });

    $docxPath = $docxFiles[0];
    $finalDocxPath = $outDir . '/report.docx';
    if ($docxPath !== $finalDocxPath) {
        @rename($docxPath, $finalDocxPath);
    }
    if (!file_exists($finalDocxPath)) {
        $finalDocxPath = $docxPath;
    }

    // Optional DOCX -> PDF conversion with LibreOffice
    $pdfReady = false;
    $finalPdfPath = $outDir . '/report.pdf';

    $soffice = '';
    $sofficeCandidates = [
        '/usr/bin/soffice',
        '/usr/local/bin/soffice',
        '/bin/soffice',
    ];
    foreach ($sofficeCandidates as $cand) {
        if (is_file($cand) && is_executable($cand)) {
            $soffice = $cand;
            break;
        }
    }

    if ($soffice === '' && fn_available('shell_exec')) {
        $found = trim((string)shell_exec('command -v soffice 2>/dev/null'));
        if ($found !== '' && is_file($found) && is_executable($found)) {
            $soffice = $found;
        }
    }

    if ($soffice !== '' && fn_available('exec')) {
        $convertCmd = escapeshellarg($soffice)
            . ' --headless --convert-to pdf --outdir '
            . escapeshellarg($outDir) . ' '
            . escapeshellarg($finalDocxPath) . ' 2>&1';

        $convOutput = [];
        $convExit = 0;
        exec($convertCmd, $convOutput, $convExit);

        @file_put_contents(
            $logDir . '/convert.log',
            "CMD: {$convertCmd}\nEXIT: {$convExit}\n\n" . implode("\n", $convOutput)
        );

        $candidatePdf = preg_replace('/\.docx$/i', '.pdf', $finalDocxPath);
        if (is_string($candidatePdf) && file_exists($candidatePdf)) {
            if ($candidatePdf !== $finalPdfPath) {
                @rename($candidatePdf, $finalPdfPath);
            }
            $pdfReady = file_exists($finalPdfPath);
        }
    }

    // URLs served through authenticated stream endpoint
    $docxUrl = '/report-generator/view.php?job=' . urlencode($jobId) . '&file=report.docx';
    $pdfUrl  = '/report-generator/view.php?job=' . urlencode($jobId) . '&file=report.pdf';

    json_response(200, [
        'ok' => true,
        'job_id' => $jobId,
        'pdf_ready' => $pdfReady,
        'pdf_url' => $pdfReady ? $pdfUrl : null,
        'docx_url' => $docxUrl
    ]);
} catch (Throwable $e) {
    error_log('generate.php exception: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    json_response(500, ['ok' => false, 'error' => 'Internal server error']);
}