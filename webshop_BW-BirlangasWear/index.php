<?php
session_start();
require 'config.php';

// Handle search
$search = isset($_GET['search']) ? $_GET['search'] : '';
$where = '';
$params = [];

if($search) {
    $where = "WHERE MATCH(name, description, search_tags) AGAINST (? IN BOOLEAN MODE)";
    $params[] = $search;
}

// Fetch products with search
$stmt = $pdo->prepare("SELECT * FROM products $where");
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BW</title>
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
        /* Reset and base styles */
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

        /* Enhanced Navigation Bar */
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

        /* Hero Section */
        .hero {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('/api/placeholder/1200/300');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            padding: 60px 20px;
            margin-bottom: 40px;
        }

        .hero h1 {
            font-size: 2.5em;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .hero p {
            font-size: 1.2em;
            max-width: 600px;
            margin: 0 auto;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }

        /* Product Grid Enhancement */
        .product-section {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .section-title {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
            font-size: 2em;
            position: relative;
        }

        .section-title::after {
            content: '';
            display: block;
            width: 50px;
            height: 3px;
            background: #3498db;
            margin: 10px auto;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
            padding: 20px;
        }

        .product-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .product-image {
            width: 100%;
            height: 200px;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-card h3 {
            color: #2c3e50;
            margin: 10px 0;
            font-size: 1.2em;
        }

        .product-card p {
            color: #666;
            margin: 10px 0;
        }

        .price {
            font-size: 1.3em;
            color: #2c3e50;
            font-weight: bold;
            margin: 15px 0;
        }

        .add-to-cart {
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            font-size: 1em;
        }

        .add-to-cart:hover {
            background-color: #2980b9;
            transform: scale(1.05);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2em;
            }
            
            .hero p {
                font-size: 1em;
            }

            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
            }
        }
    </style>
</head>
<body>

    <div class="nav-bar">
        
    <div class="search-container" style="max-width: 800px; margin: 20px auto;">
        <form method="GET" class="search-form" style="display: flex; gap: 10px;">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Search products..." style="flex: 1; padding: 10px;">
            <button type="submit" style="padding: 10px 20px;">Search</button>
        </form>
    </div>

        <div class="nav-left">
            <a href="index.php">Home</a>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="cart.php">Cart</a>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <?php endif; ?>
        </div>
        
        <div class="nav-right">
            <?php if(isset($_SESSION['user_id'])): ?>
                <div class="settings-container">
                    <button onclick="toggleSettings()" class="settings-button">
                        ⚙️
                    </button>
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

    <div class="hero">
        <h1>Welcome to BW, Birlanga's Wear</h1>
        <p>Discover the latest trends in fashion with our exclusive collection</p>
    </div>

    <div class="product-section">
        <h2 class="section-title">Our Products</h2>
        <div class="product-grid">
            <?php foreach($products as $product): ?>
                <div class="product-card">
                    <div class="product-image">
                        <?php if(!empty($product['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($product['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 style="max-width: 65%; height: auto;">
                        <?php else: ?>
                            <img src="/api/placeholder/250/200" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 style="max-width: 65%; height: auto;">
                        <?php endif; ?>
                    </div>
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p><?php echo htmlspecialchars($product['description']); ?></p>
                    <div class="price">$<?php echo number_format($product['price'], 2); ?></div>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <form action="add_to_cart.php" method="POST">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <button type="submit" class="add-to-cart">Add to Cart</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>