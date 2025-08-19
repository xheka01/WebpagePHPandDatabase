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
    <link rel="stylesheet" href="css/styles.css">
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