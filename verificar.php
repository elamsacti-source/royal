<?php
// royal/verificar.php
require_once 'config/db.php';

$mensaje = "";
$usuario_code = isset($_GET['u']) ? $_GET['u'] : '';
$usuario_real = base64_decode($usuario_code);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Unimos los 4 inputs del código (si usaste inputs separados) o uno solo.
    // Usaremos un solo input para simplificar y ser compatible móvil.
    $codigo_ingresado = trim($_POST['codigo']);
    $user_post = trim($_POST['usuario_real']);

    if ($codigo_ingresado && $user_post) {
        $stmt = $pdo->prepare("SELECT id, nombre, telefono FROM usuarios WHERE usuario = ? AND token_wsp = ? AND activo = 0");
        $stmt->execute([$user_post, $codigo_ingresado]);
        $u = $stmt->fetch();

        if ($u) {
            // CÓDIGO CORRECTO: ACTIVAR CUENTA
            $update = $pdo->prepare("UPDATE usuarios SET activo = 1, token_wsp = NULL WHERE id = ?");
            if ($update->execute([$u['id']])) {
                
                // (Opcional) Enviar mensaje de "Bienvenida Final"
                // enviarConfirmacion($u['telefono']); 

                $mensaje = "<div class='alerta exito'>
                                <i class='fa-solid fa-check-circle' style='font-size:2rem;'></i><br>
                                <h3>¡Cuenta Activada!</h3>
                                <p>Bienvenido a la familia Royal.</p>
                                <a href='index.php' class='btn-royal'>INGRESAR AHORA</a>
                            </div>
                            <script>setTimeout(()=>{window.location.href='index.php'}, 3000);</script>";
                $success = true;
            }
        } else {
            $mensaje = "<div class='alerta error'>❌ Código incorrecto o expirado.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar - Royal</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&family=Cinzel:wght@700&display=swap" rel="stylesheet">
    <style>
        body { background: #000; font-family: 'Poppins', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .verify-box { background: #111; padding: 40px 30px; border-radius: 20px; border: 1px solid #333; width: 100%; max-width: 400px; text-align: center; border-top: 3px solid #FFD700; box-shadow: 0 0 50px rgba(255, 215, 0, 0.1); }
        h2 { color: #fff; font-family:'Cinzel'; margin-bottom: 10px; }
        .code-input { 
            width: 100%; font-size: 2rem; letter-spacing: 10px; text-align: center; 
            background: #222; border: 2px solid #444; color: #FFD700; 
            border-radius: 10px; padding: 15px; margin: 20px 0; outline: none; font-weight: bold;
        }
        .code-input:focus { border-color: #FFD700; box-shadow: 0 0 15px rgba(255, 215, 0, 0.2); }
        .btn-royal { display:block; width: 100%; background: #FFD700; padding: 15px; border-radius: 10px; color: #000; font-weight: 800; border: none; cursor: pointer; text-decoration:none; }
        .alerta { padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .error { background: rgba(239,83,80,0.2); color: #ef5350; border: 1px solid #ef5350; }
        .exito { background: rgba(102,187,106,0.2); color: #66bb6a; border: 1px solid #66bb6a; }
    </style>
</head>
<body>

<div class="verify-box fade-in">
    <?php if(!isset($success)): ?>
        <div style="font-size:3rem; color:#FFD700; margin-bottom:10px;"><i class="fa-solid fa-mobile-screen-button"></i></div>
        <h2>VERIFICACIÓN</h2>
        <p style="color:#888; font-size:0.9rem; margin-bottom:20px;">
            Ingresa el código de 6 dígitos que enviamos a tu WhatsApp.
        </p>

        <?= $mensaje ?>

        <form method="POST">
            <input type="hidden" name="usuario_real" value="<?= $usuario_real ?>">
            <input type="text" name="codigo" class="code-input" placeholder="000000" maxlength="6" inputmode="numeric" required autofocus>
            
            <button type="submit" class="btn-royal">ACTIVAR CUENTA</button>
        </form>
        
        <p style="margin-top:20px; color:#666; font-size:0.8rem;">
            ¿No recibiste el código? <a href="#" onclick="alert('Contacta a soporte')" style="color:#FFD700;">Reenviar</a>
        </p>
    <?php else: ?>
        <?= $mensaje ?>
    <?php endif; ?>
</div>

</body>
</html>