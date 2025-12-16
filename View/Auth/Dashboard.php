<?php
session_start();

// Access control: only allow logged-in users
if (!isset($_SESSION['user'])) {
    header('Location: /View/Public/AccessDenied.php');
    exit();
}
else {
    require_once __DIR__ . "/../../Controller/UserController.php";
    require_once __DIR__ . "/../../Model/DB.php";

    // Log Login Action (Once per session)
    if (!isset($_SESSION['login_logged'])) {
        $log_uid = $_SESSION['user']['id'];
        $log_details = "User Login";
        $defaultProductID = '2025DEF000'; // Default ProductID for system logs
        
        $stmt = $conn->prepare("INSERT INTO inventory_logs (UserID, LogsDetails, ProductID) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("iss", $log_uid, $log_details, $defaultProductID);
            $stmt->execute();
            $stmt->close();
        }
        $_SESSION['login_logged'] = true;
    }

    $controller = new UserController();
    
    $latestImagePath = $controller->getUserAvatar($_SESSION['user']['id']);
    
    if ($latestImagePath !== ($_SESSION['user']['image'] ?? '')) {
        $_SESSION['user']['image'] = $latestImagePath;
        
        session_write_close();
        session_start();
    }

    $CURRENT_NAME = $_SESSION['user']['name'] ?? '';
    $CURRENT_ROLE = $_SESSION['user']['role'] ?? '';
    $AVATAR_PATH = $_SESSION['user']['avatar'] ?? '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../Assets/CSS/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="shortcut icon" href="../../Assets/Icon/2ENTRALIcon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

</head>
<body>
    <nav>
        <div class="sidebar">
            <div class="logo">
                <img src="../../Assets/Icon/2ENTRAL-2.png"/>
            </div>
            <div class="overview">
                <span>Overview</span>
                <button class="ajax-button" data-get="InstrumentPanel.php" data-title="Dashboard Overview"><i class="fa-solid fa-gauge"></i>Dashboard</button>
            </div>

            <div class="management">
                <span>Management</span>
                <button class="ajax-button" data-get="Inventory.php" data-title="Inventory Management"><i class="fa-solid fa-boxes-stacked"></i>Inventory</button>
                <button class="ajax-button" data-get="Order.php" data-title="Order Management"><i class="fa-solid fa-file-invoice"></i>Orders</button>
                <button class="ajax-button" data-get="Product.php" data-title="Product List"><i class="fa-solid fa-tags"></i>Products</button>
                <button class="ajax-button" data-get="Supplier.php" data-title="Supplier List"><i class="fa-solid fa-truck-field"></i>Suppliers</button>
            </div>

            <?php if ($CURRENT_ROLE === "Manager") { ?>
                <div class="system">
                    <span>System</span>
                    <button class="ajax-button" data-get="AuditLogs.php" data-title="Audit Logs"><i class="fa-solid fa-file-shield"></i>Audit Logs</button>
                    <button class="ajax-button" data-get="RecycleBin.php"><i class="fa-solid fa-box-archive"></i>Recycle Bin</button>
                    <button class="ajax-button" data-get="UsersRoles.php"><i class="fa-solid fa-users-gear"></i>Users & Roles</button>
                </div>
            <?php } ?>
        </div>
    </nav>

    <div class="page-content">
        <header>
            <h2>Dashboard Overview</h2>
            <div class="user-profile" style="display: flex; align-items: center;">
                <button class="ajax-button profile-button" data-get="Profile.php" data-title="Profile" style="background: transparent; border: none; cursor: pointer; display: flex; align-items: center; gap: 10px; font-size: 1rem; color: #333;">
                    <?php
                    $hasAvatar = $AVATAR_PATH && !str_contains($AVATAR_PATH, 'default_user_') && $AVATAR_PATH !== 'default.png';
                    $avatarUrl = '';
                    if ($hasAvatar) {
                        $fullPath = realpath(__DIR__ . "/../../Assets/Image/User/" . $AVATAR_PATH);
                        if ($fullPath && file_exists($fullPath)) {
                            $avatarUrl = "../../Assets/Image/User/" . $AVATAR_PATH . "?t=" . time();
                        } else {
                            $hasAvatar = false;
                        }
                    }
                    if ($hasAvatar) {
                        echo '<img src="' . $avatarUrl . '" id="profile-avatar" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">';
                        echo '<i class="fa-solid fa-circle-user" style="display:none; font-size: 30px;"></i>';
                    } else {
                        echo '<img src="" id="profile-avatar" style="display:none; width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">';
                        echo '<i class="fa-solid fa-circle-user" style="font-size: 30px;"></i>';
                    }
                    ?>
                </button>
            </div>
        </header>

        <div id="ajax-result"></div>
    </div>
    
<script>
    $(document).ready(function () {
        // Initialize Dark Mode from LocalStorage
        if (localStorage.getItem('darkMode') === 'enabled') {
            $('body').addClass('dark-mode');
        }

        let default_page = "InstrumentPanel.php";

        $('#ajax-result').load(default_page + window.location.search);
        $(".ajax-button[data-get='" + default_page + "']").addClass("active");
        
        $(window).on('userProfileUpdated', function(e) {
            console.log('User profile updated event received:', e.detail);    
            const profileButton = $('.profile-button');
            
            if (e.detail.userImage && !e.detail.userImage.startsWith('default_user_')) {
                const timestamp = new Date().getTime();
                const newAvatarUrl = '../../Assets/Image/User/' + e.detail.userImage + '?t=' + timestamp;
                
                const profileAvatar = profileButton.find('#profile-avatar');
                
                if (profileAvatar.length) {
                    profileAvatar.attr('src', newAvatarUrl);
                    
                    profileAvatar.css('display', 'inline-block');
                    profileButton.find('i.fa-solid.fa-circle-user').hide();
                }
            } 
            else {
                const profileAvatar = profileButton.find('#profile-avatar');
                if (profileAvatar.length) {
                    profileAvatar.hide();
                    profileButton.find('i.fa-solid.fa-circle-user').show();
                }
            }
        });
    });

    $(document).on('click', '.ajax-button', function() {
        let page = $(this).data('get');
        let title = $(this).data('title') || $(this).text().trim();
        
        $('header h2').text(title);
        $('#ajax-result').load(page);
        $('.ajax-button').removeClass("active");
        $(this).addClass("active");
    });

    $(document).on('click', '.profile-button', function () {
        $('.profile-button img').removeClass('enabled');
        $(this).find('img').addClass('enabled'); 
    });
</script>
</body>
</html>