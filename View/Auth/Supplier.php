<?php
// Supplier.php - With Client-Side Pagination & Real-Time Search
require_once __DIR__ . "/../../Model/DB.php";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- 1. HANDLE FORM SUBMISSIONS (AJAX STYLE) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // A. ADD
    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        $name = mysqli_real_escape_string($conn, $_POST['supplier_name']);
        $email = mysqli_real_escape_string($conn, $_POST['supplier_email']);
        
        $checkQuery = "SELECT SupplierID FROM suppliers WHERE Email = '$email' AND IsActive = 'Active'";
        $checkResult = mysqli_query($conn, $checkQuery);
        
        if (mysqli_num_rows($checkResult) > 0) {
            echo "duplicate_email"; 
            exit(); 
        }

        $imagePath = "default.jpg"; 
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        $maxSize = 2 * 1024 * 1024; 

        if (!empty($_FILES['supplier_image']['name'])) {
            if ($_FILES['supplier_image']['size'] > $maxSize) { echo "error_size"; exit(); }
            $fileExt = strtolower(pathinfo($_FILES["supplier_image"]["name"], PATHINFO_EXTENSION));
            if (!in_array($fileExt, $allowedTypes)) { echo "error_type"; exit(); }
            $targetDir = "../../Assets/Image/Supplier/";
            $fileName = time() . "_" . basename($_FILES["supplier_image"]["name"]);
            if(move_uploaded_file($_FILES["supplier_image"]["tmp_name"], $targetDir . $fileName)){
                $imagePath = $fileName;
            } else { echo "Error: Upload failed."; exit(); }
        }
        $sql = "INSERT INTO suppliers (SupplierName, Email, ImagePath, IsActive) VALUES ('$name', '$email', '$imagePath', 'Active')";
        mysqli_query($conn, $sql);

        // Log Add
        $userID = $_SESSION['user']['id'] ?? 0;
        $stmtLog = $conn->prepare("INSERT INTO inventory_logs (LogsDetails, ProductID, UserID) VALUES (?, '2025DEF000', ?)");
        $logDetail = "Added Supplier: " . $name;
        $stmtLog->bind_param("si", $logDetail, $userID);
        $stmtLog->execute(); $stmtLog->close();

        echo "success"; exit(); 
    } 
    
    // B. EDIT
    elseif (isset($_POST['action']) && $_POST['action'] == 'edit') {
        $id = (int)$_POST['supplier_id'];
        $name = mysqli_real_escape_string($conn, $_POST['supplier_name']);
        $email = mysqli_real_escape_string($conn, $_POST['supplier_email']);
        
        $checkQuery = "SELECT SupplierID FROM suppliers WHERE Email = '$email' AND SupplierID != $id AND IsActive = 'Active'";
        $checkResult = mysqli_query($conn, $checkQuery);
        if (mysqli_num_rows($checkResult) > 0) { echo "duplicate_email"; exit(); }

        $imageUpdateSQL = "";
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        $maxSize = 2 * 1024 * 1024;

        if (!empty($_FILES['supplier_image']['name'])) {
             if ($_FILES['supplier_image']['size'] > $maxSize) { echo "error_size"; exit(); }
            $fileExt = strtolower(pathinfo($_FILES["supplier_image"]["name"], PATHINFO_EXTENSION));
            if (!in_array($fileExt, $allowedTypes)) { echo "error_type"; exit(); }
            $targetDir = "../../Assets/Image/Supplier/";
            $fileName = time() . "_" . basename($_FILES["supplier_image"]["name"]);
            if(move_uploaded_file($_FILES["supplier_image"]["tmp_name"], $targetDir . $fileName)){
                $imageUpdateSQL = ", ImagePath = '$fileName'";
            } else { echo "Error: Upload failed."; exit(); }
        }
        $sql = "UPDATE suppliers SET SupplierName='$name', Email='$email' $imageUpdateSQL WHERE SupplierID=$id";
        mysqli_query($conn, $sql);

        // Log Edit
        $userID = $_SESSION['user']['id'] ?? 0;
        $stmtLog = $conn->prepare("INSERT INTO inventory_logs (LogsDetails, ProductID, UserID) VALUES (?, '2025DEF000', ?)");
        $logDetail = "Updated Supplier: " . $name;
        $stmtLog->bind_param("si", $logDetail, $userID);
        $stmtLog->execute(); $stmtLog->close();

        echo "success"; exit();
    }

    // C. DELETE
    elseif (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $id = (int)$_POST['supplier_id'];
        $res = mysqli_query($conn, "SELECT SupplierName FROM suppliers WHERE SupplierID=$id");
        $row = mysqli_fetch_assoc($res);
        $supName = $row['SupplierName'] ?? 'Unknown';

        $sql = "UPDATE suppliers SET IsActive='Inactive' WHERE SupplierID=$id";
        mysqli_query($conn, $sql);

        // Log Delete
        $userID = $_SESSION['user']['id'] ?? 0;
        $stmtLog = $conn->prepare("INSERT INTO inventory_logs (LogsDetails, ProductID, UserID) VALUES (?, '2025DEF000', ?)");
        $logDetail = "Deleted Supplier: " . $supName;
        $stmtLog->bind_param("si", $logDetail, $userID);
        $stmtLog->execute(); $stmtLog->close();

        echo "success"; exit();
    }
}

// --- 2. FETCH DATA (LOAD ALL FOR CLIENT-SIDE PAGING) ---
$query = "SELECT * FROM suppliers WHERE IsActive = 'Active' ORDER BY SupplierID ASC";
$result = mysqli_query($conn, $query);
$totalSuppliers = mysqli_num_rows($result);
?>

<style>
    /* 1. Main Layout & Title */
    .supplier-container {
        background: transparent;
        width: 100%;
        height: 100%;
        font-family: 'Segoe UI', Arial, sans-serif;
        margin: 0;
        display: flex;
        flex-direction: column;
        padding-bottom: 20px;
    }

    .sup-header {
        display: flex;
        justify-content: center;
        align-items: center;
        position: relative;
        margin-bottom: 40px;
        flex-shrink: 0;
        height: 0%;
    }
    
    .btn-add-sup {
        position: absolute; right: 0; top: -5px; 
        background-color: #2ecc71; color: white;
        padding: 8px 15px; border-radius: 6px; border: none;
        font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 5px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: 0.3s; font-size: 14px;
    }
    .btn-add-sup:hover { background-color: #27ae60; }

    /* 2. Grid System */
    .sup-grid-wrapper {
        flex-grow: 1;
        overflow-y: auto; 
        padding: 5px;
    }

    .sup-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr); 
        gap: 20px; 
        width: 100%; 
        margin: 0 auto;
    }
    @media (max-width: 1000px) { .sup-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 600px) { .sup-grid { grid-template-columns: 1fr; } }

    /* 3. Card */
    .sup-card {
        background: var(--card-white);
        border-radius: 10px; padding: 25px 20px; 
        display: flex; flex-direction: column; align-items: center; text-align: center;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
        transition: transform 0.2s, border 0.2s; border: 1px solid transparent;
    }
    .sup-card:hover { transform: translateY(-2px); border: 1px solid #3498db; }

    .sup-img-box { width: 40px; height: 40px; margin-bottom: 10px; display: flex; justify-content: center; align-items: center; border: 1px solid var(--border); border-radius: 4px; }
    .sup-img { max-width: 90%; max-height: 90%; object-fit: contain; }
    .sup-name { font-size: 16px; font-weight: 700; margin: 5px 0 3px 0; color: var(--text-dark); }
    .sup-email { font-size: 12px; color: var(--text-grey); margin-bottom: 20px; }

    /* 4. Action Buttons */
    .sup-actions { display: flex; gap: 8px; width: 100%; max-width: 200px; justify-content: center; }
    .btn-card { padding: 8px 0; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 13px; flex: 1; min-width: 80px; }
    .btn-view { background-color: #3498db; color: white; border: 1px solid #3498db; }
    .btn-view:hover { background-color: #2980b9; border-color: #2980b9; }
    .btn-edit { background-color: var(--card-white); border: 1px solid var(--border); color: var(--text-dark); }
    .btn-edit:hover { background-color: var(--bg-light); border-color: #95a5a6; }

    /* 5. Pagination Buttons (NEW) */
    .js-pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 5px;
        margin-top: 20px;
        padding-bottom: 20px;
    }
    .js-page-btn {
        padding: 8px 12px;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        border: 1px solid var(--border);
        background: var(--bg-light);
        color: #555;
    }
    .js-page-btn:hover { background: #dcdcdc; }
    .js-page-btn.active {
        background: #95A5A6;
        color: white;
        border-color: #95A5A6;
    }
    .js-page-btn.disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Shared Modal styles */
    .sup-modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); justify-content: center; align-items: center; overflow-y: auto; }
    #supModalContent { background-color: var(--card-white); padding: 25px; border-radius: 8px; width: 350px; position: relative; }

    /* Search Bar Design */
    .supplier-search-wrapper { position: relative; width: 100%; max-width: 400px; margin: 0 20px; }
    .sup-search-input { width: 100%; padding: 10px 20px 10px 40px; font-size: 14px; border: 2px solid var(--border); border-radius: 50px; background: var(--bg-light); color: var(--text-dark); outline: none; transition: all 0.3s ease; }
    .sup-search-input:focus { border-color: #3498db; box-shadow: 0 4px 10px rgba(52, 152, 219, 0.2); background: white; }
    .search-icon-overlay { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #95a5a6; font-size: 14px; pointer-events: none; }

    /* Detail Modal Specific */
    #detailModalContent { background-color: var(--card-white); padding: 25px; border-radius: 8px; width: 700px; max-width: 90%; position: relative; }
    .close-modal { position: absolute; top: 10px; right: 15px; font-size: 20px; cursor: pointer; color: #888; }
    .close-modal:hover { color: #333; }
    
    /* Form styles */
    .form-group { margin-bottom: 10px; text-align: left; }
    .form-group label { display: block; font-weight: bold; margin-bottom: 3px; font-size: 13px; color: var(--text-dark); }
    .form-group input { width: 100%; padding: 8px; border: 1px solid var(--border); border-radius: 3px; box-sizing: border-box; font-size: 13px; background: var(--bg-light); color: var(--text-dark); }
    .btn-save { width: 100%; padding: 10px; background: #2ecc71; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
    .btn-delete { width: 100%; padding: 10px; background: #e74c3c; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; margin-top: 10px; }
</style>

<div class="supplier-container">
    
    <div class="sup-header">
        <div class="supplier-search-wrapper">
            <i class="fa-solid fa-magnifying-glass search-icon-overlay"></i>
            <input type="text" id="searchSupplierInput" class="sup-search-input" placeholder="Search Supplier Name...">        
        </div>
        <button class="btn-add-sup" onclick="openSupModal('add')">
            <span>+</span> Add Supplier
        </button>
    </div>

    <div class="sup-grid-wrapper">
        <div class="sup-grid">
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <div class="sup-card">
                    <div class="sup-img-box">
                        <img src="../../Assets/Image/Supplier/<?php echo $row['ImagePath']; ?>" class="sup-img" onerror="this.src='../../Assets/Image/Supplier/default.jpg'">
                    </div>
                    <h3 class="sup-name"><?php echo $row['SupplierName']; ?></h3>
                    <p class="sup-email"><?php echo $row['Email']; ?></p>
                    
                    <div class="sup-actions">
                        <button class="btn-card btn-view" onclick="viewDetails(<?php echo $row['SupplierID']; ?>)">
                            View Details
                        </button>
                        <button class="btn-card btn-edit" onclick="openSupModal('edit', 
                            '<?php echo $row['SupplierID']; ?>', 
                            '<?php echo addslashes($row['SupplierName']); ?>', 
                            '<?php echo addslashes($row['Email']); ?>'
                        )">
                            Edit
                        </button>
                    </div>
                </div>
            <?php } ?>
        </div>

        <?php if ($totalSuppliers == 0): ?>
            <div style="text-align:center; color:#888; width:100%; margin-top:20px;">
                No suppliers found.
            </div>
        <?php endif; ?>

        <div id="paginationContainer" class="js-pagination"></div>
    </div>

</div>

<div id="supModal" class="sup-modal">
    <div id="supModalContent">
        <span class="close-modal" onclick="closeSupModal()">&times;</span>
        <h2 id="modalTitle" style="text-align:center;">Add Supplier</h2>
        
        <form id="supplierAjaxForm" enctype="multipart/form-data">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="supplier_id" id="editId">
            <div class="form-group"><label>Supplier Name</label><input type="text" name="supplier_name" id="inputName" required></div>
            <div class="form-group"><label>Contact Email</label><input type="email" name="supplier_email" id="inputEmail" required></div>
            <div class="form-group"><label>Company Image</label><input type="file" name="supplier_image" accept="image/*"></div>
            <button type="submit" class="btn-save">Save</button>
            <button type="button" id="btnDelete" class="btn-delete" style="display:none;" onclick="deleteSup()">Delete Supplier</button>
        </form>
    </div>
</div>

<div id="detailModal" class="sup-modal">
    <div id="detailModalContent">
        <span class="close-modal" onclick="closeDetailModal()">&times;</span> 
    </div>
</div>

<script>
// Wrap everything in this function to prevent "Already Declared" errors
(function() {
    // --- 1. VARIABLES  ---
    var currentPage = 1;
    var itemsPerPage = 8; 

    // --- 2. MAIN RENDER FUNCTION ---
    function renderSupplierGrid() {
        var searchTerm = $('#searchSupplierInput').val().toLowerCase();
        var allCards = $('.sup-card');
        var visibleCards = [];

        // A. Filter Matching Cards
        allCards.each(function() {
            var name = $(this).find('.sup-name').text().toLowerCase();
            if (name.includes(searchTerm)) {
                visibleCards.push($(this));
            } else {
                $(this).hide();
            }
        });

        // B. Calculate Pagination
        var totalItems = visibleCards.length;
        var totalPages = Math.ceil(totalItems / itemsPerPage) || 1;
        
        if (currentPage > totalPages) currentPage = 1;

        var startIndex = (currentPage - 1) * itemsPerPage;
        var endIndex = startIndex + itemsPerPage;

        // C. Show only current page slice
        visibleCards.forEach(function(card, index) {
            if (index >= startIndex && index < endIndex) {
                card.show();
                card.css('display', 'flex'); 
            } else {
                card.hide();
            }
        });

        // D. Render Buttons
        renderPaginationControls(totalPages);
    }

    // --- 3. PAGINATION CONTROLS ---
    function renderPaginationControls(totalPages) {
        var container = $('#paginationContainer');
        container.empty();

        if (totalPages <= 1) return; 

        // Prev Button
        var prevClass = currentPage === 1 ? 'disabled' : '';
        // Note: We call window.supplierChangePage (global) instead of local function
        container.append('<button class="js-page-btn ' + prevClass + '" onclick="supplierChangePage(' + (currentPage - 1) + ')">Prev</button>');

        // Number Buttons
        for (var i = 1; i <= totalPages; i++) {
            var activeClass = i === currentPage ? 'active' : '';
            container.append('<button class="js-page-btn ' + activeClass + '" onclick="supplierChangePage(' + i + ')">' + i + '</button>');
        }

        // Next Button
        var nextClass = currentPage === totalPages ? 'disabled' : '';
        container.append('<button class="js-page-btn ' + nextClass + '" onclick="supplierChangePage(' + (currentPage + 1) + ')">Next</button>');
    }

    // --- 4. EXPOSE PAGE CHANGE FUNCTION GLOBALLY ---
    window.supplierChangePage = function(pageNum) {
        if (pageNum < 1) return;
        currentPage = pageNum;
        renderSupplierGrid();
    };

    // --- 5. EVENT LISTENERS ---
    // Unbind previous events first to prevent duplicates
    $('#searchSupplierInput').off('keyup').on('keyup', function() {
        currentPage = 1; 
        renderSupplierGrid();
    });

    // --- 6. INITIALIZE ---
    renderSupplierGrid();


    // ============================================
    //      MODAL & AJAX LOGIC (Keep as is)
    // ============================================

    window.openSupModal = function(mode, id, name, email) {
        // Set default values if undefined
        id = id || ''; name = name || ''; email = email || '';

        $('#supModal').css('display', 'flex');
        
        if (mode === 'add') {
            $('#modalTitle').text("Add Supplier");
            $('#formAction').val("add");
            $('#inputName').val("");
            $('#inputEmail').val("");
            $('#editId').val("");
            $('#btnDelete').hide();
        } else {
            $('#modalTitle').text("Edit Supplier");
            $('#formAction').val("edit");
            $('#editId').val(id);
            $('#inputName').val(name);
            $('#inputEmail').val(email);
            $('#btnDelete').show();
        }
    };

    window.closeSupModal = function() { 
        $('#supModal').hide(); 
    };

    $('#supplierAjaxForm').off('submit').on('submit', function(e) {
        e.preventDefault(); 
        var formData = new FormData(this);
        $.ajax({
            url: 'Supplier.php',
            type: 'POST',
            data: formData,
            contentType: false, processData: false,
            success: function(response) {
                var res = response.trim();
                if (res === 'duplicate_email') alert('Error: Email already exists.');
                else if (res === 'error_size') alert('Error: File too large (Max 2MB).');
                else if (res === 'error_type') alert('Error: Invalid file type.');
                else if (res === 'success') {
                    closeSupModal();
                    // This reload triggers the script again, but now it's safe!
                    $('#ajax-result').load('Supplier.php'); 
                } 
                else alert('System Error: ' + res);
            },
            error: function() { alert('Unexpected error.'); }
        });
    });

    window.deleteSup = function() {
        if(confirm("Are you sure you want to delete this supplier?")) {
            $('#formAction').val("delete");
            $('#supplierAjaxForm').submit(); 
        }
    };
    
    window.viewDetails = function(id) {
        var modal = $('#detailModal');
        $('#detailModalContent').append('<div id="loading-temp" style="text-align:center; padding: 50px;">Loading...</div>');
        modal.css('display', 'flex'); 

        $.ajax({
            url: 'supplier_details.php?id=' + id,
            type: 'GET',
            success: function(data) {
                $('#loading-temp').remove();
                $('#detailModalContent').append(data); 
            },
            error: function() {
                $('#loading-temp').remove();
                $('#detailModalContent').append('<div style="color:red; text-align:center; padding:50px;">Error loading details.</div>');
            }
        });
    };

    window.closeDetailModal = function() {
        $('#detailModal').hide();
        $('#detailModalContent').contents().not('.close-modal').remove();
    };

})();
</script>