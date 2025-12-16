<?php
require_once __DIR__ . "/../Model/DB.php";

class LogsController {

    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }

    public function auditLogs($page = 1, $filters = []) {
        $DATA_LIMIT = 5;
        $offset = ($page - 1) * $DATA_LIMIT;

        $where_stmt = "WHERE 1=1";
        
        if (!empty($filters['user'])) {
            $safeUser = $this->conn->real_escape_string($filters['user']);
            $where_stmt .= " AND inventory_logs.UserID = '$safeUser'";
        }
        if (!empty($filters['action'])) {
            $safeAction = $this->conn->real_escape_string($filters['action']);
            $where_stmt .= " AND inventory_logs.LogsDetails LIKE '%$safeAction%'";
        }
        if (!empty($filters['start'])) {
            $safeStart = $this->conn->real_escape_string($filters['start']);
            $where_stmt .= " AND inventory_logs.CreatedAt >= '$safeStart 00:00:00'";
        }
        if (!empty($filters['end'])) {
            $safeEnd = $this->conn->real_escape_string($filters['end']);
            $where_stmt .= " AND inventory_logs.CreatedAt <= '$safeEnd 23:59:59'";
        }

        $count_stmt = "SELECT COUNT(*) as total FROM inventory_logs $where_stmt";
        $count_result = $this->conn->query($count_stmt);
        $total_row = $count_result->fetch_assoc();
        $total_records = $total_row['total'];
        $total_pages = ceil($total_records / $DATA_LIMIT); 

        $sql = "SELECT inventory_logs.*, users.UserName 
                FROM inventory_logs 
                LEFT JOIN users ON inventory_logs.UserID = users.UserID 
                $where_stmt 
                ORDER BY inventory_logs.CreatedAt DESC 
                LIMIT $DATA_LIMIT OFFSET $offset";

        $result = $this->conn->query($sql);
        $data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        return [
            'LOGS' => $data,
            'TOTAL_PAGES' => $total_pages,
            'CURRENT_PAGE' => (int)$page
        ];
    }

    public function getUsers() {
        $stmt = "SELECT UserID, UserName FROM users ORDER BY UserName ASC";
        $result = $this->conn->query($stmt);

        $users = [];
        if ($result && $result->num_rows > 0) {
            $users = $result->fetch_all(MYSQLI_ASSOC);
        }

        return $users;
    }

    public function exportLogs($filters = []) {
        $where_stmt = "WHERE 1=1";

        if (!empty($filters['user'])) {
            $user = $this->conn->real_escape_string($filters['user']);
            $where_stmt .= " AND inventory_logs.UserID = '$user'";
        }
        if (!empty($filters['action'])) {
            $action = $this->conn->real_escape_string($filters['action']);
            $where_stmt .= " AND inventory_logs.LogsDetails LIKE '%$action%'";
        }
        if (!empty($filters['start'])) {
            $start_date = $this->conn->real_escape_string($filters['start']);
            $where_stmt .= " AND inventory_logs.CreatedAt >= '$start_date 00:00:00'";
        }
        if (!empty($filters['end'])) {
            $end_date = $this->conn->real_escape_string($filters['end']);
            $where_stmt .= " AND inventory_logs.CreatedAt <= '$end_date 23:59:59'";
        }

        $stmt = "SELECT inventory_logs.CreatedAt, users.UserName, inventory_logs.LogsDetails
                 FROM inventory_logs
                 LEFT JOIN users ON inventory_logs.UserID = users.UserID
                 $where_stmt
                 ORDER BY inventory_logs.CreatedAt DESC";
        
        $result = $this->conn->query($stmt);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}