<?php
include_once 'session.php';
include 'db_config.php';

// Seguridad
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// --- LÓGICA DE BASE DE DATOS (AJAX - ELIMINAR Y ACTUALIZAR TELÉFONO) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // 1. ELIMINAR
    if ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("UPDATE resultados_lab SET activo = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        echo json_encode(['success' => $stmt->execute()]);
        exit;
    }

    // 2. ACTUALIZAR (SOLO TELÉFONO)
    if ($_POST['action'] === 'update_phone') {
        $id = (int)$_POST['id'];
        $telefono = $_POST['telefono'];
        $stmt = $conn->prepare("UPDATE resultados_lab SET telefono=? WHERE id=?");
        $stmt->bind_param("si", $telefono, $id);
        if ($stmt->execute()) echo json_encode(['success' => true]);
        else echo json_encode(['success' => false, 'error' => $stmt->error]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Lab - Edición Navidad 🎄</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Mountains+of+Christmas:wght@700&display=swap" rel="stylesheet">
    
    <style>
        :root { --xmas-red: #D32F2F; --xmas-dark-red: #8a1c1c; --xmas-green: #2E7D32; --xmas-gold: #FFD700; }
        body { 
            background: linear-gradient(135deg, var(--xmas-red) 0%, var(--xmas-dark-red) 100%);
            font-family: 'Montserrat', sans-serif; min-height: 100vh; padding-bottom: 50px;
        }

        /* --- NIEVE (NO BLOQUEA CLICS) --- */
        .snow-container { position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 0; }
        .snowflake { 
            color: rgba(255,255,255,0.3); font-size: 1.2em; position: fixed; top: -10%; 
            animation: snowflakes-fall 10s linear infinite, snowflakes-shake 3s ease-in-out infinite; pointer-events: none; 
        }
        @keyframes snowflakes-fall { 0% { top: -10%; } 100% { top: 100%; } }
        @keyframes snowflakes-shake { 0%, 100% { transform: translateX(0); } 50% { transform: translateX(80px); } }
        .snowflake:nth-of-type(1) { left: 10%; animation-duration: 8s; } .snowflake:nth-of-type(2) { left: 30%; animation-duration: 12s; animation-delay: 2s; } .snowflake:nth-of-type(3) { left: 70%; animation-duration: 10s; animation-delay: 1s; }

        /* --- ESTILOS GENERALES --- */
        .merry-font { font-family: 'Mountains of Christmas', cursive; letter-spacing: 1px; }
        .navbar-custom { background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); border-bottom: 1px solid rgba(255,255,255,0.2); position: relative; z-index: 10; }
        
        .main-card { background: #fff; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); border: none; overflow: hidden; position: relative; z-index: 10; margin-top: 20px; }
        .card-header-xmas { background: #fff; border-bottom: 4px solid var(--xmas-gold); padding: 15px 20px; }
        .table thead th { background-color: var(--xmas-green); color: white; font-weight: 600; text-transform: uppercase; font-size: 0.8rem; border: none; }
        
        /* Inputs Tabla */
        .phone-input { border: 2px solid #e2e8f0; background: #f8fafc; padding: 6px 10px; border-radius: 8px; width: 100%; font-size: 1rem; color: #1e293b; font-weight: 700; transition: 0.2s; }
        .phone-input:focus { border-color: var(--xmas-green); outline: none; background: #fff; box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.1); }

        /* Botones */
        .btn-save-phone { background: var(--xmas-green); color: white; border: none; font-size: 0.75rem; padding: 4px 10px; border-radius: 20px; cursor: pointer; font-weight: bold; margin-top: 4px; width: 100%; transition: 0.2s; }
        .btn-save-phone:hover { background: #1b5e20; transform: scale(1.02); }
        .btn-wsp { background: #25D366; color: white; border: none; width: 100%; padding: 8px; font-weight: 700; font-size: 0.85rem; border-radius: 8px; margin-bottom: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .btn-wsp:hover { background: #128C7E; color: white; }
        .btn-del { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; width: 100%; border-radius: 8px; padding: 6px; font-size: 0.8rem; font-weight: 600; }
        .btn-del:hover { background: #c62828; color: white; }
        .btn-pdf-view { display: flex; align-items: center; justify-content: flex-start; width: 100%; background: #fff; border: 1px solid #d32f2f; color: #d32f2f; padding: 8px 12px; border-radius: 6px; margin-bottom: 6px; font-size: 0.85rem; font-weight: 700; text-decoration: none; transition: 0.2s; cursor: pointer; }
        .btn-pdf-view:hover { background: #d32f2f; color: white; transform: translateX(3px); }
        .btn-pdf-view i { margin-right: 8px; }

        .static-data { font-weight: 700; color: #374151; display: block; }
        .data-label { font-size: 0.65rem; color: #9ca3af; text-transform: uppercase; font-weight: 700; }

        /* --- ESTILOS DEL MODAL --- */
        .modal-header-xmas { background: var(--xmas-red); color: white; border-bottom: 4px solid var(--xmas-gold); }
        .modal-title { font-family: 'Mountains of Christmas', cursive; font-weight: bold; font-size: 1.5rem; }
        .success-view { display: none; text-align: center; padding: 20px; }
        .generated-code { font-size: 2.5rem; font-weight: 800; color: var(--xmas-red); letter-spacing: 3px; display: block; margin: 10px 0; }
    </style>
</head>
<body>
    
    <div class="snow-container" aria-hidden="true"><div class="snowflake">❅</div><div class="snowflake">❆</div><div class="snowflake">❅</div></div>

    <nav class="navbar navbar-custom navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold merry-font fs-3" href="admin.php">
                🎄 Lab Control
            </a>
            <div>
                <a href="admin.php" class="btn btn-outline-light btn-sm rounded-pill px-3 me-2">Volver</a>
                <button type="button" class="btn btn-warning btn-sm rounded-pill fw-bold text-dark px-3" data-bs-toggle="modal" data-bs-target="#newResultModal">
                    <i class="fas fa-plus me-1"></i> Nuevo Resultado
                </button>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="main-card">
            <div class="card-header-xmas">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h4 class="mb-0 fw-bold text-danger merry-font">🎅 Base de Datos Pacientes</h4>
                        <p class="text-muted small mb-0 fw-bold">Gestión de resultados y corrección de números.</p>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-danger"></i></span>
                            <input type="text" id="searchInput" class="form-control border-start-0" placeholder="Buscar por Nombre o DNI...">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 100px;">Fecha</th>
                            <th style="min-width: 200px;">Paciente (Fijo)</th>
                            <th style="min-width: 180px;">Editar WhatsApp ✏️</th>
                            <th style="min-width: 150px;">Credenciales</th>
                            <th style="min-width: 160px;">📄 Ver PDFs</th>
                            <th class="text-end" style="min-width: 160px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="resultsBody">
                        <tr><td colspan="6" class="text-center p-5 text-muted fw-bold">❄️ Cargando resultados... ❄️</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="newResultModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header modal-header-xmas">
                    <h5 class="modal-title"><i class="fas fa-gift me-2"></i>Nuevo Resultado</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="resetModal()"></button>
                </div>
                <div class="modal-body p-4">
                    
                    <form id="formNewResult">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">DNI Paciente</label>
                            <input type="tel" name="dni" class="form-control" required placeholder="Ej: 12345678" maxlength="15">
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold text-secondary">Nombre Completo</label>
                                <input type="text" name="nombre" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold text-secondary">Celular / WhatsApp</label>
                                <input type="tel" name="telefono" class="form-control" required placeholder="999..." maxlength="15">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-secondary">Archivos PDF</label>
                            <input type="file" name="pdf[]" class="form-control" accept="application/pdf" multiple required>
                            <small class="text-muted" style="font-size:0.7rem;">Puede subir varios archivos a la vez.</small>
                        </div>
                        <button type="submit" id="btnUpload" class="btn btn-danger w-100 fw-bold rounded-pill" style="background: var(--xmas-red);">
                            <i class="fas fa-upload me-2"></i> SUBIR RESULTADOS
                        </button>
                    </form>

                    <div id="successView" class="success-view">
                        <i class="fas fa-check-circle display-1 text-success mb-3 animate__animated animate__bounceIn"></i>
                        <h4 class="fw-bold text-dark">¡Registrado!</h4>
                        <p class="text-muted small">El resultado ya aparece en la lista.</p>
                        
                        <div class="bg-light p-3 rounded border mb-3">
                            <span class="small text-uppercase fw-bold text-muted">Código Generado:</span>
                            <span id="newCode" class="generated-code">----</span>
                        </div>

                        <a href="#" id="modalWspLink" target="_blank" class="btn btn-success w-100 fw-bold mb-3">
                            <i class="fab fa-whatsapp me-2"></i> Enviar al Paciente
                        </a>

                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetModal()">
                            Subir Otro
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let allData = [];

        document.addEventListener("DOMContentLoaded", () => {
            fetchData();
        });

        // 1. CARGAR DATOS
        async function fetchData() {
            <?php
            // TRAEMOS LOS DATOS
            $sql = "SELECT id, dni, nombre_paciente, telefono, codigo_acceso, fecha_registro, archivo_pdf 
                    FROM resultados_lab WHERE activo = 1 ORDER BY fecha_registro DESC LIMIT 100";
            $res = $conn->query($sql);
            $rows = [];
            while($r = $res->fetch_assoc()) {
                $files = json_decode($r['archivo_pdf']);
                if (json_last_error() !== JSON_ERROR_NONE) $files = [$r['archivo_pdf']];
                $r['archivos_array'] = $files;
                $rows[] = $r;
            }
            echo "const dbData = " . json_encode($rows) . ";";
            ?>
            allData = dbData;
            renderTable(allData);
        }

        // 2. RENDERIZAR TABLA
        function renderTable(data) {
            const tbody = document.getElementById('resultsBody');
            tbody.innerHTML = '';

            if(data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center p-5 text-muted">🎅 Jo Jo Jo... No hay registros aún.</td></tr>';
                return;
            }

            data.forEach(item => {
                const tr = document.createElement('tr');
                
                // Archivos
                let filesHtml = '';
                item.archivos_array.forEach((file, i) => {
                    const cleanPath = file.replace(/\\/g, '/');
                    filesHtml += `
                        <button onclick="window.open('${cleanPath}', '_blank')" class="btn-pdf-view" title="Abrir Documento">
                            <i class="fas fa-file-pdf"></i> VER PDF ${i+1}
                        </button>`;
                });

                const safeName = item.nombre_paciente.replace(/'/g, "\\'"); 

                tr.innerHTML = `
                    <td class="small fw-bold text-secondary">${item.fecha_registro.split(' ')[0]}</td>
                    <td><span class="static-data text-uppercase" style="color:#b71c1c;">${item.nombre_paciente}</span></td>
                    <td>
                        <div style="max-width: 140px;">
                            <span class="data-label">Celular / WhatsApp</span>
                            <input type="tel" id="tel_${item.id}" class="phone-input" value="${item.telefono}">
                            <button onclick="savePhone(${item.id})" class="btn-save-phone"><i class="fas fa-save me-1"></i> Guardar N°</button>
                        </div>
                    </td>
                    <td>
                        <span class="data-label">DNI</span>
                        <span class="d-block fw-bold text-dark">${item.dni}</span>
                        <span class="data-label mt-1">Código</span>
                        <span class="badge bg-warning text-dark border border-warning">${item.codigo_acceso}</span>
                    </td>
                    <td>${filesHtml}</td>
                    <td class="text-end">
                        <div class="d-flex flex-column gap-2">
                            <button onclick="sendWsp(${item.id}, '${safeName}', '${item.dni}', '${item.codigo_acceso}')" class="btn-wsp"><i class="fab fa-whatsapp fa-lg me-1"></i> Enviar WSP</button>
                            <button onclick="deleteRow(${item.id})" class="btn-del"><i class="fas fa-trash-alt me-1"></i> Borrar</button>
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        // --- 3. LÓGICA DEL MODAL (NUEVO REGISTRO) ---
        const formNew = document.getElementById('formNewResult');
        const formView = document.getElementById('formNewResult'); // Mismo ID
        const successView = document.getElementById('successView');
        const btnUpload = document.getElementById('btnUpload');

        formNew.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const originalText = btnUpload.innerHTML;
            btnUpload.disabled = true; 
            btnUpload.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Subiendo...';

            const formData = new FormData(e.target);

            try {
                // Usamos api_resultados.php que ya tienes creado
                const res = await fetch('api_resultados.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.success) {
                    // 1. Mostrar vista éxito dentro del modal
                    formNew.style.display = 'none';
                    successView.style.display = 'block';
                    
                    document.getElementById('newCode').innerText = data.codigo;

                    // 2. Generar Link WSP
                    const nombre = formData.get('nombre');
                    const dni = formData.get('dni');
                    const tel = formData.get('telefono');
                    const linkWeb = window.location.origin + "/resultados.php";
                    const mensaje = `Hola *${nombre}* 🎄,\nSus resultados de laboratorio están listos 📄.\n\nPuede verlos aquí:\n🔗 ${linkWeb}\n\n*Sus credenciales:*\n🆔 DNI: ${dni}\n🔑 Código: ${data.codigo}\n\nSaludos, Clínica Los Ángeles.`;
                    
                    document.getElementById('modalWspLink').href = `https://wa.me/51${tel}?text=${encodeURIComponent(mensaje)}`;

                    // 3. Recargar la tabla de fondo sin recargar página
                    // Truco: Hacemos reload completo para traer los datos nuevos frescos de PHP
                    // Opcional: Podríamos hacer fetch de nuevo, pero reload es más seguro para asegurar consistencia
                    setTimeout(() => location.reload(), 3000); 

                } else {
                    alert("⚠️ Error: " + data.error);
                    btnUpload.disabled = false; btnUpload.innerHTML = originalText;
                }
            } catch (err) { 
                alert("❌ Error de conexión."); 
                btnUpload.disabled = false; btnUpload.innerHTML = originalText;
            }
        });

        function resetModal() {
            formNew.reset();
            formNew.style.display = 'block';
            successView.style.display = 'none';
            btnUpload.disabled = false;
            btnUpload.innerHTML = '<i class="fas fa-upload me-2"></i> SUBIR RESULTADOS';
        }

        // --- 4. FUNCIONES DE TABLA ---
        async function savePhone(id) {
            const telefono = document.getElementById(`tel_${id}`).value;
            if(!telefono) return alert("Ingrese un número válido");
            const btn = event.currentTarget; const oldHTML = btn.innerHTML;
            btn.innerHTML = '...'; btn.disabled = true;

            const fd = new FormData(); fd.append('action', 'update_phone'); fd.append('id', id); fd.append('telefono', telefono);
            try {
                const res = await fetch('lab_panel.php', { method:'POST', body:fd });
                const json = await res.json();
                if(json.success) {
                    btn.innerHTML = '¡Listo!'; btn.style.background = '#22c55e';
                    setTimeout(() => { btn.innerHTML = oldHTML; btn.style.background = ''; btn.disabled = false; }, 1500);
                } else { alert("Error: " + json.error); btn.innerHTML = oldHTML; btn.disabled = false; }
            } catch(e) { alert("Error"); btn.innerHTML = oldHTML; btn.disabled = false; }
        }

        function sendWsp(id, nombre, dni, codigo) {
            const telefono = document.getElementById(`tel_${id}`).value;
            const linkWeb = window.location.origin + "/resultados.php";
            const mensaje = `Hola *${nombre}* 🎄,\nSus resultados de laboratorio están listos 📄.\n\nPuede verlos aquí:\n🔗 ${linkWeb}\n\n*Sus credenciales:*\n🆔 DNI: ${dni}\n🔑 Código: ${codigo}\n\nSaludos, Clínica Los Ángeles.`;
            window.open(`https://wa.me/51${telefono}?text=${encodeURIComponent(mensaje)}`, '_blank');
        }

        async function deleteRow(id) {
            if(!confirm("¿Borrar permanentemente?")) return;
            const fd = new FormData(); fd.append('action', 'delete'); fd.append('id', id);
            await fetch('lab_panel.php', { method:'POST', body:fd });
            location.reload();
        }

        document.getElementById('searchInput').addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            const filtered = allData.filter(d => d.nombre_paciente.toLowerCase().includes(term) || d.dni.includes(term));
            renderTable(filtered);
        });
    </script>
</body>
</html>