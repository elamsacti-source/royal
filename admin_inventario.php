<?php
// admin_inventario.php
// V10.9: Fila ITEMS agregada a Cards Sede + Altura ajustada.
include_once 'session.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') { header('Location: login.php'); exit; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventario Maestro</title>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    :root {
        --primary: #0f172a; --primary-light: #1e293b; --accent: #2563eb;
        --bg-body: #f1f5f9; --surface: #ffffff; --border: #e2e8f0;
        --text-main: #334155; --text-dark: #0f172a; --text-muted: #64748b;
        --success: #10b981; --danger: #ef4444;
        --radius: 8px;
    }

    body { font-family: 'Manrope', sans-serif; background: var(--bg-body); color: var(--text-main); margin: 0; display: flex; height: 100vh; overflow: hidden; font-size: 13px; }
    
    .sidebar { width: 240px; background: var(--primary); color: white; padding: 24px 16px; display: flex; flex-direction: column; flex-shrink: 0; z-index: 20; }
    .sidebar-header { font-weight: 700; font-size: 1rem; margin-bottom: 30px; display: flex; align-items: center; gap: 10px; color: #fff; padding-left: 8px; }
    .menu-item { text-decoration: none; color: #94a3b8; padding: 9px 10px; border-radius: 6px; font-weight: 600; display: block; margin-bottom: 2px; transition:0.2s; }
    .menu-item:hover { background: rgba(255,255,255,0.05); color: white; }
    .menu-item.active { background: var(--accent); color: white; }
    
    .content { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
    .top-bar { background: var(--surface); padding: 8px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; height: 50px; box-sizing: border-box; }
    .actions-group { display: flex; gap: 10px; align-items: center; }
    
    .btn { padding: 6px 12px; border-radius: 6px; border: 1px solid transparent; cursor: pointer; font-weight: 700; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
    .btn:hover { transform: translateY(-1px); }
    .btn-primary { background: var(--accent); color: white; border-color: var(--accent); }
    .btn-white { background: white; border-color: var(--border); color: var(--text-main); } 

    .dropdown { position: relative; display: inline-block; }
    .dropdown-content { display: none; position: absolute; right: 0; top: 120%; background-color: #fff; min-width: 200px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border: 1px solid var(--border); border-radius: 8px; z-index: 100; padding: 6px 0; }
    .dropdown:hover .dropdown-content { display: block; }
    .dropdown-header { font-size: 0.7rem; font-weight: 700; color: var(--text-muted); padding: 8px 16px; text-transform: uppercase; background: #f8fafc; border-bottom: 1px solid var(--border); margin-bottom: 4px; }
    .dropdown-item { color: var(--text-main); padding: 10px 16px; text-decoration: none; display: flex; align-items: center; gap: 10px; font-size: 0.85rem; cursor: pointer; transition: 0.2s; }
    .dropdown-item:hover { background-color: #eff6ff; color: var(--accent); }

    .scroll-area { flex: 1; overflow-y: auto; padding: 15px 20px; background: #f8fafc; }

    /* FILTROS */
    .filter-section { background:white; padding:12px; border-radius:8px; border:1px solid var(--border); margin-bottom:15px; display:flex; flex-direction:column; gap:10px; }
    .filter-row { display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; }
    .filter-col { display: flex; flex-direction: column; gap: 4px; }
    .filter-lbl { font-size: 0.65rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; }
    
    .chip { border: 1px solid var(--border); background: white; padding: 4px 10px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); cursor: pointer; }
    .chip.active { background: var(--primary); color: white; border-color: var(--primary); }
    
    .multiselect { position: relative; }
    .ms-btn { padding: 5px 10px; border: 1px solid var(--border); border-radius: 4px; background: white; cursor: pointer; min-width: 160px; text-align: left; display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; color:var(--text-main); height: 28px; }
    .ms-dropdown { display: none; position: absolute; top: 100%; left: 0; width: 250px; max-height: 250px; overflow-y: auto; background: white; border: 1px solid var(--border); border-radius: 6px; box-shadow: 0 10px 20px rgba(0,0,0,0.1); z-index: 100; padding: 8px; margin-top: 2px; }
    .ms-search { width: 100%; padding: 6px; border: 1px solid var(--border); border-radius: 4px; margin-bottom: 6px; font-size: 0.75rem; box-sizing: border-box; }
    .ms-item { display: flex; align-items: center; gap: 6px; font-size: 0.75rem; cursor: pointer; padding: 4px; }
    .ms-item:hover { background: #f1f5f9; }

    .btn-search { padding: 6px 12px; border-radius: 20px; border: 1px solid var(--border); width: 180px; font-size: 0.8rem; }

    /* DASHBOARD STRIP */
    .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 15px; }
    
    .kpi-card { 
        background: white; border: 1px solid var(--border); border-radius: 8px; padding: 15px; 
        display: flex; flex-direction: column; justify-content: space-between; 
        /* AUMENTADO A 155PX PARA QUE QUEPA ITEMS SIN CORTARSE */
        height: 155px; 
        box-shadow: 0 1px 2px rgba(0,0,0,0.03); position: relative; overflow: hidden;
    }
    
    .kpi-card.global { background: #1e293b; color: white; border: none; }
    .kpi-card.global .kpi-lbl { color: #94a3b8; }
    .kpi-card.global .kv-val { color: white; }
    
    /* Detalle Stock */
    .stock-detail { 
        font-size: 0.65rem; color: #94a3b8; margin-top: 2px; 
        display: flex; gap: 6px; justify-content: flex-end; font-family: monospace; opacity: 0.8;
    }
    
    .kpi-card.sede::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 3px; }
    .huaura::before { background: #3b82f6; } .huaura .kv-val.cost { color: #3b82f6; }
    .integra::before { background: #ec4899; } .integra .kv-val.cost { color: #ec4899; }
    .mm::before { background: #10b981; } .mm .kv-val.cost { color: #10b981; }
    .kpi-card.inactive { opacity: 0.5; filter: grayscale(1); }

    .kpi-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    .kpi-lbl { font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); }
    
    .kpi-body { display: flex; flex-direction: column; gap: 6px; } /* Mas espacio entre filas */
    .kv-row { display: flex; justify-content: space-between; align-items: baseline; font-size: 0.75rem; }
    .kv-lbl { font-weight: 600; color: var(--text-muted); font-size: 0.7rem; }
    .kv-val { font-weight: 800; font-size: 0.95rem; }

    /* TABLA */
    .table-container { background: white; border: 1px solid var(--border); border-radius: 8px; overflow: hidden; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #f8fafc; text-align: left; padding: 10px 12px; font-size: 0.7rem; color: var(--text-muted); font-weight: 800; text-transform: uppercase; cursor: pointer; border-bottom: 1px solid var(--border); }
    td { padding: 6px 12px; border-bottom: 1px solid #f1f5f9; font-size: 0.8rem; vertical-align: middle; color: var(--text-main); height: 32px; }
    tr:hover td { background: #f8fafc; }

    .num { text-align: right; font-family: 'Inter', monospace; font-weight: 700; }
    .prod-main { font-weight: 700; color: var(--primary); display:block; line-height:1.1; margin-bottom:1px; }
    .prod-sub { font-size: 0.7rem; color: #64748b; font-family: monospace; }
    .tag-sede { font-size: 0.65rem; font-weight: 700; padding: 2px 6px; border-radius: 4px; background: #e2e8f0; color: var(--text-muted); text-transform: uppercase; }
    .btn-del { border: none; background: transparent; color: #cbd5e1; cursor: pointer; padding: 6px; border-radius: 6px; transition:0.2s; }
    .btn-del:hover { background: #fee2e2; color: #ef4444; }
    .pagination { padding: 8px 15px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #fff; font-size: 0.75rem; }
</style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header"><i class="ph-fill ph-first-aid-kit" style="color:var(--accent);"></i> ADMIN PANEL</div>
    <a href="#" class="menu-item active">Inventario</a>
    <a href="admin_homologar.php" class="menu-item">Homologador</a>
    <a href="intranet.php" class="menu-item">Salir</a>
</div>

<div class="content">
    <div class="top-bar">
        <div style="font-weight:800; font-size:1rem; color:var(--primary);">Inventario Maestro</div>
        <div class="actions-group">
            <div class="dropdown">
                <button class="btn btn-white"><i class="ph-bold ph-export"></i> Exportar Datos <i class="ph-bold ph-caret-down"></i></button>
                <div class="dropdown-content">
                    <div class="dropdown-header">Reportes</div>
                    <a class="dropdown-item" onclick="exportToExcel()"><i class="ph-fill ph-microsoft-excel-logo" style="color:#10b981;"></i> Excel Completo</a>
                    <div class="dropdown-header">Base de Datos (DBF)</div>
                    <a class="dropdown-item" onclick="openExportDBF('inventario')"><i class="ph-bold ph-database"></i> Inv. ZETH70</a>
                    <a class="dropdown-item" onclick="openExportDBF('principios')"><i class="ph-bold ph-database"></i> Prin. ZETH19</a>
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
                    <span class="filter-lbl">Línea(Categoría)</span>
                    <div class="multiselect" style="position:relative;">
                        <div class="ms-btn" onclick="toggleMs('cat')"><span id="ms-text-cat">Todas</span> <i class="ph-bold ph-caret-down"></i></div>
                        <div class="ms-dropdown" id="ms-drop-cat"><input type="text" class="ms-search" placeholder="Buscar..." onkeyup="filterMs('cat',this.value)"><div class="ms-list" id="ms-list-cat"></div></div>
                    </div>
                </div>
                <div class="filter-col">
                    <span class="filter-lbl">Laboratorio(SUb Categoria)</span>
                    <div class="multiselect" style="position:relative;">
                        <div class="ms-btn" onclick="toggleMs('mar')"><span id="ms-text-mar">Todas</span> <i class="ph-bold ph-caret-down"></i></div>
                        <div class="ms-dropdown" id="ms-drop-mar"><input type="text" class="ms-search" placeholder="Buscar..." onkeyup="filterMs('mar',this.value)"><div class="ms-list" id="ms-list-mar"></div></div>
                    </div>
                </div>
            </div>
            <div class="filter-row" style="justify-content: space-between; border-top: 1px solid #f1f5f9; padding-top: 10px;">
                <div style="display:flex; gap:10px;">
                    <input type="text" id="txt-prod" class="btn-search" placeholder="Producto/Código..." onkeyup="debounceSearch()">
                    <input type="text" id="txt-prin" class="btn-search" placeholder="Principio Activo..." onkeyup="debounceSearch()">
                </div>
                <div style="font-size:0.75rem; color:var(--text-muted); font-weight:700;" id="page-info">Cargando...</div>
            </div>
        </div>

        <div class="kpi-row">
            <div class="kpi-card global">
                <div class="kpi-head"><span class="kpi-lbl" style="color:white;">TOTAL GLOBAL</span> <i class="ph-bold ph-globe"></i></div>
                <div class="kpi-body">
                    <div class="kv-row"><span class="kv-lbl">Ul.Cost</span> <span class="kv-val" id="gen-costo">0.00</span></div>
                    <div class="kv-row"><span class="kv-lbl">P.V.P</span> <span class="kv-val" id="gen-venta">0.00</span></div>
                    
                    <div style="margin-top:2px;">
                        <div class="kv-row"><span class="kv-lbl">STOCK</span> <span class="kv-val" id="gen-stock" style="color:#4ade80">0</span></div>
                        <div id="gen-stock-detail" class="stock-detail"></div>
                    </div>
                    
                    <div class="kv-row"><span class="kv-lbl">ITEMS</span> <span class="kv-val" id="gen-items">0</span></div>
                </div>
            </div>
            
            <div id="card-huaura" class="kpi-card sede huaura">
                <div class="kpi-head"><span class="kpi-lbl">HUAURA</span></div>
                <div class="kpi-body">
                    <div class="kv-row"><span class="kv-lbl">Ul.Cost</span> <span class="kv-val cost" id="vc-huaura">0.00</span></div>
                    <div class="kv-row"><span class="kv-lbl">P.V.P</span> <span class="kv-val" id="vv-huaura">0.00</span></div>
                    <div class="kv-row"><span class="kv-lbl">STOCK</span> <span class="kv-val" id="sk-huaura">0</span></div>
                    <div class="kv-row"><span class="kv-lbl">ITEMS</span> <span class="kv-val" id="it-huaura">0</span></div>
                </div>
            </div>

            <div id="card-integra" class="kpi-card sede integra">
                <div class="kpi-head"><span class="kpi-lbl">INTEGRA</span></div>
                <div class="kpi-body">
                    <div class="kv-row"><span class="kv-lbl">Ul.Cost</span> <span class="kv-val cost" id="vc-integra">0.00</span></div>
                    <div class="kv-row"><span class="kv-lbl">P.V.P</span> <span class="kv-val" id="vv-integra">0.00</span></div>
                    <div class="kv-row"><span class="kv-lbl">STOCK</span> <span class="kv-val" id="sk-integra">0</span></div>
                    <div class="kv-row"><span class="kv-lbl">ITEMS</span> <span class="kv-val" id="it-integra">0</span></div>
                </div>
            </div>

            <div id="card-mm" class="kpi-card sede mm">
                <div class="kpi-head"><span class="kpi-lbl">M.MUNDO</span></div>
                <div class="kpi-body">
                    <div class="kv-row"><span class="kv-lbl">Ul.Cost</span> <span class="kv-val cost" id="vc-mm">0.00</span></div>
                    <div class="kv-row"><span class="kv-lbl">P.V.P</span> <span class="kv-val" id="vv-mm">0.00</span></div>
                    <div class="kv-row"><span class="kv-lbl">STOCK</span> <span class="kv-val" id="sk-mm">0</span></div>
                    <div class="kv-row"><span class="kv-lbl">ITEMS</span> <span class="kv-val" id="it-mm">0</span></div>
                </div>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th onclick="sort('nombre')" style="width:30%">Producto / Detalle</th>
                        <th onclick="sort('sede')" style="width:8%;text-align:center;">Sede</th>
                        <th onclick="sort('linea')" style="width:15%">Categoría / Marca</th>
                        <th onclick="sort('stock')" class="num">Stock</th>
                        <th onclick="sort('costo')" class="num">Ul. Cost</th>
                        <th onclick="sort('total')" class="num">Importe</th>
                        <th onclick="sort('precio')" class="num">P.V.P</th>
                        <th onclick="sort('margen')" class="num">M.B %</th>
                        <th style="width:5%"></th>
                    </tr>
                </thead>
                <tbody id="tb-inv"></tbody>
            </table>
            <div class="pagination">
                <button onclick="navPage(-1)" class="btn-white">Ant</button>
                <button onclick="navPage(1)" class="btn-white">Sig</button>
            </div>
        </div>
    </div>
</div>

<script>
    let filters = { sedes: ['HUAURA', 'INTEGRA', 'M.MUNDO'], groups: ['PRODUCTOS', 'SERVICIOS'], lineas: [], marcas: [] };
    let state = { q_prod:'', q_prin:'', page:1, sort:'nombre', dir:'ASC' };

    document.addEventListener("DOMContentLoaded", () => { loadFilters(); loadData(); document.addEventListener('click', e => { if(!e.target.closest('.multiselect')) { document.getElementById('ms-drop-cat').style.display='none'; document.getElementById('ms-drop-mar').style.display='none'; } }); });

    async function loadFilters() {
        try {
            const res = await fetch('admin_inventario_backend.php?action=get_filters');
            const d = await res.json();
            const lCat = document.getElementById('ms-list-cat'); d.lineas.forEach(l => lCat.innerHTML += `<label class="ms-item"><input type="checkbox" value="${l}" onchange="toggleList(this, 'lineas')"> <span>${l}</span></label>`);
            const lMar = document.getElementById('ms-list-mar'); d.marcas.forEach(m => lMar.innerHTML += `<label class="ms-item"><input type="checkbox" value="${m}" onchange="toggleList(this, 'marcas')"> <span>${m}</span></label>`);
        } catch(e) {}
    }

    function toggleSede(btn, val) {
        btn.classList.toggle('active'); const idx = filters.sedes.indexOf(val);
        if (idx > -1) filters.sedes.splice(idx, 1); else filters.sedes.push(val);
        let uiKey = 'huaura'; if(val==='INTEGRA') uiKey='integra'; if(val==='M.MUNDO') uiKey='mm';
        let card = document.getElementById('card-'+uiKey);
        if(filters.sedes.includes(val)) card.classList.remove('inactive'); else card.classList.add('inactive');
        state.page = 1; loadData();
    }
    function toggleGroup(btn, val) { btn.classList.toggle('active'); const idx = filters.groups.indexOf(val); if(idx>-1) filters.groups.splice(idx,1); else filters.groups.push(val); state.page=1; loadData(); }
    function toggleMs(type) { const drop=document.getElementById('ms-drop-'+type); const other=type==='cat'?'mar':'cat'; document.getElementById('ms-drop-'+other).style.display='none'; drop.style.display=(drop.style.display==='block')?'none':'block'; }
    function filterMs(type, txt) { txt=txt.toLowerCase(); document.getElementById('ms-list-'+type).querySelectorAll('.ms-item').forEach(el=>{ el.style.display=el.innerText.toLowerCase().includes(txt)?'flex':'none'; }); }
    function toggleList(chk, key) { const val=chk.value; if(chk.checked) filters[key].push(val); else filters[key]=filters[key].filter(x=>x!==val); 
        document.getElementById('ms-text-'+(key==='lineas'?'cat':'mar')).innerText = filters[key].length===0?'Todas':`${filters[key].length} selec.`; state.page=1; loadData(); }

    async function loadData() {
        const p = new URLSearchParams(state); p.append('sedes', filters.sedes.join(',')); p.append('groups', filters.groups.join(',')); p.append('lineas', filters.lineas.join(',')); p.append('marcas', filters.marcas.join(','));
        try {
            const res = await fetch(`admin_inventario_backend.php?${p}`);
            const d = await res.json();
            const fmt = n => 'S/ ' + parseFloat(n||0).toLocaleString('en-US', {minimumFractionDigits: 2});
            const num = n => parseFloat(n||0).toLocaleString('en-US');

            document.getElementById('gen-costo').innerText = fmt(d.card_general.costo);
            document.getElementById('gen-venta').innerText = fmt(d.card_general.venta);
            document.getElementById('gen-stock').innerText = num(d.card_general.stock);
            document.getElementById('gen-items').innerText = num(d.card_general.items);

            let stockHtml = '';
            ['HUAURA','INTEGRA','M.MUNDO'].forEach(s => {
                if(d.cards_sedes[s]) {
                    let short = s.substring(0,2); if(s==='INTEGRA') short='IN'; if(s==='M.MUNDO') short='MM';
                    let stVal = (d.cards_sedes[s].stock || 0).toLocaleString('en-US');
                    stockHtml += `<span>${short}:${stVal}</span> `;
                }
            });
            document.getElementById('gen-stock-detail').innerHTML = stockHtml;

            ['HUAURA','INTEGRA','M.MUNDO'].forEach(s => {
                let k = 'huaura'; if(s==='INTEGRA') k='integra'; if(s==='M.MUNDO') k='mm';
                if(d.cards_sedes[s]) {
                    document.getElementById(`vc-${k}`).innerText = fmt(d.cards_sedes[s].costo);
                    document.getElementById(`vv-${k}`).innerText = fmt(d.cards_sedes[s].venta);
                    document.getElementById(`sk-${k}`).innerText = num(d.cards_sedes[s].stock);
                    // NUEVO: Items
                    document.getElementById(`it-${k}`).innerText = num(d.cards_sedes[s].items);
                }
            });

            const tb = document.getElementById('tb-inv'); tb.innerHTML = '';
            if(d.list.length === 0) tb.innerHTML = '<tr><td colspan="9" style="text-align:center; padding:30px; color:#94a3b8;">Sin datos</td></tr>';
            else {
                d.list.forEach(i => {
                    let p=parseFloat(i.precio), c=parseFloat(i.costo), m=(p>0?((p-c)/p)*100:0);
                    let colorM = '#64748b'; if(m < 0) colorM = '#ef4444'; else if(m > 20) colorM = '#10b981';
                    let sedeUI = i.sede==='M.MUNDO'?'M.Mundo':(i.sede==='INTEGRA'?'Integra':i.sede);

                    tb.innerHTML += `<tr>
                        <td>
                            <span class="prod-main">${i.nombre}</span>
                            <div class="prod-sub"><span style="color:#10b981;">${i.principio||''}</span> ${i.codigo}</div>
                        </td>
                        <td style="text-align:center;"><span class="tag-sede">${sedeUI}</span></td>
                        <td>
                            <div style="font-weight:600;font-size:0.75rem;">${i.linea?i.linea.substring(0,18):'-'}</div>
                            <div style="font-size:0.7rem;color:#94a3b8;">${i.marca?i.marca.substring(0,18):'-'}</div>
                        </td>
                        <td class="num" style="color:var(--accent);">${i.stock}</td>
                        <td class="num">${fmt(c)}</td>
                        <td class="num">${fmt(i.total_valor)}</td>
                        <td class="num" style="font-weight:700;">${fmt(p)}</td>
                        <td class="num" style="color:${colorM};font-weight:700;">${m.toFixed(1)}%</td>
                        <td style="text-align:center;"><button onclick="delItem(${i.id})" class="btn-del"><i class="ph-bold ph-trash"></i></button></td>
                    </tr>`;
                });
            }
            document.getElementById('page-info').innerText = `Pág ${d.pagination.current_page} de ${d.pagination.total_pages} (${d.pagination.total_items} items)`;
        } catch(e) {}
    }

    let timer; function debounceSearch() { clearTimeout(timer); timer = setTimeout(() => { state.page = 1; state.q_prod = document.getElementById('txt-prod').value; state.q_prin = document.getElementById('txt-prin').value; loadData(); }, 300); }
    function sort(col) { if(state.sort === col) state.dir = (state.dir === 'ASC') ? 'DESC' : 'ASC'; else { state.sort = col; state.dir = 'ASC'; } loadData(); }
    function navPage(d){ state.page+=d; if(state.page<1)state.page=1; loadData(); }
    async function delItem(id) { Swal.fire({title:'¿Borrar?', icon:'warning', showCancelButton:true, confirmButtonColor:'#d33'}).then(async r=>{ if(r.isConfirmed) { await fetch('admin_inventario_backend.php', {method:'POST', body:JSON.stringify({action:'delete', id})}); loadData(); } }); }
    function exportToExcel() { window.location.href = `admin_inventario_backend.php?mode=inventory&no_limit=1&sedes=${filters.sedes.join(',')}&groups=${filters.groups.join(',')}&lineas=${filters.lineas.join(',')}&marcas=${filters.marcas.join(',')}`; }
    function openExportDBF(tipo) { Swal.fire({ title: 'Exportar DBF', html: `Generar archivo <b>${tipo.toUpperCase()}</b> para:<br><br><select id="exp-sede" class="swal2-select" style="width:100%"><option value="HUAURA">Huaura</option><option value="INTEGRA">Integra</option><option value="M.MUNDO">M. Mundo</option></select>`, showCancelButton: true, confirmButtonText: 'Descargar' }).then((r) => { if(r.isConfirmed) { let sede = document.getElementById('exp-sede').value; window.location.href = `api_exportar_dbf.php?sede=${sede}&tipo=${tipo}`; } }); }
    function openImport(){ Swal.fire('Importar', 'Usar panel backend...', 'info'); }
</script>
</body>
</html>