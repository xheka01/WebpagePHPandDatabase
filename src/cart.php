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
    <link rel="stylesheet" href="css/styles.css">
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