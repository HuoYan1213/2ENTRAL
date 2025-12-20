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

        // Log logout action
        if (isset($_SESSION['user']['id'])) {
            require __DIR__ . "/../Model/DB.php";
            if (!isset($conn)) global $conn;

            $userID = $_SESSION['user']['id'];
            $logDetails = "User Logout";
            $defaultProductID = '2025DEF000'; // Use default ID for system logs

            $stmt = $conn->prepare("INSERT INTO inventory_logs (LogsDetails, ProductID, UserID) VALUES (?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("ssi", $logDetails, $defaultProductID, $userID);
                $stmt->execute();
                $stmt->close();
            }
        }

        session_unset();
        session_destroy();

        header("Location: /index.php");
        exit();
    }

    private function checkUserStatusAndLogin(array $userData) {     
        require __DIR__ . "/../Model/DB.php";

        if (!isset($conn)) { 
            global $conn; 
        }

        $userEmail = $userData['email'] ?? ''; 
        
        if (empty($userEmail)) {
            error_log("Login attempt failed: Email missing from user data.");
            header("Location: /index.php?error=general_login_error");
            exit();
        }

        $stmt = $conn->prepare("SELECT UserID, UserName, Email, Role, IsActive, ImagePath FROM users WHERE Email = ?");
        $stmt->bind_param("s", $userEmail);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user) {
            
            // Log unauthorized attempt
            $log_uid = 0;
            $log_details = "Unauthorized Login Attempt (Unregistered): " . $userEmail;
            $defaultProductID = '2025DEF000';
            
            try {
                $logStmt = $conn->prepare("INSERT INTO inventory_logs (UserID, LogsDetails, ProductID) VALUES (?, ?, ?)");
                if ($logStmt) {
                    $logStmt->bind_param("iss", $log_uid, $log_details, $defaultProductID);
                    $logStmt->execute();
                    $logStmt->close();
                }
            } catch (Exception $e) {
                error_log("Failed to log unauthorized attempt: " . $e->getMessage());
            }

            error_log("Login attempt failed: User not found in database for email: " . $userEmail);
            header("Location: /index.php?error=user_not_registered");
            exit();
        }
        
        // Prevent System/Placeholder Account (ID 0) from logging in
        if ($user['UserID'] == 0) {
            header("Location: /index.php?error=account_inactive"); 
            exit();
        }

        if ($user['IsActive'] === 'Inactive') {
            error_log("Login blocked: Inactive account for user ID: " . $user['UserID']);
            
            header("Location: /index.php?error=account_inactive"); 
            exit();
        }
        
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['user'] = [
            'id' => $user['UserID'],
            'name' => $user['UserName'],
            'email' => $user['Email'],
            'role' => $user['Role'],
            'image' => $user['ImagePath'],
            'is_active' => $user['IsActive'] 
        ];
        
        header("Location: /View/Auth/Splash.php");
        exit();
    }
    
    public function googleCallbackExample() {
        
        $googleUserData = [
            'email' => 'huoyan0928@gmail.com',
            'name' => 'Ter Kean Sen'
        ];
        $this->checkUserStatusAndLogin($googleUserData);
    }
}