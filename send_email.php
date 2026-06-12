<?php
// send_email.php - Email helper using PHPMailer
require_once 'vendor/autoload.php'; // If using Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $mail;
    private $smtpHost;
    private $smtpPort;
    private $smtpUser;
    private $smtpPass;
    
    public function __construct() {
        // Load from environment
        $this->smtpHost = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $this->smtpPort = getenv('SMTP_PORT') ?: 587;
        $this->smtpUser = getenv('SMTP_USER') ?: '';
        $this->smtpPass = getenv('SMTP_PASS') ?: '';
        
        $this->mail = new PHPMailer(true);
        $this->mail->isSMTP();
        $this->mail->Host = $this->smtpHost;
        $this->mail->Port = $this->smtpPort;
        $this->mail->SMTPAuth = true;
        $this->mail->Username = $this->smtpUser;
        $this->mail->Password = $this->smtpPass;
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->setFrom($this->smtpUser, 'Taigon Investments');
        $this->mail->isHTML(true);
        $this->mail->CharSet = 'UTF-8';
    }
    
    public function sendOrderConfirmation($to, $firstName, $lastName, $orderId, $trackingId, $total) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($to, $firstName . ' ' . $lastName);
            $this->mail->Subject = 'Order Confirmation #' . $orderId . ' - Taigon Investments';
            
            $html = '
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #0ea5e9, #0369a1); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { background: #fff; padding: 20px; border: 1px solid #e2e8f0; border-top: none; border-radius: 0 0 10px 10px; }
                    .tracking-box { background: #f8fafc; padding: 15px; border-radius: 8px; margin: 15px 0; text-align: center; }
                    .tracking-id { font-family: monospace; font-size: 18px; font-weight: bold; color: #0369a1; letter-spacing: 1px; }
                    .btn { display: inline-block; background: linear-gradient(135deg, #0ea5e9, #0369a1); color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 15px; }
                    .footer { text-align: center; padding: 20px; font-size: 12px; color: #64748b; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h2>Order Confirmation</h2>
                    </div>
                    <div class="content">
                        <p>Dear ' . htmlspecialchars($firstName) . ' ' . htmlspecialchars($lastName) . ',</p>
                        <p>Thank you for your order! Your order has been received and is being processed.</p>
                        
                        <div class="tracking-box">
                            <strong>Order Number:</strong> #' . $orderId . '<br>
                            <strong>Tracking ID:</strong> <span class="tracking-id">' . htmlspecialchars($trackingId) . '</span>
                        </div>
                        
                        <p><strong>Order Total:</strong> TShs ' . number_format($total, 2) . '</p>
                        <p>You can track your order status using your tracking ID on our website.</p>
                        
                        <div style="text-align: center;">
                            <a href="https://taigoninvestment.co.tz/my_orders.php" class="btn">Track Your Order</a>
                        </div>
                        
                        <p>For any questions, please contact our support team at +255 740 610 143 or reply to this email.</p>
                        <p>Thank you for shopping with Taigon Investments!</p>
                    </div>
                    <div class="footer">
                        &copy; ' . date('Y') . ' Taigon Investments. All rights reserved.
                    </div>
                </div>
            </body>
            </html>';
            
            $this->mail->Body = $html;
            $this->mail->AltBody = "Order Confirmation #$orderId\n\nOrder Total: TShs " . number_format($total, 2) . "\nTracking ID: $trackingId\n\nTrack your order at: https://taigoninvestment.co.tz/my_orders.php";
            
            return $this->mail->send();
        } catch (Exception $e) {
            error_log("Email sending failed: " . $this->mail->ErrorInfo);
            return false;
        }
    }
    
    public function sendPasswordReset($to, $name, $resetLink) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($to, $name);
            $this->mail->Subject = 'Password Reset Request - Taigon Investments';
            
            $html = '
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #0ea5e9, #0369a1); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { background: #fff; padding: 20px; border: 1px solid #e2e8f0; border-top: none; border-radius: 0 0 10px 10px; }
                    .reset-box { background: #f8fafc; padding: 15px; border-radius: 8px; margin: 15px 0; text-align: center; }
                    .btn { display: inline-block; background: linear-gradient(135deg, #0ea5e9, #0369a1); color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; margin-top: 15px; }
                    .footer { text-align: center; padding: 20px; font-size: 12px; color: #64748b; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h2>Password Reset Request</h2>
                    </div>
                    <div class="content">
                        <p>Dear ' . htmlspecialchars($name) . ',</p>
                        <p>We received a request to reset your password for your Taigon Investments account.</p>
                        
                        <div class="reset-box">
                            <p>Click the button below to reset your password:</p>
                            <a href="' . $resetLink . '" class="btn">Reset Password</a>
                        </div>
                        
                        <p>This link will expire in 1 hour for security reasons.</p>
                        <p>If you didn\'t request this, please ignore this email. Your password will remain unchanged.</p>
                        
                        <p>Thank you,<br>Taigon Investments Team</p>
                    </div>
                    <div class="footer">
                        &copy; ' . date('Y') . ' Taigon Investments. All rights reserved.
                    </div>
                </div>
            </body>
            </html>';
            
            $this->mail->Body = $html;
            $this->mail->AltBody = "Password Reset Request\n\nClick here to reset your password: $resetLink\n\nThis link expires in 1 hour.";
            
            return $this->mail->send();
        } catch (Exception $e) {
            error_log("Password reset email failed: " . $this->mail->ErrorInfo);
            return false;
        }
    }
    
    public function sendOrderStatusUpdate($to, $name, $orderId, $newStatus, $notes = '') {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($to, $name);
            $this->mail->Subject = 'Order Status Update #' . $orderId . ' - Taigon Investments';
            
            $statusMessages = [
                'Processing' => 'Your order is being prepared for shipment.',
                'Shipped' => 'Your order has been shipped and is on its way!',
                'Delivered' => 'Your order has been delivered. Thank you for shopping with us!',
                'Completed' => 'Your order is complete.',
                'Cancelled' => 'Your order has been cancelled.'
            ];
            
            $statusMessage = $statusMessages[$newStatus] ?? 'Your order status has been updated.';
            
            $html = '
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #0ea5e9, #0369a1); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { background: #fff; padding: 20px; border: 1px solid #e2e8f0; border-top: none; border-radius: 0 0 10px 10px; }
                    .status-box { background: #f8fafc; padding: 15px; border-radius: 8px; margin: 15px 0; text-align: center; }
                    .status { font-size: 18px; font-weight: bold; color: #0369a1; }
                    .btn { display: inline-block; background: linear-gradient(135deg, #0ea5e9, #0369a1); color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 15px; }
                    .footer { text-align: center; padding: 20px; font-size: 12px; color: #64748b; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h2>Order Status Update</h2>
                    </div>
                    <div class="content">
                        <p>Dear ' . htmlspecialchars($name) . ',</p>
                        
                        <div class="status-box">
                            <strong>Order #' . $orderId . '</strong><br>
                            New Status: <span class="status">' . $newStatus . '</span><br>
                            <p>' . $statusMessage . '</p>
                            ' . ($notes ? '<p><strong>Note:</strong> ' . htmlspecialchars($notes) . '</p>' : '') . '
                        </div>
                        
                        <div style="text-align: center;">
                            <a href="https://taigoninvestment.co.tz/my_orders.php" class="btn">Track Your Order</a>
                        </div>
                        
                        <p>Thank you for choosing Taigon Investments!</p>
                    </div>
                    <div class="footer">
                        &copy; ' . date('Y') . ' Taigon Investments. All rights reserved.
                    </div>
                </div>
            </body>
            </html>';
            
            $this->mail->Body = $html;
            $this->mail->AltBody = "Order #$orderId Status Update\n\nNew Status: $newStatus\n\n$statusMessage\n\nTrack your order at: https://taigoninvestment.co.tz/my_orders.php";
            
            return $this->mail->send();
        } catch (Exception $e) {
            error_log("Order status email failed: " . $this->mail->ErrorInfo);
            return false;
        }
    }
}

// Global helper function
function sendEmail($to, $subject, $body) {
    $emailService = new EmailService();
    // Custom implementation if needed
    return false;
}
?>