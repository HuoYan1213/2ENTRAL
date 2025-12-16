<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== "Manager") {
    header('Location: ../Public/AccessDenied.php');
    exit();
}

require_once __DIR__ . "/../../Controller/RecycleBinController.php";
// Note: Controller is instantiated in the file above, but we need a new instance or use the logic here.
// Since RecycleBinController.php has a routing block, including it executes logic if action is set.
// For the view, we just need the class to fetch data.
// Let's re-instantiate for View data fetching.
require_once __DIR__ . "/../../Model/DB.php";
$viewController = new RecycleBinController($conn);

// Pagination & Filtering
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$filters = [
    'search' => $_GET['search'] ?? '',
    'type' => $_GET['type'] ?? ''
];

$data = $viewController->getItems($page, 8, $filters);
$items = $data['items'];
$total_pages = $data['total_pages'];
$current_page = $data['current_page'];

function buildUrl($newPage, $currentFilters) {
    $params = array_merge($currentFilters, ['page' => $newPage]);
    return '?' . http_build_query(array_filter($params, function($v) { return $v !== ''; })); 
}
?>

<link rel="stylesheet" href="../../Assets/CSS/recyclebin.css">
<!-- Include Phosphor Icons -->
<script src="https://unpkg.com/@phosphor-icons/web"></script>

<div class="recycle-bin-wrapper">

    <!-- Toolbar -->
    <div class="toolbar">
        <div class="search-group">
            <i class="ph ph-magnifying-glass" style="color: #999;"></i>
            <input type="text" class="search-input" id="search-input" placeholder="Search name, ID..." value="<?php echo htmlspecialchars($filters['search']); ?>">
        </div>

        <div class="filter-group">
            <i class="ph ph-funnel" style="color: #666;"></i>
            <select class="filter-select" id="type-filter">
                <option value="">All Types</option>
                <option value="Product" <?php echo $filters['type'] === 'Product' ? 'selected' : ''; ?>>Products</option>
                <option value="Supplier" <?php echo $filters['type'] === 'Supplier' ? 'selected' : ''; ?>>Suppliers</option>
            </select>
        </div>

        <div class="bulk-actions" id="bulkActions">
            <button class="btn-bulk btn-restore-all" onclick="confirmBulkAction('restore')">
                <i class="ph-bold ph-arrow-counter-clockwise"></i> Restore Selected
            </button>
            <button class="btn-bulk btn-delete-all" onclick="confirmBulkAction('delete')">
                <i class="ph-bold ph-trash"></i> Delete Selected
            </button>
        </div>
    </div>

    <!-- Table Card -->
    <div class="table-card">
        <div class="table-scroll">
        <table id="recycle-table">
            <thead>
                <tr>
                    <th style="width: 40px;"><input type="checkbox" class="custom-checkbox" id="selectAll"></th>
                    <th>Item</th>
                    <th>Type</th>
                    <th>Detail</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($items)): ?>
                    <?php foreach ($items as $item): 
                        $typeClass = ($item['Type'] === 'Product') ? 'type-product' : 'type-supplier';
                        $typeIcon = ($item['Type'] === 'Product') ? 'ph-package' : 'ph-truck';
                        $imagePath = ($item['Type'] === 'Product') ? '../../Assets/Image/Product/' : '../../Assets/Image/Supplier/';
                        $defaultImage = ($item['Type'] === 'Product') ? 'default-product.png' : 'default.jpg';
                    ?>
                    <tr data-id="<?php echo $item['ID']; ?>" data-type="<?php echo $item['Type']; ?>">
                        <td>
                            <input type="checkbox" class="custom-checkbox item-checkbox" 
                                value="<?php echo $item['ID']; ?>" 
                                data-type="<?php echo $item['Type']; ?>">
                        </td>
                        <td>
                            <div class="item-info-cell">
                                <div class="item-avatar">
                                    <img src="<?php echo $imagePath . $item['ImagePath']; ?>" 
                                         onerror="this.src='<?php echo $imagePath . $defaultImage; ?>'">
                                </div>
                                <div class="item-detail">
                                    <h4><?php echo htmlspecialchars($item['Name']); ?></h4>
                                    <p>ID: <?php echo htmlspecialchars($item['ID']); ?></p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="type-badge <?php echo $typeClass; ?>">
                                <i class="ph-fill <?php echo $typeIcon; ?>"></i> <?php echo $item['Type']; ?>
                            </span>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($item['Detail']); ?>
                        </td>
                        <td style="text-align: right;">
                            <button class="action-btn btn-restore" title="Restore" onclick="actionSingle('restore', '<?php echo $item['ID']; ?>', '<?php echo $item['Type']; ?>')">
                                <i class="ph-bold ph-arrow-counter-clockwise"></i>
                            </button>
                            <button class="action-btn btn-delete" title="Delete Permanently" onclick="actionSingle('delete', '<?php echo $item['ID']; ?>', '<?php echo $item['Type']; ?>')">
                                <i class="ph-bold ph-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px; color: #999;">
                            <i class="ph ph-trash" style="font-size: 48px; margin-bottom: 10px;"></i>
                            <p>Recycle bin is empty.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination-container">
        <?php if ($current_page > 1): ?>
            <a href="<?php echo buildUrl(1, $filters); ?>" class="page-btn" title="First Page"><i class="ph-bold ph-caret-double-left"></i></a>
            <a href="<?php echo buildUrl($current_page - 1, $filters); ?>" class="page-btn" title="Previous"><i class="ph-bold ph-caret-left"></i></a>
        <?php else: ?>
            <span class="page-btn disabled"><i class="ph-bold ph-caret-double-left"></i></span>
            <span class="page-btn disabled"><i class="ph-bold ph-caret-left"></i></span>
        <?php endif; ?>

        <span class="page-info">
            Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>
        </span>

        <?php if ($current_page < $total_pages): ?>
            <a href="<?php echo buildUrl($current_page + 1, $filters); ?>" class="page-btn" title="Next"><i class="ph-bold ph-caret-right"></i></a>
            <a href="<?php echo buildUrl($total_pages, $filters); ?>" class="page-btn" title="Last Page"><i class="ph-bold ph-caret-double-right"></i></a>
        <?php else: ?>
            <span class="page-btn disabled"><i class="ph-bold ph-caret-right"></i></span>
            <span class="page-btn disabled"><i class="ph-bold ph-caret-double-right"></i></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>

<!-- Confirmation Modal -->
<div id="confirmModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Confirm Action</h3>
        </div>
        <div class="modal-body">
            <p id="modalMessage">Are you sure?</p>
        </div>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeModal()">Cancel</button>
            <button class="btn-confirm" id="modalConfirmBtn">Confirm</button>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    const recycleBinPath = 'RecycleBin.php';
    const controllerPath = '../../Controller/RecycleBinController.php';

    // --- Pagination & Filter Logic ---
    $('.pagination-container .page-btn').on('click', function(e) {
        if ($(this).hasClass('disabled')) return false;
        e.preventDefault();
        $('#ajax-result').load(recycleBinPath + $(this).attr('href'));
    });

    function reloadView() {
        const search = $('#search-input').val();
        const type = $('#type-filter').val();
        const params = new URLSearchParams({ search: search, type: type });
        $('#ajax-result').load(recycleBinPath + '?' + params.toString());
    }

    let searchTimeout;
    $('#search-input').on('input', function() { clearTimeout(searchTimeout); searchTimeout = setTimeout(reloadView, 500); });
    $('#type-filter').on('change', reloadView);

    // --- Selection Logic ---
    $('#selectAll').on('change', function() {
        $('.item-checkbox').prop('checked', $(this).is(':checked'));
        updateBulkActions();
    });

    $(document).on('change', '.item-checkbox', function() {
        const allChecked = $('.item-checkbox').length === $('.item-checkbox:checked').length;
        $('#selectAll').prop('checked', allChecked);
        updateBulkActions();
    });

    function updateBulkActions() {
        const count = $('.item-checkbox:checked').length;
        if (count > 0) {
            $('#bulkActions').addClass('active');
        } else {
            $('#bulkActions').removeClass('active');
        }
    }

    // --- Action Logic ---
    let pendingAction = null;

    window.actionSingle = function(action, id, type) {
        const title = action === 'restore' ? 'Restore Item' : 'Delete Permanently';
        const msg = action === 'restore' 
            ? 'Are you sure you want to restore this item? It will become active again.' 
            : 'Are you sure you want to delete this item permanently? <b>This cannot be undone.</b>';
        
        showModal(title, msg, action === 'delete', function() {
            performAjax(action, { id: id, type: type });
        });
    };

    window.confirmBulkAction = function(action) {
        const items = [];
        $('.item-checkbox:checked').each(function() {
            items.push({
                id: $(this).val(),
                type: $(this).data('type')
            });
        });

        const title = action === 'restore' ? 'Bulk Restore' : 'Bulk Delete';
        const msg = `Are you sure you want to  <b>${items.length}</b> selected items?`;

        showModal(title, msg, action === 'delete', function() {
            $.ajax({
                url: controllerPath + '?action=bulkAction',
                type: 'POST',
                data: { action_type: action, items: JSON.stringify(items) },
                dataType: 'json',
                success: function(res) {
                    alert(res.message);
                    reloadView();
                },
                error: function() { alert('Server error occurred.'); }
            });
        });
    };

    function performAjax(action, data) {
        $.ajax({
            url: controllerPath + '?action=' + action,
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    reloadView();
                } else {
                    alert('Error: ' + res.message);
                }
            },
            error: function() { alert('Server error occurred.'); }
        });
    }

    // --- Modal Helpers ---
    function showModal(title, message, isDanger, callback) {
        $('#modalTitle').text(title);
        $('#modalMessage').html(message);
        
        const btn = $('#modalConfirmBtn');
        btn.removeClass('danger success').addClass(isDanger ? 'danger' : 'success');
        btn.text(isDanger ? 'Delete' : 'Confirm');
        
        // Unbind previous click events to prevent stacking
        btn.off('click').on('click', function() {
            closeModal();
            if (callback) callback();
        });

        $('#confirmModal').css('display', 'flex');
    }

    window.closeModal = function() {
        $('#confirmModal').hide();
    };
});
</script>
