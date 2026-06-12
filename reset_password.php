<?php
require 'db.php';
require 'security_functions.php';

secure_session_start();

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = "Invalid password reset link.";
} else {
    // Verify token
    $stmt = $conn->prepare("SELECT user_id, email, full_name FROM users WHERE reset_token = ? AND reset_expiry > NOW()");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $error = "Invalid or expired password reset link. Please request a new one.";
    } else {
        $user = $result->fetch_assoc();
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            
            if (strlen($password) < 8) {
                $error = "Password must be at least 8 characters long";
            } elseif (!preg_match('/[A-Z]/', $password)) {
                $error = "Password must contain at least one uppercase letter";
            } elseif (!preg_match('/[0-9]/', $password)) {
                $error = "Password must contain at least one number";
            } elseif ($password !== $confirm_password) {
                $error = "Passwords do not match";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $updateStmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE user_id = ?");
                $updateStmt->bind_param('si', $hashed_password, $user['user_id']);
                
                if ($updateStmt->execute()) {
                    $success = "Password reset successfully! You can now login with your new password.";
                    security_log('Password Reset', "User: {$user['email']}");
                } else {
                    $error = "Failed to reset password. Please try again.";
                }
                $updateStmt->close();
            }
        }
    }
    $stmt->close();
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Taigon Investments</title>
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
        .reset-container {
            width: 100%;
            max-width: 440px;
        }
        .reset-card {
            background: white;
            border-radius: 28px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            overflow: hidden;
        }
        .reset-header {
            background: linear-gradient(135deg, #0ea5e9, #0369a1);
            padding: 2rem;
            text-align: center;
            color: white;
        }
        .reset-header h1 { font-size: 1.5rem; margin-bottom: 0.25rem; }
        .reset-body { padding: 2rem; }
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
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(14,165,233,0.3);
        }
        .login-link { text-align: center; margin-top: 1.5rem; }
        .login-link a { color: #0ea5e9; text-decoration: none; }
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
        }
    </style>
</head>
<body>
<div class="reset-container">
    <div class="reset-card">
        <div class="reset-header">
            <i class="fas fa-lock" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
            <h1>Reset Password</h1>
            <p>Create a new password for your account</p>
        </div>
        <div class="reset-body">
            <?php if ($error): ?>
                <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success-message"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
                <div class="login-link"><a href="userlogin.php"><i class="fas fa-sign-in-alt"></i> Login Now</a></div>
            <?php endif; ?>
            <?php if (!$success && !$error && $token): ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div class="form-group">
                        <label>New Password</label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" id="password" required minlength="8">
                            <button type="button" class="password-toggle" id="togglePassword"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="confirm_password" id="confirm_password" required>
                            <button type="button" class="password-toggle" id="toggleConfirm"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>
                    <button type="submit" class="btn">Reset Password</button>
                </form>
                <div class="login-link"><a href="userlogin.php">Back to Login</a></div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    }
    const toggleConfirm = document.getElementById('toggleConfirm');
    const confirm = document.getElementById('confirm_password');
    if (toggleConfirm) {
        toggleConfirm.addEventListener('click', function() {
            const type = confirm.getAttribute('type') === 'password' ? 'text' : 'password';
            confirm.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    }
</script>
</body>
</html>