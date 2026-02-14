<?php
/**
 * Admin — Inquiries / Contact Submissions Viewer
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$db = getDB();
$type = $_GET['type'] ?? 'all';
$csrfToken = generateCSRFToken();

// Handle mark as read
if (isset($_GET['read']) && isset($_GET['table']) && isset($_GET['csrf']) && verifyCSRFToken($_GET['csrf'])) {
    $table = $_GET['table'] === 'orders' ? 'order_inquiries' : 'contact_submissions';
    $db->prepare("UPDATE $table SET is_read = 1 WHERE id = ?")->execute([(int)$_GET['read']]);
    redirect('admin/inquiries.php?type=' . e($type));
}

// Handle delete
if (isset($_GET['delete']) && isset($_GET['table']) && isset($_GET['csrf']) && verifyCSRFToken($_GET['csrf'])) {
    $table = $_GET['table'] === 'orders' ? 'order_inquiries' : 'contact_submissions';
    $db->prepare("DELETE FROM $table WHERE id = ?")->execute([(int)$_GET['delete']]);
    $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Entry deleted.'];
    redirect('admin/inquiries.php?type=' . e($type));
}

// Handle mark all as read
if (isset($_GET['markall']) && isset($_GET['csrf']) && verifyCSRFToken($_GET['csrf'])) {
    $db->exec('UPDATE contact_submissions SET is_read = 1 WHERE is_read = 0');
    $db->exec('UPDATE order_inquiries SET is_read = 1 WHERE is_read = 0');
    $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'All marked as read.'];
    redirect('admin/inquiries.php');
}

$contacts = $db->query('SELECT * FROM contact_submissions ORDER BY created_at DESC')->fetchAll();
$orders = $db->query('SELECT * FROM order_inquiries ORDER BY created_at DESC')->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-inbox"></i> Inquiries &amp; Messages</h1>
    <a href="<?php echo url('admin/inquiries.php?markall=1&csrf=' . e($csrfToken)); ?>" class="btn-admin btn-save" onclick="return confirm('Mark all as read?')">
        <i class="fas fa-check-double"></i> Mark All Read
    </a>
</div>

<!-- Tabs -->
<div class="admin-tabs">
    <a href="<?php echo url('admin/inquiries.php?type=all'); ?>" class="<?php echo $type === 'all' ? 'active' : ''; ?>">All</a>
    <a href="<?php echo url('admin/inquiries.php?type=contacts'); ?>" class="<?php echo $type === 'contacts' ? 'active' : ''; ?>">Contact Messages (<?php echo count($contacts); ?>)</a>
    <a href="<?php echo url('admin/inquiries.php?type=orders'); ?>" class="<?php echo $type === 'orders' ? 'active' : ''; ?>">Order Inquiries (<?php echo count($orders); ?>)</a>
</div>

<?php if ($type === 'all' || $type === 'contacts'): ?>
<div class="admin-section">
    <h2>Contact Messages</h2>
    <?php if (empty($contacts)): ?>
        <p class="empty-state">No contact messages yet.</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Message</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contacts as $c): ?>
                        <tr class="<?php echo !$c['is_read'] ? 'row-unread' : ''; ?>">
                            <td>
                                <?php if ($c['is_read']): ?>
                                    <span class="badge-status badge-read">Read</span>
                                <?php else: ?>
                                    <span class="badge-status badge-unread">New</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo e($c['name']); ?></strong></td>
                            <td><a href="mailto:<?php echo e($c['email']); ?>"><?php echo e($c['email']); ?></a></td>
                            <td><?php echo e($c['phone'] ?: '—'); ?></td>
                            <td style="max-width: 250px;"><?php echo e(substr($c['message'], 0, 80)); ?><?php echo strlen($c['message']) > 80 ? '...' : ''; ?></td>
                            <td><?php echo formatDate($c['created_at']); ?></td>
                            <td class="actions">
                                <a href="<?php echo url('admin/inquiry-view.php?type=contact&id=' . $c['id']); ?>" class="btn-icon" title="View"><i class="fas fa-eye"></i></a>
                                <?php if (!$c['is_read']): ?>
                                    <a href="<?php echo url('admin/inquiries.php?read=' . $c['id'] . '&table=contacts&csrf=' . e($csrfToken) . '&type=' . e($type)); ?>" class="btn-icon" title="Mark Read"><i class="fas fa-check"></i></a>
                                <?php endif; ?>
                                <a href="<?php echo url('admin/inquiries.php?delete=' . $c['id'] . '&table=contacts&csrf=' . e($csrfToken) . '&type=' . e($type)); ?>" class="btn-icon btn-danger" title="Delete" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($type === 'all' || $type === 'orders'): ?>
<div class="admin-section">
    <h2>Order Inquiries</h2>
    <?php if (empty($orders)): ?>
        <p class="empty-state">No order inquiries yet.</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Name</th>
                        <th>Service</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr class="<?php echo !$o['is_read'] ? 'row-unread' : ''; ?>">
                            <td>
                                <?php if ($o['is_read']): ?>
                                    <span class="badge-status badge-read">Read</span>
                                <?php else: ?>
                                    <span class="badge-status badge-unread">New</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo e($o['name']); ?></strong></td>
                            <td><?php echo e(ucfirst($o['service_type'])); ?></td>
                            <td><a href="mailto:<?php echo e($o['email']); ?>"><?php echo e($o['email']); ?></a></td>
                            <td><?php echo e($o['phone']); ?></td>
                            <td><?php echo formatDate($o['created_at']); ?></td>
                            <td class="actions">
                                <a href="<?php echo url('admin/inquiry-view.php?type=order&id=' . $o['id']); ?>" class="btn-icon" title="View"><i class="fas fa-eye"></i></a>
                                <?php if (!$o['is_read']): ?>
                                    <a href="<?php echo url('admin/inquiries.php?read=' . $o['id'] . '&table=orders&csrf=' . e($csrfToken) . '&type=' . e($type)); ?>" class="btn-icon" title="Mark Read"><i class="fas fa-check"></i></a>
                                <?php endif; ?>
                                <a href="<?php echo url('admin/inquiries.php?delete=' . $o['id'] . '&table=orders&csrf=' . e($csrfToken) . '&type=' . e($type)); ?>" class="btn-icon btn-danger" title="Delete" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
