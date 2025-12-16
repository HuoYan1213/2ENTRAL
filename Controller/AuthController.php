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
        // $userData 应该包含从 Google 或表单验证成功后获取的用户信息，
        // 至少包含 Email。
        
        require __DIR__ . "/../Model/DB.php";

        if (!isset($conn)) { 
            global $conn; 
        }

        $userEmail = $userData['email'] ?? ''; 
        
        if (empty($userEmail)) {
            // 无法获取邮箱，返回错误
            error_log("Login attempt failed: Email missing from user data.");
            header("Location: /index.php?error=general_login_error");
            exit();
        }

        // 1. 查询用户的 IsActive 状态和所有会话所需的信息
        $stmt = $conn->prepare("SELECT UserID, UserName, Email, Role, IsActive, ImagePath FROM users WHERE Email = ?");
        $stmt->bind_param("s", $userEmail);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user) {
            // 用户在数据库中不存在 (如果这是 Google 登录，可能需要在这里执行注册逻辑)
            error_log("Login attempt failed: User not found in database for email: " . $userEmail);
            header("Location: /index.php?error=user_not_registered");
            exit();
        }
        
        // 2. 检查 IsActive 状态 👈 关键点
        if ($user['IsActive'] === 'Inactive') {
            error_log("Login blocked: Inactive account for user ID: " . $user['UserID']);
            
            // ❗ 阻止登录并重定向到登录页附带警告参数
            header("Location: /index.php?error=account_inactive"); 
            exit();
        }

        // 3. 状态为 'Active'，继续登录流程
        
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // 设置会话变量
        $_SESSION['user'] = [
            'id' => $user['UserID'],
            'name' => $user['UserName'],
            'email' => $user['Email'],
            'role' => $user['Role'],
            'image' => $user['ImagePath'], // 存储头像路径
            'is_active' => $user['IsActive'] // 存储状态
        ];
        
        // 登录成功，重定向到 Splash 页面
        header("Location: /View/Auth/Splash.php");
        exit();
    }
    
    // -----------------------------------------------------------------
    // 假设您的 Google 登录回调方法 (PublicController.php 中应有的方法)
    // 您需要确保 PublicController 实例化并调用此方法。
    // 如果 PublicController 只是调用 AuthController，请将 PublicController
    // 的 Google 登录逻辑改为调用 checkUserStatusAndLogin()。
    // -----------------------------------------------------------------
    // ⚠️ 注意：此方法仅为示例，您需要将其逻辑集成到您实际的 Google 登录回调中。
    public function googleCallbackExample() {
        // 1. 假设这里是 Google 验证成功的代码...
        // ... (获取 Google 用户的 Profile) ...
        
        // 2. 假设成功获取到 Google 用户的邮箱
        $googleUserData = [
            'email' => 'huoyan0928@gmail.com', // 替换为从 Google 获取的实际邮箱
            'name' => 'Ter Kean Sen' // 可选
        ];

        // 3. 调用核心检查方法
        $this->checkUserStatusAndLogin($googleUserData);
    }
}