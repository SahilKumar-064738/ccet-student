<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/utils/Response.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->connect();

    $id = $_GET['id'] ?? null;

    if ($id) {
        $stmt = $db->prepare("SELECT * FROM branches WHERE id = ?");
        $stmt->execute([$id]);
        $branch = $stmt->fetch();

        if (!$branch) {
            Response::notFound('Branch not found');
        }

        Response::success($branch);
    } else {
        $stmt = $db->query("SELECT * FROM branches ORDER BY name");
        $branches = $stmt->fetchAll();
        Response::success($branches);
    }

} catch (Exception $e) {
    error_log("Branches Error: " . $e->getMessage());
    Response::serverError();
}
?>
