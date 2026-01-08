<?php
include_once 'session.php';

// SEGURIDAD: Solo Admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión Médica | Admin</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
<script src="https://unpkg.com/@phosphor-icons/web"></script>

<style>
/* =======================
   PALETA CORPORATIVA
======================= */
:root{
  --primary:#2f7f77;          /* Integra Verde */
  --secondary:#6b4c7a;        /* Integra Morado */
  --accent:#0d5bd7;           /* Ángeles Azul */
  --bg:#f4f6f9;
  --white:#ffffff;
  --border:#e5e7eb;
  --danger:#dc2626;
  --success:#16a34a;
  --text:#1f2937;
  --muted:#64748b;
}

/* =======================
   BASE
======================= */
body{
  font-family:'Inter',sans-serif;
  background:var(--bg);
  margin:0;
  display:flex;
  height:100vh;
  overflow:hidden;
  color:var(--text);
}
.hidden{display:none!important}

/* =======================
   SIDEBAR
======================= */
.sidebar{
  width:260px;
  background:linear-gradient(180deg,var(--primary),#256e66);
  color:white;
  padding:1.6rem;
  display:flex;
  flex-direction:column;
}
.brand{
  font-size:1.15rem;
  font-weight:700;
  margin-bottom:2rem;
  display:flex;
  align-items:center;
  gap:10px;
}
.menu button{
  background:none;
  border:none;
  width:100%;
  text-align:left;
  font-size:.9rem;
  display:flex;
  align-items:center;
  gap:10px;
  padding:12px;
  color:rgba(255,255,255,.75);
  cursor:pointer;
  border-radius:10px;
  margin-bottom:6px;
  transition:.2s;
}
.menu button:hover,
.menu button.active{
  background:rgba(255,255,255,.15);
  color:#fff;
}

/* =======================
   CONTENT
======================= */
.content{
  flex:1;
  padding:2rem;
  overflow-y:auto;
}
.top-nav{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:20px;
}

/* =======================
   CARDS
======================= */
.card{
  background:var(--white);
  padding:1.6rem;
  border-radius:16px;
  border:1px solid var(--border);
  margin-bottom:2rem;
  box-shadow:0 6px 18px rgba(0,0,0,.06);
}
.grid-form{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
  gap:1rem;
  margin-top:1rem;
}

label{
  display:block;
  font-size:.8rem;
  font-weight:600;
  color:var(--muted);
  margin-bottom:6px;
}

input,select{
  width:100%;
  padding:.65rem;
  border:1px solid var(--border);
  border-radius:10px;
  outline:none;
  font-family:inherit;
}
input:focus,select:focus{
  border-color:var(--primary);
  box-shadow:0 0 0 3px rgba(47,127,119,.15);
}

/* =======================
   BOTONES
======================= */
.btn-save{
  background:var(--primary);
  color:white;
  border:none;
  padding:.75rem 1.6rem;
  border-radius:12px;
  cursor:pointer;
  font-weight:600;
  margin-top:1rem;
  transition:.2s;
}
.btn-save:hover{background:#256e66}

.btn-icon{
  border:none;
  background:none;
  cursor:pointer;
  font-size:1.1rem;
}
.btn-del{color:var(--danger)}
.btn-view{color:var(--accent);text-decoration:none;font-size:.85rem}
.btn-search{
  background:var(--accent);
  color:white;
  border:none;
  padding:0 12px;
  border-radius:10px;
  cursor:pointer;
}
.btn-plus{
  background:var(--success);
  color:white;
  border:none;
  width:40px;
  border-radius:10px;
  cursor:pointer;
  font-size:1.2rem;
}
.btn-back{
  background:#e2e8f0;
  color:#334155;
  text-decoration:none;
  padding:8px 16px;
  border-radius:10px;
  font-weight:600;
  font-size:.85rem;
}

/* =======================
   TABLAS
======================= */
table{
  width:100%;
  border-collapse:collapse;
  margin-top:1rem;
  font-size:.9rem;
}
th{
  text-align:left;
  padding:12px;
  background:#f1f5f9;
  border-bottom:2px solid var(--border);
}
td{
  padding:12px;
  border-bottom:1px solid var(--border);
  vertical-align: middle;
}

/* =======================
   BADGES
======================= */
.badge{
  padding:4px 8px;
  border-radius:6px;
  font-size:.7rem;
  font-weight:700;
}
.rol-admin{background:#ede9fe;color:#5b21b6}
.rol-usuario{background:#dcfce7;color:#15803d}
.badge-sede{
  background:#e0f2fe;
  color:#0369a1;
  padding:4px 8px;
  border-radius:6px;
  font-weight:600;
  font-size:.75rem;
}

/* =======================
   HORARIOS
======================= */
.horario-box{
  background:#f8fafc;
  padding:15px;
  border-radius:12px;
  border:1px solid var(--border);
  margin-bottom:1.5rem;
}
.schedule-row{
  display:flex;
  gap:10px;
  align-items:center;
  margin-bottom:8px;
}
.btn-remove-row{
  color:var(--danger);
  cursor:pointer;
  font-size:1.2rem;
}
.grid-precios{
  display:grid;
  grid-template-columns:1fr 1fr 1fr;
  gap:2rem;
  margin-top:1.5rem;
  padding-top:1rem;
  border-top:1px dashed var(--border);
}
input[readonly]{
  background:#f8fafc;
  color:#94a3b8;
}
</style>
</head>
<body>
    <div class="sidebar">
        <div class="brand"><i class="ph ph-first-aid-kit"></i> Gestión Médica</div>
        <div class="menu">
            <button onclick="showView('usuarios')" id="btn-usu" class="active"><i class="ph ph-users"></i> Usuarios Sistema</button>
            <button onclick="showView('doctores')" id="btn-doc"><i class="ph ph-stethoscope"></i> Staff Médico</button>
            <button onclick="showView('inventario')" id="btn-inv"><i class="ph ph-pill"></i> Inventario Clínico</button>
            <button onclick="showView('catalogo')" id="btn-cat"><i class="ph ph-tag"></i> Catálogo / Precios</button>
            <button onclick="showView('sedes')" id="btn-sed"><i class="ph ph-buildings"></i> Sedes & Seguridad</button>
            <button onclick="showView('programacion')" id="btn-prog"><i class="ph ph-calendar-plus"></i> Rotación Personal</button>
            <div style="margin-top:auto; padding-top:20px; border-top:1px solid rgba(255,255,255,0.1)">
                <a href="intranet.php" style="color:#94a3b8; text-decoration:none; display:flex; align-items:center; gap:10px; padding:10px;">
                    <i class="ph ph-arrow-u-up-left"></i> Volver a Intranet
                </a>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="top-nav">
            <h2 id="page-title" style="margin:0; color:var(--primary)">Usuarios</h2>
            <div style="font-size:0.9rem; color:#64748b">Hola, <b><?php echo $_SESSION['user_name']; ?></b></div>
        </div>

        <div id="view-usuarios">
            <div class="card">
                <h3>Gestión Unificada de Usuarios</h3>
                <div class="grid-form">
                    <div>
                        <label>Nombre Completo</label>
                        <input type="text" id="usu-nombre" placeholder="Ej: María Pérez">
                    </div>
                    <div>
                        <label>Usuario / Email</label>
                        <input type="text" id="usu-user" placeholder="maria@clinica.com">
                    </div>
                    <div>
                        <label>Contraseña</label>
                        <input type="password" id="usu-pass">
                    </div>
                    <div>
                        <label>Rol Principal</label>
                        <select id="usu-rol">
                            <option value="usuario">Recepcionista / Staff</option>
                            <option value="admin">Administrador Total</option>
                        </select>
                    </div>
                    
                    <div style="display:flex; align-items:center; gap:10px; padding-top:20px; grid-column: 1 / -1;">
                        <input type="checkbox" id="check-supervisor" style="width:20px; height:20px; accent-color: var(--success);">
                        <label for="check-supervisor" style="margin:0; color:var(--success); font-weight:bold; cursor:pointer;">
                            ✅ Activar también como Supervisor de Checklist
                        </label>
                    </div>
                </div>
                <button class="btn-save" onclick="guardarUsuario()">Guardar Usuario Unificado</button>
            </div>
            
            <div class="card">
                <h3>Lista de Usuarios</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Usuario</th>
                            <th>Rol Sistema</th>
                            <th>¿Es Supervisor?</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tb-usuarios"></tbody>
                </table>
            </div>
        </div>

        <div id="view-doctores" class="hidden">
            <div class="card">
                <h3>Nuevo Doctor</h3>
                <div class="grid-form" style="grid-template-columns: 1fr 1fr 1fr;">
                    <div><label>DNI Médico</label><div style="display:flex; gap:5px"><input type="text" id="doc-dni" placeholder="8 dígitos"><button class="btn-search" onclick="buscarDNIDoc()"><i class="ph ph-magnifying-glass"></i></button></div></div>
                    <div><label>Nombre Completo</label><input type="text" id="doc-nombre" readonly placeholder="Autocompletado..."></div>
                    <div><label>Especialidad</label><div style="display:flex; gap:5px;"><select id="doc-esp" style="flex:1"></select><button class="btn-plus" onclick="nuevaEspecialidad()">+</button></div></div>
                    <div><label>CMP</label><input type="text" id="doc-cmp"></div>
                    <div><label>RNE</label><input type="text" id="doc-rne"></div>
                    <div><label>Teléfono</label><input type="text" id="doc-tel"></div>
                    <div style="grid-column: span 3;"><label>Cargar CV (PDF)</label><input type="file" id="doc-cv" accept="application/pdf"></div>
                </div>
                <button class="btn-save" onclick="guardarDoctor()">Guardar Doctor</button>
            </div>
            <div class="card"><table><thead><tr><th>DNI</th><th>Nombre</th><th>CMP / RNE</th><th>CV</th></tr></thead><tbody id="tb-doctores"></tbody></table></div>
        </div>

        <div id="view-inventario" class="hidden">
            <div class="card">
                <h3>📦 Gestión de Listas Clínicas</h3>
                <p style="color:#64748b; margin-bottom:20px">Agregue opciones para los desplegables del panel de atención.</p>
                
                <div class="grid-form" style="grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                    <div style="background:#f8fafc; padding:15px; border-radius:12px; border:1px solid #e2e8f0">
                        <h4 style="margin:0 0 10px 0; color:var(--primary)">🧪 Suplementos</h4>
                        <div style="display:flex; gap:5px; margin-bottom:10px">
                            <input id="new-suple" placeholder="Nombre...">
                            <button class="btn-plus" onclick="addInv('suple')">+</button>
                        </div>
                        <ul id="list-suple" style="list-style:none; padding:0; height:200px; overflow-y:auto;"></ul>
                    </div>

                    <div style="background:#f8fafc; padding:15px; border-radius:12px; border:1px solid #e2e8f0">
                        <h4 style="margin:0 0 10px 0; color:var(--secondary)">🩺 Procedimientos</h4>
                        <div style="display:flex; gap:5px; margin-bottom:10px">
                            <input id="new-proc" placeholder="Nombre...">
                            <button class="btn-plus" onclick="addInv('proc')">+</button>
                        </div>
                        <ul id="list-proc" style="list-style:none; padding:0; height:200px; overflow-y:auto;"></ul>
                    </div>

                    <div style="background:#f8fafc; padding:15px; border-radius:12px; border:1px solid #e2e8f0">
                        <h4 style="margin:0 0 10px 0; color:var(--accent)">💊 Medicamentos</h4>
                        <div style="display:flex; gap:5px; margin-bottom:10px">
                            <input id="new-med" placeholder="Nombre...">
                            <button class="btn-plus" onclick="addInv('med')">+</button>
                        </div>
                        <ul id="list-med" style="list-style:none; padding:0; height:200px; overflow-y:auto;"></ul>
                    </div>
                </div>
            </div>
        </div>

        <div id="view-catalogo" class="hidden">
            <div class="card">
                <h3>Configurar Servicio</h3>
                <div class="grid-form" style="display:block">
                    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1rem; margin-bottom:1rem">
                        <div><label>Doctor</label><select id="cat-doc-sel"></select></div>
                        <div><label>Sede</label><select id="cat-sede"></select></div>
                        <div><label>Tipo de Servicio</label><div style="display:flex; gap:5px;"><select id="cat-tipo" style="flex:1"></select><button class="btn-plus" onclick="nuevoTipoServicio()">+</button></div></div>
                    </div>
                    <label>Horario Referencial</label>
                    <div class="horario-box">
                        <div id="schedule-list"></div>
                        <button type="button" class="btn-back" style="font-size:0.8rem; margin-top:10px" onclick="addScheduleRow()">+ Agregar Horario</button>
                    </div>
                    <div class="grid-precios">
                        <div><label>P.V.P (Venta S/)</label><input type="number" id="cat-pv" step="0.01" oninput="calcGan()" style="font-weight:700"></div>
                        <div><label>P. Costo (S/)</label><input type="number" id="cat-pc" step="0.01" oninput="calcGan()"></div>
                        <div><label>Margen (S/)</label><input type="number" id="cat-gan" readonly style="background:#f1f5f9;color:var(--success);font-weight:bold"></div>
                    </div>
                </div>
                <button class="btn-save" onclick="guardarCatalogo()">Guardar en Catálogo</button>
            </div>
            <div class="card"><table><thead><tr><th>Sede</th><th>Tipo</th><th>Doctor</th><th>Horario</th><th>P.V.P</th><th>Margen</th><th>Acción</th></tr></thead><tbody id="tb-catalogo"></tbody></table></div>
        </div>

        <div id="view-sedes" class="hidden">
            <div class="card">
                <h3>Nueva Sede</h3>
                <div class="grid-form">
                    <div><label>Nombre Sede</label><input type="text" id="sede-nom" placeholder="Ej: Sede Norte"></div>
                    <div><label>Dirección</label><input type="text" id="sede-dir" placeholder="Av. Principal 123"></div>
                </div>
                <button class="btn-save" onclick="guardarSede()">Crear Sede</button>
            </div>
            <div class="card">
                <h3>Sedes Activas & Seguridad</h3>
                <div style="background:#fff7ed; padding:10px; border-radius:8px; border:1px solid #fed7aa; margin-bottom:15px; font-size:0.85rem; color:#c2410c;">
                    <i class="ph ph-shield-warning"></i> <b>Control de Acceso:</b> Si asigna una IP, solo se podrá acceder al sistema desde esa conexión de internet. Déjelo vacío para acceso libre.
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Sede / Dirección</th>
                            <th style="width: 280px;">Control de Acceso (IP Router)</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tb-sedes"></tbody>
                </table>
            </div>
        </div>

        <div id="view-programacion" class="hidden">
            <div class="card">
                <h3>Asignar Rotación Diaria</h3>
                <div style="background:#f0fdf4; border:1px solid #bbf7d0; color:#15803d; padding:10px; border-radius:8px; margin-bottom:15px; font-size:0.9rem;">
                    <i class="ph ph-info"></i> Seleccione el personal y la sede para asignar el día de trabajo completo.
                </div>
                <div class="grid-form">
                    <div>
                        <label>Usuario</label>
                        <select id="sel-usuario"></select>
                    </div>
                    <div>
                        <label>Sede Asignada</label>
                        <select id="sel-sede"></select>
                    </div>
                    <div>
                        <label>Fecha de Trabajo</label>
                        <input type="date" id="fecha-turno">
                    </div>
                </div>

                <button class="btn-save" onclick="guardarTurno()">Confirmar Asignación</button>
            </div>
            
            <div class="card">
                <h3>Rotación Activa</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th>Sede</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-turnos"></tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        let sedes=[], especialidades=[], doctores=[];

        document.addEventListener('DOMContentLoaded', () => { init(); addScheduleRow(); document.getElementById('fecha-turno').valueAsDate = new Date(); });

        function showView(v) {
            // Ocultar todas las vistas y remover clase active de todos los botones
            ['usuarios','doctores','inventario','catalogo','sedes','programacion'].forEach(i => { 
                document.getElementById('view-'+i).classList.add('hidden'); 
                const btn = document.getElementById('btn-'+(i==='programacion'?'prog':(i==='usuarios'?'usu':(i==='doctores'?'doc':(i==='catalogo'?'cat':(i==='inventario'?'inv':'sed'))))));
                if(btn) btn.classList.remove('active'); 
            });

            // Mostrar la vista seleccionada y activar botón
            document.getElementById('view-'+v).classList.remove('hidden'); 
            const map = {
                'programacion':'btn-prog',
                'usuarios':'btn-usu',
                'doctores':'btn-doc',
                'inventario':'btn-inv',
                'catalogo':'btn-cat',
                'sedes':'btn-sed'
            }; 
            document.getElementById(map[v]).classList.add('active');
            
            const titles = {
                'usuarios':'Usuarios del Sistema',
                'doctores':'Staff Médico',
                'inventario':'Inventario Clínico',
                'catalogo':'Catálogo de Precios',
                'sedes':'Sedes & Seguridad',
                'programacion':'Rotación de Personal'
            };
            document.getElementById('page-title').innerText = titles[v];

            if(v === 'inventario') { cargarInventario(); }
            if(v === 'sedes') { cargarSedes(); }
        }

        function addScheduleRow() {
            const container = document.getElementById('schedule-list'); const div = document.createElement('div'); div.className = 'schedule-row';
            div.innerHTML = `<select class="sch-day"><option value="Lunes">Lunes</option><option value="Martes">Martes</option><option value="Miércoles">Miércoles</option><option value="Jueves">Jueves</option><option value="Viernes">Viernes</option><option value="Sábado">Sábado</option><option value="Domingo">Domingo</option></select><span>De</span> <input type="time" class="sch-start" value="08:00"><span>a</span> <input type="time" class="sch-end" value="13:00"><i class="ph ph-trash btn-remove-row" onclick="this.parentElement.remove()"></i>`;
            container.appendChild(div);
        }

        async function init() { await cargarAuxiliares(); cargarUsuarios(); cargarDoctores(); cargarCatalogo(); listarTurnos(); }

        async function cargarAuxiliares() {
            try {
                const rS = await fetch('admin_sedes_backend.php'); 
                sedes = await rS.json();
                
                const selCatSede = document.getElementById('cat-sede');
                const selProgSede = document.getElementById('sel-sede');
                
                selCatSede.innerHTML = '<option value="">-- Sede --</option>'; 
                selProgSede.innerHTML = '<option value="">-- Sede --</option>';
                
                sedes.forEach(s => {
                    selCatSede.add(new Option(s.nombre, s.id));
                    selProgSede.add(new Option(s.nombre, s.id));
                });
                
                const rE = await fetch('admin_especialidades.php'); 
                especialidades = await rE.json();
                const selE = document.getElementById('doc-esp'); 
                selE.innerHTML = '<option value="">-- Especialidad --</option>';
                especialidades.forEach(e => selE.add(new Option(e.nombre, e.id)));
                
                cargarTiposServicio();
            } catch(e) { console.log(e); }
        }
        
        async function cargarTiposServicio() { try{const r=await fetch('admin_tipos_backend.php');const d=await r.json();const sel=document.getElementById('cat-tipo');sel.innerHTML='<option value="">-- Tipo --</option>';d.forEach(t=>sel.add(new Option(t.nombre,t.id)));}catch(e){} }
        async function nuevoTipoServicio() { const n=prompt("Nuevo tipo:"); if(n){await fetch('admin_tipos_backend.php',{method:'POST',body:JSON.stringify({nombre:n})});cargarTiposServicio();} }
        async function nuevaEspecialidad() { const n=prompt("Nueva Especialidad:"); if(n){await fetch('admin_especialidades.php',{method:'POST',body:JSON.stringify({nombre:n})});cargarAuxiliares();} }

        // --- CRUD USUARIOS ---
        async function cargarUsuarios() { 
            try {
                const r = await fetch('admin_usuarios_backend.php'); 
                const d = await r.json(); 
                
                const tb = document.getElementById('tb-usuarios'); 
                tb.innerHTML = ''; 
                
                const selP = document.getElementById('sel-usuario');
                selP.innerHTML = '<option>-- Personal --</option>'; 
                
                if(Array.isArray(d)) {
                    d.forEach(u => { 
                        if(u.rol !== 'admin') selP.add(new Option(u.nombre_completo, u.id)); 
                        
                        const esSup = u.es_supervisor == 1 ? '<span style="color:green; font-weight:bold">✔ SÍ</span>' : '<span style="color:#ccc">No</span>';
                        
                        tb.innerHTML += `
                        <tr>
                            <td>${u.nombre_completo}</td>
                            <td>${u.usuario||u.email}</td>
                            <td><span class="badge ${u.rol==='admin'?'rol-admin':'rol-usuario'}">${u.rol}</span></td>
                            <td>${esSup}</td>
                            <td><button onclick="borrar('delete_user',${u.id})" class="btn-icon btn-del"><i class="ph ph-trash"></i></button></td>
                        </tr>`; 
                    });
                }
            } catch (e) { console.error(e); }
        }

        async function guardarUsuario() { 
            const d = {
                nombre: document.getElementById('usu-nombre').value, 
                user: document.getElementById('usu-user').value, 
                pass: document.getElementById('usu-pass').value, 
                rol: document.getElementById('usu-rol').value, 
                es_supervisor: document.getElementById('check-supervisor').checked
            }; 
            if(!d.nombre || !d.user || !d.pass) return alert("Faltan datos obligatorios");

            try {
                const res = await fetch('admin_usuarios_backend.php',{method:'POST',body:JSON.stringify(d)}); 
                const json = await res.json();
                if(json.success) {
                    alert('Usuario creado correctamente'); 
                    document.getElementById('usu-nombre').value = '';
                    document.getElementById('usu-user').value = '';
                    document.getElementById('usu-pass').value = '';
                    document.getElementById('check-supervisor').checked = false;
                    cargarUsuarios(); 
                } else { alert('Error: ' + json.error); }
            } catch(e) { alert("Error de conexión"); }
        }

        async function buscarDNIDoc() { const dni=document.getElementById('doc-dni').value; if(dni.length!==8)return alert("DNI 8 dígitos"); try{const r=await fetch(`proxy.php?dni=${dni}`);const d=await r.json();const info=d.data||d;if(info.nombres)document.getElementById('doc-nombre').value=`${info.nombres} ${info.apellido_paterno} ${info.apellido_materno}`;else alert("No encontrado");}catch(e){alert("Error buscar");} }
        async function cargarDoctores() { const r=await fetch('admin_doctores_backend.php'); doctores=await r.json(); const tb=document.getElementById('tb-doctores'); tb.innerHTML=''; const selC=document.getElementById('cat-doc-sel'); selC.innerHTML='<option value="">-- Seleccione --</option>'; doctores.forEach(doc=>{ let cvBtn=doc.cv_path?`<a href="${doc.cv_path}" target="_blank" class="btn-view"><i class="ph ph-eye"></i> Ver</a>`:'-'; tb.innerHTML+=`<tr><td>${doc.dni||'-'}</td><td>${doc.nombre_completo}</td><td>CMP:${doc.cmp||'-'}</td><td>${cvBtn}</td></tr>`; selC.add(new Option(doc.nombre_completo, doc.id)); }); }
        async function guardarDoctor() { const fd=new FormData(); fd.append('dni',document.getElementById('doc-dni').value); fd.append('nombre',document.getElementById('doc-nombre').value); fd.append('esp_id',document.getElementById('doc-esp').value); fd.append('tel',document.getElementById('doc-tel').value); fd.append('cmp',document.getElementById('doc-cmp').value); fd.append('rne',document.getElementById('doc-rne').value); const fi=document.getElementById('doc-cv'); if(fi.files.length>0)fd.append('cv',fi.files[0]); if(!document.getElementById('doc-esp').value)return alert("Falta Especialidad"); await fetch('admin_doctores_backend.php',{method:'POST',body:fd}); alert('Guardado'); cargarDoctores(); }

        function calcGan(){ const pv=parseFloat(document.getElementById('cat-pv').value)||0; const pc=parseFloat(document.getElementById('cat-pc').value)||0; document.getElementById('cat-gan').value=(pv-pc).toFixed(2); }
        async function cargarCatalogo() { const r=await fetch('admin_catalogo_backend.php?action=list'); const d=await r.json(); const tb=document.getElementById('tb-catalogo'); tb.innerHTML=''; d.forEach(c=>{ let hor=c.horario_referencial?c.horario_referencial.replace(/\|/g,'<br>'):'-'; tb.innerHTML+=`<tr><td><b>${c.sede_nombre||'-'}</b></td><td>${c.tipo_nombre||'-'}<br><small>${c.esp_nombre}</small></td><td>${c.doc_nombre}</td><td style="font-size:0.8rem">${hor}</td><td>S/${c.precio_venta}</td><td style="color:var(--success);font-weight:bold">S/${c.ganancia}</td><td><button class="btn-icon btn-del" onclick="delCat(${c.id})"><i class="ph ph-trash"></i></button></td></tr>`; }); }
        async function guardarCatalogo() { let hArr=[]; document.querySelectorAll('.schedule-row').forEach(r=>{ const d=r.querySelector('.sch-day').value, i=r.querySelector('.sch-start').value, f=r.querySelector('.sch-end').value; hArr.push(`<b>${d}:</b> ${i} - ${f}`); }); const d={action:'create', doc_id:document.getElementById('cat-doc-sel').value, sede:document.getElementById('cat-sede').value, tipo_id:document.getElementById('cat-tipo').value, hor:hArr.join(' | '), pv:document.getElementById('cat-pv').value, pc:document.getElementById('cat-pc').value, gan:document.getElementById('cat-gan').value}; if(!d.doc_id||!d.pv||!d.sede)return alert("Faltan datos"); await fetch('admin_catalogo_backend.php',{method:'POST',body:JSON.stringify(d)}); alert('Guardado'); cargarCatalogo(); document.getElementById('schedule-list').innerHTML=''; addScheduleRow(); }
        async function delCat(id){ if(confirm('Borrar?')) await fetch('admin_catalogo_backend.php',{method:'POST',body:JSON.stringify({action:'delete',id})}); cargarCatalogo(); }

        // --- GESTIÓN DE SEDES CON IP ---
        async function cargarSedes() { 
            const r=await fetch('admin_sedes_backend.php'); 
            const d=await r.json(); 
            const tb=document.getElementById('tb-sedes'); 
            tb.innerHTML=''; 
            
            d.forEach(s => {
                const ipValue = s.ip_publica || '';
                tb.innerHTML += `
                <tr>
                    <td>
                        <b style="font-size:1rem; color:var(--primary)">${s.nombre}</b><br>
                        <small style="color:#64748b">${s.direccion}</small>
                    </td>
                    <td>
                        <div style="display:flex; gap:5px; align-items:center;">
                            <input type="text" id="ip-${s.id}" value="${ipValue}" placeholder="Ej: 190.234.x.x" 
                                   style="padding:5px; width:160px; font-family:monospace; border:1px solid #cbd5e1; border-radius:6px; font-size:0.85rem">
                            
                            <button onclick="usarMiIp(${s.id})" title="Detectar mi IP" 
                                    style="background:#e0f2fe; color:#0284c7; border:1px solid #bae6fd; cursor:pointer; padding:5px 10px; border-radius:6px;">
                                <i class="ph ph-map-pin"></i>
                            </button>
                            
                            <button onclick="guardarIPSede(${s.id})" title="Guardar IP" 
                                    style="background:#dcfce7; color:#166534; border:1px solid #86efac; cursor:pointer; padding:5px 10px; border-radius:6px;">
                                <i class="ph ph-floppy-disk"></i>
                            </button>
                        </div>
                    </td>
                    <td>
                        <button class="btn-icon btn-del" onclick="borrar('delete_sede',${s.id})"><i class="ph ph-trash"></i></button>
                    </td>
                </tr>`; 
            }); 
        }

        // Detectar IP externa usando ipify
        async function usarMiIp(id) {
            const input = document.getElementById(`ip-${id}`);
            input.value = "Detectando...";
            input.disabled = true;
            try {
                const res = await fetch('https://api.ipify.org?format=json');
                const data = await res.json();
                input.value = data.ip;
                alert(`¡IP Detectada: ${data.ip}!\nEsta es la IP pública de tu conexión actual.`);
            } catch (e) {
                alert("No se pudo detectar. Ingrese manual.");
                input.value = "";
            } finally {
                input.disabled = false;
            }
        }

        // Guardar IP en BD
        async function guardarIPSede(id) {
            const ip = document.getElementById(`ip-${id}`).value.trim();
            const confirmMsg = ip ? `¿Asignar IP ${ip} a esta sede?\nSolo se podrá acceder desde esa red.` : "¿Borrar IP? Se podrá acceder desde cualquier lugar.";
            
            if(!confirm(confirmMsg)) return;

            try {
                const res = await fetch('admin_sedes_backend.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ action: 'update_ip', id: id, ip: ip })
                });
                const data = await res.json();
                if(data.success) { alert("✅ Seguridad actualizada."); cargarSedes(); } 
                else { alert("Error: " + data.error); }
            } catch(e) { alert("Error de red"); }
        }

        async function guardarSede() { 
            const d={nombre:document.getElementById('sede-nom').value, direccion:document.getElementById('sede-dir').value}; 
            await fetch('admin_sedes_backend.php',{method:'POST',body:JSON.stringify(d)}); 
            alert('Guardado'); cargarSedes(); 
        }

        async function listarTurnos() { 
            const r=await fetch('admin_listar_turnos.php'); 
            const d=await r.json(); 
            const tb=document.getElementById('tabla-turnos'); 
            tb.innerHTML=''; 
            d.forEach(t=>{
                tb.innerHTML+=`
                <tr>
                    <td>${t.fecha}</td>
                    <td>${t.medico}</td>
                    <td><span class="badge-sede">${t.sede}</span></td>
                    <td><span class="badge" style="background:#dcfce7; color:#15803d">PROGRAMADO</span></td>
                    <td><button class="btn-icon btn-del" onclick="borrar('delete_turno',${t.id})"><i class="ph ph-trash"></i></button></td>
                </tr>`; 
            }); 
        }

        async function borrar(action, id) { 
            if(!confirm("¿Eliminar?"))return; 
            const r=await fetch('admin_actions.php',{method:'POST',body:JSON.stringify({action,id})}); 
            const d=await r.json(); 
            if(d.success){alert("Eliminado");init();}else alert(d.message); 
        }

        async function guardarTurno() { 
            const usuario = document.getElementById('sel-usuario').value;
            const sede = document.getElementById('sel-sede').value;
            const fecha = document.getElementById('fecha-turno').value;
            if (!usuario || !sede || !fecha) return alert("Seleccione Usuario, Sede y Fecha");

            const data = { usuario: usuario, sede: sede, fecha: fecha, turno_texto: "Completo", hora_inicio: "08:00", hora_fin: "20:00" };
            
            try {
                const r = await fetch('admin_guardar_turno.php', { method: 'POST', body: JSON.stringify(data) }); 
                const json = await r.json();
                if (json.success) { alert('Turno asignado'); listarTurnos(); } else { alert('Error: ' + json.message); }
            } catch (e) { alert("Error de conexión"); }
        }

        /* =========================================
           INVENTARIO CLÍNICO
           ========================================= */
        async function cargarInventario() {
            try {
                const res = await fetch('admin_inventario_backend.php?tipo=all');
                const data = await res.json();
                renderList('suple', data.suple);
                renderList('proc', data.proc);
                renderList('med', data.med);
            } catch(e) { console.error("Error inv", e); }
        }

        function renderList(tipo, items) {
            const ul = document.getElementById('list-' + tipo);
            ul.innerHTML = '';
            if(!items || items.length === 0) { ul.innerHTML = '<li style="padding:10px;color:#ccc;text-align:center;">Vacío</li>'; return; }
            items.forEach(i => {
                ul.innerHTML += `<li style="display:flex; justify-content:space-between; align-items:center; padding:8px; border-bottom:1px solid #eee; font-size:0.9rem"><span>${i.nombre}</span><i class="ph ph-trash" style="color:#ef4444; cursor:pointer" onclick="delInv('${tipo}', ${i.id})"></i></li>`;
            });
        }

        async function addInv(tipo) {
            const input = document.getElementById('new-' + tipo);
            const val = input.value.trim();
            if (!val) return;
            try {
                const res = await fetch('admin_inventario_backend.php', { method: 'POST', body: JSON.stringify({ action: 'add', tipo: tipo, nombre: val }) });
                const d = await res.json();
                if(d.success) { input.value = ''; cargarInventario(); } else { alert("Error: " + d.error); }
            } catch(e) { alert("Error de red"); }
        }

        async function delInv(tipo, id) {
            if(!confirm('¿Eliminar?')) return;
            try {
                const res = await fetch('admin_inventario_backend.php', { method: 'POST', body: JSON.stringify({ action: 'delete', tipo: tipo, id: id }) });
                cargarInventario();
            } catch(e) { alert("Error"); }
        }
    </script>
</body>
</html>