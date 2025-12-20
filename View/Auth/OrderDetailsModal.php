<?php
// OrderDetailsModal.php
require_once __DIR__ . "/../../Model/DB.php";

// Validate ID
if (!isset($_GET['id'])) {
    echo "<div style='padding:20px; color:red; background:white;'>Error: No Order ID provided.</div>";
    exit;
}

$purchaseID = $conn->real_escape_string($_GET['id']);

// 1. Fetch General Order Info
$orderQuery = "SELECT po.*, s.SupplierName 
               FROM purchase_order po 
               LEFT JOIN suppliers s ON po.SupplierID = s.SupplierID 
               WHERE po.PurchaseID = '$purchaseID'";
$orderResult = $conn->query($orderQuery);
$orderInfo = $orderResult->fetch_assoc();

// 2. Fetch Order Details
$detailQuery = "SELECT pd.*, p.ProductName, p.ImagePath 
                FROM purchase_details pd
                JOIN products p ON pd.ProductID = p.ProductID
                WHERE pd.PurchaseID = '$purchaseID'";
$detailResult = $conn->query($detailQuery);
?>

<div class="modal-card" onclick="event.stopPropagation()">
    <div class="modal-header">
        <div class="modal-title">
            <button class="btn-back-modal" onclick="loadHistoryList()">
                <i class="fa-solid fa-arrow-left"></i>
            </button>
            <span>Order Details: #<?php echo htmlspecialchars($purchaseID); ?></span>
        </div>
        <button class="btn-close-modal" onclick="closeHistoryModal()"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <div class="modal-body">
        <div class="details-summary">
            <div class="summary-item">
                <span class="label">Supplier:</span> 
                <span class="value"><?php echo htmlspecialchars($orderInfo['SupplierName']); ?></span>
            </div>
            <div class="summary-item">
                <span class="label">Date:</span> 
                <span class="value"><?php echo date('d M Y, h:i A', strtotime($orderInfo['CreatedAt'])); ?></span>
            </div>
            <div class="summary-item">
                <span class="label">Status:</span> 
                <span class="status-badge-simple"><?php echo $orderInfo['Status']; ?></span>
            </div>
        </div>

        <div class="table-container">
            <table class="details-table">
                <thead>
                    <tr>
                        <th style="width: 50%;">Product</th>
                        <th style="width: 10%; text-align: center;">Qty</th>
                        <th style="width: 20%; text-align: right;">Unit Cost</th>
                        <th style="width: 20%; text-align: right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grandTotal = 0;
                    if ($detailResult && $detailResult->num_rows > 0):
                        while ($item = $detailResult->fetch_assoc()): 
                            $grandTotal += $item['Subtotal'];
                            // Avoid division by zero
                            $unitCost = ($item['Quantity'] > 0) ? ($item['Subtotal'] / $item['Quantity']) : 0;
                    ?>
                    <tr>
                        <td>
                            <div class="product-cell">
                                <img src="../../Assets/Image/Product/<?php echo $item['ImagePath']; ?>" 
                                     onerror="this.src='../../Assets/Image/Product/default-product.png'">
                                <div class="prod-info">
                                    <div class="prod-name"><?php echo htmlspecialchars($item['ProductName']); ?></div>
                                    <div class="prod-id">ID: <?php echo $item['ProductID']; ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="text-align: center; font-weight: bold;"><?php echo $item['Quantity']; ?></td>
                        <td style="text-align: right;">RM <?php echo number_format($unitCost, 2); ?></td>
                        <td style="text-align: right; font-weight: bold; color: var(--text-dark);">RM <?php echo number_format($item['Subtotal'], 2); ?></td>
                    </tr>
                    <?php endwhile; 
                    else: ?>
                    <tr><td colspan="4" style="text-align:center; padding:20px; color:#999;">No items found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal-footer">
        <div class="grand-total-label">Grand Total:</div>
        <div class="grand-total-value">RM <?php echo number_format($grandTotal, 2); ?></div>
    </div>
</div>

<style>
    /* --- RE-INCLUDED BASE MODAL STYLES --- */
    .modal-card {
        background: #ffffff; /* Explicit white background */
        width: 900px;
        max-width: 95%;
        max-height: 90vh;
        border-radius: 12px;
        box-shadow: 0 15px 40px rgba(0,0,0,0.25);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        animation: slideIn 0.3s ease-out;
        position: relative;
        cursor: default;
    }
    
    @keyframes slideIn {
        from { transform: translateY(20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    /* Header */
    .modal-header {
        background: #f8f9fa;
        padding: 15px 25px;
        border-bottom: 1px solid #e0e0e0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
    }
    .modal-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #2c3e50;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    /* Buttons */
    .btn-close-modal {
        background: none; border: none; font-size: 1.5rem; color: #95a5a6; cursor: pointer;
    }
    .btn-close-modal:hover { color: #e74c3c; }

    .btn-back-modal {
        background: #fff; border: 1px solid #ddd;
        width: 32px; height: 32px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; font-size: 0.9rem; color: #555;
        transition: 0.2s;
    }
    .btn-back-modal:hover { background: #3498db; color: white; border-color: #3498db; }

    /* Body */
    .modal-body {
        padding: 0;
        overflow-y: auto;
        flex-grow: 1;
        display: flex; 
        flex-direction: column;
    }

    /* Summary Strip */
    .details-summary {
        background: #fff;
        padding: 20px 25px;
        border-bottom: 1px solid #eee;
        display: flex;
        flex-wrap: wrap;
        gap: 30px;
        background: #fdfdfd;
    }
    .summary-item { display: flex; align-items: center; gap: 8px; font-size: 0.95rem; }
    .summary-item .label { font-weight: bold; color: #7f8c8d; }
    .summary-item .value { font-weight: 600; color: #2c3e50; }
    .status-badge-simple {
        background: #2c3e50; color: white;
        padding: 4px 10px; border-radius: 4px;
        font-size: 0.85rem; font-weight: bold;
    }

    /* Table */
    .table-container { padding: 0; }
    .details-table { width: 100%; border-collapse: collapse; }
    
    .details-table th {
        background: #f8f9fa;
        color: #7f8c8d;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        padding: 12px 25px;
        border-bottom: 2px solid #e0e0e0;
        text-align: left;
    }
    
    .details-table td {
        padding: 15px 25px;
        border-bottom: 1px solid #f1f1f1;
        vertical-align: middle;
        color: #2c3e50;
    }

    .product-cell { display: flex; align-items: center; gap: 15px; }
    .product-cell img {
        width: 48px; height: 48px; 
        object-fit: contain; 
        border-radius: 6px; 
        border: 1px solid #eee;
        background: #fff;
    }
    .prod-info { display: flex; flex-direction: column; justify-content: center; }
    .prod-name { font-weight: 600; font-size: 0.95rem; color: #2c3e50; line-height: 1.2; }
    .prod-id { font-size: 0.8rem; color: #95a5a6; margin-top: 3px; }

    /* Footer */
    .modal-footer {
        background: #f8f9fa;
        border-top: 2px solid #e0e0e0;
        padding: 15px 25px;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 15px;
        flex-shrink: 0;
    }
    .grand-total-label { font-size: 1.1rem; font-weight: bold; color: #7f8c8d; }
    .grand-total-value { 
        font-size: 1.4rem; 
        font-weight: 800; 
        color: #27ae60; 
        background: #fff;
        padding: 5px 15px;
        border-radius: 8px;
        border: 1px solid #d5f5e3;
        box-shadow: 0 2px 5px rgba(39, 174, 96, 0.1);
    }
</style>