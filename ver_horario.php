<?php
// ver_horario.php
// Sistema de Gestión de Turnos - Clínica Los Ángeles
include_once 'session.php'; 

// Detectar rol y usuario para la vista inteligente
$is_admin = ($_SESSION['user_role'] ?? '') === 'admin';
$my_id = $_SESSION['user_id'] ?? '';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>Programación de Turnos | Intranet</title>

  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
  <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/dist/tippy.css" />
  <script src="https://unpkg.com/@popperjs/core@2"></script>
  <script src="https://unpkg.com/tippy.js@6"></script>
  <script src="https://unpkg.com/@phosphor-icons/web"></script>

  <style>
    /* --- ESTILO ORIGINAL (PREMIUM) --- */
    :root {
      --primary: #c92a2a; 
      --primary-soft: #fff5f5;
      --text: #1e293b;
      --text-light: #64748b;
      --bg: #f8fafc;
      --white: #ffffff;
      --border: #e2e8f0;
      --radius: 16px;
      --shadow: 0 10px 30px -10px rgba(0,0,0,0.08);
      --shadow-hover: 0 20px 40px -10px rgba(0,0,0,0.12);
    }

    * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }

    body {
      font-family: 'Outfit', sans-serif;
      background-color: var(--bg);
      /* Fondo original con degradado sutil */
      background-image: radial-gradient(at 0% 0%, rgba(201, 42, 42, 0.05) 0px, transparent 50%), 
                        radial-gradient(at 100% 100%, rgba(251, 191, 36, 0.1) 0px, transparent 50%);
      color: var(--text);
      margin: 0;
      padding: 20px;
      height: 100vh;
      display: flex;
      flex-direction: column;
      overflow: hidden; 
    }

    /* --- HEADER --- */
    .header-bar {
      display: flex; justify-content: space-between; align-items: center;
      margin-bottom: 20px; flex-shrink: 0;
    }
    .header-title h1 { font-size: 1.5rem; font-weight: 700; margin: 0; color: var(--text); letter-spacing: -0.5px; }
    .header-title p { margin: 0; color: var(--text-light); font-size: 0.95rem; }
    
    .btn-back { 
        text-decoration: none; color: var(--text-light); font-weight: 600; 
        display: flex; align-items: center; gap: 8px; transition: 0.2s; 
        background: white; padding: 10px 16px; border-radius: 50px; 
        border: 1px solid var(--border); box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    .btn-back:hover { color: var(--primary); transform: translateX(-3px); }

    /* --- TARJETA PRINCIPAL --- */
    .main-card {
      background: var(--white);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      display: flex; flex-direction: column;
      flex: 1; 
      overflow: hidden;
      border: 1px solid var(--border);
    }

    /* --- TOOLBAR --- */
    .toolbar {
      padding: 20px;
      border-bottom: 1px solid var(--border);
      display: flex; gap: 15px; flex-wrap: wrap; align-items: center;
      background: #fff;
      justify-content: space-between;
      flex-shrink: 0;
    }

    .filter-group { display: flex; gap: 10px; align-items: center; }

    select, input[type="text"], input[type="month"] {
      padding: 10px 16px; border-radius: 12px; border: 1px solid var(--border);
      font-family: inherit; font-size: 0.9rem; outline: none; color: var(--text);
      background: var(--bg); transition: 0.2s; min-width: 140px;
    }
    select:focus { border-color: var(--primary); background: #fff; box-shadow: 0 0 0 3px rgba(201, 42, 42, 0.1); }

    .btn {
      padding: 10px 20px; border-radius: 12px; font-weight: 600; font-size: 0.9rem;
      border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; 
      transition: all 0.2s; white-space: nowrap; justify-content: center;
    }
    .btn:active { transform: scale(0.98); }

    .btn-primary { background: linear-gradient(135deg, var(--primary) 0%, #991b1b 100%); color: white; box-shadow: 0 4px 10px rgba(201, 42, 42, 0.2); }
    .btn-primary:hover { box-shadow: 0 6px 15px rgba(201, 42, 42, 0.3); }
    
    .btn-success { background: #10b981; color: white; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2); }
    .btn-success:hover { background: #059669; }

    .btn-ghost { background: white; border: 1px solid var(--border); color: var(--text-light); }
    .btn-ghost:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-soft); }
    .btn-ghost.active { background: var(--text); color: white; border-color: var(--text); }

    /* --- CALENDARIO --- */
    #calendar-wrapper { flex: 1; overflow-y: auto; padding: 20px; position: relative; }
    
    .fc { font-family: 'Outfit', sans-serif; }
    .fc-theme-standard td, .fc-theme-standard th { border-color: #f1f5f9; }
    .fc-col-header-cell { background: #f8fafc; padding: 15px 0; color: var(--text-light); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; }
    
    /* Eventos Bonitos */
    .fc-event {
      border: none !important; border-radius: 8px !important;
      padding: 4px 8px; font-size: 0.85rem; font-weight: 600;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 2px !important;
      transition: transform 0.2s; cursor: pointer;
    }
    .fc-event:hover { transform: scale(1.02); z-index: 10; }
    
    /* Ajustes Vista Mensual (Celular Usuario) */
    .fc-daygrid-event { white-space: normal !important; align-items: center; font-size: 0.75rem; }
    .fc-daygrid-dot-event .fc-event-title { font-weight: 700; }

    /* Ajustes Vista Lista (Celular Admin) */
    .fc-list-event-title { font-weight: 700; color: var(--text); font-size: 0.9rem; }
    .fc-list-day-cushion { background-color: #f1f5f9 !important; font-weight: 700; color: var(--text); }

    /* --- MODALES --- */
    .modal-backdrop {
      position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);
      z-index: 2000; display: none; align-items: center; justify-content: center; 
      opacity: 0; transition: opacity 0.3s; padding: 20px;
    }
    .modal-backdrop.show { display: flex; opacity: 1; }
    
    .modal-card {
      background: white; width: 100%; max-width: 550px; border-radius: 24px;
      box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden;
      transform: translateY(20px); transition: transform 0.3s;
      max-height: 90vh; display: flex; flex-direction: column;
    }
    .modal-backdrop.show .modal-card { transform: translateY(0); }

    .modal-header { padding: 20px 30px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #fff; flex-shrink: 0; }
    .modal-header h3 { margin: 0; font-size: 1.25rem; color: var(--text); font-weight: 700; }
    
    .modal-body { padding: 30px; overflow-y: auto; -webkit-overflow-scrolling: touch; }
    
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .form-full { grid-column: span 2; }
    .form-group label { display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-light); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
    .form-group input, .form-group select { width: 100%; border-radius: 12px; padding: 12px; border: 1px solid var(--border); font-size: 0.95rem; }

    .modal-footer { padding: 20px 30px; background: #f8fafc; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 12px; flex-shrink: 0; }
    .btn-danger { background: #fee2e2; color: #ef4444; border: 1px solid #fecaca; }
    .btn-danger:hover { background: #ef4444; color: white; }

    /* --- LOADING --- */
    #loading {
      position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
      background: rgba(255,255,255,0.95); padding: 15px 30px; border-radius: 50px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.15); z-index: 3000; font-weight: 600;
      display: none; color: var(--primary); border: 1px solid var(--border);
      backdrop-filter: blur(5px);
    }

    /* --- MEDIA QUERIES (MÓVIL) --- */
    @media(max-width: 768px) {
      body { padding: 10px; }
      .header-title h1 { font-size: 1.1rem; }
      .toolbar { flex-direction: column; align-items: stretch; gap: 12px; padding: 15px; }
      
      .filter-group { flex-direction: column; width: 100%; }
      .filter-group select, .filter-group input { width: 100%; }
      
      /* Botones de vista en fila */
      .filter-group:nth-child(2) { flex-direction: row; justify-content: space-between; }
      .filter-group:nth-child(2) button { flex: 1; padding: 8px; font-size: 0.8rem; }

      /* Botones de acción */
      .filter-group:last-child { flex-direction: row; gap: 8px; }
      #btn-import { flex: 2; } #btn-new { flex: 1; } #btn-print { display: none; }
      
      /* Modal ajustado */
      .form-grid { grid-template-columns: 1fr; gap: 15px; }
      .form-full { grid-column: span 1; }
      .modal-body { padding: 20px; }
    }
  </style>
</head>
<body>

  <div class="header-bar">
    <div class="header-title">
      <h1>Turnos</h1>
      <p>Programación del personal</p>
    </div>
    <a href="intranet.php" class="btn-back"><i class="ph ph-arrow-left" style="font-size: 1.1rem;"></i> Salir</a>
  </div>

  <div class="main-card">
    
    <div class="toolbar">
      
      <div class="filter-group">
        <select id="filter-sede"><option value="">🏢 Sede: Todas</option></select>
        <select id="filter-usuario"><option value="">👩‍⚕️ Personal: Todos</option></select>
        </div>

      <div class="filter-group">
        <button id="view-list" class="btn btn-ghost">Lista</button>
        <button id="view-week" class="btn btn-ghost active">Semana</button>
        <button id="view-month" class="btn btn-ghost">Mes</button>
      </div>

      <div class="filter-group">
        <button id="btn-import" class="btn btn-success"><i class="ph ph-file-csv"></i> Importar</button>
        <button id="btn-new" class="btn btn-primary"><i class="ph ph-plus"></i> Nuevo</button>
        <button id="btn-print" class="btn btn-ghost"><i class="ph ph-printer"></i></button>
      </div>
    </div>

    <div id="calendar-wrapper">
      <div id="calendar"></div>
    </div>

  </div>

  <div id="loading"><i class="ph ph-spinner ph-spin"></i> Cargando...</div>

  <div id="modal" class="modal-backdrop">
    <div class="modal-card">
      <div class="modal-header">
        <h3 id="modal-title">Turno</h3>
        <button id="btn-close-modal" style="background:none; border:none; padding:10px; font-size:1.5rem; color:var(--text-light)">&times;</button>
      </div>

      <div class="modal-body">
        <div id="form-error" style="color:#ef4444; display:none; margin-bottom:15px; background:#fef2f2; padding:10px; border-radius:8px; font-size:0.85rem;"></div>

        <div class="form-grid">
          <div class="form-group form-full">
            <label>Personal</label>
            <select id="m-usuario"></select>
          </div>
          
          <div class="form-group form-full">
            <label>Sede</label>
            <select id="m-sede"></select>
          </div>

          <div class="form-group">
            <label>Inicio</label>
            <input id="m-start" type="datetime-local">
          </div>
          <div class="form-group">
            <label>Fin</label>
            <input id="m-end" type="datetime-local">
          </div>

          <div class="form-group">
            <label>Turno Ref.</label>
            <select id="m-turno">
                <option value="Mañana">Mañana</option>
                <option value="Tarde">Tarde</option>
                <option value="Noche">Noche</option>
                <option value="Completo">Completo</option>
            </select>
          </div>
          
          <div class="form-group">
            <label>Repetir?</label>
            <select id="m-recurrent">
                <option value="0">No</option>
                <option value="1">Sí, semanal</option>
            </select>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button id="btn-delete" class="btn btn-danger" style="display:none; margin-right:auto;">Borrar</button>
        <button id="btn-cancel" class="btn btn-ghost">Cancelar</button>
        <button id="btn-save" class="btn btn-primary">Guardar</button>
      </div>
    </div>
  </div>

  <div id="modal-import" class="modal-backdrop">
    <div class="modal-card" style="max-width: 400px;">
        <div class="modal-header">
            <h3>📂 Carga Automática</h3>
            <button class="btn-close-import" style="background:none; border:none; padding:10px; font-size:1.5rem; color:var(--text-light)">&times;</button>
        </div>
        <div class="modal-body">
            <div style="background:#eff6ff; color:#1e40af; padding:15px; border-radius:12px; margin-bottom:20px; font-size:0.85rem; line-height:1.5;">
                <b>Instrucciones:</b> El sistema detectará Sede y Horas del Excel. 
                <br>⚠️ Confirma el <b>Mes y Año</b> abajo.
            </div>
            <form id="form-import">
                <div class="form-group mb-3" style="margin-bottom:20px">
                    <label>Mes Correspondiente</label>
                    <input type="month" id="imp-mes" required style="width:100%" value="<?php echo date('Y-m'); ?>">
                </div>
                <div class="form-group mb-3" style="margin-bottom:20px">
                    <label>Archivo CSV</label>
                    <input type="file" id="imp-file" accept=".csv" required style="width:100%">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">
                    <i class="ph ph-upload-simple me-2"></i> Procesar
                </button>
            </form>
            <div id="import-msg" style="margin-top:15px; font-size:0.85rem; text-align:center; font-weight:600;"></div>
        </div>
    </div>
  </div>

  <script>
    const baseApi = ''; 
    const qs = s => document.querySelector(s);
    let calendar, currentEvent = null;
    let sedeColorMap = {};
    const colorsPalette = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#06b6d4'];

    const els = {
        loading: qs('#loading'), calendar: qs('#calendar'), modal: qs('#modal'), formError: qs('#form-error'),
        fSede: qs('#filter-sede'), fUsuario: qs('#filter-usuario'),
        mUsuario: qs('#m-usuario'), mSede: qs('#m-sede'), mStart: qs('#m-start'), mEnd: qs('#m-end'), mTurno: qs('#m-turno'), mRecurrent: qs('#m-recurrent'),
        btnSave: qs('#btn-save'), btnDelete: qs('#btn-delete'), btnCancel: qs('#btn-cancel'), btnNew: qs('#btn-new'), btnClose: qs('#btn-close-modal'),
        viewList: qs('#view-list'), viewWeek: qs('#view-week'), viewMonth: qs('#view-month')
    };

    function setLoading(show) { els.loading.style.display = show ? 'block' : 'none'; }

    function colorForSede(id) {
        if(!id) return '#94a3b8';
        if(sedeColorMap[id]) return sedeColorMap[id];
        const idx = (parseInt(id) % colorsPalette.length);
        sedeColorMap[id] = colorsPalette[idx];
        return sedeColorMap[id];
    }

    function initCalendar() {
        calendar = new FullCalendar.Calendar(els.calendar, {
            initialView: 'timeGridWeek', 
            headerToolbar: false, 
            locale: 'es', firstDay: 1, 
            slotMinTime: "06:00:00", slotMaxTime: "22:00:00", allDaySlot: false, height: '100%',
            
            // Ocultar hora automática
            displayEventTime: false, 

            eventDidMount: function(info) {
                const p = info.event.extendedProps;
                // Tooltip solo en vistas de grilla (PC)
                if(info.view.type !== 'listWeek') {
                    tippy(info.el, {
                        content: `<div style="text-align:left; font-size:0.9rem">
                            <strong>${info.event.title}</strong><br>
                            <span style="opacity:0.8">📅 ${info.event.start.toLocaleDateString()}</span><br>
                            <span style="opacity:0.8">⏰ ${info.event.start.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})} - ${info.event.end?.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}</span>
                        </div>`,
                        allowHTML: true, theme: 'translucent',
                    });
                }
            },
            eventClick: function(info) { openModal(info.event); }
        });
        calendar.render();
        updateViewForMobile(); // Ajuste inicial
    }

    // --- LÓGICA INTELIGENTE DE VISTAS (PC vs MÓVIL) ---
    function updateViewForMobile(forceView = null) {
        const isMobile = window.innerWidth < 768;
        const userSelected = els.fUsuario.value !== "";
        let newView = 'timeGridWeek'; // Default PC

        if (forceView) {
            newView = forceView;
        } else if (isMobile) {
            // SI ES MÓVIL:
            // 1. Si hay un usuario (o es un usuario normal) -> Vista MES (Para ver sus días pintados)
            // 2. Si no hay usuario (es Admin viendo todo) -> Vista LISTA (Para no saturar)
            newView = userSelected ? 'dayGridMonth' : 'listWeek';
        }

        calendar.changeView(newView);
        
        // Actualizar botones visualmente
        [els.viewList, els.viewWeek, els.viewMonth].forEach(b => b.classList.remove('active'));
        if(newView === 'listWeek') els.viewList.classList.add('active');
        if(newView === 'timeGridWeek') els.viewWeek.classList.add('active');
        if(newView === 'dayGridMonth') els.viewMonth.classList.add('active');
    }

    // --- API ---
    async function loadData() {
        try {
            const r = await fetch(baseApi + 'public_get_data.php');
            const d = await r.json();
            
            if(d.sedes) d.sedes.forEach(s => {
                els.fSede.add(new Option(s.nombre, s.id));
                els.mSede.add(new Option(s.nombre, s.id));
            });
            if(d.usuarios) d.usuarios.forEach(u => {
                els.fUsuario.add(new Option(u.nombre_completo, u.id));
                els.mUsuario.add(new Option(u.nombre_completo, u.id));
            });
            
            // Si no es admin, pre-seleccionar su propio usuario y bloquear
            const is_admin = <?php echo json_encode($is_admin); ?>;
            const my_id = <?php echo json_encode($my_id); ?>;
            
            if(!is_admin && my_id) {
                els.fUsuario.value = my_id;
                els.fUsuario.disabled = true; 
            }

            await refreshEvents();
        } catch(e) { console.error("Error carga inicial:", e); }
    }

    async function refreshEvents() {
        setLoading(true);
        try {
            const params = new URLSearchParams({
                action: 'list', inicio: '2024-01-01', fin: '2025-12-31',
                sede: els.fSede.value, usuario_id: els.fUsuario.value
            });

            const r = await fetch(baseApi + 'api_get_work_schedule.php?' + params);
            const eventsData = await r.json();
            
            const events = eventsData.map(ev => ({
                id: ev.id,
                // TÍTULO: SEDE - NOMBRE
                title: `${ev.sede_name || '?'} - ${ev.licenciada_name}`, 
                start: ev.start, end: ev.end,
                backgroundColor: colorForSede(ev.sede_id),
                borderColor: 'transparent',
                extendedProps: { ...ev }
            }));

            calendar.removeAllEvents();
            calendar.addEventSource(events);
            
            // Aplicar lógica de vista inteligente tras cargar
            updateViewForMobile();

        } catch(e) { console.error(e); }
        finally { setLoading(false); }
    }

    // --- BOTONES Y LISTENERS ---
    [els.fSede, els.fUsuario].forEach(el => el.addEventListener('change', refreshEvents));

    // Botones Manuales
    els.viewList.onclick = () => updateViewForMobile('listWeek');
    els.viewWeek.onclick = () => updateViewForMobile('timeGridWeek');
    els.viewMonth.onclick = () => updateViewForMobile('dayGridMonth');

    els.btnNew.onclick = () => openModal();
    els.btnCancel.onclick = closeModal;
    els.btnClose.onclick = closeModal;
    if(qs('#btn-print')) qs('#btn-print').onclick = () => window.print();

    // GUARDAR TURNO
    els.btnSave.onclick = async () => {
        els.btnSave.disabled = true;
        const payload = {
            usuario_id: els.mUsuario.value, sede_id: els.mSede.value,
            start: els.mStart.value, end: els.mEnd.value,
            turno: els.mTurno.value, recurrent: els.mRecurrent.value === '1' ? 1 : 0
        };
        if(!payload.usuario_id || !payload.start) {
            els.formError.innerText = "Complete los campos."; els.formError.style.display = 'block';
            els.btnSave.disabled = false; return;
        }
        try {
            let url = currentEvent ? 'api_update_shift.php' : 'api_create_shift.php';
            let method = currentEvent ? 'PATCH' : 'POST';
            if(currentEvent) payload.id = currentEvent.id;
            
            const r = await fetch(baseApi + url, { method: method, headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload) });
            const res = await r.json();
            if(res.success) { closeModal(); refreshEvents(); } else { throw new Error(res.error); }
        } catch(e) {
            els.formError.innerText = e.message; els.formError.style.display = 'block';
        } finally { els.btnSave.disabled = false; }
    };

    // ELIMINAR
    els.btnDelete.onclick = async () => {
        if(!confirm("¿Eliminar?")) return;
        await fetch(baseApi + 'api_delete_shift.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ id: currentEvent.id }) });
        closeModal(); refreshEvents();
    };

    // MODALES
    function openModal(event = null) {
        currentEvent = event; els.formError.style.display = 'none';
        if (event) {
            qs('#modal-title').innerText = 'Editar Turno'; els.btnDelete.style.display = 'block';
            const p = event.extendedProps;
            els.mUsuario.value = p.licenciada_id; els.mSede.value = p.sede_id; els.mTurno.value = p.turno;
            const toISO = d => { const tz = d.getTimezoneOffset() * 60000; return (new Date(d - tz)).toISOString().slice(0, 16); };
            els.mStart.value = toISO(event.start); els.mEnd.value = event.end ? toISO(event.end) : '';
        } else {
            qs('#modal-title').innerText = 'Nuevo Turno'; els.btnDelete.style.display = 'none';
            const d = new Date(); d.setHours(8,0,0,0);
            const toISO = d => { const tz = d.getTimezoneOffset() * 60000; return (new Date(d - tz)).toISOString().slice(0, 16); };
            els.mStart.value = toISO(d);
        }
        els.modal.classList.add('show');
    }
    function closeModal() { els.modal.classList.remove('show'); }

    // --- IMPORTAR EXCEL ---
    const modalImport = document.getElementById('modal-import');
    const btnImport = document.getElementById('btn-import');
    const closeImport = document.querySelector('.btn-close-import');

    if(btnImport) btnImport.onclick = () => modalImport.classList.add('show');
    if(closeImport) closeImport.onclick = () => modalImport.classList.remove('show');

    document.getElementById('form-import').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button');
        const msg = document.getElementById('import-msg');
        const file = document.getElementById('imp-file').files[0];
        if(!file) return alert("Selecciona un archivo");

        const fd = new FormData(); fd.append('archivo', file);
        fd.append('mes_anio', document.getElementById('imp-mes').value);

        btn.disabled = true; btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Procesando...';
        msg.innerText = "";

        try {
            const res = await fetch('admin_importar_excel.php', { method: 'POST', body: fd });
            const data = await res.json();
            if(data.success) { alert("✅ " + data.message); modalImport.classList.remove('show'); refreshEvents(); } 
            else { msg.style.color = '#ef4444'; msg.innerText = "Error: " + data.message; }
        } catch(err) { msg.style.color = '#ef4444'; msg.innerText = "Error de conexión."; } 
        finally { btn.disabled = false; btn.innerHTML = '<i class="ph ph-upload-simple me-2"></i> Procesar'; }
    });

    initCalendar();
    loadData();
  </script>
</body>
</html>