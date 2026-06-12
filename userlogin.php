<?php 
require 'db.php';
require 'security_functions.php';

// Start secure session - FIXED: Only call this once at the beginning
// Check if session is already started
if (session_status() === PHP_SESSION_NONE) {
    // Set session cookie parameters before start
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);
    
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    
    session_start();
}

// Regenerate session ID periodically
if (!isset($_SESSION['last_regeneration'])) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 300) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Update last activity
$_SESSION['last_activity'] = time();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    // Check if coming from product page
    if (isset($_GET['product_id'])) {
        header("Location: cart.php?action=add&id=" . intval($_GET['product_id']));
        exit();
    }
    // Or to previous page if redirected from cart
    elseif (isset($_SESSION['redirect_url'])) {
        $redirect = $_SESSION['redirect_url'];
        unset($_SESSION['redirect_url']);
        header("Location: $redirect");
        exit();
    }
    // Default to home page
    else {
        header('Location: index.php');
        exit();
    }
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token'])) {
        $error = "Invalid request. Please try again.";
        security_log('CSRF Attack on Login', $_SERVER['REMOTE_ADDR']);
    } else {
        try {
            verifyCSRFToken($_POST['csrf_token']);
            
            // Rate limiting
            $ip = $_SERVER['REMOTE_ADDR'];
            if (!check_rate_limit($ip . '_user_login')) {
                $error = "Too many login attempts. Please try again later.";
                security_log('Rate Limit Exceeded', "IP: $ip");
            } else {
                $email = sanitize_input($_POST['email']);
                $password = $_POST['password'];
                $remember_me = isset($_POST['remember_me']) ? true : false;
                
                // Validate email format
                if (!validate_email($email)) {
                    $error = "Please enter a valid email address.";
                } elseif (empty($password)) {
                    $error = "Please enter your password.";
                } else {
                    // Query to get user data
                    $stmt = $conn->prepare("SELECT user_id, full_name, email, password, role, phone, address FROM users WHERE email = ?");
                    $stmt->bind_param('s', $email);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows == 1) {
                        $user = $result->fetch_assoc();
                        
                        if (password_verify($password, $user['password'])) {
                            // Login successful - regenerate session ID
                            session_regenerate_id(true);
                            
                            $_SESSION['user_id'] = $user['user_id'];
                            $_SESSION['user_name'] = $user['full_name'];
                            $_SESSION['user_email'] = $user['email'];
                            $_SESSION['user_role'] = $user['role'];
                            $_SESSION['user_phone'] = $user['phone'];
                            $_SESSION['user_address'] = $user['address'];
                            $_SESSION['last_activity'] = time();
                            
                            // Set remember me cookie (30 days)
                            if ($remember_me) {
                                $token = bin2hex(random_bytes(32));
                                $expiry = time() + (86400 * 30); // 30 days
                                
                                // Check if columns exist, if not add them
                                $checkColumns = $conn->query("SHOW COLUMNS FROM users LIKE 'remember_token'");
                                if ($checkColumns->num_rows == 0) {
                                    $conn->query("ALTER TABLE users ADD COLUMN remember_token VARCHAR(255) NULL");
                                    $conn->query("ALTER TABLE users ADD COLUMN token_expiry DATETIME NULL");
                                }
                                
                                // Store token in database
                                $expiryDate = date('Y-m-d H:i:s', $expiry);
                                $stmt2 = $conn->prepare("UPDATE users SET remember_token = ?, token_expiry = ? WHERE user_id = ?");
                                $stmt2->bind_param('ssi', $token, $expiryDate, $user['user_id']);
                                $stmt2->execute();
                                $stmt2->close();
                                
                                // Set cookie
                                setcookie('remember_token', $token, $expiry, '/', '', false, true);
                            }
                            
                            // Clear rate limiting on successful login
                            unset($_SESSION['rate_limit'][$ip . '_user_login']);
                            
                            security_log('Successful Login', "User: $email | Role: {$user['role']}");
                            
                            // Redirect based on role
                            if ($user['role'] == 'courier') {
                                header('Location: courier_dashboard.php');
                                exit();
                            } elseif ($user['role'] == 'admin') {
                                header('Location: admin_dashboard.php');
                                exit();
                            }
                            
                            // For customers, check if coming from product page
                            if (isset($_GET['product_id'])) {
                                header("Location: cart.php?action=add&id=" . intval($_GET['product_id']));
                                exit();
                            } 
                            // Or to previous page if redirected
                            elseif (isset($_SESSION['redirect_url'])) {
                                $redirect = $_SESSION['redirect_url'];
                                unset($_SESSION['redirect_url']);
                                header("Location: $redirect");
                                exit();
                            }
                            // Default to home page
                            else {
                                header('Location: index.php');
                                exit();
                            }
                        } else {
                            $error = "Invalid email or password";
                            security_log('Failed Login', "Email: $email | IP: $ip - Incorrect password");
                        }
                    } else {
                        $error = "Invalid email or password";
                        security_log('Failed Login', "Email: $email | IP: $ip - User not found");
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

// Check for remember me cookie
if (empty($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    
    // Check if columns exist
    $checkColumns = $conn->query("SHOW COLUMNS FROM users LIKE 'remember_token'");
    if ($checkColumns->num_rows > 0) {
        $stmt = $conn->prepare("SELECT user_id, full_name, email, role FROM users WHERE remember_token = ? AND token_expiry > NOW()");
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['last_activity'] = time();
            
            header('Location: index.php');
            exit();
        }
        $stmt->close();
    }
}

$csrf_token = generateCSRFToken();
?>
<!-- Rest of your HTML remains the same as before -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="Login to your Taigon Investments account to shop IT products, track orders, and manage your profile">
    <title>Login - Taigon Investments | IT Solutions Tanzania</title>
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
        
        .login-container {
            width: 100%;
            max-width: 460px;
        }
        
        .login-card {
            background: white;
            border-radius: 28px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, #0ea5e9, #0369a1);
            padding: 2rem;
            text-align: center;
            color: white;
        }
        
        .logo {
            display: flex;
            justify-content: center;
            margin-bottom: 1rem;
        }
        
        .logo img {
            height: 60px;
            width: auto;
            border-radius: 12px;
            background: white;
            padding: 0.25rem;
        }
        
        .login-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .login-header p {
            font-size: 0.85rem;
            opacity: 0.9;
        }
        
        .login-body {
            padding: 2rem;
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
        
        .info-box {
            background: #e0f2fe;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
            border: 1px solid #bae6fd;
        }
        
        .info-box h4 {
            color: #0369a1;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .info-box p {
            color: #0c4a6e;
            font-size: 0.8rem;
        }
        
        .notice {
            background: #fef3c7;
            border: 1px solid #fde68a;
            border-radius: 12px;
            padding: 0.75rem;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 0.85rem;
            color: #92400e;
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
            transition: color 0.2s;
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
            border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
        }
        
        .input-group input:focus + i {
            color: #0ea5e9;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            font-size: 0.85rem;
            color: #475569;
        }
        
        .checkbox-label input {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }
        
        .forgot-link {
            font-size: 0.85rem;
            color: #0ea5e9;
            text-decoration: none;
        }
        
        .forgot-link:hover {
            text-decoration: underline;
        }
        
        .btn-login {
            width: 100%;
            padding: 0.85rem;
            background: linear-gradient(135deg, #0ea5e9, #0369a1);
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
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(14, 165, 233, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
            font-size: 0.85rem;
            color: #475569;
        }
        
        .register-link a {
            color: #0ea5e9;
            text-decoration: none;
            font-weight: 600;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        .admin-link {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.8rem;
        }
        
        .admin-link a {
            color: #94a3b8;
            text-decoration: none;
        }
        
        .admin-link a:hover {
            color: #0ea5e9;
        }
        
        .back-link {
            text-align: center;
            margin-top: 1rem;
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
            color: #0ea5e9;
        }
        
        /* Password toggle */
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .password-toggle:hover {
            color: #0ea5e9;
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .login-body {
                padding: 1.5rem;
            }
            
            .login-header {
                padding: 1.5rem;
            }
            
            .checkbox-group {
                flex-direction: column;
                gap: 0.75rem;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo">
                    <img src="images/taigon-logo.jpg" alt="Taigon Investments" onerror="this.src='image/Capture.PNG'">
                </div>
                <h1>Welcome Back</h1>
                <p>Sign in to your Taigon Investments account</p>
            </div>
            
            <div class="login-body">
                <?php if (isset($_GET['registered']) && $_GET['registered'] == '1'): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        <span>Registration successful! Please login to continue.</span>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['timeout']) && $_GET['timeout'] == '1'): ?>
                    <div class="notice">
                        <i class="fas fa-clock"></i> Your session has expired. Please login again.
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['product_id'])): ?>
                    <div class="notice">
                        <i class="fas fa-cart-plus"></i> Please login to add items to your cart
                    </div>
                <?php endif; ?>
                
                <div class="info-box">
                    <h4><i class="fas fa-info-circle"></i> Account Types</h4>
                    <p><strong>Customer:</strong> Shop IT products, track orders, and manage profile<br>
                    <strong>Courier:</strong> Manage deliveries and update order status</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                <?php endif; ?>
                
                <form action="userlogin.php<?php echo isset($_GET['product_id']) ? '?product_id='.intval($_GET['product_id']) : ''; ?>" method="POST" id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-group">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" placeholder="Enter your email" required autocomplete="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                            <button type="button" class="password-toggle" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember_me" id="remember_me">
                            <span>Remember me</span>
                        </label>
                        <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
                    </div>
                    
                    <button type="submit" class="btn-login" id="loginBtn">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>
                    
                    <div class="register-link">
                        Don't have an account? <a href="register.php">Create an Account</a>
                    </div>
                    
                    <div class="admin-link">
                        <a href="admin_login.php"><i class="fas fa-user-shield"></i> Admin Login</a>
                    </div>
                    
                    <div class="back-link">
                        <a href="index.php"><i class="fas fa-arrow-left"></i> Back to Home</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        
        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
        }
        
        // Form validation
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        
        if (loginForm) {
            loginForm.addEventListener('submit', function(e) {
                const email = document.getElementById('email').value.trim();
                const password = document.getElementById('password').value;
                
                if (!email) {
                    e.preventDefault();
                    showError('Please enter your email address');
                    return false;
                }
                
                if (!password) {
                    e.preventDefault();
                    showError('Please enter your password');
                    return false;
                }
                
                // Disable button to prevent double submission
                if (loginBtn) {
                    loginBtn.disabled = true;
                    loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';
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
            
            // Insert at the top of login body
            const loginBody = document.querySelector('.login-body');
            const firstElement = loginBody.firstChild;
            loginBody.insertBefore(errorDiv, firstElement);
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
            // Re-enable button
            if (loginBtn) {
                loginBtn.disabled = false;
                loginBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Sign In';
            }
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (errorDiv) errorDiv.remove();
            }, 5000);
        }
        
        // Enter key submit
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && loginForm) {
                const activeElement = document.activeElement;
                if (activeElement && (activeElement.type === 'email' || activeElement.type === 'password')) {
                    loginForm.dispatchEvent(new Event('submit'));
                }
            }
        });
    </script>
</body>
</html>