<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/utils/Response.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->connect();

    $id = $_GET['id'] ?? null;
    $branchId = $_GET['branch_id'] ?? null;
    $yearId = $_GET['year_id'] ?? null;

    if ($id) {
        $stmt = $db->prepare("
            SELECT s.*, b.name as branch_name, b.code as branch_code, y.year_number 
            FROM subjects s
            JOIN branches b ON s.branch_id = b.id
            JOIN years y ON s.year_id = y.id
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        $subject = $stmt->fetch();

        if (!$subject) {
            Response::notFound('Subject not found');
        }

        Response::success($subject);
    } else {
        $query = "
            SELECT s.*, b.name as branch_name, b.code as branch_code, y.year_number 
            FROM subjects s
            JOIN branches b ON s.branch_id = b.id
            JOIN years y ON s.year_id = y.id
            WHERE 1=1
        ";
        $params = [];

        if ($branchId) {
            $query .= " AND s.branch_id = ?";
            $params[] = $branchId;
        }

        if ($yearId) {
            $query .= " AND s.year_id = ?";
            $params[] = $yearId;
        }

        $query .= " ORDER BY y.year_number, s.subject_name";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $subjects = $stmt->fetchAll();

        Response::success($subjects);
    }

} catch (Exception $e) {
    error_log("Subjects Error: " . $e->getMessage());
    Response::serverError();
}
?>
