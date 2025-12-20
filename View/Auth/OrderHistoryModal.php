<?php
// OrderHistoryModal.php
require_once __DIR__ . "/../../Model/DB.php";

// Fetch Order History
// CHANGED: ORDER BY po.PurchaseID ASC
$histQuery = "SELECT po.*, s.SupplierName 
              FROM purchase_order po 
              LEFT JOIN suppliers s ON po.SupplierID = s.SupplierID 
              ORDER BY po.PurchaseID ASC"; 

$histResult = $conn->query($histQuery);
$history = [];
if ($histResult) {
    while ($row = $histResult->fetch_assoc()) {
        $history[] = $row;
    }
}
?>

<div class="modal-card" onclick="event.stopPropagation()">
    <div class="modal-header">
        <div class="modal-title">
            <i class="fa-solid fa-file-invoice"></i> Purchase Order History
        </div>
        <button class="btn-close-modal" onclick="closeHistoryModal()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    
    <div class="modal-body">
        <?php if (empty($history)): ?>
            <div style="text-align:center; padding: 40px; color:#95a5a6;">
                <i class="fa-solid fa-box-open" style="font-size: 3rem; margin-bottom: 10px;"></i><br>
                No purchase history found.
            </div>
        <?php else: ?>
            <table class="hist-table">
                <thead>
                    <tr>
                        <th>PO ID</th>
                        <th>Date</th>
                        <th>Supplier</th>
                        <th>Status</th>
                        <th style="text-align:right;">Total</th>
                        <th style="text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $h): 
                        $stClass = 'st-pending';
                        $s = strtolower($h['Status']);
                        if(strpos($s, 'complet') !== false || strpos($s, 'paid') !== false) $stClass = 'st-completed';
                        if(strpos($s, 'cancel') !== false) $stClass = 'st-cancelled';
                    ?>
                    <tr>
                        <td>#<?php echo $h['PurchaseID']; ?></td>
                        <td><?php echo date('d M Y', strtotime($h['CreatedAt'])); ?></td>
                        <td><?php echo htmlspecialchars($h['SupplierName'] ?? 'Unknown'); ?></td>
                        <td><span class="status-badge <?php echo $stClass; ?>"><?php echo $h['Status']; ?></span></td>
                        <td style="text-align:right;">RM <?php echo number_format($h['TotalAmount'], 2); ?></td>
                        
                        <td>
                            <div class="action-btn-wrapper">
                                <button class="btn-action-view" onclick="viewOrderHistory('<?php echo $h['PurchaseID']; ?>')">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                <button class="btn-action-print" onclick="printOrderHistory('<?php echo $h['PurchaseID']; ?>')">
                                    <i class="fa-solid fa-print"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<style>
    /* Modal Styles */
    .modal-card {
        background: var(--card-white);
        width: 900px;
        max-width: 90%;
        max-height: 85vh;
        border-radius: 15px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        animation: slideDown 0.3s ease;
        cursor: default; 
    }
    @keyframes slideDown { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

    .modal-header {
        padding: 15px 20px;
        border-bottom: 1px solid var(--border);
        display: flex; justify-content: space-between; align-items: center;
        background: var(--bg-light);
    }
    .modal-title { font-size: 1.2rem; font-weight: bold; color: var(--text-dark); display: flex; align-items: center; gap: 10px; }
    .btn-close-modal { background: none; border: none; font-size: 1.5rem; color: #95a5a6; cursor: pointer; transition: 0.2s; }
    .btn-close-modal:hover { color: #e74c3c; }

    .modal-body { padding: 0; overflow-y: auto; }
    
    /* Table Styles */
    .hist-table { width: 100%; border-collapse: collapse; font-size: 0.95rem; }
    
    .hist-table th { 
        background: var(--bg-light); color: #7f8c8d; text-align: left; 
        padding: 15px 20px; font-weight: 600; 
        position: sticky; top: 0; 
        border-bottom: 2px solid var(--border); 
        z-index: 2;
    }
    
    .hist-table td { 
        padding: 12px 20px; 
        border-bottom: 1px solid var(--border); 
        color: var(--text-dark);
        vertical-align: middle; 
    }
    
    .hist-table tr:hover { background: var(--bg-light); }

    .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; display: inline-block; }
    .st-completed { background: #d5f5e3; color: #27ae60; }
    .st-pending { background: #fdebd0; color: #e67e22; }
    .st-cancelled { background: #fadbd8; color: #c0392b; }

    /* Button Wrapper */
    .action-btn-wrapper {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
        align-items: center;
    }

    .btn-action-view, .btn-action-print {
        width: 32px; height: 32px;
        border-radius: 6px; 
        border: none; 
        color: white; 
        cursor: pointer; 
        font-size: 0.9rem;
        display: flex; align-items: center; justify-content: center;
        transition: 0.2s;
    }
    
    .btn-action-view { background: #3498db; }
    .btn-action-print { background: #95a5a6; }
    
    .btn-action-view:hover { background: #2980b9; transform: translateY(-2px); }
    .btn-action-print:hover { background: #7f8c8d; transform: translateY(-2px); }
</style>