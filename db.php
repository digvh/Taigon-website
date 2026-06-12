<?php
// db.php - Database connection with proper error handling

// Load environment variables
$envFile = __DIR__ . '/.env.ini';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '=') !== false && strpos(trim($line), '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

$servername = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';
$dbname = getenv('DB_NAME') ?: 'taigondb';

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    // Try without database selected first
    $conn = new mysqli($servername, $username, $password);
    if ($conn->connect_error) {
        die("Unable to connect to database server. Please try again later.");
    }
    // Create database if not exists
    $conn->query("CREATE DATABASE IF NOT EXISTS `$dbname`");
    $conn->select_db($dbname);
}

$conn->set_charset("utf8mb4");

// Set timezone
$conn->query("SET time_zone = '+03:00'");
?>