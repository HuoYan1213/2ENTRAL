<?php
session_start();

// Access control: only allow logged-in users with Manager role
if (!isset($_SESSION['user'])) {
    header('Location: ../Public/AccessDenied.php');
    exit();
}

if ($_SESSION['user']['role'] !== "Manager") {
    header('Location: ../Public/AccessDenied.php');
    exit();
}

require_once __DIR__ . "/../../Controller/AuthController.php";
$controller = new AuthController();

$filter_user   = isset($_GET['user'])   ? $_GET['user']   : '';
$filter_action = isset($_GET['filter_action']) ? $_GET['filter_action'] : '';
$filter_sdate  = isset($_GET['start'])  ? $_GET['start']  : '';
$filter_edate  = isset($_GET['end'])    ? $_GET['end']    : '';

$get_user = $controller->getUsers();
$logs = $controller->auditLogs($filter_user, $filter_action, $filter_sdate, $filter_edate);
?>

<title>Audit Logs</title>
<link rel="stylesheet" href="../../Assets/CSS/auditlogs.css">

<div class="audit-logs">
    <div class="section-top">
        <div class="audit-log-section">
            <h2>Audit Logs</h2>
            <span>Review system activities and changes.</span>
        </div>
        <div class="export-wrapper">
            <button class="export-button" id="export-audit-logs"><i class="fa-solid fa-file-export"></i>Export Logs</button>
        </div>
    </div>
    <form method="GET" action="" class="audit-log-filter">
        <div class="filter-item">
            <label for="user-filter">User:</label>
            <select id="user-filter" name="user">
                <option value="">All Users</option>
                <?php foreach ($get_user as $user): ?>
                    <option value="<?php echo $user['UserID'] ?>"
                        <?php if ($filter_user == $user['UserID']) echo "selected"; ?>>
                        <?php echo htmlspecialchars($user['UserName']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-item">
            <label for="action-filter">Action:</label>
            <select id="action-filter" name="filter_action">
                <option value="">All Actions</option>
                <optgroup label="System Authentication">
                    <option value="Login" <?php echo (($filter_action ?? '') == 'Login') ? 'selected' : ''; ?>>Login</option>
                    <option value="Logout" <?php echo (($filter_action ?? '') == 'Logout') ? 'selected' : ''; ?>>Logout</option>
                </optgroup>
                <optgroup label="Data Operations">
                    <option value="Create" <?php echo (($filter_action ?? '') == 'Create') ? 'selected' : ''; ?>>Create</option>
                    <option value="Update" <?php echo (($filter_action ?? '') == 'Update') ? 'selected' : ''; ?>>Update</option>
                    <option value="Delete" <?php echo (($filter_action ?? '') == 'Delete') ? 'selected' : ''; ?>>Delete</option>
                </optgroup>
            </select>
        </div>
        <div class="filter-item">
            <label for="date-from-filter">From:</label>
            <input type="date" id="date-from-filter" name="start" value="<?php echo htmlspecialchars($filter_sdate); ?>">
        </div>
        <div class="filter-item">
            <label for="date-to-filter">To:</label>
            <input type="date" id="date-to-filter" name="end" value="<?php echo htmlspecialchars($filter_edate); ?>">
        </div>
        <div class="filter-item">
            <button type="submit" id="apply-filters">Filter</button>
        </div>
    </form>

    <div class="audit-log-table-wrapper">
        <table class="audit-log-table">
            <thead>
                <tr>
                    <th>TIMESTAMP</th>
                    <th>PERFORMED BY</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($logs)): ?>
                    <?php foreach ($logs as $row): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($row['CreatedAt']); ?>
                            </td>
                            <td>
                                <?php if (!empty($row['UserName'])) {
                                    echo htmlspecialchars($row['UserName']);
                                }
                                else {
                                    echo 'Unknown User';
                                } ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($row['LogsDetails']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td id="no-found" colspan="3">No Logs Found matching your criteria...</td>
                    </tr>
                <?php endif; ?>
            </tbody>    
        </table>
    </div>
</div>

<script>
    $(document).ready(function() {
        const auditLogsPath = '../../View/Auth/AuditLogs.php';
        const container_selector = '#ajax-result';
        
        $('.pagination .page-button').on('click', function(e) {
            if ($(this).hasClass('disabled')) return false;
            e.preventDefault();

            const urlParams = $(this).attr('href');
            $(this).closest('.audit-logs').parent().load(auditLogsPath + urlParams)
        });

        $('.audit-log-filter').on('submit', function(e) {
            e.preventDefault();

            const formData = $(this).serialize();
            const fullUrl = auditLogsPath + '?page=1&' + formData;
            
            $(this).closest('.audit-logs').parent().load(fullUrl);
        });

        $('#export-button').on('click', function(e) {
            e.preventDefault();
            const urlParams = new URLSearchParams(window.location.search);
            
            const formData = $('.audit-log-filter').serialize();
            
            window.location.href = auditLogsPath + '?export=true&' + formData;
        });
    });


    document.getElementById('export-button').addEventListener('click', function(e) {
        e.preventDefault();
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('export', 'true');
        
        const auditLogsPath = '../Auth/AuditLogs.php'; 
        window.location.href = auditLogsPath + '?' + urlParams.toString();
    });
</script>
