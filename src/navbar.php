<?php
// navbar.php o includes/navbar.php
// Asume sesión iniciada
$current = basename($_SERVER['SCRIPT_NAME'] ?? '');
$hideByDefault = in_array($current, ['login.php','register.php', 'cart.php']);
$showSearch = isset($showSearch) ? (bool)$showSearch : !$hideByDefault;
?>
<link rel="stylesheet" href="css/navbar.css">
<div class="nav-bar">
    <?php if ($showSearch): ?>
    <div class="search-container">
        <form method="GET" action="index.php" class="search-form">
            <input type="text" name="search"
                   value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>"
                   placeholder="Search products..." aria-label="Buscar productos">
            <button type="submit">Search</button>
        </form>
    </div>
    <?php endif; ?>

    <div class="nav-left">
        <a href="index.php">Home</a>
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="cart.php">Cart</a>
            <span>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
        <?php endif; ?>
    </div>

    <div class="nav-right">
        <?php if(isset($_SESSION['user_id'])): ?>
            <div class="settings-container">
                <button onclick="toggleSettings()" class="settings-button">⚙️</button>
                <div id="settingsMenu" class="settings-menu" style="display:none">
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
