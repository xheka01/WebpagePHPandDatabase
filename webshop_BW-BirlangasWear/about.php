<?php
session_start();
require_once 'config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us</title>
    <script>
        function toggleSettings() {
            const settingsMenu = document.getElementById('settingsMenu');
            settingsMenu.style.display = settingsMenu.style.display === 'none' ? 'block' : 'none';
        }

        function confirmDelete() {
            const confirmation = prompt("Type CONFIRM to delete your account:");
            if (confirmation === "CONFIRM") {
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: #f7f7f7;
            line-height: 1.6;
        }

        /* Navigation Bar */
        .nav-bar {
            background: linear-gradient(to right, #2c3e50, #3498db);
            padding: 15px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-bar a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 5px 10px;
            border-radius: 4px;
        }

        .nav-bar a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        /* Settings Menu Styling */
        .settings-container {
            position: relative;
            display: inline-block;
        }

        .settings-menu {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 180px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 8px;
            margin-top: 10px;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .settings-menu a, .settings-menu form {
            color: #333;
            padding: 12px 20px;
            text-decoration: none;
            display: block;
            transition: all 0.2s ease;
        }

        .settings-menu a:hover {
            background-color: #f8f9fa;
            padding-left: 25px;
        }

        .settings-button {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            padding: 5px 15px;
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }

        .settings-button:hover {
            transform: rotate(45deg);
        }

        /* About page specific styles */
        .about-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .about-links {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 20px;
        }

        .about-link {
            text-align: center;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
        }

        .about-link:hover {
            background-color: #f5f5f5;
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="nav-bar">
        <div class="nav-left">
            <a href="index.php">Home</a>
            <?php if(isset($_SESSION['user_id'])): ?>
                <?php if($_SESSION['role'] === 'admin'): ?>
                    <a href="admin.php">Admin Panel</a>
                <?php endif; ?>
                <a href="cart.php">Cart</a>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <?php endif; ?>
        </div>
        
        <div class="nav-right">
            <?php if(isset($_SESSION['user_id'])): ?>
                <div class="settings-container">
                    <button onclick="toggleSettings()" class="settings-button">⚙️</button>
                    <div id="settingsMenu" class="settings-menu">
                        <a href="logout.php">Logout</a>
                        <form id="deleteForm" action="delete_account.php" method="POST" style="margin: 0;">
                            <a href="#" onclick="confirmDelete()">Delete Account</a>
                        </form>
                        <a href="about.php">About Us</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="about-container">
        <h1>About Us</h1>
        <p>Welcome to our clothing shop! Learn more about us through the following links:</p>
        
        <div class="about-links">
            <a href="https://www.facebook.com" class="about-link" target="_blank">
                <h3>Facebook</h3>
                <p>Follow us on Facebook for the latest updates and promotions</p>
            </a>
            
            <a href="https://www.twitter.com" class="about-link" target="_blank">
                <h3>Twitter</h3>
                <p>Join our Twitter community for fashion tips and news</p>
            </a>
            
            <a href="https://www.instagram.com" class="about-link" target="_blank">
                <h3>Instagram</h3>
                <p>Check out our latest styles and fashion inspiration</p>
            </a>
        </div>
    </div>
</body>
</html>