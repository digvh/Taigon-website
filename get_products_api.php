<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

// Get parameters
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = isset($_GET['per_page']) ? min(50, max(1, (int)$_GET['per_page'])) : 12;
$offset = ($page - 1) * $perPage;

$category = isset($_GET['category']) && $_GET['category'] !== 'all' ? $_GET['category'] : null;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$minPrice = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : null;
$maxPrice = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : null;

// Build WHERE clause
$whereConditions = ["status = 'active'"];
$params = [];
$types = "";

if ($category) {
    $whereConditions[] = "category = ?";
    $params[] = $category;
    $types .= "s";
}

if ($minPrice !== null) {
    $whereConditions[] = "price >= ?";
    $params[] = $minPrice;
    $types .= "d";
}

if ($maxPrice !== null) {
    $whereConditions[] = "price <= ?";
    $params[] = $maxPrice;
    $types .= "d";
}

$whereClause = implode(" AND ", $whereConditions);

// Build ORDER BY clause
switch ($sort) {
    case 'price_asc':
        $orderBy = "price ASC";
        break;
    case 'price_desc':
        $orderBy = "price DESC";
        break;
    case 'name_asc':
        $orderBy = "name ASC";
        break;
    case 'newest':
    default:
        $orderBy = "id DESC";
        break;
}

// Get total count
$countSql = "SELECT COUNT(*) as total FROM products WHERE $whereClause";
$countStmt = $conn->prepare($countSql);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$total = $countResult->fetch_assoc()['total'];
$countStmt->close();

// Get products for current page
$sql = "SELECT id, name, price, quantity, category, image, description FROM products WHERE $whereClause ORDER BY $orderBy LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    // FIXED: Proper image path handling
    $imagePath = '';
    if (!empty($row['image']) && $row['image'] !== 'default-product.jpg') {
        // Check if image exists in the filesystem
        if (file_exists($row['image'])) {
            $imagePath = $row['image'];
        } else {
            // Try alternative path patterns
            $possiblePaths = [
                'images/products/' . basename($row['image']),
                'image/' . basename($row['image']),
                'image/products/' . basename($row['image']),
                'uploads/' . basename($row['image'])
            ];
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $imagePath = $path;
                    break;
                }
            }
        }
    }
    
    // Fallback to placeholder
    if (empty($imagePath) || !file_exists($imagePath)) {
        $imagePath = 'images/placeholder.png';
    }
    
    $products[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'price' => (float)$row['price'],
        'quantity' => (int)$row['quantity'],
        'category' => $row['category'],
        'image' => $imagePath,
        'description' => $row['description']
    ];
}
$stmt->close();

echo json_encode([
    'success' => true,
    'products' => $products,
    'total' => $total,
    'current_page' => $page,
    'total_pages' => ceil($total / $perPage),
    'per_page' => $perPage
]);
?>