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

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $postAction = $_POST['inquiry_action'] ?? '';

    if ($postAction === 'mark_read' && isset($_POST['id']) && isset($_POST['table'])) {
        $table = $_POST['table'] === 'orders' ? 'order_inquiries' : 'contact_submissions';
        $db->prepare("UPDATE $table SET is_read = 1 WHERE id = ?")->execute([(int)$_POST['id']]);
        redirect('admin/inquiries.php?type=' . urlencode($type));
    }

    if ($postAction === 'delete' && isset($_POST['id']) && isset($_POST['table'])) {
        $table = $_POST['table'] === 'orders' ? 'order_inquiries' : 'contact_submissions';
        $db->prepare("DELETE FROM $table WHERE id = ?")->execute([(int)$_POST['id']]);
        $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Entry deleted.'];
        redirect('admin/inquiries.php?type=' . urlencode($type));
    }

    if ($postAction === 'mark_all_read') {
        $db->exec('UPDATE contact_submissions SET is_read = 1 WHERE is_read = 0');
        $db->exec('UPDATE order_inquiries SET is_read = 1 WHERE is_read = 0');
        $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'All marked as read.'];
        redirect('admin/inquiries.php');
    }
}

$contacts = $db->query('SELECT * FROM contact_submissions ORDER BY created_at DESC')->fetchAll();
$orders = $db->query('SELECT * FROM order_inquiries ORDER BY created_at DESC')->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-inbox"></i> Inquiries &amp; Messages</h1>
    <form method="POST" style="display:inline;" onsubmit="return confirm('Mark all as read?')">
        <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
        <input type="hidden" name="inquiry_action" value="mark_all_read">
        <button type="submit" class="btn-admin btn-save"><i class="fas fa-check-double"></i> Mark All Read</button>
    </form>
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
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                        <input type="hidden" name="inquiry_action" value="mark_read">
                                        <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                        <input type="hidden" name="table" value="contacts">
                                        <button type="submit" class="btn-icon" title="Mark Read"><i class="fas fa-check"></i></button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete?')">
                                    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                    <input type="hidden" name="inquiry_action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                    <input type="hidden" name="table" value="contacts">
                                    <button type="submit" class="btn-icon btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                </form>
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
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                        <input type="hidden" name="inquiry_action" value="mark_read">
                                        <input type="hidden" name="id" value="<?php echo $o['id']; ?>">
                                        <input type="hidden" name="table" value="orders">
                                        <button type="submit" class="btn-icon" title="Mark Read"><i class="fas fa-check"></i></button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete?')">
                                    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                    <input type="hidden" name="inquiry_action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $o['id']; ?>">
                                    <input type="hidden" name="table" value="orders">
                                    <button type="submit" class="btn-icon btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                </form>
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
