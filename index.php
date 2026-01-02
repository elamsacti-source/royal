<?php
session_start();
require_once 'config/db.php';

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['password']);

    // 1. Buscar Usuario
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ? AND activo = 1");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Verificar Password (Texto plano según tu configuración actual)
    if ($user && $password == $user['password']) {
        
        // Guardar Sesión
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nombre']  = $user['nombre'];
        $_SESSION['rol']     = $user['rol'];
        $_SESSION['id_sede'] = $user['id_sede'];

        // 3. REDIRECCIÓN SEGÚN ROL (EL SEMÁFORO)
        switch ($user['rol']) {
            case 'admin':
                header("Location: modulos/admin/dashboard.php");
                break;

            case 'driver':
                header("Location: modulos/driver/index.php");
                break;

            case 'cajero':
                // Verificar si tiene caja abierta
                $stmtCaja = $pdo->prepare("SELECT id FROM caja_sesiones WHERE id_usuario = ? AND estado = 'abierta'");
                $stmtCaja->execute([$user['id']]);
                if ($stmtCaja->fetch()) {
                    header("Location: modulos/pos/venta.php");
                } else {
                    header("Location: modulos/pos/abrir_caja.php");
                }
                break;
            
            case 'cliente': // O 'user', depende como lo guardes en registro.php
            case 'user':
                // ¡EL CLIENTE VA A LA TIENDA PÚBLICA!
                header("Location: public/index.php");
                break;

            default:
                $mensaje = "Rol no asignado. Contacte soporte.";
                session_destroy();
                break;
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
    <title>Acceso - Royal Licorería</title>
    <link rel="stylesheet" href="assets/css/estilos.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #000;
            background-image: radial-gradient(circle at center, #1a1a1a 0%, #000 100%);
            height: 100vh; display: flex; align-items: center; justify-content: center;
            font-family: 'Poppins', sans-serif;
        }
        .login-box {
            background: #111; padding: 40px; border-radius: 15px; border: 1px solid #333;
            width: 100%; max-width: 400px; text-align: center;
            box-shadow: 0 0 50px rgba(255, 215, 0, 0.1);
        }
        .logo { font-size: 3rem; color: var(--royal-gold); margin-bottom: 10px; }
        .input-group { position: relative; margin-bottom: 20px; text-align: left; }
        .input-group i { position: absolute; left: 15px; top: 14px; color: #666; }
        .input-group input { 
            width: 100%; padding: 12px 12px 12px 45px; 
            background: #222; border: 1px solid #444; color: #fff; 
            border-radius: 8px; outline: none; transition: 0.3s;
        }
        .input-group input:focus { border-color: var(--royal-gold); background: #2a2a2a; }
        .btn-royal { width: 100%; padding: 15px; font-size: 1.1rem; border-radius: 8px; font-weight: bold; cursor: pointer; border: none; background: var(--royal-gold); color: #000; }
        .links { margin-top: 20px; font-size: 0.9rem; }
        .links a { color: var(--royal-gold); text-decoration: none; margin: 0 10px; }
    </style>
</head>
<body>

    <div class="login-box fade-in">
        <div class="logo"><i class="fa-solid fa-wine-bottle"></i></div>
        <h2 style="color:#fff; margin-bottom:5px;">BIENVENIDO</h2>
        <p style="color:#666; margin-bottom:30px;">Ingresa a Royal Licorería</p>

        <?php if($mensaje): ?>
            <div style="background:rgba(239,83,80,0.2); color:#ef5350; padding:10px; border-radius:5px; margin-bottom:20px; border:1px solid #ef5350;">
                <?= $mensaje ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <i class="fa-solid fa-user"></i>
                <input type="text" name="usuario" placeholder="Usuario" required autofocus>
            </div>

            <div class="input-group">
                <i class="fa-solid fa-lock"></i>
                <input type="password" name="password" placeholder="Contraseña" required>
            </div>

            <button type="submit" class="btn-royal">INGRESAR</button>
        </form>
        
        <div class="links">
            <p style="color:#666;">¿Eres nuevo?</p>
            <a href="registro.php" style="font-weight:bold; font-size:1.1rem;">CREAR CUENTA</a>
        </div>
    </div>

</body>
</html>