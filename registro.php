<?php
// royal/registro.php
require_once 'config/db.php';

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre   = trim($_POST['nombre']);
    $usuario  = trim($_POST['usuario']);
    $password = trim($_POST['password']);
    
    // ROL FIJO PARA EL PÚBLICO
    $rol_por_defecto = 'cliente'; 

    if ($nombre && $usuario && $password) {
        // 1. Verificar si ya existe
        $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        $stmtCheck->execute([$usuario]);
        
        if ($stmtCheck->fetch()) {
            $mensaje = "<div class='alerta error'>⚠️ El usuario ya existe. Intenta con otro.</div>";
        } else {
            // 2. Crear el Usuario (Sin sede, activo por defecto)
            $sql = "INSERT INTO usuarios (nombre, usuario, password, rol, activo) VALUES (?, ?, ?, ?, 1)";
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute([$nombre, $usuario, $password, $rol_por_defecto])) {
                $mensaje = "<div class='alerta exito'>✅ Cuenta creada con éxito. <a href='index.php'>Inicia Sesión</a></div>";
            } else {
                $mensaje = "<div class='alerta error'>❌ Ocurrió un error al registrarse.</div>";
            }
        }
    } else {
        $mensaje = "<div class='alerta error'>⚠️ Completa todos los campos.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta - Royal</title>
    <link rel="stylesheet" href="assets/css/estilos.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #000;
            background-image: radial-gradient(circle at center, #1a1a1a 0%, #000 100%);
            height: 100vh; display: flex; align-items: center; justify-content: center;
        }
        .login-box {
            background: #111; padding: 40px; border-radius: 15px; border: 1px solid #333;
            width: 100%; max-width: 400px; text-align: center;
            box-shadow: 0 0 40px rgba(255, 215, 0, 0.1);
        }
        .alerta { padding: 10px; border-radius: 5px; margin-bottom: 20px; font-size: 0.9rem; }
        .error { background: rgba(239,83,80,0.2); color: #ef5350; border: 1px solid #ef5350; }
        .exito { background: rgba(102,187,106,0.2); color: #66bb6a; border: 1px solid #66bb6a; }
        .btn-link { color: var(--royal-gold); text-decoration: none; font-size: 0.9rem; margin-top: 15px; display: inline-block; }
    </style>
</head>
<body>

    <div class="login-box fade-in">
        <h2 style="color:var(--royal-gold); margin-bottom:10px;">Crear Cuenta</h2>
        <p style="color:#666; margin-bottom:20px;">Únete a Royal Licorería</p>

        <?= $mensaje ?>

        <form method="POST">
            <input type="text" name="nombre" placeholder="Nombre Completo" required class="form-control" style="background:#222; border:1px solid #444; color:#fff; width:100%; padding:12px; margin-bottom:15px; border-radius:8px;">
            
            <input type="text" name="usuario" placeholder="Usuario / Login" required class="form-control" style="background:#222; border:1px solid #444; color:#fff; width:100%; padding:12px; margin-bottom:15px; border-radius:8px;">

            <input type="password" name="password" placeholder="Contraseña" required class="form-control" style="background:#222; border:1px solid #444; color:#fff; width:100%; padding:12px; margin-bottom:20px; border-radius:8px;">

            <button type="submit" class="btn-royal btn-block" style="width:100%; padding:12px; font-weight:bold; cursor:pointer;">
                REGISTRARME
            </button>
        </form>
        
        <a href="index.php" class="btn-link">¿Ya tienes cuenta? Inicia Sesión</a>
    </div>

</body>
</html>