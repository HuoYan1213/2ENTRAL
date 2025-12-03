<?php
$controller = new AuthController();

if (isset($_GET['action'])) {
    $action = $_GET['action'];

    if (is_string($action) && method_exists($controller, $action)){
        $controller->$action();
    }
}

class AuthController {
    public function logout() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        session_unset();
        session_destroy();

        header("Location: /index.php");
        exit();
    }

    public function auditLogs($id = null, $action = null, $sdate = null, $edate = null) {
        require __DIR__ . "/../Model/DB.php";

        if (!isset($conn)) { 
            global $conn; 
        }

        $stmt = "SELECT inventory_logs.CreatedAt, inventory_logs.LogsDetails, users.UserName 
                 FROM inventory_logs 
                 LEFT JOIN users 
                 ON inventory_logs.UserID = users.UserID 
                 WHERE 1=1";

        if (!empty($id) && $id !== '') {
            $safeUserID = $conn->real_escape_string($id);
            $stmt .= " AND inventory_logs.UserID = '$safeUserID'";
        }

        if (!empty($action) && $action !== '') {
            $safeAction = $conn->real_escape_string($action);
            $stmt .= " AND inventory_logs.LogsDetails LIKE '%$safeAction%'";
        }

        if (!empty($sdate)) {
            $safeSDate = $conn->real_escape_string($sdate);
            $stmt .= " AND inventory_logs.CreatedAt >= '$safeSDate 00:00:00'";
        }

        if (!empty($edate)) {
            $safeEDate = $conn->real_escape_string($edate);
            $stmt .= " AND inventory_logs.CreatedAt <= '$safeEDate 23:59:59'";
        }

        $stmt .= " ORDER BY inventory_logs.CreatedAt DESC";

        $result = $conn->query($stmt);
        $logs = [];

        if ($result && $result->num_rows > 0) {
            $logs = $result->fetch_all(MYSQLI_ASSOC);
        }

        return $logs;
    }

    public function getUsers() {
        require __DIR__ . "/../Model/DB.php";

        if (!isset($conn)) {
            global $conn;
        }

        $stmt = "SELECT UserID, UserName FROM users ORDER BY UserName ASC";
        $result = $conn->query($stmt);

        $users = [];
        if ($result && $result->num_rows > 0) {
            $users = $result->fetch_all(MYSQLI_ASSOC);
        }

        return $users;
    }
}