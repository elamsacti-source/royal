<?php include_once 'session.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Tramitología</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f1f5f9; --card: #ffffff; 
            --primary: #2563eb; --primary-hover: #1d4ed8;
            --text-main: #0f172a; --text-muted: #64748b;
            --border: #e2e8f0; --radius: 12px;
            --danger: #ef4444; --warning: #f59e0b; --success: #10b981;
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text-muted); padding: 20px; }
        .app-shell { max-width: 1100px; margin: 0 auto; padding-bottom: 40px; }
        
        /* HEADER & STATS (Igual que antes) */
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; background: var(--card); padding: 16px 24px; border-radius: var(--radius); box-shadow: var(--shadow); }
        .header h1 { color: var(--text-main); font-size: 1.25rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 10px; }
        .btn-back { text-decoration: none; color: var(--text-muted); font-weight: 500; display: flex; align-items: center; gap: 5px; }
        .btn-back:hover { color: var(--primary); }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 24px; }
        .stat-card { background: var(--card); padding: 12px 16px; border-radius: var(--radius); border: 1px solid var(--border); display: flex; flex-direction: column; }
        .stat-value { font-size: 1.5rem; font-weight: 700; color: var(--text-main); }
        .stat-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; margin-top: 4px; }
        .stat-card.total { border-left: 4px solid var(--primary); }
        .stat-card.ok { border-left: 4px solid var(--success); }
        .stat-card.warning { border-left: 4px solid var(--warning); }
        .stat-card.expired { border-left: 4px solid var(--danger); }

        .controls-bar { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
        .search-input { flex: 1; padding: 10px 16px; border-radius: 99px; border: 1px solid #cbd5e1; outline: none; font-size: 0.95rem; }
        .search-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        .btn-main { background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 99px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn-main:hover { background: var(--primary-hover); }

        /* CARDS */
        .docs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
        .doc-card { background: var(--card); border-radius: var(--radius); border: 1px solid var(--border); padding: 20px; transition: transform 0.2s; display: flex; flex-direction: column; gap: 10px; position: relative; }
        .doc-card:hover { transform: translateY(-3px); box-shadow: var(--shadow); }
        
        .doc-header { display: flex; justify-content: space-between; align-items: flex-start; }
        .doc-entidad { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: var(--primary); letter-spacing: 0.05em; }
        .doc-title { font-size: 1.1rem; font-weight: 700; color: var(--text-main); line-height: 1.3; margin-top: 4px; }
        .sede-badge { display: inline-block; font-size: 0.65rem; padding: 2px 8px; border-radius: 4px; background: #e0f2fe; color: #0369a1; font-weight: 600; margin-top: 6px; border: 1px solid #bae6fd; }
        .status-badge { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; display: inline-block; white-space: nowrap; }
        .status-expired { background: #fef2f2; color: var(--danger); border: 1px solid #fecaca; }
        .status-warning { background: #fffbeb; color: var(--warning); border: 1px solid #fde68a; }
        .status-ok { background: #f0fdf4; color: var(--success); border: 1px solid #bbf7d0; }

        .doc-timeline { display: flex; justify-content: space-between; padding: 12px 0; border-top: 1px dashed var(--border); border-bottom: 1px dashed var(--border); font-size: 0.85rem; margin-top: auto;}
        .date-val { font-weight: 600; color: var(--text-main); }
        .timeline-label { font-size: 0.7rem; color: var(--text-muted); }

        .doc-actions { display: flex; gap: 10px; margin-top: 10px; }
        .btn-pdf { flex: 1; padding: 8px; text-align: center; background: #f1f5f9; text-decoration: none; color: var(--text-main); font-size: 0.85rem; font-weight: 600; border-radius: 8px; border: 1px solid var(--border); transition: background 0.2s;}
        .btn-pdf:hover { background: #e2e8f0; }
        .btn-pdf.disabled { opacity: 0.5; pointer-events: none; }
        .btn-delete { background: transparent; border: none; font-size: 1.1rem; cursor: pointer; padding: 0 8px; border-radius: 6px; }
        .btn-delete:hover { background: #fef2f2; color: var(--danger); }

        /* MODAL */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(2px); }
        .modal-overlay.active { display: flex; }
        .modal-card { background: var(--card); width: 100%; max-width: 500px; border-radius: 16px; padding: 24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); animation: slideUp 0.3s ease; }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        .form-group { margin-bottom: 14px; }
        .form-label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-main); }
        .form-input, .form-select { width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px; outline: none; font-size: 0.95rem; background: var(--bg); }
        .form-input:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        .btn-submit { width: 100%; padding: 12px; background: var(--primary); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; margin-top: 10px; }
        .btn-submit:hover { background: var(--primary-hover); }
        
        .btn-plus { background: #10b981; color: white; border: none; border-radius: 8px; width: 42px; font-size: 1.2rem; cursor: pointer; display:flex; align-items:center; justify-content:center;}
        .btn-plus:hover { opacity: 0.9; }

        body.dark-mode { --bg: #0f172a; --card: #1e293b; --text-main: #f8fafc; --text-muted: #94a3b8; --border: #334155; }
        body.dark-mode .search-input, body.dark-mode .form-input, body.dark-mode .form-select, body.dark-mode .btn-pdf { background: #020617; border-color: #334155; color: white; }
        body.dark-mode .sede-badge { background: #172554; color: #bfdbfe; border-color: #1e3a8a; }
    </style>
</head>
<body>
    <div class="app-shell">
        <div class="header">
            <h1><span>📂</span> Centro de Documentación</h1>
            <a href="admin.php" class="btn-back">← Volver al Panel</a>
        </div>

        <div class="stats-grid">
            <div class="stat-card total"><span class="stat-value" id="stat-total">0</span><span class="stat-label">Total</span></div>
            <div class="stat-card ok"><span class="stat-value" id="stat-ok">0</span><span class="stat-label" style="color: var(--success);">Vigentes</span></div>
            <div class="stat-card warning"><span class="stat-value" id="stat-warning">0</span><span class="stat-label" style="color: var(--warning);">Por Vencer</span></div>
            <div class="stat-card expired"><span class="stat-value" id="stat-expired">0</span><span class="stat-label" style="color: var(--danger);">Vencidos</span></div>
        </div>

        <div class="controls-bar">
            <input type="text" id="searchDocs" class="search-input" placeholder="🔍 Buscar por nombre, entidad o sede...">
            <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <button onclick="openModal()" class="btn-main">+ Nuevo Documento</button>
            <?php endif; ?>
        </div>

        <div id="grid-docs" class="docs-grid">
            <p style="grid-column:1/-1; text-align:center; padding:40px; color:var(--text-muted);">Cargando...</p>
        </div>
    </div>

    <div id="modal-form" class="modal-overlay">
        <div class="modal-card">
            <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
                <h3 style="margin:0; color:var(--text-main);">Subir Documento</h3>
                <button onclick="closeModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-muted);">&times;</button>
            </div>
            <form id="form-add">
                <div class="form-group">
                    <label class="form-label">Tipo / Nombre del Documento</label>
                    <div style="display:flex; gap:8px;">
                        <select id="nombre" class="form-select" required>
                            <option value="">-- Seleccione Tipo --</option>
                        </select>
                        <button type="button" class="btn-plus" onclick="addNombre()" title="Nuevo Tipo">+</button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Entidad Emisora</label>
                    <div style="display:flex; gap:8px;">
                        <select id="entidad" class="form-select" required>
                            <option value="">-- Seleccione Entidad --</option>
                        </select>
                        <button type="button" class="btn-plus" onclick="addEntidad()" title="Nueva Entidad">+</button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Sede / Local</label>
                    <select id="sede_id" class="form-select">
                        <option value="">-- General / Todas las sedes --</option>
                        </select>
                </div>
                <div class="form-group">
                    <label class="form-label">PDF (Opcional)</label>
                    <input type="file" id="archivo_pdf" class="form-input" accept="application/pdf">
                </div>
                <div style="display:flex; gap:10px;">
                    <div class="form-group" style="flex:1">
                        <label class="form-label">Emisión</label>
                        <input type="date" id="f_emision" class="form-input" required>
                    </div>
                    <div class="form-group" style="flex:1">
                        <label class="form-label">Vencimiento</label>
                        <input type="date" id="f_vence" class="form-input" required>
                    </div>
                </div>
                <button type="submit" class="btn-submit">Guardar</button>
            </form>
        </div>
    </div>

    <script>
        const isAdmin = <?php echo ($_SESSION['user_role'] === 'admin') ? 'true' : 'false'; ?>;
        let allDocs = [];

        async function init() {
            await Promise.all([loadSedes(), loadEntidades(), loadNombres()]);
            await loadDocs();
        }

        // 1. CARGAS INICIALES
        async function loadSedes() {
            try {
                const res = await fetch('api_get_sedes.php'); 
                const sedes = await res.json();
                const select = document.getElementById('sede_id');
                if (Array.isArray(sedes)) sedes.forEach(s => {
                    const opt = document.createElement('option'); opt.value = s.id; opt.textContent = s.nombre; select.appendChild(opt);
                });
            } catch (e) {}
        }

        async function loadEntidades() {
            try {
                const res = await fetch('api_tramites.php?action=list_entidades');
                populateSelect('entidad', await res.json(), 'nombre');
            } catch (e) {}
        }

        async function loadNombres() {
            try {
                const res = await fetch('api_tramites.php?action=list_nombres');
                populateSelect('nombre', await res.json(), 'nombre');
            } catch (e) {}
        }

        function populateSelect(id, data, field) {
            const select = document.getElementById(id);
            const prev = select.value;
            select.innerHTML = `<option value="">-- Seleccione --</option>`;
            data.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item[field]; opt.textContent = item[field];
                select.appendChild(opt);
            });
            if(prev) select.value = prev;
        }

        // 2. AGREGAR NUEVOS TIPOS/ENTIDADES
        async function addEntidad() {
            await addNewItem('add_entidad', 'entidad', "Nueva Entidad (Ej: SUNAFIL):");
        }
        async function addNombre() {
            await addNewItem('add_nombre', 'nombre', "Nuevo Tipo de Documento (Ej: Licencia):");
        }

        async function addNewItem(action, selectId, promptText) {
            const val = prompt(promptText);
            if (!val || !val.trim()) return;
            const finalVal = val.toUpperCase().trim();

            try {
                const res = await fetch('api_tramites.php', {
                    method: 'POST', headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ action: action, nombre: finalVal })
                });
                const data = await res.json();
                if (data.success) {
                    if(selectId === 'entidad') await loadEntidades();
                    if(selectId === 'nombre') await loadNombres();
                    document.getElementById(selectId).value = finalVal;
                } else { alert("Error al agregar."); }
            } catch (e) { alert("Error de red."); }
        }

        // 3. CARGA Y RENDER
        async function loadDocs() {
            try {
                const res = await fetch('api_tramites.php');
                const json = await res.json();
                if(json.success) { allDocs = json.data; renderDocs(allDocs); }
            } catch(e) { document.getElementById('grid-docs').innerHTML = '<p style="text-align:center">Error conexión</p>'; }
        }

        function renderDocs(docs) {
            updateStats(docs);
            const container = document.getElementById('grid-docs');
            container.innerHTML = '';
            if(docs.length === 0) { container.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:40px; color:var(--text-muted);">📭 No hay documentos.</div>'; return; }

            docs.forEach(doc => {
                let statusText = `${doc.dias_restantes} días`;
                if(doc.estado_calculado === 'expired') statusText = `Venció hace ${Math.abs(doc.dias_restantes)} días`;
                if(doc.dias_restantes === 0) statusText = `¡Vence HOY!`;
                
                const pdfBtn = doc.archivo_pdf ? `<a href="${doc.archivo_pdf}" target="_blank" class="btn-pdf">📄 Ver PDF</a>` : `<span class="btn-pdf disabled">Sin PDF</span>`;
                const deleteBtn = isAdmin ? `<button onclick="deleteDoc(${doc.id})" class="btn-delete" title="Eliminar">🗑️</button>` : '';

                const card = document.createElement('div');
                card.className = 'doc-card';
                card.innerHTML = `
                    <div class="doc-header">
                        <div>
                            <div class="doc-entidad">${doc.entidad || 'General'}</div>
                            <div class="doc-title">${doc.nombre}</div>
                            <div class="sede-badge">📍 ${doc.sede_nombre}</div>
                        </div>
                        <div class="status-badge status-${doc.estado_calculado}">${statusText}</div>
                    </div>
                    <div class="doc-timeline">
                        <div><span class="timeline-label">Emisión</span><span class="date-val">${formatDate(doc.fecha_emision)}</span></div>
                        <div style="text-align:right;"><span class="timeline-label">Vencimiento</span><span class="date-val" style="color:${doc.estado_calculado==='expired'?'var(--danger)':'inherit'}">${formatDate(doc.fecha_vencimiento)}</span></div>
                    </div>
                    <div class="doc-actions">${pdfBtn}${deleteBtn}</div>
                `;
                container.appendChild(card);
            });
        }

        function updateStats(docs) {
            document.getElementById('stat-total').textContent = docs.length;
            document.getElementById('stat-ok').textContent = docs.filter(d => d.estado_calculado === 'ok').length;
            document.getElementById('stat-warning').textContent = docs.filter(d => d.estado_calculado === 'warning').length;
            document.getElementById('stat-expired').textContent = docs.filter(d => d.estado_calculado === 'expired').length;
        }

        document.getElementById('searchDocs').addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            const filtered = allDocs.filter(d => d.nombre.toLowerCase().includes(term) || (d.entidad && d.entidad.toLowerCase().includes(term)) || (d.sede_nombre && d.sede_nombre.toLowerCase().includes(term)));
            renderDocs(filtered);
        });

        function formatDate(s) { if(!s) return '-'; const [y,m,d]=s.split('-'); return `${d}/${m}/${y}`; }

        async function deleteDoc(id) {
            if(!confirm('¿Eliminar documento?')) return;
            await fetch('api_tramites.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({action:'delete', id}) });
            loadDocs();
        }

        const modal = document.getElementById('modal-form');
        function openModal() { modal.classList.add('active'); }
        function closeModal() { modal.classList.remove('active'); }
        modal.addEventListener('click', e => { if(e.target===modal) closeModal(); });

        document.getElementById('form-add').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('.btn-submit');
            const original = btn.textContent;
            btn.disabled = true; btn.textContent = 'Guardando...';

            const fd = new FormData();
            fd.append('action', 'add');
            fd.append('nombre', document.getElementById('nombre').value);
            fd.append('entidad', document.getElementById('entidad').value);
            fd.append('sede_id', document.getElementById('sede_id').value);
            fd.append('fecha_emision', document.getElementById('f_emision').value);
            fd.append('fecha_vencimiento', document.getElementById('f_vence').value);
            const file = document.getElementById('archivo_pdf').files[0];
            if(file) fd.append('archivo_pdf', file);

            try {
                const res = await fetch('api_tramites.php', { method:'POST', body:fd });
                const data = await res.json();
                if(data.success) { closeModal(); e.target.reset(); loadDocs(); }
                else alert(data.error);
            } catch(err) { alert('Error de red'); }
            finally { btn.disabled = false; btn.textContent = original; }
        });

        if(localStorage.getItem('theme')==='dark') document.body.classList.add('dark-mode');
        init();
    </script>
</body>
</html>