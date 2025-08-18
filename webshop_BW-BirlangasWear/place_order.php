<?php
session_start();
require_once 'config.php';

// Check if user is logged in and has appropriate role
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

// Only process the order if this is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Create order
        $stmt = $pdo->prepare("INSERT INTO orders (user_id) VALUES (?)");
        $stmt->execute([$_SESSION['user_id']]);
        $order_id = $pdo->lastInsertId();
        
        // Get cart items
        $stmt = $pdo->prepare("
            SELECT c.*, p.price 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $cart_items = $stmt->fetchAll();
        
        // Create order items
        $stmt = $pdo->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, price_at_time) 
            VALUES (?, ?, ?, ?)
        ");
        
        foreach($cart_items as $item) {
            $stmt->execute([
                $order_id,
                $item['product_id'],
                $item['quantity'],
                $item['price']
            ]);
        }
        
        // Clear cart
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        $pdo->commit();
        $_SESSION['order_success'] = true;
        
    } catch(Exception $e) {
        $pdo->rollBack();
        $_SESSION['order_error'] = "Failed to process your order. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        .navbar {
            background-color: #343a40;
            color: white;
            padding: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
        }
        .navbar a:hover {
            text-decoration: underline;
        }
        .container {
            text-align: center;
            margin-top: 100px;
        }
        .message {
            font-size: 1.5em;
            margin-bottom: 20px;
        }
        .success {
            color: #28a745;
        }
        .error {
            color: #dc3545;
        }
        .button {
            margin-top: 20px;
            padding: 10px 20px;
            font-size: 1em;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="logo">ShopName</div>
        <div>
            <a href="index.php">Home</a>
            <a href="cart.php">Cart</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    <div class="container">
        <?php if(isset($_SESSION['order_success'])): ?>
            <div class="message success">Order placed successfully! Thank you for your purchase.</div>
            <button class="button" onclick="window.location.href='index.php'">Continue Shopping</button>
            <?php unset($_SESSION['order_success']); ?>
        <?php elseif(isset($_SESSION['order_error'])): ?>
            <div class="message error"><?php echo htmlspecialchars($_SESSION['order_error']); ?></div>
            <button class="button" onclick="window.location.href='cart.php'">Return to Cart</button>
            <?php unset($_SESSION['order_error']); ?>
        <?php elseif($_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
            <div class="message error">Invalid access. Please use cart to place orders.</div>
            <button class="button" onclick="window.location.href='cart.php'">Return to Cart</button>
        <?php endif; ?>
    </div>
</body>
</html>