<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/utils/Response.php';
require_once __DIR__ . '/../../lib/utils/Validator.php';
require_once __DIR__ . '/../../lib/utils/AuditLogger.php';
require_once __DIR__ . '/../../lib/middleware/AuthMiddleware.php';

header('Content-Type: application/json');

try {
    $auth = new AuthMiddleware();
    $user = $auth->requireSuperAdmin();

    $database = new Database();
    $db = $database->connect();

    $method = $_SERVER['REQUEST_METHOD'];

    // GET - List users
    if ($method === 'GET') {
        $stmt = $db->query("
            SELECT u.*, b.name as branch_name, b.code as branch_code, y.year_number
            FROM users u
            LEFT JOIN branches b ON u.branch_id = b.id
            LEFT JOIN years y ON u.year_id = y.id
            ORDER BY u.role, u.created_at DESC
        ");
        $users = $stmt->fetchAll();

        Response::success($users);
    }

    // POST - Create user
    elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        $email = trim($data['email'] ?? '');
        $name = trim($data['name'] ?? '');
        $role = $data['role'] ?? 'admin';
        $branchId = $data['branch_id'] ?? null;
        $yearId = $data['year_id'] ?? null;

        if (!Validator::email($email)) {
            Response::error('Invalid email');
        }

        if (!Validator::required($name)) {
            Response::error('Name is required');
        }

        if ($role === 'admin' && (!$branchId || !$yearId)) {
            Response::error('Branch and year required for admin role');
        }

        // Check duplicate
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            Response::error('User with this email already exists');
        }

        // Insert
        $stmt = $db->prepare("
            INSERT INTO users (email, name, role, branch_id, year_id)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$email, $name, $role, $branchId, $yearId]);

        $userId = $db->lastInsertId();

        $logger = new AuditLogger($db);
        $logger->log($user['id'], 'user_create', 'user', $userId, $data);

        Response::success(['user_id' => $userId], 'User created successfully', 201);
    }

    // PUT - Update user
    elseif ($method === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);
        $userId = $data['id'] ?? null;

        if (!$userId) {
            Response::error('User ID required');
        }

        $stmt = $db->prepare("
            UPDATE users 
            SET name = ?, is_active = ?, branch_id = ?, year_id = ?
            WHERE id = ? AND id != ?
        ");
        $stmt->execute([
            $data['name'],
            $data['is_active'] ?? 1,
            $data['branch_id'],
            $data['year_id'],
            $userId,
            $user['id'] // Prevent self-edit
        ]);

        $logger = new AuditLogger($db);
        $logger->log($user['id'], 'user_update', 'user', $userId, $data);

        Response::success(null, 'User updated successfully');
    }

    // DELETE - Delete user
    elseif ($method === 'DELETE') {
        $data = json_decode(file_get_contents('php://input'), true);
        $userId = $data['id'] ?? null;

        if (!$userId) {
            Response::error('User ID required');
        }

        if ($userId == $user['id']) {
            Response::error('Cannot delete yourself');
        }

        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);

        $logger = new AuditLogger($db);
        $logger->log($user['id'], 'user_delete', 'user', $userId);

        Response::success(null, 'User deleted successfully');
    }

} catch (Exception $e) {
    error_log("Users API Error: " . $e->getMessage());
    Response::serverError();
}
?>
