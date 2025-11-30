<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/utils/Response.php';
require_once __DIR__ . '/../../lib/utils/AuditLogger.php';
require_once __DIR__ . '/../../lib/middleware/AuthMiddleware.php';

header('Content-Type: application/json');

try {
    $auth = new AuthMiddleware();
    $user = $auth->authenticate();

    $database = new Database();
    $db = $database->connect();

    $method = $_SERVER['REQUEST_METHOD'];

    // GET - List files
    if ($method === 'GET') {
        $query = "
            SELECT f.*, s.subject_name, s.subject_code, 
                   b.name as branch_name, y.year_number, u.name as uploader_name
            FROM files f
            JOIN subjects s ON f.subject_id = s.id
            JOIN branches b ON s.branch_id = b.id
            JOIN years y ON s.year_id = y.id
            JOIN users u ON f.uploaded_by = u.id
            WHERE 1=1
        ";
        $params = [];

        if ($user['role'] !== 'super_admin') {
            $query .= " AND s.branch_id = ? AND s.year_id = ?";
            $params[] = $user['branch_id'];
            $params[] = $user['year_id'];
        }

        $query .= " ORDER BY f.created_at DESC";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $files = $stmt->fetchAll();

        Response::success($files);
    }

    // PUT - Update file
    elseif ($method === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);
        $fileId = $data['id'] ?? null;

        if (!$fileId) {
            Response::error('File ID required');
        }

        // Get file details
        $stmt = $db->prepare("
            SELECT f.*, s.branch_id, s.year_id 
            FROM files f
            JOIN subjects s ON f.subject_id = s.id
            WHERE f.id = ?
        ");
        $stmt->execute([$fileId]);
        $file = $stmt->fetch();

        if (!$file) {
            Response::notFound('File not found');
        }

        // Check permissions
        if ($user['role'] !== 'super_admin' && $file['uploaded_by'] != $user['id']) {
            Response::forbidden('You can only edit your own files');
        }

        // Update
        $stmt = $db->prepare("
            UPDATE files 
            SET title = ?, description = ?, teacher_name = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $data['title'],
            $data['description'],
            $data['teacher_name'],
            $fileId
        ]);

        $logger = new AuditLogger($db);
        $logger->log($user['id'], 'file_update', 'file', $fileId, $data);

        Response::success(null, 'File updated successfully');
    }

    // DELETE - Delete file
    elseif ($method === 'DELETE') {
        $data = json_decode(file_get_contents('php://input'), true);
        $fileId = $data['id'] ?? null;

        if (!$fileId) {
            Response::error('File ID required');
        }

        // Get file details
        $stmt = $db->prepare("
            SELECT f.*, s.branch_id, s.year_id 
            FROM files f
            JOIN subjects s ON f.subject_id = s.id
            WHERE f.id = ?
        ");
        $stmt->execute([$fileId]);
        $file = $stmt->fetch();

        if (!$file) {
            Response::notFound('File not found');
        }

        // Check permissions
        if ($user['role'] !== 'super_admin' && $file['uploaded_by'] != $user['id']) {
            Response::forbidden('You can only delete your own files');
        }

        // Delete physical file
        $filePath = UPLOAD_DIR . $file['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Delete from database
        $stmt = $db->prepare("DELETE FROM files WHERE id = ?");
        $stmt->execute([$fileId]);

        $logger = new AuditLogger($db);
        $logger->log($user['id'], 'file_delete', 'file', $fileId, ['title' => $file['title']]);

        Response::success(null, 'File deleted successfully');
    }

} catch (Exception $e) {
    error_log("Files API Error: " . $e->getMessage());
    Response::serverError();
}
?>
