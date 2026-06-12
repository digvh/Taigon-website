<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_GET['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Order ID required']);
    exit;
}

$orderId = (int)$_GET['order_id'];

// Verify user has access to this order
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $orderId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $hasAccess = $result->num_rows > 0;
    $stmt->close();
} else if (isset($_SESSION['tracking_order'])) {
    $trackingId = $_SESSION['tracking_order']['tracking_id'];
    $email = $_SESSION['tracking_order']['email'];
    $stmt = $conn->prepare("SELECT id FROM orders WHERE id = ? AND tracking_id = ? AND email = ?");
    $stmt->bind_param('iss', $orderId, $trackingId, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $hasAccess = $result->num_rows > 0;
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!$hasAccess) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Fetch order items
$stmt = $conn->prepare("
    SELECT oi.product_id, oi.quantity, oi.price, p.name as product_name 
    FROM order_items oi 
    LEFT JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmt->bind_param('i', $orderId);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = [
        'product_id' => $row['product_id'],
        'product_name' => $row['product_name'] ?? 'Product #' . $row['product_id'],
        'quantity' => (int)$row['quantity'],
        'price' => (float)$row['price']
    ];
}
$stmt->close();

echo json_encode(['success' => true, 'items' => $items]);
?>