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
    $sql = "SELECT ProductID, ProductName, Description, Category, Stock, Price, LowStockAlert, ImagePath, IsActive 
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
    $sql = "SELECT ProductID, ProductName, Description, Category, Stock, Price, LowStockAlert, ImagePath 
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
        padding: 20px;
        background-color: #f9f9f9;
        min-height: 100vh;
    }
    
    .product-header {
        background-color: white;
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
        color: #333;
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
        border: 1px solid #ddd;
        border-radius: 25px 0 0 25px;
        font-size: 14px;
        outline: none;
        transition: border-color 0.3s;
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
        border: 1px solid #ddd;
        border-radius: 25px;
        background-color: white;
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
        background-color: white;
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
        background-color: #fafafa;
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
        color: #222;
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
        color: #2c3e50;
    }
    
    .product-description {
        margin-bottom: 25px;
        color: #666;
        line-height: 1.7;
    }
    
    .stock-info {
        margin-bottom: 20px;
        padding: 15px;
        border-radius: 6px;
        background-color: #f8f9fa;
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
        background-color: #f5f5f5;
        border: 1px solid #ddd;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 18px;
    }
    
    .quantity-input {
        width: 50px;
        height: 36px;
        text-align: center;
        border: 1px solid #ddd;
        border-left: none;
        border-right: none;
        background-color: white;
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
        background-color: white;
        color: #555;
        border: 1px solid #ddd;
        padding: 12px 25px;
        font-size: 16px;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.3s;
        width: 100%;
    }
    
    .wishlist-btn:hover {
        border-color: #aaa;
        color: #333;
    }
    
    /* Products Grid */
    .products-section {
        background-color: white;
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
        color: #222;
        text-align: center;
    }
    
    .search-results-info {
        text-align: center;
        margin-bottom: 20px;
        color: #666;
    }
    
    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 30px;
        overflow: visible;
    }
    
    .product-card {
        border: 1px solid #eee;
        border-radius: 8px;
        overflow: hidden;
        transition: transform 0.3s, box-shadow 0.3s;
        background: white;
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
        background-color: #fafafa;
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
        color: #333;
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
        color: #2c3e50;
        margin-bottom: 8px;
    }
    
    .product-card-stock {
        font-size: 14px;
        margin-bottom: 12px;
        min-height: 20px;
    }
    
    .product-card-id {
        font-size: 11px;
        color: #888;
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
    
    .view-btn {
        flex: 1;
        background-color: #2c3e50;
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
    
    .view-btn:hover {
        background-color: #1a252f;
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
                    <img src="<?php echo htmlspecialchars($product['ImagePath']); ?>" alt="<?php echo htmlspecialchars($product['ProductName']); ?>">
                </div>
                <div class="product-details">
                    <h1 class="product-title"><?php echo htmlspecialchars($product['ProductName']); ?></h1>
                    <div class="product-category"><?php echo htmlspecialchars($product['Category']); ?></div>
                    <div class="product-id" style="font-size: 12px; color: #888; margin-bottom: 10px;">
                        Product ID: <?php echo htmlspecialchars($product['ProductID']); ?>
                    </div>
                    <div class="product-price">$<?php echo number_format($product['Price'], 2); ?></div>
                    
                    <!-- Stock Information -->
                    <div class="stock-info">
                        <div class="stock-status <?php 
                            if ($product['Stock'] == 0) echo 'out-of-stock';
                            elseif ($product['Stock'] <= $product['LowStockAlert']) echo 'low-stock';
                            else echo 'in-stock';
                        ?>">
                            <?php
                            if ($product['Stock'] == 0) {
                                echo 'Out of Stock';
                            } elseif ($product['Stock'] <= $product['LowStockAlert']) {
                                echo 'Low Stock - Only ' . $product['Stock'] . ' left';
                            } else {
                                echo 'In Stock - ' . $product['Stock'] . ' available';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <p class="product-description"><?php echo nl2br(htmlspecialchars($product['Description'])); ?></p>
                    
                    <div class="quantity-selector">
                        <div class="option-title">Quantity:</div>
                        <button class="quantity-btn">-</button>
                        <input type="text" class="quantity-input" value="1" max="<?php echo $product['Stock']; ?>">
                        <button class="quantity-btn">+</button>
                    </div>
                    
                    <button id="add-to-cart-btn" class="add-to-cart" data-product-id="<?php echo htmlspecialchars($product['ProductID']); ?>" <?php echo $product['Stock'] == 0 ? 'disabled' : ''; ?>>
                        <?php echo $product['Stock'] == 0 ? 'Out of Stock' : 'Add to Cart'; ?>
                    </button>
                    <button id="wishlist-btn" class="wishlist-btn" data-product-id="<?php echo htmlspecialchars($product['ProductID']); ?>">Add to Wishlist</button>
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
                            <img src="<?php echo htmlspecialchars($product['ImagePath']); ?>" alt="<?php echo htmlspecialchars($product['ProductName']); ?>">
                        </div>
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
                                <a href="Product.php?id=<?php echo urlencode($product['ProductID']); ?>" class="view-btn">View Details</a>
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

<script>
// Cart & wishlist UI helpers (works standalone or inside dashboard)
async function refreshCartCount() {
    try {
        const r = await fetch('/View/Auth/cart.php');
        const j = await r.json();
        const el = document.querySelector('.cart-count');
        if (el) el.textContent = j.total || 0;
    } catch (e) { console.warn('cart refresh', e); }
}

async function refreshWishlistCount() {
    try {
        const r = await fetch('/View/Auth/wishlist.php');
        const j = await r.json();
        const el = document.querySelector('.wishlist-count');
        if (el) el.textContent = j.total || 0;
    } catch (e) { console.warn('wishlist refresh', e); }
}

// attach event listeners for add-to-cart and wishlist
(function(){
    // single product handlers
    const addBtn = document.getElementById('add-to-cart-btn');
    if (addBtn) addBtn.addEventListener('click', async function(){
        const id = this.dataset.productId;
        const qtyEl = document.querySelector('.quantity-input');
        const qty = qtyEl ? Math.max(1, parseInt(qtyEl.value||1)) : 1;
        try {
            await fetch('/View/Auth/cart.php?action=add&id=' + encodeURIComponent(id) + '&qty=' + encodeURIComponent(qty), { method:'POST' });
            await refreshCartCount();
            alert('Added to cart');
        } catch(e) { console.error(e); alert('Failed adding to cart'); }
    });

    const wishBtn = document.getElementById('wishlist-btn');
    if (wishBtn) wishBtn.addEventListener('click', async function(){
        const id = this.dataset.productId;
        try {
            const r = await fetch('/View/Auth/wishlist.php?action=toggle&id=' + encodeURIComponent(id), { method:'POST' });
            const j = await r.json();
            if (j.ok) {
                this.textContent = (j.action === 'added') ? 'Wishlisted' : 'Add to Wishlist';
                await refreshWishlistCount();
            }
        } catch(e) { console.error(e); alert('Failed wishlist'); }
    });

    // Quantity selector functionality
    const quantityInput = document.querySelector('.quantity-input');
    const minusBtn = document.querySelector('.quantity-btn:first-child');
    const plusBtn = document.querySelector('.quantity-btn:last-child');
    
    if (minusBtn && plusBtn && quantityInput) {
        minusBtn.addEventListener('click', () => {
            let currentValue = parseInt(quantityInput.value);
            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
            }
        });
        
        plusBtn.addEventListener('click', () => {
            let currentValue = parseInt(quantityInput.value);
            const maxStock = parseInt(quantityInput.getAttribute('max')) || 999;
            if (currentValue < maxStock) {
                quantityInput.value = currentValue + 1;
            }
        });
    }

    // initial counts
    refreshCartCount();
    refreshWishlistCount();
})();
</script>

<?php
// Close database connection
if (isset($conn)) {
    $conn->close();
}
?>