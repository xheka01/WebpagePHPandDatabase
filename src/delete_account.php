<?php
session_start();
require_once 'config.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Check if user is an admin trying to delete their account
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if($user['role'] === 'admin') {
    // Check if this is the last admin
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $stmt->execute();
    $admin_count = $stmt->fetchColumn();
    
    if($admin_count <= 1) {
        header("Location: index.php?error=cannot_delete_last_admin");
        exit();
    }
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Delete cart items
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    
    // Delete user
    $stmt = $pdo->prepare("UPDATE users SET is_deleted = 1 WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    
    // Commit transaction
    $pdo->commit();
    
    // Destroy session
    session_destroy();
    
    header("Location: index.php?msg=account_deleted");
    exit();
} catch(PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    die("Error deleting account: " . $e->getMessage());
}
?>