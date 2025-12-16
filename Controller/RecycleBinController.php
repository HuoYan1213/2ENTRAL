<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();

require_once __DIR__ . "/../Model/DB.php";

$controller = new RecycleBinController($conn);

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    if (method_exists($controller, $action)) {
        if (ob_get_length()) ob_clean();
        $controller->$action();
    }
}

class RecycleBinController {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getItems($page = 1, $limit = 10, $filters = []) {
        $offset = ($page - 1) * $limit;
        $search = $filters['search'] ?? '';
        $type = $filters['type'] ?? '';

        // Base queries for Products and Suppliers
        $productQuery = "SELECT ProductID as ID, ProductName as Name, 'Product' as Type, ImagePath, Category as Detail FROM products WHERE IsActive = 'Inactive' AND ProductID != '2025DEF000'";
        $supplierQuery = "SELECT SupplierID as ID, SupplierName as Name, 'Supplier' as Type, ImagePath, Email as Detail FROM suppliers WHERE IsActive = 'Inactive' AND SupplierID != 0";

        // Construct the combined query based on Type filter
        if ($type === 'Product') {
            $sql = "SELECT * FROM ($productQuery) AS Combined WHERE 1=1";
        } elseif ($type === 'Supplier') {
            $sql = "SELECT * FROM ($supplierQuery) AS Combined WHERE 1=1";
        } else {
            $sql = "SELECT * FROM ($productQuery UNION ALL $supplierQuery) AS Combined WHERE 1=1";
        }

        // Apply Search
        $params = [];
        $types = "";
        if (!empty($search)) {
            $sql .= " AND (Name LIKE ? OR Detail LIKE ? OR ID LIKE ?)";
            $term = "%" . $search . "%";
            $params[] = $term; $params[] = $term; $params[] = $term;
            $types .= "sss";
        }

        // Count Total
        $countSql = "SELECT COUNT(*) as total FROM ($sql) AS CountTable";
        
        $stmt = $this->conn->prepare($countSql);
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $total = $result->fetch_assoc()['total'];
        $stmt->close();

        $totalPages = ceil($total / $limit);

        // Fetch Data with Limit
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return [
            'items' => $items,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'total_records' => $total
        ];
    }

    public function restore() {
        header('Content-Type: application/json');
        $id = $_POST['id'] ?? '';
        $type = $_POST['type'] ?? '';

        if (empty($id) || empty($type)) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }

        $table = ($type === 'Product') ? 'products' : 'suppliers';
        $idCol = ($type === 'Product') ? 'ProductID' : 'SupplierID';
        
        // For Suppliers, check if email is taken by an active supplier
        if ($type === 'Supplier') {
            $stmt = $this->conn->prepare("SELECT Email FROM suppliers WHERE SupplierID = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if ($res) {
                $email = $res['Email'];
                $check = $this->conn->prepare("SELECT SupplierID FROM suppliers WHERE Email = ? AND IsActive = 'Active'");
                $check->bind_param("s", $email);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    echo json_encode(['success' => false, 'message' => 'Cannot restore: Email already used by an active supplier.']);
                    exit;
                }
                $check->close();
            }
        }

        $sql = "UPDATE $table SET IsActive = 'Active' WHERE $idCol = ?";
        $stmt = $this->conn->prepare($sql);
        
        // Bind param type depends on ID type (Product is string, Supplier is int)
        if ($type === 'Product') $stmt->bind_param("s", $id);
        else $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => "$type restored successfully"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        exit;
    }

    public function delete() {
        header('Content-Type: application/json');
        $id = $_POST['id'] ?? '';
        $type = $_POST['type'] ?? '';

        if (empty($id) || empty($type)) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }

        if ($type === 'Product') {
            // Reassign dependencies to Default Product (2025DEF000)
            $defaultProdID = '2025DEF000';
            
            // 1. Update Purchase Details
            $this->conn->query("UPDATE purchase_details SET ProductID = '$defaultProdID' WHERE ProductID = '$id'");

            // 2. Update Inventory Logs
            $this->conn->query("UPDATE inventory_logs SET ProductID = '$defaultProdID' WHERE ProductID = '$id'");

            // Get Image to delete
            $stmt = $this->conn->prepare("SELECT ImagePath FROM products WHERE ProductID = ?");
            $stmt->bind_param("s", $id);
            $stmt->execute();
            $img = $stmt->get_result()->fetch_assoc()['ImagePath'] ?? '';
            $stmt->close();

            $delStmt = $this->conn->prepare("DELETE FROM products WHERE ProductID = ?");
            $delStmt->bind_param("s", $id);
            
            if ($delStmt->execute()) {
                // Delete image file
                if ($img && $img !== 'default-product.png' && strpos($img, 'default') === false) {
                    $path = __DIR__ . "/../Assets/Image/Product/" . $img;
                    if (file_exists($path)) @unlink($path);
                }
                echo json_encode(['success' => true, 'message' => "Product deleted permanently. History moved to Default Product."]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Delete failed: ' . $delStmt->error]);
            }

        } elseif ($type === 'Supplier') {
            // Reassign dependencies to Default Supplier (0)
            $defaultSupID = 0;

            // 1. Update Products linked to this supplier
            $this->conn->query("UPDATE products SET SupplierID = $defaultSupID WHERE SupplierID = $id");

            // 2. Update Purchase Orders linked to this supplier
            $this->conn->query("UPDATE purchase_order SET SupplierID = $defaultSupID WHERE SupplierID = $id");
            
            // Get Image
            $stmt = $this->conn->prepare("SELECT ImagePath FROM suppliers WHERE SupplierID = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $img = $stmt->get_result()->fetch_assoc()['ImagePath'] ?? '';
            $stmt->close();

            $delStmt = $this->conn->prepare("DELETE FROM suppliers WHERE SupplierID = ?");
            $delStmt->bind_param("i", $id);
            
            if ($delStmt->execute()) {
                 if ($img && $img !== 'default.jpg') {
                    $path = __DIR__ . "/../Assets/Image/Supplier/" . $img;
                    if (file_exists($path)) @unlink($path);
                }
                echo json_encode(['success' => true, 'message' => "Supplier deleted permanently. Associated records moved to Default Supplier."]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Delete failed']);
            }
        }
        exit;
    }

    public function bulkAction() {
        header('Content-Type: application/json');
        $actionType = $_POST['action_type'] ?? ''; // 'restore' or 'delete'
        $items = json_decode($_POST['items'] ?? '[]', true);

        if (empty($items) || !in_array($actionType, ['restore', 'delete'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }

        $successCount = 0;
        $failCount = 0;

        foreach ($items as $item) {
            $id = $item['id'];
            $type = $item['type'];
            
            $table = ($type === 'Product') ? 'products' : 'suppliers';
            $idCol = ($type === 'Product') ? 'ProductID' : 'SupplierID';

            if ($actionType === 'restore') {
                $sql = "UPDATE $table SET IsActive = 'Active' WHERE $idCol = ?";
                $stmt = $this->conn->prepare($sql);
                if ($type === 'Product') $stmt->bind_param("s", $id); else $stmt->bind_param("i", $id);
                if ($stmt->execute()) $successCount++; else $failCount++;
                $stmt->close();
            } elseif ($actionType === 'delete') {
                // Reassign dependencies before bulk delete
                if ($type === 'Product') {
                    $defaultProdID = '2025DEF000';
                    $this->conn->query("UPDATE purchase_details SET ProductID = '$defaultProdID' WHERE ProductID = '$id'");
                    $this->conn->query("UPDATE inventory_logs SET ProductID = '$defaultProdID' WHERE ProductID = '$id'");
                } else {
                    $defaultSupID = 0;
                    $this->conn->query("UPDATE products SET SupplierID = $defaultSupID WHERE SupplierID = $id");
                    $this->conn->query("UPDATE purchase_order SET SupplierID = $defaultSupID WHERE SupplierID = $id");
                }

                $sql = "DELETE FROM $table WHERE $idCol = ?";
                $stmt = $this->conn->prepare($sql);
                if ($type === 'Product') $stmt->bind_param("s", $id); else $stmt->bind_param("i", $id);
                if ($stmt->execute()) $successCount++; else $failCount++;
                $stmt->close();
            }
        }

        echo json_encode([
            'success' => true, 
            'message' => "Processed: $successCount success, $failCount failed."
        ]);
        exit;
    }
}
?>