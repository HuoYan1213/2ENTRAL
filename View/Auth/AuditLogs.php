<?php
    ob_start();
    session_start();

    require_once __DIR__ . "/../../Model/DB.php";
    require_once __DIR__ . "/../../Controller/LogsController.php";

    $controller = new LogsController($conn);

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;

    $filters = [
        'user' => $user = $_GET['user'] ?? '',
        'action' => $action = $_GET['action'] ?? '',
        'start' => $start_date = $_GET['start'] ?? '',
        'end' => $end_date  = $_GET['end'] ?? ''
    ];

    if (isset($_GET['export']) && $_GET['export'] == 'true') {
        $export_data = $controller->exportLogs($filters);
        ob_end_clean();

        if (ob_get_length()) ob_end_clean();

        $file_name = "System_Logs_" . date('Y-m-d_H-i-s') . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $file_name . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Timestamp', 'Performed By', 'Action'], ",", "\"", "\\");

        foreach ($export_data as $data) {
            fputcsv($output, [
                $data['CreatedAt'],
                $data['UserName'] ?? 'Unknown User',
                $data['LogsDetails']
            ], ",", "\"", "\\");
        }

        fclose($output);
        exit();
    }

    $result_data = $controller->auditLogs($page, $filters);
    $get_user = $controller->getUsers();


    $logs = $result_data['LOGS'];
    $total_pages = $result_data['TOTAL_PAGES'];
    $current_page = $result_data['CURRENT_PAGE'];
    
    // Filter Data Keeper
    function buildUrl($newPage, $currentFilters) {
        $params = array_merge($currentFilters, ['page' => $newPage]);
        return '?' . http_build_query(array_filter($params)); 
    }
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
            <button class="export-button" id="export-button"><i class="fa-solid fa-file-export"></i>Export Logs</button>
        </div>
    </div>
    <form method="GET" action="" class="audit-log-filter">
        <div class="filter-item">
            <label for="user-filter">User:</label>
            <select id="user-filter" name="user">
                <option value="">All Users</option>
                <?php foreach ($get_user as $u): ?>
                    <option value="<?php echo $u['UserID'] ?>"
                        <?php if ($user == $u['UserID']) echo "selected"; ?>>
                        <?php echo htmlspecialchars($u['UserName']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-item">
            <label for="action-filter">Action:</label>
            <select id="action-filter" name="action">
                <option value="">All Actions</option>
                <optgroup label="System Authentication">
                    <option value="Login" <?php echo (($action ?? '') == 'Login') ? 'selected' : ''; ?>>Login</option>
                    <option value="Logout" <?php echo (($action ?? '') == 'Logout') ? 'selected' : ''; ?>>Logout</option>
                </optgroup>
                <optgroup label="Data Operations">
                    <option value="Create" <?php echo (($action ?? '') == 'Create') ? 'selected' : ''; ?>>Create</option>
                    <option value="Update" <?php echo (($action ?? '') == 'Update') ? 'selected' : ''; ?>>Update</option>
                    <option value="Delete" <?php echo (($action ?? '') == 'Delete') ? 'selected' : ''; ?>>Delete</option>
                </optgroup>
            </select>
        </div>
        <div class="filter-item">
            <label for="start-date-filter">From:</label>
            <input type="date" id="start-date-filter" name="start" value="<?php echo htmlspecialchars($start_date); ?>">
        </div>
        <div class="filter-item">
            <label for="end-date-filter">To:</label>
            <input type="date" id="end-date-filter" name="end" value="<?php echo htmlspecialchars($end_date); ?>">
        </div>
        <div class="filter-item">
            <button type="submit" id="apply-filters">Filter</button>
        </div>
    </form>

    <div class="audit-log-table-wrapper">
        <table class="audit-log-table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Performed By</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($logs)): ?>
                    <?php foreach ($logs as $l): ?>
                        <tr>
                            <td>
                                <?php echo date('d/m/Y H:i:s', strtotime($l['CreatedAt'])); ?>
                            </td>
                            <td>
                                <?php if (!empty($l['UserName'])) {
                                    echo htmlspecialchars($l['UserName']);
                                }
                                else {
                                    echo 'Unknown User';
                                } ?>
                            </td>
                            <td id="action-data">
                                <?php          
                                    $badgeClass = $controller->getAction($l['LogsDetails']);                           
                                    $actionLabel = 'Info';
                                    if (stripos($l['LogsDetails'], 'Login') !== false) $actionLabel = 'Login';
                                    elseif (stripos($l['LogsDetails'], 'Logout') !== false) $actionLabel = 'Logout';
                                    elseif (stripos($l['LogsDetails'], 'Create') !== false) $actionLabel = 'Create';
                                    elseif (stripos($l['LogsDetails'], 'Update') !== false) $actionLabel = 'Update';
                                    elseif (stripos($l['LogsDetails'], 'Delete') !== false) $actionLabel = 'Delete';
                                ?>
                                
                                <span class="badge <?php echo $badgeClass; ?>">
                                    <?php echo $actionLabel; ?>
                                </span>

                                <span id="logs-details">
                                    <?php echo htmlspecialchars($l['LogsDetails']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td id="no-found" colspan="3">No Logs Found Matching Your Criteria...</td>
                    </tr>
                <?php endif; ?>
            </tbody>    
        </table>
    </div>
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php if ($current_page > 1): ?>
            <a href="<?php echo buildUrl(1, $filters); ?>" class="page-button" title="First Page">
                <i class="fa-solid fa-angles-left"></i>
            </a>
        <?php else: ?>
            <span class="page-button disabled"><i class="fa-solid fa-angles-left"></i></span>
        <?php endif; ?>

        <?php if ($current_page > 1): ?>
            <a href="<?php echo buildUrl($current_page - 1, $filters); ?>" class="page-button">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
        <?php else: ?>
            <span class="page-button disabled"><i class="fa-solid fa-chevron-left"></i></span>
        <?php endif; ?>

        <span id="page-number">
            Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>
        </span>

        <?php if ($current_page < $total_pages): ?>
            <a href="<?php echo buildUrl($current_page + 1, $filters); ?>" class="page-button">
                <i class="fa-solid fa-chevron-right"></i>
            </a>
        <?php else: ?>
            <span class="page-button disabled"><i class="fa-solid fa-chevron-right"></i></span>
        <?php endif; ?>

        <?php if ($current_page < $total_pages): ?>
            <a href="<?php echo buildUrl($total_pages, $filters); ?>" class="page-button" title="Last Page">
                <i class="fa-solid fa-angles-right"></i>
            </a>
        <?php else: ?>
            <span class="page-button disabled"><i class="fa-solid fa-angles-right"></i></span>
        <?php endif; ?>

    </div>
    <?php endif; ?>
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