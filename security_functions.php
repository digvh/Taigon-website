<?php
// security_functions.php - Core security functions

// Security constants
define('CSRF_TOKEN_LENGTH', 32);
define('SESSION_TIMEOUT', 1800); // 30 minutes
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Ensure logs directory exists
$logsDir = __DIR__ . '/logs';
if (!file_exists($logsDir)) {
    mkdir($logsDir, 0755, true);
}
if (!file_exists($logsDir . '/security.log')) {
    file_put_contents($logsDir . '/security.log', '');
}

// Generate CSRF token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        throw new Exception('CSRF token validation failed');
    }
    return true;
}

// CSRF token field for forms
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCSRFToken()) . '">';
}

// XSS Protection - Escape output
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// Sanitize input
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Validate email
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate phone (Tanzanian format)
function validate_phone($phone) {
    // Remove any non-digit characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    // Check if it's a valid Tanzanian number (9 or 10 digits)
    return (preg_match('/^[0-9]{9}$/', $phone) || preg_match('/^0[0-9]{9}$/', $phone));
}

// Rate limiting
function check_rate_limit($key, $maxAttempts = MAX_LOGIN_ATTEMPTS, $timeout = LOGIN_LOCKOUT_TIME) {
    if (!isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = ['attempts' => 0, 'first_attempt' => time()];
    }
    
    $data = $_SESSION['rate_limit'][$key];
    
    // Reset if timeout has passed
    if (time() - $data['first_attempt'] > $timeout) {
        $_SESSION['rate_limit'][$key] = ['attempts' => 1, 'first_attempt' => time()];
        return true;
    }
    
    // Check if exceeded
    if ($data['attempts'] >= $maxAttempts) {
        return false;
    }
    
    // Increment attempts
    $_SESSION['rate_limit'][$key]['attempts']++;
    return true;
}

// Session security - FIXED VERSION
function secure_session_start() {
    // Only set session ini settings if no session is active
    if (session_status() === PHP_SESSION_NONE) {
        // Use secure cookie settings BEFORE session start
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', 1);
        ini_set('session.use_only_cookies', 1);
        
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', 1);
        }
        
        session_start();
    }
    
    // Regenerate session ID periodically (only if session is active)
    if (session_status() === PHP_SESSION_ACTIVE) {
        if (!isset($_SESSION['last_regeneration'])) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) { // Every 5 minutes
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
            session_unset();
            session_destroy();
            return false;
        }
        $_SESSION['last_activity'] = time();
    }
    
    return true;
}

// Alternative: Simple session start without trying to change ini settings
function simple_session_start() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return true;
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

// Check if user is admin
function is_admin() {
    return isset($_SESSION['admin_logged_in']) || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
}

// Require login
function require_login() {
    if (!is_logged_in() && !is_admin()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: userlogin.php');
        exit();
    }
}

// Require admin
function require_admin() {
    if (!is_admin()) {
        header('HTTP/1.1 403 Forbidden');
        die('Access denied. Admin privileges required.');
    }
}

// Log security events
function security_log($event, $details = '') {
    $logDir = __DIR__ . '/logs';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $logEntry = date('Y-m-d H:i:s') . " | " . ($_SERVER['REMOTE_ADDR'] ?? 'CLI') . " | " . $event . " | " . $details . PHP_EOL;
    error_log($logEntry, 3, $logDir . '/security.log');
}

// Prevent SQL injection - Use prepared statements everywhere
function prepare_statement($conn, $sql, $types, ...$params) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        security_log('SQL Prepare Error', $conn->error);
        return false;
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    return $stmt;
}

// Input validation for IDs
function validate_id($id) {
    return filter_var($id, FILTER_VALIDATE_INT) && $id > 0;
}

// Sanitize array inputs
function sanitize_array($array) {
    $sanitized = [];
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $sanitized[$key] = sanitize_array($value);
        } else {
            $sanitized[$key] = sanitize_input($value);
        }
    }
    return $sanitized;
}

// Generate random token
function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}
?>