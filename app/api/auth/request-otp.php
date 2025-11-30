<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/utils/Response.php';
require_once __DIR__ . '/../../lib/utils/Validator.php';
require_once __DIR__ . '/../../lib/utils/Mailer.php';
require_once __DIR__ . '/../../lib/middleware/RateLimiter.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

$data = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');

if (!Validator::email($email)) {
    Response::error('Invalid email address');
}

try {
    $database = new Database();
    $db = $database->connect();

    // Check if user exists and is active
    $stmt = $db->prepare("SELECT id, email, is_active FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !$user['is_active']) {
        Response::error('User not found or inactive');
    }

    // Rate limiting
    $rateLimiter = new RateLimiter($db);
    if (!$rateLimiter->check($email, 'otp_request', RATE_LIMIT_OTP_REQUEST, 60)) {
        Response::error('Too many OTP requests. Please try again later', 429);
    }

    // Generate OTP
    $otp = str_pad(random_int(0, 999999), OTP_LENGTH, '0', STR_PAD_LEFT);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));

    // Invalidate old OTPs
    $stmt = $db->prepare("UPDATE otps SET is_used = 1 WHERE email = ? AND is_used = 0");
    $stmt->execute([$email]);

    // Save new OTP
    $stmt = $db->prepare("
        INSERT INTO otps (email, otp_code, expires_at) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$email, $otp, $expiresAt]);

    // Send OTP via email
    if (Mailer::sendOTP($email, $otp)) {
        Response::success(
            ['email' => $email],
            'OTP sent successfully. Check your email.'
        );
    } else {
        Response::error('Failed to send OTP. Please try again.');
    }

} catch (Exception $e) {
    error_log("OTP Request Error: " . $e->getMessage());
    Response::serverError('An error occurred. Please try again.');
}
?>
