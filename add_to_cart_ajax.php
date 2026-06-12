<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to add items to cart']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;
$userId = $_SESSION['user_id'];

if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

// Check if product exists and has sufficient stock
$stmt = $conn->prepare("SELECT quantity FROM products WHERE id = ? AND status = 'active'");
$stmt->bind_param('i', $productId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

$product = $result->fetch_assoc();
if ($product['quantity'] < $quantity) {
    echo json_encode(['success' => false, 'message' => 'Insufficient stock available']);
    exit;
}
$stmt->close();

// Check if product already in cart
$stmt = $conn->prepare("SELECT cart_id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
$stmt->bind_param('ii', $userId, $productId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Update existing cart item
    $cartItem = $result->fetch_assoc();
    $newQuantity = $cartItem['quantity'] + $quantity;
    
    // Check if new quantity exceeds stock
    if ($newQuantity > $product['quantity']) {
        echo json_encode(['success' => false, 'message' => 'Quantity exceeds available stock']);
        exit;
    }
    
    $stmt2 = $conn->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE cart_id = ?");
    $stmt2->bind_param('ii', $newQuantity, $cartItem['cart_id']);
    $stmt2->execute();
    $stmt2->close();
} else {
    // Add new cart item
    $stmt2 = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
    $stmt2->bind_param('iii', $userId, $productId, $quantity);
    $stmt2->execute();
    $stmt2->close();
}
$stmt->close();

// Get total cart count
$stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$cartCount = $result->fetch_assoc()['total'] ?? 0;
$stmt->close();

echo json_encode([
    'success' => true,
    'message' => 'Product added to cart',
    'cart_count' => $cartCount
]);
?>