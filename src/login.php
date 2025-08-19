<?php
// --- Seguridad de sesión antes de session_start() ---
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();
require_once 'config.php';
// --- Inicializar estructura de intentos por sesión/IP ---
$clientKey = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = [];
}
if (!isset($_SESSION['login_attempts'][$clientKey])) {
    $_SESSION['login_attempts'][$clientKey] = ['count' => 0, 'last' => 0];
}
$attempts = &$_SESSION['login_attempts'][$clientKey];

// Backoff: si demasiados intentos recientes, obligamos a esperar
$MAX_ATTEMPTS = 5;
$BACKOFF_SECONDS = 30; // espera tras alcanzar el límite
$now = time();
$mustWait = ($attempts['count'] >= $MAX_ATTEMPTS) && ($now - $attempts['last'] < $BACKOFF_SECONDS);

// --- Generar token CSRF si no existe ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = null;

// --- Solo POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Comprobar CSRF
        $csrf = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
            http_response_code(400);
            throw new RuntimeException('Invalid request (CSRF).');
        }

        if ($mustWait) {
            // No revelar detalles al usuario
            throw new RuntimeException('Too many attempts. Please try again later.');
        }

        // Validación de entrada (servidor)
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            http_response_code(400);
            throw new InvalidArgumentException('Username and password are required.');
        }
        if (mb_strlen($username) > 150 || mb_strlen($password) > 512) {
            http_response_code(400);
            throw new InvalidArgumentException('Invalid input length.');
        }

        // Consulta segura
        $stmt = $pdo->prepare('SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verificación de credenciales (mensaje genérico)
        if (!$user || !password_verify($password, $user['password'])) {
            // registrar intento fallido (no revelar si user existe)
            $attempts['count']++;
            $attempts['last'] = $now;

            // Mensaje genérico para el usuario
            $error = 'Usuario o contraseña incorrectos.';
        } else {
            // Éxito: reiniciar contador, regenerar sesión y redirigir
            $attempts = ['count' => 0, 'last' => 0];

            session_regenerate_id(true);
            $_SESSION['user_id']  = (int)$user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            // Rotar token CSRF tras iniciar sesión
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            if ($user['role'] === 'admin') {
                header('Location: admin.php', true, 302);
            } else {
                header('Location: index.php', true, 302);
            }
            exit;
        }
    } catch (InvalidArgumentException $e) {
        // Errores de validación: mensaje amable y log mínimo
        $error = 'Revisa los datos introducidos.';
        error_log('[LOGIN][VALIDATION] ' . $e->getMessage());
    } catch (RuntimeException $e) {
        // Errores previsibles (CSRF, rate-limit, etc.)
        $error = 'No se ha podido procesar tu solicitud. Inténtalo de nuevo en unos instantes.';
        error_log('[LOGIN][RUNTIME] ' . $e->getMessage());
    } catch (Throwable $e) {
        // Falla inesperada: mensaje genérico y log detallado
        $error = 'Ha ocurrido un error inesperado. Inténtalo más tarde.';
        error_log('[LOGIN][EXCEPTION] ' . $e->getMessage());
    }
}
$showSearch = false;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso</title>
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="css/navbar.css">
</head>
<body>
    <?php require __DIR__ . '/navbar.php'; ?>
    <div class="login-container">
        <h2>Acceso</h2>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-group">
                <label for="username">Usuario:</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    value="<?= isset($username) ? htmlspecialchars($username, ENT_QUOTES, 'UTF-8') : '' ?>"
                    maxlength="150"
                    required
                    autocomplete="username"
                >
            </div>
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    maxlength="512"
                    required
                    autocomplete="current-password"
                >
            </div>
            <button type="submit">Entrar</button>
        </form>

        <?php if (isset($attempts) && $attempts['count'] >= $MAX_ATTEMPTS && ($now - $attempts['last'] < $BACKOFF_SECONDS)): ?>
            <p class="hint">Has realizado demasiados intentos. Vuelve a intentarlo en unos segundos.</p>
        <?php endif; ?>

        <div class="register-link">
            <p>¿No tienes cuenta? <a href="register.php">Regístrate aquí</a></p>
        </div>
    </div>
</body>
</html>
