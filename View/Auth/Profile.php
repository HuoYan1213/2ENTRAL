<?php 
session_start();

require_once __DIR__ . "/../../Controller/UserController.php";

if (!isset($_SESSION['user'])) {
    header('Location: /View/Public/AccessDenied.php');
    exit();
} else {
    $CURRENT_NAME = $_SESSION['user']['name'] ?? '';
    $CURRENT_EMAIL = $_SESSION['user']['email'] ?? '';
    $CURRENT_ROLE = $_SESSION['user']['role'] ?? '';
    $CURRENT_IMAGE = $_SESSION['user']['image'] ?? '';
    $CURRENT_ID = $_SESSION['user']['id'] ?? '';
    
    $JOIN_DATE = $_SESSION['user']['created_at'] ?? '';
    $CURRENT_STATUS = $_SESSION['user']['is_active'] ?? 'Active'; 
    $formattedJoinDate = 'N/A';
    
    if (empty($JOIN_DATE) || empty($_SESSION['user']['is_active'])) {
        require_once __DIR__ . '/../../Model/DB.php';
        
        if (isset($conn) && $conn->connect_error === null && !empty($CURRENT_ID)) {
            $stmt = $conn->prepare("SELECT CreatedAt, IsActive FROM users WHERE UserID = ?");
            if ($stmt) {
                $stmt->bind_param("i", $CURRENT_ID);
                if ($stmt->execute()) {
                    $result = $stmt->get_result();
                    if ($result && $result->num_rows > 0) {
                        $userData = $result->fetch_assoc();
                        $JOIN_DATE = $userData['CreatedAt'] ?? '';
                        $CURRENT_STATUS = $userData['IsActive'] ?? 'Active';
                        
                        $_SESSION['user']['created_at'] = $JOIN_DATE;
                        $_SESSION['user']['is_active'] = $CURRENT_STATUS;
                        session_write_close();
                    }
                }
                $stmt->close();
            }
        }
    }
    
    if (!empty($JOIN_DATE)) {
        try {
            $date = new DateTime($JOIN_DATE);
            $formattedJoinDate = $date->format('F j, Y');
        } catch (Exception $e) {
            $formattedJoinDate = $JOIN_DATE;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="../../Assets/CSS/usersroles.css">
    
</head>
<body>
<div class="profile-container">
    <div class="profile-header">
        <div class="profile-avatar">
            <?php 
            $imageSrc = '../../Assets/Image/User/' . $CURRENT_IMAGE;
            $placeholderUrl = 'https://ui-avatars.com/api/?name=' . urlencode($CURRENT_NAME) . '&background=3498db&color=fff';
            
            $isDefaultImage = empty($CURRENT_IMAGE) || $CURRENT_IMAGE === 'default.png' || strpos($CURRENT_IMAGE, 'default_user_') !== false;
            
            if (!$isDefaultImage): 
            ?>
                <img src="<?= $imageSrc ?>?t=<?= time() ?>" alt="<?= $CURRENT_NAME ?>" 
                     onerror="this.src='<?= $placeholderUrl ?>'; this.onerror=null;"> 
            <?php else: ?>
                <img src="<?= $placeholderUrl ?>" alt="<?= $CURRENT_NAME ?>">
            <?php endif; ?>
        </div>
        <div class="profile-info">
            <h1><?= htmlspecialchars($CURRENT_NAME) ?></h1>
            <p><?= htmlspecialchars($CURRENT_EMAIL) ?></p>
            <span class="role-badge"><?= htmlspecialchars($CURRENT_ROLE) ?></span>
        </div>
    </div>

    <div class="profile-content">
        <div class="profile-overview" id="profileOverview">
            <div class="overview-header">
                <h2 class="section-title">Profile Overview</h2>
            </div>
            
            <div class="info-grid">
                <div class="info-group">
                    <div class="info-label">Full Name</div>
                    <div class="info-value"><?= htmlspecialchars($CURRENT_NAME) ?></div>
                </div>
                
                <div class="info-group">
                    <div class="info-label">Email Address</div>
                    <div class="info-value"><?= htmlspecialchars($CURRENT_EMAIL) ?></div>
                </div>
                
                <div class="info-group">
                    <div class="info-label">Role</div>
                    <div class="info-value"><?= htmlspecialchars($CURRENT_ROLE) ?></div>
                </div>
                
                <div class="info-group">
                    <div class="info-label">Account Status</div>
                    <div class="info-value">
                        <span style="color: <?= $CURRENT_STATUS === 'Active' ? '#27ae60' : '#e74c3c'; ?>; font-weight: 600;">
                            <i class="fas <?= $CURRENT_STATUS === 'Active' ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i> <?= htmlspecialchars($CURRENT_STATUS) ?>
                        </span>
                    </div>
                </div>
                
                <div class="info-group">
                    <div class="info-label">Join Date</div>
                    <div class="info-value">
                        <?php if ($formattedJoinDate !== 'N/A'): ?>
                            <i class="fas fa-calendar-alt"></i> <?= htmlspecialchars($formattedJoinDate) ?>
                        <?php else: ?>
                            <span style="color: #999; font-style: italic;"><?= htmlspecialchars($formattedJoinDate) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="info-group">
                    <div class="info-label">User ID</div>
                    <div class="info-value"><?= htmlspecialchars($CURRENT_ID) ?></div>
                </div>
            </div>
            
            <div class="overview-actions">
                <button class="btn btn-primary" id="editProfileBtn">
                    <i class="fas fa-edit"></i> Edit Profile
                </button>
            </div>
        </div>
        
        <div class="edit-profile-form" id="editProfileForm" style="display: none;">
            <div class="form-header">
                <h2 class="section-title">Edit Profile</h2>
            </div>
            
            <form id="profileForm">
                <input type="hidden" id="currentImagePath" name="currentImagePath" value="<?= htmlspecialchars($CURRENT_IMAGE) ?>">
                
                <div class="form-group">
                    <label>Profile Image</label>
                    <div class="avatar-upload">
                        <div class="avatar-preview-container" id="avatarPreviewContainer">
                            <div class="avatar-preview-large" id="avatarPreview">
                                <div class="default-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                            </div>
                            <div class="drop-message">Drop file here</div>
                        </div>
                        <input type="file" id="avatarInput" class="avatar-upload-input" name="avatar" accept="image/*">
                        
                        <label for="avatarInput" class="avatar-upload-label">
                            <i class="fas fa-upload"></i> Choose Profile Image
                        </label>
                        <div class="file-info" id="fileInfo">No file chosen</div>
                        
                        <div class="form-group" style="margin-top: 15px; display: flex; align-items: center; justify-content: center;">
                            <input type="checkbox" id="deleteAvatar" name="deleteAvatar" value="1" style="width: auto; margin-right: 5px;">
                            <label for="deleteAvatar" style="font-weight: normal; margin-bottom: 0; cursor: pointer;">Delete Current Avatar</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($CURRENT_NAME) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($CURRENT_EMAIL) ?>" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <button type="button" class="btn btn-secondary" id="cancelEditBtn">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function getPlaceholderUrl() {
        const currentName = '<?= urlencode($CURRENT_NAME) ?>';
        return 'https://ui-avatars.com/api/?name=' + currentName + '&background=3498db&color=fff';
    }

    function showDefaultAvatar() {
        const avatarPreview = document.getElementById('avatarPreview');
        const fallbackUrl = getPlaceholderUrl(); 
        if (avatarPreview) {
            avatarPreview.innerHTML = '<img src="' + fallbackUrl + '" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">';
        }
    }
    
$(document).ready(function() {
    
    function setupAvatarUpload(containerId, inputId, previewId, fileInfoId) {
        const avatarContainer = document.getElementById(containerId);
        const avatarInput = document.getElementById(inputId);
        const avatarPreview = document.getElementById(previewId);
        const fileInfo = document.getElementById(fileInfoId);
        
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
                
                $('#deleteAvatar').prop('checked', false);

                const reader = new FileReader();
                reader.onload = function(e) {
                    if (avatarPreview) {
                        avatarPreview.innerHTML = '<img src="' + e.target.result + '" alt="Avatar Preview" style="width: 100%; height: 100%; object-fit: cover;">';
                    }
                };
                reader.readAsDataURL(file);
                
                return true;
            } else {
                if (fileInfo) {
                    fileInfo.textContent = 'No file chosen';
                }
                if (avatarPreview) {
                     showDefaultAvatar(); 
                }
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
    }

    setupAvatarUpload('avatarPreviewContainer', 'avatarInput', 'avatarPreview', 'fileInfo'); 
    
    $('#editProfileBtn').click(function() {
        showEditForm();
    });

    $('#cancelEditBtn').click(function() {
        hideEditForm();
    });

    $('#deleteAvatar').on('change', function() {
        if ($(this).is(':checked')) {
            $('#avatarInput').val(''); 
            
            const fileInfo = document.getElementById('fileInfo');
            if (fileInfo) {
                fileInfo.textContent = 'No file chosen';
            }

            showDefaultAvatar();
        }
    });

    function showEditForm() {
        $('#profileOverview').hide();
        $('#editProfileForm').show();
        
        const currentImage = '<?= $CURRENT_IMAGE ?>';
        const avatarPreview = document.getElementById('avatarPreview');
        const isDefault = currentImage === 'default.png' || !currentImage || currentImage.startsWith('default_user_');
        
        $('#deleteAvatar').prop('checked', false);
        $('#fileInfo').text('No file chosen');
        $('#avatarInput').val(''); 

        if (currentImage && !isDefault) {
            const timestamp = new Date().getTime();
            const imageUrl = '../../Assets/Image/User/' + currentImage + '?t=' + timestamp;
            
            if (avatarPreview) {
                avatarPreview.innerHTML = '<img src="' + imageUrl + '" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;" onerror="showDefaultAvatar()">';
            }
        } else {
            showDefaultAvatar();
        }
    }

    function hideEditForm() {
        $('#editProfileForm').hide();
        $('#profileOverview').show();
        
        $('#profileForm')[0].reset();
        $('#avatarInput').val('');
        const fileInfo = document.getElementById('fileInfo');
        if (fileInfo) {
            fileInfo.textContent = 'No file chosen';
        }
        $('#deleteAvatar').prop('checked', false);
    }

    $('#profileForm').submit(function(e) {
        e.preventDefault();
        
        if (confirm("Are you sure you want to save these profile changes? This action is irreversible.")) {
            saveProfileChanges();
        }
    });

    function saveProfileChanges() {
        const formData = new FormData();
        
        formData.append('userID', '<?= $CURRENT_ID ?>');
        formData.append('userName', $('#name').val());
        formData.append('email', $('#email').val());
        formData.append('currentImagePath', $('#currentImagePath').val()); 
        
        const avatarFile = $('#avatarInput')[0].files[0];
        const deleteChecked = $('#deleteAvatar').is(':checked'); 
        
        if (avatarFile) {
            formData.append('avatar', avatarFile);
            formData.append('deleteAvatar', '0'); 
        } else if (deleteChecked) {
            formData.append('deleteAvatar', '1'); 
        } else {
            formData.append('deleteAvatar', '0'); 
        }
        
        const submitBtn = $('#profileForm').find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
        
        $.ajax({
            url: '../../Controller/UserController.php?action=editProfile',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response && response.success) {
                    alert('Profile updated successfully!'); 

                    const updateEvent = new CustomEvent('userProfileUpdated', {
                        detail: {
                            userName: response.userName,
                            userImage: response.imagePath
                        }
                    });
                    window.dispatchEvent(updateEvent);

                    $('#ajax-result').load('Profile.php');

                } else {
                    const errorMsg = response ? response.message : 'Unknown error occurred';
                    alert('Failed to update profile: ' + errorMsg);
                    submitBtn.html(originalText).prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                alert('Error updating profile. Please check console for details.');
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    }

});
</script>
</body>
</html>