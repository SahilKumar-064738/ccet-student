<?php
class RateLimiter {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function check($identifier, $action, $maxAttempts, $windowMinutes = 60) {
        $windowStart = date('Y-m-d H:i:s', strtotime("-$windowMinutes minutes"));

        // Clean old records
        $stmt = $this->db->prepare("DELETE FROM rate_limits WHERE window_start < ?");
        $stmt->execute([date('Y-m-d H:i:s', strtotime('-24 hours'))]);

        // Get current count
        $stmt = $this->db->prepare("
            SELECT SUM(attempt_count) as total 
            FROM rate_limits 
            WHERE identifier = ? 
            AND action = ? 
            AND window_start >= ?
        ");
        $stmt->execute([$identifier, $action, $windowStart]);
        $result = $stmt->fetch();
        $currentCount = $result['total'] ?? 0;

        if ($currentCount >= $maxAttempts) {
            return false;
        }

        // Record attempt
        $stmt = $this->db->prepare("
            INSERT INTO rate_limits (identifier, action, attempt_count, window_start) 
            VALUES (?, ?, 1, NOW())
        ");
        $stmt->execute([$identifier, $action]);

        return true;
    }
}
?>
