<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require 'db.php';

$query = "
    SELECT 
        o.id, o.first_name, o.last_name, o.email, 
        DATE_FORMAT(o.order_date, '%M %d, %Y') as order_date,
        o.order_status,
        COALESCE(SUM(oi.quantity * oi.price), 0) as total_amount
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    GROUP BY o.id
    ORDER BY o.order_date DESC
";

$result = $conn->query($query);

if (!$result) {
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}

$orders = [];

while ($row = $result->fetch_assoc()) {
    $orders[] = [
        'id' => $row['id'],
        'first_name' => $row['first_name'],
        'last_name' => $row['last_name'],
        'email' => $row['email'],
        'order_date' => $row['order_date'],
        'order_status' => $row['order_status'],
        'total_amount' => (float)$row['total_amount']
    ];
}

echo json_encode(['orders' => $orders]);
?>