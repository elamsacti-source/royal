<?php
// admin_inventario.php
// V12.2: Corrección del botón Importar (Eliminada función duplicada).
include_once 'session.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') { header('Location: login.php'); exit; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventario & Kardex Maestro</title>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* Estilos Generales */
    :root { --primary: #0f172a; --accent: #2563eb; --bg: #f1f5f9; --border: #e2e8f0; --text: #334155; --text-light: #64748b; --success: #10b981; --danger: #ef4444; }
    body { font-family: 'Manrope', sans-serif; background: var(--bg); color: var(--text); margin: 0; display: flex; height: 100vh; overflow: hidden; font-size: 13px; }
    
    /* MODAL KARDEX */
    .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; display: none; justify-content: center; align-items: center; backdrop-filter: blur(2px); }
    .modal-content { background: white; width: 90%; max-width: 750px; max-height: 85vh; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); display: flex; flex-direction: column; overflow: hidden; animation: slideUp 0.3s ease; }
    @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    
    .modal-header { padding: 15px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc; }
    .modal-title { font-size: 1.1rem; font-weight: 800; color: var(--primary); margin: 0; }
    .modal-close { background: none; border: none; font-size: 1.5rem; color: #94a3b8; cursor: pointer; transition: 0.2s; }
    .modal-close:hover { color: var(--danger); }
    
    .modal-body { flex: 1; overflow-y: auto; padding: 0; }
    .kardex-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
    .kardex-table th { background: #fff; text-align: left; padding: 12px 20px; font-weight: 700; color: var(--text-light); position: sticky; top: 0; border-bottom: 2px solid var(--border); z-index: 10; }
    .kardex-table td { padding: 10px 20px; border-bottom: 1px solid #f1f5f9; }
    .kardex-table tr:hover { background: #f8fafc; }
    .mov-tag { display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; color: white; text-transform: uppercase; }

    /* LAYOUT */
    .sidebar { width: 220px; background: var(--primary); color: white; padding: 20px 12px; display: flex; flex-direction: column; z-index: 20; }
    .sidebar-header { font-weight: 800; font-size: 1rem; margin-bottom: 25px; display: flex; align-items: center; gap: 8px; color: #fff; padding-left: 8px; }
    .menu-item { color: #94a3b8; padding: 9px 10px; border-radius: 6px; font-weight: 600; display: block; margin-bottom: 2px; text-decoration: none; transition:0.2s; }
    .menu-item:hover, .menu-item.active { background: var(--accent); color: white; }
    
    .content { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
    .top-bar { background: white; padding: 8px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; height: 50px; }
    
    .view-switch { display: flex; background: #f1f5f9; padding: 4px; border-radius: 8px; border: 1px solid var(--border); }
    .switch-btn { padding: 6px 12px; border: none; border-radius: 6px; cursor: pointer; font-size: 0.8rem; font-weight: 600; color: var(--text-light); background: transparent; }
    .switch-btn.active { background: white; color: var(--accent); box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    
    .actions-group { display: flex; gap: 10px; align-items: center; }
    .btn { padding: 6px 12px; border-radius: 6px; border: 1px solid #e2e8f0; background: white; cursor: pointer; font-weight: 700; font-size: 0.8rem; display: flex; align-items: center; gap: 6px; }
    .btn-primary { background: var(--accent); color: white; border: none; }

    .dropdown { position: relative; display: inline-block; }
    .dropdown-content { display: none; position: absolute; right: 0; top: 110%; background: #fff; min-width: 200px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border: 1px solid var(--border); border-radius: 8px; z-index: 100; padding: 6px 0; }
    .dropdown:hover .dropdown-content { display: block; }
    .dropdown-header { font-size: 0.7rem; font-weight: 700; color: var(--text-muted); padding: 8px 16px; text-transform: uppercase; background: #f8fafc; border-bottom: 1px solid var(--border); margin-bottom: 4px; }
    .dropdown-item { color: var(--text); padding: 10px 16px; text-decoration: none; display: flex; align-items: center; gap: 8px; font-size: 0.85rem; cursor: pointer; }
    .dropdown-item:hover { background: #f8fafc; color: var(--accent); }

    .scroll-area { flex: 1; overflow-y: auto; padding: 15px 20px; }

    /* FILTROS */
    .filter-section { background:white; padding:15px; border-radius:8px; border:1px solid var(--border); margin-bottom:15px; display:flex; flex-direction:column; gap:12px; }
    .filter-row { display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; }
    .filter-col { display: flex; flex-direction: column; gap: 4px; }
    .filter-lbl { font-size: 0.65rem; font-weight: 800; color: var(--text-light); text-transform: uppercase; }
    
    .chip { border: 1px solid var(--border); background: white; padding: 4px 10px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; color: var(--text-light); cursor: pointer; }
    .chip.active { background: var(--primary); color: white; border-color: var(--primary); }
    
    .multiselect { position: relative; }
    .ms-btn { padding: 6px 10px; border: 1px solid var(--border); border-radius: 6px; background: white; cursor: pointer; min-width: 180px; text-align: left; display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; font-weight:600; color:var(--text); }
    .ms-dropdown { display: none; position: absolute; top: 100%; left: 0; width: 280px; max-height: 300px; overflow-y: auto; background: white; border: 1px solid var(--border); border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 100; padding: 10px; margin-top: 4px; }
    .ms-item { display: flex; align-items: center; gap: 8px; font-size: 0.8rem; cursor: pointer; padding: 5px; }
    .ms-item:hover { background: #f8fafc; }
    .btn-search { padding: 7px 12px; border-radius: 6px; border: 1px solid var(--border); width: 220px; font-size: 0.85rem; }

    /* KPIS / CARDS */
    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px; }
    .kpi-card { background: white; border: 1px solid var(--border); border-radius: 8px; padding: 15px; height: 155px; position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; }
    
    .kpi-card.global { background: #1e293b; color: white; border: none; }
    .kpi-card.global .kpi-lbl { color: #94a3b8; }
    .kpi-card.global .kv-val { color: white; }
    
    .kpi-card.sede::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 3px; }
    .huaura::before { background: #3b82f6; } .integra::before { background: #ec4899; } .mm::before { background: #10b981; }
    .kpi-card.inactive { opacity: 0.5; filter: grayscale(1); }

    .kpi-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
    .kpi-lbl { font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: var(--text-light); }
    .kv-row { display: flex; justify-content: space-between; align-items: baseline; font-size: 0.75rem; margin-bottom: 4px; }
    .kv-lbl { font-weight: 600; color: var(--text-light); font-size: 0.7rem; }
    .kv-val { font-weight: 800; font-size: 0.95rem; }
    .stock-detail { font-size: 0.65rem; color: #94a3b8; display: flex; gap: 6px; justify-content: flex-end; opacity: 0.8; margin-top:2px; font-family: monospace; }

    /* AUDIT CARDS */
    .audit-kpi-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px; display: none; }
    .audit-card { background: white; border: 1px solid var(--border); border-radius: 8px; padding: 15px; text-align: center; }
    .ac-val { font-size: 1.5rem; font-weight: 800; display: block; margin-top: 5px; }

    /* TABLA */
    .table-container { background: white; border: 1px solid var(--border); border-radius: 8px; overflow: hidden; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #f8fafc; text-align: left; padding: 12px 14px; font-size: 0.7rem; color: var(--text-light); font-weight: 800; text-transform: uppercase; border-bottom: 1px solid var(--border); }
    td { padding: 8px 14px; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; vertical-align: middle; height: 36px; }
    
    .inp-count { width: 70px; padding: 5px; border: 1px solid #cbd5e1; border-radius: 6px; text-align: right; font-weight: 700; color: var(--primary); }
    .inp-count:focus { border-color: var(--accent); outline: none; background: #eff6ff; }
    .diff-neg { color: #ef4444; font-weight: 700; } .diff-pos { color: #10b981; font-weight: 700; }
    
    .prod-main { font-weight: 600; color: var(--primary); display:block; margin-bottom:1px; }
    .prod-sub { font-size: 0.7rem; color: #64748b; font-family: monospace; display: flex; gap: 6px; }
    .tag-sede { font-size: 0.65rem; font-weight: 700; padding: 3px 8px; border-radius: 4px; background: #e2e8f0; color: var(--text-muted); text-transform: uppercase; }
    
    .btn-icon { border: none; background: transparent; color: #94a3b8; cursor: pointer; padding: 6px; border-radius: 6px; transition:0.2s; font-size: 1.1rem; }
    .btn-icon:hover { background: #f1f5f9; color: var(--accent); }
    .btn-icon.del:hover { background: #fee2e2; color: #ef4444; }
    
    .pagination { padding: 10px 20px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #fff; }
</style>
</head>
<body>

<div id="kardex-modal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="kardex-title">Kardex</h3>
            <button class="modal-close" onclick="closeKardex()">×</button>
        </div>
        <div class="modal-body">
            <div id="kardex-loading" style="text-align:center; padding:30px; color:#94a3b8;">Cargando movimientos...</div>
            <table class="kardex-table" id="tb-kardex" style="display:none;">
                <thead><tr><th>Fecha</th><th>Tipo Movimiento</th><th style="text-align:right">Cantidad</th></tr></thead>
                <tbody id="tb-kardex-body"></tbody>
            </table>
        </div>
    </div>
</div>

<div class="sidebar">
    <div class="sidebar-header"><i class="ph-fill ph-first-aid-kit" style="color:var(--accent);"></i> ADMIN PANEL</div>
    <a href="#" class="menu-item active">Inventario</a>
    <a href="admin_homologar.php" class="menu-item">Homologador</a>
    <a href="intranet.php" class="menu-item">Salir</a>
</div>

<div class="content">
    <div class="top-bar">
        <div class="view-switch">
            <button class="switch-btn active" onclick="setMode('inventory')">Valorización</button>
            <button class="switch-btn" onclick="setMode('audit')">Inventario Físico</button>
        </div>
        <div class="actions-group">
            <div class="dropdown">
                <button class="btn"><i class="ph-bold ph-export"></i> Exportar <i class="ph-bold ph-caret-down"></i></button>
                <div class="dropdown-content">
                    <div class="dropdown-header">Reportes</div>
                    <a class="dropdown-item" onclick="exportToExcel()"><i class="ph-fill ph-microsoft-excel-logo" style="color:#10b981;"></i> Excel (Vista Actual)</a>
                    <div class="dropdown-header">Base de Datos</div>
                    <a class="dropdown-item" onclick="openExportDBF('inventario')"><i class="ph-bold ph-database"></i> DBF ZETH70</a>
                    <a class="dropdown-item" onclick="openExportDBF('principios')"><i class="ph-bold ph-database"></i> DBF ZETH19</a>
                </div>
            </div>
            <button class="btn btn-primary" onclick="openImport()"><i class="ph-bold ph-cloud-arrow-up"></i> Importar</button>
        </div>
    </div>

    <div class="scroll-area">
        
        <div class="filter-section">
            <div class="filter-row">
                <div class="filter-col">
                    <span class="filter-lbl">Sedes</span>
                    <div class="chip-group">
                        <button class="chip active" onclick="toggleSede(this, 'HUAURA')">Huaura</button>
                        <button class="chip active" onclick="toggleSede(this, 'INTEGRA')">Integra</button>
                        <button class="chip active" onclick="toggleSede(this, 'M.MUNDO')">M.Mundo</button>
                    </div>
                </div>
                <div class="filter-col">
                    <span class="filter-lbl">Grupo</span>
                    <div class="chip-group">
                        <button class="chip active" onclick="toggleGroup(this, 'PRODUCTOS')">Ventas/Prod</button>
                        <button class="chip active" onclick="toggleGroup(this, 'SERVICIOS')">Ventas/Serv</button>
                    </div>
                </div>
                <div class="filter-col">
                    <span class="filter-lbl">Categoría</span>
                    <div class="multiselect" style="position:relative;">
                        <div class="ms-btn" onclick="toggleMs('cat')"><span id="ms-text-cat">Todas</span> <i class="ph-bold ph-caret-down"></i></div>
                        <div class="ms-dropdown" id="ms-drop-cat"><input type="text" class="ms-search" placeholder="Buscar..." onkeyup="filterMs('cat',this.value)"><div class="ms-list" id="ms-list-cat"></div></div>
                    </div>
                </div>
                <div class="filter-col">
                    <span class="filter-lbl">Marca</span>
                    <div class="multiselect" style="position:relative;">
                        <div class="ms-btn" onclick="toggleMs('mar')"><span id="ms-text-mar">Todas</span> <i class="ph-bold ph-caret-down"></i></div>
                        <div class="ms-dropdown" id="ms-drop-mar"><input type="text" class="ms-search" placeholder="Buscar..." onkeyup="filterMs('mar',this.value)"><div class="ms-list" id="ms-list-mar"></div></div>
                    </div>
                </div>
            </div>
            
            <div class="filter-row" style="justify-content: space-between; border-top: 1px solid #f1f5f9; padding-top: 15px;">
                <div style="display:flex; gap:12px;">
                    <input type="text" id="txt-prod" class="btn-search" placeholder="Buscar Producto / Código..." onkeyup="debounceSearch()">
                    <input type="text" id="txt-prin" class="btn-search" placeholder="Buscar Principio Activo..." onkeyup="debounceSearch()">
                </div>
                <div style="font-size:0.85rem; color:var(--text-muted); font-weight:600;" id="page-info">Cargando...</div>
            </div>
        </div>

        <div id="view-inventory" class="kpi-row">
            <div class="kpi-card global">
                <div class="kpi-head"><span class="kpi-lbl" style="color:white;">TOTAL GLOBAL</span> <i class="ph-bold ph-globe"></i></div>
                <div class="kpi-body">
                    <div class="kv-row"><span class="kv-lbl">COSTO</span> <span class="kv-val" id="gen-costo">0.00</span></div>
                    <div class="kv-row"><span class="kv-lbl">VENTA</span> <span class="kv-val" id="gen-venta">0.00</span></div>
                    <div style="margin-top:2px;">
                        <div class="kv-row"><span class="kv-lbl">STOCK</span> <span class="kv-val" id="gen-stock" style="color:#4ade80">0</span></div>
                        <div id="gen-stock-detail" class="stock-detail"></div>
                    </div>
                    <div class="kv-row"><span class="kv-lbl">ITEMS</span> <span class="kv-val" id="gen-items">0</span></div>
                </div>
            </div>
            <div id="card-huaura" class="kpi-card sede huaura"><div class="kpi-head"><span class="kpi-lbl">HUAURA</span></div><div class="kpi-body"><div class="kv-row"><span class="kv-lbl">COSTO</span><b class="kv-val cost" id="vc-huaura">0</b></div><div class="kv-row"><span class="kv-lbl">VENTA</span><b class="kv-val" id="vv-huaura">0</b></div><div class="kv-row"><span class="kv-lbl">STOCK</span><b class="kv-val" id="sk-huaura">0</b></div><div class="kv-row"><span class="kv-lbl">ITEMS</span><b class="kv-val" id="it-huaura">0</b></div></div></div>
            <div id="card-integra" class="kpi-card sede integra"><div class="kpi-head"><span class="kpi-lbl">INTEGRA</span></div><div class="kpi-body"><div class="kv-row"><span class="kv-lbl">COSTO</span><b class="kv-val cost" id="vc-integra">0</b></div><div class="kv-row"><span class="kv-lbl">VENTA</span><b class="kv-val" id="vv-integra">0</b></div><div class="kv-row"><span class="kv-lbl">STOCK</span><b class="kv-val" id="sk-integra">0</b></div><div class="kv-row"><span class="kv-lbl">ITEMS</span><b class="kv-val" id="it-integra">0</b></div></div></div>
            <div id="card-mm" class="kpi-card sede mm"><div class="kpi-head"><span class="kpi-lbl">M.MUNDO</span></div><div class="kpi-body"><div class="kv-row"><span class="kv-lbl">COSTO</span><b class="kv-val cost" id="vc-mm">0</b></div><div class="kv-row"><span class="kv-lbl">VENTA</span><b class="kv-val" id="vv-mm">0</b></div><div class="kv-row"><span class="kv-lbl">STOCK</span><b class="kv-val" id="sk-mm">0</b></div><div class="kv-row"><span class="kv-lbl">ITEMS</span><b class="kv-val" id="it-mm">0</b></div></div></div>
        </div>

        <div id="view-audit" class="audit-kpi-row">
            <div class="audit-card" style="border-top:4px solid #ef4444;"><span style="font-size:0.7rem;font-weight:700;color:#ef4444;text-transform:uppercase;">Valor Faltantes</span><span class="ac-val" style="color:#ef4444;" id="aud-loss">S/ 0.00</span></div>
            <div class="audit-card" style="border-top:4px solid #10b981;"><span style="font-size:0.7rem;font-weight:700;color:#10b981;text-transform:uppercase;">Valor Sobrantes</span><span class="ac-val" style="color:#10b981;" id="aud-gain">S/ 0.00</span></div>
            <div class="audit-card" style="border-top:4px solid #3b82f6;"><span style="font-size:0.7rem;font-weight:700;color:#3b82f6;text-transform:uppercase;">Balance Neto</span><span class="ac-val" id="aud-net">S/ 0.00</span></div>
        </div>

        <div class="table-container">
            <table><thead id="thead-main"></thead><tbody id="tb-inv"></tbody></table>
            <div class="pagination"><button onclick="navPage(-1)" class="btn">Ant</button> <button onclick="navPage(1)" class="btn">Sig</button></div>
        </div>
    </div>
</div>

<script>
    let mode = 'inventory'; // inventory | audit
    let filters = { sedes: ['HUAURA', 'INTEGRA', 'M.MUNDO'], groups: ['PRODUCTOS', 'SERVICIOS'], lineas: [], marcas: [] };
    let state = { page:1, sort:'nombre', dir:'ASC' };

    document.addEventListener("DOMContentLoaded", () => { loadFilters(); loadData(); document.addEventListener('click', e => { if(!e.target.closest('.multiselect')) { document.getElementById('ms-drop-cat').style.display='none'; document.getElementById('ms-drop-mar').style.display='none'; } }); });

    async function loadFilters() {
        try {
            const res = await fetch('admin_inventario_backend.php?action=get_filters');
            const d = await res.json();
            const lCat = document.getElementById('ms-list-cat'); d.lineas.forEach(l => lCat.innerHTML += `<label class="ms-item"><input type="checkbox" value="${l}" onchange="toggleList(this, 'lineas')"> <span>${l}</span></label>`);
            const lMar = document.getElementById('ms-list-mar'); d.marcas.forEach(m => lMar.innerHTML += `<label class="ms-item"><input type="checkbox" value="${m}" onchange="toggleList(this, 'marcas')"> <span>${m}</span></label>`);
        } catch(e) {}
    }

    function setMode(m) {
        mode = m;
        document.querySelectorAll('.switch-btn').forEach(b => b.classList.remove('active'));
        event.target.classList.add('active');
        document.getElementById('view-inventory').style.display = mode === 'inventory' ? 'grid' : 'none';
        document.getElementById('view-audit').style.display = mode === 'audit' ? 'grid' : 'none';
        if(mode === 'audit') loadAuditKPIs();
        loadData();
    }

    function toggleSede(btn, val) {
        btn.classList.toggle('active'); const idx = filters.sedes.indexOf(val);
        if (idx > -1) filters.sedes.splice(idx, 1); else filters.sedes.push(val);
        if(mode==='inventory') {
            let k = 'huaura'; if(val==='INTEGRA') k='integra'; if(val==='M.MUNDO') k='mm';
            let card = document.getElementById('card-'+k);
            if(filters.sedes.includes(val)) card.classList.remove('inactive'); else card.classList.add('inactive');
        }
        state.page = 1; loadData();
    }
    function toggleGroup(btn, val) { btn.classList.toggle('active'); const idx = filters.groups.indexOf(val); if(idx>-1) filters.groups.splice(idx,1); else filters.groups.push(val); state.page=1; loadData(); }
    function toggleMs(type) { const drop=document.getElementById('ms-drop-'+type); const other=type==='cat'?'mar':'cat'; document.getElementById('ms-drop-'+other).style.display='none'; drop.style.display=(drop.style.display==='block')?'none':'block'; }
    function filterMs(type, txt) { txt=txt.toLowerCase(); document.getElementById('ms-list-'+type).querySelectorAll('.ms-item').forEach(el=>{ el.style.display=el.innerText.toLowerCase().includes(txt)?'flex':'none'; }); }
    function toggleList(chk, key) { const val=chk.value; if(chk.checked) filters[key].push(val); else filters[key]=filters[key].filter(x=>x!==val); 
        document.getElementById('ms-text-'+(key==='lineas'?'cat':'mar')).innerText = filters[key].length===0?'Todas':`${filters[key].length} selec.`; state.page=1; loadData(); }

    async function loadData() {
        const p = new URLSearchParams(state);
        p.append('mode', mode); 
        p.append('sedes', filters.sedes.join(',')); p.append('groups', filters.groups.join(',')); p.append('lineas', filters.lineas.join(',')); p.append('marcas', filters.marcas.join(','));
        p.append('q_prod', document.getElementById('txt-prod').value);
        p.append('q_prin', document.getElementById('txt-prin').value);

        try {
            const res = await fetch(`admin_inventario_backend.php?${p}`);
            const d = await res.json();
            const fmt = n => 'S/ ' + parseFloat(n||0).toLocaleString('en-US', {minimumFractionDigits: 2});
            const num = n => parseFloat(n||0).toLocaleString('en-US');

            if(mode === 'inventory') {
                document.getElementById('gen-costo').innerText = fmt(d.card_general.costo);
                document.getElementById('gen-venta').innerText = fmt(d.card_general.venta);
                document.getElementById('gen-stock').innerText = num(d.card_general.stock);
                document.getElementById('gen-items').innerText = num(d.card_general.items);

                let stockHtml = '';
                ['HUAURA','INTEGRA','M.MUNDO'].forEach(s => {
                    if(d.cards_sedes[s]) {
                        let short = s.substring(0,2); if(s==='INTEGRA') short='IN'; if(s==='M.MUNDO') short='MM';
                        stockHtml += `<span>${short}:${(d.cards_sedes[s].stock||0).toLocaleString('en-US')}</span> `;
                    }
                });
                document.getElementById('gen-stock-detail').innerHTML = stockHtml;

                ['HUAURA','INTEGRA','M.MUNDO'].forEach(s => {
                    let k = 'huaura'; if(s==='INTEGRA') k='integra'; if(s==='M.MUNDO') k='mm';
                    if(d.cards_sedes[s]) {
                        document.getElementById(`vc-${k}`).innerText = fmt(d.cards_sedes[s].costo);
                        document.getElementById(`vv-${k}`).innerText = fmt(d.cards_sedes[s].venta);
                        document.getElementById(`sk-${k}`).innerText = num(d.cards_sedes[s].stock);
                        document.getElementById(`it-${k}`).innerText = num(d.cards_sedes[s].items);
                    }
                });
            } else {
                loadAuditKPIs();
            }

            const th = document.getElementById('thead-main');
            if (mode === 'inventory') {
                th.innerHTML = `<tr><th style="width:30%">Producto</th><th style="text-align:center">Sede</th><th>Cat/Marca</th><th style="text-align:right">Stock</th><th style="text-align:right">Costo</th><th style="text-align:right">Importe</th><th style="text-align:right">P.V.P</th><th style="text-align:right">M.B %</th><th style="width:80px;">Acciones</th></tr>`;
            } else {
                th.innerHTML = `<tr><th style="width:30%">Producto</th><th style="text-align:center">Sede</th><th style="text-align:right">Stock Sis</th><th style="text-align:right;width:100px;">Conteo Físico</th><th style="text-align:right">Diferencia</th><th style="text-align:right">Valor Dif.</th><th style="width:50px;">Kardex</th></tr>`;
            }

            const tb = document.getElementById('tb-inv'); tb.innerHTML = '';
            d.list.forEach(i => {
                let p=parseFloat(i.precio), c=parseFloat(i.costo), m=(p>0?((p-c)/p)*100:0);
                let colorM = '#64748b'; if(m < 0) colorM = '#ef4444'; else if(m > 20) colorM = '#10b981';
                let sedeUI = i.sede==='M.MUNDO'?'M.Mundo':(i.sede==='INTEGRA'?'Integra':i.sede);
                
                let btnKardex = `<button onclick="openKardex('${i.codigo}')" class="btn-icon" title="Ver Kardex"><i class="ph-bold ph-list-magnifying-glass"></i></button>`;

                if (mode === 'inventory') {
                    tb.innerHTML += `<tr>
                        <td><span class="prod-main">${i.nombre}</span><div class="prod-sub"><span class="prod-prin">${i.principio||''}</span> ${i.codigo}</div></td>
                        <td style="text-align:center;"><span class="tag-sede">${sedeUI}</span></td>
                        <td><div style="font-weight:600;font-size:0.7rem;">${i.linea?i.linea.substring(0,18):'-'}</div><div style="font-size:0.7rem;color:#94a3b8;">${i.marca?i.marca.substring(0,18):'-'}</div></td>
                        <td class="num" style="color:var(--accent);">${i.stock}</td>
                        <td class="num">${fmt(c)}</td>
                        <td class="num">${fmt(i.total_valor)}</td>
                        <td class="num" style="font-weight:700;">${fmt(p)}</td>
                        <td class="num" style="color:${colorM};font-weight:700;">${m.toFixed(1)}%</td>
                        <td style="text-align:center;display:flex;gap:5px;justify-content:center;">${btnKardex} <button onclick="delItem(${i.id})" class="btn-icon del"><i class="ph-bold ph-trash"></i></button></td>
                    </tr>`;
                } else {
                    let countVal = i.conteo_fisico !== '' ? i.conteo_fisico : '';
                    let diff = countVal !== '' ? (countVal - i.stock) : 0;
                    let valDiff = diff * parseFloat(i.costo);
                    let colorCls = diff < 0 ? 'diff-neg' : (diff > 0 ? 'diff-pos' : '');
                    tb.innerHTML += `<tr>
                        <td><span class="prod-main">${i.nombre}</span><div class="prod-sub">${i.codigo}</div></td>
                        <td style="text-align:center;"><span class="tag-sede">${sedeUI}</span></td>
                        <td class="num">${i.stock}</td>
                        <td style="text-align:right;"><input type="number" class="inp-count" value="${countVal}" onchange="saveCount(${i.id}, this.value)"></td>
                        <td class="num ${colorCls}">${countVal!==''?diff:'-'}</td>
                        <td class="num" style="font-weight:700;">${countVal!==''?fmt(valDiff):'-'}</td>
                        <td style="text-align:center;">${btnKardex}</td>
                    </tr>`;
                }
            });
            document.getElementById('page-info').innerText = `Pág ${d.pagination.current_page} de ${d.pagination.total_pages} (${d.pagination.total_items})`;
        } catch(e) {}
    }

    async function saveCount(id, val) {
        await fetch('admin_inventario_backend.php', { method: 'POST', body: JSON.stringify({ action: 'update_count', id: id, val: val }) });
        loadData();
    }

    async function loadAuditKPIs() {
        let s = filters.sedes.length === 1 ? filters.sedes[0] : '';
        const res = await fetch(`admin_inventario_backend.php?action=audit_kpi&sede=${s}`);
        const d = await res.json();
        const fmt = n => 'S/ ' + parseFloat(n).toLocaleString('en-US', {minimumFractionDigits:2});
        document.getElementById('aud-loss').innerText = fmt(d.perdida);
        document.getElementById('aud-gain').innerText = fmt(d.ganancia);
        document.getElementById('aud-net').innerText = fmt(d.neto);
    }

    function openKardex(codigo) {
        const modal = document.getElementById('kardex-modal');
        const loading = document.getElementById('kardex-loading');
        const table = document.getElementById('tb-kardex');
        const tbody = document.getElementById('tb-kardex-body');
        modal.style.display = 'flex'; loading.style.display = 'block'; table.style.display = 'none';
        document.getElementById('kardex-title').innerText = `Kardex (Cod: ${codigo})`;

        fetch(`admin_inventario_backend.php?action=get_kardex&codigo=${codigo}`)
            .then(res => res.json())
            .then(data => {
                if(data.producto) document.getElementById('kardex-title').innerText = `${data.producto.nombre}`;
                tbody.innerHTML = '';
                if (data.movimientos.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;padding:20px;color:#94a3b8;">Sin movimientos recientes.</td></tr>';
                } else {
                    data.movimientos.forEach(m => {
                        let colorClass = m.cant < 0 ? 'diff-neg' : 'diff-pos';
                        tbody.innerHTML += `<tr><td>${m.fecha}</td><td><span class="mov-tag" style="background:${m.color}">${m.tipo}</span></td><td style="text-align:right;font-weight:700;" class="${colorClass}">${m.cant}</td></tr>`;
                    });
                }
                loading.style.display = 'none'; table.style.display = 'table';
            });
    }
    function closeKardex() { document.getElementById('kardex-modal').style.display = 'none'; }
    window.onclick = function(event) { if (event.target == document.getElementById('kardex-modal')) closeKardex(); }

    function openImport() {
        Swal.fire({
            title: 'Importar Kardex (ZETH53)',
            html: `<div style="text-align:left;font-size:0.9rem;color:#64748b;margin-bottom:15px;">Selecciona el archivo <b>ZETH53.DBF</b>.<br><small>Reemplazará el historial actual.</small></div><input type="file" id="dbfInput" accept=".dbf" class="swal2-input" style="width:80%;">`,
            showCancelButton: true,
            confirmButtonText: 'Subir',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                const f = document.getElementById('dbfInput');
                if (f.files.length === 0) { Swal.showValidationMessage('Selecciona un archivo'); return false; }
                const fd = new FormData(); fd.append('dbf_file', f.files[0]);
                return fetch('admin_importar_kardex.php', { method: 'POST', body: fd })
                .then(r => r.json()).then(d => { if(!d.success) throw new Error(d.message); return d.message; })
                .catch(e => { Swal.showValidationMessage(e); });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => { if (result.isConfirmed) Swal.fire('Éxito', result.value, 'success'); });
    }

    async function exportToExcel() {
        Swal.fire({title:'Generando Reporte...', text:'Procesando datos...', didOpen:()=>Swal.showLoading()});
        const p = new URLSearchParams(state);
        p.append('mode', mode); p.append('no_limit', '1');
        p.append('sedes', filters.sedes.join(',')); p.append('groups', filters.groups.join(',')); 
        p.append('lineas', filters.lineas.join(',')); p.append('marcas', filters.marcas.join(','));
        p.append('q_prod', document.getElementById('txt-prod').value);
        p.append('q_prin', document.getElementById('txt-prin').value);

        const res = await fetch(`admin_inventario_backend.php?${p}`);
        const d = await res.json();
        
        let html = `<html><head><meta charset='UTF-8'></head><body><h3>REPORTE ${mode === 'inventory' ? 'VALORIZADO' : 'FISICO'}</h3><table border="1"><thead>`;
        if (mode === 'inventory') {
            html += `<tr style="background:#0f172a;color:white;"><th>COD</th><th>PRODUCTO</th><th>PRINCIPIO</th><th>SEDE</th><th>CAT</th><th>MARCA</th><th>STOCK</th><th>COSTO</th><th>PRECIO</th><th>MARGEN</th><th>TOTAL</th></tr></thead><tbody>`;
            d.list.forEach(i => {
                let p=parseFloat(i.precio), c=parseFloat(i.costo), m=(p>0?((p-c)/p)*100:0);
                html += `<tr><td>${i.codigo}</td><td>${i.nombre}</td><td>${i.principio}</td><td>${i.sede}</td><td>${i.linea}</td><td>${i.marca}</td><td>${i.stock}</td><td>${c}</td><td>${p}</td><td>${m.toFixed(2)}%</td><td>${i.total_valor}</td></tr>`;
            });
        } else {
            html += `<tr style="background:#0f172a;color:white;"><th>COD</th><th>PRODUCTO</th><th>SEDE</th><th>STOCK SIS</th><th>CONTEO</th><th>DIFERENCIA</th><th>VALOR DIF.</th></tr></thead><tbody>`;
            d.list.forEach(i => {
                let cv = i.conteo_fisico !== '' ? i.conteo_fisico : '';
                let diff = cv !== '' ? (cv - i.stock) : 0;
                let valDiff = diff * parseFloat(i.costo);
                html += `<tr><td>${i.codigo}</td><td>${i.nombre}</td><td>${i.sede}</td><td>${i.stock}</td><td>${cv}</td><td>${diff}</td><td>${valDiff.toFixed(2)}</td></tr>`;
            });
        }
        html += `</tbody></table></body></html>`;
        const url = URL.createObjectURL(new Blob(['\ufeff', html], {type:'application/vnd.ms-excel'}));
        const a = document.createElement('a'); a.href=url; a.download=`Reporte_${mode}.xls`; a.click(); Swal.close();
    }

    let timer; function debounceSearch() { clearTimeout(timer); timer = setTimeout(() => { state.page = 1; loadData(); }, 300); }
    function navPage(d){ state.page+=d; if(state.page<1)state.page=1; loadData(); }
    function openExportDBF(tipo) { Swal.fire({ title: 'Exportar DBF', html: `Generar archivo <b>${tipo.toUpperCase()}</b> para:<br><br><select id="exp-sede" class="swal2-select" style="width:100%"><option value="HUAURA">Huaura</option><option value="INTEGRA">Integra</option><option value="M.MUNDO">M. Mundo</option></select>`, showCancelButton: true, confirmButtonText: 'Descargar' }).then((r) => { if(r.isConfirmed) { let sede = document.getElementById('exp-sede').value; window.location.href = `api_exportar_dbf.php?sede=${sede}&tipo=${tipo}`; } }); }
    function openImport(){ Swal.fire('Importar', 'Usar panel backend...', 'info'); } // Este es el placeholder antiguo, la funcion real está abajo, la sobreescribo
    async function delItem(id) { Swal.fire({title:'¿Borrar?', icon:'warning', showCancelButton:true, confirmButtonColor:'#d33'}).then(async r=>{ if(r.isConfirmed) { await fetch('admin_inventario_backend.php', {method:'POST', body:JSON.stringify({action:'delete', id})}); loadData(); } }); }
</script>
</body>
</html>