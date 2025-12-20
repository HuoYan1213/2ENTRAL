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

    if (isset($_GET['export_pdf']) && $_GET['export_pdf'] == 'true') {
        header('Content-Type: application/json');
        $export_data = $controller->exportLogs($filters);
        echo json_encode($export_data);
        exit();
    }

    $result_data = $controller->auditLogs($page, $filters);
    $get_user = $controller->getUsers();

    $logs = $result_data['LOGS'];
    $total_pages = $result_data['TOTAL_PAGES'];
    $current_page = $result_data['CURRENT_PAGE'];
    
    function buildUrl($newPage, $currentFilters) {
        $params = array_merge($currentFilters, ['page' => $newPage]);
        return '?' . http_build_query(array_filter($params)); 
    }

    // Helper to generate initials and color
    function getUserVisuals($name) {
        $initials = strtoupper(substr($name, 0, 1));
        if (strpos($name, ' ') !== false) {
            $parts = explode(' ', $name);
            if (count($parts) > 1) {
                $initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
            }
        }
        
        // Simple hash for color
        $colors = [
            ['bg' => '#E3F2FD', 'text' => '#1565C0'], // Blue
            ['bg' => '#FFEBEE', 'text' => '#C62828'], // Red
            ['bg' => '#E8F5E9', 'text' => '#2E7D32'], // Green
            ['bg' => '#F3E5F5', 'text' => '#7B1FA2'], // Purple
            ['bg' => '#FFF3E0', 'text' => '#EF6C00'], // Orange
            ['bg' => '#E0F2F1', 'text' => '#00695C'], // Teal
        ];
        $index = crc32($name) % count($colors);
        return ['initials' => $initials, 'style' => $colors[$index]];
    }

    // Helper to parse action type
    function getActionType($details) {
        // Add Security Alert type (Red color using act-delete style)
        if (stripos($details, 'Unauthorized') !== false) return ['type' => 'Security', 'class' => 'act-delete', 'icon' => 'ph-warning-circle'];
        if (stripos($details, 'Login') !== false) return ['type' => 'Login', 'class' => 'act-login', 'icon' => 'ph-sign-in'];
        if (stripos($details, 'Logout') !== false) return ['type' => 'Logout', 'class' => 'act-logout', 'icon' => 'ph-sign-out'];
        if (stripos($details, 'Create') !== false || stripos($details, 'Add') !== false) return ['type' => 'Created', 'class' => 'act-create', 'icon' => 'ph-plus-circle'];
        if (stripos($details, 'Update') !== false || stripos($details, 'Edit') !== false) return ['type' => 'Updated', 'class' => 'act-update', 'icon' => 'ph-pencil-simple'];
        if (stripos($details, 'Delete') !== false || stripos($details, 'Remove') !== false) return ['type' => 'Deleted', 'class' => 'act-delete', 'icon' => 'ph-trash'];
        return ['type' => 'Info', 'class' => 'act-info', 'icon' => 'ph-info'];
    }
?>

<title>Audit Logs</title>
<!-- Include Phosphor Icons for the new design -->
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<link rel="stylesheet" href="../../Assets/CSS/auditlogs.css">

<div class="audit-logs-wrapper">
    
    <div class="toast-container" id="toastContainer"></div>
    
    <!-- Header Section -->
    <div class="header-section">
        <button class="btn-export" onclick="generateAuditPDF()">
            <i class="ph-bold ph-file-pdf"></i> Export PDF
        </button>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="" class="filter-form">
        <div class="filter-bar">
            <div class="filter-group">
                <i class="ph ph-user"></i>
                <span class="filter-label">User:</span>
                <select name="user" class="filter-input">
                    <option value="">All Users</option>
                    <?php foreach ($get_user as $u): ?>
                        <option value="<?php echo $u['UserID'] ?>" <?php if ($user == $u['UserID']) echo "selected"; ?>>
                            <?php echo htmlspecialchars($u['UserName']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <i class="ph ph-faders"></i>
                <span class="filter-label">Action:</span>
                <select name="action" class="filter-input">
                    <option value="">All Actions</option>
                    <option value="Login" <?php echo ($action == 'Login') ? 'selected' : ''; ?>>Login</option>
                    <option value="Logout" <?php echo ($action == 'Logout') ? 'selected' : ''; ?>>Logout</option>
                    <option value="Create" <?php echo ($action == 'Create') ? 'selected' : ''; ?>>Create</option>
                    <option value="Update" <?php echo ($action == 'Update') ? 'selected' : ''; ?>>Update</option>
                    <option value="Delete" <?php echo ($action == 'Delete') ? 'selected' : ''; ?>>Delete</option>
                    <option value="Unauthorized" <?php echo ($action == 'Unauthorized') ? 'selected' : ''; ?>>Security Alert</option>
                </select>
            </div>

            <div class="filter-group">
                <i class="ph ph-calendar-blank"></i>
                <span class="filter-label">From:</span>
                <input type="date" name="start" class="filter-input" value="<?php echo htmlspecialchars($start_date); ?>">
            </div>

            <div class="filter-group">
                <i class="ph ph-calendar-blank"></i>
                <span class="filter-label">To:</span>
                <input type="date" name="end" class="filter-input" value="<?php echo htmlspecialchars($end_date); ?>">
            </div>

            <button type="submit" class="btn-filter">Apply Filter</button>
        </div>
    </form>

    <!-- Logs Table Card -->
    <div class="logs-card">
        <table class="audit-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Action</th>
                    <th>Activity Details</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($logs)): ?>
                    <?php foreach ($logs as $l): 
                        // Handle UserID 0 (Unregistered/System)
                        if ($l['UserID'] == 0) {
                            $userName = 'Unregistered';
                        } else {
                            $userName = !empty($l['UserName']) ? $l['UserName'] : 'Unknown User';
                        }
                        
                        $visuals = getUserVisuals($userName);
                        $actionInfo = getActionType($l['LogsDetails']);
                    ?>
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar" style="background:<?php echo $visuals['style']['bg']; ?>; color:<?php echo $visuals['style']['text']; ?>;">
                                        <?php echo $visuals['initials']; ?>
                                    </div>
                                    <div class="user-info">
                                        <div class="user-name"><?php echo htmlspecialchars($userName); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="action-badge <?php echo $actionInfo['class']; ?>">
                                    <i class="ph-bold <?php echo $actionInfo['icon']; ?>"></i> <?php echo $actionInfo['type']; ?>
                                </span>
                            </td>
                            <td>
                                <div class="details-cell">
                                    <?php echo htmlspecialchars($l['LogsDetails']); ?>
                                </div>
                            </td>
                            <td class="time-cell">
                                <?php echo date('M j, Y g:i A', strtotime($l['CreatedAt'])); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="empty-state">
                            <i class="ph ph-magnifying-glass" style="font-size: 32px; margin-bottom: 10px;"></i>
                            <p>No logs found matching your criteria.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination-container">
        <?php if ($current_page > 1): ?>
            <a href="<?php echo buildUrl(1, $filters); ?>" class="page-btn" title="First Page">
                <i class="ph-bold ph-caret-double-left"></i>
            </a>
            <a href="<?php echo buildUrl($current_page - 1, $filters); ?>" class="page-btn" title="Previous">
                <i class="ph-bold ph-caret-left"></i>
            </a>
        <?php else: ?>
            <span class="page-btn disabled"><i class="ph-bold ph-caret-double-left"></i></span>
            <span class="page-btn disabled"><i class="ph-bold ph-caret-left"></i></span>
        <?php endif; ?>

        <span class="page-info">
            Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>
        </span>

        <?php if ($current_page < $total_pages): ?>
            <a href="<?php echo buildUrl($current_page + 1, $filters); ?>" class="page-btn" title="Next">
                <i class="ph-bold ph-caret-right"></i>
            </a>
            <a href="<?php echo buildUrl($total_pages, $filters); ?>" class="page-btn" title="Last Page">
                <i class="ph-bold ph-caret-double-right"></i>
            </a>
        <?php else: ?>
            <span class="page-btn disabled"><i class="ph-bold ph-caret-right"></i></span>
            <span class="page-btn disabled"><i class="ph-bold ph-caret-double-right"></i></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>

<script>
    function showToast(type, message) {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        let iconClass = type === 'success' ? 'ph-check-circle' : 'ph-warning-circle';
        let title = type === 'success' ? 'Success' : 'Error';

        toast.innerHTML = `
            <div class="toast-icon"><i class="ph-fill ${iconClass}"></i></div>
            <div class="toast-content">
                <h4>${title}</h4>
                <p>${message}</p>
            </div>
            <div class="toast-close" onclick="this.parentElement.remove()"><i class="ph-bold ph-x"></i></div>
        `;

        container.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('hiding');
            toast.addEventListener('animationend', () => {
                if (toast.parentElement) {
                    toast.remove();
                }
            });
        }, 3000);
    }

    $(document).ready(function() {
        const auditLogsPath = '../../View/Auth/AuditLogs.php';
        
        // Handle Pagination Clicks via AJAX
        $(document).off('click', '.audit-logs-wrapper .pagination-container .page-btn').on('click', '.audit-logs-wrapper .pagination-container .page-btn', function(e) {
            if ($(this).hasClass('disabled')) return false;
            e.preventDefault();

            const urlParams = $(this).attr('href');
            $('#ajax-result').load(auditLogsPath + urlParams);
        });

        // Initialize Choices.js for Select Dropdowns
        $('select.filter-input').each(function() {
            new Choices(this, {
                searchEnabled: $(this).attr('name') === 'user', // Only enable search for User dropdown
                itemSelectText: '',
                shouldSort: false,
                position: 'bottom'
            });
        });

        // Handle Filter Form Submit via AJAX
        $('.filter-form').on('submit', function(e) {
            e.preventDefault();
            
            const startDate = $('input[name="start"]').val();
            const endDate = $('input[name="end"]').val();

            if (startDate && endDate && startDate > endDate) {
                showToast('error', "Invalid Date Range: 'From' date cannot be later than 'To' date.");
                return;
            }
            
            const formData = $(this).serialize();
            const fullUrl = auditLogsPath + '?page=1&' + formData;
            $('#ajax-result').load(fullUrl);
        });

    });

    // --- PDF Generation Logic ---

    function loadScript(url) {
        return new Promise((resolve, reject) => {
            if (document.querySelector(`script[src="${url}"]`)) {
                resolve();
                return;
            }
            const script = document.createElement('script');
            script.src = url;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    async function ensurePDFLibraries() {
        if (!window.jspdf) {
            await loadScript("https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js");
        }
        if (!window.jsPDF && window.jspdf) {
            window.jsPDF = window.jspdf.jsPDF;
        }
        try {
            const tempDoc = new window.jsPDF();
            if (typeof tempDoc.autoTable !== 'function') {
                await loadScript("https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js");
            }
        } catch (e) {
            await loadScript("https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js");
        }
    }

    function loadImage(url) {
        return new Promise((resolve) => {
            const img = new Image();
            img.crossOrigin = "Anonymous"; 
            img.src = url;
            img.onload = () => resolve(img);
            img.onerror = () => resolve(null);
        });
    }

    async function generateAuditPDF() {
        const btn = document.querySelector('.btn-export');
        const originalContent = btn.innerHTML;
        btn.innerHTML = '<i class="ph-bold ph-spinner ph-spin"></i> Generating...';
        btn.disabled = true;

        try {
            await ensurePDFLibraries();

            // Fetch data
            const formData = $('.filter-form').serialize();
            const response = await fetch('../../View/Auth/AuditLogs.php?export_pdf=true&' + formData);
            const logs = await response.json();

            const doc = new window.jsPDF();
            const logoUrl = '/Assets/Icon/2ENTRAL-1.png'; 
            const logoImg = await loadImage(logoUrl);
            const date = new Date().toLocaleString();
            const primaryColor = [26, 37, 48]; // Brand Dark

            // Header
            if (logoImg) {
                doc.addImage(logoImg, 'PNG', 14, 10, 25, 25);
                doc.setFontSize(20);
                doc.setTextColor(...primaryColor);
                doc.text("Audit Log Report", 45, 22);
                doc.setFontSize(10);
                doc.setTextColor(100);
                doc.text("System Activity & Security Records", 45, 28);
            } else {
                doc.setFontSize(20);
                doc.setTextColor(...primaryColor);
                doc.text("Audit Log Report", 14, 22);
            }

            doc.setFontSize(10);
            doc.setTextColor(100);
            doc.text(`Generated on: ${date}`, 14, 45);
            
            // Table Data
            const tableBody = logs.map(log => [
                log.CreatedAt,
                log.UserName || 'Unknown',
                log.LogsDetails
            ]);

            doc.autoTable({
                startY: 50,
                head: [['Timestamp', 'User', 'Activity Details']],
                body: tableBody,
                theme: 'grid',
                headStyles: { fillColor: primaryColor, textColor: 255, fontStyle: 'bold' },
                styles: { fontSize: 9, cellPadding: 3 },
                columnStyles: {
                    0: { cellWidth: 45 },
                    1: { cellWidth: 40 },
                    2: { cellWidth: 'auto' }
                },
                didDrawPage: function (data) {
                    // Footer
                    doc.setFontSize(8);
                    doc.setTextColor(150);
                    doc.text('Confidential - Internal Use Only', 14, doc.internal.pageSize.height - 10);
                    doc.text('Page ' + doc.internal.getNumberOfPages(), doc.internal.pageSize.width - 25, doc.internal.pageSize.height - 10);
                }
            });

            doc.save(`Audit_Logs_${new Date().toISOString().slice(0,10)}.pdf`);

        } catch (err) {
            console.error(err);
            alert("Failed to generate PDF.");
        } finally {
            btn.innerHTML = originalContent;
            btn.disabled = false;
        }
    }
</script>