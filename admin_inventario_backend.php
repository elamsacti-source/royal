<?php
// admin_inventario_backend.php
// VELOCIDAD EXTREMA: Índices activos, Sin TRIM en Joins.
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
include 'db_config.php';
date_default_timezone_set('America/Lima');

// A. REPORTES
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'history') {
        $data = []; $res = $conn->query("SELECT sede, DATE_FORMAT(fecha_carga, '%d/%m %H:%i') as fecha, total_valor FROM historial_cargas ORDER BY fecha_carga ASC");
        if($res) while($r=$res->fetch_assoc()) $data[]=$r; echo json_encode($data); exit;
    }
    if ($_GET['action'] === 'audit_report_data') {
        $sede = $conn->real_escape_string($_GET['sede'] ?? 'ALL');
        $hist = [];
        $sqlH = "SELECT DATE_FORMAT(fecha_cierre, '%d/%m %H:%i') as fecha, total_faltante, total_sobrante FROM historial_auditorias " . ($sede !== 'ALL' ? " WHERE sede = '$sede'" : "") . " ORDER BY fecha_cierre ASC LIMIT 10";
        $resH = $conn->query($sqlH); if($resH) while($r=$resH->fetch_assoc()) $hist[] = $r;

        $whereDraft = "WHERE T2.conteo IS NOT NULL AND T1.categoria IN ('FARMACIA', 'SUPLEMENTOS', 'INSUMOS MEDICOS')";
        if($sede !== 'ALL') $whereDraft .= " AND T1.sede = '$sede'";
        
        // JOIN OPTIMIZADO
        $sqlDraft = "SELECT T1.codigo, T1.nombre, COALESCE(P.des_sub, '') as principio, T1.costo, T1.stock as sistema, T2.conteo as fisico, (T2.conteo - T1.stock) as dif_qty, ((T2.conteo - T1.stock) * T1.costo) as dif_val 
                     FROM inventario_zeth T1 
                     JOIN toma_inventario T2 ON T1.id = T2.id_producto 
                     LEFT JOIN principios_zeth P ON T1.sublin = P.cod_sub AND T1.sede = P.sede
                     $whereDraft";
        
        $resD = $conn->query($sqlDraft);
        $listLoss = []; $listGain = []; $currLoss = 0; $currGain = 0;
        if($resD) { while($r = $resD->fetch_assoc()) { $val = floatval($r['dif_val']); if($val < 0) { $currLoss += abs($val); $listLoss[] = $r; } if($val > 0) { $currGain += $val; $listGain[] = $r; } } }
        echo json_encode(['chart_history' => $hist, 'current_summary' => ['loss' => $currLoss, 'gain' => $currGain, 'net' => $currGain - $currLoss], 'details_loss' => $listLoss, 'details_gain' => $listGain]); exit;
    }
}

// B. LISTADO PRINCIPAL
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $q = isset($_GET['q']) ? $conn->real_escape_string($_GET['q']) : '';
    $cat = $_GET['cat'] ?? 'ALL'; $sede = $_GET['sede'] ?? 'ALL'; $mode = $_GET['mode'] ?? 'inventory'; $noLimit = isset($_GET['no_limit']);
    $page = max(1, (int)($_GET['page'] ?? 1)); $perPage = 50; $offset = ($page - 1) * $perPage;
    $sort = $_GET['sort'] ?? 'nombre'; $dir = $_GET['dir'] ?? 'ASC';
    
    $allow = ['id','codigo','nombre','sede','categoria','stock','costo','precio','diferencia','principio'];
    if(!in_array($sort, $allow) && $sort !== 'total') $sort = 'nombre';
    $dirSql = ($dir === 'DESC') ? 'DESC' : 'ASC';
    
    $orderBy = "ORDER BY T1.$sort $dirSql";
    if ($sort === 'total') $orderBy = "ORDER BY (T1.stock * T1.costo) $dirSql";
    elseif ($sort === 'diferencia') $orderBy = "ORDER BY (COALESCE(T2.conteo, T1.stock) - T1.stock) $dirSql";
    elseif ($sort === 'principio') $orderBy = "ORDER BY P.des_sub $dirSql";

    $baseWhere = "WHERE T1.stock > 0 AND T1.categoria IN ('FARMACIA', 'SUPLEMENTOS', 'INSUMOS MEDICOS')";
    $listWhere = $baseWhere;
    if($q !== '') $listWhere .= " AND (T1.nombre LIKE '%$q%' OR T1.codigo LIKE '%$q%' OR P.des_sub LIKE '%$q%')";
    if($cat !== 'ALL') $listWhere .= " AND T1.categoria = '$cat'";
    if($sede !== 'ALL') $listWhere .= " AND T1.sede = '$sede'";

    // JOIN OPTIMIZADO: Eliminamos TRIM() para usar índices
    $sqlCore = "FROM inventario_zeth T1 LEFT JOIN principios_zeth P ON T1.sublin = P.cod_sub AND T1.sede = P.sede";

    if ($mode === 'audit') {
        $sqlList = "SELECT T1.id, T1.codigo, T1.nombre, COALESCE(P.des_sub, '') as principio, T1.categoria, T1.stock, T1.sede, COALESCE(T2.conteo, '') as conteo_fisico $sqlCore LEFT JOIN toma_inventario T2 ON T1.id = T2.id_producto $listWhere $orderBy";
    } else {
        $sqlList = "SELECT T1.id, T1.codigo, T1.nombre, COALESCE(P.des_sub, '') as principio, T1.categoria, T1.stock, T1.costo, T1.precio, T1.sede, (T1.stock * T1.costo) as total_valor, (T1.precio - T1.costo) as margen_unitario $sqlCore $listWhere $orderBy";
    }

    if (!$noLimit) $sqlList .= " LIMIT $offset, $perPage";

    $items = []; $res = $conn->query($sqlList); if($res) while($r=$res->fetch_assoc()) $items[]=$r;
    $totalItems = 0; if (!$noLimit) { $totalItems = $conn->query("SELECT COUNT(*) as t $sqlCore $listWhere")->fetch_assoc()['t']; } else { $totalItems = count($items); }

    // CARDS
    $cSedes = ['HUACHO'=>['qty'=>0,'val_costo'=>0,'val_venta'=>0], 'HUAURA'=>['qty'=>0,'val_costo'=>0,'val_venta'=>0], 'MEDIO MUNDO'=>['qty'=>0,'val_costo'=>0,'val_venta'=>0]];
    if (!$noLimit) {
        $resSedes = $conn->query("SELECT sede, COUNT(*) as qty, SUM(stock * costo) as val_costo, SUM(stock * precio) as val_venta FROM inventario_zeth T1 $baseWhere GROUP BY sede");
        while($r=$resSedes->fetch_assoc()) { 
            $s=strtoupper(trim($r['sede'])); 
            if(isset($cSedes[$s])) $cSedes[$s] = ['qty'=>$r['qty'], 'val_costo'=>$r['val_costo'], 'val_venta'=>$r['val_venta']]; 
        }

        $catWhere = "$baseWhere"; if($sede !== 'ALL') $catWhere .= " AND T1.sede = '$sede'";
        $cCats = ['TOTAL'=>['qty'=>0,'val'=>0], 'FARMACIA'=>['qty'=>0,'val'=>0], 'SUPLEMENTOS'=>['qty'=>0,'val'=>0], 'INSUMOS'=>['qty'=>0,'val'=>0]];
        $resCats = $conn->query("SELECT categoria, COUNT(*) as qty, SUM(stock * costo) as val FROM inventario_zeth T1 $catWhere GROUP BY categoria");
        while($r=$resCats->fetch_assoc()) {
            $c = strtoupper($r['categoria']); $cCats['TOTAL']['qty'] += $r['qty']; $cCats['TOTAL']['val'] += $r['val'];
            $k=null; if(strpos($c,'FARM')!==false)$k='FARMACIA'; elseif(strpos($c,'SUPLE')!==false)$k='SUPLEMENTOS'; elseif(strpos($c,'INSU')!==false)$k='INSUMOS';
            if($k) { $cCats[$k]['qty'] += $r['qty']; $cCats[$k]['val'] += $r['val']; }
        }
        $fechas = ['HUACHO'=>'--/--', 'HUAURA'=>'--/--', 'MEDIO MUNDO'=>'--/--'];
        $chk = $conn->query("SHOW TABLES LIKE 'configuracion_sedes'");
        if($chk && $chk->num_rows>0) { $rf = $conn->query("SELECT sede, ultima_actualizacion FROM configuracion_sedes"); while($r=$rf->fetch_assoc()) $fechas[strtoupper(trim($r['sede']))] = date('d/m H:i', strtotime($r['ultima_actualizacion'])); }
    }
    echo json_encode(['list' => $items, 'pagination' => ['current_page'=>$page, 'total_pages'=>ceil($totalItems/$perPage), 'total_items'=>$totalItems], 'cards_sedes' => $cSedes, 'cards_cats' => $cCats, 'last_updates' => $fechas]); exit;
}
// ... (RESTO DE POST LOGIC IGUAL) ...
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = json_decode(file_get_contents("php://input"), true);
    if (($d['action'] ?? '') === 'update_count') { $id = intval($d['id']); $val = $d['val']; if ($val === '') $conn->query("DELETE FROM toma_inventario WHERE id_producto = $id"); else { $val = intval($val); $conn->query("INSERT INTO toma_inventario (id_producto, conteo) VALUES ($id, $val) ON DUPLICATE KEY UPDATE conteo = $val"); } echo json_encode(['success'=>true]); exit; }
    if (($d['action'] ?? '') === 'delete') { $id = intval($d['id']); $conn->query("UPDATE inventario_zeth SET stock = 0 WHERE id=$id"); $conn->query("DELETE FROM toma_inventario WHERE id_producto = $id"); echo json_encode(['success'=>true]); exit; }
    if (($d['action'] ?? '') === 'finalize_audit') { $sede = $conn->real_escape_string($d['sede']); if($sede === 'ALL') { echo json_encode(['success'=>false, 'error'=>'Seleccione una sede específica']); exit; } $sqlCalc = "SELECT SUM(T1.stock * T1.costo) as sistema, SUM(COALESCE(T2.conteo, T1.stock) * T1.costo) as fisico FROM inventario_zeth T1 LEFT JOIN toma_inventario T2 ON T1.id = T2.id_producto WHERE T1.sede = '$sede' AND T1.stock > 0 AND T1.categoria IN ('FARMACIA', 'SUPLEMENTOS', 'INSUMOS MEDICOS')"; $resCalc = $conn->query($sqlCalc)->fetch_assoc(); $sis = floatval($resCalc['sistema'] ?? 0); $fis = floatval($resCalc['fisico'] ?? 0); $diff = $fis - $sis; $stmt = $conn->prepare("INSERT INTO historial_auditorias (sede, total_sistema, total_fisico, diferencia_dinero, total_sobrante, total_faltante) VALUES (?, ?, ?, ?, 0, 0)"); $stmt->bind_param("sddd", $sede, $sis, $fis, $diff); if($stmt->execute()) { $conn->query("DELETE T2 FROM toma_inventario T2 JOIN inventario_zeth T1 ON T2.id_producto = T1.id WHERE T1.sede = '$sede'"); echo json_encode(['success'=>true]); } else echo json_encode(['success'=>false, 'error'=>$conn->error]); exit; }
}
$conn->close();
?>