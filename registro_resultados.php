<?php 
include_once 'session.php'; 
include_once 'db_config.php'; // IMPORTANTE: Usar db_config

// --- LÓGICA DE LOGIN RÁPIDO ---
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = $_POST['email'];
    $pass = $_POST['password'];
    
    // CORRECCIÓN: Ahora buscamos en 'usuarios'
    // Buscamos por el campo 'usuario' o 'email'
    $stmt = $conn->prepare("SELECT id, nombre_completo, rol, password FROM usuarios WHERE usuario = ? OR email = ?");
    $stmt->bind_param("ss", $email, $email);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($row = $res->fetch_assoc()) {
        if ($pass == $row['password']) { 
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_role'] = $row['rol'];
            $_SESSION['user_name'] = $row['nombre_completo']; // Cambiado a nombre_completo
            header("Location: registro_resultados.php"); 
            exit;
        } else { $login_error = 'Contraseña incorrecta 🎅'; }
    } else { $login_error = 'Usuario no encontrado'; }
}

$is_logged = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro de Resultados | Personal 🎄</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Mountains+of+Christmas:wght@700&display=swap" rel="stylesheet">

    <style>
        :root { --xmas-red: #B71C1C; --xmas-green: #2E7D32; --xmas-gold: #FFD700; }
        body { background: #f1f5f9; font-family: 'Montserrat', sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; position: relative; }
        body::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 200px; background: linear-gradient(180deg, var(--xmas-red) 0%, transparent 100%); z-index: -1; }
        .panel-card { width: 100%; max-width: 500px; border: none; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); overflow: hidden; background: white; margin: 20px; border-top: 5px solid var(--xmas-gold); }
        .panel-header { background: #fff; color: var(--xmas-red); padding: 20px; text-align: center; border-bottom: 1px solid #eee; }
        .panel-header.login { background: var(--xmas-red); color: white; } 
        .merry-font { font-family: 'Mountains of Christmas', cursive; }
        .success-box { display: none; background: #f1f8e9; border: 1px solid var(--xmas-green); padding: 20px; border-radius: 10px; margin-top: 20px; text-align: center; animation: fadeIn 0.5s; }
        .btn-xmas-action { background-color: var(--xmas-green); border: none; transition: 0.3s; }
        .btn-xmas-action:hover { background-color: #1b5e20; transform: translateY(-2px); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        /* Spinner y Feedback */
        .input-group-text { background: white; border-left: 0; color: #ccc; }
        .searching .fa-id-card { display: none; }
        .searching::after { content: ""; display: inline-block; width: 1rem; height: 1rem; border: 2px solid currentColor; border-right-color: transparent; border-radius: 50%; animation: spinner-border .75s linear infinite; color: var(--xmas-green); }
    </style>
</head>
<body>

    <?php if (!$is_logged): ?>
        <div class="panel-card animate__animated animate__fadeIn">
            <div class="panel-header login">
                <h4 class="mb-0 fw-bold merry-font" style="font-size: 1.8rem;">🎄 Acceso Restringido</h4>
                <small class="opacity-75">Solo personal autorizado</small>
            </div>
            <div class="p-4">
                <?php if($login_error): ?>
                    <div class="alert alert-danger text-center border-0 bg-danger-subtle text-danger fw-bold">
                        <i class="fas fa-exclamation-circle me-1"></i> <?php echo $login_error; ?>
                    </div>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="action" value="login">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary">Correo Electrónico</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold text-secondary">Contraseña</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button class="btn btn-danger w-100 py-2 fw-bold rounded-pill shadow-sm" style="background: var(--xmas-red);">INGRESAR AL SISTEMA</button>
                </form>
                <div class="text-center mt-4"><a href="portal_resultados.php" class="text-decoration-none text-muted small fw-bold"><i class="fas fa-arrow-left me-1"></i> Volver al Portal</a></div>
            </div>
        </div>

    <?php else: ?>
        <div class="panel-card animate__animated animate__fadeIn">
            <div class="panel-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-start">
                        <h5 class="mb-0 fw-bold text-danger"><i class="fas fa-file-medical me-2"></i>Cargar Resultados</h5>
                        <small class="text-muted">Usuario: <?php echo htmlspecialchars($_SESSION['user_name']); ?></small>
                    </div>
                    <a href="api_logout.php" class="btn btn-sm btn-outline-danger border-2 fw-bold" style="border-radius: 20px;">Salir</a>
                </div>
            </div>
            
            <div class="p-4">
                <form id="formUpload" enctype="multipart/form-data">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">DNI Paciente (Búsqueda Automática)</label>
                        <div class="input-group">
                            <input type="tel" name="dni" id="dni" class="form-control" required placeholder="Ingrese 8 dígitos" maxlength="8" autocomplete="off">
                            <span class="input-group-text" id="dniIcon"><i class="fas fa-id-card"></i></span>
                        </div>
                        <div id="dniFeedback" class="form-text text-danger" style="display:none; font-size:0.75rem;"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Nombre del Paciente</label>
                        <input type="text" name="nombre" id="nombre" class="form-control bg-light" required placeholder="Se llenará automáticamente..." readonly>
                    </div>

                    <input type="hidden" name="telefono" id="telefono" value="000000000"> 
                    
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-secondary">Archivos (PDF)</label>
                        <input type="file" name="pdf[]" class="form-control" accept="application/pdf" multiple required>
                        <div class="form-text text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-info-circle me-1"></i> Puede seleccionar varios archivos.
                        </div>
                    </div>
                    
                    <button type="submit" id="btnSubir" class="btn btn-primary w-100 fw-bold py-2 rounded-pill shadow-sm btn-xmas-action">
                        <i class="fas fa-gift me-2"></i> REGISTRAR Y GENERAR CÓDIGO
                    </button>
                </form>

                <div id="successArea" class="success-box">
                    <h4 class="text-success fw-bold mb-3"><i class="fas fa-check-circle display-4" style="color: var(--xmas-green);"></i><br>¡Registrado!</h4>
                    
                    <div class="bg-white border rounded p-3 mb-3 shadow-sm" style="border-color: var(--xmas-gold) !important;">
                        <small class="text-muted d-block text-uppercase fw-bold mb-1">Código de Acceso:</small>
                        <span class="fs-1 fw-bold text-danger" id="generatedCode" style="letter-spacing: 2px;">----</span>
                    </div>
                    
                    <div class="alert alert-warning small border-0 py-2">
                        <i class="fas fa-exclamation-triangle me-1"></i> Sin celular. Edite en el panel para enviar WhatsApp.
                    </div>
                    
                    <button onclick="location.reload()" class="btn btn-link btn-sm mt-3 text-secondary text-decoration-none">
                        <i class="fas fa-plus me-1"></i> Subir otro resultado
                    </button>
                </div>
            </div>
        </div>

        <script>
            // --- 1. LÓGICA DE LA API DE DNI ---
            const dniInput = document.getElementById('dni');
            const nombreInput = document.getElementById('nombre');
            const dniIcon = document.getElementById('dniIcon');
            const dniFeedback = document.getElementById('dniFeedback');

            dniInput.addEventListener('input', async function() {
                const dni = this.value;
                
                // Solo actuamos si tiene 8 dígitos
                if (dni.length === 8) {
                    dniIcon.classList.add('searching');
                    dniInput.disabled = true;
                    dniFeedback.style.display = 'none';

                    // TOKEN EXACTO (Copiado de tu mensaje)
                    const token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIzOTMyNiIsImh0dHA6Ly9zY2hlbWFzLm1pY3Jvc29mdC5jb20vd3MvMjAwOC8wNi9pZGVudGl0eS9jbGFpbXMvcm9sZSI6ImNvbnN1bHRvciJ9.aJCVJFvYRxvqcWH2gBOXMEdbgs9OOEBp3YG5yY_S68Y'; 
                    
                    const url = 'https://api.factiliza.com/v1/dni/info/' + dni;

                    const options = {
                        method: 'GET',
                        headers: {
                            Authorization: 'Bearer ' + token.trim()
                        }
                    };

                    try {
                        const res = await fetch(url, options);
                        
                        if (!res.ok) {
                            console.error("Error API Status:", res.status);
                            throw new Error('API Error: ' + res.status);
                        }
                        
                        const jsonResponse = await res.json();
                        console.log("Respuesta completa:", jsonResponse); 

                        // === CORRECCIÓN AQUÍ ===
                        // Entramos a la propiedad 'data' que es donde está la información real
                        const persona = jsonResponse.data; 

                        let fullName = "";
                        
                        if (persona && persona.nombre_completo) {
                            // Opción A: Usar el campo listo que da la API
                            fullName = persona.nombre_completo;
                        } else if (persona && persona.nombres) {
                            // Opción B: Construirlo (si nombre_completo falla)
                            fullName = `${persona.nombres} ${persona.apellido_paterno} ${persona.apellido_materno}`;
                        } else {
                            throw new Error('Datos incompletos');
                        }

                        // Asignar el valor
                        nombreInput.value = fullName.trim();
                        nombreInput.readOnly = true;

                    } catch (error) {
                        console.error(error);
                        dniFeedback.innerText = "No encontrado. Ingrese nombre manualmente.";
                        dniFeedback.style.display = 'block';
                        nombreInput.readOnly = false;
                        nombreInput.value = "";
                        nombreInput.focus();
                    } finally {
                        dniIcon.classList.remove('searching');
                        dniInput.disabled = false;
                        // Si se llenó correctamente, el foco se queda en DNI o pasa al siguiente
                        if(nombreInput.value === "") nombreInput.focus(); 
                    }
                }
            });

            // --- 2. LÓGICA DE SUBIDA ---
            document.getElementById('formUpload').addEventListener('submit', async (e) => {
                e.preventDefault();
                const btn = document.getElementById('btnSubir');
                btn.disabled = true; btn.innerHTML = "Subiendo...";

                const formData = new FormData(e.target);

                try {
                    const res = await fetch('api_resultados.php', { method: 'POST', body: formData });
                    const data = await res.json();

                    if (data.success) {
                        document.getElementById('formUpload').style.display = 'none';
                        document.getElementById('successArea').style.display = 'block';
                        document.getElementById('generatedCode').innerText = data.codigo;
                    } else {
                        alert("Error: " + data.error);
                        btn.disabled = false; btn.innerText = "Reintentar";
                    }
                } catch (err) {
                    alert("Error de red");
                    btn.disabled = false;
                }
            });
        </script>

    <?php endif; ?>
</body>
</html>