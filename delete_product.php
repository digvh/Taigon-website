<?php
session_start();

// Check admin login
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit();
}

include('db.php');

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $productId = (int)$_GET['id'];
    
    // Get product image to delete
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Delete image file if exists and not default
        if (!empty($row['image']) && $row['image'] !== 'default-product.jpg' && file_exists($row['image'])) {
            unlink($row['image']);
        }
    }
    $stmt->close();
    
    // Delete product
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param('i', $productId);
    
    if ($stmt->execute()) {
        $_SESSION['admin_message'] = 'Product deleted successfully!';
    } else {
        $_SESSION['admin_message'] = 'Failed to delete product: ' . $conn->error;
    }
    $stmt->close();
}

header('Location: admin_dashboard.php?section=products');
exit();
?>