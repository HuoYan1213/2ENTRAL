<?php
session_start();

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

switch ($uri) {
    case '/auth/googleLogin':
        require 'Controller/PublicController.php';
        $controller = new PublicController();
        $controller->googleLogin();
        exit();

   case '/auth/callback':
        require 'Controller/PublicController.php';
        $controller = new PublicController();
        $controller->callback();
        exit();
}
?>  

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="Assets/Icon/2ENTRALIcon.png">
    <link rel="stylesheet" href="Assets/CSS/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <title>2ENTRAL</title>
    <style>
        * {
            margin: 0;
            padding: 0;
        }

        body, html {
            height: 100vh;
            width: 100%;
            display: flex;
            background: var(--background-gray);
            color: var(--text-main);
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            position: relative;
            align-items: center;
            justify-content: center;
        }

        .dynamic-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #4BA3C3 0%, #2980b9 100%);
            clip-path: polygon(0 0, 65% 0, 45% 100%, 0% 100%);
            z-index: 1;
        }

        .stripe-accent {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--brand-accent);
            clip-path: polygon(66% 0, 68% 0, 48% 100%, 46% 100%);
            z-index: 2;
        }

        .mesh-texture {
            position: absolute;
            top: 0;
            left: 0;
            width: 80%;
            height: 100%;
            background-image: radial-gradient(rgba(255,255,255,0.2) 2px, transparent 2px);
            background-size: 20px 20px;
            z-index: 2;
        }

        .login-container {
            z-index: 10;
            display: flex;
            width: 95%;
            max-width: 900px;
            height: 500px;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
            border-radius: 12px;
            overflow: hidden;
        }

        .brand-section {
            flex: 1;
            padding: 30px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            background: var(--background-white);
            gap: 15px;
        }

        .background-number {
            position: absolute;
            top: -35px;
            left: -20px;
            font-family: var(--font-heading);
            font-size: 15rem;
            font-weight: 900;
            font-style: italic;
            color: #F0F4F8;
            z-index: 0;
            line-height: 1;
        }

        .bran-content {
            z-index: 1;
        }

        .brand-logo img {
            width: 250px;
        }

        .brand-section span {
            color: var(--text-light);
            font-family: var(--font-body);
            font-size: 14px;
            font-weight: 800;
            font-style: italic;
            letter-spacing: 2px;
            line-height: 0.9;
            text-transform: uppercase;
            margin-left: 10px;
        }

        .login-section {
            flex: 1;
            background: var(--background-white);
            padding: 35px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            border-left: 1px solid #EEEEEE;
        }

        .login-header h1 {
            font-family: var(--font-heading);
            font-size: 36px;
            font-weight: 600;
            color: var(--brand-dark);
            margin-bottom: 50px;
        }

        .gsi-material-button {
            background: #F8FBFD;
            border: 2px solid #afafafff;
            border-radius: 5px;
            padding: 12px 30px;
            transition: box-shadow 0.2s ease;
        }

        .gsi-material-button-content-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-family:Arial, Helvetica, sans-serif;
            gap: 5px;
        }

        .gsi-material-button svg {
            width: 20px;
            height: 20px;
            margin-right: 10px;
        }

        .gsi-material-button:hover {
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
            cursor: pointer;
        }

        .gsi-material-button-contents {
            color: #000000;
        }

        .alert-box {
            margin-top: 30px;
            background: #F0F4F8;
            padding: 15px;
            border-radius: 4px;
            font-size: 12px;
            color: var(--text-main);
            border-left: 4px solid var(--brand-accent);
            font-family: var(--font-body);
        }

        .alert-box strong {
            font-size: 16px;
            font-family: var(--font-heading);
        }
    </style>
</head>
<body>
    
    <div class="dynamic-background"></div>
    <div class="stripe-accent"></div>
    <div class="mesh-texture"></div>

    <section class="login-container">
        <div class="brand-section">
            <div class="banckground-number">88</div>
            <div class="brand-logo">
                <img src="Assets/Icon/2ENTRAL-2.png">
            </div>
            <span>2ENTRAL Inventory Control System</span>
        </div>

        <div class="login-section">
            <div class="login-header">
                <h1>Staff Login</h1>
            </div>

            <button type="button" class="gsi-material-button" onclick="window.location.href='/auth/googleLogin'">
                <div class="gsi-material-button-content-wrapper">
                    <div class="gsi-material-button-icon">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" xmlns:xlink="http://www.w3.org/1999/xlink" style="display: block;">
                            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"></path>
                            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"></path>
                            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"></path>
                            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"></path>
                            <path fill="none" d="M0 0h48v48H0z"></path>
                        </svg>
                    </div>
                    <span class="gsi-material-button-contents">Sign in with Google</span>
                </div>
            </button>

            <div class="alert-box">
                <strong>Access Control: </strong><br>
                Restricted to <b>2ENTRAL</b> staff members only.
            </div>
        </div>
    </section>
</body>
</html>