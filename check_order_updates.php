<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['orders'])) {
    echo json_encode(['updates' => []]);
    exit;
}

$orders = $input['orders'];
$updates = [];

foreach ($orders as $order) {
    $orderId = (int)$order['id'];
    $currentStatus = $order['status'];
    
    // Verify access (security check)
    $hasAccess = false;
    
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT order_status FROM orders WHERE id = ? AND user_id = ?");
        $stmt->bind_param('ii', $orderId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $hasAccess = true;
            $newStatus = $row['order_status'];
        }
        $stmt->close();
    } else if (isset($_SESSION['tracking_order'])) {
        $trackingId = $_SESSION['tracking_order']['tracking_id'];
        $email = $_SESSION['tracking_order']['email'];
        $stmt = $conn->prepare("SELECT order_status FROM orders WHERE id = ? AND tracking_id = ? AND email = ?");
        $stmt->bind_param('iss', $orderId, $trackingId, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $hasAccess = true;
            $newStatus = $row['order_status'];
        }
        $stmt->close();
    }
    
    if ($hasAccess && $newStatus !== $currentStatus) {
        $updates[] = [
            'order_id' => $orderId,
            'order_status' => $newStatus,
            'timestamp' => date('c')
        ];
    }
}

echo json_encode(['updates' => $updates]);
?>