<?php
// royal/registro.php
require_once 'config/db.php';

// ---------------------------------------------------------
// CREDENCIALES WHATSAPP (ULTRAMSG)
// ---------------------------------------------------------
define('WSP_TOKEN', 'flqa5f6udnpv3zjt'); 
define('WSP_INSTANCE', 'instance157805'); 

// --- FUNCIÓN ENVÍO CÓDIGO ---
function enviarCodigoWsp($numero, $nombre, $codigo) {
    // Limpieza
    $numero = preg_replace('/[^0-9]/', '', $numero); 
    if(strlen($numero) == 9) $numero = "51" . $numero;
    $numero = "+" . $numero;

    // Mensaje
    $texto = "🔐 *Código Royal*\n\nHola $nombre, tu código de activación es:\n\n👉 *$codigo*";

    $params = array('token' => WSP_TOKEN, 'to' => $numero, 'body' => $texto);

    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://api.ultramsg.com/".WSP_INSTANCE."/messages/chat",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_SSL_VERIFYHOST => 0,
      CURLOPT_SSL_VERIFYPEER => 0,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => http_build_query($params),
      CURLOPT_HTTPHEADER => array("content-type: application/x-www-form-urlencoded"),
    ));
    $response = curl_exec($curl);
    curl_close($curl);
}

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre     = trim($_POST['nombre']);
    $dni        = trim($_POST['dni']);
    $telefono   = trim($_POST['telefono']);
    $fecha_nac  = $_POST['fecha_nacimiento'];
    $usuario    = trim($_POST['usuario']);
    $password   = trim($_POST['password']);
    
    $rol = 'cliente'; // El registro público SIEMPRE es cliente
    $foto_final = null; 

    if ($nombre && $usuario && $password && $dni && $telefono) {
        
        // 1. VALIDACIONES DE UNICIDAD
        $error = false;

        // A) Validar Usuario (Login único global)
        $stmtUser = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        $stmtUser->execute([$usuario]);
        if($stmtUser->fetch()) {
            $mensaje = "<div class='alerta error'>⚠️ El nombre de usuario ya está ocupado.</div>";
            $error = true;
        }

        // B) Validar Teléfono (Único global)
        if(!$error) {
            $stmtTel = $pdo->prepare("SELECT id FROM usuarios WHERE telefono = ?");
            $stmtTel->execute([$telefono]);
            if($stmtTel->fetch()) {
                $mensaje = "<div class='alerta error'>⚠️ Este número de teléfono ya está registrado en el sistema.</div>";
                $error = true;
            }
        }

        // C) Validar DNI (Único por Rol)
        // Permite repetir DNI solo si el rol es diferente.
        if(!$error) {
            $stmtDni = $pdo->prepare("SELECT id FROM usuarios WHERE dni = ? AND rol = ?");
            $stmtDni->execute([$dni, $rol]);
            if($stmtDni->fetch()) {
                $mensaje = "<div class='alerta error'>⚠️ Ya existe una cuenta de Cliente con este DNI.</div>";
                $error = true;
            }
        }

        if (!$error) {
            // 2. GUARDAR FOTO (SI EXISTE)
            if (isset($_FILES['selfie']) && $_FILES['selfie']['error'] == 0) {
                $dir = 'assets/uploads/usuarios/';
                
                // Crear carpeta recursivamente si no existe
                if (!file_exists($dir)) {
                    if (!mkdir($dir, 0777, true)) {
                        error_log("Error al crear directorio: $dir");
                    }
                }
                
                // Nombre único
                $ext = pathinfo($_FILES['selfie']['name'], PATHINFO_EXTENSION);
                $nombre_archivo = $dni . '_' . time() . '.' . $ext;
                $ruta_completa = $dir . $nombre_archivo;
                
                if (move_uploaded_file($_FILES['selfie']['tmp_name'], $ruta_completa)) {
                    $foto_final = $nombre_archivo;
                } else {
                    // Si falla mover el archivo (permisos), loguear error pero continuar registro sin foto
                    error_log("Error moviendo foto a: $ruta_completa");
                }
            }

            // 3. REGISTRAR
            $codigo_verificacion = rand(100000, 999999);

            $sql = "INSERT INTO usuarios (nombre, dni, telefono, fecha_nacimiento, foto_selfie, usuario, password, rol, activo, token_wsp) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?)";
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute([$nombre, $dni, $telefono, $fecha_nac, $foto_final, $usuario, $password, $rol, $codigo_verificacion])) {
                
                enviarCodigoWsp($telefono, $nombre, $codigo_verificacion);

                $user_code = base64_encode($usuario);
                header("Location: verificar.php?u=" . $user_code);
                exit;

            } else {
                $mensaje = "<div class='alerta error'>❌ Error interno al guardar.</div>";
            }
        }
    } else {
        $mensaje = "<div class='alerta error'>⚠️ Completa los campos.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Royal Licorería</title>
    <link rel="stylesheet" href="assets/css/estilos.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&family=Cinzel:wght@700&display=swap" rel="stylesheet">
    <style>
        body { background: #000; font-family: 'Poppins', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .login-box { background: #111; padding: 30px; border-radius: 15px; border: 1px solid #333; width: 100%; max-width: 500px; text-align: center; border-top: 3px solid #FFD700; }
        
        .input-dark { width: 100%; background: #222; border: 1px solid #444; color: #fff; padding: 12px; border-radius: 8px; margin-bottom: 10px; outline: none; }
        .input-dark:focus { border-color: #FFD700; }
        
        .btn-royal { width: 100%; background: linear-gradient(45deg, #b7892b, #FFD700); padding: 15px; border-radius: 8px; color: #000; font-weight: bold; border: none; cursor: pointer; margin-top: 15px; }
        
        /* Estilos de Foto */
        .file-upload { 
            border: 2px dashed #444; padding: 15px; border-radius: 10px; cursor: pointer; 
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            margin-bottom: 15px; transition: 0.3s; min-height: 100px; background: #1a1a1a; overflow: hidden;
        }
        .file-upload:hover { border-color: #FFD700; }
        
        .preview-img { 
            width: 100%; height: 150px; object-fit: cover; border-radius: 8px; 
            display: none; /* Oculto por defecto */
            margin-top: 5px; border: 1px solid #FFD700;
        }
        .upload-icon { font-size: 2rem; color: #666; margin-bottom: 5px; }
        
        .alerta { padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 0.9rem; }
        .error { background: rgba(239,83,80,0.2); color: #ef5350; border: 1px solid #ef5350; }
    </style>
</head>
<body>
    <div class="login-box fade-in">
        <h2 style="color:#FFD700; font-family:'Cinzel'; margin-bottom:10px;">REGISTRO</h2>
        <p style="color:#888; font-size:0.9rem; margin-bottom:20px;">Crea tu cuenta Royal</p>
        
        <?= $mensaje ?>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="nombre" placeholder="Nombre Completo" required class="input-dark">
            <div style="display:flex; gap:10px;">
                <input type="number" name="dni" placeholder="DNI" required class="input-dark">
                <input type="date" name="fecha_nacimiento" required class="input-dark" style="color:#aaa;">
            </div>
            <input type="tel" name="telefono" placeholder="WhatsApp (Ej: 999888777)" required class="input-dark">
            
            <label class="file-upload">
                <input type="file" name="selfie" accept="image/*" style="display:none;" onchange="previewFoto(this)">
                
                <div id="upload-placeholder">
                    <i class="fa-solid fa-camera upload-icon"></i>
                    <span style="color:#aaa; font-size:0.9rem; display:block;">Subir Selfie de Identidad</span>
                </div>

                <img id="img-preview" class="preview-img">
            </label>

            <input type="text" name="usuario" placeholder="Usuario" required class="input-dark">
            <input type="password" name="password" placeholder="Contraseña" required class="input-dark">
            
            <button type="submit" class="btn-royal">CREAR Y VERIFICAR</button>
        </form>
        <a href="index.php" style="display:block; margin-top:20px; color:#666; text-decoration:none;">Volver al Login</a>
    </div>

    <script>
        function previewFoto(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                
                reader.onload = function(e) {
                    // Mostrar imagen
                    var img = document.getElementById('img-preview');
                    img.src = e.target.result;
                    img.style.display = 'block';
                    
                    // Ocultar icono
                    document.getElementById('upload-placeholder').style.display = 'none';
                    
                    // Cambiar borde a verde
                    input.parentElement.style.borderColor = '#4caf50';
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>