<?php
include_once 'session.php'; 
date_default_timezone_set('America/Lima');

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if (!isset($_SESSION['supervisor_id']) && ($_SESSION['user_role'] ?? '') !== 'admin') { 
    header('Location: intranet.php'); exit; 
}

$user_id = $_SESSION['user_id'];
$supervisor_name = htmlspecialchars($_SESSION['supervisor_name'] ?? $_SESSION['user_name']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<title>Supervisión Diaria | Clínica Los Ángeles</title>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://unpkg.com/@phosphor-icons/web"></script>

<style>
:root {
  --primary:#2f7f77;
  --primary-soft:#e6f3f1;
  --secondary:#6b4c7a;
  --accent:#0d5bd7;

  --text:#1f2937;
  --text-light:#64748b;
  --bg:#f4f6f9;
  --card:#ffffff;
  --border:#e5e7eb;

  --success:#16a34a;
  --danger:#dc2626;

  --shadow:0 6px 20px rgba(0,0,0,.06);
  --radius:18px;
}

*{box-sizing:border-box;-webkit-tap-highlight-color:transparent}

body{
  margin:0;
  font-family:'Inter',sans-serif;
  background:var(--bg);
  color:var(--text);
  padding-bottom:120px;
}

.app-shell{max-width:900px;margin:auto;padding:20px}

/* HEADER */
.header{
  background:linear-gradient(90deg,#ffffff,#f8fafc);
  padding:16px 22px;
  border-radius:var(--radius);
  box-shadow:var(--shadow);
  border-left:6px solid var(--primary);
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:25px;
}

.header-left{display:flex;align-items:center;gap:15px}

.btn-back{
  background:#fff;
  border:1px solid var(--border);
  width:42px;height:42px;
  border-radius:50%;
  display:flex;
  align-items:center;
  justify-content:center;
  color:var(--primary);
  text-decoration:none;
  transition:.2s;
}
.btn-back:hover{background:var(--primary-soft)}

.title-area h1{
  margin:0;
  font-size:1.2rem;
  font-weight:700;
  color:var(--primary);
}
.title-area span{
  font-size:.8rem;
  font-weight:600;
  color:var(--secondary);
}

.header-actions{display:flex;gap:10px}

.btn-icon{
  width:42px;height:42px;
  border-radius:12px;
  border:none;
  background:var(--primary-soft);
  color:var(--primary);
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:1.2rem;
  cursor:pointer;
  transition:.2s;
}
.btn-icon:hover{
  background:var(--primary);
  color:#fff;
  transform:translateY(-2px);
}
.btn-logout{background:#fee2e2;color:#b91c1c}

/* CONTROLES */
.controls-card{
  background:var(--card);
  padding:22px;
  border-radius:var(--radius);
  box-shadow:var(--shadow);
  margin-bottom:25px;
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
  gap:20px;
}

.form-label{
  font-size:.75rem;
  font-weight:700;
  text-transform:uppercase;
  color:var(--text-light);
  margin-bottom:8px;
  display:block;
}

.form-input{
  width:100%;
  padding:12px 14px;
  border-radius:12px;
  border:1px solid var(--border);
  background:#fff;
  font-size:.95rem;
}
.form-input:focus{
  outline:none;
  border-color:var(--primary);
  box-shadow:0 0 0 3px rgba(47,127,119,.15);
}

/* CHECKLIST */
details{
  background:var(--card);
  border-radius:var(--radius);
  box-shadow:var(--shadow);
  margin-bottom:15px;
  overflow:hidden;
}

summary{
  padding:15px 20px;
  cursor:pointer;
  font-weight:600;
  display:flex;
  justify-content:space-between;
  align-items:center;
}
details[open] summary{
  background:var(--primary-soft);
  color:var(--primary);
}
summary::-webkit-details-marker{display:none}

.check-item{
  padding:15px 20px;
  border-top:1px solid var(--border);
  display:grid;
  grid-template-columns:1fr auto;
  gap:15px;
}

.item-text h4{
  margin:0 0 4px;
  font-size:.95rem;
}
.item-text p{
  margin:0;
  font-size:.8rem;
  color:var(--text-light);
}

.item-controls{
  width:150px;
  display:flex;
  flex-direction:column;
  gap:8px;
}

.status-select{
  padding:8px;
  border-radius:8px;
  border:1px solid var(--border);
  font-weight:600;
}

.status-select.ok{background:#dcfce7;color:#14532d}
.status-select.no{background:#fee2e2;color:#7f1d1d}
.status-select.pending{background:#f1f5f9}

.obs-input{
  padding:8px;
  border-radius:8px;
  border:1px solid var(--border);
  font-size:.8rem;
}

/* BARRA GUARDAR */
.save-dock{
  position:fixed;
  bottom:25px;
  left:50%;
  transform:translateX(-50%);
  width:90%;
  max-width:520px;
  background:rgba(255,255,255,.9);
  backdrop-filter:blur(10px);
  padding:14px 22px;
  border-radius:50px;
  box-shadow:0 12px 40px rgba(0,0,0,.15);
  display:flex;
  justify-content:space-between;
  align-items:center;
  z-index:1000;
}

.status-msg{
  font-size:.8rem;
  font-weight:600;
  color:var(--text-light);
  display:flex;
  align-items:center;
  gap:6px;
}

.btn-save{
  background:var(--primary);
  color:#fff;
  border:none;
  padding:12px 30px;
  border-radius:30px;
  font-weight:600;
  cursor:pointer;
  display:flex;
  gap:8px;
}
.btn-save:hover{background:#256e66}
.btn-save:disabled{background:#cbd5e1}

@media(max-width:600px){
  .check-item{grid-template-columns:1fr}
  .item-controls{width:100%;flex-direction:row}
  .status-select{flex:1}
}
</style>
</head>
<body>

<div class="app-shell">

<div class="header">
  <div class="header-left">
    <a href="intranet.php" class="btn-back"><i class="ph ph-arrow-left"></i></a>
    <div class="title-area">
      <h1>Supervisión</h1>
      <span><?= $supervisor_name ?></span>
    </div>
  </div>
  <div class="header-actions">
    <a href="imprimir_plantilla.php" target="_blank" class="btn-icon"><i class="ph ph-printer"></i></a>
    <a href="api_logout.php" class="btn-icon btn-logout"><i class="ph ph-sign-out"></i></a>
  </div>
</div>

<div class="controls-card">
  <div>
    <label class="form-label">Fecha de Auditoría</label>
    <input type="date" id="fecha" class="form-input">
  </div>
  <div>
    <label class="form-label">Sede</label>
    <select id="sede" class="form-input" onchange="updateStaffDisplay()"></select>
  </div>
  <div style="grid-column:1/-1">
    <label class="form-label">Personal Programado</label>
    <input type="text" id="staff-display" class="form-input" readonly>
  </div>
</div>

<div id="checklist-container"></div>

</div>

<div class="save-dock">
  <div id="status-msg" class="status-msg"><i class="ph ph-info"></i> Seleccione una sede</div>
  <button id="btn-save" class="btn-save" disabled><i class="ph ph-floppy-disk"></i> Guardar</button>
</div>

  <script>
    let currentSessionId = null;
    let sedesData = [];

    document.addEventListener('DOMContentLoaded', () => {
        // Fecha local exacta
        const hoy = new Date();
        const localIso = new Date(hoy.getTime() - (hoy.getTimezoneOffset() * 60000)).toISOString().split('T')[0];
        document.getElementById('fecha').value = localIso;
        
        document.getElementById('fecha').addEventListener('change', loadSedes);
        document.getElementById('btn-save').addEventListener('click', saveChecklist);
        
        loadSedes(); // Iniciar carga
    });

    // 1. CARGAR LISTA DE SEDES
    async function loadSedes() {
        const fecha = document.getElementById('fecha').value;
        const sedeSelect = document.getElementById('sede');
        const container = document.getElementById('checklist-container');
        
        sedeSelect.innerHTML = '<option>Buscando sedes...</option>';
        sedeSelect.disabled = true;
        
        try {
            const res = await fetch(`get_sede_actual.php?fecha=${fecha}`);
            const data = await res.json();
            
            sedesData = data.sedes;
            sedeSelect.innerHTML = '<option value="">-- Elija Sede para Iniciar --</option>';
            
            data.sedes.forEach((s, idx) => {
                const opt = new Option(s.nombre, s.sede_id);
                opt.dataset.index = idx;
                sedeSelect.add(opt);
            });
            
            sedeSelect.disabled = false;
            
            // Estado vacío inicial
            container.innerHTML = `
                <div class="empty-state">
                    <i class="ph ph-clipboard-text empty-icon"></i>
                    <h3>Listo para auditar</h3>
                    <p>Seleccione una sede arriba para cargar las actividades.</p>
                </div>
            `;
            document.getElementById('staff-display').value = "-";
            document.getElementById('btn-save').disabled = true;

        } catch(e) {
            alert("Error de conexión al cargar sedes.");
        }
    }

    // 2. AL CAMBIAR DE SEDE
    function updateStaffDisplay() {
        const sel = document.getElementById('sede');
        if (sel.value === "") {
            document.getElementById('checklist-container').innerHTML = '';
            document.getElementById('btn-save').disabled = true;
            return;
        }
        
        const idx = sel.options[sel.selectedIndex].dataset.index;
        if (idx !== undefined && sedesData[idx]) {
            document.getElementById('staff-display').value = sedesData[idx].staff;
            fetchQuestions(); // Cargar formulario
        }
    }

    // 3. CARGAR PREGUNTAS
    async function fetchQuestions() {
        const sedeId = document.getElementById('sede').value;
        const fecha = document.getElementById('fecha').value;
        const container = document.getElementById('checklist-container');
        
        if(!sedeId) return;
        
        container.innerHTML = `
            <div class="empty-state">
                <i class="ph ph-spinner ph-spin empty-icon" style="color:var(--primary)"></i>
                <p>Cargando plantilla y datos...</p>
            </div>
        `;

        try {
            const [resTmpl, resSes] = await Promise.all([
                fetch('api_get_my_template.php'),
                fetch(`api_get_existing_session.php?fecha=${fecha}&turno=DIA&sede_id=${sedeId}`)
            ]);
            
            const areas = await resTmpl.json();
            const sessionData = await resSes.json();
            
            currentSessionId = sessionData.session ? sessionData.session.id : null;
            const savedItems = sessionData.items || [];

            renderTemplate(areas, savedItems);
            
            document.getElementById('btn-save').disabled = false;
            
            const statusEl = document.getElementById('status-msg');
            if(currentSessionId) {
                statusEl.innerHTML = '<i class="ph ph-check-circle" style="color:var(--success)"></i> Datos cargados';
            } else {
                statusEl.innerHTML = '<i class="ph ph-sparkle" style="color:orange"></i> Nueva auditoría';
            }

        } catch(e) { 
            container.innerHTML = '<p style="color:red; text-align:center">Error al cargar datos.</p>'; 
        }
    }

    // 4. RENDERIZAR PLANTILLA
    function renderTemplate(areas, savedItems) {
        const container = document.getElementById('checklist-container');
        container.innerHTML = '';
        
        const savedMap = {};
        savedItems.forEach(i => savedMap[i.activity_id] = i);

        areas.forEach(area => {
            const details = document.createElement('details');
            details.open = true; // Por defecto abiertos
            
            details.innerHTML = `
                <summary>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <span style="font-size:1.2rem;">${area.icon}</span>
                        <span>${area.nombre}</span>
                    </div>
                    <i class="ph ph-caret-down icon-rotate"></i>
                </summary>
            `;
            
            const content = document.createElement('div');
            
            area.actividades.forEach(act => {
                const s = savedMap[act.id] || {};
                const est = s.estado || 'PENDIENTE';
                
                const div = document.createElement('div');
                div.className = 'check-item';
                div.dataset.id = act.id;
                
                // Color de fondo según estado
                let bgStyle = '';
                if(est === 'REALIZADO') bgStyle = 'background:#f0fdf4';
                if(est === 'NO REALIZADO') bgStyle = 'background:#fef2f2';
                div.style = bgStyle;

                div.innerHTML = `
                    <div class="item-text">
                        <h4>${act.nombre}</h4>
                        <p>${act.criterio || ''}</p>
                        <input type="text" class="obs-input" placeholder="Observación..." value="${s.observacion||''}">
                    </div>
                    
                    <div class="item-controls">
                        <select class="status-select ${est==='REALIZADO'?'ok':(est==='NO REALIZADO'?'no':'pending')}" 
                                onchange="updateColor(this)">
                            <option value="PENDIENTE" ${est==='PENDIENTE'?'selected':''}>Pendiente</option>
                            <option value="REALIZADO" ${est==='REALIZADO'?'selected':''}>✅ OK</option>
                            <option value="NO REALIZADO" ${est==='NO REALIZADO'?'selected':''}>❌ NO</option>
                            <option value="EN PROCESO" ${est==='EN PROCESO'?'selected':''}>🚧 Proceso</option>
                        </select>
                        
                        ${act.requires_quantity ? 
                            `<input type="number" class="qty-input obs-input" placeholder="Cant." value="${s.quantity||''}">` 
                            : ''}
                    </div>
                `;
                content.appendChild(div);
            });
            
            details.appendChild(content); 
            container.appendChild(details);
        });
    }

    // CAMBIAR COLOR VISUALMENTE
    window.updateColor = function(select) {
        const row = select.closest('.check-item');
        row.style.background = '';
        select.className = 'status-select'; // Reset classes
        
        if(select.value === 'REALIZADO') { 
            row.style.background = '#f0fdf4'; 
            select.classList.add('ok'); 
        } else if(select.value === 'NO REALIZADO') { 
            row.style.background = '#fef2f2'; 
            select.classList.add('no'); 
        } else {
            select.classList.add('pending');
        }
        
        document.getElementById('status-msg').innerHTML = '<i class="ph ph-pencil-simple"></i> Cambios sin guardar...';
    }

    // 5. GUARDAR DATOS
    async function saveChecklist() {
        const btn = document.getElementById('btn-save');
        const statusEl = document.getElementById('status-msg');
        
        btn.disabled = true; 
        btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Guardando...';
        
        const items = [];
        document.querySelectorAll('.check-item').forEach(row => {
            items.push({
                activity_id: row.dataset.id,
                estado: row.querySelector('select').value,
                observacion: row.querySelector('.obs-input').value,
                quantity: row.querySelector('.qty-input') ? row.querySelector('.qty-input').value : ''
            });
        });

        const payload = {
            session: { 
                fecha: document.getElementById('fecha').value, 
                turno: 'DIA', 
                sede_id: document.getElementById('sede').value 
            },
            items: items,
            session_id_to_update: currentSessionId,
            staff_list: document.getElementById('staff-display').value
        };

        try {
            const res = await fetch('api_save_checklist.php', { method:'POST', body:JSON.stringify(payload) });
            const data = await res.json();
            
            if(data.success) {
                currentSessionId = data.sessionId;
                statusEl.innerHTML = '<i class="ph ph-check-circle" style="color:white"></i> Guardado exitoso';
                
                // Efecto visual en el botón
                btn.style.background = '#10b981'; // Verde
                btn.innerHTML = '<i class="ph ph-check"></i> Listo';
                
                setTimeout(() => {
                    btn.style.background = ''; // Volver al original
                    btn.innerHTML = '<i class="ph ph-floppy-disk"></i> Guardar';
                    btn.disabled = false;
                }, 2000);
                
            } else { 
                alert("Error: " + data.error); 
                btn.disabled = false;
                btn.innerHTML = '<i class="ph ph-floppy-disk"></i> Guardar';
            }
        } catch(e) { 
            alert("Error de red"); 
            btn.disabled = false;
            btn.innerHTML = '<i class="ph ph-floppy-disk"></i> Guardar';
        } 
    }
  </script>
</body>
</html>