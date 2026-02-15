<?php
/**
 * Audit Logging Module
 */

function logAudit(string $action, string $entityType = '', int $entityId = 0, array $details = []): void {
    try {
        $db = getDB();
        $userId = null;

        // Try to get admin user ID
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = $_SESSION['admin_id'] ?? $_SESSION['customer_id'] ?? null;

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        $stmt = $db->prepare('INSERT INTO audit_log (user_id, action, entity_type, entity_id, details, ip_address, created_at) VALUES (?, ?, ?, ?, ?, ?, datetime("now"))');
        $stmt->execute([
            $userId,
            $action,
            $entityType,
            $entityId ?: null,
            !empty($details) ? json_encode($details) : null,
            $ip,
        ]);
    } catch (\Exception $e) {
        error_log("Audit log error: " . $e->getMessage());
    }
}

function getAuditLog(int $limit = 100, int $offset = 0, string $action = '', string $entityType = ''): array {
    $db = getDB();
    $sql = 'SELECT * FROM audit_log WHERE 1=1';
    $params = [];

    if ($action) {
        $sql .= ' AND action = ?';
        $params[] = $action;
    }
    if ($entityType) {
        $sql .= ' AND entity_type = ?';
        $params[] = $entityType;
    }

    $sql .= ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getAuditLogCount(string $action = '', string $entityType = ''): int {
    $db = getDB();
    $sql = 'SELECT COUNT(*) FROM audit_log WHERE 1=1';
    $params = [];
    if ($action) { $sql .= ' AND action = ?'; $params[] = $action; }
    if ($entityType) { $sql .= ' AND entity_type = ?'; $params[] = $entityType; }
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function purgeOldAuditLogs(int $daysOld = 90): int {
    $db = getDB();
    $cutoff = date('Y-m-d H:i:s', strtotime("-$daysOld days"));
    $stmt = $db->prepare('DELETE FROM audit_log WHERE created_at < ?');
    $stmt->execute([$cutoff]);
    return $stmt->rowCount();
}
