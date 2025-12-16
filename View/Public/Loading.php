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
    <link rel="icon" type="image/png" href="../../Assets/Icon/2ENTRALIcon.png">
    <link rel="stylesheet" href="../../Assets/CSS/style.css">
    <title>Loading...</title>
    <style>
        body {
            background: radial-gradient(100% 100% at 50% 100%, #34495E 0%, #10151B 100%);
            height: 100vh;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            overflow: hidden;
            font-family: var(--font-heading);
            margin: 0;
            padding: 0;
        }

        .loading-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .bounce-container {
            display: flex;
            flex-direction: column;
            width: 80px;
            height: 80px;
            position: relative;
            z-index: 2;
            animation: bounce-movement 1s infinite cubic-bezier(0.28, 0.84, 0.42, 1);
        }

        .morph-item {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            position: absolute;
            top: 0; 
            left: 0;
            display: flex; 
            justify-content: center; 
            align-items: center;
            box-shadow: inset -3px -3px 8px rgba(0, 0, 0, 0.3);
            border: 2px solid rgba(0, 0, 0, 0.1);
            animation: cycle-visibility 11s infinite steps(1);
            opacity: 0;
            background: #fff;
        }

        .morph-item img { 
            width: 44px; 
            height: 44px; 
        }

        .item-1 { 
            background: #FF8C00; 
            border-color: #E65100; 
            animation-delay: 0s;
        }

        .item-2 { 
            background: linear-gradient(to bottom right, #00FFF2, #FFFFFF); 
            border-color: #00B8A9; 
            animation-delay: calc(1s * 1);
        }
        
        .item-3 { 
            background: linear-gradient(to bottom right, #FFFFFF, #000000); 
            border-color: #333333; 
            animation-delay: calc(1s * 2); }

        .item-4 { 
            background: linear-gradient(to bottom right, #03FF35, #000000); 
            border-color: #A0CC00; 
            animation-delay: calc(1s * 3); 
        }

        .item-5 { 
            background: linear-gradient(to bottom right, #FF2003, #000000); 
            border-color: #B71C1C; 
            animation-delay: calc(1s * 4); 
        }

        .item-6 { 
            background: #030BFF; 
            border-color: #0D47A1; 
            animation-delay: calc(1s * 5); 
        }

        .item-7 { 
            background: linear-gradient(to bottom right, #0B03FF, #FFF921); 
            border-color: #F9A825; 
            animation-delay: calc(1s * 6); 
        }

        .item-8 { 
            background: #969696; 
            border-color: #757575; 
            animation-delay: calc(1s * 7); 
        }

        .item-9 { 
            background: linear-gradient(to bottom right, #000000, #E37800); 
            border-color: #6D4C41; 
            animation-delay: calc(1s * 8); 
        }

        .item-10 { 
            background: linear-gradient(to bottom right, #9E9E9E, #000000); 
            border-color: #1A2530; 
            animation-delay: calc(1s * 9); 
        }

        .item-11 { 
            background: var(--brand-primary); 
            border-color: #3A8EB0; 
            animation-delay: calc(1s * 10); 
        }

        .shadow {
            margin: 0 auto;
            margin-top: 53px;
            width: 60px; 
            height: 10px; 
            background: rgba(0,0,0,0.5); 
            border-radius: 50%;
            animation: shadow-scale 1s infinite cubic-bezier(0.28, 0.84, 0.42, 1);
        }

        .welcome-text { 
            text-align: center; 
            margin-top: 40px; 
            z-index: 5; 
        }

        .welcome-text h2 { 
            font-family: 'Kanit', sans-serif; 
            color: white; 
            font-size: 1.6rem; 
            font-weight: 800; 
            letter-spacing: 2px; 
            font-style: italic; 
        }
        
        .welcome-text span { 
            color: var(--brand-primary); 
            font-size: 0.9rem; 
            margin-top: 8px; 
            font-weight: 500; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
        }

        .loading-dots::after {
            color: #FFFFFF;
            content: ' .'; 
            animation: dots 1.5s steps(5, end) infinite;
        }

        @keyframes dots { 
            0%, 20% { content: ' .'; } 
            40% { content: ' ..'; } 
            60% { content: ' ...'; } 
            80%, 100% { content: ''; }}

        @keyframes bounce-movement {
            0%, 100% { transform: translateY(0) scale(0.95, 1.05); }
            50% { transform: translateY(80px) scale(1.1, 0.9); }
        }

        @keyframes cycle-visibility {
            0% { opacity: 1; z-index: 10; }
            9.09% { opacity: 0; z-index: 1; }
            100% { opacity: 0; z-index: 1; }
        }

        @keyframes shadow-scale {
            0%, 100% { transform: scale(1); opacity: 0.2; }
            50% { transform: scale(0.6); opacity: 0.4; }
        }
    </style>
</head>
<body>
    <div class="loading-container">
        <div class="bounce-container">
            <div class="morph-item item-1">
                <img src="../../Assets/Icon/basketball.svg">
            </div>

            <div class="morph-item item-2">
                <img src="../../Assets/Icon/shuttlecock.svg">
            </div>

            <div class="morph-item item-3">
                <img src="../../Assets/Icon/soccer-ball.svg">
            </div>

            <div class="morph-item item-4">
                <img src="../../Assets/Icon/tennis-ball.svg">
            </div>

            <div class="morph-item item-5">
                <img src="../../Assets/Icon/golf.svg">
            </div>

            <div class="morph-item item-6">
                <img src="../../Assets/Icon/ping-pong.svg">
            </div>
                
            <div class="morph-item item-7">
                <img src="../../Assets/Icon/volleyball.svg">
            </div>

            <div class="morph-item item-8">
                <img src="../../Assets/Icon/baseball-set.svg">
            </div>

            <div class="morph-item item-9">
                <img src="../../Assets/Icon/rugby.svg">
            </div>

            <div class="morph-item item-10">
                <img src="../../Assets/Icon/bowling.svg">
            </div>

            <div class="morph-item item-11">
                <img src="../../Assets/Icon/cardboard-box.svg">
            </div>
        </div>

        <div class="shadow"></div>

        <div class="welcome-text">
            <h2>LOADING<span class="loading-dots"></span></h2>
            <span>Preparing Your Full Inventory</span>
        </div>
    </div>

    <iframe id="dashboard-iframe" src="/View/Auth/Dashboard.php" style="display:none;"></iframe>

    <script>
        // --- DOM Element Selection ---
        const content_frame = document.getElementById('dashboard-iframe');
        const status_text = document.getElementById('status-text');
        const sub_text = document.getElementById('sub-text');

        // --- Configuration ---
        const min_wait_time = 5000; // 3 seconds

        // --- Promises for loading ---

        // Promise 1: A minimum wait time to ensure the animation is visible
        const wait_promise = new Promise(resolve => {
            setTimeout(resolve, min_wait_time);
        });

        // Promise 2: The content of the hidden iframe to be loaded
        const load_content_promise = new Promise(resolve => {
            // Check if already loaded (e.g., from cache)
            if (content_frame.contentDocument && content_frame.contentDocument.readyState === 'complete') {
                resolve();
            } else {
                // Wait for the onload event
                content_frame.onload = () => resolve();
            }
        });

        // --- Execution ---

        // Wait for both promises to complete
        Promise.all([wait_promise, load_content_promise]).then(() => {
            // Update text to show completion
            if (status_text) {
                // Remove the dots animation by clearing the inner HTML
                status_text.innerHTML = 'COMPLETE';
            }
            if (sub_text) {
                sub_text.innerText = 'Redirecting to Dashboard...';
            }

            // Short delay before redirecting to allow user to see "COMPLETE" message
            setTimeout(() => {
                window.location.href = "/View/Auth/Dashboard.php";
            }, 500);
        }).catch(error => {
            console.error("Loading error:", error);
            if (sub_text) {
                sub_text.innerText = 'Error loading assets. Please try again.';
            }
        });
    </script>
</body>
</html>