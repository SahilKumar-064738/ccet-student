<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/utils/Response.php';
require_once __DIR__ . '/../../lib/utils/Validator.php';
require_once __DIR__ . '/../../lib/utils/AuditLogger.php';
require_once __DIR__ . '/../../lib/middleware/AuthMiddleware.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

try {
    $auth = new AuthMiddleware();
    $user = $auth->authenticate();

    $database = new Database();
    $db = $database->connect();

    // Get form data
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $subjectId = $_POST['subject_id'] ?? null;
    $fileType = $_POST['file_type'] ?? null;
    $examType = $_POST['exam_type'] ?? null;
    $teacherName = trim($_POST['teacher_name'] ?? '');
    $linkedExamId = $_POST['linked_exam_id'] ?? null;

    // Validation
    if (!Validator::required($title)) {
        Response::error('Title is required');
    }

    if (!$subjectId || !Validator::isNumeric($subjectId)) {
        Response::error('Valid subject is required');
    }

    if (!Validator::required($teacherName)) {
        Response::error('Teacher name is required');
    }

    if (!Validator::inArray($fileType, ['exam', 'solution', 'notes'])) {
        Response::error('Invalid file type');
    }

    // Validate exam type for exam and solution
    if (in_array($fileType, ['exam', 'solution'])) {
        if (!Validator::inArray($examType, ['MST1', 'MST2', 'QUIZ', 'ASSIGNMENT', 'REAPPEAR', 'ENDSEM'])) {
            Response::error('Exam type is required for exams and solutions');
        }
    } else {
        $examType = null;
    }

    // Validate linked exam for solutions
    if ($fileType === 'solution' && !$linkedExamId) {
        Response::error('Linked exam is required for solutions');
    }

    // Check subject belongs to user's scope
    $stmt = $db->prepare("SELECT branch_id, year_id FROM subjects WHERE id = ?");
    $stmt->execute([$subjectId]);
    $subject = $stmt->fetch();

    if (!$subject) {
        Response::error('Subject not found');
    }

    if (!$auth->checkBranchYearAccess($user, $subject['branch_id'], $subject['year_id'])) {
        Response::forbidden('You can only upload to your assigned branch and year');
    }

    // Validate file
    if (!isset($_FILES['file'])) {
        Response::error('File is required');
    }

    $fileValidation = Validator::validatePdf($_FILES['file']);
    if (!$fileValidation['valid']) {
        Response::error($fileValidation['error']);
    }

    // Generate unique filename
    $extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
    $randomName = bin2hex(random_bytes(16)) . '.' . $extension;
    $uploadPath = UPLOAD_DIR . $randomName;

    // Move uploaded file
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $uploadPath)) {
        Response::error('Failed to upload file');
    }

    // Insert into database
    $stmt = $db->prepare("
        INSERT INTO files (
            title, description, subject_id, file_type, exam_type, 
            teacher_name, linked_exam_id, file_path, file_name, 
            file_size, mime_type, uploaded_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $title,
        $description,
        $subjectId,
        $fileType,
        $examType,
        $teacherName,
        $linkedExamId,
        $randomName,
        $_FILES['file']['name'],
        $_FILES['file']['size'],
        $_FILES['file']['type'],
        $user['id']
    ]);

    $fileId = $db->lastInsertId();

    // Log activity
    $logger = new AuditLogger($db);
    $logger->log($user['id'], 'file_upload', 'file', $fileId, [
        'title' => $title,
        'file_type' => $fileType,
        'subject_id' => $subjectId
    ]);

    Response::success([
        'file_id' => $fileId,
        'message' => 'File uploaded successfully'
    ], 'File uploaded successfully', 201);

} catch (Exception $e) {
    error_log("Upload Error: " . $e->getMessage());
    Response::serverError();
}
?>
