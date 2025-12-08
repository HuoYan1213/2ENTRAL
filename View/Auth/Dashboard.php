<?php
session_start();

// Access control: only allow logged-in users
if (!isset($_SESSION['user'])) {
    header('Location: /View/Public/AccessDenied.php');
    exit();
}
else {
    require_once __DIR__ . "/../../Controller/UserController.php";
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
    <link rel="stylesheet" href="../../Assets/CSS/app.css">
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
                <button class="ajax-button" data-get="InstrumentPanel.php"><i class="fa-solid fa-gauge"></i>Dashboard</button>
            </div>

            <div class="management">
                <span>Management</span>
                <button class="ajax-button" data-get="Inventory.php"><i class="fa-solid fa-boxes-stacked"></i>Inventory</button>
                <button class="ajax-button" data-get="Order.php"><i class="fa-solid fa-file-invoice"></i>Orders</button>
                <button class="ajax-button" data-get="Product.php"><i class="fa-solid fa-tags"></i>Products</button>
                <button class="ajax-button" data-get="Supplier.php"><i class="fa-solid fa-truck-field"></i>Suppliers</button>
            </div>

            <?php if ($CURRENT_ROLE === "Manager") { ?>
                <div class="system">
                    <span>System</span>
                    <button class="ajax-button" data-get="AuditLogs.php"><i class="fa-solid fa-file-shield"></i>Audit Logs</button>
                    <button class="ajax-button" data-get="RecycleBin.php"><i class="fa-solid fa-box-archive"></i>Recycle Bin</button>
                    <button class="ajax-button" data-get="UsersRoles.php"><i class="fa-solid fa-users-gear"></i>Users & Roles</button>

                </div>
            <?php } ?>

            <div class="profile">
                <span>Account</span>
                <button class="ajax-button profile-button" data-get="Profile.php">
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
                        echo '<img src="' . $avatarUrl . '" id="profile-avatar">';
                        echo '<i class="fa-solid fa-circle-user" style="display:none;"></i>';
                    } else {
                        echo '<img src="" id="profile-avatar" style="display:none;">';
                        echo '<i class="fa-solid fa-circle-user"></i>';
                    }
                    ?>
                    <?php echo htmlspecialchars($CURRENT_NAME) ?>
                </button>
                <button id="logout-button" onclick="window.location.href='/Controller/UserController.php?action=logout'"><i class="fa-solid fa-right-from-bracket"></i>Logout</button>
            </div>
        </div>
    </nav>

    <section id="ajax-result"></section>
    
<script>
    $(document).ready(function () {
        let default_page = "InstrumentPanel.php";

        $('#ajax-result').load(default_page + window.location.search);
        $(".ajax-button[data-get='" + default_page + "']").addClass("active");
                $(window).on('userProfileUpdated', function(e) {
            console.log('User profile updated event received:', e.detail);
            
            const profileButton = $('.profile-button');
            
            if (e.detail.userName) {
                profileButton.contents().filter(function() {
                    return this.nodeType === 3;
                }).remove();
                profileButton.append(' ' + e.detail.userName);
            }
            
            if (e.detail.userImage && !e.detail.userImage.startsWith('default_user_')) {
                const timestamp = new Date().getTime();
                const newAvatarUrl = '../../Assets/Image/User/' + e.detail.userImage + '?t=' + timestamp;
                
                const profileAvatar = profileButton.find('#profile-avatar');
                
                if (profileAvatar.length) {
                    profileAvatar.attr('src', newAvatarUrl);
                    
                    profileAvatar.css('display', 'inline-block');
                    profileButton.find('i.fa-solid.fa-circle-user').hide();
                }
            } else {
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