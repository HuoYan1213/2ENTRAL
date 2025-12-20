<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== "Manager") {
    header('Location: ../Public/AccessDenied.php');
    exit();
}

require_once __DIR__ . "/../../Controller/UserController.php";
$controller = new UserController();

// Pagination & Filtering Logic
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$filters = [
    'search' => $_GET['search'] ?? '',
    'role' => $_GET['role'] ?? '',
    'status' => $_GET['status'] ?? ''
];

$data = $controller->getUsersPaginated($page, 5, $filters); // Limit 5 per page
$users = $data['users'];
$total_pages = $data['total_pages'];
$current_page = $data['current_page'];

function buildUrl($newPage, $currentFilters) {
    $params = array_merge($currentFilters, ['page' => $newPage]);
    return '?' . http_build_query(array_filter($params, function($v) { return $v !== ''; })); 
}

$latestImagePath = $controller->getUserAvatar($_SESSION['user']['id']);
if ($latestImagePath !== ($_SESSION['user']['image'] ?? '')) {
    $_SESSION['user']['image'] = $latestImagePath;
}
?>

<link rel="stylesheet" href="../../Assets/CSS/usersroles.css">
<title>Users & Roles</title>
<!-- Include Phosphor Icons -->
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<style>
    /* Choices.js Overrides for UsersRoles */
    .choices { flex-grow: 1; margin-bottom: 0; font-size: 0.9rem; }
    .choices__inner {
        min-height: auto; padding: 0 !important; border: none !important;
        background-color: transparent !important; color: var(--text-dark);
    }
    .choices__list--dropdown {
        background-color: var(--card-white); border: 1px solid var(--border);
        color: var(--text-dark); margin-top: 10px; border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.15); z-index: 50;
    }
    .choices__item--choice.is-highlighted { background-color: var(--bg-light); }
    
    /* Fix for filter group layout with Choices */
    .filter-group {
        display: flex; align-items: center; gap: 10px;
        background: var(--card-white); border: 1px solid var(--border);
        padding: 8px 12px; border-radius: 8px; min-width: 180px;
    }
    .choices__input { background-color: transparent !important; }
    /* Dark mode text fix */
    body.dark-mode .choices__input { color: #fff !important; }
    body.dark-mode .choices__list--dropdown { background-color: #1E1E1E; border-color: #333; }
</style>

<div class="users-roles-wrapper">
    <div class="toast-container" id="toastContainer"></div>


    <!-- Page Intro -->
    <div class="page-intro">
        <button class="btn-export" onclick="generateUserPDF()">
            <i class="ph-bold ph-file-pdf"></i> Export PDF
        </button>
    </div>

    <!-- Toolbar -->
    <div class="toolbar">
        <div class="search-group">
            <i class="ph ph-magnifying-glass" style="color: #999;"></i>
            <input type="text" class="search-input" id="search-input" placeholder="Search name, email..." value="<?php echo htmlspecialchars($filters['search']); ?>">
        </div>

        <div class="filter-group">
            <i class="ph ph-funnel" style="color: #666;"></i>
            <select class="filter-select" id="role-filter">
                <option value="">All Roles</option>
                <option value="Manager" <?php echo $filters['role'] === 'Manager' ? 'selected' : ''; ?>>Manager</option>
                <option value="Employee" <?php echo $filters['role'] === 'Employee' ? 'selected' : ''; ?>>Employee</option>
            </select>
        </div>

        <div class="filter-group">
            <i class="ph ph-toggle-left" style="color: #666;"></i>
            <select class="filter-select" id="status-filter">
                <option value="">All Status</option>
                <option value="Active" <?php echo $filters['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                <option value="Inactive" <?php echo $filters['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
            </select>
        </div>

        <button class="btn-add-user" id="addUserBtn">
            <i class="ph-bold ph-plus"></i> Add New User
        </button>
    </div>

    <!-- Table Card -->
    <div class="table-card">
        <div class="table-scroll">
        <table id="users-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Joined Date</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): ?>
                    <?php 
                    $currentUserID = $_SESSION['user']['id']; 
                    $currentUserRole = $_SESSION['user']['role'];
                    
                    foreach ($users as $user): 
                        $userStatus = ($user['IsActive'] ?? 'Active') === 'Active' ? 'Active' : 'Inactive';
                        $isTargetManager = $user['Role'] === 'Manager';
                        $isEditingSelf = $user['UserID'] == $currentUserID;
                        $disableRoleStatus = $isTargetManager; 
                        $roleBadgeClass = ($user['Role'] === 'Manager') ? 'role-admin' : 'role-staff';
                        $roleIcon = ($user['Role'] === 'Manager') ? '<i class="ph-fill ph-crown"></i>' : '';
                        
                        // Determine if actions are allowed
                        $canEdit = !$isTargetManager; // Managers cannot be edited from this panel
                        $canToggleStatus = !$isTargetManager;
                    ?>
                    <tr data-userid="<?php echo $user['UserID']; ?>" 
                        data-original-role="<?php echo htmlspecialchars($user['Role'] ?? ''); ?>"
                        data-original-status="<?php echo htmlspecialchars($userStatus); ?>">
                        
                        <td>
                            <div class="user-info-cell">
                                <div class="user-avatar-lg">
                                    <?php 
                                    $imagePath = $user['ImagePath'] ?? '';
                                    $hasValidImage = !empty($imagePath) && strpos($imagePath, 'default_user_') === false;
                                    ?>
                                    
                                    <?php if ($hasValidImage): ?>
                                        <?php 
                                        $localImagePath = __DIR__ . '/../../Assets/Image/User/' . $imagePath;
                                        $webImagePath = '../../Assets/Image/User/' . $imagePath;
                                        $imageExists = file_exists($localImagePath);
                                        ?>
                                        <?php if ($imageExists): ?>
                                            <img src="<?php echo $webImagePath; ?>" 
                                                 alt="Avatar"
                                                 onerror="this.style.display='none'; this.parentNode.innerHTML='<?php echo strtoupper(substr($user['UserName'], 0, 2)); ?>';">
                                        <?php else: ?>
                                            <?php echo strtoupper(substr($user['UserName'], 0, 2)); ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($user['UserName'], 0, 2)); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="user-detail">
                                    <h4><?php echo htmlspecialchars($user['UserName'] ?? ''); ?></h4>
                                    <p><?php echo htmlspecialchars($user['Email'] ?? ''); ?></p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="role-badge <?php echo $roleBadgeClass; ?>">
                                <?php echo $roleIcon; ?> <?php echo htmlspecialchars($user['Role']); ?>
                            </span>
                        </td>
                        <td>
                            <label class="switch">
                                <input type="checkbox" class="status-toggle" 
                                    data-userid="<?php echo $user['UserID']; ?>" 
                                    data-username="<?php echo htmlspecialchars($user['UserName']); ?>"
                                    <?php echo $userStatus === 'Active' ? 'checked' : ''; ?>
                                    <?php echo !$canToggleStatus ? 'disabled' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </td>
                        <td>
                            <?php 
                            $createdAt = $user['CreatedAt'] ?? '';
                            if (!empty($createdAt)) {
                                echo date('M j, Y', strtotime($createdAt));
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </td>
                        <td style="text-align: right;">
                            <?php if ($canEdit): ?>
                            <button class="action-btn edit-user" title="Edit" data-userid="<?php echo $user['UserID']; ?>">
                                <i class="ph-bold ph-pencil-simple"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 20px;">No users found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
        
        <?php if (empty($users)): ?>
        <div class="no-results" id="no-results" style="display: block;">
            <i class="ph ph-magnifying-glass" style="font-size: 48px; margin-bottom: 10px;"></i>
            <p>No users found matching your criteria.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination-container">
        <?php if ($current_page > 1): ?>
            <a href="<?php echo buildUrl(1, $filters); ?>" class="page-btn" title="First Page"><i class="ph-bold ph-caret-double-left"></i></a>
            <a href="<?php echo buildUrl($current_page - 1, $filters); ?>" class="page-btn" title="Previous"><i class="ph-bold ph-caret-left"></i></a>
        <?php else: ?>
            <span class="page-btn disabled"><i class="ph-bold ph-caret-double-left"></i></span>
            <span class="page-btn disabled"><i class="ph-bold ph-caret-left"></i></span>
        <?php endif; ?>

        <span class="page-info">
            Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>
        </span>

        <?php if ($current_page < $total_pages): ?>
            <a href="<?php echo buildUrl($current_page + 1, $filters); ?>" class="page-btn" title="Next"><i class="ph-bold ph-caret-right"></i></a>
            <a href="<?php echo buildUrl($total_pages, $filters); ?>" class="page-btn" title="Last Page"><i class="ph-bold ph-caret-double-right"></i></a>
        <?php else: ?>
            <span class="page-btn disabled"><i class="ph-bold ph-caret-right"></i></span>
            <span class="page-btn disabled"><i class="ph-bold ph-caret-double-right"></i></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit User</h3>
                <span class="close" id="closeEditModal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editUserForm" enctype="multipart/form-data">
                    <input type="hidden" id="editUserID" name="userID">
                    <input type="hidden" id="currentImagePath" name="currentImagePath">
                    
                    <div class="avatar-upload">
                        <div class="avatar-preview-container" id="avatarPreviewContainer">
                            <div class="avatar-preview-large" id="avatarPreview">
                                <div class="default-avatar">
                                    <i class="ph-fill ph-user"></i>
                                </div>
                            </div>
                        </div>
                        <input type="file" id="avatarInput" class="avatar-upload-input" name="avatar" accept="image/*">
                        <label for="avatarInput" class="avatar-upload-label">
                            <i class="ph-bold ph-upload-simple"></i> Choose Profile Image
                        </label>
                        <div class="file-info" id="fileInfo">No file chosen</div>
                        
                        <div class="form-group" style="margin-top: 15px; display: flex; align-items: center; justify-content: center;">
                            <input type="checkbox" id="deleteAvatar" name="deleteAvatar" value="1" style="width: auto; margin-right: 5px;">
                            <label for="deleteAvatar" style="font-weight: normal; margin-bottom: 0;">Delete Current Avatar</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="editUserName">Name:</label>
                        <input type="text" id="editUserName" name="userName" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editUserEmail">Email:</label>
                        <input type="email" id="editUserEmail" name="email" required>
                    </div>
    
                    <div class="form-group">
                        <label for="editUserRole">Role:</label>
                        <select id="editUserRole" name="role" required>
                            <option value="Employee">Employee</option>
                            <option value="Manager">Manager</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="editUserStatus">Status:</label>
                        <select id="editUserStatus" name="isActive" required>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-save">Save Changes</button>
                        <button type="button" class="btn-cancel" id="cancelEdit">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <div id="addUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add New User</h3>
            <span class="close" id="closeAddModal">&times;</span>
        </div>
        <div class="modal-body">
            <form id="addUserForm" enctype="multipart/form-data">
                <div class="avatar-upload">
                    <div class="avatar-preview-container" id="addAvatarPreviewContainer">
                        <div class="avatar-preview-large" id="addAvatarPreview">
                            <div class="default-avatar">
                                <i class="ph-fill ph-user"></i>
                            </div>
                        </div>
                    </div>
                    <input type="file" id="addAvatarInput" class="avatar-upload-input" name="avatar" accept="image/*">
                    
                    <div style="display: flex; gap: 10px; justify-content: center; align-items: center;">
                        <label for="addAvatarInput" class="avatar-upload-label">
                            <i class="ph-bold ph-upload-simple"></i> Choose Profile Image
                        </label>
                        <button type="button" class="action-btn btn-cancel" id="cancelAddAvatar" 
                            style="margin: 0; height: 36px; box-sizing: border-box; display: none;"
                            title="Cancel Upload">
                            <i class="ph-bold ph-x"></i> Cancel
                        </button>
                    </div>
                    <div class="file-info" id="addFileInfo">No file chosen</div>
                </div>
                
                <div class="form-group">
                    <label for="addUserName">Name:</label>
                    <input type="text" id="addUserName" name="userName" required>
                </div>
                
                <div class="form-group">
                    <label for="addUserEmail">Email:</label>
                    <input type="email" id="addUserEmail" name="email" required>
                </div>

                <div class="form-group">
                    <label for="addUserRole">Role:</label>
                    <select id="addUserRole" name="role" required>
                        <option value="Employee">Employee</option>
                        <option value="Manager">Manager</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="addUserStatus">Status:</label>
                    <select id="addUserStatus" name="isActive" required>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-save">Add User</button>
                    <button type="button" class="btn-cancel" id="cancelAdd">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

    <div class="modal-overlay" id="confirmationModal">
        <div class="modal-box">
            <div class="modal-icon-large" id="confirmModalIconContainer">
                <i class="ph-fill ph-question" id="confirmModalIcon"></i>
            </div>
            <h3 class="modal-title" id="confirmModalTitle">Confirm Action</h3>
            <p class="modal-desc" id="confirmModalDesc">Are you sure?</p>
            
            <div class="modal-actions">
                <button class="modal-btn btn-cancel" onclick="closeConfirmModal()">Cancel</button>
                <button class="modal-btn btn-confirm" id="confirmModalBtn" onclick="executePendingAction()">Confirm</button>
            </div>
        </div>
    </div>

    <script>
    function showToast(type, message) {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        let iconClass = type === 'success' ? 'ph-check-circle' : 'ph-warning-circle';
        let title = type === 'success' ? 'Success' : 'Error';

        toast.innerHTML = `
            <div class="toast-icon"><i class="ph-fill ${iconClass}"></i></div>
            <div class="toast-content">
                <h4>${title}</h4>
                <p>${message}</p>
            </div>
            <div class="toast-close" onclick="this.parentElement.remove()"><i class="ph-bold ph-x"></i></div>
        `;

        container.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('hiding');
            toast.addEventListener('animationend', () => {
                if (toast.parentElement) {
                    toast.remove();
                }
            });
        }, 3000);
    }

    var pendingAction = null;

    function showConfirmModal(type, title, message, callback) {
        const modal = document.getElementById('confirmationModal');
        const iconContainer = document.getElementById('confirmModalIconContainer');
        const icon = document.getElementById('confirmModalIcon');
        const titleEl = document.getElementById('confirmModalTitle');
        const descEl = document.getElementById('confirmModalDesc');
        const confirmBtn = document.getElementById('confirmModalBtn');

        titleEl.textContent = title;
        descEl.innerHTML = message;
        
        if (type === 'add' || type === 'save') {
            icon.className = 'ph-fill ph-question';
            iconContainer.style.background = 'rgba(2, 136, 209, 0.1)';
            iconContainer.style.color = '#0288d1';
            confirmBtn.style.background = 'var(--grad-blue)';
            confirmBtn.textContent = 'Confirm';
        } else if (type === 'status') {
            icon.className = 'ph-fill ph-toggle-left';
            iconContainer.style.background = 'rgba(245, 124, 0, 0.1)';
            iconContainer.style.color = '#F57C00';
            confirmBtn.style.background = 'linear-gradient(135deg, #ffb74d 0%, #ff9800 100%)';
            confirmBtn.textContent = 'Confirm Change';
        }

        pendingAction = callback;
        modal.classList.add('active');
    }

    function closeConfirmModal() {
        const modal = document.getElementById('confirmationModal');
        modal.classList.remove('active');
        
        // Revert checkbox if the action was cancelled
        if (pendingChange && pendingChange.type === 'status' && pendingChange.checkbox) {
            pendingChange.checkbox.prop('checked', pendingChange.originalValue === 'Active');
        }
        pendingAction = null;
    }

    function executePendingAction() {
        if (pendingAction) {
            pendingAction();
        }
        document.getElementById('confirmationModal').classList.remove('active');
        pendingAction = null;
    }

    $(document).ready(function() {
        const usersRolesPath = 'UsersRoles.php';

        // Initialize Choices.js
        $('select.filter-select').each(function() {
            new Choices(this, {
                searchEnabled: false,
                itemSelectText: '',
                shouldSort: false
            });
        });

        // Handle Pagination Clicks
        $(document).off('click', '.users-roles-wrapper .pagination-container .page-btn').on('click', '.users-roles-wrapper .pagination-container .page-btn', function(e) {
            if ($(this).hasClass('disabled')) return false;
            e.preventDefault();
            const urlParams = $(this).attr('href');
            $('#ajax-result').load(usersRolesPath + urlParams);
        });

        // Handle Filters
        function reloadWithFilters(page = 1, callback = null) {
            const search = $('#search-input').val();
            const role = $('#role-filter').val();
            const status = $('#status-filter').val();
            
            const params = new URLSearchParams({
                page: page,
                search: search,
                role: role,
                status: status
            });
            
            $('#ajax-result').load(usersRolesPath + '?' + params.toString(), function() {
                if (typeof callback === 'function') callback();
            });
        }

        let searchTimeout;
        $('#search-input').on('input', function() { clearTimeout(searchTimeout); searchTimeout = setTimeout(() => reloadWithFilters(1), 500); });
        $('#role-filter, #status-filter').on('change', function() { reloadWithFilters(1); });
        
        function setupAvatarUpload(containerId, inputId, previewId, fileInfoId, cancelButtonId) {
            const avatarContainer = document.getElementById(containerId);
            const avatarInput = document.getElementById(inputId);
            const avatarPreview = document.getElementById(previewId);
            const fileInfo = document.getElementById(fileInfoId);
            const cancelButton = document.getElementById(cancelButtonId);
            
            if(avatarContainer) {
                avatarContainer.insertAdjacentHTML('beforeend', '<div class="drop-message">Drop file here</div>');
            }
            const dropMessage = avatarContainer ? avatarContainer.querySelector('.drop-message') : null;

            if (avatarContainer) {
                avatarContainer.addEventListener('click', function(e) {
                    if (!e.target.closest('.drop-message')) { 
                        avatarInput.click();
                    }
                });
            }

            function handleFile(file) {
                if (file) {
                    if (!file.type.match('image.*')) {
                        showToast('error', 'Please select an image file (JPEG, PNG, GIF, etc.)');
                        avatarInput.value = '';
                        return false;
                    }
                    if (file.size > 2 * 1024 * 1024) {
                        showToast('error', 'Image size should be less than 2MB');
                        avatarInput.value = '';
                        return false;
                    }

                    if (fileInfo) {
                        fileInfo.textContent = file.name + ' (' + (file.size / 1024).toFixed(2) + ' KB)';
                    }
                    
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    avatarInput.files = dataTransfer.files;

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        if (avatarPreview) {
                            avatarPreview.innerHTML = '<img src="' + e.target.result + '" alt="Avatar Preview" style="width: 100%; height: 100%; object-fit: cover;">';
                        }
                    };
                    reader.readAsDataURL(file);
                    
                    if (cancelButton) cancelButton.style.display = 'inline-flex';
                    
                    return true;
                } else {
                    if (fileInfo) {
                        fileInfo.textContent = 'No file chosen';
                    }
                    if (avatarPreview) {
                         avatarPreview.innerHTML = '<div class="default-avatar"><i class="ph-fill ph-user"></i></div>';
                    }
                    if (cancelButton) cancelButton.style.display = 'none';

                    return false;
                }
            }
            
            if (avatarInput) {
                avatarInput.addEventListener('change', function() {
                    handleFile(this.files[0]);
                });
            }

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                if(avatarContainer) avatarContainer.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                if(avatarContainer) avatarContainer.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                if(avatarContainer) avatarContainer.addEventListener(eventName, unhighlight, false);
            });

            function highlight() {
                avatarContainer.classList.add('drag-over');
                if(dropMessage) dropMessage.style.display = 'block';
            }

            function unhighlight() {
                avatarContainer.classList.remove('drag-over');
                if(dropMessage) dropMessage.style.display = 'none';
            }

            if(avatarContainer) {
                avatarContainer.addEventListener('drop', handleDrop, false);
            }

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;

                if (files.length > 0) {
                    handleFile(files[0]); 
                }
            }
            
            if (cancelButtonId === 'cancelAddAvatar') {
                if (cancelButton) {
                    cancelButton.addEventListener('click', function() {
                        const avatarInput = document.getElementById(inputId);
                        avatarInput.value = '';
                        handleFile(null); 
                    });
                }
            }
        } 
        
        $('#addUserBtn').click(function() {
            $('#addUserForm')[0].reset();
            $('#addFileInfo').text('No file chosen');
            $('#addAvatarPreview').html('<div class="default-avatar"><i class="ph-fill ph-user"></i></div>');
            $('#cancelAddAvatar').hide(); 
            $('#addUserModal').css('display', 'flex');
        });

        $('#closeAddModal, #cancelAdd').click(function() {
            $('#addUserModal').hide();
        });

        $('#addUserForm').submit(function(e) {
            e.preventDefault();
            const userName = $('#addUserName').val();
            showConfirmModal('add', 'Add New User?', `Are you sure you want to add <strong>${userName}</strong> to the system?`, addNewUser);
        });

        function addNewUser() {
            const formData = new FormData($('#addUserForm')[0]);
            
            const submitBtn = $('#addUserForm').find('button[type="submit"]');
            const originalText = submitBtn.html();
            submitBtn.html('<i class="ph-bold ph-spinner ph-spin"></i> Adding...').prop('disabled', true);
            
            $.ajax({
                url: '../../Controller/UserController.php?action=addUser',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    submitBtn.html(originalText).prop('disabled', false);

                    if (response && response.success) {
                        $('#addUserModal').hide();
                        reloadWithFilters(1, function() {
                            showToast('success', 'User added successfully!');
                        }); 
                    } else {
                        const errorMsg = response ? response.message : 'Unknown error occurred';
                        showToast('error', 'Failed to add user: ' + errorMsg);
                    }
                },
                error: function(xhr, status, error) {
                    submitBtn.html(originalText).prop('disabled', false);
                    showToast('error', 'Error adding user. Please check console for details.');
                }
            });
        }
        
        let pendingChange = {
            type: '', 
            userID: null,
            userName: null,
            newValue: null,
            originalValue: null,
            checkbox: null
        };

        $(document).on('click', '.edit-user', function() {
            const userID = $(this).data('userid');
            openEditModal(userID);
        });

        function openEditModal(userId) {
            
            $('#editUserForm')[0].reset();
            $('#avatarInput').val('');
            $('#fileInfo').text('No file chosen');
            $('#deleteAvatar').prop('checked', false); 
            $('#editUserRole').prop('disabled', false).removeClass('disabled-field');
            $('#editUserStatus').prop('disabled', false).removeClass('disabled-field');
            $('#editUserName').prop('disabled', false).removeClass('disabled-field');
            $('#editUserEmail').prop('disabled', false).removeClass('disabled-field');

            $('#editUserModal').css('display', 'flex');
            
            const currentLoggedInUserID = "<?php echo $_SESSION['user']['id']; ?>";

            $.ajax({
                url: '../../Controller/UserController.php?action=getUser&userID=' + userId,
                type: 'GET',
                dataType: 'json',
                success: function(user) {
                    if (user && user.UserID) {
                        $('#editUserID').val(user.UserID);
                        $('#editUserName').val(user.UserName || '');
                        $('#editUserEmail').val(user.Email || '');
                        $('#editUserRole').val(user.Role || 'Employee');
                        $('#editUserStatus').val(user.IsActive || 'Active');
                        $('#currentImagePath').val(user.ImagePath || '');
                        
                        const avatarPreview = $('#avatarPreview');
                        if (user.ImagePath && !user.ImagePath.startsWith('default_user_')) {
                            const timestamp = new Date().getTime();
                            const imageUrl = '../../Assets/Image/User/' + user.ImagePath + '?t=' + timestamp;
                            avatarPreview.html('<img src="' + imageUrl + '" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">');
                        } else {
                            avatarPreview.html('<div class="default-avatar" style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #f0f0f0; border-radius: 50%;">' +
                                '<i class="ph-fill ph-user" style="font-size: 48px; color: #666;"></i>' +
                                '</div>');
                        }
                        
                        const isTargetManager = user.Role === 'Manager';
                        const isEditingSelf = user.UserID == currentLoggedInUserID;

                        if (isTargetManager) {
                            $('#editUserRole').prop('disabled', true).addClass('disabled-field');
                            $('#editUserStatus').prop('disabled', true).addClass('disabled-field');
                            
                            if (!isEditingSelf) {
                                $('#editUserName').prop('disabled', true).addClass('disabled-field');
                                $('#editUserEmail').prop('disabled', true).addClass('disabled-field');
                            }
                        }
                    } else {
                        showToast('error', 'Failed to load user information');
                        $('#editUserModal').hide();
                    }
                },
                error: function(xhr, status, error) {
                    showToast('error', 'Error loading user information.');
                    $('#editUserModal').hide();
                }
            });
        }
        $('#closeEditModal, #cancelEdit').click(function() {
            $('#editUserModal').hide();
        });
        $(window).click(function(event) {
            if ($(event.target).is('#editUserModal')) {
                $('#editUserModal').hide();
            }
            if ($(event.target).is('#confirmationModal.modal-overlay')) {
                closeConfirmModal();
            }
        });

        $('#editUserForm').submit(function(e) {
            e.preventDefault();
            const userName = $('#editUserName').val();
            showConfirmModal('save', 'Save Changes?', `Are you sure you want to save changes for <strong>${userName}</strong>?`, saveUserChanges);
        });

        function saveUserChanges() {
            $('#editUserRole').prop('disabled', false); 
            $('#editUserStatus').prop('disabled', false); 
            $('#editUserName').prop('disabled', false); 
            $('#editUserEmail').prop('disabled', false);
            
            const formData = new FormData($('#editUserForm')[0]);
            const targetUserID = $('#editUserID').val();
            const targetUserRole = $(`tr[data-userid="${targetUserID}"]`).data('original-role');
            if (targetUserRole === 'Manager') {
                 $('#editUserRole').prop('disabled', true); 
                 $('#editUserStatus').prop('disabled', true); 
            }
            
            const submitBtn = $('#editUserForm').find('button[type="submit"]');
            const originalText = submitBtn.html();
            submitBtn.html('<i class="ph-bold ph-spinner ph-spin"></i> Saving...').prop('disabled', true);
            
            $.ajax({
                url: '../../Controller/UserController.php?action=editUser',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    submitBtn.html(originalText).prop('disabled', false);
                    
                    if (response && response.success) {
                        $('#editUserModal').hide();

                        const editedUserID = $('#editUserID').val();
                        const currentLoggedInUserID = "<?php echo $_SESSION['user']['id']; ?>";

                        if (editedUserID == currentLoggedInUserID) {
                            const updateEvent = new CustomEvent('userProfileUpdated', {
                                detail: {
                                    userName: response.userName,
                                    userImage: response.imagePath
                                }
                            });
                            window.dispatchEvent(updateEvent);
                        }

                        reloadWithFilters(<?php echo $current_page; ?>, function() {
                            showToast('success', 'User updated successfully!');
                        }); 
                    } else {
                        const errorMsg = response ? response.message : 'Unknown error occurred';
                        showToast('error', 'Failed to update user: ' + errorMsg);
                    }
                },
                error: function(xhr, status, error) {
                    submitBtn.html(originalText).prop('disabled', false);
                    showToast('error', 'Error updating user. Please check console for details.');
                }
            });
        }

        // Status Toggle Handler
        $(document).on('change', '.status-toggle', function(e) {
            e.preventDefault();
            
            const userID = $(this).data('userid');
            const userName = $(this).data('username');
            const isChecked = $(this).is(':checked');
            const newStatus = isChecked ? 'Active' : 'Inactive';
            const originalStatus = !isChecked ? 'Active' : 'Inactive';
            
            pendingChange = {
                type: 'status',
                userID: userID,
                userName: userName,
                newValue: newStatus,
                originalValue: originalStatus,
                checkbox: $(this)
            };
            
            const message = `Are you sure you want to change <strong>${userName}</strong>'s status to <strong>${newStatus}</strong>?`;
            showConfirmModal('status', 'Confirm Status Change', message, updateUserStatus);
        });

        function updateUserStatus() { // This will be called by executePendingAction
            const { userID, newValue, originalValue, checkbox } = pendingChange;
            
            $.ajax({
                url: '../../Controller/UserController.php?action=updateUserStatus',
                type: 'POST',
                data: {
                    userID: userID,
                    status: newValue
                },
                dataType: 'json',
                success: function(response) {
                    if (response && response.success) {
                        // Success, no need to reload entire page, just update data attribute
                        $(`tr[data-userid="${userID}"]`).data('original-status', newValue);
                    } else {
                        const errorMsg = response ? response.message : 'Unknown error occurred';
                        showToast('error', 'Failed to update user status: ' + errorMsg);
                        // Revert checkbox
                        if(checkbox) checkbox.prop('checked', originalValue === 'Active');
                    }
                },
                error: function(xhr, status, error) {
                    showToast('error', 'Error updating user status. Please try again.');
                    // Revert checkbox
                    if(checkbox) checkbox.prop('checked', originalValue === 'Active');
                }
            });
        }

        setupAvatarUpload('addAvatarPreviewContainer', 'addAvatarInput', 'addAvatarPreview', 'addFileInfo', 'cancelAddAvatar'); 
        setupAvatarUpload('avatarPreviewContainer', 'avatarInput', 'avatarPreview', 'fileInfo', null); 
    });

    // --- PDF Generation Logic ---
    function loadScript(url) {
        return new Promise((resolve, reject) => {
            if (document.querySelector(`script[src="${url}"]`)) { resolve(); return; }
            const script = document.createElement('script');
            script.src = url;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    async function ensurePDFLibraries() {
        if (!window.jspdf) await loadScript("https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js");
        if (!window.jsPDF && window.jspdf) window.jsPDF = window.jspdf.jsPDF;
        try {
            const tempDoc = new window.jsPDF();
            if (typeof tempDoc.autoTable !== 'function') await loadScript("https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js");
        } catch (e) {
            await loadScript("https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js");
        }
    }

    async function generateUserPDF() {
        const btn = document.querySelector('.btn-export');
        const originalContent = btn.innerHTML;
        btn.innerHTML = '<i class="ph-bold ph-spinner ph-spin"></i> Generating...';
        btn.disabled = true;

        try {
            await ensurePDFLibraries();
            const doc = new window.jsPDF();
            
            // Header
            doc.setFontSize(18);
            doc.setTextColor(26, 37, 48);
            doc.text("User Roles Report", 14, 20);
            doc.setFontSize(10);
            doc.setTextColor(100);
            doc.text("Generated on: " + new Date().toLocaleString(), 14, 28);

            // Collect Data from Table (Respects current filters)
            // Note: This only exports the current page. To export all, a separate endpoint is needed.
            const rows = [];
            $('#users-table tbody tr').each(function() {
                const $row = $(this);
                const name = $row.find('.user-detail h4').text().trim();
                const email = $row.find('.user-detail p').text().trim();
                const role = $row.find('.role-badge').text().trim();
                const status = $row.data('original-status');
                const joined = $row.find('td:eq(3)').text().trim();
                rows.push([name, email, role, status, joined]);
            });

            doc.autoTable({
                startY: 35,
                head: [['Name', 'Email', 'Role', 'Status', 'Joined Date']],
                body: rows,
                theme: 'grid',
                headStyles: { fillColor: [26, 37, 48] },
                styles: { fontSize: 10, cellPadding: 4 }
            });

            doc.save(`Users_Report_${new Date().toISOString().slice(0,10)}.pdf`);
        } catch (err) {
            console.error(err);
            alert("Failed to generate PDF.");
        } finally {
            btn.innerHTML = originalContent;
            btn.disabled = false;
        }
    }
    </script>