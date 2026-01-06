<?php
include_once 'session.php'; 

// Si el usuario NO es un admin, lo saca.
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Configuración Checklist</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    /* ======================================= */
    /* =           ESTILOS GENERALES         = */
    /* ======================================= */
    :root {
      --bg: #f8fafc; --card: #ffffff; --primary: #0284c7; --primary-soft: #e0f2fe;
      --text-primary: #0f172a; --text-secondary: #334155; --muted: #64748b;
      --border: #e2e8f0; --radius-lg: 16px; --radius-md: 12px; --radius-sm: 8px;
      --shadow-sm: 0 1px 3px 0 rgb(0 0 0 / 0.05), 0 1px 2px -1px rgb(0 0 0 / 0.05);
      --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    }
    
    * { box-sizing: border-box; }
    body {
      margin: 0; padding: 0; min-height: 100vh;
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "SF Pro Text", "Segoe UI", sans-serif;
      background: var(--bg); color: var(--text-secondary);
      transition: background-color 0.3s ease, color 0.3s ease;
    }
    .app-shell { max-width: 1080px; margin: 0 auto; padding: 16px 12px 32px; }
    
    /* HEADER */
    .header { display: flex; flex-direction: column; gap: 8px; margin-bottom: 24px; }
    h1 { margin: 0; font-size: 1.7rem; letter-spacing: 0.03em; color: var(--text-primary); display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .title-pill {
      font-size: 0.7rem; padding: 2px 10px; border-radius: 999px;
      background: #cffafe; color: #0891b2; border: 1px solid #67e8f9;
      text-transform: uppercase; letter-spacing: 0.08em; white-space: nowrap;
    }
    .subtitle { margin: 0; color: var(--muted); font-size: 0.9rem; }
    
    /* HEADER LINKS */
    .header-links { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-top: 12px; flex-wrap: wrap; }
    .header-actions { display: flex; gap: 8px; }
    
    .header-link {
        padding: 8px 14px; border-radius: 999px; background: var(--primary);
        color: #ffffff; text-decoration: none; font-size: 0.9rem;
        font-weight: 600; transition: all 0.2s ease; border: none; white-space: nowrap;
        display: inline-flex; align-items: center; gap: 6px; cursor: pointer;
    }
    .header-link:hover { filter: brightness(110%); }
    .btn-back { background-color: #64748b; }
    .logout { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
    .theme-toggle { background: var(--primary-soft); color: var(--primary); border: 1px solid var(--border); }

    h2 {
        font-size: 1.3rem; color: var(--primary);
        border-bottom: 1px solid var(--border);
        padding-bottom: 8px; margin: 20px 0 16px 0;
    }

    /* GRID PRINCIPAL */
    .admin-panels-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 14px; margin-bottom: 24px;
    }
    .span-two { grid-column: span 2; }

    .admin-block { 
        background: var(--card); border-radius: var(--radius-md); 
        padding: 14px; border: 1px solid var(--border);
        box-shadow: var(--shadow-sm);
        display: flex; flex-direction: column;
    }
    .admin-title { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted); font-weight: 600; margin-bottom: 8px; }
    
    /* Inputs y Botones */
    .admin-add-row { display: flex; gap: 6px; margin-top: 8px; flex-wrap: wrap; }
    .admin-input, .perm-select, .frecuencia-select, .date-input {
      width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid #cbd5e1;
      background: #f8fafc; color: var(--text-primary); font-size: 0.85rem; outline: none; min-height: 36px;
    }
    .admin-input { flex: 1; } 
    .admin-add-btn {
      border-radius: 999px; border: 1px solid var(--primary); background: var(--primary-soft);
      color: var(--primary); font-size: 0.8rem; padding: 6px 14px; cursor: pointer; white-space: nowrap; font-weight: 600;
    }
    
    /* Listas */
    .admin-list { margin-top: 10px; max-height: 150px; overflow-y: auto; display: flex; flex-direction: column; gap: 6px; }
    .list-row {
      display: flex; justify-content: space-between; align-items: center; gap: 8px;
      padding: 6px 10px; border-radius: 8px; background: #f1f5f9;
      border: 1px solid var(--border); font-size: 0.85rem; color: var(--text-secondary);
    }
    .list-delete-btn {
      border-radius: 6px; border: 1px solid #fecaca; background: #fef2f2;
      color: #dc2626; font-size: 0.7rem; padding: 4px 8px; cursor: pointer; font-weight: 600;
    }

    /* Permisos */
    .perm-list { max-height: 150px; overflow-y: auto; display: flex; flex-direction: column; gap: 6px; margin-top: 10px; border: 1px solid var(--border); padding: 8px; border-radius: 8px; background: #fcfcfc; }
    .perm-item { display: flex; align-items: center; gap: 8px; font-size: 0.85rem; }
    .perm-save-btn {
        width: 100%; margin-top: 12px; border-radius: 8px; border: 1px solid #16a34a;
        background: #16a34a; color: #ffffff; font-size: 0.9rem;
        padding: 8px; cursor: pointer; font-weight: 600;
    }
    .perm-save-btn:disabled { opacity: 0.5; cursor: not-allowed; background: #cbd5e1; border-color: #cbd5e1; }

    /* TABLAS DE ACTIVIDADES */
    details {
      background: var(--card); border-radius: var(--radius-lg); border: 1px solid var(--border);
      margin-bottom: 14px; overflow: hidden; box-shadow: var(--shadow-sm);
    }
    summary {
      list-style: none; cursor: pointer; padding: 12px 16px; display: flex; align-items: center; justify-content: space-between;
      background: #f8fafc; border-bottom: 1px solid var(--border);
    }
    .section-left { display: flex; align-items: center; gap: 10px; }
    .section-icon {
      width: 32px; height: 32px; border-radius: 999px; display: flex; align-items: center; justify-content: center;
      font-size: 1.1rem; background: var(--primary-soft); border: 1px solid #bae6fd; color: var(--primary);
    }
    .section-title { display: flex; flex-direction: column; gap: 2px; }
    .section-name { font-weight: 700; font-size: 0.95rem; color: var(--text-primary); }
    .section-tag { font-size: 0.75rem; color: var(--muted); }
    
    .section-body { padding: 12px; overflow-x: auto; } 
    .section-actions { display: flex; justify-content: flex-end; margin-bottom: 10px; }
    .add-row-btn {
      border-radius: 999px; border: 1px solid var(--primary); background: var(--primary-soft);
      color: var(--primary); font-size: 0.8rem; padding: 6px 12px; 
      display: inline-flex; align-items: center; gap: 6px; cursor: pointer; font-weight: 600;
    }
    
    table { width: 100%; border-collapse: collapse; font-size: 0.85rem; min-width: 800px; }
    th, td { border-bottom: 1px solid var(--border); padding: 10px; text-align: left; vertical-align: middle; }
    th { font-weight: 600; color: #475569; white-space: nowrap; background: #f8fafc; }
    
    .col-actividad { min-width: 200px; font-weight: 500; color: var(--text-primary); }
    .col-criterio { min-width: 220px; color: var(--text-secondary); }
    .col-frecuencia { width: 140px; }
    .col-cantidad { text-align: center; width: 80px; }
    .quantity-toggle { width: 1.2rem; height: 1.2rem; cursor: pointer; accent-color: var(--primary); }
    
    /* Scroll Mobile */
    @media (max-width: 768px) {
      .admin-panels-grid { grid-template-columns: 1fr !important; }
      .span-two { grid-column: auto !important; }
    }
    
    /* Dark Mode */
    body.dark-mode { --bg: #0f172a; --card: #0b1220; --primary: #38bdf8; --text-primary: #f9fafb; --text-secondary: #e5e7eb; --border: #1e293b; }
    body.dark-mode .admin-block, body.dark-mode details { background: #0b1220; border-color: #1e293b; }
    body.dark-mode .admin-input, body.dark-mode .perm-select { background: #1e293b; border-color: #334155; color: white; }
  </style>
</head>
<body>
  <div class="app-shell">
    <header class="header">
      <div class="title-row">
        <h1>🛠️ Configuración de Checklist <span class="title-pill">Admin</span></h1>
      </div>
      <p class="subtitle">Gestione sedes y las actividades de la plantilla.</p>
      
      <div class="header-links">
        <a href="intranet.php" class="header-link btn-back">← Volver a Intranet</a>
        <div class="header-actions">
            <button type="button" id="theme-toggle" class="header-link theme-toggle">🌙</button>
            <a href="api_logout.php" class="header-link logout">Cerrar Sesión</a>
        </div>
      </div>
    </header>

    <h2>Paneles de Administración</h2>
    <div class="admin-panels-grid">
              
        <div class="admin-block">
            <div class="admin-title">Sedes Operativas</div>
            <div class="admin-add-row">
              <input id="input-sede" class="admin-input" placeholder="Nueva sede" />
              <button type="button" id="btn-add-sede" class="admin-add-btn">Agregar</button>
            </div>
            <div id="admin-sedes" class="admin-list"></div>
        </div>
        
        <div class="admin-block">
            <div class="admin-title">Áreas / Categorías</div>
            <div class="admin-add-row">
              <input id="input-area-emoji" class="admin-input" placeholder="Emoji (ej: 🩺)" style="max-width: 100px;" />
              <input id="input-area-nombre" class="admin-input" placeholder="Nombre (ej: Triaje)" />
            </div>
            <div class="admin-add-row" style="margin-top: 4px;">
              <input id="input-area-codigo" class="admin-input" placeholder="Código (ej: triaje)" />
              <button type="button" id="btn-add-area" class="admin-add-btn">Agregar</button>
            </div>
            <p style="font-size: 0.65rem; color: var(--muted); margin: 4px 0 0;">Nota: El 'código' debe ser una sola palabra.</p>
        </div>
        
        <div class="admin-block">
            <div class="admin-title">Asignar Permisos a Supervisores</div>
            <select id="perm-supervisor-select" class="perm-select">
                <option value="">Seleccione un supervisor...</option>
            </select>
            <div id="perm-areas-list" class="perm-list">
                <span style="font-size: 0.75rem; color: var(--muted);">Seleccione un supervisor arriba.</span>
            </div>
            <button type="button" id="btn-save-permissions" class="perm-save-btn" disabled>Guardar Permisos</button>
        </div>

    </div>

    <h2>Editor de Plantilla de Checklist</h2>
    <div id="checklist-sections-container"></div>
    
    <p style="text-align:center; color:#ccc; font-size:0.8rem; margin-top:30px;">Panel de Configuración - Clínica Los Ángeles</p>
  </div>

  <script>
    let supervisoresData = []; 
    let sedesData = []; 
    let allAreasData = []; 
    
    // --- Render Listas Simples ---
    function renderAdminList(containerId, items, listType) {
        const cont = document.getElementById(containerId);
        if (!cont) return;
        cont.innerHTML = "";
        items.forEach(item => {
            const row = document.createElement("div");
            row.className = "list-row";
            const label = document.createElement("span");
            label.textContent = item.nombre;
            const btn = document.createElement("button");
            btn.type = "button";
            btn.textContent = "Eliminar";
            btn.className = "list-delete-btn";
            btn.dataset.id = item.id;
            btn.dataset.type = listType;
            row.appendChild(label);
            row.appendChild(btn);
            cont.appendChild(row);
        });
    }

    function syncLists() {
      // Solo renderizamos sedes, ya que supervisores se gestionan en otro lado
      renderAdminList("admin-sedes", sedesData, "sedes");
    }

    function crearOpcionesFrecuencia(valorActual) {
        const opciones = [
            { value: "", text: "--- (Manual) ---" },
            { value: "diario", text: "Diario" },
            { value: "interdiario", text: "Interdiario" },
            { value: "semanal", text: "Semanal" },
            { value: "quincenal", text: "Quincenal" },
            { value: "mensual", text: "Mensual" }
        ];
        let html = '';
        const valorNorm = (valorActual || "").toLowerCase();
        for (const opt of opciones) {
            const selected = (opt.value === valorNorm) ? 'selected' : '';
            html += `<option value="${opt.value}" ${selected}>${opt.text}</option>`;
        }
        return html;
    }

    function crearFilaHTML(actividad) {
      const tr = document.createElement("tr");
      tr.dataset.activityId = actividad.id; 
      
      const specificDate = actividad.specific_date ? actividad.specific_date : '';
      const startDate = actividad.fecha_inicio ? actividad.fecha_inicio : ''; 
      const frecuenciaOpciones = crearOpcionesFrecuencia(actividad.frecuencia);

      tr.innerHTML = `
        <td class="col-actividad" data-label="Actividad" contenteditable="true" data-field="nombre">${actividad.nombre}</td>
        <td class="col-criterio" data-label="Criterio / Estándar" contenteditable="true" data-field="criterio">${actividad.criterio || ''}</td>
        <td class="col-frecuencia" data-label="Frecuencia">
          <select class="frecuencia-select" data-field="frecuencia">${frecuenciaOpciones}</select>
        </td>
        <td class="col-cantidad" data-label="¿Cantidad?">
          <input type="checkbox" class="quantity-toggle" ${actividad.requires_quantity ? 'checked' : ''}>
        </td>
        <td class="col-fecha-esp" data-label="F. Inicio">
          <input type="date" class="date-input start-date-input" value="${startDate}">
        </td>
        <td class="col-fecha-esp" data-label="Fecha Única">
          <input type="date" class="date-input specific-date-input" value="${specificDate}">
        </td>
        <td class="actions-cell" data-label="Acciones">
          <button type="button" class="list-delete-btn delete-row-btn" data-id="${actividad.id}">X</button>
        </td>
      `;
      return tr;
    }

    function renderChecklistTemplate(areas) {
      const sectionsContainer = document.getElementById("checklist-sections-container");
      sectionsContainer.innerHTML = "";
      allAreasData = []; 

      areas.forEach(area => {
        allAreasData.push({ id: area.id, nombre: area.nombre });

        const details = document.createElement("details");
        const summary = document.createElement("summary");
        summary.innerHTML = `
          <div class="section-left">
            <div class="section-icon">${area.icon}</div>
            <div class="section-title">
              <span class="section-name">${area.nombre}</span>
              <span class="section-tag">${area.tag}</span>
            </div>
          </div>
        `;

        const body = document.createElement("div");
        body.className = "section-body";
        body.innerHTML = `
          <div class="section-actions">
            <button type="button" class="add-row-btn" data-area-id="${area.id}" data-area-codigo="${area.codigo}">+ Agregar actividad</button>
          </div>
          <table>
            <thead>
              <tr>
                <th class="col-actividad">Actividad</th>
                <th class="col-criterio">Criterio / Estándar</th>
                <th class="col-frecuencia">Frecuencia</th>
                <th class="col-cantidad">Cant?</th>
                <th class="col-fecha-esp">Inicio Recurrente</th> <th class="col-fecha-esp">Fecha Única</th>
                <th>Acción</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        `;
        const tbody = body.querySelector("tbody");
        area.actividades.forEach(act => {
          const tr = crearFilaHTML(act);
          tbody.appendChild(tr);
        });
        details.appendChild(summary); details.appendChild(body);
        sectionsContainer.appendChild(details);
      });
    }

    // --- EVENTOS ---
    document.addEventListener("click", async (e) => {
      // Agregar Actividad
      const addBtn = e.target.closest(".add-row-btn");
      if (addBtn) {
        const areaId = addBtn.dataset.areaId;
        const nombreNuevaActividad = prompt("Nombre de la nueva actividad:", "Nueva actividad");
        if (!nombreNuevaActividad || nombreNuevaActividad.trim() === "") return;
        
        try {
            const response = await fetch('api_add_activity.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ area_id: areaId, nombre: nombreNuevaActividad })
            });
            const res = await response.json();
            if (res.success) { location.reload(); } else { alert(res.error); }
        } catch (error) { alert("Error al agregar"); }
        return;
      }
      
      // Eliminar Actividad
      const delBtn = e.target.closest(".delete-row-btn");
      if (delBtn) {
        if (!confirm(`¿Eliminar actividad?`)) return;
        try {
            const response = await fetch('api_delete_activity.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: delBtn.dataset.id })
            });
            if ((await response.json()).success) delBtn.closest("tr").remove();
        } catch (error) { alert("Error al eliminar"); }
        return;
      }

      // Eliminar Sede
      const delListBtn = e.target.closest(".list-delete-btn");
      if (delListBtn && delListBtn.dataset.type === 'sedes') {
          if (!confirm(`¿Eliminar sede?`)) return;
          try {
              const resp = await fetch("api_delete_sede.php", {
                  method: "POST", headers: { "Content-Type": "application/json" },
                  body: JSON.stringify({ id: delListBtn.dataset.id }),
              });
              if ((await resp.json()).success) { delListBtn.closest(".list-row").remove(); }
          } catch (err) { alert("Error"); }
      }
    });

    // --- UPDATES ---
    async function handleRowUpdate(activityId, field, value) {
        try {
            const response = await fetch('api_update_activity.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: activityId, field: field, value: value })
            });
            return (await response.json()).success;
        } catch (error) { console.error(error); return false; }
    }

    document.addEventListener("change", async (e) => {
        const toggle = e.target.closest(".quantity-toggle");
        if (toggle) {
            const id = toggle.closest("tr").dataset.activityId;
            if (!await handleRowUpdate(id, 'requires_quantity', toggle.checked ? 1 : 0)) toggle.checked = !toggle.checked;
        }
        
        const freqSelect = e.target.closest(".frecuencia-select");
        if (freqSelect) {
            handleRowUpdate(freqSelect.closest("tr").dataset.activityId, 'frecuencia', freqSelect.value);
        }
    });

    // --- CELL EDIT ---
    let originalValue_cell = '';
    document.addEventListener('focusin', (e) => { if (e.target.matches('td[contenteditable="true"]')) originalValue_cell = e.target.textContent; });
    document.addEventListener('focusout', (e) => { 
        if (e.target.matches('td[contenteditable="true"]')) {
            const cell = e.target;
            const newVal = cell.textContent.trim();
            if(newVal !== originalValue_cell) {
                handleRowUpdate(cell.closest("tr").dataset.activityId, cell.dataset.field, newVal);
            }
        }
    });

    // --- LOGICA PERMISOS ---
    function populatePermissionsSupervisorDropdown() {
        const select = document.getElementById('perm-supervisor-select');
        select.innerHTML = '<option value="">Seleccione un supervisor...</option>';
        supervisoresData.forEach(sup => {
            const opt = document.createElement('option');
            opt.value = sup.id; opt.textContent = sup.nombre;
            select.appendChild(opt);
        });
    }

    function setupPermissionListeners() {
        const select = document.getElementById('perm-supervisor-select');
        const listContainer = document.getElementById('perm-areas-list');
        const saveBtn = document.getElementById('btn-save-permissions');
        
        select.addEventListener('change', async () => {
            if (!select.value) { listContainer.innerHTML = ''; return; }
            listContainer.innerHTML = 'Cargando...'; saveBtn.disabled = true;
            try {
                const resp = await fetch(`api_get_permissions.php?supervisor_id=${select.value}`);
                const data = await resp.json();
                const perms = data.supervisor_permissions || [];
                listContainer.innerHTML = '';
                allAreasData.forEach(area => {
                    const isChecked = perms.map(Number).includes(Number(area.id));
                    const item = document.createElement('div'); item.className = 'perm-item';
                    item.innerHTML = `<input type="checkbox" value="${area.id}" ${isChecked ? 'checked' : ''}><label>${area.nombre}</label>`;
                    listContainer.appendChild(item);
                });
                saveBtn.disabled = false;
            } catch (e) { listContainer.innerHTML = 'Error.'; }
        });

        saveBtn.addEventListener('click', async () => {
            const ids = Array.from(listContainer.querySelectorAll('input:checked')).map(cb => cb.value);
            try {
                await fetch('api_save_permissions.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ supervisor_id: select.value, area_ids: ids })
                });
                alert('¡Guardado!');
            } catch (e) { alert("Error"); }
        });
    }

    function setupAddButtons() {
        // Solo para Sedes y Areas, usuarios ya no.
        const btnSede = document.getElementById('btn-add-sede');
        if(btnSede) btnSede.addEventListener('click', async () => {
            const val = document.getElementById('input-sede').value;
            if(!val) return;
            const r = await fetch('api_add_sede.php', { method:'POST', body: JSON.stringify({ nombre: val }) });
            if((await r.json()).success) location.reload();
        });

        const btnArea = document.getElementById('btn-add-area');
        if(btnArea) btnArea.addEventListener('click', async () => {
            const n = document.getElementById('input-area-nombre').value;
            const c = document.getElementById('input-area-codigo').value;
            const e = document.getElementById('input-area-emoji').value;
            if(!n || !c) return alert('Datos faltantes');
            const r = await fetch('api_add_area.php', { method:'POST', body: JSON.stringify({ nombre: n, codigo: c, emoji: e }) });
            if((await r.json()).success) location.reload();
        });
    }

    // --- CARGA INICIAL ---
    (async function () {
        try {
            // Nota: api_get_linkage_data ahora devuelve supervisores correctamente desde la tabla nueva
            const [linkData, sedesDB, tmpl] = await Promise.all([
                fetch('api_get_linkage_data.php').then(r=>r.json()),
                fetch('api_get_sedes.php').then(r=>r.json()),
                fetch('api_get_template.php').then(r=>r.json())
            ]);
            
            supervisoresData = linkData.supervisores || [];
            sedesData = sedesDB || [];
            
            renderChecklistTemplate(tmpl);
            syncLists();
            populatePermissionsSupervisorDropdown();
            setupPermissionListeners();
            setupAddButtons();

        } catch (e) { console.error(e); alert("Error de carga"); }
    })();

    // Dark Mode Toggle
    document.getElementById('theme-toggle').addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
    });
    if(localStorage.getItem('theme')==='dark') document.body.classList.add('dark-mode');
  </script>
</body>
</html>