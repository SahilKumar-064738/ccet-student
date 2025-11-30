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
    $user = $auth->authenticate();

    $database = new Database();
    $db = $database->connect();

    $method = $_SERVER['REQUEST_METHOD'];

    // GET - List subjects
    if ($method === 'GET') {
        $query = "
            SELECT s.*, b.name as branch_name, b.code as branch_code, y.year_number
            FROM subjects s
            JOIN branches b ON s.branch_id = b.id
            JOIN years y ON s.year_id = y.id
            WHERE 1=1
        ";
        $params = [];

        if ($user['role'] !== 'super_admin') {
            $query .= " AND s.branch_id = ? AND s.year_id = ?";
            $params[] = $user['branch_id'];
            $params[] = $user['year_id'];
        }

        $query .= " ORDER BY y.year_number, s.subject_name";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $subjects = $stmt->fetchAll();

        Response::success($subjects);
    }

    // POST - Create subject
    elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        $subjectName = trim($data['subject_name'] ?? '');
        $subjectCode = trim($data['subject_code'] ?? '');
        $branchId = $data['branch_id'] ?? null;
        $yearId = $data['year_id'] ?? null;

        if (!Validator::required($subjectName)) {
            Response::error('Subject name is required');
        }

        if (!$branchId || !$yearId) {
            Response::error('Branch and year are required');
        }

        // Check access
        if (!$auth->checkBranchYearAccess($user, $branchId, $yearId)) {
            Response::forbidden('You can only add subjects to your assigned branch and year');
        }

        // Check duplicate
        $stmt = $db->prepare("
            SELECT id FROM subjects 
            WHERE subject_name = ? AND branch_id = ? AND year_id = ?
        ");
        $stmt->execute([$subjectName, $branchId, $yearId]);
        if ($stmt->fetch()) {
            Response::error('Subject already exists for this branch and year');
        }

        // Insert
        $stmt = $db->prepare("
            INSERT INTO subjects (subject_name, subject_code, branch_id, year_id, created_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$subjectName, $subjectCode, $branchId, $yearId, $user['id']]);

        $subjectId = $db->lastInsertId();

        $logger = new AuditLogger($db);
        $logger->log($user['id'], 'subject_create', 'subject', $subjectId, $data);

        Response::success(['subject_id' => $subjectId], 'Subject created successfully', 201);
    }

    // PUT - Update subject
    elseif ($method === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);
        $subjectId = $data['id'] ?? null;

        if (!$subjectId) {
            Response::error('Subject ID required');
        }

        // Get subject
        $stmt = $db->prepare("SELECT * FROM subjects WHERE id = ?");
        $stmt->execute([$subjectId]);
        $subject = $stmt->fetch();

        if (!$subject) {
            Response::notFound('Subject not found');
        }

        // Check access
        if (!$auth->checkBranchYearAccess($user, $subject['branch_id'], $subject['year_id'])) {
            Response::forbidden('Access denied');
        }

        // Update
        $stmt = $db->prepare("
            UPDATE subjects 
            SET subject_name = ?, subject_code = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $data['subject_name'],
            $data['subject_code'],
            $subjectId
        ]);

        $logger = new AuditLogger($db);
        $logger->log($user['id'], 'subject_update', 'subject', $subjectId, $data);

        Response::success(null, 'Subject updated successfully');
    }

    // DELETE - Delete subject
    elseif ($method === 'DELETE') {
        $data = json_decode(file_get_contents('php://input'), true);
        $subjectId = $data['id'] ?? null;

        if (!$subjectId) {
            Response::error('Subject ID required');
        }

        // Get subject
        $stmt = $db->prepare("SELECT * FROM subjects WHERE id = ?");
        $stmt->execute([$subjectId]);
        $subject = $stmt->fetch();

        if (!$subject) {
            Response::notFound('Subject not found');
        }

        // Check access
        if (!$auth->checkBranchYearAccess($user, $subject['branch_id'], $subject['year_id'])) {
            Response::forbidden('Access denied');
        }

        // Delete
        $stmt = $db->prepare("DELETE FROM subjects WHERE id = ?");
        $stmt->execute([$subjectId]);

        $logger = new AuditLogger($db);
        $logger->log($user['id'], 'subject_delete', 'subject', $subjectId, ['name' => $subject['subject_name']]);

        Response::success(null, 'Subject deleted successfully');
    }

} catch (Exception $e) {
    error_log("Subjects API Error: " . $e->getMessage());
    Response::serverError();
}
?>
