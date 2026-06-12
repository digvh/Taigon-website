<?php 
require 'db.php';
require 'security_functions.php';

secure_session_start();

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';
$allowedRoles = ['customer', 'courier'];

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token'])) {
        $error = "Invalid request. Please try again.";
        security_log('CSRF Attack on Registration', $_SERVER['REMOTE_ADDR']);
    } else {
        try {
            verifyCSRFToken($_POST['csrf_token']);
            
            // Rate limiting (3 attempts per 10 minutes)
            $ip = $_SERVER['REMOTE_ADDR'];
            if (!check_rate_limit($ip . '_register', 3, 600)) {
                $error = 'Too many registration attempts. Please try again later.';
                security_log('Rate Limit Exceeded - Registration', "IP: $ip");
            } else {
                // Sanitize inputs
                $full_name = sanitize_input($_POST['full_name']);
                $email = sanitize_input($_POST['email']);
                $phone = sanitize_input($_POST['phone']);
                $address = sanitize_input($_POST['address']);
                $password = $_POST['password'];
                $confirm_password = $_POST['confirm_password'];
                $role = in_array($_POST['role'], $allowedRoles) ? $_POST['role'] : 'customer';
                
                // Validate inputs
                if (empty($full_name) || empty($email) || empty($phone) || empty($address)) {
                    $error = "All fields are required";
                } elseif (!validate_email($email)) {
                    $error = "Invalid email format";
                } elseif (!validate_phone($phone)) {
                    $error = "Invalid phone number. Must be 9 digits (e.g., 712345678)";
                } elseif (strlen($password) < 8) {
                    $error = "Password must be at least 8 characters long";
                } elseif (!preg_match('/[A-Z]/', $password)) {
                    $error = "Password must contain at least one uppercase letter";
                } elseif (!preg_match('/[0-9]/', $password)) {
                    $error = "Password must contain at least one number";
                } elseif ($password !== $confirm_password) {
                    $error = "Passwords do not match";
                } else {
                    // Check if email already exists
                    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
                    $stmt->bind_param('s', $email);
                    $stmt->execute();
                    $stmt->store_result();
                    
                    if ($stmt->num_rows > 0) {
                        $error = "Email already registered. Please login instead.";
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        
                        $conn->begin_transaction();
                        
                        try {
                            // Insert user
                            $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, address, password, role) VALUES (?, ?, ?, ?, ?, ?)");
                            $stmt->bind_param('ssssss', $full_name, $email, $phone, $address, $hashed_password, $role);
                            
                            if (!$stmt->execute()) {
                                throw new Exception("Registration failed");
                            }
                            
                            $new_user_id = $stmt->insert_id;
                            
                            // If registering as courier, add to couriers table
                            if ($role == 'courier') {
                                $vehicle_type = sanitize_input($_POST['vehicle_type'] ?? '');
                                $license_plate = sanitize_input($_POST['license_plate'] ?? '');
                                if (!empty($vehicle_type) && !empty($license_plate)) {
                                    $stmt2 = $conn->prepare("INSERT INTO couriers (user_id, vehicle_type, license_plate, status) VALUES (?, ?, ?, 'active')");
                                    $stmt2->bind_param('iss', $new_user_id, $vehicle_type, $license_plate);
                                    $stmt2->execute();
                                    $stmt2->close();
                                }
                            }
                            
                            $conn->commit();
                            
                            security_log('New Registration', "User: $full_name | Email: $email | Role: $role | IP: $ip");
                            
                            // ✅ SUCCESS - Set session variable and redirect to login with success flag
                            $_SESSION['registration_success'] = true;
                            $_SESSION['registration_email'] = $email;
                            
                            header('Location: userlogin.php?registered=1');
                            exit();
                            
                        } catch (Exception $e) {
                            $conn->rollback();
                            security_log('Registration Error', $e->getMessage());
                            $error = "Registration failed. Please try again.";
                        }
                    }
                    $stmt->close();
                }
            }
        } catch (Exception $e) {
            $error = "Invalid request. Please try again.";
            security_log('CSRF Token Validation Failed - Registration', $e->getMessage());
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
    <meta name="description" content="Create an account at Taigon Investments to shop IT products, track orders, and get special offers">
    <title>Register - Taigon Investments | Create Account</title>
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
            padding: 2rem;
        }
        
        .register-container {
            width: 100%;
            max-width: 580px;
        }
        
        .register-card {
            background: white;
            border-radius: 28px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }
        
        .register-header {
            background: linear-gradient(135deg, #0ea5e9, #0369a1);
            padding: 2rem;
            text-align: center;
            color: white;
        }
        
        .register-header i {
            font-size: 2.5rem;
            margin-bottom: 0.75rem;
        }
        
        .register-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .register-header p {
            font-size: 0.85rem;
            opacity: 0.9;
        }
        
        .register-body {
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
        
        .info-box p {
            color: #0369a1;
            font-size: 0.8rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.85rem;
            color: #1e293b;
        }
        
        .form-group label .required {
            color: #ef4444;
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
        
        .input-group input, 
        .input-group select {
            width: 100%;
            padding: 0.85rem 1rem 0.85rem 2.8rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            font-size: 0.95rem;
            font-family: inherit;
            transition: all 0.2s;
            background: white;
        }
        
        .input-group input:focus, 
        .input-group select:focus {
            outline: none;
            border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
        }
        
        .input-group input:focus + i,
        .input-group select:focus + i {
            color: #0ea5e9;
        }
        
        .two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.7rem;
        }
        
        .strength-bar {
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            margin-top: 0.25rem;
            width: 100%;
        }
        
        .strength-bar-fill {
            height: 100%;
            border-radius: 2px;
            width: 0%;
            transition: width 0.3s;
        }
        
        .strength-weak { background: #ef4444; width: 25%; }
        .strength-fair { background: #f59e0b; width: 50%; }
        .strength-good { background: #0ea5e9; width: 75%; }
        .strength-strong { background: #10b981; width: 100%; }
        
        .courier-fields {
            display: none;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }
        
        .btn-register {
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
            margin-top: 0.5rem;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(14, 165, 233, 0.3);
        }
        
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
            font-size: 0.85rem;
            color: #475569;
        }
        
        .login-link a {
            color: #0ea5e9;
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover {
            text-decoration: underline;
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
        
        /* Phone input prefix */
        .phone-input-group {
            position: relative;
        }
        
        .phone-prefix {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 0.85rem;
            font-weight: 500;
            z-index: 1;
        }
        
        .phone-input-group input {
            padding-left: 3.5rem;
        }
        
        /* Responsive */
        @media (max-width: 600px) {
            .register-body {
                padding: 1.5rem;
            }
            
            .register-header {
                padding: 1.5rem;
            }
            
            .two-columns {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }
    </style>
</head>
<body>
<div class="register-container">
    <div class="register-card">
        <div class="register-header">
            <i class="fas fa-user-plus"></i>
            <h1>Create Account</h1>
            <p>Join Taigon Investments for exclusive deals and easy ordering</p>
        </div>
        
        <div class="register-body">
            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <div class="info-box">
                <p><i class="fas fa-gift"></i> Register today and get 5% off your first order! 🎉</p>
            </div>
            
            <form method="POST" id="registerForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                
                <div class="form-group">
                    <label>Full Name <span class="required">*</span></label>
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" name="full_name" placeholder="Enter your full name" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Email Address <span class="required">*</span></label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="your@email.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="two-columns">
                    <div class="form-group">
                        <label>Phone Number <span class="required">*</span></label>
                        <div class="phone-input-group">
                            <span class="phone-prefix">+255</span>
                            <input type="tel" name="phone" placeholder="712345678" required value="<?php echo htmlspecialchars(preg_replace('/^\+255/', '', $_POST['phone'] ?? '')); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Address <span class="required">*</span></label>
                        <div class="input-group">
                            <i class="fas fa-map-marker-alt"></i>
                            <input type="text" name="address" placeholder="Street, city, area" required value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Password <span class="required">*</span></label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" required minlength="8">
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength">
                        <div class="strength-bar">
                            <div class="strength-bar-fill" id="strengthFill"></div>
                        </div>
                        <span id="strengthText" style="color: #64748b;">Enter password</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Confirm Password <span class="required">*</span></label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <button type="button" class="password-toggle" id="toggleConfirmPassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div id="passwordMatch" style="font-size: 0.7rem; margin-top: 0.25rem;"></div>
                </div>
                
                <div class="form-group">
                    <label>Register As</label>
                    <select name="role" id="roleSelect" class="input-group" style="padding-left: 2.8rem;">
                        <option value="customer" <?php echo (isset($_POST['role']) && $_POST['role'] == 'customer') ? 'selected' : ''; ?>>Customer - Shop IT Products</option>
                        <option value="courier" <?php echo (isset($_POST['role']) && $_POST['role'] == 'courier') ? 'selected' : ''; ?>>Courier - Delivery Partner</option>
                    </select>
                </div>
                
                <div id="courierFields" class="courier-fields">
                    <div class="two-columns">
                        <div class="form-group">
                            <label>Vehicle Type</label>
                            <select name="vehicle_type" class="input-group" style="padding-left: 2.8rem;">
                                <option value="">Select vehicle type</option>
                                <option value="Motorcycle">Motorcycle</option>
                                <option value="Car">Car</option>
                                <option value="Van">Van</option>
                                <option value="Truck">Truck</option>
                                <option value="Bicycle">Bicycle</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>License Plate</label>
                            <div class="input-group">
                                <i class="fas fa-car"></i>
                                <input type="text" name="license_plate" placeholder="e.g., T123 ABC">
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-register" id="registerBtn">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
                
                <div class="login-link">
                    Already have an account? <a href="userlogin.php">Login here</a>
                </div>
                
                <div class="back-link">
                    <a href="index.php"><i class="fas fa-arrow-left"></i> Back to Home</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Password strength checker
    const passwordInput = document.getElementById('password');
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.getElementById('strengthText');
    
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            strengthFill.className = 'strength-bar-fill';
            
            if (password.length === 0) {
                strengthFill.style.width = '0%';
                strengthText.innerHTML = 'Enter password';
                strengthText.style.color = '#64748b';
            } else if (strength <= 1) {
                strengthFill.classList.add('strength-weak');
                strengthText.innerHTML = 'Weak password';
                strengthText.style.color = '#ef4444';
            } else if (strength === 2) {
                strengthFill.classList.add('strength-fair');
                strengthText.innerHTML = 'Fair password';
                strengthText.style.color = '#f59e0b';
            } else if (strength === 3) {
                strengthFill.classList.add('strength-good');
                strengthText.innerHTML = 'Good password';
                strengthText.style.color = '#0ea5e9';
            } else {
                strengthFill.classList.add('strength-strong');
                strengthText.innerHTML = 'Strong password!';
                strengthText.style.color = '#10b981';
            }
            
            // Check password match
            checkPasswordMatch();
        });
    }
    
    // Password match checker
    const confirmPasswordInput = document.getElementById('confirm_password');
    const passwordMatchSpan = document.getElementById('passwordMatch');
    
    function checkPasswordMatch() {
        if (!passwordInput || !confirmPasswordInput || !passwordMatchSpan) return;
        
        const password = passwordInput.value;
        const confirm = confirmPasswordInput.value;
        
        if (confirm.length === 0) {
            passwordMatchSpan.innerHTML = '';
            passwordMatchSpan.style.color = '#64748b';
        } else if (password === confirm) {
            passwordMatchSpan.innerHTML = '<i class="fas fa-check-circle"></i> Passwords match';
            passwordMatchSpan.style.color = '#10b981';
        } else {
            passwordMatchSpan.innerHTML = '<i class="fas fa-times-circle"></i> Passwords do not match';
            passwordMatchSpan.style.color = '#ef4444';
        }
    }
    
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
    }
    
    // Toggle password visibility
    const togglePassword = document.getElementById('togglePassword');
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    }
    
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    if (toggleConfirmPassword && confirmPasswordInput) {
        toggleConfirmPassword.addEventListener('click', function() {
            const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPasswordInput.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    }
    
    // Show/hide courier fields
    const roleSelect = document.getElementById('roleSelect');
    const courierFields = document.getElementById('courierFields');
    
    if (roleSelect) {
        roleSelect.addEventListener('change', function() {
            if (this.value === 'courier') {
                courierFields.style.display = 'block';
                // Make fields required
                document.querySelectorAll('#courierFields select, #courierFields input').forEach(field => {
                    if (field.name) field.required = true;
                });
            } else {
                courierFields.style.display = 'none';
                // Remove required attribute
                document.querySelectorAll('#courierFields select, #courierFields input').forEach(field => {
                    if (field.name) field.required = false;
                });
            }
        });
        
        // Trigger on page load if courier was selected
        if (roleSelect.value === 'courier') {
            courierFields.style.display = 'block';
        }
    }
    
    // Form validation
    const registerForm = document.getElementById('registerForm');
    const registerBtn = document.getElementById('registerBtn');
    
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const phone = document.querySelector('input[name="phone"]').value;
            const phoneDigits = phone.replace(/\D/g, '');
            
            if (phoneDigits.length !== 9) {
                e.preventDefault();
                showError('Phone number must be 9 digits (e.g., 712345678)');
                return false;
            }
            
            const password = passwordInput.value;
            const confirm = confirmPasswordInput.value;
            
            if (password !== confirm) {
                e.preventDefault();
                showError('Passwords do not match');
                return false;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                showError('Password must be at least 8 characters');
                return false;
            }
            
            if (!/[A-Z]/.test(password)) {
                e.preventDefault();
                showError('Password must contain at least one uppercase letter');
                return false;
            }
            
            if (!/[0-9]/.test(password)) {
                e.preventDefault();
                showError('Password must contain at least one number');
                return false;
            }
            
            // Disable button to prevent double submission
            if (registerBtn) {
                registerBtn.disabled = true;
                registerBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
            }
        });
    }
    
    function showError(message) {
        // Remove existing error
        const existingError = document.querySelector('.error-message');
        if (existingError) existingError.remove();
        
        // Create new error
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i><span>' + message + '</span>';
        
        // Insert at top
        const registerBody = document.querySelector('.register-body');
        const firstElement = registerBody.firstChild;
        registerBody.insertBefore(errorDiv, firstElement);
        
        // Scroll to error
        errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        // Re-enable button
        if (registerBtn) {
            registerBtn.disabled = false;
            registerBtn.innerHTML = '<i class="fas fa-user-plus"></i> Create Account';
        }
        
        // Auto remove
        setTimeout(() => {
            if (errorDiv) errorDiv.remove();
        }, 5000);
    }
    
    // Auto-format phone number
    const phoneInput = document.querySelector('input[name="phone"]');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 9) value = value.slice(0, 9);
            this.value = value;
        });
    }
</script>
</body>
</html>