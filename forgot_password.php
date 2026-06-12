<?php
require 'db.php';
require 'security_functions.php';

secure_session_start();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize_input($_POST['email']);
    
    if (!validate_email($email)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT user_id, full_name FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $stmt2 = $conn->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE user_id = ?");
            $stmt2->bind_param('ssi', $token, $expiry, $user['user_id']);
            $stmt2->execute();
            $stmt2->close();
            
            // Send reset email
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/Taigon%20website/reset_password.php?token=" . $token;
            $subject = "Password Reset - Taigon Investments";
            $message = "Dear {$user['full_name']},\n\n";
            $message .= "Click the link below to reset your password:\n\n";
            $message .= $reset_link . "\n\n";
            $message .= "This link expires in 1 hour.\n\n";
            $message .= "If you didn't request this, please ignore this email.\n\n";
            $message .= "Thank you,\nTaigon Investments";
            
            $headers = "From: no-reply@taigoninvestment.co.tz";
            
            if (mail($email, $subject, $message, $headers)) {
                $success = "Password reset link has been sent to your email.";
            } else {
                $error = "Failed to send email. Please try again later.";
            }
        } else {
            $error = "No account found with that email address.";
        }
        $stmt->close();
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Taigon Investments</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1.5rem;
        }
        .forgot-container {
            width: 100%;
            max-width: 440px;
        }
        .forgot-card {
            background: white;
            border-radius: 28px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            overflow: hidden;
        }
        .forgot-header {
            background: linear-gradient(135deg, #0ea5e9, #0369a1);
            padding: 2rem;
            text-align: center;
            color: white;
        }
        .forgot-header h1 { font-size: 1.5rem; margin-bottom: 0.25rem; }
        .forgot-body { padding: 2rem; }
        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 0.85rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .success-message {
            background: #d1fae5;
            color: #065f46;
            padding: 0.85rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .form-group { margin-bottom: 1.25rem; }
        .input-group { position: relative; }
        .input-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }
        .input-group input {
            width: 100%;
            padding: 0.85rem 1rem 0.85rem 2.8rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            font-size: 0.95rem;
        }
        .input-group input:focus {
            outline: none;
            border-color: #0ea5e9;
        }
        .btn {
            width: 100%;
            padding: 0.85rem;
            background: linear-gradient(135deg, #0ea5e9, #0369a1);
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
        }
        .back-link { text-align: center; margin-top: 1.5rem; }
        .back-link a { color: #94a3b8; text-decoration: none; font-size: 0.85rem; }
        .back-link a:hover { color: #0ea5e9; }
    </style>
</head>
<body>
<div class="forgot-container">
    <div class="forgot-card">
        <div class="forgot-header">
            <i class="fas fa-key" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
            <h1>Forgot Password</h1>
            <p>We'll send you a reset link</p>
        </div>
        <div class="forgot-body">
            <?php if ($error): ?>
                <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success-message"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <div class="form-group">
                    <label>Email Address</label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Enter your email" required>
                    </div>
                </div>
                <button type="submit" class="btn">Send Reset Link</button>
            </form>
            <div class="back-link">
                <a href="userlogin.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>