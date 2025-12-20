<?php
// PrintInvoice.php
require_once __DIR__ . "/../../Model/DB.php";

if (!isset($_GET['id'])) { die("Invalid ID"); }
$id = $conn->real_escape_string($_GET['id']);

// 1. Fetch Header Info
// FIXED: Removed 's.Address' and 's.ContactNumber' because they don't exist in your table.
$sql = "SELECT po.*, s.SupplierName, s.Email as SupEmail
        FROM purchase_order po
        JOIN suppliers s ON po.SupplierID = s.SupplierID
        WHERE po.PurchaseID = '$id'";

$res = $conn->query($sql);
if ($res->num_rows == 0) die("Order not found");
$order = $res->fetch_assoc();

// 2. Fetch Items
$sqlItems = "SELECT pd.*, p.ProductName 
             FROM purchase_details pd
             JOIN products p ON pd.ProductID = p.ProductID
             WHERE pd.PurchaseID = '$id'";
$resItems = $conn->query($sqlItems);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?php echo $id; ?></title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; padding: 40px; }
        .invoice-box { max-width: 800px; margin: auto; border: 1px solid #eee; padding: 30px; }
        
        .header { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .title { font-size: 40px; font-weight: bold; color: #2c3e50; }
        .info { text-align: right; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #f8f9fa; text-align: left; padding: 12px; border-bottom: 2px solid #ddd; }
        td { padding: 12px; border-bottom: 1px solid #eee; }
    </style>
</head>
<body>

    <div class="invoice-box">
        <div class="header">
            <div>
                <div class="title">INVOICE</div>
                <div><strong>PO ID:</strong> #<?php echo $id; ?></div>
                <div><strong>Date:</strong> <?php echo date('d M Y', strtotime($order['CreatedAt'])); ?></div>
                <div><strong>Status:</strong> <?php echo $order['Status']; ?></div>
            </div>
            <div class="info">
                <strong>Supplier:</strong><br>
                <?php echo htmlspecialchars($order['SupplierName']); ?><br>
                <?php echo htmlspecialchars($order['SupEmail']); ?>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Item Description</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Unit Cost</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $grandTotal = 0;
                while($item = $resItems->fetch_assoc()): 
                    $grandTotal += $item['Subtotal'];
                    // Avoid division by zero check
                    $unitCost = ($item['Quantity'] > 0) ? $item['Subtotal'] / $item['Quantity'] : 0;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['ProductName']); ?></td>
                    <td style="text-align: center;"><?php echo $item['Quantity']; ?></td>
                    <td style="text-align: right;">RM <?php echo number_format($unitCost, 2); ?></td>
                    <td style="text-align: right;">RM <?php echo number_format($item['Subtotal'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div style="text-align: right; margin-top: 20px;">
            <h2 style="margin: 0;">Grand Total: RM <?php echo number_format($grandTotal, 2); ?></h2>
        </div>

        <div style="margin-top: 80px; display: flex; justify-content: space-between;">
            <div style="text-align: center; border-top: 1px solid #333; width: 200px; padding-top: 10px;">
                Authorized Signature
            </div>
            <div style="text-align: center; border-top: 1px solid #333; width: 200px; padding-top: 10px;">
                Date
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>