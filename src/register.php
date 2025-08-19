<?php
session_start();
require_once 'config.php';
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $passwordPlain = $_POST['password'] ?? '';
    $password = password_hash($passwordPlain, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->execute([$username, $password]);
        header("Location: login.php");
        exit();
    } catch(PDOException $e) {
        // Evita exponer el detalle
        $error = "El nombre de usuario ya existe.";
    }
}
$showSearch = false;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>

    <!-- Estilos globales -->
    <link rel="stylesheet" href="css/register.css">

    <!-- Scripts comunes de la navbar -->
    <script src="js/navbar.js" defer></script>
</head>

<body data-auth="<?php echo isset($_SESSION['user_id']) ? '1' : '0'; ?>">
    <?php require __DIR__ . '/navbar.php'; ?>

    <div class="register-container">
        <h2>Registro</h2>

        <?php if(isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off" novalidate>
            <div class="form-group">
                <label for="username">Usuario:</label>
                <input type="text" id="username" name="username" required maxlength="150" autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required maxlength="512" autocomplete="new-password">
            </div>
            <button type="submit">Crear cuenta</button>
        </form>

        <div class="login-link">
            <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
        </div>
    </div>
</body>
</html>
