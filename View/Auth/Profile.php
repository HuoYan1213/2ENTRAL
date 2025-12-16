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

<link href="../../Assets/CSS/profile.css" rel="stylesheet">
<script src="https://unpkg.com/@phosphor-icons/web"></script>

<div class="toast-container" id="toastContainer"></div>
<div class="modal-overlay" id="confirmModal">
    <div class="modal-box">
        <div class="modal-icon-large">
            <i class="ph-fill ph-question"></i>
        </div>
        <h3 class="modal-title">Confirm Action</h3>
        <p class="modal-desc">Are you sure you want to proceed?</p>
        
        <div class="modal-actions">
            <button class="modal-btn btn-cancel" onclick="closeConfirmModal()">Cancel</button>
            <button class="modal-btn btn-confirm" onclick="executePendingAction()">Confirm</button>
        </div>
    </div>
</div>

<div class="profile-container">
    
    <!-- Left: Player Card -->
    <div class="player-card">
        <div class="card-bg">
            <div style="position:absolute; right:15px; top:15px; background:rgba(255,255,255,0.2); padding:4px 10px; border-radius:20px; color:white; font-size:0.75rem; display:flex; align-items:center; gap:5px;">
                <div style="width:8px; height:8px; background:#66bb6a; border-radius:50%;"></div> <?= htmlspecialchars($CURRENT_STATUS) ?>
            </div>
        </div>
        
        <div class="profile-avatar" onclick="document.getElementById('avatarInput').click()" title="Click to change avatar">
            <?php 
            $imageSrc = '../../Assets/Image/User/' . $CURRENT_IMAGE;
            $placeholderUrl = 'https://ui-avatars.com/api/?name=' . urlencode($CURRENT_NAME) . '&background=3498db&color=fff';
            $isDefaultImage = empty($CURRENT_IMAGE) || $CURRENT_IMAGE === 'default.png' || strpos($CURRENT_IMAGE, 'default_user_') !== false;
            ?>
            <img src="<?= (!$isDefaultImage ? $imageSrc . '?t=' . time() : $placeholderUrl) ?>" id="cardAvatarImg" onerror="this.src='<?= $placeholderUrl ?>'">
        </div>

        <div class="player-name"><?= htmlspecialchars($CURRENT_NAME) ?></div>
        <div class="player-role"><?= htmlspecialchars($CURRENT_ROLE) ?></div>

        <div class="player-stats">
            <div class="stat-box">
                <div class="stat-num"><?= htmlspecialchars($formattedJoinDate) ?></div>
                <div class="stat-label">Joined</div>
            </div>
            <div class="stat-box">
                <div class="stat-num">
                    <?php 
                    if (!empty($JOIN_DATE) && $JOIN_DATE !== 'N/A') {
                        $join = new DateTime($JOIN_DATE);
                        $now = new DateTime();
                        $interval = $join->diff($now);
                        echo ($interval->y > 0) ? $interval->y . 'yr' : (($interval->m > 0) ? $interval->m . 'mo' : $interval->d . 'd');
                    } 
                    else {
                        echo '-';
                    }
                    ?>
                </div>
                <div class="stat-label">Tenure</div>
            </div>
        </div>
    </div>

    <!-- Right: Settings Card -->
    <div class="settings-card">
        <h3>Account Details</h3>
        
        <form id="profileForm">
            <input type="hidden" id="currentImagePath" name="currentImagePath" value="<?= htmlspecialchars($CURRENT_IMAGE) ?>">
            <input type="file" id="avatarInput" name="avatar" accept="image/*" style="display:none;">
            <input type="hidden" id="deleteAvatar" name="deleteAvatar" value="0">

            <div class="form-grid">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($CURRENT_NAME) ?>">
                </div>
                <div class="form-group">
                    <label>User ID</label>
                    <input type="text" value="<?= htmlspecialchars($CURRENT_ID) ?>" disabled style="background:#f9f9f9; color:#888;">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($CURRENT_EMAIL) ?>">
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <input type="text" value="Logistics & Warehouse" disabled style="background:#f9f9f9; color:#888;">
                </div>
            </div>

            <h3 class="section-header">Preferences</h3>
            
            <div class="toggle-row">
                <div class="toggle-text">
                    <h4>Dark Mode</h4>
                    <p>Switch between light and dark themes interface.</p>
                </div>
                <label class="switch">
                    <input type="checkbox" id="darkModeToggle">
                    <span class="slider"></span>
                </label>
            </div>

            <div class="profile-actions">
                <button type="submit" class="btn-save">Save Changes</button>
                <button type="button" class="btn-logout">Log Out</button>
            </div>
        </form>
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
            // Check if the element is still in the DOM before removing
            if (toast.parentElement) {
                toast.remove();
            }
        });
    }, 3000);
}

let pendingAction = null;

function showConfirmModal(type, title, message, actionCallback) {
    const modal = document.getElementById('confirmModal');
    const icon = modal.querySelector('.modal-icon-large i');
    const titleEl = modal.querySelector('.modal-title');
    const descEl = modal.querySelector('.modal-desc');
    const confirmBtn = modal.querySelector('.btn-confirm');

    titleEl.textContent = title;
    descEl.innerHTML = message;
    
    // Customize based on type
    if (type === 'logout') {
        icon.className = 'ph-fill ph-sign-out';
        icon.parentElement.style.background = 'rgba(239, 83, 80, 0.1)';
        icon.parentElement.style.color = '#ef5350';
        confirmBtn.style.background = 'var(--grad-pink)';
        confirmBtn.textContent = 'Logout';
    } else {
        icon.className = 'ph-fill ph-floppy-disk';
        icon.parentElement.style.background = 'rgba(2, 136, 209, 0.1)';
        icon.parentElement.style.color = '#0288d1';
        confirmBtn.style.background = 'var(--grad-blue)';
        confirmBtn.textContent = 'Confirm';
    }

    pendingAction = actionCallback;
    modal.classList.add('active');
}

function closeConfirmModal() {
    document.getElementById('confirmModal').classList.remove('active');
    pendingAction = null;
}

function executePendingAction() {
    if (pendingAction) pendingAction();
    closeConfirmModal();
}

$(document).ready(function() {
    // Avatar Preview Logic
    $('#avatarInput').on('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#cardAvatarImg').attr('src', e.target.result);
            }
            reader.readAsDataURL(file);
        }
    });
    
    // Dark Mode Logic
    if (localStorage.getItem('darkMode') === 'enabled') {
        $('#darkModeToggle').prop('checked', true);
    }
    $('#darkModeToggle').on('change', function() {
        if ($(this).is(':checked')) {
            $('body').addClass('dark-mode');
            localStorage.setItem('darkMode', 'enabled');
        } else {
            $('body').removeClass('dark-mode');
            localStorage.setItem('darkMode', 'disabled');
        }
    });

    $('.btn-logout').click(function() {
        showConfirmModal('logout', 'Log Out?', 'Are you sure you want to log out of your account?', function() {
            window.location.href = '/Controller/UserController.php?action=logout';
        });
    });

    $('#profileForm').submit(function(e) {
        e.preventDefault();
        
        showConfirmModal('save', 'Save Changes?', 'Are you sure you want to update your profile information?', function() {
            saveProfileChanges();
        });
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
                    showToast('success', 'Profile updated successfully!');

                    const updateEvent = new CustomEvent('userProfileUpdated', {
                        detail: {
                            userName: response.userName,
                            userImage: response.imagePath
                        }
                    });
                    window.dispatchEvent(updateEvent);
                    
                    // No need to reload, UI updates via event.
                    submitBtn.html(originalText).prop('disabled', false);
                } else {
                    const errorMsg = response ? response.message : 'Unknown error occurred';
                    showToast('error', 'Failed to update profile: ' + errorMsg);
                    submitBtn.html(originalText).prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                showToast('error', 'Error updating profile. Please check the console.');
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    }

});
</script>
