<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2ENTRAL - Components</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;600;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <style>
        :root {
            --text-dark: #212B36;
            --text-grey: #637381;
            /* 沿用你的 Logo 渐变色 */
            --grad-pink: linear-gradient(135deg, #ef5350 0%, #ab47bc 100%);
            --grad-blue: linear-gradient(135deg, #23b6e6 0%, #0288d1 100%);
            --grad-green: linear-gradient(135deg, #9ccc65 0%, #66bb6a 100%);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #F4F6F8;
            height: 100vh;
            display: flex; justify-content: center; align-items: center; gap: 20px;
        }

        /* 演示按钮样式 */
        button {
            padding: 12px 24px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; font-family: 'Kanit'; font-size: 1rem; color: white; transition: transform 0.2s;
        }
        button:hover { transform: translateY(-2px); }

        /* =========================================
           1. TOAST DESIGN (提示条)
           ========================================= */
        
        .toast-container {
            position: fixed; top: 24px; right: 24px;
            display: flex; flex-direction: column; gap: 12px;
            z-index: 9999;
        }

        .toast {
            background: white;
            min-width: 300px;
            padding: 16px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            display: flex; align-items: center; gap: 12px;
            position: relative;
            overflow: hidden;
            /* 进场动画：从右侧滑入 + 弹性 */
            animation: slideInRight 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
            border-left: 4px solid transparent; /* 留给渐变色 */
        }

        /* 离开动画 */
        .toast.hiding {
            animation: fadeOutRight 0.4s forwards;
        }

        /* 图标圆圈 */
        .toast-icon {
            width: 32px; height: 32px; border-radius: 50%;
            display: flex; justify-content: center; align-items: center;
            flex-shrink: 0; font-size: 18px;
        }

        .toast-content h4 { margin: 0; font-family: 'Kanit'; font-size: 0.95rem; color: var(--text-dark); }
        .toast-content p { margin: 2px 0 0; font-size: 0.8rem; color: var(--text-grey); }

        /* 关闭按钮 */
        .toast-close {
            margin-left: auto; color: #999; cursor: pointer; font-size: 18px;
        }
        .toast-close:hover { color: #333; }

        /* --- 样式变体 --- */
        
        /* 成功 (Success) - 绿色 */
        .toast-success { border-left-color: #66bb6a; }
        .toast-success .toast-icon { background: rgba(102, 187, 106, 0.15); color: #2e7d32; }
        
        /* 错误 (Error) - 粉色 */
        .toast-error { border-left-color: #ef5350; }
        .toast-error .toast-icon { background: rgba(239, 83, 80, 0.15); color: #c62828; }

        /* =========================================
           2. MODAL DESIGN (弹窗)
           ========================================= */

        /* 遮罩层 (背景模糊) */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(26, 37, 48, 0.6); /* 深色半透明 */
            backdrop-filter: blur(4px); /* 毛玻璃效果，看起来很高级 */
            z-index: 1000;
            display: flex; justify-content: center; align-items: center;
            opacity: 0; visibility: hidden;
            transition: all 0.3s;
        }

        /* 激活状态 */
        .modal-overlay.active { opacity: 1; visibility: visible; }

        /* 弹窗主体 */
        .modal-box {
            background: white;
            width: 90%; max-width: 420px;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            padding: 32px;
            text-align: center;
            transform: scale(0.9); opacity: 0;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .modal-overlay.active .modal-box { transform: scale(1); opacity: 1; }

        /* 弹窗内容 */
        .modal-icon-large {
            width: 64px; height: 64px; border-radius: 50%;
            background: rgba(2, 136, 209, 0.1); color: #0288d1;
            font-size: 32px;
            display: flex; justify-content: center; align-items: center;
            margin: 0 auto 20px;
        }
        .modal-title { font-family: 'Kanit'; font-size: 1.5rem; margin-bottom: 8px; color: var(--text-dark); }
        .modal-desc { font-size: 0.9rem; color: var(--text-grey); margin-bottom: 32px; line-height: 1.5; }

        /* 按钮组 */
        .modal-actions { display: flex; gap: 12px; }
        .modal-btn { flex: 1; padding: 12px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; font-family: 'Inter'; }
        
        .btn-confirm { background: var(--grad-blue); color: white; }
        .btn-confirm:hover { box-shadow: 0 4px 12px rgba(2, 136, 209, 0.3); }
        
        .btn-cancel { background: white; border: 1px solid #ddd; color: #666; }
        .btn-cancel:hover { background: #f9f9f9; border-color: #ccc; }

        /* 动画关键帧 */
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes fadeOutRight {
            to { opacity: 0; transform: translateX(50px); }
        }
    </style>
</head>
<body>

    <div style="display:flex; flex-direction:column; gap:20px; align-items:center;">
        <h2 style="font-family:'Kanit'; color:#1A2530;">2ENTRAL UI Components</h2>
        <div style="display:flex; gap:10px;">
            <button onclick="showToast('success')" style="background: var(--grad-green);">Show Success Toast</button>
            <button onclick="showToast('error')" style="background: var(--grad-pink);">Show Error Toast</button>
        </div>
        <div>
            <button onclick="openModal()" style="background: var(--grad-blue);">Open Confirmation Modal</button>
        </div>
    </div>


    <div class="toast-container" id="toastContainer">
        </div>


    <div class="modal-overlay" id="confirmModal">
        <div class="modal-box">
            <div class="modal-icon-large">
                <i class="ph-fill ph-package"></i>
            </div>
            <h3 class="modal-title">Restock Item?</h3>
            <p class="modal-desc">You are about to add <strong>100 units</strong> to "Yonex Astrox 99". This action will update the inventory database.</p>
            
            <div class="modal-actions">
                <button class="modal-btn btn-cancel" onclick="closeModal()">Cancel</button>
                <button class="modal-btn btn-confirm" onclick="confirmAction()">Confirm Restock</button>
            </div>
        </div>
    </div>


    <script>
        // === Toast Logic ===
        function showToast(type) {
            const container = document.getElementById('toastContainer');
            
            // 创建 Toast 元素
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            
            // 根据类型设置内容
            let iconClass = type === 'success' ? 'ph-check-circle' : 'ph-warning-circle';
            let title = type === 'success' ? 'Success' : 'Error';
            let message = type === 'success' ? 'Inventory updated successfully.' : 'Connection lost. Please retry.';

            toast.innerHTML = `
                <div class="toast-icon"><i class="ph-fill ${iconClass}"></i></div>
                <div class="toast-content">
                    <h4>${title}</h4>
                    <p>${message}</p>
                </div>
                <div class="toast-close" onclick="this.parentElement.remove()"><i class="ph-bold ph-x"></i></div>
            `;

            // 添加到页面
            container.appendChild(toast);

            // 3秒后自动消失
            setTimeout(() => {
                toast.classList.add('hiding');
                // 动画播完后移除 DOM
                toast.addEventListener('animationend', () => {
                    toast.remove();
                });
            }, 3000);
        }

        // === Modal Logic ===
        const modal = document.getElementById('confirmModal');

        function openModal() {
            modal.classList.add('active');
        }

        function closeModal() {
            modal.classList.remove('active');
        }

        function confirmAction() {
            closeModal();
            // 模拟确认后弹出成功提示
            setTimeout(() => {
                showToast('success');
            }, 300);
        }

        // 点击背景也可以关闭
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });
    </script>

</body>
</html>