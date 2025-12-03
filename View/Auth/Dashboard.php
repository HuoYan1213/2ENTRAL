<?php
session_start();

// Access control: only allow logged-in users
if (!isset($_SESSION['user'])) {
    header('Location: /View/Public/AccessDenied.php');
    exit();
}
else {
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
                    <?php if ($AVATAR_PATH) { ?> 
                        <img src="../../Assets/Image/User/<?php echo $AVATAR_PATH ?>" id="profile-avatar">
                    <?php } else { ?>
                        <i class="fa-solid fa-circle-user"></i>
                    <?php } ?>
                    <?php echo $CURRENT_NAME ?>
                </button>
                <button id="logout-button" onclick="window.location.href='/Controller/AuthController.php?action=logout'"><i class="fa-solid fa-right-from-bracket"></i>Logout</button>
            </div>
        </div>
    </nav>

    <section id="ajax-result"></section>
    
<script>
    $(document).ready(function () {
        let default_page = "InstrumentPanel.php";

        $('#ajax-result').load(default_page + window.location.search);
        $(".ajax-button[data-get='" + default_page + "']").addClass("active");
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