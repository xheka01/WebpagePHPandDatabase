<?php
session_start();
require_once 'config.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

if(!isset($_SESSION['user_id']) || !isset($_POST['cart_id'])) {
    header("Location: cart.php");
    exit();
}

$stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
$stmt->execute([$_POST['cart_id'], $_SESSION['user_id']]);

header("Location: cart.php");
exit();
?>