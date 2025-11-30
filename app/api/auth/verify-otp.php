<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/utils/Response.php';
require_once __DIR__ . '/../../lib/utils/Validator.php';
require_once __DIR__ . '/../../lib/middleware/RateLimiter.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

$data = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');
$otp = trim($data['otp'] ?? '');

if (!Validator::email($email) || !Validator::required($otp)) {
    Response::error('Email and OTP are required');
}

try {
    $database = new Database();
    $db = $database->connect();

    // Get the latest valid OTP
    $stmt = $db->prepare("
        SELECT id, otp_code, expires_at, attempts, is_used 
        FROM otps 
        WHERE email = ? 
        AND is_used = 0 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $otpRecord = $stmt->fetch();

    if (!$otpRecord) {
        Response::error('Invalid or expired OTP');
    }

    // Check if OTP expired
    if (strtotime($otpRecord['expires_at']) < time()) {
        Response::error('OTP has expired. Please request a new one.');
    }

    // Check attempts
    if ($otpRecord['attempts'] >= OTP_MAX_ATTEMPTS) {
        Response::error('Maximum verification attempts exceeded. Please request a new OTP.');
    }

    // Verify OTP
    if ($otpRecord['otp_code'] !== $otp) {
        // Increment attempts
        $stmt = $db->prepare("UPDATE otps SET attempts = attempts + 1 WHERE id = ?");
        $stmt->execute([$otpRecord['id']]);
        
        $remainingAttempts = OTP_MAX_ATTEMPTS - ($otpRecord['attempts'] + 1);
        Response::error("Invalid OTP. $remainingAttempts attempts remaining.");
    }

    // Mark OTP as used
    $stmt = $db->prepare("UPDATE otps SET is_used = 1 WHERE id = ?");
    $stmt->execute([$otpRecord['id']]);

    // Get user details
    $stmt = $db->prepare("
        SELECT u.*, b.name as branch_name, b.code as branch_code, y.year_number 
        FROM users u
        LEFT JOIN branches b ON u.branch_id = b.id
        LEFT JOIN years y ON u.year_id = y.id
        WHERE u.email = ? AND u.is_active = 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        Response::error('User not found or inactive');
    }

    // Start session
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['branch_id'] = $user['branch_id'];
    $_SESSION['year_id'] = $user['year_id'];

    session_regenerate_id(true);

    Response::success([
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role'],
            'branch_id' => $user['branch_id'],
            'branch_name' => $user['branch_name'],
            'branch_code' => $user['branch_code'],
            'year_id' => $user['year_id'],
            'year_number' => $user['year_number']
        ]
    ], 'Login successful');

} catch (Exception $e) {
    error_log("OTP Verification Error: " . $e->getMessage());
    Response::serverError('An error occurred. Please try again.');
}
?>
