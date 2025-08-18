<?php
session_start();
require_once 'config.php';

// Check if user is logged in and has appropriate role
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT c.*, p.name, p.price, p.description, p.image_path 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
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

        .cart-section {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .cart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
            padding: 20px;
        }

        .cart-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .cart-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .cart-image {
            width: 100%;
            height: 200px;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .section-title {
            text-align: center;
            margin: 30px 0;
            color: #2c3e50;
            font-size: 2em;
        }

        .cart-total {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            text-align: right;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .cart-actions {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1em;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
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

    <div class="cart-section">
        <h2 class="section-title">Your Shopping Cart</h2>

        <?php if(empty($cart_items)): ?>
            <div style="text-align: center; padding: 40px;">
                <h3>Your cart is empty</h3>
                <a href="index.php" class="btn btn-primary" style="margin-top: 20px;">Continue Shopping</a>
            </div>
        <?php else: ?>
            <div class="cart-grid">
                <?php 
                $total = 0;
                foreach($cart_items as $item): 
                    $total += $item['price'] * $item['quantity'];
                ?>
                    <div class="cart-card">
                        <div class="product-image">
                            <?php if(!empty($item['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($item['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                     style="max-width: 100%; height: auto;">
                            <?php else: ?>
                                <img src="/api/placeholder/250/200" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                     style="max-width: 100%; height: auto;">
                            <?php endif; ?>
                        </div>
                        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                        <p><?php echo htmlspecialchars($item['description']); ?></p>
                        <div class="price">$<?php echo number_format($item['price'], 2); ?></div>
                        <p>Quantity: <?php echo $item['quantity']; ?></p>
                        <p>Subtotal: $<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                        <form action="remove_from_cart.php" method="POST" style="margin-top: 15px;">
                            <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                            <button type="submit" class="btn btn-danger">Remove</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-total">
                <h3>Total: $<?php echo number_format($total, 2); ?></h3>
            </div>

            <div class="cart-actions">
                <a href="index.php" class="btn btn-primary">Continue Shopping</a>
                <form action="place_order.php" method="POST" style="display: inline;">
                    <button type="submit" class="btn btn-primary">Place Order</button>
                    
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>