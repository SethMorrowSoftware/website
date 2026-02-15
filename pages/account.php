<?php
/**
 * Customer Account Dashboard
 */

if (!isCustomerLoggedIn()) {
    $_SESSION['flash_message'] = 'Please sign in to view your account.';
    $_SESSION['flash_type'] = 'info';
    redirect('index.php?page=login');
}

$customer = getLoggedInCustomer();
if (!$customer) {
    logoutCustomer();
    redirect('index.php?page=login');
}

$orders = getCustomerOrders($customer['id']);
$csrfToken = generateCSRFToken();

// Handle profile update
$activeTab = $_GET['tab'] ?? 'orders';
?>

<!-- Breadcrumb -->
<div class="container">
    <div class="breadcrumb">
        <a href="<?php echo url('/'); ?>">Home</a>
        <span>/</span>
        <span class="current">My Account</span>
    </div>
</div>

<section class="section">
    <div class="container">
        <div class="account-header">
            <h1><i class="fas fa-user-circle"></i> Welcome, <?php echo e($customer['first_name']); ?>!</h1>
            <form method="POST" action="<?php echo url('index.php'); ?>" style="display:inline;">
                <input type="hidden" name="action" value="customer_logout">
                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                <button type="submit" class="btn btn-outline btn-sm"><i class="fas fa-sign-out-alt"></i> Sign Out</button>
            </form>
        </div>

        <!-- Account Tabs -->
        <div class="account-tabs">
            <a href="<?php echo url('index.php?page=account&tab=orders'); ?>" class="account-tab <?php echo $activeTab === 'orders' ? 'active' : ''; ?>">
                <i class="fas fa-shopping-bag"></i> My Orders
            </a>
            <a href="<?php echo url('index.php?page=account&tab=profile'); ?>" class="account-tab <?php echo $activeTab === 'profile' ? 'active' : ''; ?>">
                <i class="fas fa-user-edit"></i> Profile
            </a>
            <a href="<?php echo url('index.php?page=account&tab=password'); ?>" class="account-tab <?php echo $activeTab === 'password' ? 'active' : ''; ?>">
                <i class="fas fa-lock"></i> Password
            </a>
            <?php if (isFeatureEnabled('wishlists')): ?>
            <a href="<?php echo url('index.php?page=wishlist'); ?>" class="account-tab">
                <i class="fas fa-heart"></i> Wishlist
            </a>
            <?php endif; ?>
        </div>

        <!-- Orders Tab -->
        <?php if ($activeTab === 'orders'): ?>
            <div class="account-section">
                <h2>Order History</h2>
                <?php if (empty($orders)): ?>
                    <div class="empty-state">
                        <i class="fas fa-shopping-bag" style="font-size: 3rem; color: var(--color-gray-400); margin-bottom: var(--space-lg);"></i>
                        <h3>No orders yet</h3>
                        <p>When you place an order, it will appear here.</p>
                        <?php if (isFeatureEnabled('catalog')): ?>
                            <a href="<?php echo url('index.php?page=catalog'); ?>" class="btn btn-primary"><i class="fas fa-shopping-cart"></i> Start Shopping</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="orders-table-container">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><strong><?php echo e($order['order_number']); ?></strong></td>
                                        <td><?php echo formatDate($order['created_at']); ?></td>
                                        <td><?php echo formatCurrency($order['total']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo e($order['payment_status']); ?>">
                                                <?php echo e(ucfirst($order['payment_status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo e($order['order_status']); ?>">
                                                <?php echo e(ucfirst($order['order_status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?php echo url('index.php?page=order-complete&order=' . e($order['order_number'])); ?>" class="btn btn-sm btn-outline">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        <!-- Profile Tab -->
        <?php elseif ($activeTab === 'profile'): ?>
            <div class="account-section">
                <h2>Profile Information</h2>
                <form method="POST" action="<?php echo url('index.php'); ?>" class="auth-form">
                    <input type="hidden" name="action" value="update_profile">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" class="form-control" value="<?php echo e($customer['first_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" class="form-control" value="<?php echo e($customer['last_name']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" class="form-control" value="<?php echo e($customer['email']); ?>" disabled>
                        <small class="form-help">Email address cannot be changed.</small>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo e($customer['phone'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="default_shipping_address">Default Shipping Address</label>
                        <textarea id="default_shipping_address" name="default_shipping_address" class="form-control" rows="3" placeholder="Street, City, State, ZIP"><?php echo e($customer['default_shipping_address'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                </form>
            </div>

        <!-- Password Tab -->
        <?php elseif ($activeTab === 'password'): ?>
            <div class="account-section">
                <h2>Change Password</h2>
                <form method="POST" action="<?php echo url('index.php'); ?>" class="auth-form">
                    <input type="hidden" name="action" value="change_customer_password">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">

                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" required minlength="8">
                    </div>

                    <div class="form-group">
                        <label for="new_password_confirm">Confirm New Password</label>
                        <input type="password" id="new_password_confirm" name="new_password_confirm" class="form-control" required minlength="8">
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="fas fa-lock"></i> Update Password</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</section>
