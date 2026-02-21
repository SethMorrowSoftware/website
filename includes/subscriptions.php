<?php
/**
 * Subscription & Recurring Billing Module
 *
 * Provides:
 * - Subscription plan management
 * - Customer subscription lifecycle (create, pause, resume, cancel)
 * - Billing period calculations
 * - Invoice generation and tracking
 * - Dunning management (retry failed charges)
 */

// ============================================================
// Subscription Plans
// ============================================================

/**
 * Get all active subscription plans.
 */
function getSubscriptionPlans(bool $activeOnly = true): array {
    $db = getDB();
    $sql = "SELECT sp.*, p.name as product_name
            FROM subscription_plans sp
            LEFT JOIN products p ON sp.product_id = p.id";
    if ($activeOnly) $sql .= " WHERE sp.is_active = 1";
    $sql .= " ORDER BY sp.price ASC";
    return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get a subscription plan by ID.
 */
function getSubscriptionPlan(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT sp.*, p.name as product_name FROM subscription_plans sp LEFT JOIN products p ON sp.product_id = p.id WHERE sp.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * Create a subscription plan.
 */
function createSubscriptionPlan(array $data): ?int {
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO subscription_plans (product_id, name, description, price, billing_interval, interval_count, trial_days, setup_fee, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['product_id'] ?? null,
        $data['name'],
        $data['description'] ?? '',
        $data['price'],
        $data['billing_interval'] ?? 'monthly',
        $data['interval_count'] ?? 1,
        $data['trial_days'] ?? 0,
        $data['setup_fee'] ?? 0,
        isset($data['is_active']) ? (int)$data['is_active'] : 1,
    ]);
    return (int)$db->lastInsertId() ?: null;
}

/**
 * Update a subscription plan.
 */
function updateSubscriptionPlan(int $id, array $data): bool {
    $db = getDB();
    $stmt = $db->prepare("
        UPDATE subscription_plans SET
            name = ?, description = ?, price = ?, billing_interval = ?,
            interval_count = ?, trial_days = ?, setup_fee = ?, is_active = ?
        WHERE id = ?
    ");
    return $stmt->execute([
        $data['name'],
        $data['description'] ?? '',
        $data['price'],
        $data['billing_interval'] ?? 'monthly',
        $data['interval_count'] ?? 1,
        $data['trial_days'] ?? 0,
        $data['setup_fee'] ?? 0,
        isset($data['is_active']) ? (int)$data['is_active'] : 1,
        $id,
    ]);
}

// ============================================================
// Customer Subscriptions
// ============================================================

/**
 * Create a subscription for a customer.
 */
function createSubscription(int $customerId, int $planId, ?string $paymentMethod = null, ?string $paymentToken = null): ?int {
    $plan = getSubscriptionPlan($planId);
    if (!$plan) return null;

    $now = new DateTime();
    $periodEnd = calculatePeriodEnd($now, $plan['billing_interval'], (int)$plan['interval_count']);

    $trialEndsAt = null;
    $status = 'active';
    $nextBilling = $periodEnd->format('Y-m-d H:i:s');

    if ($plan['trial_days'] > 0) {
        $trialEndsAt = (clone $now)->modify('+' . $plan['trial_days'] . ' days')->format('Y-m-d H:i:s');
        $status = 'trialing';
        $nextBilling = $trialEndsAt;
    }

    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO subscriptions (customer_id, plan_id, status, current_period_start, current_period_end,
            next_billing_date, payment_method, payment_token, trial_ends_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $customerId, $planId, $status,
        $now->format('Y-m-d H:i:s'),
        $periodEnd->format('Y-m-d H:i:s'),
        $nextBilling,
        $paymentMethod, $paymentToken, $trialEndsAt,
    ]);

    return (int)$db->lastInsertId() ?: null;
}

/**
 * Get a subscription by ID.
 */
function getSubscription(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT s.*, sp.name as plan_name, sp.price, sp.billing_interval, sp.interval_count,
               c.first_name, c.last_name, c.email as customer_email
        FROM subscriptions s
        JOIN subscription_plans sp ON s.plan_id = sp.id
        JOIN customers c ON s.customer_id = c.id
        WHERE s.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * Get all subscriptions for a customer.
 */
function getCustomerSubscriptions(int $customerId): array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT s.*, sp.name as plan_name, sp.price, sp.billing_interval
        FROM subscriptions s
        JOIN subscription_plans sp ON s.plan_id = sp.id
        WHERE s.customer_id = ?
        ORDER BY s.created_at DESC
    ");
    $stmt->execute([$customerId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get all subscriptions (admin view).
 */
function getAllSubscriptions(?string $statusFilter = null): array {
    $db = getDB();
    $sql = "
        SELECT s.*, sp.name as plan_name, sp.price, sp.billing_interval,
               c.first_name, c.last_name, c.email as customer_email
        FROM subscriptions s
        JOIN subscription_plans sp ON s.plan_id = sp.id
        JOIN customers c ON s.customer_id = c.id
    ";
    $params = [];
    if ($statusFilter) {
        $sql .= " WHERE s.status = ?";
        $params[] = $statusFilter;
    }
    $sql .= " ORDER BY s.created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Pause a subscription.
 */
function pauseSubscription(int $id): bool {
    $db = getDB();
    return $db->prepare("UPDATE subscriptions SET status = 'paused', updated_at = NOW() WHERE id = ? AND status IN ('active','trialing')")->execute([$id]);
}

/**
 * Resume a paused subscription.
 */
function resumeSubscription(int $id): bool {
    $sub = getSubscription($id);
    if (!$sub || $sub['status'] !== 'paused') return false;

    $db = getDB();
    $now = new DateTime();
    $periodEnd = calculatePeriodEnd($now, $sub['billing_interval'], (int)$sub['interval_count']);

    return $db->prepare("
        UPDATE subscriptions SET status = 'active',
            current_period_start = ?, current_period_end = ?,
            next_billing_date = ?, updated_at = NOW()
        WHERE id = ?
    ")->execute([
        $now->format('Y-m-d H:i:s'),
        $periodEnd->format('Y-m-d H:i:s'),
        $periodEnd->format('Y-m-d H:i:s'),
        $id,
    ]);
}

/**
 * Cancel a subscription.
 */
function cancelSubscription(int $id, string $reason = ''): bool {
    $db = getDB();
    return $db->prepare("
        UPDATE subscriptions SET status = 'cancelled', cancelled_at = NOW(), cancel_reason = ?, updated_at = NOW()
        WHERE id = ? AND status NOT IN ('cancelled','expired')
    ")->execute([$reason, $id]);
}

// ============================================================
// Billing & Invoices
// ============================================================

/**
 * Calculate the end of a billing period.
 */
function calculatePeriodEnd(DateTime $start, string $interval, int $count = 1): DateTime {
    $end = clone $start;
    $intervalMap = [
        'weekly' => 'weeks',
        'monthly' => 'months',
        'quarterly' => 'months',
        'yearly' => 'years',
    ];
    $unit = $intervalMap[$interval] ?? 'months';
    $multiplier = $interval === 'quarterly' ? 3 * $count : $count;
    $end->modify("+{$multiplier} {$unit}");
    return $end;
}

/**
 * Get billing format label for an interval.
 */
function getBillingLabel(string $interval, int $count = 1): string {
    if ($count === 1) {
        $labels = ['weekly' => '/week', 'monthly' => '/month', 'quarterly' => '/quarter', 'yearly' => '/year'];
        return $labels[$interval] ?? '/' . $interval;
    }
    $units = ['weekly' => 'weeks', 'monthly' => 'months', 'quarterly' => 'quarters', 'yearly' => 'years'];
    return ' every ' . $count . ' ' . ($units[$interval] ?? $interval);
}

/**
 * Get invoices for a subscription.
 */
function getSubscriptionInvoices(int $subscriptionId): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM subscription_invoices WHERE subscription_id = ? ORDER BY created_at DESC");
    $stmt->execute([$subscriptionId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Create an invoice for a subscription renewal.
 */
function createSubscriptionInvoice(int $subscriptionId, float $amount, string $periodStart, string $periodEnd): ?int {
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO subscription_invoices (subscription_id, amount, billing_period_start, billing_period_end)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$subscriptionId, $amount, $periodStart, $periodEnd]);
    return (int)$db->lastInsertId() ?: null;
}

/**
 * Mark an invoice as paid.
 */
function markInvoicePaid(int $invoiceId, string $paymentId = ''): bool {
    $db = getDB();
    return $db->prepare("UPDATE subscription_invoices SET status = 'paid', payment_id = ?, paid_at = NOW() WHERE id = ?")->execute([$paymentId, $invoiceId]);
}

/**
 * Mark an invoice as failed.
 */
function markInvoiceFailed(int $invoiceId): bool {
    $db = getDB();
    return $db->prepare("UPDATE subscription_invoices SET status = 'failed', attempt_count = attempt_count + 1, last_attempt_at = NOW() WHERE id = ?")->execute([$invoiceId]);
}

/**
 * Process due subscriptions (cron job).
 *
 * For each subscription due for renewal:
 * 1. Create an invoice
 * 2. Attempt charge (stub — actual payment handled by payment gateway plugin)
 * 3. Advance the billing period on success
 * 4. Mark as past_due on failure
 */
function processSubscriptionRenewals(): array {
    $db = getDB();
    $results = ['processed' => 0, 'renewed' => 0, 'failed' => 0];

    $due = $db->query("
        SELECT s.*, sp.price, sp.billing_interval, sp.interval_count
        FROM subscriptions s
        JOIN subscription_plans sp ON s.plan_id = sp.id
        WHERE s.status IN ('active','past_due')
          AND s.next_billing_date <= NOW()
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($due as $sub) {
        $results['processed']++;

        $now = new DateTime();
        $periodEnd = calculatePeriodEnd($now, $sub['billing_interval'], (int)$sub['interval_count']);

        // Create invoice
        $invoiceId = createSubscriptionInvoice(
            $sub['id'],
            (float)$sub['price'],
            $now->format('Y-m-d H:i:s'),
            $periodEnd->format('Y-m-d H:i:s')
        );

        // Payment processing hook (plugins can handle actual payment)
        $paymentSuccess = false;
        if (function_exists('do_action')) {
            // Plugins listen to 'process_subscription_payment' and set result
            $paymentResult = apply_filters('process_subscription_payment', null, $sub, $invoiceId);
            $paymentSuccess = $paymentResult === true;
        }

        if ($paymentSuccess && $invoiceId) {
            markInvoicePaid($invoiceId);
            // Advance period
            $db->prepare("
                UPDATE subscriptions SET
                    status = 'active',
                    current_period_start = ?,
                    current_period_end = ?,
                    next_billing_date = ?,
                    updated_at = NOW()
                WHERE id = ?
            ")->execute([
                $now->format('Y-m-d H:i:s'),
                $periodEnd->format('Y-m-d H:i:s'),
                $periodEnd->format('Y-m-d H:i:s'),
                $sub['id'],
            ]);
            $results['renewed']++;
        } else {
            if ($invoiceId) markInvoiceFailed($invoiceId);
            // Move to past_due after first failure, expire after 3 failures
            $failedInvoices = $db->prepare("SELECT COUNT(*) FROM subscription_invoices WHERE subscription_id = ? AND status = 'failed'");
            $failedInvoices->execute([$sub['id']]);
            $failCount = (int)$failedInvoices->fetchColumn();

            if ($failCount >= 3) {
                $db->prepare("UPDATE subscriptions SET status = 'expired', updated_at = NOW() WHERE id = ?")->execute([$sub['id']]);
            } else {
                $db->prepare("UPDATE subscriptions SET status = 'past_due', updated_at = NOW() WHERE id = ?")->execute([$sub['id']]);
            }
            $results['failed']++;
        }
    }

    return $results;
}

/**
 * Get subscription summary stats (admin dashboard).
 */
function getSubscriptionStats(): array {
    try {
        $db = getDB();
        $active = $db->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'active'")->fetchColumn();
        $trialing = $db->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'trialing'")->fetchColumn();
        $pastDue = $db->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'past_due'")->fetchColumn();
        $mrr = $db->query("
            SELECT COALESCE(SUM(
                CASE sp.billing_interval
                    WHEN 'monthly' THEN sp.price
                    WHEN 'weekly' THEN sp.price * 4.33
                    WHEN 'quarterly' THEN sp.price / 3
                    WHEN 'yearly' THEN sp.price / 12
                END
            ), 0)
            FROM subscriptions s
            JOIN subscription_plans sp ON s.plan_id = sp.id
            WHERE s.status IN ('active','trialing')
        ")->fetchColumn();
        return [
            'active' => (int)$active,
            'trialing' => (int)$trialing,
            'past_due' => (int)$pastDue,
            'mrr' => round((float)$mrr, 2),
        ];
    } catch (Exception $e) {
        return ['active' => 0, 'trialing' => 0, 'past_due' => 0, 'mrr' => 0];
    }
}
