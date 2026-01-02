<?php
session_start();
require_once 'config/db.php';

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['password']);

    // Buscamos el usuario
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ? AND activo = 1");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch();

    // COMPARACIÓN DIRECTA (Sin Hash)
    if ($user && $password == $user['password']) {
        // Login Exitoso
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nombre'] = $user['nombre'];
        $_SESSION['rol'] = $user['rol'];

        // Redirección
        if ($user['rol'] == 'admin') {
            header("Location: modulos/admin/dashboard.php");
        } else {
            header("Location: modulos/pos/abrir_caja.php");
        }
        exit;
    } else {
        $mensaje = "Usuario o contraseña incorrectos";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Royal Licorería</title>
    <link rel="stylesheet" href="assets/css/estilos.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-image: radial-gradient(circle at center, #1a1a1a 0%, #000 100%);
            display: flex; justify-content: center; align-items: center; height: 100vh;
        }
        .login-card {
            background: #111; padding: 40px; border-radius: 15px; border: 1px solid #333;
            width: 100%; max-width: 400px; text-align: center;
            box-shadow: 0 0 30px rgba(255, 193, 7, 0.1);
        }
        .logo-login { font-size: 3rem; color: var(--royal-gold); margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="login-card fade-in">
    <div class="logo-login">
        ROYAL <i class="fa-solid fa-wine-bottle"></i>
    </div>
    <h2 style="color:#fff; margin-bottom: 5px;">Bienvenido</h2>
    <p style="color:#666; margin-bottom: 30px;">Ingresa tus credenciales</p>

    <?php if($mensaje): ?>
        <div style="background:#ef5350; color:white; padding:10px; border-radius:5px; margin-bottom:20px;">
            <?= $mensaje ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div style="text-align:left; margin-bottom:5px; color:#888;">Usuario</div>
        <input type="text" name="usuario" required placeholder="admin" autofocus>

        <div style="text-align:left; margin-bottom:5px; color:#888;">Contraseña</div>
        <input type="password" name="password" required placeholder="••••••">

        <button type="submit" class="btn-royal btn-block" style="margin-top:10px;">
            INGRESAR <i class="fa-solid fa-arrow-right"></i>
        </button>
    </form>
</div>

</body>
</html>