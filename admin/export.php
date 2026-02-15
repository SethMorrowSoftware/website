<?php
/**
 * Admin — Data Export & Backup
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$db = getDB();
$csrfToken = generateCSRFToken();

// Handle export actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $exportType = $_POST['export_type'] ?? '';

    if ($exportType === 'orders_csv') {
        $dateFrom = $_POST['date_from'] ?? '';
        $dateTo = $_POST['date_to'] ?? '';

        $sql = "SELECT o.order_number, o.customer_name, o.customer_email, o.customer_phone, o.shipping_address, o.subtotal, o.tax, o.total, o.payment_method, o.payment_status, o.order_status, o.created_at FROM orders o WHERE 1=1";
        $params = [];
        if ($dateFrom) { $sql .= " AND date(o.created_at) >= ?"; $params[] = $dateFrom; }
        if ($dateTo) { $sql .= " AND date(o.created_at) <= ?"; $params[] = $dateTo; }
        $sql .= " ORDER BY o.created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="orders_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Order #', 'Customer', 'Email', 'Phone', 'Address', 'Subtotal', 'Tax', 'Total', 'Payment Method', 'Payment Status', 'Order Status', 'Date']);
        foreach ($rows as $row) {
            fputcsv($out, array_values($row));
        }
        fclose($out);
        exit;
    }

    if ($exportType === 'products_csv') {
        $rows = $db->query("SELECT p.name, p.slug, pc.name as category, p.description, p.price, p.unit, p.product_type, p.stock_quantity, p.is_available, p.is_visible FROM products p LEFT JOIN product_categories pc ON p.category_id = pc.id ORDER BY pc.sort_order, p.sort_order")->fetchAll();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="products_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Name', 'Slug', 'Category', 'Description', 'Price', 'Unit', 'Type', 'Stock', 'Available', 'Visible']);
        foreach ($rows as $row) {
            fputcsv($out, array_values($row));
        }
        fclose($out);
        exit;
    }

    if ($exportType === 'customers_csv') {
        $rows = $db->query("SELECT email, first_name, last_name, phone, default_shipping_address, created_at, last_login FROM customers ORDER BY created_at DESC")->fetchAll();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="customers_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Email', 'First Name', 'Last Name', 'Phone', 'Shipping Address', 'Joined', 'Last Login']);
        foreach ($rows as $row) {
            fputcsv($out, array_values($row));
        }
        fclose($out);
        exit;
    }

    if ($exportType === 'database_backup') {
        $dbFile = DB_PATH;
        if (file_exists($dbFile)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="database_backup_' . date('Y-m-d_His') . '.sqlite"');
            header('Content-Length: ' . filesize($dbFile));
            readfile($dbFile);
            exit;
        }
    }
}

require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-download"></i> Data Export & Backup</h1>
    <p>Export your data as CSV files or download a full database backup.</p>
</div>

<div class="admin-grid-2">
    <!-- Orders Export -->
    <div class="admin-section">
        <h2><i class="fas fa-shopping-bag"></i> Export Orders</h2>
        <div class="admin-card">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                <input type="hidden" name="export_type" value="orders_csv">
                <div class="form-row-2">
                    <div class="form-group">
                        <label>From Date</label>
                        <input type="date" name="date_from" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>To Date</label>
                        <input type="date" name="date_to" class="form-control">
                    </div>
                </div>
                <button type="submit" class="btn-admin btn-primary"><i class="fas fa-file-csv"></i> Download Orders CSV</button>
            </form>
        </div>
    </div>

    <!-- Products Export -->
    <div class="admin-section">
        <h2><i class="fas fa-box"></i> Export Products</h2>
        <div class="admin-card">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                <input type="hidden" name="export_type" value="products_csv">
                <p style="margin-bottom: var(--space-md); color: var(--color-gray-500);">Export all products with their categories, pricing, and stock information.</p>
                <button type="submit" class="btn-admin btn-primary"><i class="fas fa-file-csv"></i> Download Products CSV</button>
            </form>
        </div>
    </div>

    <!-- Customers Export -->
    <div class="admin-section">
        <h2><i class="fas fa-users"></i> Export Customers</h2>
        <div class="admin-card">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                <input type="hidden" name="export_type" value="customers_csv">
                <p style="margin-bottom: var(--space-md); color: var(--color-gray-500);">Export all registered customer accounts with contact information.</p>
                <button type="submit" class="btn-admin btn-primary"><i class="fas fa-file-csv"></i> Download Customers CSV</button>
            </form>
        </div>
    </div>

    <!-- Database Backup -->
    <div class="admin-section">
        <h2><i class="fas fa-database"></i> Database Backup</h2>
        <div class="admin-card">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                <input type="hidden" name="export_type" value="database_backup">
                <p style="margin-bottom: var(--space-md); color: var(--color-gray-500);">Download the entire SQLite database file. This includes all data, settings, and configurations.</p>
                <button type="submit" class="btn-admin btn-warning"><i class="fas fa-download"></i> Download Database Backup</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
