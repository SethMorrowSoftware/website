<?php
/**
 * Admin — View Single Inquiry
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();
requirePermission('manage_orders');

$db = getDB();
$type = $_GET['type'] ?? 'contact';
$id = (int)($_GET['id'] ?? 0);
$item = null;

if ($type === 'contact') {
    $stmt = $db->prepare('SELECT * FROM contact_submissions WHERE id = ?');
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    // Mark as read
    if ($item && !$item['is_read']) {
        $db->prepare('UPDATE contact_submissions SET is_read = 1 WHERE id = ?')->execute([$id]);
    }
} elseif ($type === 'order') {
    $stmt = $db->prepare('SELECT * FROM order_inquiries WHERE id = ?');
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if ($item && !$item['is_read']) {
        $db->prepare('UPDATE order_inquiries SET is_read = 1 WHERE id = ?')->execute([$id]);
    }
}

require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-envelope-open-text"></i> <?php echo $type === 'order' ? 'Order Inquiry' : 'Contact Message'; ?></h1>
    <a href="<?php echo url('admin/inquiries.php'); ?>" class="btn-admin btn-back"><i class="fas fa-arrow-left"></i> Back to Inquiries</a>
</div>

<?php if (!$item): ?>
    <div class="empty-state"><p>Inquiry not found.</p></div>
<?php else: ?>
    <div class="admin-form" style="max-width: 800px;">
        <div class="form-section">
            <div class="detail-grid">
                <div class="detail-row">
                    <div class="detail-label">Name</div>
                    <div class="detail-value"><?php echo e($item['name']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Email</div>
                    <div class="detail-value"><a href="mailto:<?php echo e($item['email']); ?>"><?php echo e($item['email']); ?></a></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Phone</div>
                    <div class="detail-value">
                        <?php if ($item['phone']): ?>
                            <a href="tel:<?php echo e($item['phone']); ?>"><?php echo e($item['phone']); ?></a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Date</div>
                    <div class="detail-value"><?php echo formatDate($item['created_at']); ?></div>
                </div>

                <?php if ($type === 'order'): ?>
                    <div class="detail-row">
                        <div class="detail-label">Service Type</div>
                        <div class="detail-value"><strong><?php echo e(ucfirst($item['service_type'])); ?></strong></div>
                    </div>
                    <?php if ($item['delivery_address']): ?>
                        <div class="detail-row">
                            <div class="detail-label">Delivery Address</div>
                            <div class="detail-value"><?php echo e($item['delivery_address']); ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if ($item['preferred_date']): ?>
                        <div class="detail-row">
                            <div class="detail-label">Preferred Date</div>
                            <div class="detail-value"><?php echo e($item['preferred_date']); ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if ($item['product_details']): ?>
                        <div class="detail-row">
                            <div class="detail-label">Product Details</div>
                            <div class="detail-value">
                                <?php
                                $details = json_decode($item['product_details'], true);
                                if (is_array($details)) {
                                    foreach ($details as $key => $val) {
                                        if ($val && !is_array($val)) {
                                            echo '<div><strong>' . e(ucwords(str_replace('_', ' ', $key))) . ':</strong> ' . e($val) . '</div>';
                                        }
                                    }
                                } else {
                                    echo e($item['product_details']);
                                }
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($item['notes']): ?>
                        <div class="detail-row">
                            <div class="detail-label">Notes</div>
                            <div class="detail-value"><?php echo nl2br(e($item['notes'])); ?></div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="detail-row">
                        <div class="detail-label">Message</div>
                        <div class="detail-value" style="white-space: pre-wrap;"><?php echo e($item['message']); ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-actions">
            <a href="mailto:<?php echo e($item['email']); ?>?subject=Re: Your inquiry" class="btn-admin btn-save">
                <i class="fas fa-reply"></i> Reply via Email
            </a>
            <?php if ($item['phone']): ?>
                <a href="tel:<?php echo e($item['phone']); ?>" class="btn-admin btn-back">
                    <i class="fas fa-phone"></i> Call
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
