<?php
require_once __DIR__ . '/../app/config/database.php';

$db = new Database();
$conn = $db->connect();

echo "=== CCET Student Vault - Super Admin Setup ===\n\n";

$email = readline("Enter Super Admin Email: ");
$name = readline("Enter Super Admin Name: ");

try {
    $stmt = $conn->prepare("
        INSERT INTO users (email, name, role, branch_id, year_id) 
        VALUES (?, ?, 'super_admin', NULL, NULL)
        ON DUPLICATE KEY UPDATE name = ?, role = 'super_admin'
    ");
    $stmt->execute([$email, $name, $name]);
    
    echo "\n✅ Super Admin created successfully!\n";
    echo "Email: $email\n";
    echo "Use OTP login to access the system.\n";
} catch (PDOException $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
}
?>
