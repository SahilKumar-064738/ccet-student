<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/utils/Response.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->connect();

    $subjectId = $_GET['subject_id'] ?? null;
    $fileType = $_GET['file_type'] ?? null;
    $examType = $_GET['exam_type'] ?? null;
    $teacher = $_GET['teacher'] ?? null;

    if (!$subjectId) {
        Response::error('Subject ID is required');
    }

    $query = "
        SELECT f.*, s.subject_name, s.subject_code 
        FROM files f
        JOIN subjects s ON f.subject_id = s.id
        WHERE f.subject_id = ?
    ";
    $params = [$subjectId];

    if ($fileType) {
        $query .= " AND f.file_type = ?";
        $params[] = $fileType;
    }

    if ($examType) {
        $query .= " AND f.exam_type = ?";
        $params[] = $examType;
    }

    if ($teacher) {
        $query .= " AND f.teacher_name = ?";
        $params[] = $teacher;
    }

    $query .= " ORDER BY f.created_at DESC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $files = $stmt->fetchAll();

    Response::success($files);

} catch (Exception $e) {
    error_log("Files Error: " . $e->getMessage());
    Response::serverError();
}
?>
