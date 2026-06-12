<?php
session_start();
require 'db.php';
require 'security_functions.php';

// Start secure session
secure_session_start();

// Redirect if already logged in as user
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token'])) {
        $error = "Invalid request. Please try again.";
        security_log('CSRF Attack on Order Tracking', $_SERVER['REMOTE_ADDR']);
    } else {
        try {
            verifyCSRFToken($_POST['csrf_token']);
            
            // Rate limiting
            $ip = $_SERVER['REMOTE_ADDR'];
            if (!check_rate_limit($ip . '_tracking')) {
                $error = "Too many attempts. Please try again later.";
            } else {
                $trackingId = sanitize_input($_POST['tracking_id']);
                $email = sanitize_input($_POST['email']);
                
                // Validate inputs
                if (empty($trackingId)) {
                    $error = "Please enter your tracking ID";
                } elseif (!validate_email($email)) {
                    $error = "Please enter a valid email address";
                } else {
                    // Verify tracking ID and email
                    $stmt = $conn->prepare("SELECT id, tracking_id, email, first_name, last_name, order_status FROM orders WHERE tracking_id = ? AND email = ?");
                    $stmt->bind_param('ss', $trackingId, $email);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $order = $result->fetch_assoc();
                        
                        // Store minimal tracking info in session
                        $_SESSION['tracking_order'] = [
                            'id' => $order['id'],
                            'tracking_id' => $order['tracking_id'],
                            'email' => $order['email'],
                            'first_name' => $order['first_name'],
                            'last_name' => $order['last_name'],
                            'order_status' => $order['order_status']
                        ];
                        
                        // Clear rate limiting
                        unset($_SESSION['rate_limit'][$ip . '_tracking']);
                        
                        security_log('Order Tracking', "Tracking ID: $trackingId | Email: $email");
                        
                        header('Location: my_orders.php');
                        exit;
                    } else {
                        $error = "Invalid tracking ID or email. Please try again.";
                        security_log('Failed Order Tracking', "Tracking ID: $trackingId | Email: $email | IP: $ip");
                    }
                    $stmt->close();
                }
            }
        } catch (Exception $e) {
            $error = "Invalid request. Please try again.";
            security_log('CSRF Token Validation Failed', $e->getMessage());
        }
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="Track your order from Taigon Investments using your tracking ID">
    <title>Track Your Order - Taigon Investments</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1.5rem;
        }
        
        .tracking-container {
            width: 100%;
            max-width: 500px;
        }
        
        .tracking-card {
            background: white;
            border-radius: 28px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }
        
        .tracking-header {
            background: linear-gradient(135deg, #f97316, #ea580c);
            padding: 2rem;
            text-align: center;
            color: white;
        }
        
        .tracking-header i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .tracking-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .tracking-header p {
            font-size: 0.85rem;
            opacity: 0.9;
        }
        
        .tracking-body {
            padding: 2rem;
        }
        
        .info-box {
            background: #fff7ed;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
            border: 1px solid #fed7aa;
        }
        
        .info-box i {
            color: #f97316;
            font-size: 1.2rem;
            margin-right: 0.5rem;
        }
        
        .info-box p {
            color: #9a3412;
            font-size: 0.85rem;
        }
        
        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 0.85rem 1rem;
            border-radius: 12px;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-left: 3px solid #ef4444;
        }
        
        .success-message {
            background: #d1fae5;
            color: #065f46;
            padding: 0.85rem 1rem;
            border-radius: 12px;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-left: 3px solid #10b981;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.85rem;
            color: #1e293b;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1rem;
        }
        
        .input-group input {
            width: 100%;
            padding: 0.85rem 1rem 0.85rem 2.8rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            font-size: 0.95rem;
            font-family: inherit;
            transition: all 0.2s;
        }
        
        .input-group input:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }
        
        .input-group input:focus + i {
            color: #f97316;
        }
        
        .tracking-hint {
            font-size: 0.7rem;
            color: #94a3b8;
            margin-top: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .tracking-hint i {
            font-size: 0.7rem;
        }
        
        .btn-track {
            width: 100%;
            padding: 0.85rem;
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-track:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(249, 115, 22, 0.3);
        }
        
        .btn-track:active {
            transform: translateY(0);
        }
        
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1.5rem 0;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .divider span {
            padding: 0 1rem;
            color: #94a3b8;
            font-size: 0.8rem;
        }
        
        .customer-login {
            text-align: center;
        }
        
        .customer-login p {
            font-size: 0.85rem;
            color: #475569;
            margin-bottom: 0.75rem;
        }
        
        .btn-customer {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.7rem 1.5rem;
            background: transparent;
            color: #0ea5e9;
            border: 1.5px solid #0ea5e9;
            border-radius: 14px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s;
        }
        
        .btn-customer:hover {
            background: #0ea5e9;
            color: white;
            transform: translateY(-2px);
        }
        
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }
        
        .back-link a {
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .back-link a:hover {
            color: #f97316;
        }
        
        /* Sample tracking ID tooltip */
        .sample-tracking {
            cursor: pointer;
            color: #f97316;
            text-decoration: underline dotted;
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .tracking-body {
                padding: 1.5rem;
            }
            
            .tracking-header {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="tracking-container">
        <div class="tracking-card">
            <div class="tracking-header">
                <i class="fas fa-truck"></i>
                <h1>Track Your Order</h1>
                <p>Enter your tracking ID and email to check order status</p>
            </div>
            
            <div class="tracking-body">
                <div class="info-box">
                    <i class="fas fa-question-circle"></i>
                    <p>Your tracking ID was sent to your email after placing your order.<br>
                    Format example: <strong class="sample-tracking" onclick="document.getElementById('tracking_id').value='TGN-XXXXXXXX'">TGN-XXXXXXXX</strong></p>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php" id="trackingForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    
                    <div class="form-group">
                        <label for="tracking_id">Tracking ID</label>
                        <div class="input-group">
                            <i class="fas fa-qrcode"></i>
                            <input type="text" id="tracking_id" name="tracking_id" placeholder="Enter your tracking ID (e.g., TGN-XXXXXXXX)" required autocomplete="off" value="<?php echo htmlspecialchars($_POST['tracking_id'] ?? ''); ?>">
                        </div>
                        <div class="tracking-hint">
                            <i class="fas fa-info-circle"></i>
                            <span>Found in your order confirmation email</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-group">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" placeholder="Email used when placing order" required autocomplete="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-track" id="trackBtn">
                        <i class="fas fa-search"></i> Track Order
                    </button>
                </form>
                
                <div class="divider">
                    <span>OR</span>
                </div>
                
                <div class="customer-login">
                    <p>Have a registered account?</p>
                    <a href="userlogin.php" class="btn-customer">
                        <i class="fas fa-sign-in-alt"></i> Customer Login
                    </a>
                </div>
                
                <div class="back-link">
                    <a href="index.php">
                        <i class="fas fa-arrow-left"></i> Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const trackingForm = document.getElementById('trackingForm');
        const trackBtn = document.getElementById('trackBtn');
        
        // Format tracking ID input (auto uppercase)
        const trackingInput = document.getElementById('tracking_id');
        if (trackingInput) {
            trackingInput.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
            
            // Remove spaces
            trackingInput.addEventListener('keypress', function(e) {
                if (e.key === ' ') {
                    e.preventDefault();
                }
            });
        }
        
        // Form validation
        if (trackingForm) {
            trackingForm.addEventListener('submit', function(e) {
                const trackingId = document.getElementById('tracking_id').value.trim();
                const email = document.getElementById('email').value.trim();
                
                if (!trackingId) {
                    e.preventDefault();
                    showError('Please enter your tracking ID');
                    return false;
                }
                
                if (trackingId.length < 8) {
                    e.preventDefault();
                    showError('Please enter a valid tracking ID');
                    return false;
                }
                
                if (!email) {
                    e.preventDefault();
                    showError('Please enter your email address');
                    return false;
                }
                
                // Disable button to prevent double submission
                if (trackBtn) {
                    trackBtn.disabled = true;
                    trackBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Tracking...';
                }
            });
        }
        
        function showError(message) {
            // Remove existing error message
            const existingError = document.querySelector('.error-message');
            if (existingError) existingError.remove();
            
            // Create new error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i><span>' + message + '</span>';
            
            // Insert after info box
            const infoBox = document.querySelector('.info-box');
            infoBox.insertAdjacentElement('afterend', errorDiv);
            
            // Scroll to error
            errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Re-enable button
            if (trackBtn) {
                trackBtn.disabled = false;
                trackBtn.innerHTML = '<i class="fas fa-search"></i> Track Order';
            }
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (errorDiv) errorDiv.remove();
            }, 5000);
        }
        
        // Enter key submit
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && trackingForm) {
                const activeElement = document.activeElement;
                if (activeElement && (activeElement.id === 'tracking_id' || activeElement.id === 'email')) {
                    trackingForm.dispatchEvent(new Event('submit'));
                }
            }
        });
        
        // Sample tracking ID click handler
        const sampleElement = document.querySelector('.sample-tracking');
        if (sampleElement) {
            sampleElement.addEventListener('click', function() {
                const trackingInput = document.getElementById('tracking_id');
                if (trackingInput) {
                    trackingInput.value = 'TGN-XXXXXXXX';
                    trackingInput.focus();
                    trackingInput.select();
                }
            });
        }
    </script>
</body>
</html>