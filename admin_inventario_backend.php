<?php
// admin_inventario_backend.php
// V59.0: Fix Filtro Sede (Mapeo Robusto + Trim) + Funcionalidad Full

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
if (($_GET['action'] ?? '') !== 'export') { header('Content-Type: application/json'); }
header("Access-Control-Allow-Origin: *");
error_reporting(0); 

include 'db_config.php';
date_default_timezone_set('America/Lima');

// MAPEO ROBUSTO (Frontend -> Base de Datos)
// Clave: Valor que envía el <select> del HTML
// Valor: Valor real en la columna 'sede' de la tabla 'inventario_zeth'
$SEDE_MAP_IN = [
    'HUAURA'      => 'HUAURA',
    'INTEGRA'     => 'HUACHO',      // Si seleccionan INTEGRA, busca HUACHO
    'HUACHO'      => 'HUACHO',      // Por si acaso
    'M.MUNDO'     => 'MEDIO MUNDO', // Si seleccionan M.MUNDO, busca MEDIO MUNDO
    'MEDIO MUNDO' => 'MEDIO MUNDO'  // Por si acaso
];

// MAPEO SALIDA (Base de Datos -> Frontend Visual)
$SEDE_MAP_OUT = [
    'HUAURA'      => 'HUAURA',
    'HUACHO'      => 'INTEGRA',
    'MEDIO MUNDO' => 'M.MUNDO'
];

$CATS_PRODUCTOS_SQL = "'FARMACIA','FARMACOS','SUPLEMENTOS','INSUMOS MEDICOS'";

$DICCIONARIO = [
    'CT'=>'Cargo por Transferencia', 'CI'=>'Cargo por toma de inventario', 'CC'=>'Cargo por Compra',
    'DT'=>'Descargo por Transferencia', 'DI'=>'Descargo por toma de inventario', 'CP'=>'Cargo por Compra',
    '01'=>'Cargo por Compra', '02'=>'Cargo por Transferencia', '03'=>'Descargo por Venta', 'VE'=>'Descargo por Venta',
    'DV'=>'Descargo por Venta', '16'=>'Saldo Inicial', 'TR'=>'Transferencia', 'CA'=>'Ajuste (+)', 'DA'=>'Ajuste (-)'
];

// --- HELPERS ---
function construirWhere($conn) {
    global $SEDE_MAP_IN, $CATS_PRODUCTOS_SQL;
    $where = "WHERE 1=1";

    // 1. FILTRO SEDE (CORREGIDO)
    $sedeInput = $_GET['sedes'] ?? '';
    if ($sedeInput && $sedeInput !== '') {
        // Limpiar entrada
        $sedeKey = strtoupper(trim($sedeInput));
        
        // Buscar en el mapa
        if (isset($SEDE_MAP_IN[$sedeKey])) {
            $sedeDB = $SEDE_MAP_IN[$sedeKey];
            $sedeDB = $conn->real_escape_string($sedeDB);
            // Usamos TRIM(T1.sede) para evitar problemas con espacios en la BD
            $where .= " AND TRIM(T1.sede) = '$sedeDB'";
        }
    }

    $tipo = $_GET['tipo'] ?? 'ALL';
    if ($tipo === 'PRODUCTO') $where .= " AND L.des_lin IN ($CATS_PRODUCTOS_SQL)";
    elseif ($tipo === 'SERVICIO') $where .= " AND L.des_lin NOT IN ($CATS_PRODUCTOS_SQL)";

    $lines = $_GET['lineas'] ?? 'ALL';
    if ($lines !== 'ALL' && !empty($lines)) {
        $arr = array_map(function($x) use($conn){ return "'".$conn->real_escape_string($x)."'"; }, explode(',', $lines));
        $where .= " AND L.des_lin IN (" . implode(',', $arr) . ")";
    }

    $brands = $_GET['marcas'] ?? 'ALL';
    if ($brands !== 'ALL' && !empty($brands)) {
        $arr = array_map(function($x) use($conn){ return "'".$conn->real_escape_string($x)."'"; }, explode(',', $brands));
        $where .= " AND M.des_mar IN (" . implode(',', $arr) . ")";
    }

    $q = $_GET['q_prod'] ?? '';
    if($q) $where .= " AND (T1.nombre LIKE '%$q%' OR T1.codigo LIKE '%$q%')";
    
    $qp = $_GET['q_prin'] ?? '';
    if($qp) $where .= " AND P.des_sub LIKE '%$qp%'";

    $hideZero = isset($_GET['hide_zero']) && $_GET['hide_zero'] === 'true';
    if ($hideZero) {
        $where .= " AND T1.stock != 0";
    }

    return $where;
}

$joins = "FROM inventario_zeth T1 
          LEFT JOIN principios_zeth P ON (T1.sublin = P.cod_sub AND T1.sede = P.sede)
          LEFT JOIN lineas_zeth L ON (T1.lineaz = L.cod_lin AND T1.sede = L.sede)
          LEFT JOIN marcas_zeth M ON (T1.marcaz = M.cod_mar AND T1.sede = M.sede)";

// --- ENDPOINTS ---

if (($_GET['action'] ?? '') === 'get_filters') {
    $lines=[]; $brands=[];
    $r=$conn->query("SELECT DISTINCT des_lin FROM lineas_zeth WHERE des_lin!='' ORDER BY des_lin"); while($x=$r->fetch_row()) $lines[]=$x[0];
    $r=$conn->query("SELECT DISTINCT des_mar FROM marcas_zeth WHERE des_mar!='' ORDER BY des_mar"); while($x=$r->fetch_row()) $brands[]=$x[0];
    echo json_encode(['lineas'=>$lines,'marcas'=>$brands]); exit;
}

// Búsqueda Mini para Comparativo
if (($_GET['action'] ?? '') === 'search_mini') {
    $term = $conn->real_escape_string($_GET['term'] ?? '');
    $sedeInput = strtoupper(trim($_GET['sede'] ?? ''));
    $sedeDB = $SEDE_MAP_IN[$sedeInput] ?? $sedeInput; // Usar mapa
    $sedeDB = $conn->real_escape_string($sedeDB);

    $res = $conn->query("SELECT codigo, nombre FROM inventario_zeth WHERE TRIM(sede) = '$sedeDB' AND (nombre LIKE '%$term%' OR codigo LIKE '%$term%') LIMIT 10");
    $data = [];
    if($res) while($r=$res->fetch_assoc()) $data[] = $r;
    echo json_encode($data); exit;
}

if (($_GET['action'] ?? '') === 'get_kardex') {
    $codigo = $conn->real_escape_string($_GET['codigo'] ?? '');
    $sedeInput = strtoupper(trim($_GET['sede'] ?? ''));
    $sedeDB = $SEDE_MAP_IN[$sedeInput] ?? $sedeInput; // Usar mapa
    $sedeDB = $conn->real_escape_string($sedeDB);
    
    $prod = $conn->query("SELECT nombre FROM inventario_zeth WHERE codigo = '$codigo' LIMIT 1")->fetch_assoc();
    $movs = [];
    $sql = "SELECT fecha_movimiento as fecha, tipo_movimiento as tipo, cantidad as cant 
            FROM kardex_zeth WHERE codigo_producto = '$codigo' AND TRIM(sede) = '$sedeDB' 
            ORDER BY fecha_movimiento DESC, id DESC LIMIT 50";
    $res = $conn->query($sql);
    if ($res) while($r = $res->fetch_assoc()) {
        $code = strtoupper(trim($r['tipo']));
        $r['tipo_nombre'] = $DICCIONARIO[$code] ?? $code;
        $r['color'] = ($r['cant'] < 0) ? '#ef4444' : '#10b981';
        $movs[] = $r;
    }
    echo json_encode(['producto' => $prod, 'movimientos' => $movs]); exit;
}

// EXPORTAR
if (($_GET['action'] ?? '') === 'export') {
    $filename = "Plantilla_Inv_".date('Ymd').".xls";
    header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    echo "\xEF\xBB\xBF"; 
    
    $where = construirWhere($conn);
    $sql = "SELECT T1.codigo, T1.nombre, L.des_lin, T1.stock
            $joins $where ORDER BY L.des_lin ASC, T1.nombre ASC";
    $res = $conn->query($sql);
    
    echo "<table border='1'>
            <tr style='background-color:#dbeafe; font-weight:bold;'>
                <th>ID</th><th>CATEGORIA</th><th>PRODUCTO</th><th>STOCK SIS</th>
                <th style='background-color:#fff;'>STOCK FISICO</th>
                <th style='background-color:#fff;'>VENC 1</th>
                <th style='background-color:#fff;'>VENC 2</th>
            </tr>";
    
    $i = 0;
    while($r = $res->fetch_assoc()) {
        echo "<tr>
            <td>$i</td>
            <td>{$r['des_lin']}</td>
            <td>".mb_convert_encoding($r['nombre'],'UTF-16LE','UTF-8')."</td>
            <td>".number_format($r['stock'],0)."</td>
            <td></td><td></td><td></td>
        </tr>";
        $i++;
    }
    echo "</table>"; exit;
}

// LISTADO PRINCIPAL
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = $_GET['mode'] ?? 'inventory';
    $where = construirWhere($conn);
    
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 50;
    $offset = ($page-1)*$limit;
    
    $sort = $_GET['sort'] ?? 'nombre'; 
    $dir = ($_GET['dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
    
    $orderBy = "T1.nombre";
    switch ($sort) {
        case 'stock': $orderBy = "T1.stock"; break;
        case 'costo': $orderBy = "T1.costo"; break;
        case 'total': $orderBy = "(T1.stock * T1.costo)"; break;
        case 'venta': $orderBy = "T1.precio"; break;
        case 'margen': $orderBy = "(CASE WHEN T1.precio > 0 THEN ((T1.precio - T1.costo)/T1.precio) ELSE 0 END)"; break;
        case 'sede': $orderBy = "T1.sede"; break;
    }

    if ($mode === 'audit') {
        $sqlList = "SELECT T1.id, T1.codigo, T1.nombre, T1.sede, T1.stock, T1.costo,
                    L.des_lin as linea, M.des_mar as marca, P.des_sub as principio,
                    COALESCE(T2.conteo, 0) as conteo,
                    (COALESCE(T2.conteo, 0) - T1.stock) as diff_cant,
                    ((COALESCE(T2.conteo, 0) - T1.stock) * T1.costo) as diff_money
                    $joins LEFT JOIN toma_inventario T2 ON T1.id = T2.id_producto 
                    $where 
                    ORDER BY $orderBy $dir LIMIT $offset, $limit";
        
        $sqlAuditKPI = "SELECT 
                        SUM(CASE WHEN (COALESCE(T2.conteo,0) - T1.stock) > 0 THEN (COALESCE(T2.conteo,0) - T1.stock)*T1.costo ELSE 0 END) as gain,
                        SUM(CASE WHEN (COALESCE(T2.conteo,0) - T1.stock) < 0 THEN (COALESCE(T2.conteo,0) - T1.stock)*T1.costo ELSE 0 END) as loss
                        $joins LEFT JOIN toma_inventario T2 ON T1.id = T2.id_producto $where AND T2.conteo IS NOT NULL";
        
        $auditKpiRes = $conn->query($sqlAuditKPI)->fetch_assoc();
        $auditCards = [
            'gain' => floatval($auditKpiRes['gain']),
            'loss' => floatval($auditKpiRes['loss']),
            'net'  => floatval($auditKpiRes['gain']) + floatval($auditKpiRes['loss'])
        ];
        
        $totalItems = $conn->query("SELECT COUNT(*) as t $joins $where")->fetch_assoc()['t'];

    } else {
        $sqlList = "SELECT T1.id, T1.codigo, T1.nombre, T1.stock, T1.costo, T1.precio, T1.sede, 
                    L.des_lin as linea, M.des_mar as marca, P.des_sub as principio, 
                    (T1.stock * T1.costo) as total_valor,
                    CASE WHEN T1.precio > 0 THEN ((T1.precio - T1.costo)/T1.precio)*100 ELSE 0 END as margen_pct
                    $joins $where ORDER BY $orderBy $dir LIMIT $offset, $limit";
        
        $totalItems = $conn->query("SELECT COUNT(*) as t $joins $where")->fetch_assoc()['t'];
    }
    
    $items = []; 
    $res = $conn->query($sqlList); 
    if($res) while($r=$res->fetch_assoc()) {
        $dbSede = strtoupper(trim($r['sede']));
        // Usamos el mapa OUT para mostrar el nombre bonito en la tabla
        $r['sede_ui'] = $SEDE_MAP_OUT[$dbSede] ?? $dbSede;
        $items[] = $r;
    }

    // KPIs Generales
    $kpiSel = "SUM(T1.stock * T1.costo) as v_costo, SUM(T1.stock * T1.precio) as v_venta, SUM(T1.stock) as v_stock, COUNT(*) as items";
    $cardGeneral = $conn->query("SELECT $kpiSel $joins $where")->fetch_assoc();
    
    $cardsSedes = [];
    $resS = $conn->query("SELECT T1.sede, $kpiSel $joins $where GROUP BY T1.sede");
    if($resS) while($r=$resS->fetch_assoc()) {
        $dbSede = strtoupper(trim($r['sede']));
        $ui = $SEDE_MAP_OUT[$dbSede] ?? $dbSede;
        $cardsSedes[$ui] = ['costo'=>floatval($r['v_costo']), 'venta'=>floatval($r['v_venta']), 'stock'=>intval($r['v_stock']), 'items'=>intval($r['items'])];
    }

    echo json_encode([
        'list' => $items, 
        'pagination' => ['current_page'=>$page, 'total_pages'=>ceil($totalItems/$limit), 'total_items'=>$totalItems], 
        'card_general' => $cardGeneral, 
        'cards_sedes' => $cardsSedes,
        'audit_kpis' => $auditCards ?? null
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = json_decode(file_get_contents("php://input"), true);
    if (($d['action'] ?? '') === 'update_count') { 
        $id = intval($d['id']); $val = $d['val']; 
        if ($val === '') $conn->query("DELETE FROM toma_inventario WHERE id_producto = $id"); 
        else { $val = floatval($val); $conn->query("INSERT INTO toma_inventario (id_producto, conteo) VALUES ($id, $val) ON DUPLICATE KEY UPDATE conteo = $val"); } 
        echo json_encode(['success'=>true]); exit; 
    }
    if (($d['action'] ?? '') === 'delete') { 
        $id = intval($d['id']); 
        $conn->query("DELETE FROM inventario_zeth WHERE id=$id"); 
        $conn->query("DELETE FROM toma_inventario WHERE id_producto=$id");
        echo json_encode(['success'=>true]); exit; 
    }
}
?>