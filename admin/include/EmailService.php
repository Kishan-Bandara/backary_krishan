<?php
/**
 * EmailService - Common SMTP Email Service for Bakery Admin
 * 
 * Usage:
 *   require_once(__DIR__ . '/EmailService.php');
 *   $emailService = new EmailService();
 *   $result = $emailService->send($to, $subject, $htmlBody, $attachments);
 * 
 * Designed for reuse across: Cart Orders, Standing Orders, Invoices, etc.
 */

// try autoload in expected locations
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    // fall back to DB Migration location in case composer was run there
    $autoloadPath = __DIR__ . '/../DB Migration/vendor/autoload.php';
}
if (!file_exists($autoloadPath)) {
    throw new Exception('Composer autoload.php not found; please run `composer install` in admin folder');
}
require_once($autoloadPath);
require_once(__DIR__ . '/database.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class EmailService
{
    private $settings = null;
    private $db;
    private $lastError = '';

    public function __construct()
    {
        $this->db = new Database();
        $this->loadSettings();
    }

    /**
     * Load SMTP settings from database
     */
    private function loadSettings()
    {
        try {
            $this->settings = $this->db->getRow('SELECT * FROM smtp_settings WHERE id = 1');
        } catch (Exception $e) {
            $this->settings = null;
            $this->lastError = 'Could not load SMTP settings: ' . $e->getMessage();
        }
    }

    /**
     * Check if email service is configured and enabled
     */
    public function isEnabled(): bool
    {
        return $this->settings
            && !empty($this->settings['smtp_host'])
            && !empty($this->settings['smtp_from_email'])
            && (int)$this->settings['smtp_enabled'] === 1;
    }

    /**
     * Get last error message
     */
    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * Send an email
     * 
     * @param string|array $to       Email address or ['email' => 'name'] or [['email' => 'x', 'name' => 'y'], ...]
     * @param string       $subject  Email subject
     * @param string       $htmlBody HTML content of the email
     * @param array        $attachments  Array of file paths or ['path' => '/path/to/file', 'name' => 'display.pdf']
     * @param string       $templateType For logging: 'cart_order', 'standing_order', etc.
     * @param int|null     $referenceId  For logging: invoice_h_id, standing_order id, etc.
     * @return bool
     */
    public function send($to, string $subject, string $htmlBody, array $attachments = [], string $templateType = 'general', ?int $referenceId = null): bool
    {
        if (!$this->isEnabled()) {
            $this->lastError = 'Email service is not enabled or not configured. Please configure SMTP settings.';
            error_log('EmailService: ' . $this->lastError);
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host       = $this->settings['smtp_host'];
            $mail->Port       = (int)$this->settings['smtp_port'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->settings['smtp_username'];
            $mail->Password   = $this->settings['smtp_password'];
            $mail->CharSet    = 'UTF-8';

            // Encryption
            $encryption = $this->settings['smtp_encryption'];
            if ($encryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = '';
                $mail->SMTPAutoTLS = false;
            }

            // From
            $mail->setFrom(
                $this->settings['smtp_from_email'],
                $this->settings['smtp_from_name'] ?: 'Bakery Admin'
            );

            // Reply-To
            if (!empty($this->settings['smtp_reply_to_email'])) {
                $mail->addReplyTo(
                    $this->settings['smtp_reply_to_email'],
                    $this->settings['smtp_reply_to_name'] ?: ''
                );
            }

            // Recipients
            $toEmail = '';
            $toName = '';
            if (is_string($to)) {
                $mail->addAddress($to);
                $toEmail = $to;
            } elseif (is_array($to)) {
                // Check if it's associative ['email' => 'name']
                if (isset($to['email'])) {
                    $mail->addAddress($to['email'], $to['name'] ?? '');
                    $toEmail = $to['email'];
                    $toName = $to['name'] ?? '';
                } else {
                    // Array of recipients
                    foreach ($to as $recipient) {
                        if (is_string($recipient)) {
                            $mail->addAddress($recipient);
                            $toEmail = $recipient;
                        } elseif (is_array($recipient)) {
                            $mail->addAddress($recipient['email'], $recipient['name'] ?? '');
                            $toEmail = $toEmail ?: $recipient['email'];
                        }
                    }
                }
            }

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>', '</tr>'], "\n", $htmlBody));

            // Attachments
            foreach ($attachments as $attachment) {
                if (is_string($attachment)) {
                    if (file_exists($attachment)) {
                        $mail->addAttachment($attachment);
                    }
                } elseif (is_array($attachment)) {
                    if (isset($attachment['string'])) {
                        // Attach from string (e.g., PDF content)
                        $mail->addStringAttachment(
                            $attachment['string'],
                            $attachment['name'] ?? 'attachment.pdf',
                            'base64',
                            $attachment['type'] ?? 'application/pdf'
                        );
                    } elseif (isset($attachment['path']) && file_exists($attachment['path'])) {
                        $mail->addAttachment(
                            $attachment['path'],
                            $attachment['name'] ?? basename($attachment['path'])
                        );
                    }
                }
            }

            $mail->send();
            $this->lastError = '';

            // Log success
            $this->logEmail($toEmail, $toName, $subject, $templateType, $referenceId, 'sent');

            return true;

        } catch (PHPMailerException $e) {
            $this->lastError = $e->getMessage();
            error_log('EmailService PHPMailer Error: ' . $this->lastError);

            // Log failure
            $this->logEmail($toEmail ?? '', $toName ?? '', $subject, $templateType, $referenceId, 'failed', $this->lastError);

            return false;

        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            error_log('EmailService Error: ' . $this->lastError);

            $this->logEmail($toEmail ?? '', $toName ?? '', $subject, $templateType, $referenceId, 'failed', $this->lastError);

            return false;
        }
    }

    /**
     * Send an order confirmation email (Cart Order or Standing Order)
     * 
     * @param int    $invoiceId  The invoice_h_id
     * @param string $orderType  'cart_order' or 'standing_order'
     * @return bool
     */
    public function sendOrderConfirmation(int $invoiceId, string $orderType = 'cart_order'): bool
    {
        try {
            // Fetch invoice header
            $invoice = $this->db->getRow('SELECT * FROM invoice_hedder WHERE invoice_h_id = ?', [$invoiceId]);
            if (!$invoice) {
                $this->lastError = 'Invoice not found: ' . $invoiceId;
                return false;
            }

            // Fetch customer
            $customer = $this->db->getRow('SELECT * FROM customer WHERE customer_id = ?', [$invoice['invoice_h_customer_id']]);
            if (!$customer) {
                $this->lastError = 'Customer not found for invoice: ' . $invoiceId;
                return false;
            }

            // Get customer email - check shipping address contact email too
            $customerEmail = $customer['customer_email'] ?? '';
            if (empty($customerEmail) && !empty($invoice['shipping_address_id'])) {
                $shipping = $this->db->getRow('SELECT contact_person_email FROM customer_shipping_address WHERE id = ?', [$invoice['shipping_address_id']]);
                $customerEmail = $shipping['contact_person_email'] ?? '';
            }

            if (empty($customerEmail)) {
                $this->lastError = 'No email address found for customer: ' . ($customer['customer_name'] ?? $customer['customer_id']);
                error_log('EmailService: ' . $this->lastError);
                return false;
            }

            // Fetch invoice items
            $items = $this->db->getRows(
                'SELECT id.*, im.item_name, im.item_code 
                 FROM invoice_details id 
                 JOIN item_master im ON im.item_id = id.invoice_d_item_id 
                 WHERE id.invoice_h_id = ?',
                [$invoiceId]
            );

            // Fetch business settings
            $settings = $this->db->getRow('SELECT * FROM general_settings WHERE id = 1');
            $invoiceSettings = null;
            try {
                $invoiceSettings = $this->db->getRow('SELECT * FROM invoice_settings WHERE id = 1');
            } catch (Exception $e) { /* ignore */ }

            // Get currency
            $currencyInfo = $this->db->getRow('SELECT * FROM currency WHERE activated = ?', ['Y']);
            $currencySymbol = $currencyInfo['currency'] ?? '$';

            // Build email data
            $emailData = [
                'invoice'          => $invoice,
                'customer'         => $customer,
                'items'            => $items,
                'settings'         => $settings,
                'invoiceSettings'  => $invoiceSettings,
                'currencySymbol'   => $currencySymbol,
                'orderType'        => $orderType,
                'orderTypeLabel'   => ($orderType === 'standing_order') ? 'Standing Order' : 'Order',
            ];

            // Generate email HTML
            $htmlBody = $this->renderTemplate('order_confirmation', $emailData);

            // Generate PDF
            require_once(__DIR__ . '/OrderPdfGenerator.php');
            $pdfGenerator = new OrderPdfGenerator();
            $pdfContent = $pdfGenerator->generate($emailData);

            $subject = $emailData['orderTypeLabel'] . ' Confirmation - ' . ($invoice['invoice_h_code'] ?? 'INV' . $invoiceId);

            $pdfFilename = ($orderType === 'standing_order' ? 'Standing_Order_' : 'Order_') . ($invoice['invoice_h_code'] ?? $invoiceId) . '.pdf';

            $attachments = [];
            if ($pdfContent) {
                $attachments[] = [
                    'string' => $pdfContent,
                    'name'   => $pdfFilename,
                    'type'   => 'application/pdf'
                ];
            }

            return $this->send(
                ['email' => $customerEmail, 'name' => $customer['customer_name'] ?? ''],
                $subject,
                $htmlBody,
                $attachments,
                $orderType,
                $invoiceId
            );

        } catch (Exception $e) {
            $this->lastError = 'Failed to send order confirmation: ' . $e->getMessage();
            error_log('EmailService: ' . $this->lastError);
            return false;
        }
    }

    /**
     * Send Standing Order summary email (with all items and weekly schedule)
     * 
     * @param int $standingOrderId
     * @return bool
     */
    public function sendStandingOrderSummary(int $standingOrderId): bool
    {
        try {
            // Fetch standing order
            $so = $this->db->getRow('SELECT * FROM standing_order WHERE id = ?', [$standingOrderId]);
            if (!$so) {
                $this->lastError = 'Standing order not found: ' . $standingOrderId;
                return false;
            }

            // Fetch customer
            $customer = $this->db->getRow('SELECT * FROM customer WHERE customer_id = ?', [$so['customer_id']]);
            if (!$customer) {
                $this->lastError = 'Customer not found for standing order';
                return false;
            }

            $customerEmail = $customer['customer_email'] ?? '';
            if (empty($customerEmail)) {
                $this->lastError = 'No email for customer: ' . ($customer['customer_name'] ?? $so['customer_id']);
                error_log('EmailService: ' . $this->lastError);
                return false;
            }

            // Fetch standing order items with product names
            $soItems = $this->db->getRows(
                'SELECT soi.*, im.item_name, im.item_code, im.item_normal_selling_price
                 FROM standing_order_item soi
                 JOIN item_master im ON im.item_id = soi.item_id
                 WHERE soi.standing_order_id = ?',
                [$standingOrderId]
            );

            // Fetch shipping address
            $shipping = null;
            if (!empty($so['shipping_address_id'])) {
                $shipping = $this->db->getRow('SELECT * FROM customer_shipping_address WHERE id = ?', [$so['shipping_address_id']]);
            }

            // Get settings
            $settings = $this->db->getRow('SELECT * FROM general_settings WHERE id = 1');
            $invoiceSettings = null;
            try {
                $invoiceSettings = $this->db->getRow('SELECT * FROM invoice_settings WHERE id = 1');
            } catch (Exception $e) { /* ignore */ }

            $currencyInfo = $this->db->getRow('SELECT * FROM currency WHERE activated = ?', ['Y']);
            $currencySymbol = $currencyInfo['currency'] ?? '$';

            $emailData = [
                'standingOrder'    => $so,
                'customer'         => $customer,
                'items'            => $soItems,
                'shipping'         => $shipping,
                'settings'         => $settings,
                'invoiceSettings'  => $invoiceSettings,
                'currencySymbol'   => $currencySymbol,
                'orderType'        => 'standing_order',
                'orderTypeLabel'   => 'Standing Order',
            ];

            $htmlBody = $this->renderTemplate('standing_order_summary', $emailData);

            // Generate PDF
            require_once(__DIR__ . '/OrderPdfGenerator.php');
            $pdfGenerator = new OrderPdfGenerator();
            $pdfContent = $pdfGenerator->generateStandingOrder($emailData);

            $subject = 'Standing Order Confirmation - ' . ($customer['customer_name'] ?? 'Customer');

            $attachments = [];
            if ($pdfContent) {
                $attachments[] = [
                    'string' => $pdfContent,
                    'name'   => 'Standing_Order_' . $standingOrderId . '.pdf',
                    'type'   => 'application/pdf'
                ];
            }

            return $this->send(
                ['email' => $customerEmail, 'name' => $customer['customer_name'] ?? ''],
                $subject,
                $htmlBody,
                $attachments,
                'standing_order',
                $standingOrderId
            );

        } catch (Exception $e) {
            $this->lastError = 'Failed to send standing order email: ' . $e->getMessage();
            error_log('EmailService: ' . $this->lastError);
            return false;
        }
    }

    /**
     * Render an email template with data
     * 
     * @param string $templateName  Template identifier
     * @param array  $data          Data to pass to template
     * @return string HTML content
     */
    private function renderTemplate(string $templateName, array $data): string
    {
        $templateFile = __DIR__ . '/email_templates/' . $templateName . '.php';

        if (!file_exists($templateFile)) {
            error_log("EmailService: Template not found: $templateFile");
            return '<p>Email template not found.</p>';
        }

        // Extract data so template can use variables directly
        extract($data);

        ob_start();
        include($templateFile);
        return ob_get_clean();
    }

    /**
     * Log email to database
     */
    private function logEmail(string $toEmail, string $toName, string $subject, string $templateType, ?int $referenceId, string $status, string $errorMessage = ''): void
    {
        try {
            $this->db->insertRow(
                'INSERT INTO email_log (to_email, to_name, subject, template_type, reference_id, status, error_message) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$toEmail, $toName, $subject, $templateType, $referenceId, $status, $errorMessage ?: null]
            );
        } catch (Exception $e) {
            error_log('EmailService: Failed to log email: ' . $e->getMessage());
        }
    }

    /**
     * Send a test email to verify SMTP configuration
     * 
     * @param string $testEmail Email address to send test to
     * @return bool
     */
    public function sendTestEmail(string $testEmail): bool
    {
        $html = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="background: #2c3e50; color: #fff; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
                <h2 style="margin: 0;">✅ SMTP Test Successful</h2>
            </div>
            <div style="background: #f8f9fa; padding: 20px; border-radius: 0 0 8px 8px; border: 1px solid #dee2e6;">
                <p>This is a test email from your Bakery Admin system.</p>
                <p>If you received this email, your SMTP settings are configured correctly.</p>
                <p style="color: #6c757d; font-size: 12px;">Sent at: ' . date('Y-m-d H:i:s') . '</p>
            </div>
        </div>';

        return $this->send($testEmail, 'Bakery Admin - SMTP Test Email', $html, [], 'test', null);
    }
}
