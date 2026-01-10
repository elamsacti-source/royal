<?php
// admin_inventario_backend.php
// V11.2: Soporte real para exportación de Auditoría (Conteo Físico).

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
include 'db_config.php';
date_default_timezone_set('America/Lima');

// MAPEO DE NOMBRES
$SEDE_MAP_IN = ['HUAURA'=>'HUAURA', 'INTEGRA'=>'HUACHO', 'M.MUNDO'=>'MEDIO MUNDO'];
$SEDE_MAP_OUT = ['HUAURA'=>'HUAURA', 'HUACHO'=>'INTEGRA', 'MEDIO MUNDO'=>'M.MUNDO'];

$CATS_PRODUCTOS_ARRAY = ['FARMACIA', 'FARMACOS', 'SUPLEMENTOS', 'INSUMOS MEDICOS'];
$CATS_PRODUCTOS_SQL = "'" . implode("','", $CATS_PRODUCTOS_ARRAY) . "'";

// A. GET
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'get_filters') {
        $lines = []; $brands = [];
        $resL = $conn->query("SELECT DISTINCT des_lin FROM lineas_zeth WHERE des_lin != '' ORDER BY des_lin ASC");
        if($resL) while($r = $resL->fetch_assoc()) $lines[] = $r['des_lin'];
        $resM = $conn->query("SELECT DISTINCT des_mar FROM marcas_zeth WHERE des_mar != '' ORDER BY des_mar ASC");
        if($resM) while($r = $resM->fetch_assoc()) $brands[] = $r['des_mar'];
        echo json_encode(['lineas' => $lines, 'marcas' => $brands]); exit;
    }
    
    // KPI AUDITORIA (Pérdidas/Ganancias)
    if ($_GET['action'] === 'audit_kpi') {
        $sede = isset($_GET['sede']) ? $conn->real_escape_string($_GET['sede']) : '';
        // Traducir UI -> DB para el filtro
        $dbSede = $SEDE_MAP_IN[$sede] ?? '';
        
        $where = "WHERE T2.conteo IS NOT NULL";
        if($dbSede !== '') $where .= " AND T1.sede = '$dbSede'";
        
        $sql = "SELECT 
                    SUM(CASE WHEN (T2.conteo - T1.stock) < 0 THEN (T2.conteo - T1.stock) * T1.costo ELSE 0 END) as perdida,
                    SUM(CASE WHEN (T2.conteo - T1.stock) > 0 THEN (T2.conteo - T1.stock) * T1.costo ELSE 0 END) as ganancia,
                    COUNT(*) as items
                FROM inventario_zeth T1 
                JOIN toma_inventario T2 ON T1.id = T2.id_producto 
                $where";
        
        $res = $conn->query($sql)->fetch_assoc();
        echo json_encode([
            'perdida' => floatval($res['perdida']),
            'ganancia' => floatval($res['ganancia']),
            'neto' => floatval($res['ganancia']) + floatval($res['perdida'])
        ]);
        exit;
    }
}

// B. LISTADO
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = $_GET['mode'] ?? 'inventory'; // 'inventory' o 'audit'
    $q_prod = isset($_GET['q_prod']) ? $conn->real_escape_string($_GET['q_prod']) : '';
    $q_prin = isset($_GET['q_prin']) ? $conn->real_escape_string($_GET['q_prin']) : '';
    
    // Filtros
    $rawSedes = $_GET['sedes'] ?? '';
    $sedesArr = $rawSedes ? explode(',', $rawSedes) : [];
    $sedesSQL = "";
    if (!empty($sedesArr) && $rawSedes !== '') {
        $dbSedes = [];
        foreach($sedesArr as $uiSede) {
            $dbName = $SEDE_MAP_IN[$uiSede] ?? $uiSede;
            $dbSedes[] = "'" . $conn->real_escape_string($dbName) . "'";
        }
        $sedesSQL = " AND T1.sede IN (" . implode(',', $dbSedes) . ")";
    }

    $rawGroups = $_GET['groups'] ?? ''; $groupsArr = $rawGroups ? explode(',', $rawGroups) : [];
    $groupSQL = "";
    if (in_array('PRODUCTOS', $groupsArr) && !in_array('SERVICIOS', $groupsArr)) $groupSQL = " AND L.des_lin IN ($CATS_PRODUCTOS_SQL)";
    elseif (!in_array('PRODUCTOS', $groupsArr) && in_array('SERVICIOS', $groupsArr)) $groupSQL = " AND L.des_lin NOT IN ($CATS_PRODUCTOS_SQL)";

    $rawCats = $_GET['lineas'] ?? ''; $catMultiSQL = "";
    if ($rawCats !== '' && $rawCats !== 'ALL') {
        $cArr = explode(',', $rawCats); $cl=[]; foreach($cArr as $c) $cl[]="'".$conn->real_escape_string($c)."'";
        $catMultiSQL = " AND L.des_lin IN (" . implode(',', $cl) . ")";
    }

    $rawBrands = $_GET['marcas'] ?? ''; $brandMultiSQL = "";
    if ($rawBrands !== '' && $rawBrands !== 'ALL') {
        $bArr = explode(',', $rawBrands); $bl=[]; foreach($bArr as $b) $bl[]="'".$conn->real_escape_string($b)."'";
        $brandMultiSQL = " AND M.des_mar IN (" . implode(',', $bl) . ")";
    }

    // Paginación
    $noLimit = isset($_GET['no_limit']);
    $page = max(1, (int)($_GET['page'] ?? 1)); $perPage = 50; $offset = ($page - 1) * $perPage;
    $sort = $_GET['sort'] ?? 'nombre'; $dir = $_GET['dir'] === 'DESC' ? 'DESC' : 'ASC';
    
    switch ($sort) {
        case 'nombre': $ord = "T1.nombre"; break;
        case 'linea': $ord = "L.des_lin"; break;
        case 'marca': $ord = "M.des_mar"; break;
        case 'stock': $ord = "T1.stock"; break;
        case 'costo': $ord = "T1.costo"; break;
        case 'precio': $ord = "T1.precio"; break;
        default: $ord = "T1.nombre"; break;
    }
    $orderBy = "ORDER BY $ord $dir";

    $baseWhere = "WHERE 1=1 $sedesSQL $groupSQL $catMultiSQL $brandMultiSQL";
    if($q_prod !== '') $baseWhere .= " AND (T1.nombre LIKE '%$q_prod%' OR T1.codigo LIKE '%$q_prod%')";
    if($q_prin !== '') $baseWhere .= " AND P.des_sub LIKE '%$q_prin%'";

    $sqlCore = "FROM inventario_zeth T1 
                LEFT JOIN principios_zeth P ON (T1.sublin = P.cod_sub AND T1.sede = P.sede)
                LEFT JOIN lineas_zeth L ON (T1.lineaz = L.cod_lin AND T1.sede = L.sede)
                LEFT JOIN marcas_zeth M ON (T1.marcaz = M.cod_mar AND T1.sede = M.sede)";

    // SELECCIÓN DE COLUMNAS SEGÚN MODO
    if ($mode === 'audit') {
        // Modo Auditoría: Incluye conteo físico
        $sqlList = "SELECT T1.id, T1.codigo, T1.nombre, T1.stock, T1.costo, T1.sede,
                    COALESCE(P.des_sub, '') as principio,
                    COALESCE(L.des_lin, '') as linea,
                    COALESCE(M.des_mar, '') as marca,
                    COALESCE(T2.conteo, '') as conteo_fisico 
                    $sqlCore LEFT JOIN toma_inventario T2 ON T1.id = T2.id_producto $baseWhere $orderBy";
    } else {
        // Modo Inventario Normal
        $sqlList = "SELECT T1.id, T1.codigo, T1.nombre, 
                    COALESCE(P.des_sub, '') as principio, 
                    COALESCE(L.des_lin, '') as linea, 
                    COALESCE(M.des_mar, '') as marca, 
                    T1.stock, T1.costo, T1.precio, T1.sede, 
                    (T1.stock * T1.costo) as total_valor 
                    $sqlCore $baseWhere $orderBy";
    }

    if (!$noLimit) $sqlList .= " LIMIT $offset, $perPage";
    
    $items = []; 
    $res = $conn->query($sqlList); 
    if($res) while($r=$res->fetch_assoc()) {
        $dbSede = strtoupper(trim($r['sede']));
        $r['sede'] = $SEDE_MAP_OUT[$dbSede] ?? $dbSede;
        $items[] = $r;
    }
    
    $totalItems = $conn->query("SELECT COUNT(*) as t $sqlCore $baseWhere")->fetch_assoc()['t'];

    // KPIs Generales (Solo modo inventario normal)
    $generalCard = []; $sedesCards = [];
    if ($mode === 'inventory') {
        $kpiSQL = "SELECT SUM(stock * costo) as total_costo, SUM(stock * precio) as total_venta, SUM(stock) as total_stock, COUNT(*) as total_items $sqlCore $baseWhere";
        $kpiRes = $conn->query($kpiSQL)->fetch_assoc();
        $generalCard = ['costo' => floatval($kpiRes['total_costo']), 'venta' => floatval($kpiRes['total_venta']), 'stock' => intval($kpiRes['total_stock']), 'items' => intval($kpiRes['total_items'])];

        $resSedes = $conn->query("SELECT T1.sede, SUM(T1.stock * T1.costo) as v_costo, SUM(T1.stock * T1.precio) as v_venta, SUM(T1.stock) as v_stock, COUNT(*) as v_items $sqlCore $sedeWhere GROUP BY T1.sede");
        if($resSedes) while($r=$resSedes->fetch_assoc()) {
            $uiSede = $SEDE_MAP_OUT[strtoupper(trim($r['sede']))] ?? $r['sede'];
            $sedesCards[$uiSede] = ['costo'=>floatval($r['v_costo']), 'venta'=>floatval($r['v_venta']), 'stock'=>intval($r['v_stock']), 'items'=>intval($r['v_items'])];
        }
    }

    echo json_encode(['list' => $items, 'pagination' => ['current_page'=>$page, 'total_pages'=>ceil($totalItems/$perPage), 'total_items'=>$totalItems], 'card_general' => $generalCard, 'cards_sedes' => $sedesCards]); exit;
}

// POST
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
        $conn->query("DELETE FROM toma_inventario WHERE id_producto = $id"); 
        if ($conn->query("DELETE FROM inventario_zeth WHERE id=$id")) echo json_encode(['success'=>true]); 
        else echo json_encode(['success'=>false, 'error'=>$conn->error]); 
        exit; 
    }
}
?>