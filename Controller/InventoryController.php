<?php
session_start();
require_once __DIR__ . '/../Model/InventoryModel.php';

$controller = new InventoryController();

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
 
    if (method_exists($controller, $action)) {
        $controller->$action();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit();
    }
}

class InventoryController {
    private $model;

    public function __construct() {
        $this->model = new InventoryModel();
    }

    public function index() {
        if (!isset($_SESSION['user'])) {
            header('Location: /View/Public/AccessDenied.php');
            exit();
        }

        $stats = $this->model->getInventoryStats();
        $categoryStats = $this->model->getCategoryStats();
        $totalAlerts = ($stats['low_stock_count'] ?? 0) + ($stats['out_of_stock_count'] ?? 0);

        $data = [
            'products' => $this->model->getAllProducts() ?? [],
            'logs' => $this->model->getInventoryLogs() ?? [],
            'lowStockCount' => $stats['low_stock_count'] ?? 0,
            'outOfStockCount' => $stats['out_of_stock_count'] ?? 0,
            'totalAlerts' => $totalAlerts,
            'totalProducts' => $stats['total_products'] ?? 0,
            'totalStockValue' => $stats['total_inventory_value'] ?? 0,
            'categoryStats' => $categoryStats,
            'currentUser' => $_SESSION['user']['name'] ?? 'User'
        ];

        $this->loadView('Inventory', $data);
    }

    public function adjustStock() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $productId = $_POST['product_id'] ?? '';
            $quantity = intval($_POST['quantity'] ?? 0);
            $reason = $_POST['reason'] ?? 'Manual adjustment';
            $userId = $_SESSION['user']['id'];

            if ($productId && $quantity != 0) {
                $success = $this->model->adjustStock($productId, $quantity, $reason, $userId);
                
                if ($success) {
                    $stats = $this->model->getInventoryStats();
                    $products = $this->model->getAllProducts();
                    $logs = $this->model->getInventoryLogs();
                    foreach ($logs as &$log) {
                        $log['FormattedDate'] = date('M j, Y g:i A', strtotime($log['CreatedAt']));
                    }
                    
                    $responseData = [
                        'success' => true,
                        'message' => 'Stock adjusted successfully!',
                        'products' => $products,
                        'stats' => $stats,
                        'logs' => $logs
                    ];
                    echo json_encode($responseData);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to adjust stock in database.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid input data (ID or Quantity).']);
            }
        } else {
             echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        }
        exit();
    }

    public function getStats() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false]);
            exit();
        }

        $stats = $this->model->getInventoryStats();
        $data = [
            'success' => true,
            'totalProducts' => $stats['total_products'] ?? 0,
            'lowStockCount' => $stats['low_stock_count'] ?? 0,
            'outOfStockCount' => $stats['out_of_stock_count'] ?? 0,
            'totalStockValue' => number_format($stats['total_inventory_value'] ?? 0, 2),
            'totalStockItems' => $stats['total_stock_value'] ?? 0
        ];
        echo json_encode($data);
        exit();
    }


    public function getAllProducts() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'message' => 'User not logged in']);
            exit();
        }

        try {
            $products = $this->model->getAllProducts();
            $stats = $this->model->getInventoryStats();
            $logs = $this->model->getInventoryLogs();
            foreach ($logs as &$log) {
                $log['FormattedDate'] = date('M j, Y g:i A', strtotime($log['CreatedAt']));
            }
            
            $data = [
                'success' => true,
                'products' => $products,
                'stats' => $stats,
                'logs' => $logs
            ];
            echo json_encode($data);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    private function loadView($viewName, $data = []) {
        extract($data);
        $viewPath = __DIR__ . "/../View/Auth/{$viewName}.php";
        
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            die("View file not found: " . $viewPath);
        }
    }
    
}
?>