<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../lib/utils/Response.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

session_unset();
session_destroy();

Response::success(null, 'Logged out successfully');
?>
