<?php
// admin_inventario_backend.php
// V45.0: Kardex con Nombres Largos, Excel UTF-8 Fix y Funcionalidad Full

// Cabeceras generales
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
if (($_GET['action'] ?? '') !== 'export') { header('Content-Type: application/json'); }
header("Access-Control-Allow-Origin: *");
error_reporting(0); 

include 'db_config.php';
date_default_timezone_set('America/Lima');

// MAPEO DE SEDES
$SEDE_MAP_IN = ['HUAURA'=>'HUAURA', 'INTEGRA'=>'HUACHO', 'M.MUNDO'=>'MEDIO MUNDO'];
$SEDE_MAP_OUT = ['HUAURA'=>'HUAURA', 'HUACHO'=>'INTEGRA', 'MEDIO MUNDO'=>'M.MUNDO'];
$CATS_PRODUCTOS_SQL = "'FARMACIA','FARMACOS','SUPLEMENTOS','INSUMOS MEDICOS'";

// DICCIONARIO KARDEX (PERSONALIZADO SEGÚN TU PEDIDO)
$DICCIONARIO = [
    // Tus requerimientos exactos:
    'CT' => 'Cargo por Transferencia',
    'CI' => 'Cargo por toma de inventario',
    'CC' => 'Cargo por Compra',
    'DT' => 'Descargo por Transferencia',
    'DI' => 'Descargo por toma de inventario',
    
    // Mapeos adicionales para códigos comunes del sistema antiguo:
    'CP' => 'Cargo por Compra',
    '01' => 'Cargo por Compra',
    '02' => 'Cargo por Transferencia',
    '03' => 'Descargo por Venta',
    'VE' => 'Descargo por Venta',
    'DV' => 'Descargo por Venta',
    '16' => 'Saldo Inicial',
    'TR' => 'Transferencia',
    'CA' => 'Ajuste (+)',
    'DA' => 'Ajuste (-)'
];

// --- FUNCIONES AUXILIARES ---
function construirWhere($conn) {
    global $SEDE_MAP_IN, $CATS_PRODUCTOS_SQL;
    $where = "WHERE 1=1";

    $sede = $_GET['sedes'] ?? '';
    if($sede && isset($SEDE_MAP_IN[$sede])) $where .= " AND T1.sede = '" . $SEDE_MAP_IN[$sede] . "'";

    $tipo = $_GET['tipo'] ?? 'ALL';
    if ($tipo === 'PRODUCTO') $where .= " AND L.des_lin IN ($CATS_PRODUCTOS_SQL)";
    elseif ($tipo === 'SERVICIO') $where .= " AND L.des_lin NOT IN ($CATS_PRODUCTOS_SQL)";

    $lines = $_GET['lineas'] ?? 'ALL';
    if ($lines !== 'ALL' && $lines !== '') {
        $arr = array_map(function($x) use($conn){ return "'".$conn->real_escape_string($x)."'"; }, explode(',', $lines));
        $where .= " AND L.des_lin IN (" . implode(',', $arr) . ")";
    }

    $brands = $_GET['marcas'] ?? 'ALL';
    if ($brands !== 'ALL' && $brands !== '') {
        $arr = array_map(function($x) use($conn){ return "'".$conn->real_escape_string($x)."'"; }, explode(',', $brands));
        $where .= " AND M.des_mar IN (" . implode(',', $arr) . ")";
    }

    $q = $_GET['q_prod'] ?? '';
    if($q) $where .= " AND (T1.nombre LIKE '%$q%' OR T1.codigo LIKE '%$q%')";
    
    $qp = $_GET['q_prin'] ?? '';
    if($qp) $where .= " AND P.des_sub LIKE '%$qp%'";

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

if (($_GET['action'] ?? '') === 'get_kardex') {
    $codigo = $conn->real_escape_string($_GET['codigo'] ?? '');
    $sedeInput = $_GET['sede'] ?? '';
    $sedeDB = $SEDE_MAP_IN[$sedeInput] ?? $sedeInput;
    $sedeDB = $conn->real_escape_string($sedeDB);
    
    $prod = $conn->query("SELECT nombre FROM inventario_zeth WHERE codigo = '$codigo' LIMIT 1")->fetch_assoc();
    $movs = [];
    
    $sql = "SELECT fecha_movimiento as fecha, tipo_movimiento as tipo, cantidad as cant 
            FROM kardex_zeth WHERE codigo_producto = '$codigo' AND sede = '$sedeDB' 
            ORDER BY fecha_movimiento DESC, id DESC LIMIT 50";
    $res = $conn->query($sql);
    if ($res) {
        while($row = $res->fetch_assoc()) {
            $code = strtoupper(trim($row['tipo']));
            // Usamos el diccionario actualizado
            $row['tipo_nombre'] = $DICCIONARIO[$code] ?? "Movimiento ($code)";
            $row['color'] = ($row['cant'] < 0) ? '#ef4444' : '#10b981';
            $movs[] = $row;
        }
    }
    echo json_encode(['producto' => $prod, 'movimientos' => $movs]); exit;
}

// EXPORTAR EXCEL (MEJORADO CON UTF-8 BOM)
if (($_GET['action'] ?? '') === 'export') {
    $filename = "Inventario_".date('YmdHis').".xls";
    
    // Headers para forzar descarga correcta
    header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    // BOM para que Excel reconozca tildes y ñ
    echo "\xEF\xBB\xBF"; 
    
    $where = construirWhere($conn);
    $sql = "SELECT T1.codigo, T1.nombre, P.des_sub, L.des_lin, M.des_mar, T1.sede, 
            T1.stock, T1.costo, (T1.stock*T1.costo) as total, T1.precio,
            CASE WHEN T1.precio > 0 THEN ((T1.precio - T1.costo)/T1.precio)*100 ELSE 0 END as margen
            $joins $where ORDER BY T1.nombre ASC";
    $res = $conn->query($sql);
    
    echo "<table border='1'>
            <tr style='background-color:#f0f0f0; font-weight:bold;'>
                <th>CODIGO</th><th>PRODUCTO</th><th>SEDE</th><th>CATEGORIA</th>
                <th>STOCK</th><th>COSTO U.</th><th>TOTAL COSTO</th><th>P. VENTA</th><th>MARGEN %</th>
            </tr>";
    
    while($r = $res->fetch_assoc()) {
        $sedeUI = $SEDE_MAP_OUT[strtoupper(trim($r['sede']))] ?? $r['sede'];
        echo "<tr>
            <td style='mso-number-format:\"\@\";'>{$r['codigo']}</td>
            <td>{$r['nombre']}</td>
            <td>$sedeUI</td>
            <td>{$r['linea']}</td>
            <td>".number_format($r['stock'],2)."</td>
            <td>".number_format($r['costo'],2)."</td>
            <td>".number_format($r['total'],2)."</td>
            <td>".number_format($r['precio'],2)."</td>
            <td>".number_format($r['margen'],2)."%</td>
        </tr>";
    }
    echo "</table>"; exit;
}

// LISTADO JSON
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
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
        case 'nombre': $orderBy = "T1.nombre"; break;
    }
    
    $sqlList = "SELECT T1.id, T1.codigo, T1.nombre, T1.stock, T1.costo, T1.precio, T1.sede, 
                L.des_lin as linea, M.des_mar as marca, P.des_sub as principio, 
                (T1.stock * T1.costo) as total_valor,
                CASE WHEN T1.precio > 0 THEN ((T1.precio - T1.costo)/T1.precio)*100 ELSE 0 END as margen_pct
                $joins $where ORDER BY $orderBy $dir LIMIT $offset, $limit";
    
    $items = []; $res = $conn->query($sqlList);
    if($res) while($r = $res->fetch_assoc()) {
        $r['sede_ui'] = $SEDE_MAP_OUT[strtoupper(trim($r['sede']))] ?? $r['sede'];
        $items[] = $r;
    }
    
    $total = $conn->query("SELECT COUNT(*) as t $joins $where")->fetch_assoc()['t'];

    $kpiSel = "SUM(T1.stock * T1.costo) as v_costo, SUM(T1.stock * T1.precio) as v_venta, SUM(T1.stock) as v_stock, COUNT(*) as items";
    $cardGeneral = $conn->query("SELECT $kpiSel $joins $where")->fetch_assoc();
    
    $cardsSedes = [];
    $resS = $conn->query("SELECT T1.sede, $kpiSel $joins $where GROUP BY T1.sede");
    if($resS) while($r=$resS->fetch_assoc()) {
        $ui = $SEDE_MAP_OUT[strtoupper(trim($r['sede']))] ?? $r['sede'];
        $cardsSedes[$ui] = ['costo'=>floatval($r['v_costo']), 'venta'=>floatval($r['v_venta']), 'stock'=>intval($r['v_stock']), 'items'=>intval($r['items'])];
    }

    echo json_encode([
        'list' => $items, 
        'pagination' => ['current_page'=>$page, 'total_pages'=>ceil($total/$limit), 'total_items'=>$total], 
        'card_general' => $cardGeneral, 
        'cards_sedes' => $cardsSedes
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = json_decode(file_get_contents("php://input"), true);
    if (($d['action'] ?? '') === 'delete') { 
        $id = intval($d['id']); 
        $conn->query("DELETE FROM inventario_zeth WHERE id=$id"); 
        $conn->query("DELETE FROM toma_inventario WHERE id_producto=$id");
        echo json_encode(['success'=>true]); exit; 
    }
}
?>