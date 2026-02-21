<?php
/**
 * Admin — Audit Log
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();
requirePermission('view_audit_log');

// Purge old logs (older than 90 days)
purgeOldAuditLogs(90);

$filter_action = $_GET['action_filter'] ?? '';
$filter_entity = $_GET['entity_filter'] ?? '';
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

$logs = getAuditLog($perPage, $offset, $filter_action, $filter_entity);
$totalLogs = getAuditLogCount($filter_action, $filter_entity);
$totalPages = ceil($totalLogs / $perPage);

require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-history"></i> Audit Log</h1>
    <p>Track all administrative actions. Entries older than 90 days are automatically purged.</p>
</div>

<div class="admin-section">
    <!-- Filters -->
    <form method="GET" class="admin-filters" style="display:flex;gap:var(--space-md);margin-bottom:var(--space-lg);flex-wrap:wrap;">
        <input type="hidden" name="p" value="1">
        <div class="form-group" style="margin:0;">
            <input type="text" name="action_filter" class="form-control" placeholder="Filter by action..." value="<?php echo e($filter_action); ?>">
        </div>
        <div class="form-group" style="margin:0;">
            <input type="text" name="entity_filter" class="form-control" placeholder="Filter by entity..." value="<?php echo e($filter_entity); ?>">
        </div>
        <button type="submit" class="btn-admin btn-primary"><i class="fas fa-filter"></i> Filter</button>
        <?php if ($filter_action || $filter_entity): ?>
            <a href="<?php echo url('admin/audit-log.php'); ?>" class="btn-admin btn-outline">Clear</a>
        <?php endif; ?>
    </form>

    <?php if (empty($logs)): ?>
        <p class="empty-state">No audit log entries found.</p>
    <?php else: ?>
        <div class="admin-card">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Action</th>
                        <th>Entity</th>
                        <th>Details</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td style="white-space:nowrap;font-size:var(--text-sm);"><?php echo formatDate($log['created_at']); ?></td>
                        <td><span class="badge-status badge-unread"><?php echo e($log['action']); ?></span></td>
                        <td>
                            <?php if ($log['entity_type']): ?>
                                <?php echo e($log['entity_type']); ?>
                                <?php if ($log['entity_id']): ?>#<?php echo $log['entity_id']; ?><?php endif; ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;font-size:var(--text-sm);">
                            <?php
                            if ($log['details']) {
                                $details = json_decode($log['details'], true);
                                if (is_array($details)) {
                                    echo e(implode(', ', array_map(fn($k, $v) => "$k: $v", array_keys($details), array_values($details))));
                                } else {
                                    echo e($log['details']);
                                }
                            } else {
                                echo '—';
                            }
                            ?>
                        </td>
                        <td style="font-size:var(--text-sm);"><?php echo e($log['ip_address']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="admin-pagination" style="margin-top:var(--space-lg);display:flex;gap:var(--space-sm);justify-content:center;">
            <?php for ($i = 1; $i <= min($totalPages, 10); $i++): ?>
                <a href="?p=<?php echo $i; ?>&action_filter=<?php echo e($filter_action); ?>&entity_filter=<?php echo e($filter_entity); ?>"
                   class="btn-admin btn-sm <?php echo $i === $page ? 'btn-primary' : 'btn-outline'; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
