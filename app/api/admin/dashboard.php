<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/utils/Response.php';
require_once __DIR__ . '/../../lib/middleware/AuthMiddleware.php';

header('Content-Type: application/json');

try {
    $auth = new AuthMiddleware();
    $user = $auth->authenticate();

    $database = new Database();
    $db = $database->connect();

    $stats = [];

    if ($user['role'] === 'super_admin') {
        // Super Admin sees all stats
        $stmt = $db->query("SELECT COUNT(*) as count FROM subjects");
        $stats['total_subjects'] = $stmt->fetch()['count'];

        $stmt = $db->query("SELECT COUNT(*) as count FROM files");
        $stats['total_files'] = $stmt->fetch()['count'];

        $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
        $stats['total_admins'] = $stmt->fetch()['count'];

        $stmt = $db->query("SELECT SUM(download_count) as count FROM files");
        $stats['total_downloads'] = $stmt->fetch()['count'] ?? 0;

        // Recent uploads
        $stmt = $db->query("
            SELECT f.*, s.subject_name, b.code as branch_code, y.year_number, u.name as uploader_name
            FROM files f
            JOIN subjects s ON f.subject_id = s.id
            JOIN branches b ON s.branch_id = b.id
            JOIN years y ON s.year_id = y.id
            JOIN users u ON f.uploaded_by = u.id
            ORDER BY f.created_at DESC
            LIMIT 10
        ");
        $stats['recent_uploads'] = $stmt->fetchAll();

    } else {
        // Branch-Year Admin sees their scope stats
        $stmt = $db->prepare("
            SELECT COUNT(*) as count FROM subjects 
            WHERE branch_id = ? AND year_id = ?
        ");
        $stmt->execute([$user['branch_id'], $user['year_id']]);
        $stats['my_subjects'] = $stmt->fetch()['count'];

        $stmt = $db->prepare("
            SELECT COUNT(*) as count FROM files f
            JOIN subjects s ON f.subject_id = s.id
            WHERE s.branch_id = ? AND s.year_id = ?
        ");
        $stmt->execute([$user['branch_id'], $user['year_id']]);
        $stats['my_files'] = $stmt->fetch()['count'];

        $stmt = $db->prepare("
            SELECT COUNT(*) as count FROM files WHERE uploaded_by = ?
        ");
        $stmt->execute([$user['id']]);
        $stats['uploaded_by_me'] = $stmt->fetch()['count'];

        $stmt = $db->prepare("
            SELECT SUM(f.download_count) as count FROM files f
            WHERE f.uploaded_by = ?
        ");
        $stmt->execute([$user['id']]);
        $stats['my_downloads'] = $stmt->fetch()['count'] ?? 0;

        // Recent uploads by this admin
        $stmt = $db->prepare("
            SELECT f.*, s.subject_name
            FROM files f
            JOIN subjects s ON f.subject_id = s.id
            WHERE f.uploaded_by = ?
            ORDER BY f.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$user['id']]);
        $stats['my_recent_uploads'] = $stmt->fetchAll();
    }

    Response::success($stats);

} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    Response::serverError();
}
?>
