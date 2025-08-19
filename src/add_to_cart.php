<?php
session_start();
require_once 'config.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

if(!isset($_SESSION['user_id']) || !isset($_POST['product_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'];

// Check if product already in cart
$stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
$stmt->execute([$user_id, $product_id]);
$existing_item = $stmt->fetch();

if($existing_item) {
    // Update quantity
    $stmt = $pdo->prepare("UPDATE cart SET quantity = quantity + 1 WHERE id = ?");
    $stmt->execute([$existing_item['id']]);
} else {
    // Add new item
    $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
    $stmt->execute([$user_id, $product_id]);
}

header("Location: cart.php");
exit();
?>