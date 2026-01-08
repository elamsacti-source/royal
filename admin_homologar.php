<?php
// admin_homologar.php
// HOMOLOGADOR SUPREMO: PRODUCTOS + PRECIOS + CATEGORIAS (Z14) + MARCAS (Z15)
// MEJORA: Visualización de Stock destacada en el buscador.

include_once 'session.php';
include_once 'db_config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') { header('Location: login.php'); exit; }

// --- BACKEND ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    // 1. BUSCAR PRODUCTOS (Trae todo: Precios, Stock, Z14, Z15)
    if ($action === 'search_prod') {
        $sede = $conn->real_escape_string($_POST['sede']);
        $term = $conn->real_escape_string($_POST['term']);
        
        // Consulta incluyendo STOCK
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
            
            // Label simple para el input al seleccionarse
            $labelSimple = $r['nombre'];

            $data[] = [
                'id' => $r['id'],
                'label' => $labelSimple, // Lo que se pone en el input al seleccionar
                'value' => $labelSimple,
                'nombre_puro' => $r['nombre'],
                'codigo' => $r['codigo'],
                'stock' => $stockVal, // Dato numérico para validaciones o colores
                
                'principio_txt' => $r['principio'],
                'principio_cod' => $r['cod_prin'],
                
                'linea_txt' => $r['linea'],
                'linea_cod' => $r['cod_lin'],
                
                'marca_txt' => $r['marca'],
                'marca_cod' => $r['cod_mar'],
                
                'costo' => $r['costo'],
                'precio' => $r['precio']
            ];
        }
        echo json_encode($data);
        exit;
    }

    // 2. BUSCADORES AUXILIARES (Para los autocompletes maestros)
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

    // 3. SINCRONIZAR TODO (Producto, Precios, Categoria, Marca)
    if ($action === 'sync') {
        $nuevoNombre = mb_strtoupper(trim($_POST['nombre']), 'UTF-8');
        
        $codPrin = $_POST['cod_principio'];
        $nomPrin = mb_strtoupper(trim($_POST['nom_principio']), 'UTF-8');
        
        $codLin  = $_POST['cod_linea'];
        $codMar  = $_POST['cod_marca'];
        
        $costo   = floatval($_POST['costo']);
        $precio  = floatval($_POST['precio']);

        // A. Crear Principio si es nuevo
        if (empty($codPrin) && !empty($nomPrin)) {
            $r = $conn->query("SELECT MAX(CAST(SUBSTRING(cod_sub, 2) AS UNSIGNED)) as m FROM principios_zeth WHERE cod_sub LIKE 'P%'");
            $next = ($r->fetch_assoc()['m'] ?? 0) + 1;
            $codPrin = 'P' . str_pad($next, 4, '0', STR_PAD_LEFT);
            $stmtI = $conn->prepare("INSERT INTO principios_zeth (sede, cod_sub, des_sub) VALUES (?,?,?)");
            foreach(['HUACHO','HUAURA','MEDIO MUNDO'] as $s) {
                if($conn->query("SELECT id FROM principios_zeth WHERE sede='$s' AND cod_sub='$codPrin'")->num_rows==0){
                    $stmtI->bind_param("sss", $s, $codPrin, $nomPrin); $stmtI->execute();
                }
            }
        }

        $ids = [
            'HUAURA' => $_POST['id_huaura'] ?? null,
            'HUACHO' => $_POST['id_huacho'] ?? null,
            'MEDIO MUNDO' => $_POST['id_mmundo'] ?? null
        ];

        // B. Update Masivo
        if (!empty($codPrin)) {
            $sql = "UPDATE inventario_zeth SET nombre=?, sublin=?, lineaz=?, marcaz=?, costo=?, precio=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $log = [];
            foreach ($ids as $sede => $id) {
                if ($id) {
                    $stmt->bind_param("ssssddi", $nuevoNombre, $codPrin, $codLin, $codMar, $costo, $precio, $id);
                    if($stmt->execute()) $log[] = $sede;
                }
            }
            echo json_encode(['success' => true, 'msg' => "Sincronizado: Nombre, Precios, Cat y Marca. (" . implode(', ', $log) . ")"]);
        } else {
            echo json_encode(['success' => false, 'msg' => "Falta Principio Activo."]);
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
<title>Homologador Total</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<style>
    body { font-family: 'Inter', sans-serif; background: #f1f5f9; padding: 20px; color: #1e293b; }
    .container { max-width: 1400px; margin: 0 auto; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 20px; }
    
    .grid-cols { display: grid; grid-template-columns: 1.3fr 1fr 1fr; gap: 20px; }
    
    /* PANELES */
    .panel { padding: 15px; border-radius: 10px; border: 1px solid #cbd5e1; display: flex; flex-direction: column; gap: 8px; background:white; position:relative;}
    .p-huaura { background: #eff6ff; border-color: #3b82f6; border-width: 2px; } /* Azul Maestro */
    .p-huacho { background: #f0fdf4; border-color: #bbf7d0; } /* Verde */
    .p-mmundo { background: #fffbeb; border-color: #fde68a; } /* Amarillo */

    .p-title { font-weight: 800; font-size: 0.85rem; text-transform: uppercase; color: #334155; display:flex; justify-content:space-between; }
    .tag { padding: 2px 6px; border-radius: 4px; font-size: 0.65rem; color: white; font-weight: bold; }
    
    label { font-size: 0.7rem; font-weight: 700; color: #64748b; margin-top: 5px; display:block; }
    input.inp { width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box; }
    input.inp:focus { outline: 2px solid #3b82f6; border-color: #3b82f6; }
    
    /* INPUTS MAESTROS */
    .inp-master { font-weight: 600; color: #1e3a8a; border-color: #93c5fd; }

    /* TARJETAS RESULTADO */
    .card-res { background: white; padding: 10px; border-radius: 6px; border: 1px solid #e2e8f0; display: none; margin-top:5px; border-left: 3px solid #10b981; }
    .card-res.active { display: block; }
    .cr-head { display:flex; justify-content:space-between; align-items:flex-start; }
    .cr-cod { font-size:0.7rem; color:#94a3b8; font-weight:bold; }
    .cr-nom { font-weight:700; font-size:0.9rem; color:#0f172a; line-height:1.2; }
    .cr-meta { font-size:0.75rem; color:#475569; margin-top:4px; display:flex; flex-direction:column; gap:2px;}
    .cr-price { font-size:0.8rem; font-weight:800; color:#334155; margin-top:5px; border-top:1px solid #f1f5f9; padding-top:4px; display:flex; justify-content:space-between;}
    
    .btn-close { cursor:pointer; color:red; font-weight:bold; border:none; background:none; font-size:1rem; padding:0; }

    .master-edit { display: none; flex-direction: column; gap: 8px; margin-top: 10px; border-top: 1px solid #bfdbfe; padding-top: 10px; }
    
    .row-2 { display: flex; gap: 10px; }
    .col-50 { flex: 1; }

    .btn-sync { grid-column: 1 / -1; background: #1e293b; color: white; border: none; padding: 15px; border-radius: 8px; font-size: 1rem; font-weight: 700; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 20px; box-shadow:0 4px 6px rgba(0,0,0,0.1); }
    .btn-sync:hover { background: #0f172a; }
    .btn-sync:disabled { background: #cbd5e1; cursor: not-allowed; }

    .ui-autocomplete { max-height: 250px; overflow-y: auto; overflow-x: hidden; z-index: 9999; font-size: 0.8rem; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
    .alert-box { position: fixed; top: 20px; right: 20px; padding: 15px 25px; background: #10b981; color: white; font-weight: bold; border-radius: 8px; display: none; z-index: 10000; }
    .badge-new { background:#f97316; color:white; font-size:0.6rem; padding:1px 4px; border-radius:3px; position:absolute; right:25px; top:35px; display:none;}

    /* ESTILOS PARA LA LISTA DE PRODUCTOS (STOCK DIFERENCIADO) */
    .ui-menu-item-wrapper { display: flex !important; justify-content: space-between; align-items: center; padding: 6px 10px !important; border-bottom: 1px solid #f1f5f9; }
    .prod-info { display:flex; flex-direction:column; }
    .prod-name { font-weight: 700; color: #1e293b; font-size: 0.85rem; }
    .prod-cod { font-size: 0.7rem; color: #64748b; }
    .prod-stock { 
        font-size: 0.75rem; font-weight: 800; padding: 2px 8px; border-radius: 12px; 
        display: flex; align-items: center; gap: 4px; min-width: 80px; justify-content: center;
    }
    .stk-ok { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; } /* Verde */
    .stk-low { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; } /* Rojo */

</style>
</head>
<body>

<div id="alertBox" class="alert-box"></div>

<div class="container">
    <div class="header">
        <h2 style="margin:0"><i class="ph ph-arrows-merge"></i> Homologador Maestro</h2>
        <a href="admin_inventario.php" style="text-decoration:none; color:#64748b; font-weight:600;"><i class="ph ph-arrow-left"></i> Volver</a>
    </div>

    <div class="grid-cols">
        <div class="panel p-huaura">
            <div class="p-title">1. Huaura <span class="tag" style="background:#2563eb">MAESTRO</span></div>
            <input type="text" class="inp" id="src_huaura" placeholder="Buscar producto base...">
            
            <div id="master_panel" class="master-edit">
                <input type="hidden" id="id_huaura">
                
                <label>Nombre Final:</label>
                <input type="text" class="inp inp-master" id="m_nombre">
                
                <label>Principio Activo (Z19):</label>
                <input type="text" class="inp inp-master" id="src_prin">
                <input type="hidden" id="m_prin_cod">
                <span id="bdg_prin" class="badge-new">NUEVO</span>

                <div class="row-2">
                    <div class="col-50">
                        <label>Categoría (Z14):</label>
                        <input type="text" class="inp inp-master" id="src_linea">
                        <input type="hidden" id="m_linea_cod">
                    </div>
                    <div class="col-50">
                        <label>Marca (Z15):</label>
                        <input type="text" class="inp inp-master" id="src_marca">
                        <input type="hidden" id="m_marca_cod">
                    </div>
                </div>

                <div class="row-2" style="background:#dbeafe; padding:8px; border-radius:6px; margin-top:5px;">
                    <div class="col-50">
                        <label style="color:#15803d; margin-top:0;">Costo S/:</label>
                        <input type="number" step="0.01" class="inp" id="m_costo" style="font-weight:bold;">
                    </div>
                    <div class="col-50">
                        <label style="color:#1d4ed8; margin-top:0;">Venta S/:</label>
                        <input type="number" step="0.01" class="inp" id="m_precio" style="font-weight:bold;">
                    </div>
                </div>
            </div>
        </div>

        <div class="panel p-huacho">
            <div class="p-title">2. Huacho <span class="tag" style="background:#10b981">DESTINO</span></div>
            <input type="text" class="inp" id="src_huacho" placeholder="Buscar equivalente...">
            <div id="c_huacho" class="card-res">
                <div class="cr-head">
                    <span class="cr-cod" id="c_huacho_cod"></span>
                    <button class="btn-close" onclick="resetCard('huacho')">×</button>
                </div>
                <div class="cr-nom" id="c_huacho_nom"></div>
                <div class="cr-meta">
                    <span>💊 <span id="c_huacho_prin"></span></span>
                    <span>📂 <span id="c_huacho_lin"></span></span>
                    <span>🏷️ <span id="c_huacho_mar"></span></span>
                </div>
                <div class="cr-price">
                    <span>C: <span id="c_huacho_costo"></span></span>
                    <span>V: <span id="c_huacho_precio"></span></span>
                </div>
                <input type="hidden" id="id_huacho">
            </div>
        </div>

        <div class="panel p-mmundo">
            <div class="p-title">3. M. Mundo <span class="tag" style="background:#f59e0b">DESTINO</span></div>
            <input type="text" class="inp" id="src_mm" placeholder="Buscar equivalente...">
            <div id="c_mm" class="card-res">
                <div class="cr-head">
                    <span class="cr-cod" id="c_mm_cod"></span>
                    <button class="btn-close" onclick="resetCard('mm')">×</button>
                </div>
                <div class="cr-nom" id="c_mm_nom"></div>
                <div class="cr-meta">
                    <span>💊 <span id="c_mm_prin"></span></span>
                    <span>📂 <span id="c_mm_lin"></span></span>
                    <span>🏷️ <span id="c_mm_mar"></span></span>
                </div>
                <div class="cr-price">
                    <span>C: <span id="c_mm_costo"></span></span>
                    <span>V: <span id="c_mm_precio"></span></span>
                </div>
                <input type="hidden" id="id_mm">
            </div>
        </div>

        <button id="btn_sync" class="btn-sync" onclick="syncAll()" disabled>
            <i class="ph ph-arrows-left-right"></i> SINCRONIZAR TODO (DATOS + PRECIOS + CATEGORÍAS)
        </button>
    </div>
</div>

<script>
    const fmt = n => "S/ " + parseFloat(n||0).toFixed(2);

    // FUNCIÓN PARA RENDERIZAR ITEMS BONITOS (CON STOCK DESTACADO)
    function renderProducto(ul, item) {
        let stockClass = item.stock > 0 ? "stk-ok" : "stk-low";
        let stockIcon = item.stock > 0 ? "📦" : "⚠️";
        
        return $("<li>")
            .append(`<div>
                <div class="prod-info">
                    <span class="prod-name">${item.nombre_puro}</span>
                    <span class="prod-cod">COD: ${item.codigo}</span>
                </div>
                <div class="prod-stock ${stockClass}">
                    ${stockIcon} ${item.stock}
                </div>
            </div>`)
            .appendTo(ul);
    };

    // 1. MAESTRO HUAURA
    $("#src_huaura").autocomplete({
        source: (req, res) => $.post('admin_homologar.php', {action:'search_prod', sede:'HUAURA', term:req.term}, res, 'json'),
        select: (e, ui) => {
            $("#id_huaura").val(ui.item.id);
            $("#m_nombre").val(ui.item.nombre_puro);
            
            // Datos adicionales
            $("#src_prin").val(ui.item.principio_txt); $("#m_prin_cod").val(ui.item.principio_cod);
            $("#src_linea").val(ui.item.linea_txt); $("#m_linea_cod").val(ui.item.linea_cod);
            $("#src_marca").val(ui.item.marca_txt); $("#m_marca_cod").val(ui.item.marca_cod);
            $("#m_costo").val(ui.item.costo); $("#m_precio").val(ui.item.precio);

            checkPrin();
            $("#master_panel").css('display','flex');
            $("#btn_sync").prop('disabled', false);
            return false; // Evita que se llene el input con el valor por defecto
        }
    }).autocomplete("instance")._renderItem = renderProducto; 
    
    // Al seleccionar, ponemos el nombre limpio en el input
    $("#src_huaura").on("autocompleteselect", function(event, ui) {
        $(this).val(ui.item.nombre_puro);
    });

    // AUTOCOMPLETES MAESTROS AUXILIARES
    $("#src_prin").autocomplete({
        source: (req, res) => $.post('admin_homologar.php', {action:'search_prin', term:req.term}, res, 'json'),
        select: (e, ui) => { $("#m_prin_cod").val(ui.item.id); checkPrin(); },
        change: (e, ui) => { if(!ui.item) $("#m_prin_cod").val(''); checkPrin(); }
    });
    $("#src_linea").autocomplete({
        source: (req, res) => $.post('admin_homologar.php', {action:'search_linea', term:req.term}, res, 'json'),
        select: (e, ui) => $("#m_linea_cod").val(ui.item.id)
    });
    $("#src_marca").autocomplete({
        source: (req, res) => $.post('admin_homologar.php', {action:'search_marca', term:req.term}, res, 'json'),
        select: (e, ui) => $("#m_marca_cod").val(ui.item.id)
    });

    function checkPrin() { $("#bdg_prin").toggle($("#m_prin_cod").val() === ''); }
    $("#src_prin").on('input', () => { $("#m_prin_cod").val(''); checkPrin(); });

    // 2. DESTINOS
    function setupDest(sede, inp, card, pfx) {
        $(inp).autocomplete({
            source: (req, res) => $.post('admin_homologar.php', {action:'search_prod', sede:sede, term:req.term}, res, 'json'),
            select: (e, ui) => {
                $("#id_"+pfx).val(ui.item.id);
                $("#c_"+pfx+"_cod").text(ui.item.codigo);
                $("#c_"+pfx+"_nom").text(ui.item.nombre_puro);
                $("#c_"+pfx+"_prin").text(ui.item.principio_txt || '-');
                $("#c_"+pfx+"_lin").text(ui.item.linea_txt || '-');
                $("#c_"+pfx+"_mar").text(ui.item.marca_txt || '-');
                $("#c_"+pfx+"_costo").text(fmt(ui.item.costo));
                $("#c_"+pfx+"_precio").text(fmt(ui.item.precio));
                $(card).show(); $(inp).val(''); return false;
            }
        }).autocomplete("instance")._renderItem = renderProducto;
    }
    setupDest('HUACHO', '#src_huacho', '#c_huacho', 'huacho');
    setupDest('MEDIO MUNDO', '#src_mm', '#c_mm', 'mm');

    function resetCard(pfx) { $("#id_"+pfx).val(''); $("#c_"+pfx).hide(); }

    // 3. SYNC
    function syncAll() {
        const d = {
            action: 'sync',
            nombre: $("#m_nombre").val(),
            cod_principio: $("#m_prin_cod").val(), nom_principio: $("#src_prin").val(),
            cod_linea: $("#m_linea_cod").val(), cod_marca: $("#m_marca_cod").val(),
            costo: $("#m_costo").val(), precio: $("#m_precio").val(),
            id_huaura: $("#id_huaura").val(), id_huacho: $("#id_huacho").val(), id_mmundo: $("#id_mm").val()
        };

        if(!d.id_huaura) return alert("Falta maestro Huaura");
        if(d.nom_principio === '') return alert("Principio obligatorio");
        if(d.costo === '' || d.precio === '') return alert("Precios obligatorios");

        $("#btn_sync").prop('disabled',true).html('⏳ Procesando...');
        $.post('admin_homologar.php', d, (res) => {
            if(res.success) {
                $("#alertBox").text("✅ "+res.msg).fadeIn().delay(3000).fadeOut();
                $("#src_huaura").val('').focus(); $("#master_panel").hide();
                resetCard('huacho'); resetCard('mm');
                $("#btn_sync").prop('disabled',false).html('<i class="ph ph-arrows-left-right"></i> SINCRONIZAR TODO');
            } else { alert(res.msg); $("#btn_sync").prop('disabled',false).text('Reintentar'); }
        }, 'json');
    }
</script>
</body>
</html>