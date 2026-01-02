<?php
// royal/registro.php
require_once 'config/db.php';

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recibir y limpiar datos
    $nombre     = trim($_POST['nombre']);
    $dni        = trim($_POST['dni']);
    $telefono   = trim($_POST['telefono']);
    $fecha_nac  = $_POST['fecha_nacimiento'];
    $usuario    = trim($_POST['usuario']);
    $password   = trim($_POST['password']);
    
    // Configuración por defecto
    $rol_por_defecto = 'cliente'; 
    $foto_ruta = null;

    // Validar campos obligatorios
    if ($nombre && $usuario && $password && $dni && $telefono) {
        
        // 1. Verificar si el usuario o DNI ya existen
        $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ? OR dni = ?");
        $stmtCheck->execute([$usuario, $dni]);
        
        if ($stmtCheck->fetch()) {
            $mensaje = "<div class='alerta error'>⚠️ El usuario o el DNI ya están registrados.</div>";
        } else {
            
            // 2. PROCESAR SELFIE (Si el usuario tomó la foto)
            if (isset($_FILES['selfie']) && $_FILES['selfie']['error'] == 0) {
                // Carpeta donde se guardarán las fotos
                $dir_subida = 'assets/uploads/usuarios/';
                
                // Crear carpeta si no existe
                if (!file_exists($dir_subida)) { 
                    mkdir($dir_subida, 0777, true); 
                }
                
                $extension = pathinfo($_FILES['selfie']['name'], PATHINFO_EXTENSION);
                // Nombre único para la foto: DNI_FECHAHORA.jpg
                $nombre_foto = $dni . '_' . date('YmdHis') . '.' . $extension;
                $ruta_destino = $dir_subida . $nombre_foto;

                if (move_uploaded_file($_FILES['selfie']['tmp_name'], $ruta_destino)) {
                    $foto_ruta = $nombre_foto;
                }
            }

            // 3. Insertar el Nuevo Usuario en la Base de Datos
            $sql = "INSERT INTO usuarios (nombre, dni, telefono, fecha_nacimiento, foto_selfie, usuario, password, rol, activo) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute([$nombre, $dni, $telefono, $fecha_nac, $foto_ruta, $usuario, $password, $rol_por_defecto])) {
                $mensaje = "<div class='alerta exito'>✅ Cuenta creada con éxito. <a href='index.php' style='color:#fff; text-decoration:underline;'>Inicia Sesión aquí</a></div>";
            } else {
                $mensaje = "<div class='alerta error'>❌ Error al guardar en la base de datos.</div>";
            }
        }
    } else {
        $mensaje = "<div class='alerta error'>⚠️ Por favor completa todos los campos obligatorios.</div>";
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
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            padding: 20px 0; font-family: 'Poppins', sans-serif;
        }
        .login-box {
            background: #111; padding: 30px; border-radius: 15px; border: 1px solid #333;
            width: 100%; max-width: 450px; text-align: center;
            box-shadow: 0 0 40px rgba(255, 215, 0, 0.1);
        }
        .alerta { padding: 10px; border-radius: 5px; margin-bottom: 20px; font-size: 0.9rem; }
        .error { background: rgba(239,83,80,0.2); color: #ef5350; border: 1px solid #ef5350; }
        .exito { background: rgba(102,187,106,0.2); color: #66bb6a; border: 1px solid #66bb6a; }
        .btn-link { color: var(--royal-gold); text-decoration: none; font-size: 0.9rem; margin-top: 15px; display: inline-block; }
        
        .input-dark {
            background:#222; border:1px solid #444; color:#fff; width:100%; 
            padding:12px; margin-bottom:15px; border-radius:8px; outline:none;
        }
        .input-dark:focus { border-color: var(--royal-gold); background: #2a2a2a; }
        
        /* Botón de carga de foto */
        .file-upload {
            border: 2px dashed #444; padding: 20px; border-radius: 10px;
            margin-bottom: 20px; cursor: pointer; transition: 0.3s; position: relative;
        }
        .file-upload:hover { border-color: var(--royal-gold); background: #1a1a1a; }
        .section-title { text-align: left; color: #888; font-size: 0.75rem; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 1px; margin-top: 10px; }
    </style>
</head>
<body>

    <div class="login-box fade-in">
        <h2 style="color:var(--royal-gold); margin-bottom:5px;"><i class="fa-solid fa-user-plus"></i> Registro</h2>
        <p style="color:#666; margin-bottom:20px;">Únete a Royal Licorería</p>

        <?= $mensaje ?>

        <form method="POST" enctype="multipart/form-data">
            
            <div class="section-title">Información Personal</div>
            <input type="text" name="nombre" placeholder="Nombre y Apellido" required class="input-dark">
            
            <div style="display:flex; gap:10px;">
                <input type="number" name="dni" placeholder="DNI" required class="input-dark">
                <input type="date" name="fecha_nacimiento" required class="input-dark" style="color:#aaa;">
            </div>

            <input type="tel" name="telefono" placeholder="Celular / WhatsApp" required class="input-dark">

            <div class="section-title">Seguridad (Opcional)</div>
            <label class="file-upload" style="display:block;">
                <i class="fa-solid fa-camera" style="font-size:2rem; color:var(--royal-gold); margin-bottom:10px;"></i><br>
                <span style="color:#ccc; font-size:0.9rem;">Tomar Selfie de Seguridad</span>
                <small style="display:block; color:#666; font-size:0.8rem;">(Se abrirá tu cámara)</small>
                
                <input type="file" name="selfie" accept="image/*" capture="user" style="display:none;" onchange="verPrevia(this)">
                
                <div id="preview-msg" style="color:#66bb6a; font-weight:bold; margin-top:10px; display:none;">
                    <i class="fa-solid fa-check"></i> Foto lista
                </div>
            </label>

            <div class="section-title">Datos de Acceso</div>
            <input type="text" name="usuario" placeholder="Usuario para Login" required class="input-dark">
            <input type="password" name="password" placeholder="Contraseña" required class="input-dark">

            <button type="submit" class="btn-royal btn-block" style="width:100%; padding:15px; font-weight:bold; cursor:pointer; font-size:1.1rem; border:none; border-radius:8px;">
                CREAR CUENTA
            </button>
        </form>
        
        <a href="index.php" class="btn-link">¿Ya tienes cuenta? Ingresa aquí</a>
    </div>

    <script>
        function verPrevia(input) {
            if (input.files && input.files[0]) {
                document.getElementById('preview-msg').style.display = 'block';
                document.querySelector('.file-upload').style.borderColor = '#66bb6a';
                document.querySelector('.file-upload').style.background = 'rgba(102, 187, 106, 0.1)';
            }
        }
    </script>
</body>
</html>