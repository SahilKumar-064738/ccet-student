<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

$fileId = $_GET['file_id'] ?? null;

if (!$fileId) {
    http_response_code(400);
    die('File ID required');
}

try {
    $database = new Database();
    $db = $database->connect();

    $stmt = $db->prepare("SELECT * FROM files WHERE id = ?");
    $stmt->execute([$fileId]);
    $file = $stmt->fetch();

    if (!$file) {
        http_response_code(404);
        die('File not found');
    }

    $filePath = UPLOAD_DIR . $file['file_path'];

    if (!file_exists($filePath)) {
        http_response_code(404);
        die('File not found on server');
    }

    // Serve file for preview (inline)
    header('Content-Type: ' . $file['mime_type']);
    header('Content-Disposition: inline; filename="' . $file['file_name'] . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: public, max-age=3600');
    
    readfile($filePath);
    exit;

} catch (Exception $e) {
    error_log("Preview Error: " . $e->getMessage());
    http_response_code(500);
    die('Error previewing file');
}
?>
