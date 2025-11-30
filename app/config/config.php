<?php
// Application Configuration
define('APP_NAME', 'CCET Student Vault');
define('APP_URL', 'http://localhost:8080');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_MIME_TYPES', ['application/pdf']);
define('ALLOWED_EXTENSIONS', ['pdf']);

// OTP Configuration
define('OTP_EXPIRY_MINUTES', 10);
define('OTP_LENGTH', 6);
define('OTP_MAX_ATTEMPTS', 5);

// Rate Limiting
define('RATE_LIMIT_OTP_REQUEST', 3); // per hour
define('RATE_LIMIT_OTP_VERIFY', 5); // per OTP

// Session Configuration
define('SESSION_LIFETIME', 28800); // 8 hours
define('SESSION_NAME', 'CCET_VAULT_SESSION');

// Email Configuration (Update with real SMTP)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_FROM_EMAIL', 'noreply@ccet.ac.in');
define('SMTP_FROM_NAME', 'CCET Student Vault');

// Security
define('SECURE_COOKIE', false); // Set to true in production with HTTPS
define('CSRF_TOKEN_LENGTH', 32);

// Pagination
define('ITEMS_PER_PAGE', 20);

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Error Reporting (disable in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Create upload directory if not exists
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}
?>
