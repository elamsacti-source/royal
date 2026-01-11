<?php
// admin_homologar.php
// V5.2: Integración con Inventario (Auto-carga por URL) + UI PRO
include_once 'session.php';
include_once 'db_config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') { header('Location: login.php'); exit; }

// --- BACKEND ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    // 1. BUSCAR PRODUCTOS
    if ($action === 'search_prod') {
        $sede = $conn->real_escape_string($_POST['sede']);
        $term = $conn->real_escape_string($_POST['term']);
        
        $sql = "SELECT i.id, i.codigo, i.nombre, i.costo, i.precio, i.stock,
                       p.des_sub as principio, i.sublin as cod_prin,
                       l.des_lin as linea, i.lineaz as cod_lin,
                       m.des_mar as marca, i.marcaz as cod_mar
                FROM inventario_zeth i 
                LEFT JOIN principios_zeth p ON (i.sublin = p.cod_sub AND i.sede = p.sede)
                LEFT JOIN lineas_zeth l ON (i.lineaz = l.cod_lin AND i.sede = l.sede)
                LEFT JOIN marcas_zeth m ON (i.marcaz = m.cod_mar AND i.sede = m.sede)
                WHERE i.sede = '$sede' AND (i.nombre LIKE '%$term%' OR i.codigo LIKE '%$term%') 
                LIMIT 15";
        
        $res = $conn->query($sql);
        $data = [];
        while($r = $res->fetch_assoc()) {
            $stockVal = intval($r['stock']);
            $costo = floatval($r['costo']);
            $precio = floatval($r['precio']);
            
            // Ganancia % (Margen sobre Venta)
            $ganancia = 0;
            if ($precio > 0) { 
                $ganancia = round((($precio - $costo) / $precio) * 100, 1); 
            }

            $data[] = [
                'id' => $r['id'],
                'label' => $r['nombre'], 
                'value' => $r['nombre'],
                'nombre_puro' => $r['nombre'],
                'codigo' => $r['codigo'],
                'stock' => $stockVal,
                'ganancia_pct' => $ganancia,
                'principio_txt' => $r['principio'], 'principio_cod' => $r['cod_prin'],
                'linea_txt' => $r['linea'], 'linea_cod' => $r['cod_lin'],
                'marca_txt' => $r['marca'], 'marca_cod' => $r['cod_mar'],
                'costo' => $costo, 'precio' => $precio
            ];
        }
        echo json_encode($data);
        exit;
    }

    // 2. BUSCADORES AUXILIARES
    if ($action === 'search_prin') {
        $term = $conn->real_escape_string($_POST['term']);
        $res = $conn->query("SELECT DISTINCT cod_sub, des_sub FROM principios_zeth WHERE des_sub LIKE '%$term%' LIMIT 15");
        $d=[]; while($r=$res->fetch_assoc()) $d[]=['id'=>$r['cod_sub'], 'label'=>$r['des_sub']];
        echo json_encode($d); exit;
    }
    if ($action === 'search_linea') {
        $term = $conn->real_escape_string($_POST['term']);
        $res = $conn->query("SELECT DISTINCT cod_lin, des_lin FROM lineas_zeth WHERE des_lin LIKE '%$term%' LIMIT 15");
        $d=[]; while($r=$res->fetch_assoc()) $d[]=['id'=>$r['cod_lin'], 'label'=>$r['des_lin']];
        echo json_encode($d); exit;
    }
    if ($action === 'search_marca') {
        $term = $conn->real_escape_string($_POST['term']);
        $res = $conn->query("SELECT DISTINCT cod_mar, des_mar FROM marcas_zeth WHERE des_mar LIKE '%$term%' LIMIT 15");
        $d=[]; while($r=$res->fetch_assoc()) $d[]=['id'=>$r['cod_mar'], 'label'=>$r['des_mar']];
        echo json_encode($d); exit;
    }

    // 3. SINCRONIZAR
    if ($action === 'sync') {
        $nuevoNombre = mb_strtoupper(trim($_POST['nombre']), 'UTF-8');
        
        $codPrin = trim($_POST['cod_principio']);
        $nomPrin = mb_strtoupper(trim($_POST['nom_principio']), 'UTF-8');
        
        $codLin  = trim($_POST['cod_linea']);
        $nomLin  = mb_strtoupper(trim($_POST['nom_linea']), 'UTF-8');
        
        $codMar  = trim($_POST['cod_marca']);
        $nomMar  = mb_strtoupper(trim($_POST['nom_marca']), 'UTF-8');
        
        $costo   = floatval($_POST['costo']);
        $precio  = floatval($_POST['precio']);

        // --- A. GESTIÓN DE CATEGORÍAS (LÍNEAS) ---
        if (empty($codLin) && !empty($nomLin)) {
            $qL = $conn->query("SELECT cod_lin FROM lineas_zeth WHERE des_lin = '$nomLin' LIMIT 1");
            if ($rowL = $qL->fetch_assoc()) {
                $codLin = $rowL['cod_lin'];
            } else {
                $qMax = $conn->query("SELECT MAX(CAST(cod_lin AS UNSIGNED)) as max_cod FROM lineas_zeth WHERE cod_lin REGEXP '^[0-9]+$'");
                $next = ($qMax->fetch_assoc()['max_cod'] ?? 0) + 1;
                $codLin = str_pad($next, 2, '0', STR_PAD_LEFT);
                $stmtInsL = $conn->prepare("INSERT INTO lineas_zeth (sede, cod_lin, des_lin) VALUES (?, ?, ?)");
                foreach(['HUACHO','HUAURA','MEDIO MUNDO'] as $s) {
                     $stmtInsL->bind_param("sss", $s, $codLin, $nomLin); $stmtInsL->execute();
                }
            }
        }

        // --- B. GESTIÓN DE MARCAS (SUBCATEGORÍAS) ---
        if (empty($codMar) && !empty($nomMar)) {
            $qM = $conn->query("SELECT cod_mar FROM marcas_zeth WHERE des_mar = '$nomMar' LIMIT 1");
            if ($rowM = $qM->fetch_assoc()) {
                $codMar = $rowM['cod_mar'];
            } else {
                $qMax = $conn->query("SELECT MAX(CAST(cod_mar AS UNSIGNED)) as max_cod FROM marcas_zeth WHERE cod_mar REGEXP '^[0-9]+$'");
                $next = ($qMax->fetch_assoc()['max_cod'] ?? 0) + 1;
                $codMar = str_pad($next, 4, '0', STR_PAD_LEFT);
                $stmtInsM = $conn->prepare("INSERT INTO marcas_zeth (sede, cod_mar, des_mar) VALUES (?, ?, ?)");
                foreach(['HUACHO','HUAURA','MEDIO MUNDO'] as $s) {
                     $stmtInsM->bind_param("sss", $s, $codMar, $nomMar); $stmtInsM->execute();
                }
            }
        }
        if (!empty($codMar) && ctype_digit($codMar) && strlen($codMar) < 4) { $codMar = str_pad($codMar, 4, '0', STR_PAD_LEFT); }

        // --- C. GESTIÓN DE PRINCIPIO ACTIVO ---
        if (empty($codPrin) && !empty($nomPrin)) {
            $r = $conn->query("SELECT MAX(CAST(SUBSTRING(cod_sub, 2) AS UNSIGNED)) as m FROM principios_zeth WHERE cod_sub LIKE 'P%'");
            $next = ($r->fetch_assoc()['m'] ?? 0) + 1;
            $codPrin = 'P' . str_pad($next, 4, '0', STR_PAD_LEFT);
        }
        if (!empty($codPrin) && !empty($nomPrin)) {
            $stmtPrin = $conn->prepare("INSERT INTO principios_zeth (sede, cod_sub, des_sub) VALUES (?,?,?) ON DUPLICATE KEY UPDATE des_sub = VALUES(des_sub)");
            foreach(['HUACHO','HUAURA','MEDIO MUNDO'] as $s) {
                $stmtPrin->bind_param("sss", $s, $codPrin, $nomPrin); $stmtPrin->execute();
            }
        }

        // --- D. ACTUALIZAR PRODUCTOS ---
        $ids = ['HUAURA' => $_POST['id_huaura'] ?? null, 'HUACHO' => $_POST['id_huacho'] ?? null, 'MEDIO MUNDO' => $_POST['id_mmundo'] ?? null];

        if (!empty($codPrin)) {
            $log = [];
            $stmtUpd = $conn->prepare("UPDATE inventario_zeth SET nombre=?, sublin=?, lineaz=?, marcaz=?, costo=?, precio=? WHERE id=?");
            $stmtIns = $conn->prepare("INSERT INTO inventario_zeth (codigo, nombre, sublin, lineaz, marcaz, costo, precio, stock, sede) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)");

            foreach ($ids as $sede => $id) {
                if (!empty($id)) {
                    $stmtUpd->bind_param("ssssddi", $nuevoNombre, $codPrin, $codLin, $codMar, $costo, $precio, $id);
                    if($stmtUpd->execute()) $log[] = "$sede";
                } else {
                    $qMax = $conn->query("SELECT MAX(CAST(codigo AS UNSIGNED)) as max_cod FROM inventario_zeth WHERE sede = '$sede'");
                    $nextCodNum = ($qMax->fetch_assoc()['max_cod'] ?? 0) + 1;
                    $nuevoCodigo = str_pad($nextCodNum, 5, '0', STR_PAD_LEFT); 
                    $stmtIns->bind_param("sssssdds", $nuevoCodigo, $nuevoNombre, $codPrin, $codLin, $codMar, $costo, $precio, $sede);
                    if($stmtIns->execute()) $log[] = "$sede (Nuevo)";
                }
            }
            echo json_encode(['success' => true, 'msg' => "Proceso Completado en: " . implode(', ', $log)]);
        } else {
            echo json_encode(['success' => false, 'msg' => "Error: Falta Principio Activo."]);
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestión de Homologación | Clínica</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<style>
    :root {
        --primary: #2563eb; --secondary: #64748b; --bg-body: #f1f5f9; --surface: #ffffff; --border: #cbd5e1;
        --text-main: #0f172a; --text-muted: #64748b; --success: #10b981; --warning: #f59e0b; --danger: #ef4444;
        --radius: 8px; --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    body { font-family: 'Inter', sans-serif; background-color: var(--bg-body); color: var(--text-main); margin: 0; padding-bottom: 80px; font-size: 14px; }
    .app-header { background: var(--surface); padding: 1rem 2rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    .app-title { font-size: 1.25rem; font-weight: 700; display: flex; align-items: center; gap: 10px; }
    .btn-back { color: var(--secondary); text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 6px; }
    .main-grid { display: grid; grid-template-columns: 1.2fr 1fr 1fr; gap: 24px; max-width: 1600px; margin: 24px auto; padding: 0 24px; }
    .panel { background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow); border: 1px solid var(--border); display: flex; flex-direction: column; overflow: hidden; }
    .panel-header { padding: 16px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc; }
    .panel-title { font-weight: 700; font-size: 0.9rem; text-transform: uppercase; color: var(--secondary); display: flex; align-items: center; gap: 8px;}
    .badge-sede { padding: 4px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: 700; color: white; }
    .bg-huaura { background: var(--primary); } .bg-huacho { background: var(--success); } .bg-mmundo { background: var(--warning); }
    .panel-body { padding: 20px; flex: 1; display: flex; flex-direction: column; gap: 16px; }
    .form-group { position: relative; } .form-label { display: block; font-size: 0.75rem; font-weight: 600; color: var(--secondary); margin-bottom: 6px; }
    .input-control { width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 6px; font-size: 0.9rem; box-sizing: border-box; }
    .input-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
    .row-split { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .panel-master { border-top: 4px solid var(--primary); }
    .panel-master .input-control { background-color: #f8fafc; border-color: #e2e8f0; font-weight: 500; }
    .panel-master .input-control:focus { background-color: white; border-color: var(--primary); }
    .result-card { background: #f1f5f9; border: 1px solid var(--border); border-radius: 8px; padding: 16px; display: none; margin-top:5px; }
    .rc-header { display: flex; justify-content: space-between; margin-bottom: 8px; }
    .rc-code { font-family: monospace; font-size: 0.8rem; background: #e2e8f0; padding: 2px 6px; border-radius: 4px; color: var(--secondary); font-weight: 700; }
    .rc-title { font-weight: 700; font-size: 1rem; margin-bottom: 12px; }
    .rc-meta { display: grid; gap: 6px; margin-bottom: 12px; }
    .rc-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 0.8rem; background: white; padding: 4px 8px; border-radius: 4px; border: 1px solid #e2e8f0; }
    .rc-price-box { background: white; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; display: flex; justify-content: space-between; align-items: center; font-weight: 600; }
    .btn-close-card { background: none; border: none; cursor: pointer; color: var(--secondary); padding: 4px; border-radius: 50%; }
    .btn-promote { width: 100%; margin-top: 12px; padding: 8px; background: white; border: 1px solid var(--border); color: var(--secondary); font-weight: 600; font-size: 0.8rem; border-radius: 6px; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px; }
    .btn-promote:hover { border-color: var(--primary); color: var(--primary); background: #eff6ff; }
    .action-bar { position: fixed; bottom: 0; left: 0; right: 0; background: var(--surface); padding: 16px; border-top: 1px solid var(--border); display: flex; justify-content: center; z-index: 90; box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.05); }
    .btn-sync { background: var(--text-main); color: white; border: none; padding: 12px 32px; border-radius: 8px; font-size: 1rem; font-weight: 600; display: flex; align-items: center; gap: 12px; cursor: pointer; transition: 0.2s; }
    .btn-sync:hover { background: #000; }
    .btn-sync:disabled { background: #cbd5e1; cursor: not-allowed; }
    .ui-autocomplete { background: white; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); padding: 6px 0; max-height: 300px; overflow-y: auto; overflow-x: hidden; z-index: 9999 !important; }
    .ui-menu-item-wrapper { padding: 10px 16px !important; border-bottom: 1px solid #f1f5f9; display: flex !important; justify-content: space-between; align-items: center; }
    .ui-state-active { background: #eff6ff !important; border: none !important; color: var(--primary) !important; margin: 0 !important; }
    .ac-stock-badge { font-size: 0.75rem; font-weight: 700; padding: 4px 10px; border-radius: 20px; display: flex; align-items: center; gap: 6px; }
    .stk-high { background: #dcfce7; color: #15803d; } .stk-low { background: #fee2e2; color: #b91c1c; }
    .badge-new { background: var(--warning); color: #fff; font-size: 0.65rem; padding: 2px 6px; border-radius: 4px; display: none; }
</style>
</head>
<body>

<header class="app-header">
    <div class="app-title"><i class="ph-duotone ph-circles-three-plus" style="font-size: 1.8rem; color: var(--primary);"></i><span>Homologación Maestra</span></div>
    <a href="admin_inventario.php" class="btn-back"><i class="ph ph-arrow-left"></i> Volver</a>
</header>

<div class="main-grid">
    <div class="panel panel-master">
        <div class="panel-header"><div class="panel-title"><i class="ph-fill ph-crown"></i> Maestro (Huaura)</div><span class="badge-sede bg-huaura">PRINCIPAL</span></div>
        <div class="panel-body">
            <div class="form-group"><input type="text" class="input-control" id="src_huaura" placeholder="Buscar producto maestro..."></div>
            <div id="master_panel" style="display:none; flex-direction:column; gap:16px; border-top:1px solid var(--border); padding-top:16px;">
                <input type="hidden" id="id_huaura">
                <div class="form-group"><label class="form-label">Nombre Comercial</label><input type="text" class="input-control" id="m_nombre"></div>
                <div class="form-group"><label class="form-label">Principio Activo (Zeth19) <span id="bdg_prin" class="badge-new">NUEVO</span></label><input type="text" class="input-control" id="src_prin"><input type="hidden" id="m_prin_cod"></div>
                <div class="row-split">
                    <div class="form-group"><label class="form-label">Linea (Categoria))</label><input type="text" class="input-control" id="src_linea"><input type="hidden" id="m_linea_cod"></div>
                    <div class="form-group"><label class="form-label">Laboratorio (Subcategoria)</label><input type="text" class="input-control" id="src_marca"><input type="hidden" id="m_marca_cod"></div>
                </div>
                <div class="row-split" style="background: #f0f9ff; padding: 12px; border-radius: 8px; border: 1px solid #bae6fd;">
                    <div class="form-group"><label class="form-label" style="color:#0369a1;">Ul. Cost (S/)</label><input type="number" step="0.01" class="input-control" id="m_costo" style="font-weight:700;"></div>
                    <div class="form-group">
                        <label class="form-label" style="color:#0369a1;">P.V.P (S/)</label>
                        <div style="position:relative;"><input type="number" step="0.01" class="input-control" id="m_precio" style="font-weight:700;">
                        <span id="m_ganancia" style="position:absolute; right:5px; top:-20px; font-size:0.7rem; color:#0284c7; font-weight:bold;">-</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="panel">
        <div class="panel-header"><div class="panel-title"><i class="ph-fill ph-hospital"></i> Huacho</div><span class="badge-sede bg-huacho">SUCURSAL</span></div>
        <div class="panel-body">
            <div class="form-group"><input type="text" class="input-control" id="src_huacho" placeholder="Buscar coincidencia..."></div>
            <div id="c_huacho" class="result-card">
                <div class="rc-header"><span class="rc-code" id="c_huacho_cod"></span><button class="btn-close-card" onclick="resetCard('huacho')"><i class="ph-bold ph-x"></i></button></div>
                <div class="rc-title" id="c_huacho_nom"></div>
                <div class="rc-meta"><span class="rc-badge"><i class="ph-fill ph-pill"></i> <span id="c_huacho_prin"></span></span><span class="rc-badge"><i class="ph-fill ph-tag"></i> <span id="c_huacho_mar"></span></span></div>
                <div class="rc-price-box"><div>C: <span id="c_huacho_costo"></span> | V: <span id="c_huacho_precio"></span></div><div style="background:#e0f2fe; color:#0284c7; padding:2px 8px; border-radius:12px; font-size:0.75rem;" id="g_huacho"></div></div>
                <button class="btn-promote" onclick="promoteToMaster('huacho')"><i class="ph-bold ph-arrow-up"></i> Usar como Base</button>
                <input type="hidden" id="id_huacho"><input type="hidden" id="data_huacho_full">
            </div>
        </div>
    </div>
    <div class="panel">
        <div class="panel-header"><div class="panel-title"><i class="ph-fill ph-hospital"></i> M. Mundo</div><span class="badge-sede bg-mmundo">SUCURSAL</span></div>
        <div class="panel-body">
            <div class="form-group"><input type="text" class="input-control" id="src_mm" placeholder="Buscar coincidencia..."></div>
            <div id="c_mm" class="result-card">
                <div class="rc-header"><span class="rc-code" id="c_mm_cod"></span><button class="btn-close-card" onclick="resetCard('mm')"><i class="ph-bold ph-x"></i></button></div>
                <div class="rc-title" id="c_mm_nom"></div>
                <div class="rc-meta"><span class="rc-badge"><i class="ph-fill ph-pill"></i> <span id="c_mm_prin"></span></span><span class="rc-badge"><i class="ph-fill ph-tag"></i> <span id="c_mm_mar"></span></span></div>
                <div class="rc-price-box"><div>C: <span id="c_mm_costo"></span> | V: <span id="c_mm_precio"></span></div><div style="background:#e0f2fe; color:#0284c7; padding:2px 8px; border-radius:12px; font-size:0.75rem;" id="g_mm"></div></div>
                <button class="btn-promote" onclick="promoteToMaster('mm')"><i class="ph-bold ph-arrow-up"></i> Usar como Base</button>
                <input type="hidden" id="id_mm"><input type="hidden" id="data_mm_full">
            </div>
        </div>
    </div>
</div>

<div class="action-bar">
    <button id="btn_sync" class="btn-sync" onclick="syncAll()" disabled><i class="ph-bold ph-arrows-left-right"></i><span>SINCRONIZAR PRODUCTO EN TODAS LAS SEDES</span></button>
</div>

<script>
    const fmt = n => "S/ " + parseFloat(n||0).toFixed(2);
    
    // CONFIGURACIÓN DE NOTIFICACIONES (SWEETALERT2)
    const Toast = Swal.mixin({
      toast: true, position: 'top-end', showConfirmButton: false, timer: 3000,
      timerProgressBar: true,
      didOpen: (toast) => { toast.addEventListener('mouseenter', Swal.stopTimer); toast.addEventListener('mouseleave', Swal.resumeTimer); }
    });

    function renderProducto(ul, item) {
        let stockClass = item.stock > 0 ? "stk-high" : "stk-low";
        let stockIcon = item.stock > 0 ? '<i class="ph-fill ph-package"></i>' : '<i class="ph-fill ph-warning-circle"></i>';
        let html = `<div style="display:flex; flex-direction:column;"><span style="font-weight:600;">${item.nombre_puro}</span><div style="font-size:0.75rem; color:#64748b; margin-top:2px;">COD: <b>${item.codigo}</b> • <span style="color:${item.ganancia_pct > 20 ? 'green' : 'orange'}">📈 ${item.ganancia_pct}%</span></div></div><div class="ac-stock-badge ${stockClass}">${stockIcon} ${item.stock}</div>`;
        return $("<li>").append(`<div>${html}</div>`).appendTo(ul);
    };
    function calcGananciaMaster() {
        let c = parseFloat($("#m_costo").val()) || 0; let v = parseFloat($("#m_precio").val()) || 0;
        if(v > 0) { let pct = ((v - c) / v * 100).toFixed(1); $("#m_ganancia").text("M.B : " + pct + "%"); } else { $("#m_ganancia").text("-"); }
    }
    $("#m_costo, #m_precio").on('input', calcGananciaMaster);

    $("#src_huaura").autocomplete({ source: (req, res) => $.post('admin_homologar.php', {action:'search_prod', sede:'HUAURA', term:req.term}, res, 'json'), minLength: 2,
        select: (e, ui) => {
            $("#id_huaura").val(ui.item.id); $("#m_nombre").val(ui.item.nombre_puro);
            $("#src_prin").val(ui.item.principio_txt); $("#m_prin_cod").val(ui.item.principio_cod);
            $("#src_linea").val(ui.item.linea_txt); $("#m_linea_cod").val(ui.item.linea_cod);
            $("#src_marca").val(ui.item.marca_txt); $("#m_marca_cod").val(ui.item.marca_cod);
            $("#m_costo").val(ui.item.costo); $("#m_precio").val(ui.item.precio);
            calcGananciaMaster(); checkPrin(); $("#master_panel").slideDown(); $("#btn_sync").prop('disabled', false); return false;
        }
    }).autocomplete("instance")._renderItem = renderProducto;
    $("#src_huaura").on("autocompleteselect", function(event, ui) { $(this).val(ui.item.nombre_puro); });

    $("#src_prin").autocomplete({ source: (req, res) => $.post('admin_homologar.php', {action:'search_prin', term:req.term}, res, 'json'), select: (e, ui) => { $("#m_prin_cod").val(ui.item.id); checkPrin(); }, change: (e, ui) => { if(!ui.item) $("#m_prin_cod").val(''); checkPrin(); } });
    $("#src_linea").autocomplete({ source: (req, res) => $.post('admin_homologar.php', {action:'search_linea', term:req.term}, res, 'json'), select: (e, ui) => $("#m_linea_cod").val(ui.item.id), change: (e, ui) => { if(!ui.item) $("#m_linea_cod").val(''); } });
    $("#src_marca").autocomplete({ source: (req, res) => $.post('admin_homologar.php', {action:'search_marca', term:req.term}, res, 'json'), select: (e, ui) => $("#m_marca_cod").val(ui.item.id), change: (e, ui) => { if(!ui.item) $("#m_marca_cod").val(''); } });
    function checkPrin() { if($("#m_prin_cod").val() === '') $("#bdg_prin").fadeIn(); else $("#bdg_prin").fadeOut(); }
    $("#src_prin").on('input', () => { $("#m_prin_cod").val(''); checkPrin(); });

    function setupDest(sede, inp, card, pfx, pillId, dataHiddenId) {
        $(inp).autocomplete({ source: (req, res) => $.post('admin_homologar.php', {action:'search_prod', sede:sede, term:req.term}, res, 'json'), minLength: 2,
            select: (e, ui) => {
                $("#id_"+pfx).val(ui.item.id); $("#c_"+pfx+"_cod").text(ui.item.codigo); $("#c_"+pfx+"_nom").text(ui.item.nombre_puro);
                $("#c_"+pfx+"_prin").text(ui.item.principio_txt || '-'); $("#c_"+pfx+"_mar").text(ui.item.marca_txt || '-');
                $("#c_"+pfx+"_costo").text(fmt(ui.item.costo)); $("#c_"+pfx+"_precio").text(fmt(ui.item.precio));
                $(pillId).html(`<i class="ph-bold ph-trend-up"></i> ${ui.item.ganancia_pct}%`); $(dataHiddenId).val(JSON.stringify(ui.item));
                $(card).slideDown(); $(inp).val(''); return false;
            }
        }).autocomplete("instance")._renderItem = renderProducto;
    }
    setupDest('HUACHO', '#src_huacho', '#c_huacho', 'huacho', '#g_huacho', '#data_huacho_full');
    setupDest('MEDIO MUNDO', '#src_mm', '#c_mm', 'mm', '#g_mm', '#data_mm_full');
    function resetCard(pfx) { $("#id_"+pfx).val(''); $("#c_"+pfx).slideUp(); }

    function promoteToMaster(pfx) {
        let jsonStr = $("#data_"+pfx+"_full").val(); if(!jsonStr) return; let item = JSON.parse(jsonStr);
        $("#m_nombre").val(item.nombre_puro); $("#src_prin").val(item.principio_txt); $("#m_prin_cod").val(item.principio_cod);
        $("#src_linea").val(item.linea_txt); $("#m_linea_cod").val(item.linea_cod);
        $("#src_marca").val(item.marca_txt); $("#m_marca_cod").val(item.marca_cod);
        $("#m_costo").val(item.costo); $("#m_precio").val(item.precio);
        $("#id_huaura").val(''); 
        calcGananciaMaster(); checkPrin(); $("#master_panel").slideDown(); $("#btn_sync").prop('disabled', false);
        Toast.fire({ icon: 'info', title: 'Datos cargados al Panel Maestro' });
        $('html, body').animate({ scrollTop: $("#master_panel").offset().top - 100 }, 500);
    }

    function syncAll() {
        const d = {
            action: 'sync', nombre: $("#m_nombre").val(), 
            cod_principio: $("#m_prin_cod").val(), nom_principio: $("#src_prin").val(),
            cod_linea: $("#m_linea_cod").val(), nom_linea: $("#src_linea").val(),
            cod_marca: $("#m_marca_cod").val(), nom_marca: $("#src_marca").val(),
            costo: $("#m_costo").val(), precio: $("#m_precio").val(),
            id_huaura: $("#id_huaura").val(), id_huacho: $("#id_huacho").val(), id_mmundo: $("#id_mm").val()
        };
        
        if(d.nom_principio === '') return Swal.fire({ icon: 'warning', title: 'Faltan datos', text: 'El Principio Activo es obligatorio.' });
        if(d.costo === '' || d.precio === '') return Swal.fire({ icon: 'warning', title: 'Faltan datos', text: 'Costos y Precios son obligatorios.' });

        let htmlMsg = `<div style="text-align:left; font-size:0.9rem;">
            ${!d.id_huaura ? '<p>✅ Se creará en <b>HUAURA</b></p>' : ''}
            ${!d.id_huacho ? '<p>✅ Se creará en <b>HUACHO</b></p>' : ''}
            ${!d.id_mmundo ? '<p>✅ Se creará en <b>M. MUNDO</b></p>' : ''}
            <p style="margin-top:10px; color:#64748b;">Los datos se actualizarán en todas las sedes.</p>
        </div>`;

        Swal.fire({
            title: '¿Confirmar Sincronización?',
            html: htmlMsg,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Sí, Sincronizar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                let btn = $("#btn_sync"); let originalContent = btn.html(); btn.prop('disabled',true).html('<i class="ph ph-spinner ph-spin"></i> Procesando...');
                $.post('admin_homologar.php', d, (res) => {
                    if(res.success) {
                        Toast.fire({ icon: 'success', title: 'Sincronización completada' });
                        $("#src_huaura").val('').focus(); $("#master_panel").slideUp();
                        resetCard('huacho'); resetCard('mm'); btn.prop('disabled',false).html(originalContent);
                    } else { 
                        Swal.fire({ icon: 'error', title: 'Error', text: res.msg });
                        btn.prop('disabled',false).html(originalContent); 
                    }
                }, 'json');
            }
        });
    }

    // --- NUEVA LÓGICA: CARGA AUTOMÁTICA DESDE URL (PARA ADMIN_INVENTARIO.PHP) ---
    $(document).ready(function() {
        const urlParams = new URLSearchParams(window.location.search);
        const searchCode = urlParams.get('search_code');

        if (searchCode) {
            // Ponemos visualmente el código
            $("#src_huaura").val(searchCode);

            // Simulamos la búsqueda en backend para obtener los datos
            $.post('admin_homologar.php', {
                action: 'search_prod',
                sede: 'HUAURA',
                term: searchCode
            }, function(data) {
                if (data && data.length > 0) {
                    // Intentamos match exacto por código, sino el primero
                    let item = data.find(x => x.codigo === searchCode) || data[0];

                    // LLENAMOS EL PANEL MAESTRO (Igual que al seleccionar del autocomplete)
                    $("#id_huaura").val(item.id);
                    $("#m_nombre").val(item.nombre_puro);
                    $("#src_prin").val(item.principio_txt); $("#m_prin_cod").val(item.principio_cod);
                    $("#src_linea").val(item.linea_txt); $("#m_linea_cod").val(item.linea_cod);
                    $("#src_marca").val(item.marca_txt); $("#m_marca_cod").val(item.marca_cod);
                    $("#m_costo").val(item.costo); $("#m_precio").val(item.precio);

                    calcGananciaMaster();
                    checkPrin();
                    $("#master_panel").slideDown();
                    $("#btn_sync").prop('disabled', false);

                    // Limpiamos la URL para evitar recargas en bucle
                    window.history.replaceState({}, document.title, "admin_homologar.php");
                }
            }, 'json');
        }
    });
</script>
</body>
</html>