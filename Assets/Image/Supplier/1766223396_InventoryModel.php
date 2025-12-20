<?php
require_once __DIR__ . '/DB.php';

class InventoryModel {
    private $conn;

    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }

    public function getAllProducts() {
        $sql = "SELECT p.*, s.SupplierName, s.Email as SupplierEmail
                FROM products p 
                LEFT JOIN suppliers s ON p.SupplierID = s.SupplierID 
                WHERE p.IsActive = 'Active'
                ORDER BY p.ProductName";
        
        try {
            $result = $this->conn->query($sql);
            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } catch (Exception $e) {
            error_log("Database error in getAllProducts: " . $e->getMessage());
            return [];
        }
    }


    public function getInventoryLogs($limit = 50) {
        $sql = "SELECT il.*, p.ProductName, u.UserName 
                FROM inventory_logs il 
                JOIN products p ON il.ProductID = p.ProductID 
                JOIN users u ON il.UserID = u.UserID 
                WHERE il.IsActive = 'Active'
                ORDER BY il.CreatedAt DESC 
                LIMIT ?";
        
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } catch (Exception $e) {
            error_log("Database error in getInventoryLogs: " . $e->getMessage());
            return [];
        }
    }

    public function adjustStock($productId, $quantity, $reason, $userId) {
        $this->conn->begin_transaction();

        try {
            $sql = "SELECT Stock, ProductName FROM products WHERE ProductID = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $productId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            if (!$row) {
                throw new Exception("Product not found");
            }

            $currentStock = $row['Stock'];
            $productName = $row['ProductName']; 
            $newStock = $currentStock + $quantity;
            
            if ($newStock < 0) {
                throw new Exception("Stock cannot be negative");
            }


            $updateSql = "UPDATE products SET Stock = ? WHERE ProductID = ?";
            $updateStmt = $this->conn->prepare($updateSql);
            $updateStmt->bind_param("is", $newStock, $productId);
            $updateStmt->execute();
            $logDetails = sprintf(
                "Update: %s %s (%d ➡️ %d)", 
                $productName, 
                $reason, 
                $currentStock, 
                $newStock
            );
            
            $logSql = "INSERT INTO inventory_logs (LogsDetails, ProductID, UserID) 
                       VALUES (?, ?, ?)";
            $logStmt = $this->conn->prepare($logSql);
            $logStmt->bind_param("ssi", $logDetails, $productId, $userId);
            $logStmt->execute();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Database error in adjustStock: " . $e->getMessage());
            return false;
        }
    }

    public function getLowStockCount() {
        $sql = "SELECT COUNT(*) as count 
                FROM products 
                WHERE Stock <= LowStockAlert 
                AND IsActive = 'Active'";
        
        try {
            $result = $this->conn->query($sql);
            return $result ? $result->fetch_assoc()['count'] : 0;
        } catch (Exception $e) {
            error_log("Database error in getLowStockCount: " . $e->getMessage());
            return 0;
        }
    }

    private function getProductStock($productId) {
        $sql = "SELECT Stock FROM products WHERE ProductID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['Stock'] ?? 0;
    }

    public function getProductById($productId) {
        $sql = "SELECT p.*, s.SupplierName 
                FROM products p 
                LEFT JOIN suppliers s ON p.SupplierID = s.SupplierID 
                WHERE p.ProductID = ? AND p.IsActive = 'Active'";
        
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $productId);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result ? $result->fetch_assoc() : null;
        } catch (Exception $e) {
            error_log("Database error in getProductById: " . $e->getMessage());
            return null;
        }
    }


public function getInventoryStats() {
    $sql = "SELECT 
                COUNT(*) as total_products,
                SUM(Stock) as total_stock_value,
                SUM(Stock * Price) as total_inventory_value,
                COUNT(CASE WHEN Stock <= LowStockAlert AND Stock > 0 THEN 1 END) as low_stock_count,
                COUNT(CASE WHEN Stock = 0 THEN 1 END) as out_of_stock_count
            FROM products 
            WHERE IsActive = 'Active'";
    
    try {
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_assoc() : [];
    } catch (Exception $e) {
        error_log("Database error in getInventoryStats: " . $e->getMessage());
        return [];
    }
}


    public function getCategoryStats() {
        $sql = "SELECT 
                    Category,
                    COUNT(*) as product_count,
                    SUM(Stock) as total_stock,
                    AVG(Price) as avg_price
                FROM products 
                WHERE IsActive = 'Active'
                GROUP BY Category
                ORDER BY product_count DESC";
        
        try {
            $result = $this->conn->query($sql);
            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } catch (Exception $e) {
            error_log("Database error in getCategoryStats: " . $e->getMessage());
            return [];
        }
    }
}
?>