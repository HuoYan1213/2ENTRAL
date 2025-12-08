<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== "Manager") {
    header('Location: ../Public/AccessDenied.php');
    exit();
}

require_once __DIR__ . "/../../Controller/UserController.php";
$controller = new UserController();

$users = $controller->getUsers();

$latestImagePath = $controller->getUserAvatar($_SESSION['user']['id']);
if ($latestImagePath !== ($_SESSION['user']['image'] ?? '')) {
    $_SESSION['user']['image'] = $latestImagePath;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users & Roles</title>
    <link rel="stylesheet" href="../../Assets/CSS/usersroles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
</head>
<body>
    <div class="userole-title">
        <h2>Users & Roles</h2>
        <span>Manage system users and their permissions.</span>
    </div>

    <div class="search-container">
    <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" id="search-input" placeholder="Search users...">
    </div>
    <div class="filter-options">
        
        <select class="filter-select" id="role-filter">
            <option value="">All Roles</option>
            <option value="Manager">Manager</option>
            <option value="Employee">Employee</option>
        </select>
        <select class="filter-select" id="status-filter">
            <option value="">All Status</option>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
        </select>

        <button class="action-btn add-user-btn" id="addUserBtn" style="background-color: #9b59b6;">
            <i class="fas fa-user-plus"></i> Add User
        </button>
    </div>
</div>

    <div class="table-container">
        <table id="users-table">
            <thead>
                <tr>
                    <th data-sort="numeric" data-column="0" class="sortable sort-asc">
                        ID 
                        <span class="sort-icon-container">
                            <i class="fas fa-sort-up"></i>
                            <i class="fas fa-sort-down"></i>
                        </span>
                    </th> 
                    
                    <th data-sort="text" data-column="1" class="sortable">
                        Name 
                        <span class="sort-icon-container">
                            <i class="fas fa-sort-up"></i>
                            <i class="fas fa-sort-down"></i>
                        </span>
                    </th>
                    
                    <th>Email</th>
                    <th>Create At</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Action</th>
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
                        
                        $roleDisabledAttr = $disableRoleStatus ? 'disabled' : '';
                        $statusDisabledAttr = $disableRoleStatus ? 'disabled' : '';
                        $editButtonHidden = ($isTargetManager && !$isEditingSelf) ? 'style="display:none;"' : '';
                    ?>
                    <tr data-userid="<?php echo $user['UserID']; ?>" 
                        data-original-role="<?php echo htmlspecialchars($user['Role'] ?? ''); ?>"
                        data-original-status="<?php echo htmlspecialchars($userStatus); ?>">
                        
                        <td><?php echo htmlspecialchars($user['UserID']); ?></td> 
                        
                        <td>
                            <div class="name-cell">
                                <div class="avatar-preview">
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
                                                 style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover;"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <div class="default-avatar-fallback" style="width: 35px; height: 35px; border-radius: 50%; background: #ddd; display: none; align-items: center; justify-content: center;">
                                                <i class="fas fa-user" style="color: #666; font-size: 14px;"></i>
                                            </div>
                                        <?php else: ?>
                                            <div style="width: 35px; height: 35px; border-radius: 50%; background: #ddd; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-user" style="color: #666; font-size: 14px;"></i>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div style="width: 35px; height: 35px; border-radius: 50%; background: #ddd; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-user" style="color: #666; font-size: 14px;"></i>
                                            </div>
                                        <?php endif; ?>
                                </div>
                                <?php echo htmlspecialchars($user['UserName'] ?? ''); ?>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($user['Email'] ?? ''); ?></td>
                        <td>
                            <?php 
                            $createdAt = $user['CreatedAt'] ?? '';
                            if (!empty($createdAt)) {
                                echo date('Y-m-d H:i:s', strtotime($createdAt));
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </td>
                        <td>
                            <select class="role-select" data-userid="<?php echo $user['UserID']; ?>" data-username="<?php echo htmlspecialchars($user['UserName'] ?? ''); ?>" <?php echo $roleDisabledAttr; ?>>
                                <option value="Employee" <?php echo ($user['Role'] ?? '') === 'Employee' ? 'selected' : ''; ?>>Employee</option>
                                <option value="Manager" <?php echo ($user['Role'] ?? '') === 'Manager' ? 'selected' : ''; ?>>Manager</option>
                            </select>
                        </td>
                        <td>
                            <select class="status-select" data-userid="<?php echo $user['UserID']; ?>" data-username="<?php echo htmlspecialchars($user['UserName'] ?? ''); ?>" <?php echo $statusDisabledAttr; ?>>
                                <option value="Active" <?php echo $userStatus === 'Active' ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo $userStatus === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </td>
                        <td>
                            <button class="action-btn edit-user" data-userid="<?php echo $user['UserID']; ?>" <?php echo $editButtonHidden; ?>>
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="action-btn btn-save-role save-role" data-userid="<?php echo $user['UserID']; ?>" style="display: none;">
                                <i class="fas fa-save"></i> Save Role
                            </button>
                            <button class="action-btn btn-save-status save-status" data-userid="<?php echo $user['UserID']; ?>" style="display: none;">
                                <i class="fas fa-save"></i> Save Status
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 20px;">No users found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="no-results" id="no-results">
            <i class="fas fa-search" style="font-size: 48px; margin-bottom: 10px;"></i>
            <p>No users found matching your criteria.</p>
        </div>
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
                                    <i class="fas fa-user"></i>
                                </div>
                            </div>
                        </div>
                        <input type="file" id="avatarInput" class="avatar-upload-input" name="avatar" accept="image/*">
                        <label for="avatarInput" class="avatar-upload-label">
                            <i class="fas fa-upload"></i> Choose Profile Image
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
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                    </div>
                    <input type="file" id="addAvatarInput" class="avatar-upload-input" name="avatar" accept="image/*">
                    
                    <div style="display: flex; gap: 10px; justify-content: center; align-items: center;">
                        <label for="addAvatarInput" class="avatar-upload-label">
                            <i class="fas fa-upload"></i> Choose Profile Image
                        </label>
                        <button type="button" class="action-btn btn-cancel" id="cancelAddAvatar" 
                            style="margin: 0; height: 36px; box-sizing: border-box; display: none;"
                            title="Cancel Upload">
                            <i class="fas fa-times"></i> Cancel
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

    <div id="confirmationModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h3>Confirm Change</h3>
                <span class="close" id="closeConfirmModal">&times;</span>
            </div>
            <div class="modal-body">
                <p id="confirmationMessage">Are you sure you want to make this change?</p>
                <div class="form-actions">
                    <button type="button" class="btn-cancel" id="cancelConfirm">Cancel</button>
                    <button type="button" class="btn-save" id="confirmChange">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        
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
                        alert('Please select an image file (JPEG, PNG, GIF, etc.)');
                        avatarInput.value = '';
                        return false;
                    }
                    if (file.size > 2 * 1024 * 1024) {
                        alert('Image size should be less than 2MB');
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
                         avatarPreview.innerHTML = '<div class="default-avatar"><i class="fas fa-user"></i></div>';
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
            $('#addAvatarPreview').html('<div class="default-avatar"><i class="fas fa-user"></i></div>');
            $('#cancelAddAvatar').hide(); 
            $('#addUserModal').css('display', 'flex');
        });

        $('#closeAddModal, #cancelAdd').click(function() {
            $('#addUserModal').hide();
        });

        $('#addUserForm').submit(function(e) {
            e.preventDefault();
            
            if (confirm("Are you sure you want to add this new user?")) {
                addNewUser();
            }
        });

        function addNewUser() {
            const formData = new FormData($('#addUserForm')[0]);
            
            const submitBtn = $('#addUserForm').find('button[type="submit"]');
            const originalText = submitBtn.html();
            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Adding...').prop('disabled', true);
            
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
                        alert('User added successfully!');
                        $('#addUserModal').hide();
                        $('#ajax-result').load('UsersRoles.php');
                    } else {
                        const errorMsg = response ? response.message : 'Unknown error occurred';
                        alert('Failed to add user: ' + errorMsg);
                    }
                },
                error: function(xhr, status, error) {
                    submitBtn.html(originalText).prop('disabled', false);
                    alert('Error adding user. Please check console for details.');
                }
            });
        }
        
        let pendingChange = {
            type: '', 
            userID: null,
            userName: null,
            newValue: null,
            originalValue: null
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
                                '<i class="fas fa-user" style="font-size: 48px; color: #666;"></i>' +
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
                        alert('Failed to load user information');
                        $('#editUserModal').hide();
                    }
                },
                error: function(xhr, status, error) {
                    alert('Error loading user information.');
                    $('#editUserModal').hide();
                }
            });
        }
        $('#closeEditModal, #cancelEdit').click(function() {
            $('#editUserModal').hide();
        });
        $('#closeConfirmModal, #cancelConfirm').click(function() {
            $('#confirmationModal').hide();
        });
        $(window).click(function(event) {
            if ($(event.target).is('#editUserModal')) {
                $('#editUserModal').hide();
            }
            if ($(event.target).is('#confirmationModal')) {
                $('#confirmationModal').hide();
            }
        });

        $('#editUserForm').submit(function(e) {
            e.preventDefault();
            
            if (confirm("Are you sure you want to save these changes for the user?")) {
                saveUserChanges();
            }
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
            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
            
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
                        alert('User updated successfully!');
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

                        $('#ajax-result').load('UsersRoles.php');
                    } else {
                        const errorMsg = response ? response.message : 'Unknown error occurred';
                        alert('Failed to update user: ' + errorMsg);
                    }
                },
                error: function(xhr, status, error) {
                    submitBtn.html(originalText).prop('disabled', false);
                    alert('Error updating user. Please check console for details.');
                }
            });
        }

        $('#users-table').on('change', '.role-select', function() {
            if ($(this).is(':disabled')) return; 
            const newRole = $(this).val();
            const originalRole = $(this).closest('tr').data('original-role');
            
            if (newRole === originalRole) {
                $(this).closest('tr').find('.save-role').hide();
                return;
            }
            
            $(this).closest('tr').find('.save-role').show();
        });

        $('#users-table').on('change', '.status-select', function() {
            if ($(this).is(':disabled')) return; 
            const newStatus = $(this).val();
            const originalStatus = $(this).closest('tr').data('original-status');
            
            if (newStatus === originalStatus) {
                $(this).closest('tr').find('.save-status').hide();
                return;
            }
            
            $(this).closest('tr').find('.save-status').show();
        });

        $('#users-table').on('click', '.save-role', function() {
            if ($(this).closest('tr').find('.role-select').is(':disabled')) return;
            const userID = $(this).data('userid');
            const $row = $(this).closest('tr');
            const $roleSelect = $row.find('.role-select');
            const userName = $row.find('.name-cell > span').text();
            const newRole = $roleSelect.val();
            const originalRole = $row.data('original-role');
            
            pendingChange = {
                type: 'role',
                userID: userID,
                userName: userName,
                newValue: newRole,
                originalValue: originalRole
            };
            
            $('#confirmationMessage').html(
                `Are you sure you want to change <strong>${userName}</strong>'s role?<br><br>
                <strong>From:</strong> ${originalRole}<br>
                <strong>To:</strong> ${newRole}`
            );
            $('#confirmationModal').css('display', 'flex');
        });

        $('#users-table').on('click', '.save-status', function() {
            if ($(this).closest('tr').find('.status-select').is(':disabled')) return;
            const userID = $(this).data('userid');
            const $row = $(this).closest('tr');
            const $statusSelect = $row.find('.status-select');
            const userName = $row.find('.name-cell > span').text();
            const newStatus = $statusSelect.val();
            const originalStatus = $row.data('original-status');
            
            pendingChange = {
                type: 'status',
                userID: userID,
                userName: userName,
                newValue: newStatus,
                originalValue: originalStatus
            };
            
            $('#confirmationMessage').html(
                `Are you sure you want to change <strong>${userName}</strong>'s status?<br><br>
                <strong>From:</strong> ${originalStatus}<br>
                <strong>To:</strong> ${newStatus}`
            );
            $('#confirmationModal').css('display', 'flex');
        });
        

        $('#confirmChange').click(function() {
            $('#confirmationModal').hide();
            
            if (pendingChange.type === 'role') {
                updateUserRole();
            } else if (pendingChange.type === 'status') {
                updateUserStatus();
            }
        });

        $('#cancelConfirm').click(function() {
            $('#confirmationModal').hide();
            
            const $row = $(`tr[data-userid="${pendingChange.userID}"]`);
            if (pendingChange.type === 'role') {
                $row.find('.role-select').val(pendingChange.originalValue);
                $row.find('.save-role').hide();
            } else if (pendingChange.type === 'status') {
                $row.find('.status-select').val(pendingChange.originalValue);
                $row.find('.save-status').hide();
            }
        });

        function updateUserRole() {
            const { userID, newValue, originalValue } = pendingChange;
            
            const $saveBtn = $(`tr[data-userid="${userID}"] .save-role`);
            const originalText = $saveBtn.html();
            $saveBtn.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
            
            $.ajax({
                url: '../../Controller/UserController.php?action=updateUserRole',
                type: 'POST',
                data: {
                    userID: userID,
                    role: newValue
                },
                dataType: 'json',
                success: function(response) {
                    $saveBtn.html(originalText).prop('disabled', false);
                    
                    if (response && response.success) {
                        alert('User role updated successfully!');
                        $('#ajax-result').load('UsersRoles.php');
                    } else {
                        const errorMsg = response ? response.message : 'Unknown error occurred';
                        alert('Failed to update user role: ' + errorMsg);
                        $row.find('.role-select').val(originalValue);
                        $row.find('.save-role').hide();
                    }
                },
                error: function(xhr, status, error) {
                    $saveBtn.html(originalText).prop('disabled', false);
                    alert('Error updating user role. Please try again.');
                    $row.find('.role-select').val(originalValue);
                    $row.find('.save-role').hide();
                }
            });
        }

        function updateUserStatus() {
            const { userID, newValue, originalValue } = pendingChange;
            
            const $saveBtn = $(`tr[data-userid="${userID}"] .save-status`);
            const originalText = $saveBtn.html();
            $saveBtn.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
            
            $.ajax({
                url: '../../Controller/UserController.php?action=updateUserStatus',
                type: 'POST',
                data: {
                    userID: userID,
                    status: newValue
                },
                dataType: 'json',
                success: function(response) {
                    $saveBtn.html(originalText).prop('disabled', false);
                    
                    if (response && response.success) {
                        alert('User status updated successfully!');
                        $('#ajax-result').load('UsersRoles.php');
                    } else {
                        const errorMsg = response ? response.message : 'Unknown error occurred';
                        alert('Failed to update user status: ' + errorMsg);
                        $row.find('.status-select').val(originalValue);
                        $row.find('.save-status').hide();
                    }
                },
                error: function(xhr, status, error) {
                    $saveBtn.html(originalText).prop('disabled', false);
                    alert('Error updating user status. Please try again.');
                    $row.find('.status-select').val(originalValue);
                    $row.find('.save-status').hide();
                }
            });
        }

        $(document).on('click', '#users-table th.sortable', function() {
            const $header = $(this);
            const columnIndex = $header.data('column');
            const sortType = $header.data('sort');
            
            let direction = 'asc';
            if ($header.hasClass('sort-asc')) {
                direction = 'desc';
            } else if ($header.hasClass('sort-desc')) {
                direction = 'asc';
            }
            
            $('#users-table th.sortable').removeClass('sort-asc sort-desc');

            $header.addClass('sort-' + direction);
            
            sortTable(columnIndex, direction, sortType);
        });

        function sortTable(columnIndex, direction, type) {
            const $tbody = $('#users-table tbody');
            const rows = $tbody.find('tr').toArray();
            
            rows.sort(function(a, b) {
                let valA, valB;
                
                if ($(a).find('td').length < 7 || $(b).find('td').length < 7) {
                    return 0;
                }

                if (columnIndex == 1) { 
                    valA = $(a).find('td').eq(columnIndex).text().trim();
                    valB = $(b).find('td').eq(columnIndex).text().trim();
                } else {
                    valA = $(a).find('td').eq(columnIndex).text().trim();
                    valB = $(b).find('td').eq(columnIndex).text().trim();
                }

                let comparison = 0;

                if (type === 'numeric') {
                    const numA = parseFloat(valA) || 0;
                    const numB = parseFloat(valB) || 0;
                    comparison = numA - numB;
                } else { 
                    comparison = valA.localeCompare(valB, 'en', { sensitivity: 'base' });
                }
                
                return direction === 'asc' ? comparison : -comparison;
            });
            
            $tbody.empty();
            $.each(rows, function(index, row) {
                $tbody.append(row);
            });
        }

        $('#search-input').on('input', filterUsers);
        $('#role-filter, #status-filter').change(filterUsers);

        function filterUsers() {
            const searchTerm = $('#search-input').val().toLowerCase();
            const roleFilter = $('#role-filter').val();
            const statusFilter = $('#status-filter').val();
            
            let hasVisibleRows = false;
            
            $('#users-table tbody tr').each(function() {
                if ($(this).find('td').length < 7) return; 
                
                const userID = $(this).find('td:nth-child(1)').text().toLowerCase(); 
                const userName = $(this).find('td:nth-child(2)').text().toLowerCase();
                
                const userRole = $(this).find('.role-select').val();
                const userStatus = $(this).find('.status-select').val();
                
                const matchesSearch = userID.includes(searchTerm) || userName.includes(searchTerm);
                
                const matchesRole = !roleFilter || userRole === roleFilter;
                const matchesStatus = !statusFilter || userStatus === statusFilter;
                
                const isVisible = matchesSearch && matchesRole && matchesStatus;
                $(this).toggle(isVisible);
                
                if (isVisible) hasVisibleRows = true;
            });
            
            $('#no-results').toggle(!hasVisibleRows);
        }

        const initialSortColumn = 0;
        const initialSortDirection = 'asc';
        const initialSortType = 'numeric';
        
        sortTable(initialSortColumn, initialSortDirection, initialSortType);
        
        $('#no-results').hide();

        setupAvatarUpload('addAvatarPreviewContainer', 'addAvatarInput', 'addAvatarPreview', 'addFileInfo', 'cancelAddAvatar'); 
        setupAvatarUpload('avatarPreviewContainer', 'avatarInput', 'avatarPreview', 'fileInfo', null); 
    });
    </script>
</body>
</html>