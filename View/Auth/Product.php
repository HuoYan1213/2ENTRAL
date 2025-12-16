<?php
// Include database connection
include __DIR__ . '/../../model/DB.php';

// Check if connection exists
if (!isset($conn)) {
    die("Database connection failed");
}

// Initialize variables
$view_mode = '';
$product = null;
$products = [];
$error = '';
$search_query = '';
$sort_by = 'ProductName';

// Handle search and sort parameters
if (isset($_GET['search'])) {
    $search_query = trim($_GET['search']);
}

if (isset($_GET['sort'])) {
    $sort_by = $_GET['sort'];
}

// Check if we're viewing a single product or all products
if (isset($_GET['id'])) {
    // Single product view
    $product_id = $_GET['id'];
        $sql = "SELECT ProductID, ProductName, Description, Category, Stock, Price, LowStockAlert, ImagePath, SupplierID, IsActive 
            FROM products 
            WHERE ProductID = ? AND IsActive = 'Active'";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("s", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $product = $result->fetch_assoc();
            $view_mode = 'single';
        } else {
            $error = "Product not found with ID: " . htmlspecialchars($product_id);
        }
        $stmt->close();
    } else {
        $error = "Database error: " . $conn->error;
    }
} else {
    // All products view - with search and sort functionality
        $sql = "SELECT ProductID, ProductName, Description, Category, Stock, Price, LowStockAlert, ImagePath, SupplierID 
            FROM products 
            WHERE IsActive = 'Active'";
    
    // Add search filter if search query exists
    if (!empty($search_query)) {
        $sql .= " AND (ProductName LIKE ? OR Category LIKE ? OR Description LIKE ?)";
    }
    
    // Add sorting
    $valid_sort_columns = ['ProductName', 'Price', 'Category', 'Stock'];
    if (in_array($sort_by, $valid_sort_columns)) {
        $sql .= " ORDER BY " . $sort_by;
        if ($sort_by === 'Price' || $sort_by === 'Stock') {
            $sql .= " DESC"; // Sort prices and stock descending
        }
    } else {
        $sql .= " ORDER BY ProductName"; // Default sort
    }
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        if (!empty($search_query)) {
            $search_param = "%" . $search_query . "%";
            $stmt->bind_param("sss", $search_param, $search_param, $search_param);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $products = $result->fetch_all(MYSQLI_ASSOC);
            $view_mode = 'all';
        } else {
            $error = "No products found" . (!empty($search_query) ? " matching '" . htmlspecialchars($search_query) . "'" : ".");
        }
        $stmt->close();
    } else {
        $error = "Database error: " . $conn->error;
    }
}
?>
<style>
    /* Product-specific styles for dashboard integration */
    .product-content {
        background-color: var(--bg-light);
        width: 100%;
        min-height: 100%;
        box-sizing: border-box;
    }
    
    

    .product-header {
        background-color: var(--card-white);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        padding: 15px 0;
        margin-bottom: 20px;
    }
    
    .header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }
    
    .product-logo {
        font-size: 24px;
        font-weight: 700;
        color: var(--text-dark);
        text-decoration: none;
    }
    
    .search-sort-container {
        display: flex;
        align-items: center;
        gap: 15px;
        flex: 1;
        max-width: 600px;
        margin: 0 20px;
    }
    
    .search-form {
        display: flex;
        flex: 1;
    }
    
    .search-input {
        flex: 1;
        padding: 10px 15px;
        border: 1px solid var(--border);
        border-radius: 25px 0 0 25px;
        font-size: 14px;
        outline: none;
        transition: border-color 0.3s;
        background: var(--bg-light);
        color: var(--text-dark);
    }
    
    .search-input:focus {
        border-color: #3498db;
    }
    
    .search-button {
        padding: 10px 20px;
        background-color: #2c3e50;
        color: white;
        border: none;
        border-radius: 0 25px 25px 0;
        cursor: pointer;
        transition: background-color 0.3s;
    }
    
    .search-button:hover {
        background-color: #1a252f;
    }
    
    .sort-select {
        padding: 10px 15px;
        border: 1px solid var(--border);
        border-radius: 25px;
        background-color: var(--bg-light);
        color: var(--text-dark);
        font-size: 14px;
        cursor: pointer;
        outline: none;
        transition: border-color 0.3s;
    }
    
    .sort-select:focus {
        border-color: #3498db;
    }
    
    /* Single Product Section */
    .product-section {
        background-color: var(--card-white);
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        margin: 20px 0;
        overflow: hidden;
        max-width: 1200px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .product-container {
        display: flex;
        flex-wrap: wrap;
    }
    
    .product-image {
        flex: 1;
        min-width: 300px;
        padding: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: var(--bg-light);
    }
    
    .product-image img {
        max-width: 100%;
        max-height: 400px;
        object-fit: contain;
        border-radius: 4px;
    }
    
    .product-details {
        flex: 1;
        min-width: 300px;
        padding: 30px;
    }
    
    .product-title {
        font-size: 28px;
        margin-bottom: 15px;
        color: var(--text-dark);
    }
    
    .product-category {
        display: inline-block;
        background-color: #f0f8ff;
        color: #3498db;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 14px;
        margin-bottom: 15px;
        font-weight: 500;
    }
    
    .product-price {
        font-size: 24px;
        font-weight: 600;
        margin-bottom: 20px;
        color: var(--text-dark);
    }
    
    .product-description {
        margin-bottom: 25px;
        color: var(--text-grey);
        line-height: 1.7;
    }
    
    .stock-info {
        margin-bottom: 20px;
        padding: 15px;
        border-radius: 6px;
        background-color: var(--bg-light);
    }
    
    .stock-status {
        font-weight: 600;
        margin-bottom: 5px;
    }
    
    .in-stock {
        color: #27ae60;
    }
    
    .low-stock {
        color: #e67e22;
    }
    
    .out-of-stock {
        color: #e74c3c;
    }
    
    .quantity-selector {
        display: flex;
        align-items: center;
        margin-bottom: 25px;
    }
    
    .option-title {
        font-weight: 600;
        margin-bottom: 10px;
        color: #444;
        margin-right: 15px;
    }
    
    .quantity-btn {
        width: 36px;
        height: 36px;
        background-color: var(--bg-light);
        border: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 18px;
        color: var(--text-dark);
    }
    
    .quantity-input {
        width: 50px;
        height: 36px;
        text-align: center;
        border: 1px solid var(--border);
        border-left: none;
        border-right: none;
        background-color: var(--card-white);
        color: var(--text-dark);
    }
    
    .add-to-cart {
        background-color: #2c3e50;
        color: white;
        border: none;
        padding: 12px 25px;
        font-size: 16px;
        font-weight: 600;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.3s;
        width: 100%;
        margin-bottom: 15px;
    }
    
    .add-to-cart:hover {
        background-color: #1a252f;
    }
    
    .add-to-cart:disabled {
        background-color: #bdc3c7;
        cursor: not-allowed;
    }
    
    .wishlist-btn {
        background-color: var(--card-white);
        color: var(--text-grey);
        border: 1px solid var(--border);
        padding: 12px 25px;
        font-size: 16px;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.3s;
        width: 100%;
    }
    
    .wishlist-btn:hover {
        border-color: var(--text-grey);
        color: var(--text-dark);
    }
    
    /* Products Grid */
    .products-section {
        background-color: var(--card-white);
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        padding: 30px;
        margin: 20px 0;
        overflow: visible;
        max-width: 1200px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .section-title {
        font-size: 28px;
        margin-bottom: 30px;
        color: var(--text-dark);
        text-align: center;
    }
    
    .search-results-info {
        text-align: center;
        margin-bottom: 20px;
        color: var(--text-grey);
    }
    
    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 30px;
        overflow: visible;
    }
    
    .product-card {
        border: 1px solid var(--border);
        border-radius: 8px;
        overflow: hidden;
        transition: transform 0.3s, box-shadow 0.3s;
        background: var(--card-white);
        display: flex;
        flex-direction: column;
        height: 100%;
        min-height: 450px;
    }
    
    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
    
    .product-card-image {
        height: 200px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: var(--bg-light);
        padding: 20px;
        flex-shrink: 0;
    }
    
    .product-card-image img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }
    
    .product-card-details {
        padding: 20px;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
        justify-content: space-between;
    }
    
    .product-card-title {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 10px;
        color: var(--text-dark);
        line-height: 1.4;
        display: -webkit-box;
      
        overflow: hidden;
        text-overflow: ellipsis;
        min-height: 44px;
    }
    
    .product-card-category {
        display: inline-block;
        background-color: #f0f8ff;
        color: #3498db;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        margin-bottom: 10px;
        font-weight: 500;
        width: fit-content;
        max-width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .product-card-price {
        font-size: 18px;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 8px;
    }
    
    .product-card-stock {
        font-size: 14px;
        margin-bottom: 12px;
        min-height: 20px;
    }
    
    .product-card-id {
        font-size: 11px;
        color: var(--text-grey);
        margin-bottom: 12px;
        font-family: monospace;
        word-break: break-all;
    }
    
    .product-card-actions {
        display: flex;
        gap: 10px;
        margin-top: auto;
        padding-top: 10px;
    }

    .add-product-btn {
        background-color: #27ae60;
        color: white;
        border: none;
        padding: 9px 14px;
        border-radius: 20px;
        cursor: pointer;
        font-weight: 700;
        box-shadow: 0 4px 10px rgba(39,174,96,0.12);
    }

    .add-product-btn:hover {
        background-color: #229954;
    }
    
    .edit-btn {
        flex: 1;
        background-color: #3498db;
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
        text-align: center;
        font-size: 14px;
        transition: background-color 0.3s;
        font-weight: 500;
    }
    
    .edit-btn:hover {
        background-color: #1a252f;
    }
    
    /* Modal styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.4);
        padding-top: 50px;
    }
    
    .modal.show {
        display: block;
    }
    
    .modal-content {
        background-color: var(--card-white);
        margin: auto;
        padding: 20px;
        border-radius: 8px;
        width: 90%;
        max-width: 600px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        max-height: 90vh;
        overflow-y: auto;
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .modal-header h2 {
        margin: 0;
        color: var(--text-dark);
    }
    
    .close-btn {
        background: none;
        border: none;
        font-size: 28px;
        cursor: pointer;
        color: #999;
    }
    
    .close-btn:hover {
        color: #333;
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        color: var(--text-dark);
    }
    
    .form-group input, .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid var(--border);
        border-radius: 4px;
        font-size: 14px;
        box-sizing: border-box;
        background: var(--bg-light);
        color: var(--text-dark);
    }
    
    .form-group textarea {
        resize: vertical;
        min-height: 80px;
    }
    
    .form-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }
    
    .form-actions button {
        flex: 1;
        padding: 10px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 600;
    }
    
    .btn-save {
        background-color: #27ae60;
        color: white;
    }
    
    .btn-save:hover {
        background-color: #229954;
    }
    
    .btn-cancel {
        background-color: #e74c3c;
        color: white;
    }
    
    .btn-cancel:hover {
        background-color: #c0392b;
    }
    
    /* Stock status colors */
    .in-stock {
        color: #27ae60;
    }
    
    .low-stock {
        color: #e67e22;
    }
    
    .out-of-stock {
        color: #e74c3c;
    }
    
    /* Error Message */
    .error-message {
        background-color: #ffeaea;
        border: 1px solid #e74c3c;
        color: #c0392b;
        padding: 20px;
        border-radius: 8px;
        margin: 20px auto;
        text-align: center;
        max-width: 1200px;
    }
    
    /* Responsive Styles */
    @media (max-width: 768px) {
        .header-content {
            flex-direction: column;
            gap: 15px;
        }
        
        .search-sort-container {
            order: 2;
            width: 100%;
            margin: 10px 0;
        }
        
        .product-container {
            flex-direction: column;
        }
        
        .search-form {
            flex: 2;
        }
        
        .sort-select {
            flex: 1;
        }
        
        .products-grid {
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .product-card {
            min-height: 420px;
        }
        
        .product-card-title {
            font-size: 15px;
            min-height: 40px;
        }
        
        .product-card-image {
            height: 180px;
        }
    }
    
    @media (max-width: 480px) {
        .products-grid {
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        .product-card {
            min-height: 400px;
        }
        
        .section-title {
            font-size: 24px;
        }
        
        .product-card-image {
            height: 160px;
            padding: 15px;
        }
        
        .product-card-details {
            padding: 15px;
        }
        
        .search-sort-container {
            flex-direction: column;
            gap: 10px;
        }
        
        .search-form {
            width: 100%;
        }
        
        .sort-select {
            width: 100%;
        }
    }
</style>

<div class="product-content">
    <div class="product-header">
        <div class="header-content">
            <div class="product-logo">Product Store</div>
            <button type="button" class="add-product-btn" onclick="openAddModal()">+ Add Product</button>
            <div class="search-sort-container">
                <form method="GET" action="Product.php" class="search-form">
                    <input type="text" name="search" class="search-input" placeholder="Search products..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" class="search-button">Search</button>
                </form>
                <select name="sort" class="sort-select" onchange="this.form.submit()" form="sortForm">
                    <option value="ProductName" <?php echo $sort_by === 'ProductName' ? 'selected' : ''; ?>>Sort A-Z</option>
                    <option value="Price" <?php echo $sort_by === 'Price' ? 'selected' : ''; ?>>Sort by Price (High to Low)</option>
                    <option value="Category" <?php echo $sort_by === 'Category' ? 'selected' : ''; ?>>Sort by Category</option>
                    <option value="Stock" <?php echo $sort_by === 'Stock' ? 'selected' : ''; ?>>Sort by Stock</option>
                </select>
                <form id="sortForm" method="GET" action="Product.php" style="display: none;">
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                </form>
            </div>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="error-message">
            <?php echo $error; ?>
        </div>
    <?php elseif ($view_mode === 'single' && !empty($product)): ?>
        <!-- Single Product View -->
        <section class="product-section">
            <div class="product-container">
                <div class="product-image">
                    <img src="/Assets/Image/Product/<?php echo htmlspecialchars($product['ImagePath']); ?>" 
                    alt="<?php echo htmlspecialchars($product['ProductName']); ?>"
                    onerror="this.onerror=null; this.src='../../Assets/Image/Product/default-product.png';">                </div>
                <div class="product-details">
                    <h1 class="product-title"><?php echo htmlspecialchars($product['ProductName']); ?></h1>
                    <div class="product-category"><?php echo htmlspecialchars($product['Category']); ?></div>
                    <div class="product-id" style="font-size: 12px; color: #888; margin-bottom: 10px;">
                        Product ID: <?php echo htmlspecialchars($product['ProductID']); ?>
                    </div>
                    <div class="product-price">$<?php echo number_format($product['Price'], 2); ?></div>
                    
                   
                    <p class="product-description"><?php echo nl2br(htmlspecialchars($product['Description'])); ?></p>
                </div>
            </div>
        </section>
    <?php elseif ($view_mode === 'all' && !empty($products)): ?>
        <!-- All Products View -->
        <section class="products-section">
            <h1 class="section-title">All Products</h1>
            
            <?php if (!empty($search_query)): ?>
                <div class="search-results-info">
                    Showing <?php echo count($products); ?> product(s) matching "<?php echo htmlspecialchars($search_query); ?>"
                </div>
            <?php endif; ?>
            
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-card-image">
                            <img src="/Assets/Image/Product/<?php echo htmlspecialchars($product['ImagePath']); ?>" 
                            alt="<?php echo htmlspecialchars($product['ProductName']); ?>"
                            onerror="this.onerror=null; this.src='../../Assets/Image/Product/default-product.png';">                        </div>
                        <div class="product-card-details">
                            <h3 class="product-card-title"><?php echo htmlspecialchars($product['ProductName']); ?></h3>
                            <div class="product-card-category"><?php echo htmlspecialchars($product['Category']); ?></div>
                            <div class="product-card-id">ID: <?php echo htmlspecialchars($product['ProductID']); ?></div>
                            <div class="product-card-price">$<?php echo number_format($product['Price'], 2); ?></div>
                            <div class="product-card-stock <?php 
                                if ($product['Stock'] == 0) echo 'out-of-stock';
                                elseif ($product['Stock'] <= $product['LowStockAlert']) echo 'low-stock';
                                else echo 'in-stock';
                            ?>">
                                <?php
                                if ($product['Stock'] == 0) {
                                    echo 'Out of Stock';
                                } elseif ($product['Stock'] <= $product['LowStockAlert']) {
                                    echo 'Low Stock';
                                } else {
                                    echo 'In Stock';
                                }
                                ?>
                            </div>
                            <div class="product-card-actions">
                                <button type="button" class="edit-btn" onclick="openEditModal('<?php echo htmlspecialchars($product['ProductID']); ?>', '<?php echo htmlspecialchars(addslashes($product['ProductName'])); ?>', '<?php echo htmlspecialchars(addslashes($product['Category'])); ?>', '<?php echo htmlspecialchars(addslashes($product['Description'])); ?>', <?php echo $product['Price']; ?>, <?php echo $product['Stock']; ?>, <?php echo $product['LowStockAlert']; ?>, '<?php echo htmlspecialchars($product['SupplierID'] ?? ''); ?>', '<?php echo htmlspecialchars($product['ImagePath'] ?? ''); ?>')">Edit</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php else: ?>
        <div class="error-message">
            No products available.
        </div>
    <?php endif; ?>
</div>



<!-- Add Product Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add Product</h2>
            <button class="close-btn" onclick="closeAddModal()">&times;</button>
        </div>
        <div id="addFormError" style="display:none;color:#c0392b;margin-bottom:12px;"></div>
        <form id="addForm" method="POST" action="product-add.php">
            <div class="form-group">
                <label>Product Name</label>
                <input type="text" id="addProductName" name="product_name" required>
            </div>
            
            <div class="form-group">
                <label>Category</label>
                <input type="text" id="addCategory" name="category">
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea id="addDescription" name="description"></textarea>
            </div>
            
            <div class="form-group">
                <label>Price</label>
                <input type="number" id="addPrice" name="price" step="0.01" required>
            </div>
            
            <div class="form-group">
                <label>Stock</label>
                <input type="number" id="addStock" name="stock" required>
            </div>
            
            <div class="form-group">
                <label>Low Stock Alert</label>
                <input type="number" id="addLowStockAlert" name="low_stock_alert" required>
            </div>
            
            <div class="form-group">
                <label>Product Image (optional)</label>
                <input type="file" id="addProductImage" name="product_image" accept="image/*">
                <small style="color:#666;">Supported formats: JPG, PNG, GIF. Max size: 5MB</small>
                <div id="addImagePreview" style="margin-top:10px;display:none;">
                    <img id="addImagePreviewImg" style="max-width:150px;max-height:150px;border:1px solid #ddd;border-radius:4px;">
                </div>
            </div>

            <div class="form-group">
                <label>Supplier</label>
                <select id="addSupplier" name="supplier_id" required>
                    <option value="">-- Select Supplier --</option>
                    <?php
                    // Fetch suppliers from database
                    $supplier_sql = "SELECT SupplierID, SupplierName FROM suppliers WHERE IsActive = 'Active' ORDER BY SupplierName";
                    $supplier_result = $conn->query($supplier_sql);
                    if ($supplier_result && $supplier_result->num_rows > 0) {
                        while ($row = $supplier_result->fetch_assoc()) {
                            echo '<option value="' . htmlspecialchars($row['SupplierID']) . '">' . htmlspecialchars($row['SupplierName']) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-save">Create Product</button>
                <button type="button" class="btn-cancel" onclick="closeAddModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Product Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Product</h2>
            <button class="close-btn" onclick="closeEditModal()">&times;</button>
        </div>
        <form id="editForm" method="POST" action="product-edit.php">
            <input type="hidden" id="editProductId" name="product_id">
            
            <div class="form-group">
                <label>Product Name</label>
                <input type="text" id="editProductName" name="product_name" required>
            </div>
            
            <div class="form-group">
                <label>Category</label>
                <input type="text" id="editCategory" name="category">
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea id="editDescription" name="description"></textarea>
            </div>
            
            <div class="form-group">
                <label>Price</label>
                <input type="number" id="editPrice" name="price" step="0.01" required>
            </div>
            
            <div class="form-group">
                <label>Stock</label>
                <input type="number" id="editStock" name="stock" required>
            </div>
            
            <div class="form-group">
                <label>Low Stock Alert</label>
                <input type="number" id="editLowStockAlert" name="low_stock_alert" required>
            </div>

            <div class="form-group">
                <label>Product Image (optional)</label>
                <input type="file" id="editProductImage" name="product_image" accept="image/*">
                <small style="color:#666;">Supported formats: JPG, PNG, GIF. Max size: 5MB</small>
                <div id="editImagePreview" style="margin-top:10px;">
                    <img id="editImagePreviewImg" style="max-width:150px;max-height:150px;border:1px solid #ddd;border-radius:4px;">
                </div>
                <small style="color:#999;margin-top:5px;display:block;">Leave blank to keep current image</small>
            </div>

            <div class="form-group">
                <label>Supplier</label>
                <select id="editSupplier" name="supplier_id" required>
                    <option value="">-- Select Supplier --</option>
                    <?php
                    $supplier_sql = "SELECT SupplierID, SupplierName FROM suppliers WHERE IsActive = 'Active' ORDER BY SupplierName";
                    $supplier_result = $conn->query($supplier_sql);
                    if ($supplier_result && $supplier_result->num_rows > 0) {
                        while ($row = $supplier_result->fetch_assoc()) {
                            echo '<option value="' . htmlspecialchars($row['SupplierID']) . '">' . htmlspecialchars($row['SupplierName']) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-save">Save Changes</button>
                <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                <button type="button" class="btn-delete" onclick="deleteProduct(document.getElementById('editProductId').value)" style="background-color:#e74c3c;margin-left:auto;color:white">Delete Product</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(productId, productName, category, description, price, stock, lowStockAlert, supplierId, imagePath) {
    document.getElementById('editProductId').value = productId;
    document.getElementById('editProductName').value = productName;
    document.getElementById('editCategory').value = category;
    document.getElementById('editDescription').value = description;
    document.getElementById('editPrice').value = price;
    document.getElementById('editStock').value = stock;
    document.getElementById('editLowStockAlert').value = lowStockAlert;
    document.getElementById('editSupplier').value = supplierId || '';
    
    // Show current image if exists
    const previewImg = document.getElementById('editImagePreviewImg');
    const previewDiv = document.getElementById('editImagePreview');
    if (imagePath && imagePath.trim()) {
        previewImg.src = imagePath;
        previewDiv.style.display = 'block';
    } else {
        previewDiv.style.display = 'none';
    }
    
    document.getElementById('editProductImage').value = '';
    document.getElementById('editModal').classList.add('show');
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('show');
}

function deleteProduct(productId) {
    if (!productId) {
        alert('Product ID not found');
        return;
    }
    
    if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
        const formData = new FormData();
        formData.append('product_id', productId);
        
        fetch('product-delete.php', { method: 'POST', body: formData })
        .then(response => response.text().then(text => ({ ok: response.ok, status: response.status, statusText: response.statusText, text })))
        .then(resp => {
            const raw = resp.text;
            // Try to parse JSON, but show raw server output if it's not JSON
            try {
                const data = JSON.parse(raw);
                if (resp.ok && data.success) {
                    alert('Product deleted successfully');
                    closeEditModal();
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || ('HTTP ' + resp.status + ' ' + resp.statusText)));
                }
            } catch (e) {
                console.error('Failed to parse JSON from server:', e);
                // Show raw server response to help debugging
                alert('Server response (not JSON). HTTP ' + resp.status + ' ' + resp.statusText + '\n\n' + raw.substring(0, 2000));
            }
        })
        .catch(error => { 
            console.error('Fetch error:', error); 
            alert('An error occurred while deleting the product: ' + error.message); 
        });
    }
}

function openAddModal() {
    const form = document.getElementById('addForm');
    if (form) form.reset();
    const err = document.getElementById('addFormError');
    if (err) { err.style.display = 'none'; err.textContent = ''; }
    document.getElementById('addImagePreview').style.display = 'none';
    document.getElementById('addModal').classList.add('show');
}

function closeAddModal() {
    document.getElementById('addModal').classList.remove('show');
}

// Image preview for add form
const addImageInput = document.getElementById('addProductImage');
if (addImageInput) {
    addImageInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                const previewImg = document.getElementById('addImagePreviewImg');
                const previewDiv = document.getElementById('addImagePreview');
                previewImg.src = event.target.result;
                previewDiv.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });
}

// Image preview for edit form
const editImageInput = document.getElementById('editProductImage');
if (editImageInput) {
    editImageInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                const previewImg = document.getElementById('editImagePreviewImg');
                const previewDiv = document.getElementById('editImagePreview');
                previewImg.src = event.target.result;
                previewDiv.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });
}

// Close modals when clicking outside of them
window.onclick = function(event) {
    const editModal = document.getElementById('editModal');
    const addModal = document.getElementById('addModal');
    if (editModal && event.target == editModal) {
        editModal.classList.remove('show');
    }
    if (addModal && event.target == addModal) {
        addModal.classList.remove('show');
    }
}

// Handle edit form submission
const editFormEl = document.getElementById('editForm');
if (editFormEl) {
    editFormEl.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('product-edit.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Product updated successfully');
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => { console.error('Error:', error); alert('An error occurred while saving changes'); });
    });
}

// Handle add form submission
const addFormEl = document.getElementById('addForm');
if (addFormEl) {
    addFormEl.addEventListener('submit', function(e) {
        e.preventDefault();
        const errBox = document.getElementById('addFormError');
        if (errBox) { errBox.style.display = 'none'; errBox.textContent = ''; }

        const formData = new FormData(this);
        // Basic client validation
        const name = (formData.get('product_name') || '').toString().trim();
        const price = parseFloat(formData.get('price'));
        const stock = parseInt(formData.get('stock'));
        if (!name) { if (errBox) { errBox.textContent = 'Product name is required'; errBox.style.display = 'block'; } return; }
        if (isNaN(price) || price < 0) { if (errBox) { errBox.textContent = 'Price must be >= 0'; errBox.style.display = 'block'; } return; }
        if (isNaN(stock) || stock < 0) { if (errBox) { errBox.textContent = 'Stock must be >= 0'; errBox.style.display = 'block'; } return; }

        fetch('product-add.php', { method: 'POST', body: formData })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP Error: ${response.status} ${response.statusText}`);
            }
            return response.text();
        })
        .then(text => {
            console.log('Raw response:', text);
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    alert('Product created successfully');
                    location.reload();
                } else {
                    if (errBox) { errBox.textContent = data.message || 'Failed to create product'; errBox.style.display = 'block'; }
                }
            } catch (e) {
                console.error('JSON parse error:', e);
                if (errBox) { errBox.textContent = 'Invalid server response: ' + text.substring(0, 100); errBox.style.display = 'block'; }
            }
        })
        .catch(err => { 
            console.error('Fetch error:', err); 
            if (errBox) { errBox.textContent = 'Network error: ' + err.message; errBox.style.display = 'block'; } 
        });
    });
}
</script>

<?php
// Close database connection
if (isset($conn)) {
    $conn->close();
}
?>