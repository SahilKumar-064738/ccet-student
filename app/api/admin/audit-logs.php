<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/utils/Response.php';
require_once __DIR__ . '/../../lib/middleware/AuthMiddleware.php';

header('Content-Type: application/json');

try {
    $auth = new AuthMiddleware();
    $user = $auth->requireSuperAdmin();

    $database = new Database();
    $db = $database->connect();

    $limit = $_GET['limit'] ?? 100;
    $offset = $_GET['offset'] ?? 0;

    $stmt = $db->prepare("
        SELECT a.*, u.name as user_name, u.email as user_email
        FROM audit_logs a
        LEFT JOIN users u ON a.user_id = u.id
        ORDER BY a.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$limit, $offset]);
    $logs = $stmt->fetchAll();

    Response::success($logs);

} catch (Exception $e) {
    error_log("Audit Logs Error: " . $e->getMessage());
    Response::serverError();
}
?>
