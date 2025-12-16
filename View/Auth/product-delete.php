<?php
header('Content-Type: application/json');
session_start();

// Prevent PHP warnings/HTML leaking into JSON responses; log instead
ini_set('display_errors', '0');
error_reporting(E_ALL);
ob_start();

function send_json($arr) {
    // Capture any accidental output and log it for debugging
    $buf = ob_get_clean();
    if ($buf && trim($buf) !== '') {
        error_log("product-delete unexpected output:\n" . $buf);
    }
    header('Content-Type: application/json');
    global $DEBUG, $DEBUG_PAYLOAD;
    if (!empty($DEBUG) && !empty($DEBUG_PAYLOAD)) {
        $arr['__debug'] = $DEBUG_PAYLOAD;
    }
    echo json_encode($arr);
    exit;
}

// Temporary debug switch: set to true to return extra debug info in JSON responses
$DEBUG = true;
$DEBUG_PAYLOAD = [];

// Exception handler: log and return JSON
set_exception_handler(function($e) {
    error_log('product-delete uncaught exception: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    $buf = ob_get_clean();
    if ($buf && trim($buf) !== '') error_log("product-delete buffer before exception:\n" . $buf);
    echo json_encode(['success' => false, 'message' => 'Server error (exception)']);
    exit;
});

// Shutdown handler: catch fatal errors and return JSON
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && ($err['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR))) {
        error_log('product-delete fatal error: ' . print_r($err, true));
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        $buf = ob_get_clean();
        if ($buf && trim($buf) !== '') error_log("product-delete buffer before shutdown:\n" . $buf);
        echo json_encode(['success' => false, 'message' => 'Server fatal error occurred']);
    }
});

// Database connection
require_once __DIR__ . '/../../model/DB.php';

// Check if user is logged in and has permission to delete
if (!isset($_SESSION['user']) && !isset($_SESSION['User_ID']) && !isset($_SESSION['UserID']) && !isset($_SESSION['id'])) {
    send_json(['success' => false, 'message' => 'Unauthorized: Not logged in']);
}

// Get user ID from session (support multiple key variants)
$user_id = $_SESSION['user']['UserID'] ?? $_SESSION['user']['id'] ?? $_SESSION['user']['user_id'] ?? $_SESSION['User_ID'] ?? $_SESSION['UserID'] ?? $_SESSION['id'] ?? null;

if (!$user_id) {
    send_json(['success' => false, 'message' => 'Unauthorized: User ID not found']);
}

// Only POST requests allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['success' => false, 'message' => 'Invalid request method']);
}

// Get product ID
$product_id = isset($_POST['product_id']) ? trim($_POST['product_id']) : '';

// Validation
if (empty($product_id)) {
    send_json(['success' => false, 'message' => 'Product ID is required']);
}

// Check if product exists and get image path
$check_stmt = $conn->prepare("SELECT ProductID, ProductName, ImagePath FROM products WHERE ProductID = ?");
if (!$check_stmt) {
    error_log('DB Prepare Error (check): ' . $conn->error);
    send_json(['success' => false, 'message' => 'Database error']);
}

$check_stmt->bind_param("s", $product_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    $check_stmt->close();
    send_json(['success' => false, 'message' => 'Product not found']);
}

$product_row = $result->fetch_assoc();
$image_path = $product_row['ImagePath'];
$product_name = $product_row['ProductName'];
$check_stmt->close();

// Check for dependent rows that would block deletion
$dep_counts = [];
$dep_queries = [
    'purchase_details' => "SELECT COUNT(*) AS cnt FROM purchase_details WHERE ProductID = ?",
    'inventory_logs' => "SELECT COUNT(*) AS cnt FROM inventory_logs WHERE ProductID = ?"
];
foreach ($dep_queries as $k => $q) {
    $s = $conn->prepare($q);
    if ($s) {
        $s->bind_param('s', $product_id);
        $s->execute();
        $r = $s->get_result();
        $rowc = $r->fetch_assoc();
        $dep_counts[$k] = intval($rowc['cnt'] ?? 0);
        $s->close();
    } else {
        error_log('DB Prepare Error (dep check ' . $k . '): ' . $conn->error);
        $dep_counts[$k] = -1;
    }
}

// If 'soft' param provided, perform soft-delete (set IsActive = 'Inactive')
if (isset($_POST['soft']) && $_POST['soft'] === '1') {
    $upd = $conn->prepare("UPDATE products SET IsActive = 'Inactive' WHERE ProductID = ?");
    if (!$upd) {
        error_log('DB Prepare Error (soft delete): ' . $conn->error);
        send_json(['success' => false, 'message' => 'Database error preparing soft-delete']);
    }
    $upd->bind_param('s', $product_id);
    if ($upd->execute()) {
        $upd->close();

        // Log soft delete
        $logDetails = "Deactivated Product: " . $product_name;
        $logStmt = $conn->prepare("INSERT INTO inventory_logs (LogsDetails, ProductID, UserID) VALUES (?, ?, ?)");
        if ($logStmt) {
            $logStmt->bind_param("ssi", $logDetails, $product_id, $user_id);
            $logStmt->execute();
            $logStmt->close();
        }

        send_json(['success' => true, 'message' => 'Product deactivated (soft-deleted) successfully', 'deps' => $dep_counts]);
    } else {
        $err = $upd->error;
        $upd->close();
        error_log('DB Execute Error (soft delete): ' . $err);
        send_json(['success' => false, 'message' => 'Failed to deactivate product: ' . $err, 'deps' => $dep_counts]);
    }
}

// If dependencies exist, abort and return counts
$total_deps = 0;
foreach ($dep_counts as $c) { if ($c > 0) $total_deps += $c; }
if ($total_deps > 0) {
    send_json(['success' => false, 'message' => 'Product has dependent records and cannot be deleted. Use soft delete instead.', 'deps' => $dep_counts]);
}

// Delete product from database
$delete_stmt = $conn->prepare("DELETE FROM products WHERE ProductID = ?");
if (!$delete_stmt) {
    error_log('DB Prepare Error (delete): ' . $conn->error);
    send_json(['success' => false, 'message' => 'Database error']);
}

$delete_stmt->bind_param("s", $product_id);

if ($delete_stmt->execute()) {
    // Delete image file if it exists and is not the default
    if ($image_path && $image_path !== '/Assets/Image/default-product.png' && $image_path !== '/Assets/Image/default-product.png' && strpos($image_path, 'products/') !== false) {
        // Build a safe absolute path and ensure it's inside our products folder
        $base_dir = realpath(__DIR__ . '/../../Assets/Image/Product/');
        $candidate = realpath(__DIR__ . '/../../' . ltrim($image_path, '/\\'));
        if ($candidate && $base_dir && strpos($candidate, $base_dir) === 0 && file_exists($candidate)) {
            if (!@unlink($candidate)) {
                error_log("Failed to unlink product image: $candidate");
            }
        }
    }
    
    $delete_stmt->close();

    // Log hard delete (using default system ID since product is gone)
    $logDetails = "Deleted Product Permanently: " . $product_name . " (" . $product_id . ")";
    $defaultProductID = '2025DEF000';
    $logStmt = $conn->prepare("INSERT INTO inventory_logs (LogsDetails, ProductID, UserID) VALUES (?, ?, ?)");
    if ($logStmt) {
        $logStmt->bind_param("ssi", $logDetails, $defaultProductID, $user_id);
        $logStmt->execute();
        $logStmt->close();
    }

    error_log("Product deleted by user $user_id: $product_id");
    send_json(['success' => true, 'message' => 'Product deleted successfully']);
} else {
    error_log('DB Execute Error: ' . $delete_stmt->error);
    $delete_stmt->close();
    send_json(['success' => false, 'message' => 'Failed to delete product: ' . $delete_stmt->error]);
}

$conn->close();
?>
