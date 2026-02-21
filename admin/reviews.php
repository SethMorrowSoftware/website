<?php
/**
 * Admin — Reviews Moderation
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();
requirePermission('manage_reviews');

$db = getDB();
$csrfToken = generateCSRFToken();

// Handle approve action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve']) && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $reviewId = (int)$_POST['approve'];
    approveReview($reviewId);
    $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Review approved.'];
    $filter = $_GET['filter'] ?? 'all';
    redirect('admin/reviews.php' . ($filter !== 'all' ? '?filter=' . urlencode($filter) : ''));
}

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete']) && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $reviewId = (int)$_POST['delete'];
    deleteReview($reviewId);
    $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Review deleted.'];
    $filter = $_GET['filter'] ?? 'all';
    redirect('admin/reviews.php' . ($filter !== 'all' ? '?filter=' . urlencode($filter) : ''));
}

$filter = $_GET['filter'] ?? 'all';
if (!in_array($filter, ['all', 'pending', 'approved'])) {
    $filter = 'all';
}
$reviews = getAllReviewsAdmin($filter);

require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-star"></i> Reviews</h1>
</div>

<!-- Filter Tabs -->
<div class="admin-tabs" style="margin-bottom: 1.5rem;">
    <a href="<?php echo url('admin/reviews.php'); ?>" class="admin-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
        All
    </a>
    <a href="<?php echo url('admin/reviews.php?filter=pending'); ?>" class="admin-tab <?php echo $filter === 'pending' ? 'active' : ''; ?>">
        Pending
    </a>
    <a href="<?php echo url('admin/reviews.php?filter=approved'); ?>" class="admin-tab <?php echo $filter === 'approved' ? 'active' : ''; ?>">
        Approved
    </a>
</div>

<style>
.admin-tabs {
    display: flex;
    gap: 0;
    border-bottom: 2px solid #dee2e6;
}
.admin-tab {
    padding: 0.6rem 1.25rem;
    text-decoration: none;
    color: #666;
    font-weight: 500;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: color 0.2s, border-color 0.2s;
}
.admin-tab:hover {
    color: #333;
}
.admin-tab.active {
    color: var(--admin-primary, #4a90d9);
    border-bottom-color: var(--admin-primary, #4a90d9);
}
.review-stars { color: #f5a623; font-size: 0.85rem; }
.review-stars .far { color: #ccc; }
</style>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Customer</th>
                <th>Rating</th>
                <th>Title</th>
                <th>Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reviews as $review): ?>
                <tr>
                    <td><?php echo e($review['product_name'] ?? 'Unknown'); ?></td>
                    <td>
                        <strong><?php echo e($review['customer_name']); ?></strong><br>
                        <small style="color:#888;"><?php echo e($review['customer_email']); ?></small>
                        <?php if ($review['is_verified_purchase']): ?>
                            <br><small style="color:#28a745;"><i class="fas fa-check-circle"></i> Verified</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="review-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="<?php echo $i <= $review['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                            <?php endfor; ?>
                        </div>
                    </td>
                    <td style="max-width: 250px;">
                        <?php if ($review['title']): ?>
                            <strong><?php echo e($review['title']); ?></strong><br>
                        <?php endif; ?>
                        <small><?php echo e(substr($review['body'] ?? '', 0, 100)); ?><?php echo strlen($review['body'] ?? '') > 100 ? '...' : ''; ?></small>
                    </td>
                    <td><?php echo date('M j, Y', strtotime($review['created_at'])); ?></td>
                    <td>
                        <?php if ($review['is_approved']): ?>
                            <span class="badge-status badge-active">Approved</span>
                        <?php else: ?>
                            <span class="badge-status badge-inactive">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td class="actions">
                        <?php if (!$review['is_approved']): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                <input type="hidden" name="approve" value="<?php echo $review['id']; ?>">
                                <button type="submit" class="btn-icon" title="Approve" style="color:#28a745;"><i class="fas fa-check"></i></button>
                            </form>
                        <?php endif; ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this review?')">
                            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                            <input type="hidden" name="delete" value="<?php echo $review['id']; ?>">
                            <button type="submit" class="btn-icon btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (empty($reviews)): ?>
    <div class="empty-state">
        <p>No <?php echo $filter !== 'all' ? e($filter) . ' ' : ''; ?>reviews found.</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
