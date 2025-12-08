<?php
require_once __DIR__ . "/../../Model/DB.php";

// --- 1. HANDLE FORM SUBMISSIONS (AJAX STYLE) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // A. ADD
    if (isset($_POST['action']) && $_POST['action'] == 'add') {
        $name = mysqli_real_escape_string($conn, $_POST['supplier_name']);
        $email = mysqli_real_escape_string($conn, $_POST['supplier_email']);
        
        //Check duplicate email of company
        $checkQuery = "SELECT SupplierID FROM suppliers WHERE Email = '$email' AND IsActive = 'Active'";
        $checkResult = mysqli_query($conn, $checkQuery);
        
        if (mysqli_num_rows($checkResult) > 0) {
            echo "duplicate_email"; // Signal a duplication error
            exit(); 
        }

        $imagePath = "default.jpg"; 

        if (!empty($_FILES['supplier_image']['name'])) {
            $targetDir = "../../Assets/Image/Supplier/";
            $fileName = time() . "_" . basename($_FILES["supplier_image"]["name"]);
            if(move_uploaded_file($_FILES["supplier_image"]["tmp_name"], $targetDir . $fileName)){
                $imagePath = $fileName;
            }
        }
        $sql = "INSERT INTO suppliers (SupplierName, Email, ImagePath, IsActive) VALUES ('$name', '$email', '$imagePath', 'Active')";
        mysqli_query($conn, $sql);
        echo "success"; 
        exit(); 
    } 
    
    // B. EDIT
    elseif (isset($_POST['action']) && $_POST['action'] == 'edit') {
        $id = (int)$_POST['supplier_id'];
        $name = mysqli_real_escape_string($conn, $_POST['supplier_name']);
        $email = mysqli_real_escape_string($conn, $_POST['supplier_email']);
        
        //Check duplicate email of the company for EDITING
        $checkQuery = "SELECT SupplierID FROM suppliers WHERE Email = '$email' AND SupplierID != $id AND IsActive = 'Active'";
        $checkResult = mysqli_query($conn, $checkQuery);
        
        if (mysqli_num_rows($checkResult) > 0) {
            echo "duplicate_email"; // Signal a duplication error
            exit(); 
        }

        $imageUpdateSQL = "";
        if (!empty($_FILES['supplier_image']['name'])) {
            $targetDir = "../../Assets/Image/Supplier/";
            $fileName = time() . "_" . basename($_FILES["supplier_image"]["name"]);
            if(move_uploaded_file($_FILES["supplier_image"]["tmp_name"], $targetDir . $fileName)){
                $imageUpdateSQL = ", ImagePath = '$fileName'";
            }
        }
        $sql = "UPDATE suppliers SET SupplierName='$name', Email='$email' $imageUpdateSQL WHERE SupplierID=$id";
        mysqli_query($conn, $sql);
        echo "success";
        exit();
    }

    // C. DELETE
    elseif (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $id = (int)$_POST['supplier_id'];
        $sql = "UPDATE suppliers SET IsActive='Inactive' WHERE SupplierID=$id";
        mysqli_query($conn, $sql);
        echo "success";
        exit();
    }
}

// --- 2. PAGINATION LOGIC ---
// Set limit to 6 suppliers per page (2 rows of 3)
$limit = 6; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Count
$countQuery = "SELECT COUNT(*) AS total FROM suppliers WHERE IsActive = 'Active'";
$countResult = mysqli_query($conn, $countQuery);
$totalSuppliers = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalSuppliers / $limit);

// Fetch
$query = "SELECT * FROM suppliers WHERE IsActive = 'Active' ORDER BY SupplierID ASC LIMIT $start, $limit";
$result = mysqli_query($conn, $query);
?>

<style>
    /* 1. Main Layout & Title */
    .supplier-container {
        padding: 40px 60px;
        background: #F8F5EE; 
        height: 100%;
        font-family: 'Segoe UI', Arial, sans-serif;
        max-width: 100%;
        margin: 0;
    }

    .sup-header {
        display: flex;
        justify-content: center;
        align-items: center;
        position: relative;
        margin-bottom: 40px;
    }
    .sup-title {
        font-size: 36px;
        font-weight: 900;
        text-decoration: underline;
        color: #000;
        margin: 0 auto; 
    }
    
    /* Green Add Button */
    .btn-add-sup {
        position: absolute;
        right: 0;
        top: -5px; 
        background-color: #2ecc71; 
        color: white;
        padding: 8px 15px; 
        border-radius: 6px; 
        border: none;
        font-weight: bold;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        transition: 0.3s;
        font-size: 14px;
    }
    .btn-add-sup:hover { background-color: #27ae60; }

    /* 2. Grid System - 3 Columns (New Requirement) */
    .sup-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr); 
        gap: 20px; 
        max-width: 1000px; 
        margin: 0 auto;
    }
    /* Responsive adjustments */
    @media (max-width: 1000px) { .sup-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 600px) { .sup-grid { grid-template-columns: 1fr; } }

    /* 3. Card */
    .sup-card {
        background: white;
        border-radius: 10px; 
        padding: 25px 20px; 
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
        transition: transform 0.2s, border 0.2s;
        border: 1px solid transparent;
    }
    .sup-card:hover { 
        transform: translateY(-2px); 
        border: 1px solid #3498db; 
    }

    .sup-img-box {
        width: 40px; 
        height: 40px; 
        margin-bottom: 10px; 
        display: flex;
        justify-content: center;
        align-items: center;
        border: 1px solid #ccc;
        border-radius: 4px;
    }
    .sup-img {
        max-width: 90%;
        max-height: 90%;
        object-fit: contain;
    }
    .sup-name { 
        font-size: 16px; 
        font-weight: 700; 
        margin: 5px 0 3px 0; 
        color: #2c3e50; 
    }
    .sup-email { 
        font-size: 12px; 
        color: #7f8c8d; 
        margin-bottom: 20px; 
    }

    /* 4. Action Buttons */
    .sup-actions { display: flex; gap: 8px; width: 100%; max-width: 200px; justify-content: center; }
    .btn-card {
        padding: 8px 0; 
        border-radius: 6px; 
        font-weight: 600;
        cursor: pointer;
        font-size: 13px;
        flex: 1; 
        min-width: 80px;
    }

    .btn-view { 
        background-color: #3498db; 
        color: white; 
        border: 1px solid #3498db; 
    }
    .btn-view:hover { background-color: #2980b9; border-color: #2980b9; }
    
    .btn-edit { 
        background-color: white; 
        border: 1px solid #bdc3c7; 
        color: #2c3e50; 
    }
    .btn-edit:hover { background-color: #f5f5f5; border-color: #95a5a6; }

    /* 5. Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 5px; 
        margin-top: 50px; 
    }
    .pagination a, .pagination .current-page {
        padding: 8px 12px; 
        border-radius: 4px; 
        font-size: 14px; 
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
    }

    .pagination a {
        background: #CCCCCC; 
        color: #555;
        border: none;
    }
    .pagination a:hover { background: #B3B3B3; }
    
    .pagination .current-page {
        background: #95A5A6; 
        color: white;
    }

    /* Shared Modal styles */
    .sup-modal { 
        display: none; 
        position: fixed; 
        z-index: 9999; 
        left: 0; 
        top: 0; 
        width: 100%; 
        height: 100%; 
        background-color: rgba(0,0,0,0.5); 
        justify-content: center; 
        align-items: center; 
        overflow-y: auto; 
    }
    
    /* Add/Edit Modal Specific Style */
    #supModalContent {
        background-color: white; 
        padding: 25px; 
        border-radius: 8px; 
        width: 350px; 
        position: relative;
    }

    /* Detail Modal Specific Style (Wider) */
    #detailModalContent {
        background-color: white; 
        padding: 25px; 
        border-radius: 8px; 
        width: 700px; 
        max-width: 90%;
        position: relative;
    }

    .close-modal { 
        position: absolute; 
        top: 10px; 
        right: 15px; 
        font-size: 20px; 
        cursor: pointer; 
        color: #888;
    }
    .close-modal:hover { color: #333; }
    
    /* Form styles */
    .form-group { margin-bottom: 10px; text-align: left; }
    .form-group label { display: block; font-weight: bold; margin-bottom: 3px; font-size: 13px; }
    .form-group input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 3px; box-sizing: border-box; font-size: 13px; }
    .btn-save { width: 100%; padding: 10px; background: #2ecc71; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
    .btn-delete { width: 100%; padding: 10px; background: #e74c3c; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; margin-top: 10px; }

</style>

<div class="supplier-container">
    
    <div class="sup-header">
        <h1 class="sup-title">Supplier List</h1>
        <button class="btn-add-sup" onclick="openSupModal('add')">
            <span>+</span> Add Supplier
        </button>
    </div>

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

    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="Supplier.php?page=<?php echo $page-1; ?>">Prev</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i == $page): ?>
                <span class="current-page"><?php echo $i; ?></span>
            <?php else: ?>
                <a href="Supplier.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="Supplier.php?page=<?php echo $page+1; ?>">Next</a>
        <?php endif; ?>
    </div>

</div>

<div id="supModal" class="sup-modal">
    <div id="supModalContent">
        <span class="close-modal" onclick="closeSupModal()">&times;</span>
        <h2 id="modalTitle" style="text-align:center;">Add Supplier</h2>
        
        <form id="supplierAjaxForm" enctype="multipart/form-data">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="supplier_id" id="editId">

            <div class="form-group">
                <label>Supplier Name</label>
                <input type="text" name="supplier_name" id="inputName" required>
            </div>
            
            <div class="form-group">
                <label>Contact Email</label>
                <input type="email" name="supplier_email" id="inputEmail" required>
            </div>

            <div class="form-group">
                <label>Company Image</label>
                <input type="file" name="supplier_image" accept="image/*">
            </div>

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
    // --- 1. PAGINATION FIX (AJAX Interception) ---
    $(document).ready(function() {
        // Intercepts clicks on pagination links inside the 'pagination' class
        $('.pagination a').click(function(e) {
            e.preventDefault(); // Prevents full page reload
            let url = $(this).attr('href'); 
            // Load the new page content into the dashboard's main content area (#ajax-result)
            $('#ajax-result').load(url); 
        });
    });

    // --- 2. MODAL FUNCTIONS (Add/Edit) ---
    function openSupModal(mode, id = '', name = '', email = '') {
        document.getElementById('supModal').style.display = 'flex';
        
        if (mode === 'add') {
            document.getElementById('modalTitle').innerText = "Add Supplier";
            document.getElementById('formAction').value = "add";
            document.getElementById('inputName').value = "";
            document.getElementById('inputEmail').value = "";
            document.getElementById('editId').value = "";
            document.getElementById('btnDelete').style.display = "none";
        } else {
            document.getElementById('modalTitle').innerText = "Edit Supplier";
            document.getElementById('formAction').value = "edit";
            document.getElementById('editId').value = id;
            document.getElementById('inputName').value = name;
            document.getElementById('inputEmail').value = email;
            document.getElementById('btnDelete').style.display = "block";
        }
    }

    function closeSupModal() {
        document.getElementById('supModal').style.display = 'none';
    }

    // --- 3. AJAX FORM SUBMIT (Add/Edit) ---
    $('#supplierAjaxForm').on('submit', function(e) {
        e.preventDefault(); 
        var formData = new FormData(this);
        $.ajax({
            url: 'Supplier.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                // Check if the response signals a duplicate email
                if (response.trim() === 'duplicate_email') {
                    alert('Error: This email address is already in use by another active supplier.');
                } else {
                    // Success or other response
                    closeSupModal();
                    // Reload the current view to show the updated data
                    $('#ajax-result').load('Supplier.php'); 
                }
            },
            error: function() {
                alert('An unexpected error occurred during the submission.');
            }
        });
    });

    // --- 4. AJAX DELETE ---
    function deleteSup() {
        if(confirm("Are you sure you want to delete this supplier?")) {
            document.getElementById('formAction').value = "delete";
            $('#supplierAjaxForm').submit(); 
        }
    }
    
    // --- 5. VIEW DETAILS FUNCTIONALITY ---
    function viewDetails(id) {
        var modal = document.getElementById('detailModal');
        var contentBox = document.getElementById('detailModalContent');
        
        // Show loading state and open modal
        // Note: We leave the close button span intact by loading only the dynamic content
        $('#detailModalContent').append('<div id="loading-temp" style="text-align:center; padding: 50px;">Loading Supplier Details...</div>');
        modal.style.display = 'flex'; 

        // Load supplier_details.php content using AJAX
        $.ajax({
            url: 'supplier_details.php?id=' + id,
            type: 'GET',
            success: function(data) {
                // Clear the temporary loading message and prepend the new content
                $('#loading-temp').remove();
                // We append to the modal content, ensuring the close button is still present as it's outside the scope of the AJAX load, but inside #detailModalContent
                $('#detailModalContent').append(data); 
            },
            error: function() {
                // Display error message
                $('#loading-temp').remove();
                $('#detailModalContent').append('<div id="loading-temp" style="color: red; text-align:center; padding: 50px;">Error loading details. The requested content could not be found or loaded.</div>');
            }
        });
    }

    // Function to close the Detail Modal
    function closeDetailModal() {
        document.getElementById('detailModal').style.display = 'none';
        // Clear all dynamically loaded content, keeping only the close button span
        $('#detailModalContent').contents().not('.close-modal').remove();
    }
</script>