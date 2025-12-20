<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../model/DB.php';

// Check if database connection exists
if (!isset($conn)) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Check authentication (support multiple session key variants)
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

// Only POST requests allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get and sanitize inputs
$product_name = isset($_POST['product_name']) ? trim($_POST['product_name']) : '';
$category = isset($_POST['category']) ? trim($_POST['category']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
$stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
$low_stock_alert = isset($_POST['low_stock_alert']) ? intval($_POST['low_stock_alert']) : 0;
$supplier_id = isset($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 0;

// Handle file upload
$image_path = '';
if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . '/../../Assets/Image/Product/';
    
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            echo json_encode(['success' => false, 'message' => 'Failed to create directory. Check permissions.']);
            exit;
        }
    }
    
    $file_name = $_FILES['product_image']['name'];
    $file_tmp = $_FILES['product_image']['tmp_name'];
    $file_size = $_FILES['product_image']['size'];
    $file_type = mime_content_type($file_tmp);
    
    // Validate file
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file_type, $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: JPG, PNG, GIF']);
        exit;
    }
    
    if ($file_size > $max_size) {
        echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit']);
        exit;
    }
    
    // Generate unique filename
    $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
    $unique_filename = 'product_' . time() . '_' . uniqid() . '.' . $file_ext;
    $file_path = $upload_dir . $unique_filename;
    
    if (move_uploaded_file($file_tmp, $file_path)) {
        $image_path = $unique_filename;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
        exit;
    }
}

// Server-side validation
if (empty($product_name)) {
    echo json_encode(['success' => false, 'message' => 'Product name is required']);
    exit;
}

if ($price < 0) {
    echo json_encode(['success' => false, 'message' => 'Price cannot be negative']);
    exit;
}

if ($stock < 0) {
    echo json_encode(['success' => false, 'message' => 'Stock cannot be negative']);
    exit;
}

if ($supplier_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Supplier is required']);
    exit;
}

// Validate supplier exists
$supplier_check_sql = "SELECT SupplierID FROM suppliers WHERE SupplierID = ? AND IsActive = 'Active'";
$supplier_check_stmt = $conn->prepare($supplier_check_sql);
if (!$supplier_check_stmt) {
    error_log('DB Prepare Error (supplier_check): ' . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$supplier_check_stmt->bind_param('i', $supplier_id);
$supplier_check_stmt->execute();
$supplier_check_result = $supplier_check_stmt->get_result();
if ($supplier_check_result->num_rows === 0) {
    $supplier_check_stmt->close();
    echo json_encode(['success' => false, 'message' => 'Invalid supplier selected']);
    exit;
}
$supplier_check_stmt->close();

$year = date('y');
$prefix = $year . 'SPO'; // Dynamically generate prefix (e.g., 25SPO)
$max_id_sql = "SELECT MAX(CAST(SUBSTRING(ProductID, 6) AS UNSIGNED)) as max_num FROM products WHERE ProductID LIKE CONCAT(?, '%')";
$max_id_stmt = $conn->prepare($max_id_sql);
if (!$max_id_stmt) {
    error_log('DB Prepare Error (max_id): ' . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$max_id_stmt->bind_param('s', $prefix);
$max_id_stmt->execute();
$max_id_result = $max_id_stmt->get_result();
$max_id_row = $max_id_result->fetch_assoc();
$max_id_stmt->close();

$next_num = ($max_id_row['max_num'] ?? 4) + 1;
$product_id = $prefix . str_pad($next_num, 5, '0', STR_PAD_LEFT);

// Prepare insert statement
$insert_sql = "INSERT INTO products (ProductID, ProductName, Description, Category, Stock, Price, LowStockAlert, ImagePath, SupplierID, IsActive) 
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')";

$insert_stmt = $conn->prepare($insert_sql);

if (!$insert_stmt) {
    error_log('DB Prepare Error (insert): ' . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Database error preparing insert']);
    exit;
}

// Bind parameters: s=string, i=integer, d=decimal
// ProductID(s), ProductName(s), Description(s), Category(s), Stock(i), Price(d), LowStockAlert(i), ImagePath(s), SupplierID(i)
if (!$insert_stmt->bind_param('ssssidisi', $product_id, $product_name, $description, $category, $stock, $price, $low_stock_alert, $image_path, $supplier_id)) {
    error_log('DB Bind Error: ' . $insert_stmt->error);
    echo json_encode(['success' => false, 'message' => 'Database error binding parameters']);
    $insert_stmt->close();
    exit;
}

// Execute insert
if ($insert_stmt->execute()) {
    $insert_stmt->close();
    
    // Log creation
    $logDetails = "Created Product: " . $product_name;
    $logStmt = $conn->prepare("INSERT INTO inventory_logs (LogsDetails, ProductID, UserID) VALUES (?, ?, ?)");
    if ($logStmt) {
        $logStmt->bind_param("ssi", $logDetails, $product_id, $user_id);
        $logStmt->execute();
        $logStmt->close();
    }

    error_log("Product created by user $user_id: $product_id ($product_name)");
    echo json_encode(['success' => true, 'message' => 'Product created successfully', 'product_id' => $product_id]);
} else {
    error_log('DB Execute Error: ' . $insert_stmt->error);
    echo json_encode(['success' => false, 'message' => 'Failed to insert product: ' . $insert_stmt->error]);
    $insert_stmt->close();
}

$conn->close();
