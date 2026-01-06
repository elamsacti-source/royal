<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Consulta de Resultados | Clínica Los Ángeles 🎄</title>
    <link rel="icon" type="image/x-icon" href="imagenes/icon.png" />
    
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,600,700" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Mountains+of+Christmas:wght@700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root { 
            --xmas-red: #D32F2F; --xmas-dark-red: #8a1c1c;
            --xmas-green: #2E7D32; --xmas-gold: #FFD700;
        }
        
        body { 
            background: linear-gradient(135deg, #a40606 0%, #680000 100%);
            min-height: 100vh; display: flex; align-items: center; justify-content: center; 
            font-family: 'Montserrat', sans-serif; padding: 20px; overflow-x: hidden; position: relative;
        }

        /* --- NIEVE --- */
        .snow-container { position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 1; }
        .snowflake { color: #fff; font-size: 1.2em; position: fixed; top: -10%; animation: snowflakes-fall 10s linear infinite, snowflakes-shake 3s ease-in-out infinite; opacity: 0.6; }
        @keyframes snowflakes-fall { 0% { top: -10%; } 100% { top: 100%; } }
        @keyframes snowflakes-shake { 0%, 100% { transform: translateX(0); } 50% { transform: translateX(80px); } }
        .snowflake:nth-of-type(0) { left: 10%; animation-delay: 0s, 0s; } .snowflake:nth-of-type(1) { left: 30%; animation-delay: 5s, 2s; } .snowflake:nth-of-type(2) { left: 70%; animation-delay: 2s, 1s; } 

        .merry-font { font-family: 'Mountains of Christmas', cursive; font-weight: bold; }

        .login-card { 
            background: rgba(255, 255, 255, 0.98); padding: 40px; border-radius: 20px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.4); width: 100%; max-width: 550px; /* Un poco más ancho para los botones */
            position: relative; z-index: 10; border: 4px solid white; 
        }
        
        .login-card::before { 
            content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 8px; 
            background: repeating-linear-gradient(45deg, var(--xmas-red), var(--xmas-red) 10px, var(--xmas-gold) 10px, var(--xmas-gold) 20px);
            border-top-left-radius: 15px; border-top-right-radius: 15px;
        }

        .brand-logo { width: 90px; margin-bottom: 15px; }
        .form-control { border-radius: 10px; padding: 12px; background: #fffcfc; border: 1px solid #e0e0e0; }
        .form-control:focus { box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.2); border-color: var(--xmas-green); }
        
        /* Botón de Consultar Principal */
        .btn-consultar { 
            width: 100%; padding: 12px; border-radius: 25px; font-weight: bold; 
            background: var(--xmas-green); border: 2px solid var(--xmas-gold); color: white; 
            margin-top: 15px; transition: 0.3s; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .btn-consultar:hover { background: #1b5e20; transform: translateY(-2px); color: var(--xmas-gold); }
        
        /* TARJETA DE RESULTADOS */
        .result-card { display: none; margin-top: 20px; border: 2px dashed var(--xmas-red); border-radius: 12px; padding: 20px; background: #fffdfd; animation: fadeIn 0.5s; }
        
        /* CADA ARCHIVO (FILA) */
        .file-item { 
            display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; 
            background: #fff; border: 1px solid #f0f0f0; padding: 15px; 
            border-radius: 12px; margin-bottom: 12px; transition: 0.2s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        }
        .file-item:hover { border-color: var(--xmas-gold); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
        
        /* ESTILOS DE LOS BOTONES VER / DESCARGAR */
        .btn-group-custom { display: flex; gap: 8px; margin-top: 5px; }

        .btn-action { 
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 0.85rem; padding: 8px 16px; border-radius: 50px; 
            text-decoration: none; font-weight: 600; transition: all 0.3s ease;
            border: 1px solid transparent; cursor: pointer; white-space: nowrap;
        }

        /* Botón Azul (Ver) */
        .btn-ver { background: #e3f2fd; color: #1565c0; border-color: #bbdefb; }
        .btn-ver:hover { background: #1565c0; color: white; border-color: #1565c0; box-shadow: 0 4px 10px rgba(21, 101, 192, 0.3); }

        /* Botón Verde (Descargar) */
        .btn-descargar { background: #e8f5e9; color: #2e7d32; border-color: #c8e6c9; }
        .btn-descargar:hover { background: #2e7d32; color: white; border-color: #2e7d32; box-shadow: 0 4px 10px rgba(46, 125, 50, 0.3); }
        
        .back-link { color: rgba(255,255,255,0.8); text-decoration: none; font-size: 0.9rem; margin-top: 20px; display: inline-block; transition: 0.3s; }
        .back-link:hover { color: var(--xmas-gold); transform: translateX(-5px); }

        @keyframes fadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
        
        /* Ajuste móvil para botones */
        @media (max-width: 450px) {
            .file-item { flex-direction: column; align-items: flex-start; }
            .btn-group-custom { width: 100%; margin-top: 10px; justify-content: space-between; }
            .btn-action { flex: 1; }
        }
    </style>
</head>
<body>

    <div class="snow-container" aria-hidden="true"><div class="snowflake">❅</div><div class="snowflake">❆</div><div class="snowflake">❅</div></div>

    <div style="width: 100%; max-width: 550px; text-align: center;">
        
        <div class="login-card text-start">
            <div class="text-center">
                <img src="imagenes/logoLosAngeles.png" alt="Logo" class="brand-logo">
                <h3 class="fw-bold mb-1 merry-font" style="color: var(--xmas-red);">Resultados en Línea</h3>
                <p class="text-muted small mb-4">Sistema seguro de descarga.</p>
            </div>

            <form id="formConsulta">
                <div class="mb-3">
                    <label class="small fw-bold text-secondary">DNI del Paciente</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-id-card text-danger"></i></span>
                        <input type="tel" id="dni" class="form-control border-start-0" placeholder="Ej: 12345678" required maxlength="15">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold text-secondary">Código de Seguridad</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-lock text-danger"></i></span>
                        <input type="password" id="codigo" class="form-control border-start-0" placeholder="Ej: 4829" required maxlength="10">
                    </div>
                </div>
                
                <button type="submit" class="btn-consultar"><i class="fas fa-search me-2"></i>Consultar Ahora</button>
            </form>

            <div id="resultArea" class="result-card">
                <div class="text-center mb-4">
                    <div class="text-success mb-2"><i class="fas fa-check-circle fa-3x" style="color: var(--xmas-green);"></i></div>
                    <h5 class="fw-bold text-dark mb-0" id="resPaciente">Nombre Paciente</h5>
                    <span class="badge bg-warning text-dark mt-1" id="resFecha">--/--/----</span>
                </div>
                
                <p class="small fw-bold text-secondary mb-3 ps-1"><i class="fas fa-folder-open me-1"></i> Documentos disponibles:</p>
                
                <div id="filesList" class="files-container">
                    </div>

                <div class="text-center mt-4">
                    <button onclick="location.reload()" class="btn btn-sm text-muted text-decoration-underline">Realizar otra consulta</button>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <a href="portal_resultados.php" class="back-link"><i class="fas fa-arrow-left me-1"></i> Volver al Portal</a>
        </div>

    </div>

    <div class="modal fade" id="pdfModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" style="height: 90vh;">
            <div class="modal-content h-100">
                <div class="modal-header bg-dark text-white py-2">
                    <h6 class="modal-title"><i class="fas fa-file-pdf me-2"></i>Visor de Resultados</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0 bg-light">
                    <iframe id="pdfFrame" src="" width="100%" height="100%" style="border:none;"></iframe>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('formConsulta').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.querySelector('.btn-consultar');
            const dni = document.getElementById('dni').value;
            const codigo = document.getElementById('codigo').value;
            
            const originalText = btn.innerHTML;
            btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando...';

            try {
                const res = await fetch(`api_resultados.php?dni=${dni}&codigo=${codigo}`);
                const data = await res.json();

                if (data.success) {
                    // Ocultamos formulario
                    document.getElementById('formConsulta').style.display = 'none';
                    const area = document.getElementById('resultArea');
                    const list = document.getElementById('filesList');
                    
                    document.getElementById('resPaciente').innerText = data.data.nombre_paciente;
                    document.getElementById('resFecha').innerText = data.data.fecha_registro;
                    
                    list.innerHTML = '';

                    // Generamos lista con BOTONES DE TEXTO + ICONO
                    data.data.archivos.forEach((ruta, index) => {
                        const item = document.createElement('div');
                        item.className = "file-item";
                        item.innerHTML = `
                            <div class="d-flex align-items-center">
                                <div class="bg-light rounded-circle p-2 me-3 text-danger border border-danger-subtle">
                                    <i class="fas fa-file-pdf fa-lg"></i>
                                </div>
                                <div class="d-flex flex-column text-start">
                                    <span class="fw-bold text-dark">Resultado de Lab. #${index + 1}</span>
                                    <span class="text-muted" style="font-size: 0.75rem;">Archivo PDF</span>
                                </div>
                            </div>
                            <div class="btn-group-custom">
                                <button onclick="verPDF('${ruta}')" class="btn-action btn-ver">
                                    <i class="fas fa-eye me-2"></i>Visualizar
                                </button>
                                <a href="${ruta}" download class="btn-action btn-descargar">
                                    <i class="fas fa-download me-2"></i>Descargar
                                </a>
                            </div>
                        `;
                        list.appendChild(item);
                    });
                    
                    area.style.display = 'block';
                    
                    // Alerta bonita
                    Swal.fire({
                        icon: 'success',
                        title: '¡Resultados Encontrados!',
                        text: 'Hemos localizado sus documentos.',
                        timer: 2000,
                        showConfirmButton: false
                    });

                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'No encontrado',
                        text: data.error || 'Verifique su DNI y código.',
                        confirmButtonColor: '#D32F2F'
                    });
                    btn.disabled = false; btn.innerHTML = originalText;
                }
            } catch (err) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Error de Conexión',
                    text: 'Intente nuevamente más tarde.',
                });
                btn.disabled = false; btn.innerHTML = originalText;
            }
        });

        // Función para abrir el Modal con el PDF
        function verPDF(ruta) {
            document.getElementById('pdfFrame').src = ruta;
            var myModal = new bootstrap.Modal(document.getElementById('pdfModal'));
            myModal.show();
        }
    </script>
</body>
</html>