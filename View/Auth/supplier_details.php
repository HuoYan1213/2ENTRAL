<?php
require_once __DIR__ . "/../../Model/DB.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Supplier Info
$query = "SELECT * FROM suppliers WHERE SupplierID = $id";
$supplier = mysqli_fetch_assoc(mysqli_query($conn, $query));

// Supplier Products
$productQuery = "SELECT * FROM products WHERE SupplierID = $id AND IsActive='Active'";
$productResult = mysqli_query($conn, $productQuery);
?>

<style>
    /* Scoped styles for the modal content */
    .sd-header {
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 25px;
        padding-bottom: 20px;
        border-bottom: 1px solid #eee;
    }

    .sd-logo {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 50%; /* Circular logo looks modern */
        border: 2px solid #f0f0f0;
        background: #fafafa;
    }

    .sd-info h2 {
        margin: 0 0 5px 0;
        font-size: 24px;
        color: #333;
    }

    .sd-info p {
        margin: 0;
        color: #666;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .sd-products-title {
        font-size: 16px;
        font-weight: 700;
        color: #444;
        margin-bottom: 15px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Table Styling */
    .sd-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    .sd-table th {
        text-align: left;
        padding: 12px;
        background-color: #f8f9fa;
        color: #666;
        font-weight: 600;
        border-bottom: 2px solid #eee;
    }

    .sd-table td {
        padding: 12px;
        border-bottom: 1px solid #f0f0f0;
        color: #333;
    }

    .sd-table tr:last-child td {
        border-bottom: none;
    }

    .sd-table tr:hover {
        background-color: #fcfcfc;
    }

    .stock-tag {
        padding: 4px 8px;
        background: #e3f2fd;
        color: #1565c0;
        border-radius: 4px;
        font-weight: bold;
        font-size: 12px;
    }

    .price-text {
        font-weight: bold;
        color: #2e7d32;
    }
</style>

<div class="sd-header">
    <img src="../../Assets/Image/Supplier/<?php echo $supplier['ImagePath']; ?>" 
         class="sd-logo" 
         alt="Supplier Logo"
         onerror="this.src='../../Assets/Image/Supplier/default.jpg';"> <div class="sd-info">
        <h2><?php echo $supplier['SupplierName']; ?></h2>
        <p>
            ✉️ <?php echo $supplier['Email']; ?>
        </p>
    </div>
</div>

<div>
    <div class="sd-products-title">Products Supplied</div>

    <?php if (mysqli_num_rows($productResult) == 0): ?>
        <div style="text-align:center; padding: 20px; color:#888;">
            No products found for this supplier.
        </div>
    <?php else: ?>
        <table class="sd-table">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Stock</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($p = mysqli_fetch_assoc($productResult)) { ?>
                    <tr>
                        <td>
                            <strong><?php echo $p['ProductName']; ?></strong>
                        </td>
                        <td><?php echo $p['Category']; ?></td>
                        <td>
                            <span class="stock-tag"><?php echo $p['Stock']; ?> Units</span>
                        </td>
                        <td>
                            <span class="price-text">RM <?php echo number_format($p['Price'], 2); ?></span>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>