<?php
include_once 'session.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') { header('Location: login.php'); exit; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestión de Inventario</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<style>
    :root { --primary: #1e40af; --bg: #f3f4f6; --white: #fff; --text: #1e293b; --border: #e2e8f0; --muted: #64748b; --c-farm: #0ea5e9; --c-suple: #16a34a; --c-insumo: #ea580c; --bg-huacho: linear-gradient(135deg, #0369a1 0%, #0ea5e9 100%); --bg-huaura: linear-gradient(135deg, #7c3aed 0%, #a78bfa 100%); --bg-mm: linear-gradient(135deg, #059669 0%, #10b981 100%); }
    body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); margin: 0; display: flex; height: 100vh; overflow: hidden; }
    
    .sidebar { width: 240px; background: #0f172a; color: white; padding: 20px; display: flex; flex-direction: column; flex-shrink: 0; }
    .content { flex: 1; padding: 15px 25px; overflow-y: auto; display: flex; flex-direction: column; }
    
    .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; gap:10px; flex-wrap:wrap; }
    .view-toggles { display: flex; background: #e2e8f0; padding: 3px; border-radius: 8px; gap: 5px; }
    .view-btn { padding: 6px 12px; border: none; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 600; color: #64748b; background: transparent; transition:.2s; white-space: nowrap; }
    .view-btn.active { background: #fff; color: var(--primary); box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    
    .btn-action { padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer; font-weight: 600; color: #fff; display: inline-flex; align-items: center; gap: 6px; font-size: 0.85rem; }
    .btn-imp { background: #1e293b; } .btn-fin { background: #ea580c; display: none; }
    .btn-print { background: #64748b; } .btn-print:hover { background: #475569; }
    .btn-preview { background: #0ea5e9; display: none; }
    .btn-excel { background: #16a34a; } .btn-excel:hover { background: #15803d; }
    
    /* CARDS */
    .dashboard-grid { display: flex; flex-direction: column; gap: 10px; margin-bottom: 15px; }
    .row-sedes { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
    .card-sede { padding: 15px 18px; border-radius: 12px; color: white; display: flex; justify-content: space-between; align-items: center; position: relative; overflow: hidden; min-height: 90px; box-shadow: 0 4px 10px -2px rgba(0,0,0,0.15); }
    .cs-huacho { background: var(--bg-huacho); } .cs-huaura { background: var(--bg-huaura); } .cs-mm { background: var(--bg-mm); }
    .cs-info { z-index: 2; position: relative; width: 100%; }
    .cs-title { font-size: 0.75rem; font-weight: 800; opacity: 0.8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 4px; display:inline-block;}
    .cs-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2px; }
    .cs-lbl { font-size: 0.7rem; font-weight: 500; opacity: 0.9; }
    .cs-val { font-size: 1.1rem; font-weight: 800; }
    .cs-date { font-size: 0.65rem; opacity: 0.8; margin-top: 6px; display: flex; align-items: center; gap: 4px; }
    .cs-icon { position: absolute; right: 10px; bottom: -10px; font-size: 4rem; opacity: 0.15; transform: rotate(-15deg); z-index: 1; }

    .row-cats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
    .card-cat { background: #fff; padding: 12px; border-radius: 10px; border: 1px solid var(--border); display: flex; align-items: center; gap: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); transition: transform .2s; }
    .cc-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
    .ico-tot { background:#eff6ff; color:var(--primary); } .ico-far { background:#e0f2fe; color:var(--c-farm); }
    .ico-sup { background:#dcfce7; color:var(--c-suple); } .ico-ins { background:#ffedd5; color:var(--c-insumo); }
    .cc-info { display: flex; flex-direction: column; }
    .cc-lbl { font-size: 0.65rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; }
    .cc-num { font-size: 1.1rem; font-weight: 800; color: var(--text); line-height: 1; }
    .cc-money { font-size: 0.75rem; color: #64748b; font-weight: 600; margin-top: 2px; }

    /* TABLAS OPTIMIZADAS */
    .table-tools { display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 10px 15px; border: 1px solid var(--border); border-radius: 10px 10px 0 0; }
    .table-wrap { background: #fff; border: 1px solid var(--border); border-radius: 0 0 10px 10px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    .table-scroll { flex: 1; overflow: auto; }
    
    table { width: 100%; border-collapse: collapse; min-width: 1000px; } /* Ancho mínimo ajustado */
    
    /* Encabezados ajustados */
    th { 
        background: var(--primary); color: white; padding: 10px 8px; /* Menos padding */
        font-size: 0.75rem; text-align: center; cursor: pointer; user-select: none; 
        border-right: 1px solid rgba(255,255,255,0.1); 
        white-space: nowrap; /* Evita que el título se parta */
    }
    
    /* Celdas ajustadas */
    td { 
        padding: 8px 8px; /* Menos padding para compactar */
        border-bottom: 1px solid var(--border); 
        font-size: 0.8rem; vertical-align: middle; 
    }
    
    /* Clases para anchos específicos */
    .col-prod { width: 35%; text-align: left; }
    .col-sede { width: 8%; font-size: 0.7rem; text-align: center; color: #64748b; font-weight: 600; }
    .col-cat  { width: 8%; font-size: 0.7rem; text-align: center; color: #64748b; }
    .col-num  { width: 9%; text-align: right; }
    .col-act  { width: 4%; text-align: center; }

    tbody tr:nth-child(even) { background: #f8fafc; }
    .input-count { width: 70px; padding: 5px; border: 2px solid #e2e8f0; border-radius: 6px; text-align: center; font-weight: bold; color: #1e293b; outline: none; }
    .input-count:focus { border-color: var(--primary); background: #eff6ff; }
    
    .view-section { display: none; flex-direction: column; flex: 1; }
    .view-section.active { display: flex; }
    
    /* MODAL */
    .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 1000; display: none; align-items: center; justify-content: center; backdrop-filter: blur(2px); }
    .modal-card { background: white; padding: 30px; border-radius: 16px; width: 90%; max-width: 400px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); display:flex; flex-direction: column; max-height: 90vh;}
    
    /* ... (Estilos de reportes y impresión iguales) ... */
    .modal-xl { max-width: 1100px; height: 90vh; }
    .modal-body-scroll { overflow: auto; margin: 10px 0; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; }
    .summary-preview { display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap; }
    .sp-box { flex: 1; padding: 15px; border-radius: 8px; border: 2px solid #eee; text-align: center; min-width: 150px; }
    .sp-title { font-size: 0.85rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
    .sp-val { font-size: 1.4rem; font-weight: 800; }
    .th-sortable { cursor: pointer; user-select: none; padding: 12px !important; background: #f8fafc; position: sticky; top: 0; border-bottom: 2px solid #cbd5e1; font-size: 0.85rem; color: #1e293b; font-weight: 700; }
    .chart-container { background: #fff; padding: 20px; border-radius: 12px; border: 1px solid var(--border); min-height: 350px; }
    .report-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
    .report-box { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; display: flex; flex-direction: column; height: 450px; overflow: hidden; }
    .report-header { padding: 12px 15px; font-weight: 700; border-bottom: 1px solid #e2e8f0; display:flex; align-items:center; gap:8px; font-size:0.9rem; }
    .rh-loss { background: #fff1f2; color: #be123c; border-bottom-color: #fecdd3; }
    .rh-gain { background: #f0fdf4; color: #15803d; border-bottom-color: #bbf7d0; }
    .audit-summary-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px; }
    .as-card { background: #fff; padding: 20px; border-radius: 12px; border: 1px solid var(--border); text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.03); }
    .as-val { font-size: 1.6rem; font-weight: 800; margin-top: 5px; letter-spacing: -0.5px; }
    
    #printable-area { display: none; }
    @media print {
        @page { size: A4; margin: 10mm; }
        body { background: white; height: auto; overflow: visible; display: block; }
        .sidebar, .header, .dashboard-grid, .table-tools, #view-inventory, #view-audit, #view-evolution, #view-report, #pag-controls, #modal-imp, #modal-preview { display: none !important; }
        #printable-area { display: block !important; font-family: 'Arial', sans-serif; color: #000; }
        .print-header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .print-header h2 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .print-table { width: 100%; border-collapse: collapse; font-size: 10px; }
        .print-table th { background: #eee !important; color: #000 !important; border: 1px solid #000; padding: 5px; text-align: center; font-weight: bold; }
        .print-table td { border: 1px solid #000; padding: 4px; vertical-align: middle; }
        .write-box { height: 25px; } 
    }
</style>
</head>
<body>

<div class="sidebar">
    <div style="color:white; font-weight:700; margin-bottom:30px; font-size:1.2rem; display:flex; align-items:center; gap:10px;">
        <i class="ph ph-first-aid-kit" style="color:var(--c-farm); font-size:1.5rem;"></i> Admin Panel
    </div>
    <div style="font-size:0.75rem; color:#64748b; font-weight:700; margin-bottom:10px; text-transform:uppercase;">Menú Principal</div>
    <a href="#" style="color:white; text-decoration:none; display:flex; align-items:center; gap:10px; padding:12px; background:rgba(255,255,255,0.1); border-radius:8px; font-weight:500;">
        <i class="ph ph-package"></i> Inventario Zeth
    </a>
    <a href="intranet.php" style="color:#94a3b8; text-decoration:none; display:flex; align-items:center; gap:10px; padding:12px; margin-top:auto;">
        <i class="ph ph-arrow-u-up-left"></i> Volver
    </a>
</div>

<div class="content">
    <div class="header">
        <div class="view-toggles">
            <button class="view-btn active" onclick="switchView('inventory')"><i class="ph ph-list-dashes"></i> Valorización</button>
            <button class="view-btn" onclick="switchView('audit')"><i class="ph ph-clipboard-text"></i> Toma Inventario</button>
            <button class="view-btn" onclick="switchView('evolution')"><i class="ph ph-chart-line-up"></i> Evolución</button>
            <button class="view-btn" onclick="switchView('report')"><i class="ph ph-file-text"></i> Reporte Auditoría</button>
        </div>
        <div style="display:flex; gap:10px;">
            <button class="btn-action btn-print" onclick="prepareAndPrint()"><i class="ph ph-printer"></i> Imprimir Hoja</button>
            <button id="btn-preview" class="btn-action btn-preview" onclick="showPreviewAudit()"><i class="ph ph-eye"></i> Previsualizar</button>
            <button id="btn-finalize" class="btn-action btn-fin" onclick="finalizeAudit()"><i class="ph ph-check-circle"></i> Finalizar Auditoría</button>
            
            <button class="btn-action btn-excel" onclick="exportToExcel()"><i class="ph ph-file-xls"></i> Exportar Excel</button>
            
            <button class="btn-action btn-imp" onclick="openImport()"><i class="ph ph-upload-simple"></i> Importar DBF</button>
        </div>
    </div>

    <div class="dashboard-grid" id="main-cards">
        <div class="row-sedes">
            <div class="card-sede cs-huacho">
                <div class="cs-info">
                    <div class="cs-title">Huacho</div>
                    <div class="cs-row"><span class="cs-lbl">Costo:</span> <span class="cs-val" id="vc-huacho">S/ 0.00</span></div>
                    <div class="cs-row"><span class="cs-lbl">Venta:</span> <span class="cs-val" id="vv-huacho">S/ 0.00</span></div>
                    <div class="cs-date"><i class="ph ph-clock"></i> <span id="d-huacho">--/--</span></div>
                </div>
                <i class="ph ph-map-pin cs-icon"></i>
            </div>
            <div class="card-sede cs-huaura">
                <div class="cs-info">
                    <div class="cs-title">Huaura</div>
                    <div class="cs-row"><span class="cs-lbl">Costo:</span> <span class="cs-val" id="vc-huaura">S/ 0.00</span></div>
                    <div class="cs-row"><span class="cs-lbl">Venta:</span> <span class="cs-val" id="vv-huaura">S/ 0.00</span></div>
                    <div class="cs-date"><i class="ph ph-clock"></i> <span id="d-huaura">--/--</span></div>
                </div>
                <i class="ph ph-hospital cs-icon"></i>
            </div>
            <div class="card-sede cs-mm">
                <div class="cs-info">
                    <div class="cs-title">Medio Mundo</div>
                    <div class="cs-row"><span class="cs-lbl">Costo:</span> <span class="cs-val" id="vc-mm">S/ 0.00</span></div>
                    <div class="cs-row"><span class="cs-lbl">Venta:</span> <span class="cs-val" id="vv-mm">S/ 0.00</span></div>
                    <div class="cs-date"><i class="ph ph-clock"></i> <span id="d-mm">--/--</span></div>
                </div>
                <i class="ph ph-tree-palm cs-icon"></i>
            </div>
        </div>
        <div class="row-cats">
            <div class="card-cat"><div class="cc-icon ico-tot"><i class="ph ph-cube"></i></div><div class="cc-info"><div class="cc-lbl">Total</div><div class="cc-num" id="c-tot">0</div><div class="cc-money" id="m-tot">S/ 0.00</div></div></div>
            <div class="card-cat"><div class="cc-icon ico-far"><i class="ph ph-pill"></i></div><div class="cc-info"><div class="cc-lbl">Medicamentos</div><div class="cc-num" id="c-far">0</div><div class="cc-money" id="m-far">S/ 0.00</div></div></div>
            <div class="card-cat"><div class="cc-icon ico-sup"><i class="ph ph-flask"></i></div><div class="cc-info"><div class="cc-lbl">Suplementos</div><div class="cc-num" id="c-sup">0</div><div class="cc-money" id="m-sup">S/ 0.00</div></div></div>
            <div class="card-cat"><div class="cc-icon ico-ins"><i class="ph ph-syringe"></i></div><div class="cc-info"><div class="cc-lbl">Insumos</div><div class="cc-num" id="c-ins">0</div><div class="cc-money" id="m-ins">S/ 0.00</div></div></div>
        </div>
    </div>

    <div class="table-tools" id="common-tools">
        <div style="display:flex; gap:5px;">
            <button class="view-btn active" onclick="filterCat('ALL',this)">Todo</button>
            <button class="view-btn" onclick="filterCat('FARMACIA',this)">Medicamentos</button>
            <button class="view-btn" onclick="filterCat('SUPLEMENTOS',this)">Suplementos</button>
            <button class="view-btn" onclick="filterCat('INSUMOS MEDICOS',this)">Insumos</button>
        </div>
        <div style="display:flex; gap:10px;">
            <select id="sede-filter" onchange="filterSede(this.value)" style="padding:6px;border-radius:6px;border:1px solid #e2e8f0;font-size:0.85rem">
                <option value="ALL">Todas las Sedes</option><option value="HUACHO">Huacho</option><option value="HUAURA">Huaura</option><option value="MEDIO MUNDO">M. Mundo</option>
            </select>
            <input type="text" id="txt-search" style="padding:6px;border-radius:6px;border:1px solid #e2e8f0; width:220px;" placeholder="Buscar producto o principio..." onkeyup="debounceSearch()">
        </div>
    </div>

    <div id="view-inventory" class="view-section active">
        <div class="table-wrap"><div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th class="col-prod" onclick="sort('nombre')">Producto <i id="inv-icon-nombre" class="ph ph-caret-up-down"></i></th>
                        <th class="col-sede" onclick="sort('sede')">Sede <i id="inv-icon-sede" class="ph ph-caret-up-down"></i></th>
                        <th class="col-cat" onclick="sort('categoria')">Cat. <i id="inv-icon-categoria" class="ph ph-caret-up-down"></i></th>
                        <th class="col-num" onclick="sort('stock')">Stock <i id="inv-icon-stock" class="ph ph-caret-up-down"></i></th>
                        <th class="col-num" onclick="sort('costo')">Costo <i id="inv-icon-costo" class="ph ph-caret-up-down"></i></th>
                        <th class="col-num" onclick="sort('precio')">Precio <i id="inv-icon-precio" class="ph ph-caret-up-down"></i></th>
                        <th class="col-num">Margen</th>
                        <th class="col-num" onclick="sort('total')">Total <i id="inv-icon-total" class="ph ph-caret-up-down"></i></th>
                        <th class="col-act"></th>
                    </tr>
                </thead>
                <tbody id="tb-inv"></tbody>
            </table>
        </div></div>
    </div>

    <div id="view-audit" class="view-section">
        <div class="table-wrap"><div class="table-scroll">
            <table><thead><tr>
                <th align="left" onclick="sort('nombre')">Producto <i id="aud-icon-nombre" class="ph ph-caret-up-down"></i></th>
                <th onclick="sort('sede')">Sede <i id="aud-icon-sede" class="ph ph-caret-up-down"></i></th>
                <th align="right">Sistema</th>
                <th style="background:#e0f2fe;color:#0369a1; border-bottom:2px solid #0284c7;">Físico (Real)</th>
                <th align="center" onclick="sort('diferencia')">Diferencia <i id="aud-icon-diferencia" class="ph ph-caret-up-down"></i></th>
            </tr></thead><tbody id="tb-audit"></tbody></table>
        </div></div>
    </div>

    <div id="view-evolution" class="view-section"><div class="chart-container"><h3>Evolución de Valor (S/)</h3><div id="chart-val" style="height:350px;"></div></div></div>
    <div id="view-report" class="view-section">
        <div class="audit-summary-row"><div class="as-card"><div style="font-size:0.8rem;font-weight:700;color:#ef4444">FALTANTE</div><div class="as-val" style="color:#ef4444" id="rep-loss">S/ 0.00</div></div><div class="as-card"><div style="font-size:0.8rem;font-weight:700;color:#22c55e">SOBRANTE</div><div class="as-val" style="color:#22c55e" id="rep-gain">S/ 0.00</div></div><div class="as-card"><div style="font-size:0.8rem;font-weight:700;color:#1e40af">NETO</div><div class="as-val" style="color:#1e40af" id="rep-net">S/ 0.00</div></div></div>
        <div class="chart-container" style="margin-bottom:20px;"><h4>Historial</h4><div id="chart-audit" style="height:250px;"></div></div>
        <div class="report-grid"><div class="report-box"><div class="report-header rh-loss">Pérdidas</div><div class="table-scroll"><table style="width:100%;"><thead><tr><th align="left">Producto</th><th align="right">Falta</th><th align="right">Dinero</th></tr></thead><tbody id="tb-loss-detail"></tbody></table></div></div><div class="report-box"><div class="report-header rh-gain">Ganancias</div><div class="table-scroll"><table style="width:100%;"><thead><tr><th align="left">Producto</th><th align="right">Sobra</th><th align="right">Dinero</th></tr></thead><tbody id="tb-gain-detail"></tbody></table></div></div></div>
    </div>

    <div id="pag-controls" style="padding:10px 15px; background:#fff; border:1px solid #e2e8f0; border-top:none; display:flex; justify-content:space-between; border-radius: 0 0 10px 10px;">
        <span id="page-info">0-0 de 0</span>
        <div style="display:flex; gap:5px;"><button class="view-btn" onclick="navPage(-1)">Ant</button> <button class="view-btn" onclick="navPage(1)">Sig</button></div>
    </div>
</div>

<div id="modal-preview" class="modal-overlay" style="display:none;"><div class="modal-card modal-xl"><div style="display:flex;justify-content:space-between;margin-bottom:15px;"><h3 style="margin:0">Previsualización</h3><button onclick="document.getElementById('modal-preview').style.display='none'">x</button></div><div class="summary-preview"><div class="sp-box" style="background:#fef2f2;color:red"><div class="sp-title">Faltante</div><div class="sp-val" id="prev-loss-val">S/0</div></div><div class="sp-box" style="background:#f0fdf4;color:green"><div class="sp-title">Sobrante</div><div class="sp-val" id="prev-gain-val">S/0</div></div><div class="sp-box" style="background:#eff6ff;color:blue"><div class="sp-title">Neto</div><div class="sp-val" id="prev-net-val">S/0</div></div></div><div style="display:flex;gap:20px;max-height:60vh;overflow:hidden"><div style="flex:1;overflow:auto"><table style="width:100%"><thead><tr><th>Producto</th><th align="right">Cant</th><th align="right">S/</th></tr></thead><tbody id="prev-tb-loss"></tbody></table></div><div style="flex:1;overflow:auto"><table style="width:100%"><thead><tr><th>Producto</th><th align="right">Cant</th><th align="right">S/</th></tr></thead><tbody id="prev-tb-gain"></tbody></table></div></div><div style="margin-top:15px;text-align:right"><div style="display:flex;justify-content:space-between"><button class="btn-action btn-excel" onclick="exportToExcel()">Exportar XLS</button><div><button class="btn-action" style="background:#e2e8f0;color:#333;margin-right:10px" onclick="document.getElementById('modal-preview').style.display='none'">Seguir</button><button class="btn-action btn-fin" onclick="finalizeAudit()">Confirmar</button></div></div></div></div></div>
<div id="printable-area" style="display:none"><div class="print-header"><h2>HOJA DE TOMA DE INVENTARIO FÍSICO</h2><p>Generado el: <?php echo date('d/m/Y H:i'); ?> | Sede: <span id="print-sede-name">General</span></p></div><table class="print-table"><thead><tr><th style="width:50px;">ID</th><th>PRODUCTO / CÓDIGO</th><th style="width:100px;">CANTIDAD</th><th>VENCIMIENTO</th></tr></thead><tbody id="tb-print"></tbody></table></div>

<div id="modal-imp" class="modal-overlay">
    <div class="modal-card">
        <h3>Subir Datos (DBF/CSV)</h3>
        <form id="form-imp">
            <label style="font-weight:bold; font-size:0.8rem;">1. Selecciona la Sede:</label>
            <select id="imp-sede" style="width:100%;margin-bottom:15px;padding:10px;border-radius:6px;border:1px solid #ccc;">
                <option value="HUACHO">Huacho</option>
                <option value="HUAURA">Huaura</option>
                <option value="MEDIO MUNDO">M. Mundo</option>
            </select>
            <label style="font-weight:bold; font-size:0.8rem;">2. ¿Qué archivo es?</label>
            <select id="imp-tipo" style="width:100%;margin-bottom:15px;padding:10px;border-radius:6px;border:1px solid #ccc;">
                <option value="inventario">Inventario (Zeth70)</option>
                <option value="principios">Maestro Principios (Zeth19)</option>
            </select>
            <label style="font-weight:bold; font-size:0.8rem;">3. Archivo:</label>
            <input type="file" id="imp-file" accept=".dbf,.DBF,.csv,.CSV,.txt" required style="width:100%;margin-bottom:20px">
            <button class="btn-action btn-imp" style="width:100%;justify-content:center;padding:12px;">Procesar Carga</button>
        </form>
    </div>
</div>

<script>
    let state = { q:'', cat:'ALL', sede:'ALL', page:1, sort:'nombre', dir:'ASC' };
    let chartInstanceVal = null, chartInstanceAud = null;
    let prevData = { loss: [], gain: [] };
    let prevSort = { loss: { col: 'val', dir: 'desc' }, gain: { col: 'val', dir: 'desc' } };

    document.addEventListener('DOMContentLoaded', () => { loadData(); });

    function switchView(v) {
        document.querySelectorAll('.view-btn').forEach(b=>b.classList.remove('active')); event.currentTarget.classList.add('active');
        document.querySelectorAll('.view-section').forEach(s=>s.classList.remove('active')); document.getElementById('view-'+v).classList.add('active');
        const tools=document.getElementById('common-tools'); const cards=document.getElementById('main-cards'); const pag=document.getElementById('pag-controls');
        document.getElementById('btn-finalize').style.display = (v==='audit')?'inline-flex':'none';
        document.getElementById('btn-preview').style.display = (v==='audit')?'inline-flex':'none';
        if(v==='inventory' || v==='audit'){ tools.style.display='flex'; cards.style.display='flex'; pag.style.display='flex'; loadData(); }
        else if(v==='evolution'){ tools.style.display='none'; cards.style.display='none'; pag.style.display='none'; loadChartVal(); }
        else if(v==='report'){ tools.style.display='flex'; cards.style.display='none'; pag.style.display='none'; loadReport(); }
    }

    async function loadData() {
        const p = new URLSearchParams(state); p.append('ts', new Date().getTime()); p.append('mode', document.getElementById('view-audit').classList.contains('active') ? 'audit' : 'inventory');
        const res = await fetch(`admin_inventario_backend.php?${p}`);
        const d = await res.json();
        const fmt = n => 'S/ '+parseFloat(n||0).toLocaleString('es-PE',{minimumFractionDigits:2});

        ['HUACHO','HUAURA','MEDIO MUNDO'].forEach(s=>{ 
            const k=s==='MEDIO MUNDO'?'mm':s.toLowerCase(); 
            if(d.cards_sedes[s]) {
                if(document.getElementById(`vc-${k}`)) document.getElementById(`vc-${k}`).innerText = fmt(d.cards_sedes[s].val_costo);
                if(document.getElementById(`vv-${k}`)) document.getElementById(`vv-${k}`).innerText = fmt(d.cards_sedes[s].val_venta);
            }
            document.getElementById(`d-${k}`).innerText = d.last_updates[s] || '--/--'; 
        });

        const setC=(k,o)=>{document.getElementById('c-'+k).innerText=o.qty; document.getElementById('m-'+k).innerText=fmt(o.val);};
        setC('tot',d.cards_cats.TOTAL); setC('far',d.cards_cats.FARMACIA); setC('sup',d.cards_cats.SUPLEMENTOS); setC('ins',d.cards_cats.INSUMOS);
        updateSortIcons();

        const tb = document.getElementById(state.page ? 'tb-inv' : 'tb-audit'); 
        if(document.getElementById('view-audit').classList.contains('active')){
            const tbA=document.getElementById('tb-audit'); tbA.innerHTML='';
            d.list.forEach(i=>{ const df=i.conteo_fisico!==''?i.conteo_fisico-i.stock:'-'; tbA.innerHTML+=`<tr><td>${i.nombre}<br><small style="color:#059669">${i.principio}</small></td><td>${i.sede}</td><td align="right">${i.stock}</td><td align="center"><input class="input-count" type="number" value="${i.conteo_fisico}" onchange="saveCount(${i.id},this.value)"></td><td align="center">${df}</td></tr>`; });
        } else {
            const tbI=document.getElementById('tb-inv'); tbI.innerHTML='';
            d.list.forEach(i=>{ 
                let margen = 0; let colorMargen = '#64748b';
                let precio = parseFloat(i.precio || 0); let costo = parseFloat(i.costo || 0);
                if (precio > 0) {
                   margen = ((precio - costo) / precio) * 100;
                   if(margen < 0) colorMargen = '#ef4444'; else if(margen > 20) colorMargen = '#16a34a';
                }
                tbI.innerHTML+=`<tr>
                    <td class="col-prod"><b>${i.nombre}</b><br><small style="color:#059669">${i.principio}</small><br><small style="color:#94a3b8">${i.codigo}</small></td>
                    <td class="col-sede">${i.sede}</td>
                    <td class="col-cat">${i.categoria}</td>
                    <td class="col-num">${i.stock}</td>
                    <td class="col-num">${fmt(costo)}</td>
                    <td class="col-num" style="font-weight:bold;color:#1e40af;">${fmt(precio)}</td>
                    <td class="col-num" style="color:${colorMargen};font-weight:bold;">${margen.toFixed(1)}%</td>
                    <td class="col-num">${fmt(i.total_valor)}</td>
                    <td class="col-act"><button onclick="delItem(${i.id})" style="border:none;background:#fee2e2;color:red;width:24px;border-radius:4px;cursor:pointer">x</button></td>
                </tr>`; 
            });
        }
        document.getElementById('page-info').innerText=`${((state.page-1)*50)+1}-${Math.min(state.page*50,d.pagination.total_items)} de ${d.pagination.total_items}`;
    }

    function updateSortIcons() {
        const icons = document.querySelectorAll('.ph-caret-up-down, .ph-caret-up, .ph-caret-down');
        icons.forEach(i => { if(!i.id.includes('icon-loss') && !i.id.includes('icon-gain')) i.className = 'ph ph-caret-up-down'; });
        const activePrefix = document.getElementById('view-audit').classList.contains('active') ? 'aud-icon-' : 'inv-icon-';
        const targetId = activePrefix + state.sort;
        const targetEl = document.getElementById(targetId);
        if (targetEl) { targetEl.className = state.dir === 'ASC' ? 'ph ph-caret-up' : 'ph ph-caret-down'; }
    }

    // Funciones auxiliares
    async function showPreviewAudit() {
        if(state.sede === 'ALL') { alert("Seleccione una Sede específica en el filtro para auditar."); return; }
        const p = new URLSearchParams({action:'audit_report_data', sede:state.sede});
        const res = await fetch(`admin_inventario_backend.php?${p}`);
        const d = await res.json();
        const fmt = n => 'S/ '+parseFloat(n).toLocaleString('es-PE',{minimumFractionDigits:2});
        document.getElementById('prev-loss-val').innerText = fmt(d.current_summary.loss);
        document.getElementById('prev-gain-val').innerText = fmt(d.current_summary.gain);
        document.getElementById('prev-net-val').innerText = fmt(d.current_summary.net);
        prevData.loss = d.details_loss.map(x => ({...x, dif_qty: Number(x.dif_qty), dif_val: Number(x.dif_val)}));
        prevData.gain = d.details_gain.map(x => ({...x, dif_qty: Number(x.dif_qty), dif_val: Number(x.dif_val)}));
        renderPreviewTable('loss'); renderPreviewTable('gain');
        document.getElementById('modal-preview').style.display = 'flex';
    }

    function sortPreview(type, col) {
        const current = prevSort[type];
        if(current.col === col) current.dir = current.dir === 'asc' ? 'desc' : 'asc'; else { current.col = col; current.dir = col === 'nombre' ? 'asc' : 'desc'; }
        renderPreviewTable(type);
    }
    
    function renderPreviewTable(type) {
        const list = prevData[type]; const s = prevSort[type];
        const tbody = document.getElementById(type === 'loss' ? 'prev-tb-loss' : 'prev-tb-gain');
        const fmt = n => 'S/ '+parseFloat(n).toLocaleString('es-PE',{minimumFractionDigits:2});
        list.sort((a, b) => {
            let valA = (s.col === 'nombre') ? a.nombre.toLowerCase() : Math.abs(a['dif_' + s.col]);
            let valB = (s.col === 'nombre') ? b.nombre.toLowerCase() : Math.abs(b['dif_' + s.col]);
            if (valA < valB) return s.dir === 'asc' ? -1 : 1;
            if (valA > valB) return s.dir === 'asc' ? 1 : -1;
            return 0;
        });
        tbody.innerHTML = '';
        if(list.length === 0) { tbody.innerHTML = '<tr><td colspan="4" align="center" style="padding:10px; color:#999;">Ninguno</td></tr>'; return; }
        list.forEach((i, index) => {
            let color = type === 'loss' ? 'red' : 'green'; let sign = type === 'loss' ? '' : '+';
            tbody.innerHTML += `<tr><td style="color:#94a3b8; font-size:0.75rem;">${index+1}</td><td>${i.nombre}</td><td align="right" style="color:${color};font-weight:bold">${sign}${Math.abs(i.dif_qty)}</td><td align="right" style="color:${color}">${fmt(Math.abs(i.dif_val))}</td></tr>`;
        });
    }

    async function prepareAndPrint() {
        const btn = document.querySelector('.btn-print'); const oldText = btn.innerHTML;
        btn.innerHTML = '⏳ Cargando...'; btn.disabled = true;
        try {
            const p = new URLSearchParams(state); p.append('no_limit', '1'); p.append('mode', 'inventory'); p.set('sort', 'id'); p.set('dir', 'ASC');
            const res = await fetch(`admin_inventario_backend.php?${p}`); const d = await res.json();
            const tbPrint = document.getElementById('tb-print'); tbPrint.innerHTML = '';
            document.getElementById('print-sede-name').innerText = (state.sede === 'ALL') ? 'Todas las Sedes' : state.sede;
            d.list.forEach((i, idx) => { tbPrint.innerHTML += `<tr><td style="text-align:center">${idx+1}</td><td><b>${i.nombre}</b><br><span style="font-size:9px;color:#555">COD: ${i.codigo} | ${i.sede}</span></td><td class="write-box"></td><td class="write-box"></td></tr>`; });
            window.print();
        } catch(e) { alert("Error al cargar datos."); } finally { btn.innerHTML = oldText; btn.disabled = false; }
    }
    
    async function exportToExcel() {
        const btn = document.querySelector('.btn-excel'); const oldText = btn.innerHTML;
        btn.innerHTML = '⏳ Generando...'; btn.disabled = true;
        try {
            const p = new URLSearchParams(state); p.append('no_limit', '1'); p.append('mode', 'inventory'); p.set('sort', 'id'); p.set('dir', 'ASC'); 
            const res = await fetch(`admin_inventario_backend.php?${p}`); const d = await res.json();
            
            // Construcción del HTML para Excel con soporte de tildes y ñ
            let table = `<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><meta charset="UTF-8"></head><body><table border="1"><thead><tr><th style="background:#1e40af;color:white;">ID</th><th style="background:#1e40af;color:white;">CÓDIGO</th><th style="background:#1e40af;color:white;">PRODUCTO</th><th style="background:#1e40af;color:white;">PRINCIPIO ACTIVO</th><th style="background:#1e40af;color:white;">SEDE</th><th style="background:#1e40af;color:white;">CATEGORÍA</th><th style="background:#1e40af;color:white;">STOCK</th><th style="background:#1e40af;color:white;">COSTO</th><th style="background:#1e40af;color:white;">PRECIO</th></tr></thead><tbody>`;
            
            d.list.forEach((i, index) => { table += `<tr><td>${index + 1}</td><td>${i.codigo}</td><td>${i.nombre}</td><td>${i.principio}</td><td>${i.sede}</td><td>${i.categoria}</td><td>${i.stock}</td><td>${i.costo}</td><td>${i.precio}</td></tr>`; });
            table += `</tbody></table></body></html>`;
            
            // Usamos BOM para forzar UTF-8 en Excel
            const blob = new Blob(['\ufeff', table], { type: 'application/vnd.ms-excel' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a'); a.href = url; a.download = `Inventario_${state.sede}_${new Date().toISOString().slice(0,10)}.xls`;
            document.body.appendChild(a); a.click(); document.body.removeChild(a);
        } catch(e) { alert("Error al exportar."); } finally { btn.innerHTML = oldText; btn.disabled = false; }
    }

    async function saveCount(id, val) { await fetch('admin_inventario_backend.php', { method:'POST', body:JSON.stringify({action:'update_count', id, val}) }); loadData(); }
    async function delItem(id) { if(confirm("¿Eliminar?")) { await fetch('admin_inventario_backend.php', { method:'POST', body:JSON.stringify({action:'delete', id}) }); loadData(); } }
    async function finalizeAudit() { if(confirm("¿Cerrar auditoría oficial?")) { await fetch('admin_inventario_backend.php', { method:'POST', body:JSON.stringify({action:'finalize_audit', sede:state.sede}) }); alert("Finalizado"); document.getElementById('modal-preview').style.display='none'; switchView('report'); } }
    
    async function loadReport() {
        const p = new URLSearchParams({action:'audit_report_data', sede:state.sede}); const res = await fetch(`admin_inventario_backend.php?${p}`); const d = await res.json();
        const fmt = n => 'S/ '+parseFloat(n).toLocaleString('es-PE',{minimumFractionDigits:2});
        document.getElementById('rep-loss').innerText = fmt(d.current_summary.loss); document.getElementById('rep-gain').innerText = fmt(d.current_summary.gain); document.getElementById('rep-net').innerText = fmt(d.current_summary.net);
        const tl=document.getElementById('tb-loss-detail'); tl.innerHTML=''; d.details_loss.forEach(i=>{tl.innerHTML+=`<tr><td>${i.nombre}</td><td style="color:red">${i.dif_qty}</td><td style="color:red">${fmt(i.dif_val)}</td></tr>`});
        const tg=document.getElementById('tb-gain-detail'); tg.innerHTML=''; d.details_gain.forEach(i=>{tg.innerHTML+=`<tr><td>${i.nombre}</td><td style="color:green">+${i.dif_qty}</td><td style="color:green">${fmt(i.dif_val)}</td></tr>`});
        if(chartInstanceAud) chartInstanceAud.destroy(); chartInstanceAud = new ApexCharts(document.getElementById('chart-audit'), { series: [{name:'Faltante', data: d.chart_history.map(x=>x.total_faltante)}], chart: {type:'bar', height:250}, xaxis: {categories: d.chart_history.map(x=>x.fecha)}, colors: ['#ef4444'] }); chartInstanceAud.render();
    }
    
    async function loadChartVal() {
        const res = await fetch('admin_inventario_backend.php?action=history&ts='+new Date().getTime()); const d = await res.json();
        const series = [{ name: 'Huacho', data: [] }, { name: 'Huaura', data: [] }, { name: 'Medio Mundo', data: [] }];
        d.forEach(h => { const v = parseFloat(h.total_valor).toFixed(2); if(h.sede=='HUACHO') series[0].data.push({x:h.fecha, y:v}); if(h.sede=='HUAURA') series[1].data.push({x:h.fecha, y:v}); if(h.sede=='MEDIO MUNDO') series[2].data.push({x:h.fecha, y:v}); });
        if(chartInstanceVal) chartInstanceVal.destroy(); chartInstanceVal = new ApexCharts(document.getElementById('chart-val'), { series: series, chart: {type:'area', height:300}, xaxis: {type:'category'}, colors: ['#0ea5e9', '#8b5cf6', '#10b981'] }); chartInstanceVal.render();
    }

    let timer; function debounceSearch(){ clearTimeout(timer); timer=setTimeout(()=>{state.page=1;state.q=document.getElementById('txt-search').value;loadData();},300); }
    function filterCat(c,b){ document.querySelectorAll('.table-tools .view-btn').forEach(btn=>btn.classList.remove('active')); b.classList.add('active'); state.cat=c; state.page=1; loadData(); }
    function filterSede(s){ state.sede=s; state.page=1; loadData(); if(document.getElementById('view-report').classList.contains('active')) loadReport(); }
    function navPage(d){ state.page+=d; if(state.page<1)state.page=1; loadData(); }
    function sort(c){ if(state.sort===c) state.dir=(state.dir==='ASC')?'DESC':'ASC'; else {state.sort=c; state.dir='ASC';} state.page=1; loadData(); }
    
    // IMPORTACIÓN
    const m=document.getElementById('modal-imp'); function openImport(){m.style.display='flex';} 
    document.getElementById('form-imp').addEventListener('submit',async(e)=>{
        e.preventDefault(); 
        const btn = e.target.querySelector('button'); 
        const oldText = btn.innerHTML;
        btn.innerHTML = "Cargando..."; btn.disabled = true;

        const fd=new FormData(); 
        fd.append('dbf_file', document.getElementById('imp-file').files[0]); 
        fd.append('sede_destino', document.getElementById('imp-sede').value);
        fd.append('tipo_archivo', document.getElementById('imp-tipo').value);

        try {
            const res = await fetch('api_importar_zeth.php',{method:'POST',body:fd}); 
            const text = await res.text();
            try {
                const d = JSON.parse(text);
                alert(d.success ? "✅ " + d.message : "⚠️ " + d.message);
            } catch(jsonErr) {
                console.error(text);
                alert("Error del servidor (Ver consola para detalles).");
            }
        } catch(err) {
            alert("Error de conexión.");
        }
        
        m.style.display='none';
        loadData();
        btn.innerHTML = oldText; btn.disabled = false;
    });
</script>
</body>
</html>