<?php
session_start();

// Access control: only allow logged-in users
if (!isset($_SESSION['user'])) {
    header('Location: /View/Public/AccessDenied.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loading...</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            height: 100vh;
            margin: auto;
        }

        section {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #F8FBFD;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            transition: opacity 0.6s ease-out, visibility 0.6s;
        }

        .image-wrapper img {
            display: block;
            width: 180px;
            height: 180px;
            animation: float 3s ease-in-out infinite;
        }

        .shadow-image-wrapper::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            width: 50%;
            transform: translateX(-50%);
            height: 8px;
            background: #a0a0a033;
            border-radius: 100%;
            z-index: 999;
        }

        h1 {
            margin: 30px 0px;
            color: #1E2A35;
        }

        .loader {
            width: 60px; height: 60px;
            border: 4px solid rgba(75, 163, 195, 0.2);
            border-top: 4px solid #4BA3C3;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }

        .loading-text {
            color: #5F7383;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .progress-bar {
            width: 280px; 
            height: 4px; 
            background: #e1e4e8; 
            border-radius: 4px; 
            overflow: hidden;
        }

        .progress-bar-filling {
            height: 100%; 
            background: #4BA3C3; 
            width: 0%; 
            border-radius: 4px; 
            transition: width 0.3s;
        }

        #percent-text {
            font-size: 12px;
            color: #4BA3C3;
            margin-top: 8px;
            font-weight: bold;
        }

        @keyframes float {
            0%   { transform: translateY(0px); }
            50%  { transform: translateY(-6px); }
            100% { transform: translateY(0px); }
        }

        @keyframes spin { 
            0% { transform: rotate(0deg); } 
            100% { transform: rotate(360deg); } 
        }
    </style>
</head>
<body>
    <section id="loader-screen">
        <div class="image-wrapper">
            <img src="/Assets/Icon/2ENTRAL-1.png">
        </div>
        <div>
            <h1>Welcome Back, <?php echo $_SESSION['user']['name'] ?></h1>
        </div>
        <div class="loader"></div>
        <div class="loading-text" id="status-text">
            System is initializing...
        </div>
        <div class="progress-bar">
            <div class="progress-bar-filling" id="progress"></div>
        </div>
        <div id="percent-text">0%</div>
    </section>

    <iframe id="dashboard-iframe" src="/View/Auth/Dashboard.php" style="display:none;"></iframe>

    <script>
        const loader_screen = document.getElementById('loader-screen');
        const content_frame = document.getElementById('dashboard-iframe');
        const progress_bar = document.getElementById('progress');
        const status_text = document.getElementById('status-text');
        const percent_text = document.getElementById('percent-text');
        
        const wait_time = 3000; // Minimum wait time in milliseconds

        const wait_five_seconds = new Promise((resolve) => {
            let elapsed = 0;
            const update_interval = 50; // 每50毫秒更新一次进度条
            
            const timer = setInterval(() => {
                elapsed += update_interval;
                
                // 计算进度百分比 (elapsed / 5000)
                let percent = Math.min((elapsed / wait_time) * 100, 99); // 最多跑到 99%
                
                // 更新 UI
                progress_bar.style.width = percent + '%';
                percent_text.innerText = Math.floor(percent) + '%';
                // 如果超过5秒，结束定时器并解决 Promise
                if (elapsed >= wait_time) {
                    clearInterval(timer);
                    resolve();
                }
            }, update_interval);
        });

        const load_content = new Promise((resolve) => {
            if (content_frame.contentDocument && content_frame.contentDocument.readyState === 'complete') {
                resolve();
            } else {
                content_frame.onload = function() {
                    resolve();
                };
            }
        });

        Promise.all([wait_five_seconds, load_content]).then(() => {
            progress_bar.style.width = '100%';
            percent_text.innerText = '100%';
            status_text.innerText = 'Initialization Complete!';

            setTimeout(() => {
                window.location.href = "/View/Auth/Dashboard.php";
            }, 500);
        });
    </script>
</body>
</html>