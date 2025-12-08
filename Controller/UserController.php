<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();

$controller = new UserController();

if (isset($_GET['action'])) {
    $action = $_GET['action'];

    if (is_string($action) && method_exists($controller, $action)) {
        if (ob_get_length()) {
            ob_clean();
        }
        $controller->$action();
    }
}

class UserController {
    public function logout() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        session_unset();
        session_destroy();

        header("Location: /index.php");
        exit();
    }

    public function getUsers() {
        require __DIR__ . "/../Model/DB.php";

        if (!isset($conn)) {
            global $conn;
        }

        $stmt = "SELECT UserID, UserName, Email, CreatedAt, Role, ImagePath, IsActive FROM users ORDER BY UserID ASC";
        $result = $conn->query($stmt);

        $users = [];
        if ($result && $result->num_rows > 0) {
            $users = $result->fetch_all(MYSQLI_ASSOC);
        }

        return $users;
    }

    public function getUser($userID = null) {
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json');
        
        require __DIR__ . "/../Model/DB.php";

        if (!isset($conn)) { 
            global $conn; 
        }

        if ($userID === null && isset($_GET['userID'])) {
            $userID = $_GET['userID'];
        }

        if (empty($userID)) {
            echo json_encode(null);
            exit();
        }

        $stmt = $conn->prepare("SELECT UserID, UserName, Email, Role, IsActive, ImagePath FROM users WHERE UserID = ?");
        $stmt->bind_param("i", $userID);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            if (empty($user['ImagePath']) || $user['ImagePath'] === 'default.png') {
                $user['ImagePath'] = 'default_user_' . $user['UserID'] . '.png';
            }
            
            echo json_encode($user);
        } else {
            echo json_encode(null);
        }
        exit();
    }
    
    private function checkAvatarExists($imagePath) {
        if (empty($imagePath) || $imagePath === 'default.png') {
            return false;
        }
        
        // 修正路径：回退一层 (/../) 而不是两层
        $uploadDir = __DIR__ . "/../Assets/Image/User/";
        $fullPath = $uploadDir . $imagePath;
        
        return file_exists($fullPath) && is_file($fullPath);
    }
    
    public function getUserAvatar($userID = null) {
        require __DIR__ . "/../Model/DB.php";
        
        if (!isset($conn)) {
            global $conn;
        }
        
        if ($userID === null) {
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            
            if (isset($_SESSION['user']['id'])) {
                $userID = $_SESSION['user']['id'];
            }
        }
        
        if (empty($userID)) {
            return 'default.png';
        }
        
        $stmt = $conn->prepare("SELECT ImagePath FROM users WHERE UserID = ?");
        $stmt->bind_param("i", $userID);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (empty($user['ImagePath']) || $user['ImagePath'] === 'default.png') {
                 return 'default_user_' . $userID . '.png';
            }
            return $user['ImagePath'];
        }
        
        return 'default.png';
    }

    public function editUser() {
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json');
        
        require __DIR__ . "/../Model/DB.php";

        if (!isset($conn)) { 
            global $conn; 
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log("Invalid request method in editUser.");
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
            exit();
        }

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== "Manager") {
            echo json_encode(['success' => false, 'message' => 'Access Denied. Only Manager can edit users.']);
            exit();
        }

        $userID = $_POST['userID'] ?? '';
        $userName = trim($_POST['userName'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'Employee';
        $isActive = $_POST['isActive'] ?? 'Active'; 
        $currentImagePath = $_POST['currentImagePath'] ?? '';
        $deleteAvatar = $_POST['deleteAvatar'] ?? '0'; 

        $currentLoggedInUserID = $_SESSION['user']['id'];
        $targetUserStmt = $conn->prepare("SELECT Role, IsActive, ImagePath FROM users WHERE UserID = ?");
        $targetUserStmt->bind_param("i", $userID);
        $targetUserStmt->execute();
        $targetResult = $targetUserStmt->get_result();
        $targetUser = $targetResult->fetch_assoc();
        $targetUserStmt->close();
        
        if (!$targetUser) {
            echo json_encode(['success' => false, 'message' => 'Target user not found.']);
            exit();
        }
        
        $isTargetManager = $targetUser['Role'] === 'Manager';
        $isEditingSelf = $userID == $currentLoggedInUserID;
        
        if ($isTargetManager && !$isEditingSelf) {
            echo json_encode(['success' => false, 'message' => 'Permission denied: Cannot modify another Manager\'s account.']);
            exit();
        }
        
        if ($isEditingSelf && $isTargetManager) {
            $role = $targetUser['Role'];
            $isActive = $targetUser['IsActive'];
        }

        $checkStmt = $conn->prepare("SELECT UserID FROM users WHERE Email = ? AND UserID != ?");
        if ($checkStmt) {
            $checkStmt->bind_param("si", $email, $userID);
            $checkStmt->execute();
            $checkStmt->store_result();
            if ($checkStmt->num_rows > 0) {
                $checkStmt->close();
                echo json_encode(['success' => false, 'message' => 'Email already exists for another user.']);
                exit();
            }
            $checkStmt->close();
        }

        $imagePath = $currentImagePath;
        
        // FIX: 修正路径并添加文件夹检查
        $uploadDir = __DIR__ . "/../Assets/Image/User/";
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $fileExtension = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            $newFileName = $userID . '-' . uniqid() . '.' . $fileExtension;
            $uploadFile = $uploadDir . $newFileName;
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadFile)) {
                $imagePath = $newFileName; 
                
                if (!empty($currentImagePath) && strpos($currentImagePath, 'default_user_') === false) {
                    $oldFilePath = $uploadDir . $currentImagePath;
                    if (file_exists($oldFilePath) && is_file($oldFilePath)) {
                        @unlink($oldFilePath);
                    }
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to upload new avatar.']);
                exit();
            }
        } elseif ($deleteAvatar === '1') {
            $imagePath = 'default_user_' . $userID . '.png';
            
            if (!empty($currentImagePath) && strpos($currentImagePath, 'default_user_') === false) {
                $oldFilePath = $uploadDir . $currentImagePath;
                if (file_exists($oldFilePath) && is_file($oldFilePath)) {
                    @unlink($oldFilePath);
                }
            }
        }
        
        $stmt = $conn->prepare("UPDATE users SET UserName = ?, Email = ?, Role = ?, ImagePath = ?, IsActive = ? WHERE UserID = ?");
        
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
            exit();
        }
        
        $stmt->bind_param("sssssi", $userName, $email, $role, $imagePath, $isActive, $userID);

        if ($stmt->execute()) {
            error_log("Database update successful for user: " . $userID);
            
            if (isset($_SESSION['user']) && $_SESSION['user']['id'] == $userID) {
                $_SESSION['user']['image'] = $imagePath;
                $_SESSION['user']['name'] = $userName;
                $_SESSION['user']['email'] = $email;
                $_SESSION['user']['role'] = $role;
                $_SESSION['user']['is_active'] = $isActive;
                
                session_write_close();
                session_start();
            }
            
            $logStmt = $conn->prepare("INSERT INTO inventory_logs (LogsDetails, ProductID, UserID) VALUES (?, ?, ?)");
            if ($logStmt) {
                $logDetails = "Edited user: " . $userName . " (ID: " . $userID . ")";
                $validProductID = '25BAD00001'; 
                $currentUserID = $_SESSION['user']['id'] ?? 1; 
                $logStmt->bind_param("ssi", $logDetails, $validProductID, $currentUserID);
                $logStmt->execute();
                $logStmt->close();
            }

            echo json_encode([
                'success' => true, 
                'message' => 'User updated successfully', 
                'imagePath' => $imagePath,
                'userName' => $userName,
                'email' => $email,
                'role' => $role,
                'isActive' => $isActive
            ]);
        } else {
            error_log("Database update failed: " . $stmt->error);
            echo json_encode([
                'success' => false, 
                'message' => 'Database update failed: ' . $stmt->error,
                'debug' => ['userID' => $userID, 'isActive' => $isActive]
            ]);
        }
        
        $stmt->close();
        exit();
    }
    
    public function updateUserRole() {
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json');
        
        require __DIR__ . "/../Model/DB.php";

        if (!isset($conn)) { 
            global $conn; 
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit();
        }

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== "Manager") {
            echo json_encode(['success' => false, 'message' => 'Access Denied. Only Manager can update roles.']);
            exit();
        }

        $userID = $_POST['userID'] ?? '';
        $role = $_POST['role'] ?? '';

        if (empty($userID) || empty($role)) {
            echo json_encode(['success' => false, 'message' => 'User ID and Role are required']);
            exit();
        }
        
        $currentLoggedInUserID = $_SESSION['user']['id'];

        $targetUserStmt = $conn->prepare("SELECT Role FROM users WHERE UserID = ?");
        $targetUserStmt->bind_param("i", $userID);
        $targetUserStmt->execute();
        $targetResult = $targetUserStmt->get_result();
        $targetUser = $targetResult->fetch_assoc();
        $targetUserStmt->close();
        
        if (!$targetUser) {
            echo json_encode(['success' => false, 'message' => 'Target user not found.']);
            exit();
        }
        
        if ($targetUser['Role'] === 'Manager') {
            echo json_encode(['success' => false, 'message' => 'Permission denied: Cannot modify a Manager\'s role.']);
            exit();
        }

        $stmt = $conn->prepare("UPDATE users SET Role = ? WHERE UserID = ?");
        $stmt->bind_param("si", $role, $userID);

        if ($stmt->execute()) {
            
            if (isset($_SESSION['user']) && $_SESSION['user']['id'] == $userID) {
                $_SESSION['user']['role'] = $role;
                session_write_close();
            }
            
            echo json_encode(['success' => true, 'message' => 'User role updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update user role: ' . $stmt->error]);
        }
        
        $stmt->close();
        exit();
    }

    public function updateUserStatus() {
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json');
        
        require __DIR__ . "/../Model/DB.php";

        if (!isset($conn)) { 
            global $conn; 
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit();
        }

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== "Manager") {
            echo json_encode(['success' => false, 'message' => 'Access Denied. Only Manager can update status.']);
            exit();
        }

        $userID = $_POST['userID'] ?? '';
        $status = $_POST['status'] ?? '';

        if (empty($userID) || empty($status)) {
            echo json_encode(['success' => false, 'message' => 'User ID and Status are required']);
            exit();
        }

        $isActiveValue = ($status === 'Active') ? 'Active' : 'Inactive';

        $currentLoggedInUserID = $_SESSION['user']['id'];

        $targetUserStmt = $conn->prepare("SELECT Role FROM users WHERE UserID = ?");
        $targetUserStmt->bind_param("i", $userID);
        $targetUserStmt->execute();
        $targetResult = $targetUserStmt->get_result();
        $targetUser = $targetResult->fetch_assoc();
        $targetUserStmt->close();
        
        if (!$targetUser) {
            echo json_encode(['success' => false, 'message' => 'Target user not found.']);
            exit();
        }
        
        if ($targetUser['Role'] === 'Manager') {
            echo json_encode(['success' => false, 'message' => 'Permission denied: Cannot modify a Manager\'s status.']);
            exit();
        }

        $stmt = $conn->prepare("UPDATE users SET IsActive = ? WHERE UserID = ?");
        
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn->error]);
            exit();
        }
        
        $stmt->bind_param("si", $isActiveValue, $userID);

        if ($stmt->execute()) {
            
            if (isset($_SESSION['user']) && $_SESSION['user']['id'] == $userID) {
                $_SESSION['user']['is_active'] = $isActiveValue;
                session_write_close();
            }
            
            echo json_encode(['success' => true, 'message' => 'User status updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update user status: ' . $stmt->error]);
        }
        
        $stmt->close();
        exit();
    }

    public function addUser() {
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json');
        
        require __DIR__ . "/../Model/DB.php";

        if (!isset($conn)) { 
            global $conn; 
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit();
        }

        $userName = $_POST['userName'] ?? '';
        $email = $_POST['email'] ?? '';
        $role = $_POST['role'] ?? 'Employee';
        $isActive = $_POST['isActive'] ?? 'Active';
        
        if (empty($userName) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Name and Email are required']);
            exit();
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email format']);
            exit();
        }
        $checkStmt = $conn->prepare("SELECT UserID FROM users WHERE Email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            exit();
        }
        $checkStmt->close();

        $uploadPath = NULL; 
        $hasUpload = false;
        
        // FIX: 修正路径并添加文件夹检查
        $uploadDir = __DIR__ . "/../Assets/Image/User/";
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $fileTmpName = $_FILES['avatar']['tmp_name'];
            $fileExtension = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            
            $newFileName = uniqid() . '_' . time() . '.' . $fileExtension;
            $targetPath = $uploadDir . $newFileName;
            
            if (move_uploaded_file($fileTmpName, $targetPath)) {
                $uploadPath = $newFileName;
                $hasUpload = true;
            } else {
                error_log("UPLOAD FAILED for user: " . $userName);
            }
        }
        
        $initialImagePath = $uploadPath ?: 'TEMP_UID_'.time().rand(1000, 9999); 

        $stmt = $conn->prepare("INSERT INTO users (UserName, Email, Role, IsActive, ImagePath) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn->error]);
            exit();
        }
        
        $stmt->bind_param("sssss", $userName, $email, $role, $isActive, $initialImagePath); 

        if ($stmt->execute()) {
            $newUserID = $conn->insert_id;
            $stmt->close();
            
            if (!$hasUpload) {
                $finalImagePath = 'default_user_' . $newUserID . '.png';
                
                $updateStmt = $conn->prepare("UPDATE users SET ImagePath = ? WHERE UserID = ?");
                if ($updateStmt) {
                    $updateStmt->bind_param("si", $finalImagePath, $newUserID);
                    $updateStmt->execute();
                    $updateStmt->close();
                }
            } else {
                $finalImagePath = $uploadPath;
            }
            
            $logStmt = $conn->prepare("INSERT INTO inventory_logs (LogsDetails, ProductID, UserID) VALUES (?, ?, ?)");
            if ($logStmt) {
                $logDetails = "Added new user: " . $userName;
                $validProductID = '25BAD00001'; 
                if (session_status() == PHP_SESSION_NONE) { session_start(); }
                $currentUserID = $_SESSION['user']['id'] ?? 1; 
                $logStmt->bind_param("ssi", $logDetails, $validProductID, $currentUserID);
                $logStmt->execute();
                $logStmt->close();
            }

            echo json_encode(['success' => true, 'message' => 'User added successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add user: ' . $stmt->error]);
            $stmt->close();
        }
        
        exit();
    }

    public function editProfile() {
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: application/json');
        
        require __DIR__ . "/../Model/DB.php";

        if (!isset($conn)) { 
            global $conn; 
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit();
        }

        $userID = $_POST['userID'] ?? '';
        $userName = $_POST['userName'] ?? '';
        $email = $_POST['email'] ?? '';
        $currentImagePath = $_POST['currentImagePath'] ?? '';
        $deleteAvatar = $_POST['deleteAvatar'] ?? '0';

        if (empty($userID) || empty($userName) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email format']);
            exit();
        }

        $checkStmt = $conn->prepare("SELECT UserID FROM users WHERE Email = ? AND UserID != ?");
        if (!$checkStmt) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
            exit();
        }
        
        $checkStmt->bind_param("si", $email, $userID);
        if (!$checkStmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $checkStmt->error]);
            exit();
        }
        
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            exit();
        }
        $checkStmt->close();

        $imagePath = $currentImagePath;
        
        // FIX: 修正路径及语法错误
        $uploadDir = __DIR__ . "/../Assets/Image/User/";
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        if ($deleteAvatar === '1') {
            $imagePath = 'default_user_' . $userID . '.png';
            
            if (!empty($currentImagePath) && strpos($currentImagePath, 'default_user_') === false) {
                $oldFilePath = $uploadDir . $currentImagePath;
                if (file_exists($oldFilePath) && is_file($oldFilePath)) {
                    @unlink($oldFilePath);
                }
            }
        } 
        else if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            
            $fileExtension = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($fileExtension, $allowedTypes)) {
                echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, JPEG, PNG, GIF, and WebP are allowed.']);
                exit();
            }
            
            if ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
                echo json_encode(['success' => false, 'message' => 'File size too large. Maximum size is 2MB.']);
                exit();
            }
            
            $newFileName = uniqid() . '_' . time() . '.' . $fileExtension;
            $targetPath = $uploadDir . $newFileName;
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetPath)) {
                $imagePath = $newFileName;
                
                if (!empty($currentImagePath) && strpos($currentImagePath, 'default_user_') === false && $imagePath !== $currentImagePath) {
                    $oldFilePath = $uploadDir . $currentImagePath;
                    if (file_exists($oldFilePath) && is_file($oldFilePath)) {
                        @unlink($oldFilePath);
                    }
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to upload image.']);
                exit();
            }
        }

        $passwordUpdate = "";
        if (isset($_POST['password']) && !empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $passwordUpdate = ", Password = ?";
        }

        if (!empty($passwordUpdate)) {
            $stmt = $conn->prepare("UPDATE users SET UserName = ?, Email = ?, ImagePath = ?, Password = ? WHERE UserID = ?");
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
                exit();
            }
            $stmt->bind_param("ssssi", $userName, $email, $imagePath, $password, $userID);
        } else {
            $stmt = $conn->prepare("UPDATE users SET UserName = ?, Email = ?, ImagePath = ? WHERE UserID = ?");
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
                exit();
            }
            $stmt->bind_param("sssi", $userName, $email, $imagePath, $userID);
        }

        if ($stmt->execute()) {
            
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            
            if (isset($_SESSION['user']) && $_SESSION['user']['id'] == $userID) {
                $_SESSION['user']['image'] = $imagePath;
                $_SESSION['user']['name'] = $userName;
                $_SESSION['user']['email'] = $email;
                
                session_write_close();
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Profile updated successfully', 
                'imagePath' => $imagePath,
                'userName' => $userName
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update profile: ' . $stmt->error]);
        }
        
        $stmt->close();
        exit();
    }
}
?>