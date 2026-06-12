<?php
session_start();
include('db.php');

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit();
}

$error = '';
$success = '';
$product = null;
$isEdit = false;
$productId = 0;

// Get product ID for editing
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $productId = (int)$_GET['id'];
    $isEdit = true;
    
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
    } else {
        $error = "Product not found.";
        $isEdit = false;
    }
    $stmt->close();
}

// SECURE IMAGE UPLOAD FUNCTION
function secureUploadImage($file, $existingImage = '') {
    // Allowed mime types and extensions
    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif'
    ];
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    
    // Check if file was uploaded without errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload failed: ' . $file['error']];
    }
    
    // Check file size
    if ($file['size'] > $maxFileSize) {
        return ['success' => false, 'message' => 'File too large. Maximum 5MB allowed.'];
    }
    
    // Verify MIME type using finfo (more secure than just extension)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!isset($allowedTypes[$mimeType])) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, WEBP, and GIF are allowed.'];
    }
    
    // Generate secure filename
    $extension = $allowedTypes[$mimeType];
    $newFilename = 'product_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    
    // Create upload directory if not exists
    $uploadDir = 'uploads/products/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $uploadPath = $uploadDir . $newFilename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['success' => false, 'message' => 'Failed to save uploaded file.'];
    }
    
    // Delete old image if exists and not default
    if (!empty($existingImage) && $existingImage !== 'default-product.jpg' && file_exists($existingImage)) {
        unlink($existingImage);
    }
    
    return ['success' => true, 'path' => $uploadPath];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $warranty_months = (int)($_POST['warranty_months'] ?? 12);
    $status = $_POST['status'] ?? 'active';
    
    // Validation
    if (empty($name)) {
        $error = "Product name is required.";
    } elseif ($price <= 0) {
        $error = "Valid price is required.";
    } elseif ($quantity < 0) {
        $error = "Quantity cannot be negative.";
    } elseif (empty($category)) {
        $error = "Category is required.";
    } else {
        // Handle image upload securely
        $imagePath = $_POST['current_image'] ?? '';
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = secureUploadImage($_FILES['image'], $imagePath);
            if ($uploadResult['success']) {
                $imagePath = $uploadResult['path'];
            } else {
                $error = $uploadResult['message'];
            }
        }
        
        if (empty($error)) {
            if ($isEdit && $productId > 0) {
                $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, quantity = ?, category = ?, brand = ?, model = ?, warranty_months = ?, status = ?, image = ?, updated_at = NOW() WHERE id = ?");
                $stmt->bind_param('ssdississi', $name, $description, $price, $quantity, $category, $brand, $model, $warranty_months, $status, $imagePath, $productId);
                
                if ($stmt->execute()) {
                    $success = "Product updated successfully!";
                    $stmt2 = $conn->prepare("SELECT * FROM products WHERE id = ?");
                    $stmt2->bind_param('i', $productId);
                    $stmt2->execute();
                    $product = $stmt2->get_result()->fetch_assoc();
                    $stmt2->close();
                } else {
                    $error = "Failed to update product: " . $conn->error;
                }
                $stmt->close();
            } else {
                $stmt = $conn->prepare("INSERT INTO products (name, description, price, quantity, category, brand, model, warranty_months, status, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('ssdississi', $name, $description, $price, $quantity, $category, $brand, $model, $warranty_months, $status, $imagePath);
                
                if ($stmt->execute()) {
                    $success = "Product created successfully!";
                    $productId = $stmt->insert_id;
                    $isEdit = true;
                    $stmt2 = $conn->prepare("SELECT * FROM products WHERE id = ?");
                    $stmt2->bind_param('i', $productId);
                    $stmt2->execute();
                    $product = $stmt2->get_result()->fetch_assoc();
                    $stmt2->close();
                } else {
                    $error = "Failed to create product: " . $conn->error;
                }
                $stmt->close();
            }
        }
    }
}

// Fetch categories for dropdown
$categories = [];
$catResult = $conn->query("SELECT categories_name FROM categore WHERE status = 'active' ORDER BY categories_name");
while ($row = $catResult->fetch_assoc()) {
    $categories[] = $row['categories_name'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Edit Product' : 'Add New Product'; ?> - Taigon Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; color: #0f172a; line-height: 1.5; }
        .admin-container { display: flex; min-height: 100vh; }
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s;
            z-index: 100;
        }
        .sidebar-header { padding: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 1rem; }
        .sidebar-header h2 { font-size: 1.2rem; display: flex; align-items: center; gap: 0.5rem; }
        .sidebar-header h2 i { color: #0ea5e9; }
        .sidebar-menu { padding: 0 1rem; }
        .sidebar-menu h3 { font-size: 0.7rem; text-transform: uppercase; color: #64748b; letter-spacing: 1px; padding: 0.75rem 0.5rem; margin-top: 0.5rem; }
        .sidebar-menu ul { list-style: none; }
        .sidebar-menu li a { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; color: #cbd5e1; text-decoration: none; border-radius: 12px; transition: all 0.2s; font-size: 0.9rem; }
        .sidebar-menu li a:hover, .sidebar-menu li a.active { background: rgba(14,165,233,0.2); color: white; }
        .sidebar-menu li a i { width: 20px; font-size: 1rem; }
        .main-content { flex: 1; margin-left: 280px; padding: 1.5rem; }
        .top-bar {
            background: white;
            border-radius: 16px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }
        .page-title h1 { font-size: 1.3rem; font-weight: 600; }
        .page-title p { font-size: 0.8rem; color: #475569; }
        .user-info { display: flex; align-items: center; gap: 1rem; }
        .logout-btn { background: #ef4444; color: white; padding: 0.5rem 1rem; border-radius: 2rem; text-decoration: none; font-size: 0.8rem; font-weight: 500; }
        .form-card { background: white; border-radius: 24px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .form-header { padding: 1.25rem 1.5rem; background: linear-gradient(135deg, #0ea5e9, #0369a1); color: white; font-weight: 600; display: flex; justify-content: space-between; align-items: center; }
        .form-header a { color: white; text-decoration: none; background: rgba(255,255,255,0.2); padding: 0.4rem 1rem; border-radius: 2rem; font-size: 0.8rem; transition: all 0.2s; }
        .form-header a:hover { background: rgba(255,255,255,0.3); }
        .form-body { padding: 2rem; }
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group.full-width { grid-column: span 2; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.85rem; color: #0f172a; }
        .form-group label .required { color: #ef4444; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.9rem;
            font-family: inherit;
            transition: all 0.2s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(14,165,233,0.25);
        }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .image-upload-area {
            border: 2px dashed #e2e8f0;
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 1rem;
        }
        .image-upload-area:hover { border-color: #0ea5e9; background: rgba(14,165,233,0.05); }
        .image-upload-area i { font-size: 2rem; color: #94a3b8; margin-bottom: 0.5rem; }
        .image-preview { margin-top: 1rem; text-align: center; }
        .image-preview img { max-width: 200px; max-height: 200px; border-radius: 12px; border: 1px solid #e2e8f0; padding: 0.5rem; background: #f8fafc; }
        .action-buttons { display: flex; gap: 1rem; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e2e8f0; }
        .btn-primary { background: linear-gradient(135deg, #0ea5e9, #0369a1); color: white; border: none; padding: 0.8rem 2rem; border-radius: 12px; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(14,165,233,0.25); }
        .btn-secondary { background: #f8fafc; border: 1px solid #e2e8f0; color: #475569; padding: 0.8rem 2rem; border-radius: 12px; font-size: 0.9rem; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-danger { background: #ef4444; color: white; border: none; padding: 0.8rem 2rem; border-radius: 12px; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .alert { padding: 1rem 1.25rem; border-radius: 12px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; }
        .alert-success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .alert-error { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
        .mobile-menu-toggle { display: none; position: fixed; top: 1rem; left: 1rem; z-index: 200; background: #0ea5e9; color: white; border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.mobile-open { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .mobile-menu-toggle { display: flex; align-items: center; justify-content: center; }
            .form-grid { grid-template-columns: 1fr; }
            .form-group.full-width { grid-column: span 1; }
        }
        @media (max-width: 768px) {
            .main-content { padding: 1rem; }
            .top-bar { flex-direction: column; gap: 0.5rem; text-align: center; }
            .action-buttons { flex-direction: column; }
            .action-buttons a, .action-buttons button { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
    <button class="mobile-menu-toggle" id="mobileMenuToggle"><i class="fas fa-bars"></i></button>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header"><h2><i class="fas fa-chart-line"></i> Taigon Admin</h2></div>
        <div class="sidebar-menu">
            <h3>Main</h3>
            <ul><li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li></ul>
            <h3>Management</h3>
            <ul>
                <li><a href="admin_dashboard.php?section=orders"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <li><a href="admin_dashboard.php?section=products"><i class="fas fa-box"></i> Products</a></li>
                <li><a href="admin_dashboard.php?section=inventory"><i class="fas fa-warehouse"></i> Inventory</a></li>
            </ul>
            <h3>Account</h3>
            <ul>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                <li><a href="index.php"><i class="fas fa-store"></i> View Store</a></li>
            </ul>
        </div>
    </aside>
    <main class="main-content">
        <div class="top-bar">
            <div class="page-title">
                <h1><?php echo $isEdit ? 'Edit Product' : 'Add New Product'; ?></h1>
                <p><?php echo $isEdit ? 'Modify product details and inventory' : 'Create a new product for your store'; ?></p>
            </div>
            <div class="user-info">
                <span><i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        <div class="form-card">
            <div class="form-header">
                <span><i class="fas <?php echo $isEdit ? 'fa-edit' : 'fa-plus-circle'; ?>"></i> <?php echo $isEdit ? 'Edit Product' : 'Add New Product'; ?></span>
                <a href="admin_dashboard.php?section=products"><i class="fas fa-arrow-left"></i> Back to Products</a>
            </div>
            <div class="form-body">
                <?php if ($success): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?php echo htmlspecialchars($success); ?></span></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><span><?php echo htmlspecialchars($error); ?></span></div>
                <?php endif; ?>
                <form method="POST" action="edit_product.php<?php echo $isEdit ? '?id=' . $productId : ''; ?>" enctype="multipart/form-data">
                    <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                    <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($product['image'] ?? ''); ?>">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label>Product Name <span class="required">*</span></label>
                            <input type="text" name="name" required value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" placeholder="Enter product name">
                        </div>
                        <div class="form-group full-width">
                            <label>Description</label>
                            <textarea name="description" placeholder="Product description, features, specifications..."><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Price (TShs) <span class="required">*</span></label>
                            <input type="number" name="price" step="1000" required value="<?php echo $product['price'] ?? ''; ?>" placeholder="0">
                        </div>
                        <div class="form-group">
                            <label>Quantity / Stock <span class="required">*</span></label>
                            <input type="number" name="quantity" required value="<?php echo $product['quantity'] ?? '0'; ?>" placeholder="0">
                        </div>
                        <div class="form-group">
                            <label>Category <span class="required">*</span></label>
                            <select name="category" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo (isset($product['category']) && $product['category'] == $cat) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="active" <?php echo (isset($product['status']) && $product['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo (isset($product['status']) && $product['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                <option value="out_of_stock" <?php echo (isset($product['status']) && $product['status'] == 'out_of_stock') ? 'selected' : ''; ?>>Out of Stock</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Brand</label>
                            <input type="text" name="brand" value="<?php echo htmlspecialchars($product['brand'] ?? ''); ?>" placeholder="e.g., Dell, HP, Lenovo">
                        </div>
                        <div class="form-group">
                            <label>Model</label>
                            <input type="text" name="model" value="<?php echo htmlspecialchars($product['model'] ?? ''); ?>" placeholder="e.g., XPS 15, Spectre x360">
                        </div>
                        <div class="form-group">
                            <label>Warranty (Months)</label>
                            <input type="number" name="warranty_months" value="<?php echo $product['warranty_months'] ?? '12'; ?>" placeholder="12">
                        </div>
                        <div class="form-group full-width">
                            <label>Product Image</label>
                            <div class="image-upload-area" onclick="document.getElementById('imageInput').click()">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Click to upload or drag and drop</p>
                                <small>Supported formats: JPG, PNG, WEBP, GIF (Max 5MB)</small>
                                <input type="file" name="image" id="imageInput" accept="image/jpeg,image/png,image/webp,image/gif" style="display: none;" onchange="previewImage(this)">
                            </div>
                            <div class="image-preview" id="imagePreview">
                                <?php if (!empty($product['image']) && file_exists($product['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="Current product image">
                                    <div class="current-image-label">Current Image</div>
                                <?php else: ?>
                                    <img src="images/placeholder.png" alt="No image" id="previewImg" style="display: none;">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="action-buttons">
                        <button type="submit" class="btn-primary"><i class="fas fa-save"></i> <?php echo $isEdit ? 'Update Product' : 'Create Product'; ?></button>
                        <a href="admin_dashboard.php?section=products" class="btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                        <?php if ($isEdit && $productId > 0): ?>
                            <button type="button" class="btn-danger" onclick="confirmDelete(<?php echo $productId; ?>)"><i class="fas fa-trash"></i> Delete Product</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <script>
        const mobileToggle = document.getElementById('mobileMenuToggle');
        const sidebar = document.getElementById('sidebar');
        mobileToggle?.addEventListener('click', () => { sidebar.classList.toggle('mobile-open'); });
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 1024) {
                if (!sidebar.contains(e.target) && !mobileToggle.contains(e.target)) {
                    sidebar.classList.remove('mobile-open');
                }
            }
        });
        function previewImage(input) {
            const preview = document.getElementById('previewImg');
            const previewContainer = document.getElementById('imagePreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (!preview) {
                        const img = document.createElement('img');
                        img.id = 'previewImg';
                        img.src = e.target.result;
                        previewContainer.innerHTML = '';
                        previewContainer.appendChild(img);
                    } else {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    }
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        function confirmDelete(productId) {
            if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
                window.location.href = 'delete_product.php?id=' + productId;
            }
        }
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const name = document.querySelector('input[name="name"]').value.trim();
            const price = document.querySelector('input[name="price"]').value;
            const quantity = document.querySelector('input[name="quantity"]').value;
            const category = document.querySelector('select[name="category"]').value;
            if (!name) { e.preventDefault(); alert('Please enter product name'); return false; }
            if (!price || parseFloat(price) <= 0) { e.preventDefault(); alert('Please enter a valid price'); return false; }
            if (quantity === '' || parseInt(quantity) < 0) { e.preventDefault(); alert('Please enter a valid quantity'); return false; }
            if (!category) { e.preventDefault(); alert('Please select a category'); return false; }
            return true;
        });
    </script>
</body>
</html>