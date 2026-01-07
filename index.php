<?php
session_start();
require 'config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['usuario'];
    $password = $_POST['password'];

    // Consulta para buscar al usuario
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE nombre = ? LIMIT 1");
    $stmt->execute([$nombre]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificación simple (Para producción usar password_verify)
    if ($usuario && $usuario['password'] === $password) {
        
        // Guardamos datos en sesión
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['rol'] = $usuario['rol'];

        // Redirección inteligente según el Rol
        if ($usuario['rol'] === 'admin') {
            header("Location: admin/productos.php");
        } else {
            header("Location: vendedor/pos.php");
        }
        exit;
    } else {
        $error = "Usuario o contraseña incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SUARCORP</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-bg">

    <div class="login-container">
        <h1>SUARCORP</h1>
        <p>Sistema de Gestión</p>
        
        <?php if($error): ?>
            <div class="alert"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Usuario</label>
                <input type="text" name="usuario" placeholder="Ej: Admin" required autofocus>
            </div>
            
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" placeholder="******" required>
            </div>

            <button type="submit" class="btn-login">Ingresar</button>
        </form>
        
        <div class="footer-login">
            <small>Soporte Técnico &copy; 2026</small>
        </div>
    </div>

</body>
</html>