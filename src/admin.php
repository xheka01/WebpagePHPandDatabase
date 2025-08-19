<?php
session_start();
require_once 'config.php';

// Check if user is admin
if(!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Product related handling

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'add_product':
                $image_path = null;
                if(isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $upload_dir = 'uploads/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
                    
                    if(in_array($file_extension, $allowed_extensions)) {
                        $image_path = $upload_dir . uniqid() . '.' . $file_extension;
                        move_uploaded_file($_FILES['image']['tmp_name'], $image_path);
                    }
                }
                
                $stmt = $pdo->prepare("INSERT INTO products (name, description, image_path, price, search_tags) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$_POST['name'], $_POST['description'], $image_path, $_POST['price'], $_POST['search_tags']]);
                break;
                
            case 'edit_product':
                $image_path = null;
                if(isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $upload_dir = 'uploads/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
                    
                    if(in_array($file_extension, $allowed_extensions)) {
                        // Delete old image if exists
                        $stmt = $pdo->prepare("SELECT image_path FROM products WHERE id = ?");
                        $stmt->execute([$_POST['product_id']]);
                        $old_image = $stmt->fetchColumn();
                        if($old_image && file_exists($old_image)) {
                            unlink($old_image);
                        }
                        
                        $image_path = $upload_dir . uniqid() . '.' . $file_extension;
                        move_uploaded_file($_FILES['image']['tmp_name'], $image_path);
                    }
                }
                
                if($image_path) {
                    $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, image_path = ?, price = ?, search_tags = ? WHERE id = ?");
                    $stmt->execute([$_POST['name'], $_POST['description'], $image_path, $_POST['price'], $_POST['search_tags'], $_POST['product_id']]);
                } else {
                    $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, search_tags = ? WHERE id = ?");
                    $stmt->execute([$_POST['name'], $_POST['description'], $_POST['price'], $_POST['search_tags'], $_POST['product_id']]);
                }
                break;
                
            case 'delete_product':
                // Delete product image before deleting product
                $stmt = $pdo->prepare("SELECT image_path FROM products WHERE id = ?");
                $stmt->execute([$_POST['product_id']]);
                $image_path = $stmt->fetchColumn();
                if($image_path && file_exists($image_path)) {
                    unlink($image_path);
                }
                
                $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                $stmt->execute([$_POST['product_id']]);
                break;
        }
    }
}

// Handle user related forms
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(isset($_POST['action'])) {
        switch($_POST['action']) {     
            case 'add_user':
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
                $stmt->execute([$_POST['username'], $password, $_POST['role']]);
                break;

            case 'delete_user':
                // Prevent deleting the last admin
                if($_POST['role'] === 'admin') {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
                    $stmt->execute();
                    if($stmt->fetchColumn() <= 1) {
                        $_SESSION['error'] = "Cannot delete the last administrator";
                        break;
                    }
                }
                
                // Delete user's cart items first
                $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
                $stmt->execute([$_POST['user_id']]);
                
                // Then delete the user
                $stmt = $pdo->prepare("UPDATE users SET is_deleted = 1 WHERE id = ?");
                $stmt->execute([$_POST['user_id']]);
                break;

            case 'update_user':
                $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                $stmt->execute([$_POST['role'], $_POST['user_id']]);
                break;
        }
        header("Location: admin.php");
        exit();
    }
}

// Fetch all products
$products = $pdo->query("SELECT * FROM products")->fetchAll();

// Fetch all users except current admin
$stmt = $pdo->prepare("SELECT * FROM users WHERE id != ?");
$stmt->execute([$_SESSION['user_id']]);
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: #f0f2f5;
            line-height: 1.6;
        }

        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .admin-header {
            background: linear-gradient(to right, #1a237e, #0d47a1);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .admin-header h1 {
            font-size: 2em;
            margin: 0;
        }

        .logout-btn {
            background-color: #ff3d00;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .logout-btn:hover {
            background-color: #dd2c00;
            transform: translateY(-2px);
        }

        .section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .section h2 {
            color: #1a237e;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e3e3e3;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }

        .card {
            background: white;
            border: 1px solid #e3e3e3;
            padding: 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: bold;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group textarea {
            height: 100px;
            resize: vertical;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #1a237e;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .user-card {
            background: linear-gradient(to right bottom, #ffffff, #f8f9fa);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .user-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .role-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }

        .role-admin {
            background-color: #ffd700;
            color: #000;
        }

        .role-user {
            background-color: #90caf9;
            color: #000;
        }

        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #c62828;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>Admin Panel</h1>
            <form action="logout.php" method="POST" style="display: inline;">
                <button type="submit" class="logout-btn">Log out to the store</button>
            </form>
        </div>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="section">
            <h2>Add New User</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_user">
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>Role:</label>
                    <select name="role">
                        <option value="user">User</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Add User</button>
            </form>
        </div>

        <div class="section">
            <h2>Manage Users</h2>
            <div class="grid">
                <?php foreach($users as $user): ?>
                    <div class="user-card">
                        <h3>
                            <?php echo htmlspecialchars($user['username']); ?>
                            <span class="role-badge role-<?php echo $user['role']; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </h3>
                        <form method="POST" style="margin-top: 15px;">
                            <input type="hidden" name="action" value="update_user">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <div class="form-group">
                                <label>Change Role:</label>
                                <select name="role">
                                    <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Role</button>
                        </form>
                        <form method="POST" style="margin-top: 10px;">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <input type="hidden" name="role" value="<?php echo $user['role']; ?>">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">Delete User</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="section">
            <h2>Add New Product</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_product">
                <div class="form-group">
                    <label>Name:</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Description:</label>
                    <textarea name="description" required></textarea>
                </div>
                <div class="form-group">
                    <label>Image:</label>
                    <input type="file" name="image" accept="image/*">
                </div>
                <div class="form-group">
                    <label>Price:</label>
                    <input type="number" step="0.01" name="price" required>
                </div>
                <div class="form-group">
                    <label>Search Tags (comma separated):</label>
                    <input type="text" name="search_tags">
                </div>
                <button type="submit" class="btn btn-primary">Add Product</button>
            </form>
        </div>

        <div class="section">
            <h2>Manage Products</h2>
            <div class="grid">
                <?php foreach($products as $product): ?>
                    <div class="card">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="edit_product">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <div class="form-group">
                                <label>Name:</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Description:</label>
                                <textarea name="description" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Image:</label>
                                <?php if($product['image_path']): ?>
                                    <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="Current product image" style="max-width: 200px; display: block; margin-bottom: 10px;">
                                <?php endif; ?>
                                <input type="file" name="image" accept="image/*">
                            </div>
                            <div class="form-group">
                                <label>Price:</label>
                                <input type="number" step="0.01" name="price" value="<?php echo $product['price']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Search Tags:</label>
                                <input type="text" name="search_tags" value="<?php echo htmlspecialchars($product['search_tags']); ?>">
                            </div>
                            <button type="submit" class="btn btn-primary">Update</button>
                        </form>
                        <form method="POST" style="margin-top: 10px;">
                            <input type="hidden" name="action" value="delete_product">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this product? This action cannot be undone.')">Delete Product</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>