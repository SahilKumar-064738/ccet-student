<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../utils/Response.php';

class AuthMiddleware {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function authenticate() {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_start();
        }

        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
            Response::unauthorized('Not authenticated');
        }

        $stmt = $this->db->prepare("
            SELECT id, email, name, role, branch_id, year_id, is_active 
            FROM users 
            WHERE id = ? AND is_active = 1
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user) {
            session_destroy();
            Response::unauthorized('Invalid session');
        }

        return $user;
    }

    public function requireRole($allowedRoles) {
        $user = $this->authenticate();

        if (!in_array($user['role'], $allowedRoles)) {
            Response::forbidden('Insufficient permissions');
        }

        return $user;
    }

    public function requireSuperAdmin() {
        return $this->requireRole(['super_admin']);
    }

    public function checkBranchYearAccess($user, $branchId, $yearId) {
        if ($user['role'] === 'super_admin') {
            return true;
        }

        if ($user['branch_id'] == $branchId && $user['year_id'] == $yearId) {
            return true;
        }

        return false;
    }
}
?>
