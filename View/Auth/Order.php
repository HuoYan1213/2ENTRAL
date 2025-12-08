<?php
// Order.php - HORIZONTAL RECTANGLE LAYOUT
require_once __DIR__ . "/../../Model/DB.php";

// 1. Fetch Products
$query = "SELECT p.*, s.SupplierName, s.SupplierID as SID 
          FROM products p 
          JOIN suppliers s ON p.SupplierID = s.SupplierID 
          WHERE p.IsActive = 'Active'";
$result = $conn->query($query);
$products = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// 2. Fetch Suppliers
$supQuery = "SELECT * FROM suppliers WHERE IsActive = 'Active'";
$supResult = $conn->query($supQuery);
$suppliers = [];
if ($supResult) {
    while ($row = $supResult->fetch_assoc()) {
        $suppliers[] = $row;
    }
}
?>

<div class="main-order-container">
    <style>
        /* --- GLOBAL & LAYOUT --- */
        .main-order-container * { box-sizing: border-box; }
        .main-order-container { 
            height: 100%; 
            background: #f4f6f9; 
            position: relative; 
            overflow: hidden; 
        }

        /* --- VIEW 1: SUPPLIER SELECTION --- */
        #view-suppliers { padding: 30px; text-align: center; height: 100%; overflow-y: auto; }
        .supplier-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 25px; margin-top: 20px; padding-bottom: 50px;
        }
        .supplier-card {
            background: white; border-radius: 12px; padding: 25px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05); cursor: pointer; transition: 0.3s;
            border: 2px solid transparent; display: flex; flex-direction: column; align-items: center;
        }
        .supplier-card:hover {
            transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); border-color: #3498db;
        }
        .supplier-card img { width: 100px; height: 100px; object-fit: contain; margin-bottom: 15px; }
        .sup-name { font-size: 1.1rem; font-weight: bold; color: #2c3e50; margin-bottom: 5px; }
        .sup-contact { font-size: 0.9rem; color: #7f8c8d; }

        /* --- VIEW 2: PRODUCT ORDERING --- */
        #view-products { display: none; height: 100%; display: flex; flex-direction: column; }
        .order-layout-wrapper { display: flex; width: 100%; height: 100%; gap: 20px; overflow: hidden; }
        
        /* LEFT: PRODUCTS LIST */
        .product-section { flex: 7; display: flex; flex-direction: column; gap: 15px; height: 100%; overflow: hidden; }
        
        .top-bar { 
            flex-shrink: 0; display: flex; align-items: center; gap: 15px; 
            background: white; padding: 15px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); 
        }

        /* CHANGED: List View for Rectangles */
        .product-list { 
            flex-grow: 1; overflow-y: auto; min-height: 0; padding-right: 5px; 
            display: flex; flex-direction: column; gap: 10px; /* Stack items vertically */
        }

        /* CHANGED: Horizontal Card Styling */
        .product-card { 
            background: white; border-radius: 10px; padding: 15px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.05); 
            display: flex; flex-direction: row; align-items: center; /* Horizontal alignment */
            border-left: 5px solid #3498db; /* Accent on left */
            gap: 20px;
            transition: 0.2s;
        }
        .product-card:hover { transform: translateX(5px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }

        .product-card img { 
            width: 70px; height: 70px; object-fit: contain; flex-shrink: 0; 
        }

        /* Middle Info Section */
        .card-info { flex: 1; text-align: left; }
        .p-name { font-weight: bold; font-size: 1rem; color: #2c3e50; margin-bottom: 4px; }
        .p-meta { font-size: 0.85rem; color: #7f8c8d; display: flex; gap: 15px; }
        .stock-tag { font-weight: bold; padding: 2px 6px; border-radius: 4px; background: #f0f0f0; }
        .good-stock { color: #2ecc71; }
        .low-stock { color: #e74c3c; }
        .p-price { font-weight: bold; color: #2980b9; }

        /* Right Action Section */
        .action-group { 
            display: flex; flex-direction: column; align-items: flex-end; gap: 5px; flex-shrink: 0; 
        }
        .qty-input-field { width: 70px; padding: 5px; border: 1px solid #ddd; border-radius: 5px; text-align: center; }
        .btn-add { 
            background: #34495e; color: white; border: none; padding: 8px 15px; 
            border-radius: 5px; cursor: pointer; font-size: 0.9rem;
        }
        .btn-add:hover { background: #2c3e50; }

        /* RIGHT: CART (Unchanged logic, kept styles) */
        .cart-section { 
            flex: 3; background: white; border-radius: 15px; padding: 20px; 
            display: flex; flex-direction: column; box-shadow: 0 5px 15px rgba(0,0,0,0.05); 
            height: 100%; border-top: 5px solid #2ecc71; overflow: hidden; 
        }
        .cart-header { flex-shrink: 0; font-size: 1.2rem; font-weight: bold; margin-bottom: 15px; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; color: #2c3e50; }
        .cart-items { flex: 1; overflow-y: auto; margin-bottom: 15px; min-height: 0; }
        .cart-footer { flex-shrink: 0; border-top: 2px solid #f0f0f0; padding-top: 15px; background: white; }
        
        .cart-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; font-size: 0.9rem; }
        .cart-controls { display: flex; align-items: center; gap: 3px; }
        .qty-btn { width: 22px; height: 22px; border: 1px solid #ddd; background: #fff; cursor: pointer; }
        .remove-btn { color: #e74c3c; cursor: pointer; margin-left: 8px; }
        .total-row { display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: bold; margin-bottom: 15px; }
        .btn-checkout { background: #27ae60; color: white; border: none; padding: 15px; width: 100%; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 1rem; }
        .btn-checkout:disabled { background: #bdc3c7; cursor: not-allowed; }
        .btn-back-sup { padding: 8px 15px; background: #e74c3c; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;}
        .search-input { padding: 8px; border: 1px solid #ddd; border-radius: 5px; width: 220px; }
    </style>

    <div id="view-suppliers">
        <h2 style="color: #2c3e50;">Select Supplier</h2>
        <div class="supplier-grid">
            <?php foreach($suppliers as $sup): ?>
                <div class="supplier-card" onclick="selectSupplier('<?php echo $sup['SupplierID']; ?>', '<?php echo htmlspecialchars($sup['SupplierName']); ?>')">
                    <img src="../../Assets/Image/Supplier/<?php echo $sup['ImagePath']; ?>" onerror="this.src='../../Assets/Image/Supplier/default.jpg'">
                    <div class="sup-name"><?php echo htmlspecialchars($sup['SupplierName']); ?></div>
                    <div class="sup-contact"><?php echo htmlspecialchars($sup['Email']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="view-products"> <?php  // View Product 1 ?>
        <div class="order-layout-wrapper">
            <div class="product-section">
                <div class="top-bar">
                    <button class="btn-back-sup" id="btnChangeSupplier">
                        <i class="fa-solid fa-arrow-left"></i> Back
                    </button>
                    <div id="activeSupplierDisplay" style="font-weight:bold; flex:1; font-size:1.1rem; color:#2c3e50;"></div>
                    <input type="text" id="searchProduct" class="search-input" placeholder="Search products...">
                </div>

                <div class="product-list" id="productGrid">
                    <?php foreach ($products as $prod): 
                        $stockClass = ($prod['Stock'] < 10) ? 'low-stock' : 'good-stock';
                    ?>
                        <div class="product-card" 
                             data-supplier-id="<?php echo $prod['SupplierID']; ?>"
                             data-id="<?php echo $prod['ProductID']; ?>" 
                             data-name="<?php echo htmlspecialchars($prod['ProductName']); ?>"
                             data-price="<?php echo $prod['Price']; ?>">
                            
                            <img src="../../Assets/Image/Product/<?php echo $prod['ImagePath']; ?>" alt="Product">
                            
                            <div class="card-info">
                                <div class="p-name"><?php echo htmlspecialchars($prod['ProductName']); ?></div>
                                <div class="p-meta">
                                    <span class="stock-tag <?php echo $stockClass; ?>">
                                        Stock: <?php echo $prod['Stock']; ?>
                                    </span>
                                    <span class="p-price">Cost: RM <?php echo $prod['Price']; ?></span>
                                </div>
                            </div>
                            
                            <div class="action-group">
                                <input type="number" class="qty-input-field" id="qty_input_<?php echo $prod['ProductID']; ?>" value="10" min="1">
                                <button class="btn-add order-add-btn" data-id="<?php echo $prod['ProductID']; ?>">Restock</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="cart-section">
                <div class="cart-header">
                    <i class="fa-solid fa-clipboard-list"></i> Order Draft
                </div>
                
                <div class="cart-items" id="cartItems">
                    <div style="text-align: center; color: #aaa; margin-top: 50px;">Draft is empty</div>
                </div>
                
                <div class="cart-footer">
                    <div class="total-row">
                        <span>Est. Cost:</span>
                        <span id="cartTotal">RM 0.00</span>
                    </div>
                    <button class="btn-checkout" id="checkoutBtn" disabled>
                        Review & Confirm <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    // 1. CHANGED: Try to load existing data from LocalStorage immediately
    let poDraft = JSON.parse(localStorage.getItem('userCart')) || []; 
    let currentSupplierID = null; 
    const allProducts = <?php echo json_encode($products); ?>;

    // --- NEW: RESTORE STATE ON LOAD ---
    // If we have items in the cart, we must skip the "Select Supplier" screen
    // and go straight to the product view for that supplier.
    if (poDraft.length > 0) {
        // Get the supplier info from the first item in the draft
        currentSupplierID = poDraft[0].supplierId;
        let savedSupplierName = poDraft[0].supplierName;

        // Update the Top Bar UI
        $('#activeSupplierDisplay').html(`<i class="fa-solid fa-truck-field"></i> ${savedSupplierName}`);
        
        // Switch Views immediately
        $('#view-suppliers').hide();
        $('#view-products').css('display', 'flex');
        
        // Render the saved items
        renderDraft();
        
        // Filter the grid to show products for this supplier
        // (We use setTimeout to ensure the DOM elements are fully ready)
        setTimeout(filterProductGrid, 50);
    }

    // --- VIEW SWITCHING ---
    window.selectSupplier = function(id, name) {
        currentSupplierID = id;
        $('#activeSupplierDisplay').html(`<i class="fa-solid fa-truck-field"></i> ${name}`);
        $('#view-suppliers').hide();
        $('#view-products').css('display', 'flex').hide().fadeIn(200);
        filterProductGrid();
    };

    $('#btnChangeSupplier').on('click', function() {
        if (poDraft.length > 0 && !confirm("Changing supplier will clear your current draft. Continue?")) return;
        
        // Clear data
        poDraft = [];
        currentSupplierID = null;
        localStorage.removeItem('userCart'); // Clear storage too
        
        renderDraft();
        $('#view-products').hide();
        $('#view-suppliers').fadeIn(200);
    });

    // --- FILTERING ---
    function filterProductGrid() {
        let searchVal = $('#searchProduct').val().toLowerCase();
        $('.product-card').each(function() {
            let cardSupID = $(this).data('supplier-id');
            let cardName = $(this).data('name').toLowerCase();
            // Match Supplier ID AND Search Text
            if (cardSupID == currentSupplierID && cardName.indexOf(searchVal) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }
    $(document).on('keyup', '#searchProduct', function() { filterProductGrid(); });

    // --- CART LOGIC ---
    window.addToPO = function(id, amount) {
        const product = allProducts.find(p => p.ProductID === id);
        const existingItem = poDraft.find(item => item.id === id);
        amount = parseInt(amount);
        if (isNaN(amount) || amount <= 0) { alert("Invalid quantity"); return; }

        if (existingItem) {
            existingItem.qty += amount;
        } else {
            poDraft.push({
                id: product.ProductID,
                name: product.ProductName,
                price: parseFloat(product.Price),
                qty: amount,
                supplierId: product.SupplierID,
                supplierName: product.SupplierName
            });
        }
        renderDraft();
        saveToStorage(); // Save on every change
    };

    window.updatePOQty = function(id, change) {
        const item = poDraft.find(i => i.id === id);
        if (!item) return;
        const newQty = item.qty + change;
        if (newQty > 0) item.qty = newQty;
        else removeFromPO(id);
        renderDraft();
        saveToStorage(); // Save on every change
    };

    window.removeFromPO = function(id) {
        poDraft = poDraft.filter(item => item.id !== id);
        renderDraft();
        saveToStorage(); // Save on every change
    };

    // Helper to save state
    function saveToStorage() {
        localStorage.setItem('userCart', JSON.stringify(poDraft));
    }

    function renderDraft() {
        const container = $('#cartItems');
        container.empty();
        let total = 0;

        if (poDraft.length === 0) {
            container.html('<div style="text-align: center; color: #aaa; margin-top: 50px;">Draft is empty</div>');
            $('#checkoutBtn').prop('disabled', true);
            $('#cartTotal').text('RM 0.00');
            return;
        }

        poDraft.forEach(item => {
            const lineTotal = item.price * item.qty;
            total += lineTotal;
            container.append(`
                <div class="cart-item">
                    <div style="flex:1">
                        <div style="font-weight:bold;">${item.name}</div>
                        <div style="color:#777; font-size:0.8rem;">RM ${item.price.toFixed(2)} x ${item.qty}</div>
                    </div>
                    <div class="cart-controls">
                        <button class="qty-btn" onclick="updatePOQty('${item.id}', -1)">-</button>
                        <span style="width:25px; text-align:center; font-weight:bold;">${item.qty}</span>
                        <button class="qty-btn" onclick="updatePOQty('${item.id}', 1)">+</button>
                        <i class="fa-solid fa-trash remove-btn" onclick="removeFromPO('${item.id}')"></i>
                    </div>
                </div>
            `);
        });

        $('#cartTotal').text('RM ' + total.toFixed(2));
        $('#checkoutBtn').prop('disabled', false);
    }

    // --- EVENTS ---
    $(document).off('click', '.order-add-btn').on('click', '.order-add-btn', function() {
        let id = $(this).data('id');
        let inputVal = $('#qty_input_' + id).val();
        window.addToPO(id, inputVal);
        $('#qty_input_' + id).val(10);
    });

    $('#checkoutBtn').on('click', function() {
        saveToStorage(); // Ensure saved before navigating
        $('#ajax-result').load('Payment.php'); 
    });

    // Run filter initially in case we restored state
    if(currentSupplierID) {
        filterProductGrid();
    }

})();
</script>
</div>