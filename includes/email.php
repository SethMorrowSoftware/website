<?php
/**
 * Email Module — SMTP support with mail() fallback
 */

/**
 * Send an email using SMTP if configured, otherwise fallback to mail()
 */
function sendEmail(string $to, string $subject, string $htmlBody, string $fromName = '', string $fromEmail = ''): bool {
    $smtpHost = getSetting('smtp_host');
    $smtpPort = (int)getSetting('smtp_port', '587');
    $smtpUser = getSetting('smtp_username');
    $smtpPass = getSetting('smtp_password');
    $smtpEncryption = getSetting('smtp_encryption', 'tls');

    if (!$fromName) $fromName = getSetting('company_name', 'Website');
    if (!$fromEmail) $fromEmail = getSetting('smtp_from_email', getSetting('contact_email', 'noreply@localhost'));

    // Use SMTP if configured
    if ($smtpHost && $smtpUser) {
        return sendSmtpEmail($smtpHost, $smtpPort, $smtpUser, $smtpPass, $smtpEncryption, $fromName, $fromEmail, $to, $subject, $htmlBody);
    }

    // Fallback to PHP mail()
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: $fromName <$fromEmail>\r\n";
    $headers .= "Reply-To: $fromEmail\r\n";

    return @mail($to, $subject, $htmlBody, $headers);
}

/**
 * Send email via SMTP socket connection
 */
function sendSmtpEmail(string $host, int $port, string $user, string $pass, string $encryption, string $fromName, string $fromEmail, string $to, string $subject, string $htmlBody): bool {
    try {
        $timeout = 10;

        // Connect
        if ($encryption === 'ssl') {
            $socket = @fsockopen("ssl://$host", $port, $errno, $errstr, $timeout);
        } else {
            $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
        }

        if (!$socket) {
            error_log("SMTP connection failed: $errstr ($errno)");
            return false;
        }

        stream_set_timeout($socket, $timeout);

        // Read greeting
        $response = smtpGetResponse($socket);
        if (substr($response, 0, 3) !== '220') {
            fclose($socket);
            return false;
        }

        // EHLO
        smtpSend($socket, "EHLO " . gethostname());
        $ehloResponse = smtpGetResponse($socket);

        // STARTTLS if needed
        if ($encryption === 'tls' && strpos($ehloResponse, 'STARTTLS') !== false) {
            smtpSend($socket, "STARTTLS");
            $response = smtpGetResponse($socket);
            if (substr($response, 0, 3) !== '220') {
                fclose($socket);
                return false;
            }

            $crypto = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT);
            if (!$crypto) {
                fclose($socket);
                return false;
            }

            // Re-EHLO after STARTTLS
            smtpSend($socket, "EHLO " . gethostname());
            smtpGetResponse($socket);
        }

        // AUTH LOGIN
        smtpSend($socket, "AUTH LOGIN");
        $response = smtpGetResponse($socket);
        if (substr($response, 0, 3) !== '334') {
            fclose($socket);
            return false;
        }

        smtpSend($socket, base64_encode($user));
        $response = smtpGetResponse($socket);
        if (substr($response, 0, 3) !== '334') {
            fclose($socket);
            return false;
        }

        smtpSend($socket, base64_encode($pass));
        $response = smtpGetResponse($socket);
        if (substr($response, 0, 3) !== '235') {
            error_log("SMTP auth failed: $response");
            fclose($socket);
            return false;
        }

        // MAIL FROM (sanitize to prevent command injection)
        $safeFrom = str_replace(["\r", "\n", "\0"], '', $fromEmail);
        smtpSend($socket, "MAIL FROM:<$safeFrom>");
        $response = smtpGetResponse($socket);
        if (substr($response, 0, 3) !== '250') {
            fclose($socket);
            return false;
        }

        // RCPT TO (sanitize to prevent command injection)
        $safeTo = str_replace(["\r", "\n", "\0"], '', $to);
        smtpSend($socket, "RCPT TO:<$safeTo>");
        $response = smtpGetResponse($socket);
        if (substr($response, 0, 3) !== '250') {
            fclose($socket);
            return false;
        }

        // DATA
        smtpSend($socket, "DATA");
        $response = smtpGetResponse($socket);
        if (substr($response, 0, 3) !== '354') {
            fclose($socket);
            return false;
        }

        // Build message (sanitize header fields to prevent header injection)
        $sanitize = fn($s) => str_replace(["\r", "\n", "\0"], '', $s);
        $message = "From: " . $sanitize($fromName) . " <" . $sanitize($fromEmail) . ">\r\n";
        $message .= "To: " . $sanitize($to) . "\r\n";
        $message .= "Subject: " . $sanitize($subject) . "\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Date: " . date('r') . "\r\n";
        $message .= "Message-ID: <" . uniqid() . "@" . gethostname() . ">\r\n";
        $message .= "\r\n";
        // SMTP dot-stuffing: lines starting with "." must be escaped as ".."
        $stuffedBody = str_replace("\r\n.", "\r\n..", $htmlBody);
        $message .= $stuffedBody . "\r\n";
        $message .= ".";

        smtpSend($socket, $message);
        $response = smtpGetResponse($socket);
        if (substr($response, 0, 3) !== '250') {
            fclose($socket);
            return false;
        }

        // QUIT
        smtpSend($socket, "QUIT");
        fclose($socket);

        return true;
    } catch (\Exception $e) {
        error_log("SMTP error: " . $e->getMessage());
        return false;
    }
}

function smtpSend($socket, string $data): void {
    fwrite($socket, $data . "\r\n");
}

function smtpGetResponse($socket): string {
    $response = '';
    while ($line = fgets($socket, 512)) {
        $response .= $line;
        // If the 4th character is a space, it's the last line
        if (isset($line[3]) && $line[3] === ' ') break;
    }
    return $response;
}

/**
 * Build a styled HTML email
 */
function buildEmailHtml(string $title, string $bodyHtml): string {
    $companyName = getSetting('company_name', 'Our Store');
    $primaryColor = getSetting('primary_color', '#2563EB');

    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
    <body style="margin:0;padding:0;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:40px 20px;">
    <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <tr><td style="background:' . e($primaryColor) . ';padding:24px 32px;text-align:center;">
        <h1 style="color:#fff;margin:0;font-size:22px;">' . e($companyName) . '</h1>
    </td></tr>
    <tr><td style="padding:32px;">
        <h2 style="margin:0 0 16px;color:#1e293b;">' . e($title) . '</h2>
        ' . $bodyHtml . '
    </td></tr>
    <tr><td style="padding:16px 32px;background:#f8fafc;text-align:center;font-size:12px;color:#94a3b8;">
        &copy; ' . date('Y') . ' ' . e($companyName) . '. All rights reserved.
    </td></tr>
    </table>
    </td></tr></table></body></html>';
}

/**
 * Send order confirmation email
 */
function sendOrderEmail(int $orderId): bool {
    $db = getDB();
    $order = $db->prepare('SELECT * FROM orders WHERE id = ?');
    $order->execute([$orderId]);
    $order = $order->fetch();
    if (!$order) return false;

    $items = $db->prepare('SELECT * FROM order_items WHERE order_id = ?');
    $items->execute([$orderId]);
    $items = $items->fetchAll();

    $itemsHtml = '<table style="width:100%;border-collapse:collapse;margin:16px 0;">';
    $itemsHtml .= '<tr style="border-bottom:2px solid #e2e8f0;"><th style="text-align:left;padding:8px;">Item</th><th style="text-align:center;padding:8px;">Qty</th><th style="text-align:right;padding:8px;">Price</th></tr>';
    foreach ($items as $item) {
        $itemsHtml .= '<tr style="border-bottom:1px solid #f1f5f9;">';
        $itemsHtml .= '<td style="padding:8px;">' . e($item['product_name']) . '</td>';
        $itemsHtml .= '<td style="text-align:center;padding:8px;">' . $item['quantity'] . '</td>';
        $itemsHtml .= '<td style="text-align:right;padding:8px;">' . formatCurrency($item['total_price']) . '</td>';
        $itemsHtml .= '</tr>';
    }
    $itemsHtml .= '</table>';

    $bodyHtml = '<p>Thank you for your order!</p>';
    $bodyHtml .= '<p><strong>Order Number:</strong> ' . e($order['order_number']) . '</p>';
    $bodyHtml .= $itemsHtml;
    $bodyHtml .= '<p style="font-size:18px;font-weight:700;text-align:right;">Total: ' . formatCurrency($order['total']) . '</p>';
    if ($order['shipping_address']) {
        $bodyHtml .= '<p><strong>Shipping to:</strong> ' . e($order['shipping_address']) . '</p>';
    }

    $html = buildEmailHtml('Order Confirmation', $bodyHtml);
    return sendEmail($order['customer_email'], 'Order Confirmation - ' . $order['order_number'], $html);
}

/**
 * Send password reset email
 */
function sendPasswordResetEmail(string $toEmail, string $firstName, string $token): bool {
    $baseUrl = getCanonicalBaseUrl();
    $resetUrl = $baseUrl . '/index.php?page=reset-password&token=' . urlencode($token);
    $companyName = getSetting('company_name', 'Our Store');

    $bodyHtml = '<p>Hi ' . e($firstName) . ',</p>';
    $bodyHtml .= '<p>We received a request to reset your password. Click the button below to set a new password:</p>';
    $bodyHtml .= '<p style="text-align:center;margin:24px 0;"><a href="' . e($resetUrl) . '" style="display:inline-block;padding:12px 32px;background:' . e(getSetting('primary_color', '#2563EB')) . ';color:#fff;text-decoration:none;border-radius:6px;font-weight:600;">Reset Password</a></p>';
    $bodyHtml .= '<p>If you did not request this, you can safely ignore this email. The link will expire in 1 hour.</p>';
    $bodyHtml .= '<p style="font-size:12px;color:#94a3b8;margin-top:24px;">If the button doesn\'t work, copy and paste this URL into your browser:<br>' . e($resetUrl) . '</p>';

    $html = buildEmailHtml('Password Reset', $bodyHtml);
    return sendEmail($toEmail, 'Reset Your Password - ' . $companyName, $html);
}

/**
 * Send test email from admin settings
 */
function sendTestEmail(string $toEmail): bool {
    $html = buildEmailHtml('Test Email', '<p>This is a test email from your website. If you received this, your email configuration is working correctly!</p><p>Sent at: ' . date('Y-m-d H:i:s') . '</p>');
    return sendEmail($toEmail, 'Test Email - ' . getSetting('company_name', 'Your Website'), $html);
}
