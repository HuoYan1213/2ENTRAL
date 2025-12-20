<?php
class PublicController {

    // Redirect to Google's OAuth2 consent screen
    public function googleLogin() {
        $CLIENT_ID = "77356876626-37k1c90kptlt4mjut2th77vcqro3t1j5.apps.googleusercontent.com";
        $REDIRECT_URI = "http://localhost:3000/auth/callback";
        
        $AUTH_URL = "https://accounts.google.com/o/oauth2/v2/auth?".http_build_query([
            'client_id' => $CLIENT_ID,
            'redirect_uri' => $REDIRECT_URI,
            'response_type' => 'code',
            'scope' => "email profile",
            'access_type' => 'online'
        ]);

        header("Location: $AUTH_URL");
        exit();
    }

    // Handle the OAuth2 callback here
    public function callback() {
        require __DIR__ . "/../Model/DB.php";

        if (!isset($_GET['code'])) {
            die("Authorization code not received.");
        }

        $CLIENT_ID = "77356876626-37k1c90kptlt4mjut2th77vcqro3t1j5.apps.googleusercontent.com";
        $CLIENT_SECRET = "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";
        $REDIRECT_URI = "http://localhost:3000/auth/callback";

        $data = [
            "code" => $_GET['code'],
            "client_id" => $CLIENT_ID,
            "client_secret" => $CLIENT_SECRET,
            "redirect_uri" => $REDIRECT_URI,
            "grant_type" => "authorization_code"
        ];

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $ch = curl_init("https://oauth2.googleapis.com/token");
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $token = json_decode($response, true);
        
        if (!isset($token['access_token'])) {
            die("Failed to get access token. Response: " . $response);
        }

        $access_token = $token['access_token'];

        $ch = curl_init("https://www.googleapis.com/oauth2/v2/userinfo?access_token=" . $access_token);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $userinfo = curl_exec($ch);

        $user = json_decode($userinfo, true);
        $email = $user['email'];

        $stmt = $conn->prepare("SELECT * FROM users WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            // Log unauthorized login attempt (User not found)
            $log_uid = 0; // Use 0 for unknown user
            $log_details = "Unauthorized Login Attempt (Unregistered): " . $email;
            $defaultProductID = '2025DEF000';

            try {
                $logStmt = $conn->prepare("INSERT INTO inventory_logs (UserID, LogsDetails, ProductID) VALUES (?, ?, ?)");
                if ($logStmt) {
                    $logStmt->bind_param("iss", $log_uid, $log_details, $defaultProductID);
                    $logStmt->execute();
                    $logStmt->close();
                }
            } catch (Exception $e) {
                // Prevent crash if UserID 0 does not exist in database
                error_log("Failed to log unauthorized attempt: " . $e->getMessage());
            }

            header('Location: /View/Public/AccessDenied.php');
            exit();
        }

        $row = $result->fetch_assoc();

        // Prevent System/Placeholder Account (ID 0) from logging in
        if ($row['UserID'] == 0) {
            header("Location: /View/Public/AccessDenied.php");
            exit();
        }

        if ($row['IsActive'] === 'Inactive') {
            header("Location: /View/Public/AccessDenied.php");
            exit();
        }

        session_start();
        $_SESSION['user'] = [
            'id' => $row['UserID'],
            'email' => $row['Email'],
            'name' => $row['UserName'],
            'role' => $row['Role'],
            'avatar' => $row['ImagePath']
        ];

        // Log Login Action immediately after successful authentication
        $log_uid = $row['UserID'];
        $log_details = "User Login";
        $defaultProductID = '2025DEF000';

        $logStmt = $conn->prepare("INSERT INTO inventory_logs (UserID, LogsDetails, ProductID) VALUES (?, ?, ?)");
        if ($logStmt) {
            $logStmt->bind_param("iss", $log_uid, $log_details, $defaultProductID);
            $logStmt->execute();
            $logStmt->close();
        }
       
        header("Location: /View/Public/Loading.php");
        exit();
    }
}