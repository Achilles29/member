<?php
$filename = $_GET['file'] ?? '';
$path = __DIR__ . '/uploads/foto_pelanggan/' . basename($filename);
if (file_exists($path)) {
    header('Content-Type: image/jpeg');
    readfile($path);
    exit;
}
http_response_code(404);
