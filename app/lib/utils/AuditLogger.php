<?php
class AuditLogger {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function log($userId, $action, $entityType, $entityId = null, $details = null) {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $stmt = $this->db->prepare("
            INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $detailsJson = $details ? json_encode($details) : null;
        $stmt->execute([$userId, $action, $entityType, $entityId, $detailsJson, $ipAddress, $userAgent]);
    }
}
?>
