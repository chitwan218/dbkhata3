<?php
require_once 'config/config.php';
require_once 'includes/auth.php';

requireLogin();

$relativePath = $_GET['file'] ?? '';
if (!$relativePath) {
    http_response_code(400);
    exit('No file specified.');
}

// Define the uploads root directory (absolute path)
$uploadsRoot = realpath(__DIR__ . '/uploads');
if (!$uploadsRoot) {
    http_response_code(500);
    exit('Uploads directory not found.');
}

// Resolve the requested file's absolute path
$fullPath = realpath($uploadsRoot . '/' . $relativePath);

// Security check: file must exist and be inside the uploads directory
if (!$fullPath || strpos($fullPath, $uploadsRoot) !== 0 || !is_file($fullPath)) {
    http_response_code(404);
    exit('File not found or access denied.');
}

// Determine MIME type
$mime = mime_content_type($fullPath) ?: 'application/octet-stream';

// Send headers
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($fullPath));

// Inline display for certain MIME types, otherwise force download
$inlineMimes = [
    'image/jpeg', 'image/png', 'image/gif', 'application/pdf',
    'text/plain', 'text/html', 'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
];

$disposition = in_array($mime, $inlineMimes) ? 'inline' : 'attachment';

header('Content-Disposition: ' . $disposition . '; filename="' . basename($fullPath) . '"');

// Output the file content
readfile($fullPath);
exit;
