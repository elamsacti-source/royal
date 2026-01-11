<?php
// admin_inventario.php
// V58.0: Frontend Definitivo - Doble Clic en Nombre, Diseño Correcto y Comparativo Manual
include_once 'session.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') { header('Location: login.php'); exit; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventario Maestro</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    /* ESTILOS BASE */
    :root { --primary: #0f172a; --accent: #2563eb; --bg: #f8fafc; --border: #e2e8f0; --text: #334155; --muted: #64748b; --danger: #ef4444; --success: #10b981; }
    body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); margin: 0; display: flex; height: 100vh; overflow: hidden; font-size: 11px; }
    
    .sidebar { width: 220px; background: var(--primary); color: white; display: flex; flex-direction: column; padding: 20px 12px; flex-shrink: 0; }
    .menu-item { color: #94a3b8; padding: 8px 10px; border-radius: 6px; display: flex; gap: 10px; align-items: center; text-decoration: none; margin-bottom: 2px; transition:0.2s; font-size: 0.85rem; }
    .menu-item:hover, .menu-item.active { background: var(--accent); color: white; }
    
    .content { flex: 1; display: flex; flex-direction: column; padding: 12px 16px; overflow: hidden; height: 100vh; }
    .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-shrink: 0; }
    .page-title { font-size: 1.1rem; font-weight: 700; color: var(--primary); margin: 0; }
    
    .btn { height: 28px; padding: 0 12px; border-radius: 6px; cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; font-size: 0.75rem; border: 1px solid var(--border); background: white; color: var(--text); transition:0.2s; }
    .btn:hover { border-color: var(--accent); color: var(--accent); }
    .btn-primary { background: var(--primary); color: white; border-color: var(--primary); }
    .btn-active { background: var(--accent); color: white; border-color: var(--accent); }

    /* FILTROS */
    .filter-box { background: white; padding: 6px 10px; border-radius: 8px; border: 1px solid var(--border); margin-bottom: 8px; display: flex; gap: 8px; align-items: center; flex-wrap: wrap; position: relative; z-index: 50; min-height: 42px; }
    .f-item { display: flex; flex-direction: column; gap: 1px; min-width: 110px; position: relative; }
    .f-lbl { font-size: 0.6rem; font-weight: 700; color: var(--muted); text-transform: uppercase; }
    .f-input { height: 24px; border: 1px solid var(--border); border-radius: 4px; padding: 0 6px; font-size: 0.75rem; width: 100%; box-sizing: border-box; outline: none; }
    .chk-container { display: flex; align-items: center; gap: 6px; font-weight: 600; font-size: 0.75rem; cursor: pointer; user-select: none; }
    .ms-trigger { display: flex; justify-content: space-between; align-items: center; cursor: pointer; background: white; font-size: 0.75rem; }

    /* KPIs */
    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 8px; flex-shrink: 0; position: relative; z-index: 1; }
    .kpi-card { background: white; border: 1px solid var(--border); border-radius: 8px; padding: 8px; display: flex; flex-direction: column; gap: 2px; position: relative; overflow: hidden; }
    .kpi-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 3px; }
    .kc-global::before { background: var(--primary); } .kc-huaura::before { background: #3b82f6; } .kc-integra::before { background: #ec4899; } .kc-mm::before { background: #10b981; }
    .kpi-head { font-size: 0.65rem; font-weight: 800; color: var(--muted); text-transform: uppercase; display: flex; justify-content: space-between; margin-bottom: 2px; }
    .kpi-line { display: flex; justify-content: space-between; align-items: center; font-size: 0.7rem; border-bottom: 1px dashed #f1f5f9; padding-bottom: 1px; }
    .kv-money { color: var(--primary); font-family: monospace; font-weight: 700; }

    .audit-kpi-row { display: none; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 8px; flex-shrink: 0; position: relative; z-index: 1; }
    .kpi-audit-card { background: white; border: 1px solid var(--border); border-radius: 8px; padding: 12px; display: flex; justify-content: space-between; align-items: center; }

    /* TABLA */
    .table-cont { background: white; border: 1px solid var(--border); border-radius: 8px; flex: 1; display: flex; flex-direction: column; overflow: hidden; z-index: 0; }
    .table-scroll { flex: 1; overflow-y: auto; overflow-x: hidden; }
    table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    thead th { background: #f8fafc; position: sticky; top: 0; z-index: 10; padding: 6px 8px; text-align: left; font-size: 0.65rem; font-weight: 700; color: var(--muted); text-transform: uppercase; border-bottom: 1px solid var(--border); cursor: pointer; user-select: none; }
    tbody td { padding: 4px 8px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; font-size: 0.75rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    tbody tr:hover { background: #f8fafc; }
    
    /* CELDA CLICKEABLE SOLO EN NOMBRE */
    .cell-clickable { cursor: pointer; transition: background 0.15s; }
    .cell-clickable:hover { background: #eef2ff; }

    /* DISEÑO CELDA PRODUCTO */
    .prod-cell { display: flex; flex-direction: column; line-height: 1.2; }
    .prod-main { font-weight: 600; color: var(--primary); font-size: 0.8rem; overflow: hidden; text-overflow: ellipsis; display: block; }
    .prod-sub { display: flex; align-items: center; gap: 6px; margin-top: 1px; }
    .badge-code { background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 3px; padding: 0 4px; font-family: monospace; font-weight: 700; color: #475569; font-size: 0.65rem; flex-shrink: 0; }
    .prin-txt { font-size: 0.7rem; color: var(--muted); font-style: italic; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    .st-top { font-weight: 600; font-size: 0.7rem; color: var(--text); display: block; }
    .st-bot { font-size: 0.65rem; color: var(--muted); text-transform: uppercase; display: block; }
    .margen-pos { color: #10b981; font-weight: 700; } .margen-neg { color: #ef4444; font-weight: 700; }
    .btn-icon { width: 22px; height: 22px; border: none; background: transparent; border-radius: 4px; color: var(--muted); cursor: pointer; display: flex; align-items: center; justify-content: center; }
    .btn-icon:hover { background: #f1f5f9; color: var(--accent); } .btn-icon.del:hover { background: #fee2e2; color: var(--danger); }

    .ms-menu { position: absolute; top: 100%; left: 0; width: 100%; min-width: 180px; background: white; border: 1px solid var(--border); border-radius: 6px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); z-index: 100; display: none; padding: 5px; margin-top: 2px; }
    .ms-menu.show { display: block; }
    .ms-list { max-height: 200px; overflow-y: auto; }
    .ms-item { padding: 4px; display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 0.75rem; border-radius: 4px; }
    .ms-item:hover { background: #f1f5f9; }
    
    .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; display: none; justify-content: center; align-items: center; }
    .modal-box { background: white; width: 95%; max-width: 500px; max-height: 80vh; border-radius: 8px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
    
    /* MODAL COMPARATIVO */
    .modal-compare { max-width: 1000px; width: 98%; height: 85vh; }
    .compare-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; height: 100%; padding: 10px; background: #f1f5f9; overflow: hidden; }
    .compare-col { background: white; border-radius: 6px; border: 1px solid #e2e8f0; display: flex; flex-direction: column; overflow: hidden; }
    .col-head { padding: 8px; font-weight: 700; text-align: center; color: white; font-size: 0.75rem; }
    .ch-huaura { background: #3b82f6; } .ch-integra { background: #ec4899; } .ch-mm { background: #10b981; }
    .col-search { padding: 5px; background: #f8fafc; border-bottom: 1px solid #eee; display: flex; gap: 5px; }
    .col-body { flex: 1; overflow-y: auto; padding: 0; }
    .tb-mini { width: 100%; font-size: 0.65rem; border-collapse: collapse; }
    .tb-mini th { background: #f8fafc; padding: 4px; position: sticky; top: 0; }
    .tb-mini td { padding: 3px 6px; border-bottom: 1px solid #f1f5f9; }
    
    #tb-kardex { width: 100%; border-collapse: collapse; font-size: 0.7rem; } #tb-kardex th { background: #f1f5f9; padding: 6px 10px; text-align: left; position: sticky; top: 0; } #tb-kardex td { padding: 4px 10px; border-bottom: 1px solid #f8fafc; }
    .table-footer { padding: 6px 12px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: white; flex-shrink: 0; }
</style>
</head>
<body>

<div class="sidebar">
    <div style="font-weight:800;font-size:1rem;margin-bottom:20px;padding-left:8px;color:white;">ROYAL ADMIN</div>
    <a href="#" class="menu-item active"><i class="ph-bold ph-package"></i> Inventario</a>
    <a href="admin_homologar.php" class="menu-item"><i class="ph-bold ph-arrows-left-right"></i> Homologador</a>
    <a href="intranet.php" class="menu-item" style="margin-top:auto;"><i class="ph-bold ph-sign-out"></i> Salir</a>
</div>

<div class="content">
    <div class="header-bar">
        <h1 class="page-title">Inventario General</h1>
        <div style="display:flex; background:#e2e8f0; padding:2px; border-radius:6px;">
            <button class="btn btn-active" id="tab-inventory" onclick="setMode('inventory')" style="border:none;">Inventario</button>
            <button class="btn" id="tab-audit" onclick="setMode('audit')" style="border:none; background:transparent; color:#64748b;">Resultados / Diferencias</button>
        </div>
        <div style="display:flex; gap:8px;">
            <button class="btn" onclick="openImportModal()"><i class="ph-bold ph-upload-simple"></i> Importar</button>
            <button class="btn btn-primary" onclick="exportarExcel()"><i class="ph-bold ph-file-xls"></i> Exportar</button>
        </div>
    </div>

    <div class="filter-box">
        <div class="f-item"><span class="f-lbl">Tipo</span><select id="sel-tipo" class="f-input" onchange="loadData()"><option value="ALL">Todos</option><option value="PRODUCTO">Productos</option><option value="SERVICIO">Servicios</option></select></div>
        <div class="f-item"><span class="f-lbl">Sede</span><select id="sel-sede" class="f-input" onchange="loadData()"><option value="">Global</option><option value="HUAURA">Huaura</option><option value="INTEGRA">Integra</option><option value="M.MUNDO">M. Mundo</option></select></div>
        <div class="f-item"><span class="f-lbl">Categoría</span><div class="ms-container" id="ms-cat"><div class="f-input ms-trigger" onclick="toggleMs('ms-cat')"><span class="ms-label">Todas</span> <i class="ph-bold ph-caret-down"></i></div><div class="ms-menu"><div style="display:flex;gap:5px;padding-bottom:5px;border-bottom:1px solid #eee;margin-bottom:5px;"><button class="btn" style="height:20px;font-size:0.6rem;" onclick="msAction('ms-cat','all')">Todas</button><button class="btn" style="height:20px;font-size:0.6rem;" onclick="msAction('ms-cat','none')">Ninguna</button></div><div class="ms-list" id="list-cat"></div></div></div></div>
        <div class="f-item"><span class="f-lbl">Laboratorio</span><div class="ms-container" id="ms-lab"><div class="f-input ms-trigger" onclick="toggleMs('ms-lab')"><span class="ms-label">Todas</span> <i class="ph-bold ph-caret-down"></i></div><div class="ms-menu"><div style="display:flex;gap:5px;padding-bottom:5px;border-bottom:1px solid #eee;margin-bottom:5px;"><button class="btn" style="height:20px;font-size:0.6rem;" onclick="msAction('ms-lab','all')">Todas</button><button class="btn" style="height:20px;font-size:0.6rem;" onclick="msAction('ms-lab','none')">Ninguna</button></div><div class="ms-list" id="list-lab"></div></div></div></div>
        <div class="f-item" style="flex:1;"><span class="f-lbl">Buscar</span><input type="text" id="txt-prod" class="f-input" placeholder="Nombre/Código..." onkeyup="debounceLoad()"></div>
        <div class="f-item" style="flex:1;"><span class="f-lbl">Principio</span><input type="text" id="txt-prin" class="f-input" placeholder="Principio..." onkeyup="debounceLoad()"></div>
        
        <div class="f-item" id="filter-diff" style="display:none; justify-content:center; padding-top:12px;">
            <label class="chk-container"><input type="checkbox" id="chk-hide-zero" onchange="loadData()"> Ocultar Stock 0</label>
        </div>
    </div>

    <div id="view-inventory" class="kpi-row">
        <div class="kpi-card kc-global"><div class="kpi-head">GLOBAL</div><div class="kpi-data"><div class="kpi-line"><span class="kl">Costo</span><span class="kv-money" id="kg-costo">0.00</span></div><div class="kpi-line"><span class="kl">Venta</span><span class="kv-money" id="kg-venta">0.00</span></div><div class="kpi-line"><span class="kl">Items</span><span class="kv" id="kg-items">0</span></div><div class="kpi-line"><span class="kl">Stock</span><span class="kv" id="kg-stock">0</span></div></div></div>
        <div class="kpi-card kc-huaura"><div class="kpi-head" style="color:#3b82f6;">HUAURA</div><div class="kpi-data"><div class="kpi-line"><span class="kl">Costo</span><span class="kv-money" id="kh-costo">0.00</span></div><div class="kpi-line"><span class="kl">Venta</span><span class="kv-money" id="kh-venta">0.00</span></div><div class="kpi-line"><span class="kl">Items</span><span class="kv" id="kh-items">0</span></div><div class="kpi-line"><span class="kl">Stock</span><span class="kv" id="kh-stock">0</span></div></div></div>
        <div class="kpi-card kc-integra"><div class="kpi-head" style="color:#ec4899;">INTEGRA</div><div class="kpi-data"><div class="kpi-line"><span class="kl">Costo</span><span class="kv-money" id="ki-costo">0.00</span></div><div class="kpi-line"><span class="kl">Venta</span><span class="kv-money" id="ki-venta">0.00</span></div><div class="kpi-line"><span class="kl">Items</span><span class="kv" id="ki-items">0</span></div><div class="kpi-line"><span class="kl">Stock</span><span class="kv" id="ki-stock">0</span></div></div></div>
        <div class="kpi-card kc-mm"><div class="kpi-head" style="color:#10b981;">M. MUNDO</div><div class="kpi-data"><div class="kpi-line"><span class="kl">Costo</span><span class="kv-money" id="km-costo">0.00</span></div><div class="kpi-line"><span class="kl">Venta</span><span class="kv-money" id="km-venta">0.00</span></div><div class="kpi-line"><span class="kl">Items</span><span class="kv" id="km-items">0</span></div><div class="kpi-line"><span class="kl">Stock</span><span class="kv" id="km-stock">0</span></div></div></div>
    </div>

    <div id="view-audit" class="audit-kpi-row">
        <div class="kpi-audit-card" style="border-left:4px solid #10b981;"><div><div style="font-size:0.65rem;font-weight:700;color:#94a3b8;">SOBRANTE</div><div style="font-size:1.1rem;font-weight:700;color:#10b981;" id="aud-gain">S/ 0.00</div></div><i class="ph-fill ph-trend-up" style="color:#10b981;font-size:1.5rem;"></i></div>
        <div class="kpi-audit-card" style="border-left:4px solid #ef4444;"><div><div style="font-size:0.65rem;font-weight:700;color:#94a3b8;">FALTANTE</div><div style="font-size:1.1rem;font-weight:700;color:#ef4444;" id="aud-loss">S/ 0.00</div></div><i class="ph-fill ph-trend-down" style="color:#ef4444;font-size:1.5rem;"></i></div>
        <div class="kpi-audit-card" style="border-left:4px solid #3b82f6;"><div><div style="font-size:0.65rem;font-weight:700;color:#94a3b8;">BALANCE NETO</div><div style="font-size:1.1rem;font-weight:700;color:#3b82f6;" id="aud-net">S/ 0.00</div></div><i class="ph-fill ph-scales" style="color:#3b82f6;font-size:1.5rem;"></i></div>
    </div>

    <div class="table-cont">
        <div class="table-scroll">
            <table>
                <colgroup id="col-group">
                    </colgroup>
                <thead id="thead-main"></thead>
                <tbody id="tb-body"></tbody>
            </table>
        </div>
        <div class="table-footer">
            <span id="page-info" style="color:var(--muted); font-size:0.7rem;">...</span>
            <div style="display:flex; gap:5px;">
                <button class="btn" style="height:24px;" onclick="navPage(-1)">Ant.</button>
                <button class="btn" style="height:24px;" onclick="navPage(1)">Sig.</button>
            </div>
        </div>
    </div>
</div>

<div id="modal-kardex" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <div style="padding:10px;background:#f8fafc;border-bottom:1px solid #eee;display:flex;justify-content:space-between;">
            <h4 style="margin:0;color:var(--primary);font-size:0.8rem;" id="kardex-title-text">Kardex</h4>
            <button class="btn-icon" onclick="document.getElementById('modal-kardex').style.display='none'"><i class="ph-bold ph-x"></i></button>
        </div>
        <div style="flex:1;overflow-y:auto;padding:0;">
            <table id="tb-kardex"><thead><tr><th>Fecha</th><th>Mov</th><th style="text-align:right">Cant.</th></tr></thead><tbody id="tb-kardex-lines"></tbody></table>
        </div>
    </div>
</div>

<div id="modal-kardex-compare" class="modal-overlay" style="display:none;">
    <div class="modal-box modal-compare">
        <div style="padding:10px;background:#f8fafc;border-bottom:1px solid #eee;display:flex;justify-content:space-between;">
            <h4 style="margin:0;color:var(--primary);font-size:0.8rem;" id="kardex-compare-title">Comparativo Kardex</h4>
            <button class="btn-icon" onclick="document.getElementById('modal-kardex-compare').style.display='none'"><i class="ph-bold ph-x"></i></button>
        </div>
        <div class="compare-grid">
            <div class="compare-col">
                <div class="col-head ch-huaura">HUAURA</div>
                <div class="col-search" id="src-huaura-box"><input type="text" id="in-huaura" class="f-input" placeholder="Buscar..."><button class="btn" onclick="searchKardex('HUAURA')"><i class="ph-bold ph-magnifying-glass"></i></button></div>
                <div class="col-body"><table class="tb-mini"><tbody id="tb-comp-huaura"></tbody></table></div>
            </div>
            <div class="compare-col">
                <div class="col-head ch-integra">INTEGRA</div>
                <div class="col-search" id="src-integra-box"><input type="text" id="in-integra" class="f-input" placeholder="Buscar..."><button class="btn" onclick="searchKardex('INTEGRA')"><i class="ph-bold ph-magnifying-glass"></i></button></div>
                <div class="col-body"><table class="tb-mini"><tbody id="tb-comp-integra"></tbody></table></div>
            </div>
            <div class="compare-col">
                <div class="col-head ch-mm">M. MUNDO</div>
                <div class="col-search" id="src-mm-box"><input type="text" id="in-mm" class="f-input" placeholder="Buscar..."><button class="btn" onclick="searchKardex('M.MUNDO')"><i class="ph-bold ph-magnifying-glass"></i></button></div>
                <div class="col-body"><table class="tb-mini"><tbody id="tb-comp-mm"></tbody></table></div>
            </div>
        </div>
    </div>
</div>

<script>
    let mode = 'inventory'; 
    let state = { page: 1, sort: 'nombre', dir: 'ASC', filters: { cats: 'ALL', labs: 'ALL' }, timer: null };

    document.addEventListener("DOMContentLoaded", () => { loadFilters(); loadData(); document.addEventListener('click', e => { if (!e.target.closest('.ms-container')) document.querySelectorAll('.ms-menu').forEach(d => d.classList.remove('show')); }); });

    function setMode(m) {
        mode = m; state.page = 1;
        document.getElementById('tab-inventory').className = m==='inventory' ? 'btn btn-active' : 'btn';
        document.getElementById('tab-audit').className = m==='audit' ? 'btn btn-active' : 'btn';
        document.getElementById('tab-inventory').style.background = m==='inventory'?'var(--accent)':'transparent';
        document.getElementById('tab-inventory').style.color = m==='inventory'?'white':'#64748b';
        document.getElementById('tab-audit').style.background = m==='audit'?'var(--accent)':'transparent';
        document.getElementById('tab-audit').style.color = m==='audit'?'white':'#64748b';
        document.getElementById('view-inventory').style.display = m==='inventory'?'grid':'none';
        document.getElementById('view-audit').style.display = m==='audit'?'grid':'none';
        document.getElementById('filter-diff').style.display = m==='audit'?'flex':'none';
        loadData();
    }

    function sortBy(col) { if(state.sort === col) state.dir = state.dir === 'ASC' ? 'DESC' : 'ASC'; else { state.sort = col; state.dir = 'ASC'; } loadData(); }
    async function loadFilters() { try { const res = await fetch('admin_inventario_backend.php?action=get_filters'); const d = await res.json(); renderMultiSelect('list-cat', d.lineas, 'cats'); renderMultiSelect('list-lab', d.marcas, 'labs'); } catch(e) {} }
    function renderMultiSelect(divId, items, key) { const div=document.getElementById(divId); div.innerHTML=''; items.forEach(i=>{if(!i)return;div.innerHTML+=`<div class="ms-item"><input type="checkbox" value="${i}" checked onchange="updateMs('${key}','${divId}')"> <span>${i}</span></div>`}); updateMs(key, divId); }
    function toggleMs(id) { document.querySelector(`#${id} .ms-menu`).classList.toggle('show'); }
    function updateMs(key, divId) { const sel=[]; document.querySelectorAll(`#${divId} input`).forEach(c=>{if(c.checked)sel.push(c.value)}); state.filters[key]=(sel.length===document.querySelectorAll(`#${divId} input`).length)?'ALL':sel; document.querySelector(`#${divId}`).parentElement.parentElement.querySelector('.ms-label').innerText=(state.filters[key]==='ALL')?'Todas':(sel.length===0?'Ninguna':`${sel.length} selec.`); loadData(); }
    function msAction(p,a) { const d=document.querySelector(`#${p} .ms-list`); d.querySelectorAll('input').forEach(c=>c.checked=(a==='all')); updateMs(p==='ms-cat'?'cats':'labs', d.id); }

    function getParams() {
        const c = Array.isArray(state.filters.cats) ? state.filters.cats.join(',') : 'ALL';
        const l = Array.isArray(state.filters.labs) ? state.filters.labs.join(',') : 'ALL';
        const hideZero = document.getElementById('chk-hide-zero').checked;
        return new URLSearchParams({ q_prod: document.getElementById('txt-prod').value, q_prin: document.getElementById('txt-prin').value, tipo: document.getElementById('sel-tipo').value, lineas: c, marcas: l, sedes: document.getElementById('sel-sede').value, page: state.page, sort: state.sort, dir: state.dir, mode: mode, hide_zero: hideZero });
    }
    function exportarExcel() { window.location.href = `admin_inventario_backend.php?${getParams().toString()}&action=export`; }

    async function loadData() {
        document.getElementById('tb-body').innerHTML = '<tr><td colspan="9" style="text-align:center;padding:20px;">Cargando...</td></tr>';
        
        const thead = document.getElementById('thead-main');
        const colgroup = document.getElementById('col-group');
        
        if(mode === 'inventory') {
            colgroup.innerHTML = `<col style="width:30%"><col style="width:8%"><col style="width:15%"><col style="width:8%"><col style="width:8%"><col style="width:8%"><col style="width:8%"><col style="width:8%"><col style="width:7%">`;
            thead.innerHTML = `<tr><th onclick="sortBy('nombre')">Producto</th><th onclick="sortBy('sede')">Sede</th><th>Clasificación</th><th onclick="sortBy('stock')" style="text-align:right">Stock</th><th onclick="sortBy('costo')" style="text-align:right">Costo</th><th onclick="sortBy('total')" style="text-align:right">Total</th><th onclick="sortBy('venta')" style="text-align:right">P.Venta</th><th onclick="sortBy('margen')" style="text-align:right">Margen%</th><th style="text-align:center;">Acción</th></tr>`;
        } else {
            colgroup.innerHTML = `<col style="width:35%"><col style="width:10%"><col style="width:10%"><col style="width:15%"><col style="width:10%"><col style="width:10%"><col style="width:10%">`;
            thead.innerHTML = `<tr><th onclick="sortBy('nombre')">Producto</th><th onclick="sortBy('sede')">Sede</th><th style="text-align:right">Stock Sis</th><th style="text-align:right">Conteo Físico</th><th style="text-align:right">Diferencia</th><th style="text-align:right">Valor Diff</th><th style="text-align:center;">Acción</th></tr>`;
        }

        try {
            const res = await fetch(`admin_inventario_backend.php?${getParams().toString()}`);
            const d = await res.json();
            const fmt = n => 'S/ ' + parseFloat(n||0).toLocaleString('es-PE', {minimumFractionDigits: 2});
            const num = n => parseInt(n||0).toLocaleString();
            
            if(mode === 'inventory' && d.card_general) {
                const g = d.card_general;
                document.getElementById(`kg-costo`).innerText = fmt(g.v_costo); document.getElementById(`kg-venta`).innerText = fmt(g.v_venta);
                document.getElementById(`kg-items`).innerText = num(g.items); document.getElementById(`kg-stock`).innerText = num(g.v_stock);
                const mapSedes = {'HUAURA':'h','INTEGRA':'i','M.MUNDO':'m'};
                if(d.cards_sedes) for(const [n,v] of Object.entries(d.cards_sedes)) { const k=mapSedes[n]; if(k){ document.getElementById(`k${k}-costo`).innerText=fmt(v.costo); document.getElementById(`k${k}-venta`).innerText=fmt(v.venta); document.getElementById(`k${k}-items`).innerText=num(v.items); document.getElementById(`k${k}-stock`).innerText=num(v.stock); }}
            } else if (mode === 'audit' && d.audit_kpis) {
                const a = d.audit_kpis; document.getElementById('aud-gain').innerText = fmt(a.gain); document.getElementById('aud-loss').innerText = fmt(a.loss); document.getElementById('aud-net').innerText = fmt(a.net);
            }

            const tb = document.getElementById('tb-body'); tb.innerHTML = '';
            if(!d.list || d.list.length === 0) { tb.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:20px;">Sin datos</td></tr>'; }
            else {
                d.list.forEach(i => {
                    // CELL CLICKABLE CON ONDBLCLICK
                    let prodHtml = `
                    <div class="prod-cell cell-clickable" ondblclick="irAHomologar('${i.codigo}')">
                        <span class="prod-main">${i.nombre}</span>
                        <div class="prod-sub">
                            <span class="badge-code">${i.codigo}</span>
                            <span class="prin-txt">${i.principio||''}</span>
                        </div>
                    </div>`;
                    
                    if(mode === 'inventory') {
                        let m = parseFloat(i.margen_pct);
                        let mClass = m > 0 ? 'margen-pos' : (m < 0 ? 'margen-neg' : '');
                        tb.innerHTML += `<tr><td>${prodHtml}</td><td>${i.sede_ui}</td><td><span class="st-top">${i.linea}</span><span class="st-bot">${i.marca||'-'}</span></td><td style="text-align:right;font-weight:700;">${num(i.stock)}</td><td style="text-align:right;">${parseFloat(i.costo).toFixed(2)}</td><td style="text-align:right;font-weight:700;color:var(--primary);">${parseFloat(i.total_valor).toFixed(2)}</td><td style="text-align:right;">${parseFloat(i.precio).toFixed(2)}</td><td style="text-align:right;"><span class="${mClass}">${m.toFixed(1)}%</span></td><td><div style="display:flex;justify-content:center;gap:5px;"><button class="btn-icon" onclick="event.stopPropagation(); openKardex('${i.codigo}','${i.sede_ui}')"><i class="ph-bold ph-list-dashes"></i></button><button class="btn-icon del" onclick="event.stopPropagation(); deleteItem(${i.id})"><i class="ph-bold ph-trash"></i></button></div></td></tr>`;
                    } else {
                        let conteo = i.conteo !== null ? i.conteo : 0; let diff = i.diff_cant; let colDiff = diff < 0 ? '#ef4444' : (diff > 0 ? '#10b981' : '#94a3b8');
                        tb.innerHTML += `<tr><td>${prodHtml}</td><td>${i.sede_ui}</td><td style="text-align:right;">${num(i.stock)}</td><td style="text-align:right;"><input type="number" value="${conteo}" onchange="saveCount(${i.id}, this.value)" onclick="event.stopPropagation()" style="width:60px;text-align:right;border:1px solid #ccc;border-radius:4px;"></td><td style="text-align:right;font-weight:700;color:${colDiff}">${diff > 0 ? '+'+diff : diff}</td><td style="text-align:right;color:${colDiff}">${fmt(i.diff_money)}</td><td style="text-align:center;"><button class="btn-icon" onclick="event.stopPropagation(); openKardexCompare('${i.codigo}', '${i.sede_ui}')"><i class="ph-bold ph-magnifying-glass"></i></button></td></tr>`;
                    }
                });
            }
            document.getElementById('page-info').innerText = `Pág. ${d.pagination.current_page} de ${d.pagination.total_pages}`;
        } catch(e) {}
    }

    function saveCount(id, val) { fetch('admin_inventario_backend.php', { method: 'POST', body: JSON.stringify({action:'update_count', id:id, val:val}) }).then(()=>loadData()); }
    function irAHomologar(codigo) { window.location.href = `admin_homologar.php?search_code=${codigo}`; }

    function openKardex(c,sUI) {
        document.getElementById('modal-kardex').style.display='flex';
        document.getElementById('kardex-title-text').innerText = c;
        document.getElementById('tb-kardex-lines').innerHTML = '<tr><td colspan="3">Cargando...</td></tr>';
        const mapa = {'INTEGRA':'HUACHO','M.MUNDO':'MEDIO MUNDO','HUAURA':'HUAURA'};
        fetch(`admin_inventario_backend.php?action=get_kardex&codigo=${c}&sede=${mapa[sUI]||sUI}`).then(r=>r.json()).then(d=>{
            document.getElementById('kardex-title-text').innerText = d.producto ? d.producto.nombre : c;
            const tb = document.getElementById('tb-kardex-lines'); tb.innerHTML='';
            if(!d.movimientos.length) tb.innerHTML='<tr><td colspan="3" style="text-align:center;padding:10px;">Sin datos</td></tr>';
            else d.movimientos.forEach(m=>{ tb.innerHTML+=`<tr><td style="color:#64748b;">${m.fecha}</td><td>${m.tipo_nombre}</td><td style="text-align:right;font-weight:700;color:${m.color}">${m.cant}</td></tr>`; });
        });
    }

    function openKardexCompare(codigo, sedeOrigen) {
        document.getElementById('modal-kardex-compare').style.display='flex';
        document.getElementById('kardex-compare-title').innerText = 'Comparativo: ' + codigo;
        ['huaura','integra','mm'].forEach(k => document.getElementById(`tb-comp-${k}`).innerHTML = '');
        
        const mapSedes = {'HUAURA':'huaura', 'INTEGRA':'integra', 'M.MUNDO':'mm'};
        const keyOrigen = mapSedes[sedeOrigen];

        ['huaura','integra','mm'].forEach(k => { document.getElementById(`src-${k}-box`).style.display = (k === keyOrigen) ? 'none' : 'flex'; });
        
        loadColumnKardex(keyOrigen, codigo, true);
    }

    function loadColumnKardex(key, codigo, updateTitle=false) {
        const tb = document.getElementById(`tb-comp-${key}`);
        tb.innerHTML = '<tr><td>Cargando...</td></tr>';
        
        const mapKeys = {'huaura':'HUAURA', 'integra':'INTEGRA', 'mm':'M.MUNDO'};
        const sedeEnvio = mapKeys[key];

        fetch(`admin_inventario_backend.php?action=get_kardex&codigo=${codigo}&sede=${sedeEnvio}`).then(r=>r.json()).then(d=>{
            if(updateTitle && d.producto) document.getElementById('kardex-compare-title').innerText = `${d.producto.nombre} (${codigo})`;
            tb.innerHTML = '';
            if(!d.movimientos || !d.movimientos.length) tb.innerHTML = '<tr><td colspan="3" style="text-align:center;color:#999;">Sin datos</td></tr>';
            else d.movimientos.forEach(m => tb.innerHTML += `<tr><td>${m.fecha.substring(0,10)}</td><td style="font-size:0.6rem;">${m.tipo_nombre}</td><td style="text-align:right;font-weight:700;color:${m.color}">${m.cant}</td></tr>`);
        });
    }

    function searchKardex(sedeUI) {
        const mapUI = {'HUAURA':'huaura', 'INTEGRA':'integra', 'M.MUNDO':'mm'};
        const key = mapUI[sedeUI];
        const term = document.getElementById(`in-${key}`).value;
        if(!term) return;
        
        fetch(`admin_inventario_backend.php?action=search_mini&sede=${sedeUI}&term=${term}`).then(r=>r.json()).then(d=>{
            if(d.length > 0) { loadColumnKardex(key, d[0].codigo); document.getElementById(`in-${key}`).value = d[0].nombre; }
            else Swal.fire('No encontrado', 'No se encontraron productos', 'info');
        });
    }

    function deleteItem(id) { Swal.fire({title:'¿Borrar?', text:'Irreversible', showCancelButton:true, confirmButtonText:'Sí'}).then(r=>{if(r.isConfirmed) fetch('admin_inventario_backend.php', {method:'POST', body:JSON.stringify({action:'delete',id:id})}).then(()=>loadData());}); }
    function openImportModal() {
        Swal.fire({
            title: 'Importador',
            html: `<div style="display:flex;flex-direction:column;gap:10px;text-align:left;"><select id="swal-sede" class="swal2-select" style="margin:0;width:100%;font-size:0.8rem;"><option value="HUAURA">Huaura</option><option value="INTEGRA">Integra</option><option value="M.MUNDO">M. Mundo</option></select><select id="swal-tipo" class="swal2-select" style="margin:0;width:100%;font-size:0.8rem;"><option value="inventario">Inventario</option><option value="kardex">Kardex (Chunks)</option><option value="principios">Principios</option><option value="lineas">Cat</option><option value="marcas">Lab</option></select><input type="file" id="swal-file" class="swal2-file" style="width:100%;font-size:0.8rem;"></div>`,
            showCancelButton: true, confirmButtonText: 'Cargar',
            preConfirm: () => {
                const t=document.getElementById('swal-tipo').value, f=document.getElementById('swal-file').files[0], s=document.getElementById('swal-sede').value;
                if(!f) return Swal.showValidationMessage('Archivo?');
                if(t==='kardex') return uploadKardexChunks(f,s);
                const fd=new FormData(); fd.append('dbf_file',f); fd.append('sede_destino',s); fd.append('tipo_archivo',t);
                return fetch('api_importar_zeth.php', {method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(!d.success)throw new Error(d.message);return d.message;}).catch(e=>Swal.showValidationMessage(e));
            }
        }).then(r=>{if(r.value)Swal.fire('Ok',r.value,'success').then(()=>loadData());});
    }
    async function uploadKardexChunks(file, sede) {
        const CHUNK = 1024*1024; const tot = Math.ceil(file.size/CHUNK);
        for(let i=0; i<tot; i++) {
            const fd = new FormData(); fd.append('file_chunk', file.slice(i*CHUNK, (i+1)*CHUNK)); fd.append('chunk_index', i); fd.append('is_last', i===tot-1);
            try { await fetch('admin_importar_kardex.php', {method:'POST', body:fd}); } catch(e) { Swal.showValidationMessage('Error chunk '+i); throw e; }
        }
        const fd = new FormData(); fd.append('action', 'importar'); fd.append('sede', sede);
        return fetch('admin_importar_kardex.php', {method:'POST', body:fd}).then(r=>r.json()).then(d=>{if(!d.success)throw new Error(d.message);return d.message;}).catch(e=>Swal.showValidationMessage(e));
    }
    function debounceLoad() { clearTimeout(state.timer); state.timer=setTimeout(()=>{state.page=1; loadData()}, 300); }
    function navPage(d){ state.page+=d; if(state.page<1)state.page=1; loadData(); }
</script>
</body>
</html>