<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../model/DB.php';


if (!isset($conn)) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}


$user_id = null;
if (isset($_SESSION['user'])) {
    $user_id = $_SESSION['user']['UserID'] ?? $_SESSION['user']['id'] ?? $_SESSION['user']['user_id'] ?? null;
}
if (!$user_id) {
    $user_id = $_SESSION['User_ID'] ?? $_SESSION['UserID'] ?? $_SESSION['id'] ?? null;
}

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Please log in first']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}


$product_id = isset($_POST['product_id']) ? trim($_POST['product_id']) : '';
$product_name = isset($_POST['product_name']) ? trim($_POST['product_name']) : '';
$category = isset($_POST['category']) ? trim($_POST['category']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
$stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
$low_stock_alert = isset($_POST['low_stock_alert']) ? intval($_POST['low_stock_alert']) : 0;
$supplier_id = isset($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 0;


if (empty($product_id)) {
    echo json_encode(['success' => false, 'message' => 'Product ID is required']);
    exit;
}

$stmt = $conn->prepare("SELECT ImagePath FROM products WHERE ProductID = ?");
$stmt->bind_param("s", $product_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

$current_image_filename = $res->fetch_assoc()['ImagePath'];
$stmt->close();

$image_filename_for_db = $current_image_filename;

if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
    
    $upload_dir = __DIR__ . '/../../Assets/Image/Product/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_name = $_FILES['product_image']['name'];
    $file_tmp = $_FILES['product_image']['tmp_name'];
    $file_size = $_FILES['product_image']['size'];
    $file_type = mime_content_type($file_tmp);
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file_type, $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type.']);
        exit;
    }
    
    if ($file_size > $max_size) {
        echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit']);
        exit;
    }
    
    $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
    $unique_filename = 'product_' . time() . '_' . uniqid() . '.' . $file_ext;
    $target_file = $upload_dir . $unique_filename;
    
    if (move_uploaded_file($file_tmp, $target_file)) {
        if ($current_image_filename && 
            !str_contains($current_image_filename, '/') && 
            $current_image_filename !== 'default-product.png' &&
            file_exists($upload_dir . $current_image_filename)) {
            
            @unlink($upload_dir . $current_image_filename);
        }

        $image_filename_for_db = $unique_filename;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload image. Check folder permissions.']);
        exit;
    }
}

$update_sql = "UPDATE products 
               SET ProductName = ?, 
                   Category = ?, 
                   Description = ?, 
                   Price = ?, 
                   Stock = ?, 
                   LowStockAlert = ?,
                   SupplierID = ?,
                   ImagePath = ? 
               WHERE ProductID = ?";

$update_stmt = $conn->prepare($update_sql);

if (!$update_stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$update_stmt->bind_param(
    "sssdiiiss",
    $product_name,
    $category,
    $description,
    $price,
    $stock,
    $low_stock_alert,
    $supplier_id,
    $image_filename_for_db,
    $product_id
);

if ($update_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update product: ' . $update_stmt->error]);
}

$update_stmt->close();
$conn->close();
?>