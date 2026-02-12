<?php
declare(strict_types=1);
require_once __DIR__ . '/../_guard.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method Not Allowed');
}

http_response_code(501);
exit('Not implemented yet');
