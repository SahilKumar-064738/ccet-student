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

    $stmt = $db->prepare("
        SELECT u.*, b.name as branch_name, b.code as branch_code, y.year_number 
        FROM users u
        LEFT JOIN branches b ON u.branch_id = b.id
        LEFT JOIN years y ON u.year_id = y.id
        WHERE u.id = ?
    ");
    $stmt->execute([$user['id']]);
    $userData = $stmt->fetch();

    Response::success([
        'user' => [
            'id' => $userData['id'],
            'email' => $userData['email'],
            'name' => $userData['name'],
            'role' => $userData['role'],
            'branch_id' => $userData['branch_id'],
            'branch_name' => $userData['branch_name'],
            'branch_code' => $userData['branch_code'],
            'year_id' => $userData['year_id'],
            'year_number' => $userData['year_number']
        ]
    ]);

} catch (Exception $e) {
    Response::unauthorized();
}
?>
