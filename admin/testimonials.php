<?php
/**
 * Admin — Testimonials Manager
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$db = getDB();

if (isset($_GET['delete']) && isset($_GET['csrf']) && verifyCSRFToken($_GET['csrf'])) {
    $db->prepare('DELETE FROM testimonials WHERE id = ?')->execute([(int)$_GET['delete']]);
    $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Testimonial deleted.'];
    redirect('/admin/testimonials.php');
}

$testimonials = $db->query('SELECT * FROM testimonials ORDER BY sort_order')->fetchAll();
$csrfToken = generateCSRFToken();

require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-quote-right"></i> Testimonials</h1>
    <a href="/admin/testimonial-edit.php" class="btn-admin btn-add"><i class="fas fa-plus"></i> Add Testimonial</a>
</div>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Customer</th>
                <th>Quote</th>
                <th>Visible</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($testimonials as $t): ?>
                <tr>
                    <td><strong><?php echo e($t['customer_name']); ?></strong></td>
                    <td style="max-width: 400px;"><?php echo e(substr($t['quote'], 0, 120)); ?><?php echo strlen($t['quote']) > 120 ? '...' : ''; ?></td>
                    <td><?php echo $t['is_visible'] ? '<span class="badge-status badge-active">Yes</span>' : '<span class="badge-status badge-inactive">No</span>'; ?></td>
                    <td class="actions">
                        <a href="/admin/testimonial-edit.php?id=<?php echo $t['id']; ?>" class="btn-icon" title="Edit"><i class="fas fa-edit"></i></a>
                        <a href="/admin/testimonials.php?delete=<?php echo $t['id']; ?>&csrf=<?php echo e($csrfToken); ?>" class="btn-icon btn-danger" title="Delete" onclick="return confirm('Delete this testimonial?')"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (empty($testimonials)): ?>
    <div class="empty-state"><p>No testimonials yet. <a href="/admin/testimonial-edit.php">Add your first testimonial</a>.</p></div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
