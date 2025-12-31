<?php
include_once 'session.php'; 
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; } 
$user_name = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historia Clínica | ELAM</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Mountains+of+Christmas:wght@700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        :root { --primary: #1e40af; --bg-body: #f1f5f9; --gray: #64748b; --dark: #0f172a; --white: #ffffff; }
        * { box-sizing: border-box; font-family: 'Outfit', sans-serif; margin: 0; padding: 0; }
        
        body { background: var(--bg-body); color: var(--dark); height: 100vh; display: flex; flex-direction: column; overflow: hidden; }
        
        /* LAYOUT */
        .top-bar { background: var(--white); padding: 15px 30px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); z-index: 10; }
        .layout { display: grid; grid-template-columns: 350px 1fr; height: 100%; overflow: hidden; }
        
        /* SIDEBAR */
        .sidebar { background: #fff; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; z-index: 5; }
        .search-box { padding: 20px; border-bottom: 1px solid #f1f5f9; background: #f8fafc; display: flex; gap: 10px; flex-direction: column; }
        .search-wrapper { position: relative; }
        .search-input { width: 100%; padding: 12px 15px 12px 40px; border-radius: 12px; border: 1px solid #cbd5e1; outline: none; transition: 0.3s; }
        .search-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(30,64,175,0.1); }
        .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--gray); font-size: 1.2rem; }
        
        .btn-new { background: var(--primary); color: white; border: none; padding: 10px; border-radius: 10px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; transition: 0.2s; }
        .btn-new:hover { background: #1d4ed8; transform: translateY(-1px); }

        .patient-list { flex: 1; overflow-y: auto; padding: 10px; }
        .p-card { padding: 15px; border-radius: 12px; border: 1px solid #f1f5f9; margin-bottom: 10px; cursor: pointer; transition: 0.2s; background: white; }
        .p-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-color: var(--primary); }
        .p-card.active { background: #eff6ff; border-color: var(--primary); }
        .p-name { font-weight: 700; color: var(--dark); font-size: 0.95rem; }
        .p-info { font-size: 0.8rem; color: var(--gray); margin-top: 4px; display: flex; justify-content: space-between; }

        /* CONTENT */
        .content { background: #f8fafc; overflow-y: auto; padding: 30px; position: relative; }
        .empty-state { height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--gray); opacity: 0.7; }
        
        /* CARD PACIENTE */
        .patient-header { background: white; padding: 25px; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); display: flex; gap: 20px; align-items: center; margin-bottom: 25px; border: 1px solid #e2e8f0; }
        .avatar { width: 80px; height: 80px; background: #dbeafe; color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: 700; flex-shrink: 0; }
        
        /* BADGES */
        .ph-badge { background: #dcfce7; color: #15803d; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; border:1px solid #bbf7d0; display: inline-flex; align-items: center; gap: 4px; }
        .ph-badge-old { background: #fef3c7; color: #b45309; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; border:1px solid #fde68a; display: inline-flex; align-items: center; gap: 4px; }
        .ph-badge-sede { background: #e0f2fe; color: #0369a1; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; border: 1px solid #bae6fd; display: inline-flex; align-items: center; gap: 4px; }
        
        .ph-loc { color: var(--gray); font-size: 0.9rem; margin-top: 5px; display: flex; gap: 5px; align-items: center; }

        /* TIMELINE */
        .timeline { position: relative; padding-left: 20px; }
        .timeline::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 2px; background: #e2e8f0; }
        .t-item { position: relative; margin-bottom: 25px; padding-left: 25px; }
        .t-dot { position: absolute; left: -9px; top: 0; width: 20px; height: 20px; background: var(--primary); border: 4px solid #fff; border-radius: 50%; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .t-card { background: white; border-radius: 16px; padding: 20px; border: 1px solid #e2e8f0; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .t-header { display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; margin-bottom: 10px; }
        .t-date { font-weight: 800; color: var(--dark); font-size: 1.1rem; }
        .t-doc { color: var(--primary); font-weight: 600; }
        
        /* TRIAJE GRID */
        .t-triaje { display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 10px; margin-bottom: 15px; padding: 10px; background: #f0fdf4; border-radius: 10px; color: #166534; font-size: 0.85rem; font-weight: 600; border: 1px solid #bbf7d0; text-align: center; }
        
        .t-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 0.9rem; }
        .t-label { font-size: 0.75rem; font-weight: 700; color: var(--gray); text-transform: uppercase; margin-bottom: 3px; }
        .t-val { color: var(--dark); }
        
        .btn-receta { display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px; background: #fff7ed; color: #c2410c; border-radius: 20px; text-decoration: none; font-weight: 600; font-size: 0.8rem; border: 1px solid #fed7aa; margin-top: 10px; cursor: pointer; }
        .btn-back { text-decoration: none; color: var(--gray); display: flex; align-items: center; gap: 5px; font-weight: 600; transition: 0.2s; }
        .btn-back:hover { color: var(--primary); }

        /* MODAL */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; display: none; align-items: center; justify-content: center; backdrop-filter: blur(2px); }
        .modal-overlay.show { display: flex; }
        .modal { background: white; width: 90%; max-width: 600px; border-radius: 20px; padding: 25px; box-shadow: 0 20px 50px rgba(0,0,0,0.2); max-height: 90vh; overflow-y: auto; }
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; font-size: 0.8rem; font-weight: 700; color: var(--gray); margin-bottom: 5px; }
        .form-input { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; outline: none; font-size: 0.9rem; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
    </style>
</head>
<body>

    <div class="top-bar">
        <div style="display:flex; align-items:center; gap:15px;">
            <a href="panel_citas.php" class="btn-back"><i class="ph ph-arrow-left"></i> Volver a Recepción</a>
            <div style="width:1px; height:20px; background:#e2e8f0;"></div>
            <h2 style="font-family:'Outfit',sans-serif; font-size:1.2rem; color:var(--primary);">📂 Historias Clínicas</h2>
        </div>
        <div style="display:flex; gap:10px; align-items:center;">
            <span style="font-weight:600; color:var(--dark)"><?php echo htmlspecialchars($user_name); ?></span>
            <div style="width:35px; height:35px; background:var(--primary-soft); border-radius:50%; display:flex; align-items:center; justify-content:center; color:var(--primary);"><i class="ph ph-user"></i></div>
        </div>
    </div>

    <div class="layout">
        
        <div class="sidebar">
            <div class="search-box">
                <button class="btn-new" onclick="abrirModalCrear()"><i class="ph ph-plus-circle" style="font-size:1.2rem;"></i> Nueva Historia Médica</button>
                <div class="search-wrapper">
                    <i class="ph ph-magnifying-glass search-icon"></i>
                    <input type="text" id="buscador" class="search-input" placeholder="Buscar DNI, Nombre..." onkeyup="buscarPaciente(this.value)">
                </div>
            </div>
            <div id="lista-resultados" class="patient-list">
                <div style="text-align:center; padding:20px; color:#94a3b8;">
                    <i class="ph ph-users" style="font-size:2rem; margin-bottom:10px;"></i><br>
                    Busque o cree un paciente...
                </div>
            </div>
        </div>

        <div class="content" id="detalle-paciente">
            <div class="empty-state">
                <i class="ph ph-folder-open" style="font-size:4rem; margin-bottom:15px;"></i>
                <h3>Seleccione un paciente</h3>
                <p>Verá su información personal y todo su historial de atenciones.</p>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="modal-crear">
        <div class="modal">
            <h2 style="margin-bottom:20px; color:var(--primary);">Nueva Historia Clínica</h2>
            
            <div class="form-group"><label class="form-label">DNI</label><div style="display:flex; gap:8px;"><input id="new-dni" class="form-input" placeholder="8 dígitos" maxlength="8"><button class="btn-new" style="width:auto; padding:0 15px;" onclick="buscarReniec()"><i class="ph ph-magnifying-glass"></i></button></div></div>
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                <div class="form-group"><label class="form-label">Nombres</label><input id="new-nombres" class="form-input"></div>
                <div class="form-group"><label class="form-label">Apellidos</label><input id="new-apellidos" class="form-input"></div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                <div class="form-group"><label class="form-label">Teléfono</label><input id="new-telefono" class="form-input"></div>
                <div class="form-group"><label class="form-label">Nacimiento</label><input type="date" id="new-nacimiento" class="form-input"></div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:10px;">
                <div class="form-group"><label class="form-label">Departamento</label><input id="new-dep" class="form-input" placeholder="Lima"></div>
                <div class="form-group"><label class="form-label">Provincia</label><input id="new-prov" class="form-input" placeholder="Lima"></div>
                <div class="form-group"><label class="form-label">Distrito</label><input id="new-dist" class="form-input" placeholder="Miraflores"></div>
            </div>
            <div class="form-group"><label class="form-label">Dirección Completa</label><input id="new-dir" class="form-input" placeholder="Av. Principal 123..."></div>

            <div style="background:#f1f5f9; padding:15px; border-radius:10px; border:1px solid #e2e8f0;">
                <div class="form-group">
                    <label class="form-label" style="color:var(--primary)"><i class="ph ph-file-text"></i> N° Historia Antigua (ZQCLINIC)</label>
                    <input id="new-zq" class="form-input" placeholder="Ingrese el código manual (Opcional)">
                </div>
                <p style="font-size:0.8rem; color:#64748b; margin:0;"><i class="ph ph-info"></i> El N° de Historia Nueva se generará automáticamente (Ej: HCL-0001).</p>
            </div>

            <div class="modal-footer"><button class="btn-new" style="background:#e2e8f0; color:black;" onclick="cerrarModalCrear()">Cancelar</button><button class="btn-new" onclick="guardarPaciente()">Guardar Ficha</button></div>
        </div>
    </div>

    <script>
        // --- BUSQUEDA ---
        let searchTimeout;
        function buscarPaciente(term) {
            clearTimeout(searchTimeout);
            if(term.length < 3) return;

            searchTimeout = setTimeout(async () => {
                const list = document.getElementById('lista-resultados');
                list.innerHTML = '<div style="text-align:center; padding:10px; color:gray"><i class="ph ph-spinner ph-spin"></i> Buscando...</div>';
                
                try {
                    const res = await fetch(`api_hcl_buscar.php?q=${encodeURIComponent(term)}`);
                    const data = await res.json();
                    
                    list.innerHTML = '';
                    if(data.length === 0) {
                        list.innerHTML = '<div style="text-align:center; padding:20px; color:gray">No se encontraron pacientes.</div>';
                        return;
                    }

                    data.forEach(p => {
                        const div = document.createElement('div');
                        div.className = 'p-card';
                        div.onclick = () => cargarHistorial(p);
                        div.innerHTML = `
                            <div class="p-name">${p.nombres} ${p.apellidos}</div>
                            <div class="p-info">
                                <span><i class="ph ph-identification-card"></i> ${p.dni}</span>
                                <span><i class="ph ph-folder"></i> HC: ${p.historia_clinica || p.dni}</span>
                            </div>
                        `;
                        list.appendChild(div);
                    });
                } catch(e) { console.error(e); }
            }, 300);
        }

        // --- CARGAR HISTORIAL ---
        async function cargarHistorial(paciente) {
            document.querySelectorAll('.p-card').forEach(el => el.classList.remove('active'));
            event.currentTarget.classList.add('active');

            const content = document.getElementById('detalle-paciente');
            content.innerHTML = '<div style="text-align:center; margin-top:50px;"><i class="ph ph-spinner ph-spin" style="font-size:2rem;"></i><p>Cargando historial...</p></div>';

            try {
                // Ahora la API devuelve un objeto { paciente: {...}, historial: [...] }
                const res = await fetch(`api_hcl_historial.php?dni=${paciente.dni}`);
                const data = await res.json();
                
                const p = data.paciente || paciente; // Usar datos frescos si existen
                const hList = data.historial || [];

                // Armar dirección
                let ubicacion = [p.distrito, p.provincia, p.departamento].filter(Boolean).join(', ');
                if(!ubicacion) ubicacion = "Ubicación no registrada";

                // Badges
                let badgeZQ = p.codigo_zqclinic ? `<span class="ph-badge-old" title="Código Antiguo">ZQ: ${p.codigo_zqclinic}</span>` : '';
                
                // Badge Sede Origen (Si viene de la API)
                let nombreSede = p.nombre_sede_origen || 'Desconocido/Migración';
                let badgeSede = `<span class="ph-badge-sede" title="Sede de Origen"><i class="ph ph-buildings"></i> Origen: ${nombreSede}</span>`;

                // --- EDAD (Nuevo Cálculo) ---
                let edadHtml = p.edad ? ` <strong style="color:var(--primary)">(${p.edad} años)</strong>` : '';

                let html = `
                    <div class="patient-header">
                        <div class="avatar">${p.nombres.charAt(0)}</div>
                        <div style="flex:1">
                            <h1 style="font-size:1.5rem; margin-bottom:5px;">${p.nombres} ${p.apellidos}</h1>
                            <div style="display:flex; gap:10px; color:var(--gray); font-size:0.9rem; flex-wrap:wrap; align-items:center;">
                                <span><i class="ph ph-identification-card"></i> DNI: <b>${p.dni}</b></span>
                                <span><i class="ph ph-phone"></i> <b>${p.telefono}</b></span>
                                <span><i class="ph ph-cake"></i> ${p.fecha_nacimiento || '--'} ${edadHtml}</span>
                                <span class="ph-badge">HC: ${p.historia_clinica || '---'}</span>
                                ${badgeZQ}
                                ${badgeSede}
                            </div>
                            <div class="ph-loc"><i class="ph ph-map-pin"></i> ${p.direccion || ''} <small>(${ubicacion})</small></div>
                        </div>
                    </div>
                    
                    <h3 style="margin-bottom:20px; color:var(--primary); border-bottom:1px solid #e2e8f0; padding-bottom:10px;">Línea de Tiempo de Atenciones</h3>
                    <div class="timeline">
                `;

                if(hList.length === 0) {
                    html += `<p style="color:gray; padding-left:20px;">Este paciente no tiene atenciones registradas aún.</p>`;
                } else {
                    hList.forEach(h => {
                        let btnReceta = '';
                        if(h.archivo_receta) {
                            const safeUrl = h.archivo_receta.replace(/\\/g, '/').replace(/'/g, "\\'");
                            btnReceta = `<div style="margin-top:10px;"><button class="btn-receta" onclick="window.open('${safeUrl}', '_blank')"><i class="ph ph-file-pdf"></i> Ver Receta Adjunta</button></div>`;
                        }

                        let estadoColor = h.estado_cita === 'ATENDIDO' ? '#16a34a' : '#d97706';
                        
                        html += `
                            <div class="t-item">
                                <div class="t-dot"></div>
                                <div class="t-card">
                                    <div class="t-header">
                                        <span class="t-date">${h.fecha} <small style="font-weight:400; color:#64748b;">${h.hora}</small></span>
                                        <span class="t-doc">${h.doctor || 'No asignado'} <span style="color:#94a3b8">(${h.especialidad})</span></span>
                                    </div>
                                    
                                    <div class="t-triaje">
                                        <div><i class="ph ph-scales"></i> ${h.peso} kg</div>
                                        <div><i class="ph ph-ruler"></i> ${h.talla} m</div>
                                        <div><i class="ph ph-heartbeat"></i> ${h.presion}</div>
                                        <div><i class="ph ph-thermometer"></i> ${h.temperatura}°</div>
                                        <div><i class="ph ph-drop"></i> ${h.saturacion}%</div>
                                        <div><i class="ph ph-heart"></i> ${h.fc} bpm</div>
                                        <div><i class="ph ph-lungs"></i> ${h.fr} rpm</div>
                                    </div>

                                    <div class="t-grid">
                                        <div>
                                            <div class="t-label">Observaciones / Diagnóstico</div>
                                            <div class="t-val">${h.observaciones || '<i>Sin observaciones</i>'}</div>
                                        </div>
                                        <div>
                                            <div class="t-label">Tratamiento Indicado</div>
                                            <div class="t-val" style="font-weight:600;">${h.tratamientos || '---'}</div>
                                        </div>
                                    </div>
                                    <div style="margin-top:10px; font-size:0.85rem; color:#64748b; display:flex; justify-content:space-between; align-items:center;">
                                        <span>Estado: <b style="color:${estadoColor}">${h.estado_cita}</b></span>
                                        <span>Triaje: ${h.hora_triaje || '--:--'}</span>
                                    </div>
                                    ${btnReceta}
                                </div>
                            </div>
                        `;
                    });
                }
                html += `</div>`; // Cierre timeline
                content.innerHTML = html;

            } catch(e) {
                content.innerHTML = `<div style="color:red; text-align:center;">Error al cargar datos: ${e.message}</div>`;
            }
        }

        // --- CREAR PACIENTE ---
        function abrirModalCrear() {
            // Lista segura de IDs para evitar errores si alguno no existe
            const ids = [
                'new-dni','new-nombres','new-apellidos','new-telefono','new-nacimiento',
                'new-zq','new-dep','new-prov','new-dist','new-dir'
            ];
            ids.forEach(id => {
                const el = document.getElementById(id);
                if(el) el.value = '';
            });
            document.getElementById('modal-crear').classList.add('show');
        }
        function cerrarModalCrear() { document.getElementById('modal-crear').classList.remove('show'); }

        async function buscarReniec() {
            const dni = document.getElementById('new-dni').value;
            if(dni.length !== 8) return alert("DNI debe tener 8 dígitos");
            
            try {
                const r = await fetch(`proxy.php?dni=${dni}`);
                const j = await r.json();
                const d = j.data || j;
                
                if(d.nombres) {
                    document.getElementById('new-nombres').value = d.nombres;
                    document.getElementById('new-apellidos').value = `${d.apellido_paterno} ${d.apellido_materno}`;
                    
                    // --- NUEVO: Lógica para Fecha de Nacimiento ---
                    if(d.fecha_nacimiento) {
                        // Detectar si viene como DD/MM/AAAA (formato peruano común)
                        if(d.fecha_nacimiento.includes('/')) {
                            const partes = d.fecha_nacimiento.split('/');
                            if(partes.length === 3) {
                                // Convertir a AAAA-MM-DD para el input HTML
                                const fechaIso = `${partes[2]}-${partes[1]}-${partes[0]}`;
                                document.getElementById('new-nacimiento').value = fechaIso;
                            }
                        } else {
                            // Si ya viene como AAAA-MM-DD
                            document.getElementById('new-nacimiento').value = d.fecha_nacimiento;
                        }
                    }
                    // ---------------------------------------------

                    // Autocompletar ubicación si la API lo trae
                    if(d.departamento) document.getElementById('new-dep').value = d.departamento;
                    if(d.provincia) document.getElementById('new-prov').value = d.provincia;
                    if(d.distrito) document.getElementById('new-dist').value = d.distrito;
                    if(d.direccion) document.getElementById('new-dir').value = d.direccion;
                } else { alert("DNI no encontrado"); }
            } catch(e) { alert("Error de conexión"); }
        }

        async function guardarPaciente() {
            const dni = document.getElementById('new-dni').value;
            const nom = document.getElementById('new-nombres').value;
            
            if(!dni || !nom) return alert("DNI y Nombres son obligatorios");

            const payload = {
                dni: dni,
                nombres: nom,
                apellidos: document.getElementById('new-apellidos').value,
                telefono: document.getElementById('new-telefono').value,
                nacimiento: document.getElementById('new-nacimiento').value,
                zq: document.getElementById('new-zq').value, // HC Antigua
                departamento: document.getElementById('new-dep').value,
                provincia: document.getElementById('new-prov').value,
                distrito: document.getElementById('new-dist').value,
                direccion: document.getElementById('new-dir').value
            };

            try {
                const r = await fetch('api_hcl_crear.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                const res = await r.json();
                
                if(res.success) {
                    alert("¡Paciente creado! \nHistoria Nueva: " + res.hc_nueva); 
                    cerrarModalCrear();
                    document.getElementById('buscador').value = dni; // Auto-buscar
                    buscarPaciente(dni);
                } else {
                    alert("Error: " + res.error);
                }
            } catch(e) { alert("Error de red"); }
        }
    </script>
</body>
</html>