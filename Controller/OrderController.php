<?php
session_start();
require_once '../Model/DB.php'; // Ensure path is correct

header('Content-Type: application/json');

// 1. Authorization Check
if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

$userID = $_SESSION['user']['id']; // Taken from your Login session
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['cart'])) {
    echo json_encode(['status' => 'error', 'message' => 'No items in order']);
    exit;
}

$cart = $input['cart'];
$totalAmount = $input['total'];
$supplierID = $input['supplierId'];

// 2. Start Transaction
$conn->begin_transaction();

try {
    // A. Generate Custom PurchaseID (e.g., 25PUR00009)
    $yearPrefix = date('y'); 
    $prefix = $yearPrefix . "PUR";
    
    // Check DB for last ID
    $sql = "SELECT PurchaseID FROM purchase_order WHERE PurchaseID LIKE '$prefix%' ORDER BY PurchaseID DESC LIMIT 1 FOR UPDATE";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $num = intval(substr($row['PurchaseID'], 5)) + 1;
        $newID = $prefix . str_pad($num, 5, '0', STR_PAD_LEFT);
    } else {
        $newID = $prefix . "00001";
    }

    // B. Insert Purchase Order (Status = Pending)
    $stmtOrder = $conn->prepare("INSERT INTO purchase_order (PurchaseID, TotalAmount, Status, CreatedAt, IsActive, UserID, SupplierID) VALUES (?, ?, 'Pending', NOW(), 'Active', ?, ?)");
    $stmtOrder->bind_param("sdii", $newID, $totalAmount, $userID, $supplierID);
    
    if (!$stmtOrder->execute()) throw new Exception("Order Insert Failed");

    // C. Insert Details & Update Stock
    $stmtDetail = $conn->prepare("INSERT INTO purchase_details (Quantity, Subtotal, CreatedAt, IsActive, ProductID, PurchaseID) VALUES (?, ?, NOW(), 'Active', ?, ?)");
    
    // RESTOCK LOGIC: Update products SET Stock = Stock + Qty
    $stmtStock = $conn->prepare("UPDATE products SET Stock = Stock + ? WHERE ProductID = ?");

    foreach ($cart as $item) {
        $qty = $item['qty'];
        $price = $item['price'];
        $subtotal = $qty * $price;
        $prodID = $item['id'];

        // Insert Detail
        $stmtDetail->bind_param("idss", $qty, $subtotal, $prodID, $newID);
        if (!$stmtDetail->execute()) throw new Exception("Detail Insert Failed");

        // Update Stock
        $stmtStock->bind_param("is", $qty, $prodID);
        if (!$stmtStock->execute()) throw new Exception("Stock Update Failed");
    }

    // D. Commit
    $conn->commit();
    echo json_encode(['status' => 'success', 'orderID' => $newID]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>