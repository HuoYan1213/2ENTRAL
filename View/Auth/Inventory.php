<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: /View/Public/AccessDenied.php');
    exit();
}


require_once __DIR__ . "/../../Model/InventoryModel.php";
$model = new InventoryModel();


$stats = $model->getInventoryStats();
$categoryStats = $model->getCategoryStats();
$products = $model->getAllProducts() ?? [];
$logs = $model->getInventoryLogs() ?? [];

$totalProducts = $stats['total_products'] ?? 0;
$lowStockCount = $stats['low_stock_count'] ?? 0;
$outOfStockCount = $stats['out_of_stock_count'] ?? 0;
$totalStockValue = $stats['total_inventory_value'] ?? 0;
$currentUser = $_SESSION['user']['name'] ?? 'User';

$message = [];
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/Assets/CSS/inventory.css">

<body>

<div class="inventory-container">
    <div class="header">
    <div class="title-section">
        <h1>Inventory Management</h1>
        <p>Manage your inventory items and track stock movements</p>
    </div>
    
    <div style="display: flex; gap: 15px; align-items: center;">
        <button class="btn btn-primary" onclick="generatePDF()">
            <i class="fas fa-file-pdf"></i> Export Report
        </button>
        
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <span><?= htmlspecialchars($currentUser ?? ($_SESSION['user']['name'] ?? 'User')) ?></span>
        </div>
    </div>
</div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $message['type'] === 'success' ? 'success' : 'error' ?>">
            <i class="fas fa-<?= $message['type'] === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
            <?= htmlspecialchars($message['text']) ?>
        </div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card" data-filter="all" onclick="applyFilter('all')">
            <div class="stat-icon" style="color: var(--primary);">
                <i class="fas fa-boxes"></i>
            </div>
            <div class="stat-value"><?= $totalProducts ?></div>
            <div class="stat-label">Total Products</div>
        </div>
        
        <div class="stat-card danger" data-filter="low-stock" onclick="applyFilter('low-stock')">
            <div class="stat-icon" style="color: var(--danger);">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-value"><?= $lowStockCount ?? 0 ?></div>
            <div class="stat-label">Low Stock</div>
        </div>
        
        <div class="stat-card warning" data-filter="out-of-stock" onclick="applyFilter('out-of-stock')">
            <div class="stat-icon" style="color: var(--warning);">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-value"><?= $outOfStockCount ?></div>
            <div class="stat-label">Out of Stock</div>
        </div>
    </div>

    <div class="secondary-row">
        <div class="stat-card info value-card">
            <div class="card-content">
                <div class="stat-icon" style="color: var(--info);">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div>
                    <div class="stat-value" id="totalStockValueDisplay">RM <?= number_format($totalStockValue, 2) ?></div>
                    <div class="stat-label">Total Inventory Value</div>
                </div>
            </div>
        </div>

        <div class="category-module">
            <div class="category-trigger" onclick="toggleCategoryDropdown()">
                <div class="trigger-info">
                    <span class="trigger-label">Current Category</span>
                    <span class="trigger-value" id="selectedCategoryName">All Categories</span>
                </div>
                
                <div class="trigger-icons">
                    <i class="fas fa-times clear-cat-icon" 
                       id="clearCategoryBtn" 
                       style="display: none;" 
                       onclick="event.stopPropagation(); selectCategory('all', 'All Categories')"></i>
                       
                    <i class="fas fa-chevron-down dropdown-arrow" id="categoryDropdownArrow"></i>
                </div>
            </div>

            <div class="category-dropdown" id="categoryDropdown">
                <div class="cat-option active" onclick="selectCategory('all', 'All Categories')">
                    <span class="cat-name">All Categories</span>
                    <span class="badge badge-info"><?= $totalProducts ?></span>
                </div>
                
                <?php foreach($categoryStats as $category): ?>
                <div class="cat-option" onclick="selectCategory('<?= htmlspecialchars($category['Category']) ?>', '<?= htmlspecialchars($category['Category']) ?>')">
                    <span class="cat-name"><?= htmlspecialchars($category['Category']) ?></span>
                    <span class="badge badge-primary"><?= $category['product_count'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div id="filter-indicator" class="filter-indicator" style="display: none;">
        <div class="filter-active">
            <span id="filter-text"></span>
            <button id="clear-filter" class="btn btn-outline">
                <i class="fas fa-times"></i> Clear Filter
            </button>
        </div>
    </div>

    <div class="tabs-container">
        <div class="tabs-header">
            <div class="tabs-nav">
                <div class="tab active" onclick="showTab('stock')">
                    <i class="fas fa-boxes"></i> Current Stock
                </div>
                <div class="tab" onclick="showTab('logs')">
                    <i class="fas fa-history"></i> Movement Logs
                </div>
            </div>

            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" 
                       id="productSearchInput" 
                       class="search-input" 
                       placeholder="Search name or ID..." 
                       onkeyup="handleSearch(this.value)">
            </div>
        </div>

        <div id="stock" class="tab-content active">
            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h3>No Products Found</h3>
                    <p>Add some products to get started with inventory management.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th class="th-image">Image</th> 
                                <th>Product ID</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Supplier</th>
                                <th>Current Stock</th>
                                <th>Low Stock Alert</th>
                                <th>Price (RM)</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($products as $product): ?>
                            <?php 
                                $stockStatus = '';
                                if ($product['Stock'] == 0) {
                                    $stockStatus = 'out-of-stock';
                                } elseif ($product['Stock'] <= $product['LowStockAlert']) {
                                    $stockStatus = 'low-stock';
                                } elseif ($product['Stock'] <= ($product['LowStockAlert'] * 2)) {
                                    $stockStatus = 'warning';
                                } else {
                                    $stockStatus = 'good';
                                }

                            
                                $hasImage = !empty($product['ImagePath']);
                                $filename = $hasImage ? rawurlencode($product['ImagePath']) : '';
                                $imagePath = $hasImage ? "/Assets/Image/Product/" . $filename : "";
                            ?>
                            <tr>
                                <td>
                                    <?php if($hasImage): ?>
                                        <img src="<?= $imagePath ?>" 
                                            class="product-thumb" 
                                            onclick="openImageModal(this.src)" 
                                            alt="Img"
                                            onerror="this.onerror=null; this.src='../../Assets/Image/Product/default-product.png';">
                                    <?php else: ?>
                                        <div class="no-image-placeholder">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td><code><?= htmlspecialchars($product['ProductID']) ?></code></td>
                                <td>
                                    <strong><?= htmlspecialchars($product['ProductName']) ?></strong>
                                    <?php if (!empty($product['Description'])): ?>
                                        <br><small style="color: var(--text-light);"><?= htmlspecialchars($product['Description']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($product['Category']) ?></td>
                                <td>
                                    <?= htmlspecialchars($product['SupplierName'] ?? 'N/A') ?>
                                    <?php if (!empty($product['SupplierEmail'])): ?>
                                        <br><small style="color: var(--text-light);"><?= htmlspecialchars($product['SupplierEmail']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><strong style="font-size: 16px;"><?= $product['Stock'] ?></strong></td>
                                <td><?= $product['LowStockAlert'] ?></td>
                                <td>RM <?= number_format($product['Price'], 2) ?></td>
                                <td>
                                    <?php if($stockStatus === 'out-of-stock'): ?>
                                        <span class="badge badge-danger">Out of Stock</span>
                                    <?php elseif($stockStatus === 'low-stock'): ?>
                                        <span class="badge badge-danger">Low Stock</span>
                                    <?php elseif($stockStatus === 'warning'): ?>
                                        <span class="badge badge-warning">Watch</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Good</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-primary" 
                                            onclick="openAdjustModal('<?= $product['ProductID'] ?>', '<?= htmlspecialchars(addslashes($product['ProductName'])) ?>')">
                                        <i class="fas fa-edit"></i> Adjust
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div id="logs" class="tab-content">
            <?php if (empty($logs)): ?>
                <div class="empty-state">
                    <i class="fas fa-history"></i>
                    <h3>No Movement Logs</h3>
                    <p>Stock adjustments will appear here once made.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Product</th>
                                <th>Details</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($logs as $log): ?>
                            <tr>
                                <td><?= date('M j, Y g:i A', strtotime($log['CreatedAt'])) ?></td>
                                <td><strong><?= htmlspecialchars($log['ProductName']) ?></strong></td>
                                <td><?= htmlspecialchars($log['LogsDetails']) ?></td>
                                <td><?= htmlspecialchars($log['UserName']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="adjustModal" class="modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal()">&times;</button>
        <h2 class="modal-title">Adjust Stock</h2>
        <p class="modal-subtitle" id="modalProductName"></p>
        
        <form id="adjustStockForm" method="POST">
            <input type="hidden" name="product_id" id="modalProductId">
            <input type="hidden" id="adjustmentType" value="add">
            
            <div class="form-group">
                <label class="form-label">Action Type</label>
                <div class="adjustment-type-selector">
                    <button type="button" class="type-btn active" data-type="add" onclick="setAdjustmentType('add')">
                        <i class="fas fa-plus-circle"></i> Add Stock
                    </button>
                    <button type="button" class="type-btn" data-type="remove" onclick="setAdjustmentType('remove')">
                        <i class="fas fa-minus-circle"></i> Remove Stock
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Quantity</label>
                <input type="number" id="inputQuantity" name="display_quantity" class="form-control" 
                       placeholder="0" min="1" required>
                <small id="quantityHelper" class="helper-text" style="color: var(--success);">
                    Adding stock to inventory
                </small>
            </div>
            
            <div class="form-group">
                <label class="form-label">Reason</label>
                <select name="reason" id="reasonSelect" class="form-control" required>
                    </select>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                <button type="submit" id="submitBtn" class="btn btn-primary btn-submit-add">
                    Confirm Adjustment
                </button>
            </div>
        </form>
    </div>
</div>

<div id="imageModal" class="image-modal" onclick="closeImageModal()">
    <span class="image-modal-close">&times;</span>
    <img class="image-modal-content" id="fullSizeImage" onerror="this.onerror=null; this.src='../../Assets/Image/Product/default-product.png';">
</div>

<script>
let currentFilter = 'all';
let activeCategory = 'all';
let currentSearchTerm = '';
let allProducts = <?= json_encode($products ?? []) ?>; 

const REASONS = {
    add: ["New Shipment", "Returned Items", "Stock Count Correction", "Manual Adjustment"],
    remove: ["Sold (Offline/Manual)", "Damaged Goods", "Theft/Loss", "Expired", "Quality Control Fail", "Stock Count Correction", "Manual Adjustment"]
};

document.addEventListener('DOMContentLoaded', function() {
    
});


function showTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
    document.getElementById(tabName).classList.add('active');
    event.target.classList.add('active');
}


const modal = document.getElementById('adjustModal');

function openAdjustModal(productId, productName) {
    document.getElementById('modalProductId').value = productId;
    document.getElementById('modalProductName').textContent = productName;
    setAdjustmentType('add');
    document.getElementById('inputQuantity').value = '';
    modal.style.display = 'flex';
}

function closeModal() {
    modal.style.display = 'none';
    document.getElementById('adjustStockForm').reset();
}

window.onclick = e => { if (e.target === modal) closeModal(); };

function setAdjustmentType(type) {
    document.getElementById('adjustmentType').value = type;
    document.querySelectorAll('.type-btn').forEach(btn => {
        btn.classList.remove('active');
        if(btn.dataset.type === type) btn.classList.add('active');
    });

    const helper = document.getElementById('quantityHelper');
    const submitBtn = document.getElementById('submitBtn');
    
    if (type === 'add') {
        helper.textContent = "Valid stock will be added to the current count.";
        helper.style.color = "var(--success)";
        submitBtn.textContent = "Add to Inventory";
        submitBtn.className = "btn btn-primary btn-submit-add";
    } else {
        helper.textContent = "Stock will be deducted from the current count.";
        helper.style.color = "var(--danger)";
        submitBtn.textContent = "Remove from Inventory";
        submitBtn.className = "btn btn-primary btn-submit-remove";
    }
    updateReasonDropdown(type);
}

function updateReasonDropdown(type) {
    const select = document.getElementById('reasonSelect');
    select.innerHTML = '<option value="">Select a reason</option>';
    REASONS[type].forEach(reason => {
        const option = document.createElement('option');
        option.value = reason;
        option.textContent = reason;
        select.appendChild(option);
    });
}


document.getElementById('adjustStockForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const type = document.getElementById('adjustmentType').value;
    const inputQty = parseFloat(document.getElementById('inputQuantity').value);
    const reason = document.getElementById('reasonSelect').value;
    
    if (!inputQty || inputQty <= 0) { alert("Please enter a valid positive quantity."); return; }
    if (!reason) { alert("Please select a reason."); return; }

    let finalQuantity = (type === 'remove') ? -Math.abs(inputQty) : inputQty;

    const formData = new FormData();
    formData.append('product_id', document.getElementById('modalProductId').value);
    formData.append('quantity', finalQuantity);
    formData.append('reason', reason);

    const submitButton = document.getElementById('submitBtn');
    const originalText = submitButton.innerHTML;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    submitButton.disabled = true;

    fetch('/Controller/InventoryController.php?action=adjustStock', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            allProducts = data.products;
            updateStatsCards(data.stats);
            if (data.logs) updateLogsTable(data.logs);
            refreshTableData();
            
            showAlert(data.message, 'success');
            closeModal();
        } else {
            showAlert(data.message || 'Operation failed', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred.', 'error');
    })
    .finally(() => {
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    });
});

function updateAllData() {
    fetch('/Controller/InventoryController.php?action=getAllProducts')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                allProducts = data.products;
                updateStatsCards(data.stats);
                if (data.logs) updateLogsTable(data.logs);
                refreshTableData(); 
            }
        })
        .catch(e => console.error('Error updating data:', e));
}

function updateStatsCards(stats) {
    const statValues = document.querySelectorAll('.stat-card .stat-value');
    if (statValues.length >= 3) {
        statValues[0].textContent = stats.totalProducts || stats.total_products;
        
        const low = parseInt(stats.lowStockCount || stats.low_stock_count || 0);
        const out = parseInt(stats.outOfStockCount || stats.out_of_stock_count || 0);
        statValues[1].textContent = low;
        statValues[2].textContent = out;
    }
    
    const valueElement = document.getElementById('totalStockValueDisplay');
    if (valueElement) {
        const totalValue = parseFloat(stats.total_inventory_value || stats.totalStockValue || 0);
        valueElement.textContent = 'RM ' + totalValue.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
}


function toggleCategoryDropdown() {
    document.getElementById('categoryDropdown').classList.toggle('show');
    document.querySelector('.category-trigger').classList.toggle('active');
}

window.addEventListener('click', function(e) {
    if (!document.querySelector('.category-module').contains(e.target)) {
        document.getElementById('categoryDropdown').classList.remove('show');
        document.querySelector('.category-trigger').classList.remove('active');
    }
});

function selectCategory(category, categoryName) {
    document.getElementById('selectedCategoryName').textContent = categoryName;
    document.querySelectorAll('.cat-option').forEach(opt => {
        opt.classList.remove('active');
        if(opt.querySelector('.cat-name').textContent === categoryName) opt.classList.add('active');
    });

    const clearBtn = document.getElementById('clearCategoryBtn');
    const arrow = document.getElementById('categoryDropdownArrow');
    
    if (category === 'all') {
        clearBtn.style.display = 'none';
        arrow.style.display = 'block';
    } else {
        clearBtn.style.display = 'flex';
        arrow.style.display = 'none';
    }

    if (document.getElementById('categoryDropdown').classList.contains('show')) toggleCategoryDropdown();
    activeCategory = category;
    refreshTableData();
}

function applyFilter(filterType) {
    currentFilter = filterType;
    document.querySelectorAll('.stat-card').forEach(card => card.classList.remove('active'));
    if (filterType !== 'all') {
        const activeCard = document.querySelector(`.stat-card[data-filter="${filterType}"]`);
        if (activeCard) activeCard.classList.add('active');
    }
    refreshTableData();
}

function handleSearch(value) {
    currentSearchTerm = value.toLowerCase().trim();
    refreshTableData();
}

document.getElementById('clear-filter').addEventListener('click', function() {
    document.getElementById('productSearchInput').value = '';
    currentSearchTerm = '';
    currentFilter = 'all';
    document.querySelectorAll('.stat-card').forEach(card => card.classList.remove('active'));
    selectCategory('all', 'All Categories'); 
    document.getElementById('filter-indicator').style.display = 'none';
    refreshTableData();
});


function refreshTableData() {
    let filtered = allProducts;
    
    if (activeCategory !== 'all') {
        filtered = filtered.filter(p => p.Category === activeCategory);
    }
    if (currentFilter === 'low-stock') {
        filtered = filtered.filter(p => parseInt(p.Stock) <= parseInt(p.LowStockAlert) && parseInt(p.Stock) > 0);
    } else if (currentFilter === 'out-of-stock') {
        filtered = filtered.filter(p => parseInt(p.Stock) === 0);
    }
    if (currentSearchTerm !== '') {
        filtered = filtered.filter(p => 
            p.ProductName.toLowerCase().includes(currentSearchTerm) || 
            p.ProductID.toString().toLowerCase().includes(currentSearchTerm)
        );
    }
    updateProductsTable(filtered);
    updateFilterText(filtered.length);
}

function updateFilterText(count) {
    const indicator = document.getElementById('filter-indicator');
    if (currentFilter === 'all' && activeCategory === 'all' && currentSearchTerm === '') {
        indicator.style.display = 'none';
        return;
    }
    let details = [];
    if (activeCategory !== 'all') details.push(`Category: <strong>${activeCategory}</strong>`);
    if (currentFilter === 'low-stock') details.push(`Status: <strong>Low Stock</strong>`);
    if (currentFilter === 'out-of-stock') details.push(`Status: <strong>Out of Stock</strong>`);
    if (currentSearchTerm !== '') details.push(`Search: <strong>"${currentSearchTerm}"</strong>`);
    
    document.getElementById('filter-text').innerHTML = `Found ${count} products` + (details.length > 0 ? ` (${details.join(', ')})` : '');
    indicator.style.display = 'block';
}

function updateLogsTable(logs) {
    const tbody = document.querySelector('#logs table tbody');
    if (!tbody) return;
    if (logs.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" class="empty-state-cell"><div class="empty-state"><i class="fas fa-history"></i><h3>No Movement Logs</h3><p>Stock adjustments will appear here once made.</p></div></td></tr>`;
        return;
    }
    let html = '';
    logs.forEach(log => {
        html += `
            <tr>
                <td>${log.FormattedDate || log.CreatedAt}</td>
                <td><strong>${escapeHtml(log.ProductName)}</strong></td>
                <td>${escapeHtml(log.LogsDetails)}</td>
                <td>${escapeHtml(log.UserName)}</td>
            </tr>`;
    });
    tbody.innerHTML = html;
}

// --- HTML Generators ---
function updateProductsTable(products) {
    const tbody = document.querySelector('#stock table tbody');
    const thead = document.querySelector('#stock table thead tr');
    // Ensure table header for Image exists
    if (thead && !thead.querySelector('.th-image')) {
        const th = document.createElement('th');
        th.className = 'th-image';
        th.textContent = 'Image';
        thead.insertBefore(th, thead.firstElementChild);
    }

    if (!tbody) return;
    
    if (products.length === 0) {
        tbody.innerHTML = `<tr><td colspan="10" class="empty-state-cell"><div class="empty-state"><i class="fas fa-search"></i><h3>No Products Found</h3><p>No products match the current criteria.</p></div></td></tr>`;
        return;
    }
    
    let html = '';
    products.forEach(product => {
        const stock = parseInt(product.Stock);
        const alert = parseInt(product.LowStockAlert);
        let stockStatus = (stock == 0) ? 'out-of-stock' : (stock <= alert ? 'low-stock' : (stock <= alert * 2 ? 'warning' : 'good'));
        
        let badge = '';
        if(stockStatus === 'out-of-stock') badge = '<span class="badge badge-danger">Out of Stock</span>';
        else if(stockStatus === 'low-stock') badge = '<span class="badge badge-danger">Low Stock</span>';
        else if(stockStatus === 'warning') badge = '<span class="badge badge-warning">Watch</span>';
        else badge = '<span class="badge badge-success">Good</span>';
        
    
        let imageHtml = '';
        if (product.ImagePath && product.ImagePath.trim() !== "") {
            const imgSrc = `/Assets/Image/Product/${encodeURIComponent(product.ImagePath)}`; 
            imageHtml = `<img src="${imgSrc}" class="product-thumb" onclick="openImageModal('${imgSrc}')" alt="Img" onerror="this.parentNode.innerHTML='<div class=\\'no-image-placeholder\\'><i class=\\'fas fa-image\\'></i></div>'">`;
        } else {
            imageHtml = `<div class="no-image-placeholder"><i class="fas fa-image"></i></div>`;
        }

        html += `
            <tr>
                <td>${imageHtml}</td>
                <td><code>${escapeHtml(product.ProductID)}</code></td>
                <td><strong>${escapeHtml(product.ProductName)}</strong>${product.Description ? `<br><small style="color: var(--text-light);">${escapeHtml(product.Description)}</small>` : ''}</td>
                <td>${escapeHtml(product.Category)}</td>
                <td>${escapeHtml(product.SupplierName || 'N/A')}${product.SupplierEmail ? `<br><small style="color: var(--text-light);">${escapeHtml(product.SupplierEmail)}</small>` : ''}</td>
                <td><strong style="font-size: 16px;">${product.Stock}</strong></td>
                <td>${product.LowStockAlert}</td>
                <td>RM ${parseFloat(product.Price).toFixed(2)}</td>
                <td>${badge}</td>
                <td>
                    <button class="btn btn-primary" onclick="openAdjustModal('${product.ProductID}', '${escapeHtml(product.ProductName).replace(/'/g, "\\'")}')">
                        <i class="fas fa-edit"></i> Adjust
                    </button>
                </td>
            </tr>`;
    });
    tbody.innerHTML = html;
}

function escapeHtml(text) {
    if (text == null) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showAlert(message, type) {
    const existingAlert = document.querySelector('.alert');
    if (existingAlert) existingAlert.remove();
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>${message}`;
    
    const header = document.querySelector('.header');
    header.parentNode.insertBefore(alert, header.nextSibling);
    setTimeout(() => { if (alert.parentNode) alert.remove(); }, 5000);
}


function openImageModal(src) {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById("fullSizeImage");
    modal.style.display = "flex";
    modalImg.src = src;
}

function closeImageModal() {
    document.getElementById('imageModal').style.display = "none";
}

document.addEventListener('keydown', e => { 
    if (e.key === 'Escape') {
        if(document.getElementById('adjustModal')) closeModal();
        if(document.getElementById('imageModal')) closeImageModal();
    }
});



function loadImage(url) {
    return new Promise((resolve) => {
        const img = new Image();
        img.crossOrigin = "Anonymous"; 
        img.src = url;
        img.onload = () => resolve(img);
        img.onerror = () => resolve(null);
    });
}


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


async function generatePDF() {
    const btn = document.querySelector('button[onclick="generatePDF()"]');
    const originalText = btn.innerHTML;
    

    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Loading Libs...';
    btn.disabled = true;

    try {

        await ensurePDFLibraries();

        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';

        const doc = new window.jsPDF();
        const logoUrl = '/Assets/Icon/2ENTRAL-1.png'; 
        const date = new Date().toLocaleString();
        const userName = "<?= htmlspecialchars($currentUser ?? 'User') ?>";
        const primaryColor = [75, 163, 195];
        const productImages = {};
        const logoImg = await loadImage(logoUrl);

        const imagePromises = allProducts.map(async (p) => {
            if (p.ImagePath && p.ImagePath.trim() !== "") {
                const path = `/Assets/Image/Product/${encodeURIComponent(p.ImagePath)}`;
                const img = await loadImage(path);
                if (img) productImages[p.ProductID] = img;
            }
        });
        await Promise.all(imagePromises);


        let totalValue = 0;
        let lowStockItems = 0;
        let outOfStockItems = 0;
        const categorySummary = {};

        allProducts.forEach(p => {
            const stock = parseInt(p.Stock);
            const price = parseFloat(p.Price);
            const alert = parseInt(p.LowStockAlert);
            const val = stock * price;
            
            totalValue += val;
            if (stock === 0) outOfStockItems++;
            else if (stock <= alert) lowStockItems++;

            if (!categorySummary[p.Category]) {
                categorySummary[p.Category] = { count: 0, value: 0 };
            }
            categorySummary[p.Category].count += 1;
            categorySummary[p.Category].value += val;
        });


        if (logoImg) {
            doc.addImage(logoImg, 'PNG', 14, 10, 25, 25);
            doc.setFontSize(22);
            doc.setTextColor(...primaryColor);
            doc.text("Inventory Report", 45, 20);
            doc.setFontSize(14);
            doc.setTextColor(100);
            doc.text("2ENTRAL MANAGEMENT SYSTEM", 45, 28);
        } else {
            doc.setFontSize(22);
            doc.setTextColor(...primaryColor);
            doc.text("2ENTRAL Inventory Report", 14, 20);
        }
        
        doc.setFontSize(10);
        doc.setTextColor(100);
        doc.text(`Generated on: ${date}`, 14, 42);
        doc.text(`Generated by: ${userName}`, 14, 47);

        doc.setDrawColor(200);
        doc.setFillColor(248, 249, 250);
        doc.roundedRect(14, 52, 180, 25, 3, 3, 'FD');
        
        doc.setFontSize(11);
        doc.setTextColor(100);
        doc.text("Total Inventory Value", 20, 62);
        doc.setFontSize(16);
        doc.setTextColor(44, 62, 80);
        doc.text(`RM ${totalValue.toLocaleString('en-US', {minimumFractionDigits: 2})}`, 20, 71);
        
        doc.setFontSize(11);
        doc.setTextColor(100);
        doc.text("Stock Health", 100, 62);
        doc.setFontSize(16);
        if (lowStockItems + outOfStockItems > 0) doc.setTextColor(231, 76, 60); 
        else doc.setTextColor(39, 174, 96);
        doc.text(`${lowStockItems} Low Stock / ${outOfStockItems} Out of Stock`, 100, 71);


        const tableBody = allProducts.map(p => {
            const stock = parseInt(p.Stock);
            const alert = parseInt(p.LowStockAlert);
            let status = "Good";
            if (stock === 0) status = "Out of Stock";
            else if (stock <= alert) status = "Low Stock";
            
            return [
                '', 
                p.ProductID,
                p.ProductName,
                p.Category,
                stock,
                `RM ${parseFloat(p.Price).toFixed(2)}`,
                status
            ];
        });

    
        doc.autoTable({
            startY: 85,
            head: [['Image', 'ID', 'Product Name', 'Category', 'Stock', 'Price', 'Status']],
            body: tableBody,
            theme: 'grid',
            headStyles: { fillColor: primaryColor, halign: 'center' },
            columnStyles: {
                0: { cellWidth: 15, minCellHeight: 15 },
                4: { halign: 'center' },
                6: { fontStyle: 'bold' }
            },
            styles: { fontSize: 9, valign: 'middle', cellPadding: 3 },
            
            didParseCell: function(data) {
                if (data.section === 'body' && data.column.index === 6) {
                    const status = data.cell.raw;
                    if (status === 'Out of Stock') {
                        data.cell.styles.textColor = [231, 76, 60]; 
                    } else if (status === 'Low Stock') {
                        data.cell.styles.textColor = [243, 156, 18]; 
                    } else {
                        data.cell.styles.textColor = [39, 174, 96]; 
                    }
                }
            },

    
            didDrawCell: function(data) {
                if (data.section === 'body' && data.column.index === 0) {
                    const productId = data.row.raw[1]; 
                    const img = productImages[productId];
                    if (img) {
                        doc.addImage(img, 'JPEG', data.cell.x + 2, data.cell.y + 2, 11, 11);
                    }
                }
            }
        });

        let finalY = doc.lastAutoTable.finalY + 15;
        if (finalY > 250) { doc.addPage(); finalY = 20; }

   
        doc.setFontSize(14);
        doc.setTextColor(44, 62, 80);
        doc.text("Summary by Category", 14, finalY);
        
        const catBody = Object.keys(categorySummary).map(cat => [
            cat,
            categorySummary[cat].count,
            `RM ${categorySummary[cat].value.toLocaleString('en-US', {minimumFractionDigits: 2})}`
        ]);

        doc.autoTable({
            startY: finalY + 5,
            head: [['Category', 'Item Count', 'Total Value']],
            body: catBody,
            theme: 'striped',
            headStyles: { fillColor: [52, 73, 94] },
            tableWidth: 120,
            styles: { fontSize: 10 }
        });

        
        let signatureY = doc.lastAutoTable.finalY + 30; 
        const pageHeight = doc.internal.pageSize.height;


        if (signatureY + 20 > pageHeight) {
            doc.addPage();
            signatureY = 40; 
        }

        doc.setDrawColor(150);
        doc.setLineWidth(0.5);
        doc.line(20, signatureY, 80, signatureY);
        doc.setFontSize(10);
        doc.setTextColor(100);
        doc.text("Prepared By", 20, signatureY + 5);
        
        doc.line(120, signatureY, 180, signatureY);
        doc.text("Verified / Approved By", 120, signatureY + 5);

        doc.save(`2ENTRAL_Inventory_${new Date().toISOString().slice(0,10)}.pdf`);
    
    } catch (err) {
        console.error("PDF Error:", err);
        alert("Failed to load PDF libraries. Please check your internet connection.");
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}
</script>

</body>
</html>