<?php
// 1. FORZAR UTF-8
header('Content-Type: text/html; charset=utf-8');

include_once 'session.php';
include_once 'db_config.php';

if (isset($conn)) {
    $conn->set_charset("utf8mb4");
}

// SEGURIDAD
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// --- PARÁMETROS GLOBALES ---
$tab          = $_GET['tab'] ?? 'citas';
$fecha_inicio = $_GET['f_ini'] ?? date('Y-m-01');
$fecha_fin    = $_GET['f_fin'] ?? date('Y-m-d');
$sede_id      = $_GET['sede'] ?? '';
$doctor_sel   = $_GET['doctor'] ?? ''; 
$agrupacion   = $_GET['agrup'] ?? 'dia'; 
$busqueda     = $_GET['q'] ?? '';

// --- CARGAR DATOS PARA FILTROS ---
$sedes_opt = [];
$res_sedes = $conn->query("SELECT id, nombre FROM sedes");
if($res_sedes) while($s = $res_sedes->fetch_assoc()) $sedes_opt[] = $s;

$docs_opt = [];
$res_docs = $conn->query("SELECT DISTINCT doctor FROM citas WHERE doctor IS NOT NULL AND doctor != '' ORDER BY doctor ASC");
if($res_docs) while($d = $res_docs->fetch_assoc()) $docs_opt[] = $d['doctor'];

// --- FILTROS (CLAUSULAS WHERE) ---
// Filtros Base
$filtro_citas_comun = "c.fecha BETWEEN '$fecha_inicio' AND '$fecha_fin'";
$filtro_trat_comun  = "tc.fecha_programada BETWEEN '$fecha_inicio' AND '$fecha_fin'";

if($sede_id != '') { 
    $filtro_citas_comun .= " AND c.sede_id = '$sede_id'";
    $filtro_trat_comun  .= " AND c.sede_id = '$sede_id'";
}
if($doctor_sel != '') { 
    $filtro_citas_comun .= " AND c.doctor = '$doctor_sel'";
    $filtro_trat_comun  .= " AND c.doctor = '$doctor_sel'";
}

// ==========================================
// PESTAÑA 1: CITAS
// ==========================================
if ($tab == 'citas') {
    $where = "WHERE $filtro_citas_comun";
    
    $sql_total = "SELECT COUNT(*) as total FROM citas c $where";
    $total_citas = $conn->query($sql_total)->fetch_assoc()['total'];

    $sql_stats = "SELECT c.estado, COUNT(*) as total FROM citas c $where GROUP BY c.estado";
    $res_stats = $conn->query($sql_stats);
    $stats = ['ATENDIDO'=>0, 'CANCELADO'=>0, 'PROGRAMADO'=>0];
    while($r = $res_stats->fetch_assoc()) $stats[strtoupper($r['estado'])] = $r['total'];

    $p_atend = ($total_citas>0) ? round(($stats['ATENDIDO']/$total_citas)*100,1) : 0;
    $p_cancel = ($total_citas>0) ? round(($stats['CANCELADO']/$total_citas)*100,1) : 0;
    $p_pend = ($total_citas>0) ? round(($stats['PROGRAMADO']/$total_citas)*100,1) : 0;

    $sql_list = "SELECT c.fecha, c.doctor, c.especialidad, c.dni, 
                        CONCAT(c.nombres, ' ', c.apellidos) as paciente, 
                        c.telefono, c.estado, s.nombre as nombre_sede
                 FROM citas c LEFT JOIN sedes s ON c.sede_id = s.id
                 $where ORDER BY c.fecha DESC";
    $res_list = $conn->query($sql_list);
}

// ==========================================
// PESTAÑA 2: PRODUCCIÓN
// ==========================================
elseif ($tab == 'produccion') {
    $where = "WHERE $filtro_trat_comun";
    
    $sql_kpi = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN (tc.estado='REALIZADO' OR tc.realizado=1) THEN 1 ELSE 0 END) as hechos
                FROM tratamiento_cronograma tc 
                INNER JOIN atenciones a ON tc.atencion_id = a.id
                INNER JOIN citas c ON a.cita_id = c.id
                $where";
    $kpi_data = $conn->query($sql_kpi)->fetch_assoc();
    $total_items = $kpi_data['total'];
    $total_hechos = $kpi_data['hechos'];
    $p_efectividad = ($total_items>0) ? round(($total_hechos/$total_items)*100,1) : 0;

    $sql_doc = "SELECT c.doctor, c.especialidad, tc.producto, tc.tipo,
                    COUNT(*) as recetado,
                    SUM(CASE WHEN tc.estado='REALIZADO' OR tc.realizado=1 THEN 1 ELSE 0 END) as realizado
                FROM tratamiento_cronograma tc
                INNER JOIN atenciones a ON tc.atencion_id = a.id
                INNER JOIN citas c ON a.cita_id = c.id
                $where
                GROUP BY c.doctor, c.especialidad, tc.producto, tc.tipo
                ORDER BY c.doctor ASC, recetado DESC";
    $res_doc = $conn->query($sql_doc);
    
    $reporte_doc = [];
    while($row = $res_doc->fetch_assoc()){
        $doc = $row['doctor'];
        if(!isset($reporte_doc[$doc])) {
            $reporte_doc[$doc] = ['especialidad'=>$row['especialidad'], 'items'=>[], 'total_rec'=>0, 'total_hecho'=>0];
        }
        $reporte_doc[$doc]['items'][] = $row;
        $reporte_doc[$doc]['total_rec'] += $row['recetado'];
        $reporte_doc[$doc]['total_hecho'] += $row['realizado'];
    }
}

// ==========================================
// PESTAÑA 3: DETALLE
// ==========================================
elseif ($tab == 'tratamientos') {
    $where = "WHERE $filtro_trat_comun";
    if(!empty($busqueda)) { $where .= " AND (c.nombres LIKE '%$busqueda%' OR c.apellidos LIKE '%$busqueda%' OR c.dni LIKE '%$busqueda%')"; }

    $sql_trat = "SELECT c.fecha, CONCAT(c.nombres,' ',c.apellidos) as paciente, c.dni, c.doctor,
                        tc.producto, tc.tipo, tc.fecha_programada, tc.estado, tc.realizado, s.nombre as sede
                 FROM tratamiento_cronograma tc
                 INNER JOIN atenciones a ON tc.atencion_id = a.id
                 INNER JOIN citas c ON a.cita_id = c.id
                 LEFT JOIN sedes s ON c.sede_id = s.id
                 $where ORDER BY tc.fecha_programada DESC";
    $res_trat = $conn->query($sql_trat);
}

// ==========================================
// PESTAÑA 4: EVOLUCIÓN
// ==========================================
elseif ($tab == 'evolucion') {
    $where_c = "WHERE $filtro_citas_comun";
    $where_p = "WHERE $filtro_trat_comun";

    $sql_group = ""; 
    if($agrupacion == 'mes') {
        $sql_group = "DATE_FORMAT(fecha, '%Y-%m')"; 
        $sql_group_tc = "DATE_FORMAT(tc.fecha_programada, '%Y-%m')";
    } elseif($agrupacion == 'semana') {
        $sql_group = "YEARWEEK(fecha, 1)";
        $sql_group_tc = "YEARWEEK(tc.fecha_programada, 1)";
    } else { 
        $sql_group = "DATE(fecha)";
        $sql_group_tc = "DATE(tc.fecha_programada)";
    }

    $sql_evo_citas = "SELECT $sql_group as periodo, 
                             COUNT(*) as total,
                             SUM(CASE WHEN estado='ATENDIDO' THEN 1 ELSE 0 END) as atendidos
                      FROM citas c $where_c
                      GROUP BY $sql_group ORDER BY $sql_group ASC";
    $res_evo_c = $conn->query($sql_evo_citas);
    $labels_c = []; $data_c_total = []; $data_c_atend = [];
    while($r = $res_evo_c->fetch_assoc()) {
        $labels_c[] = $r['periodo']; $data_c_total[] = $r['total']; $data_c_atend[] = $r['atendidos'];
    }

    $sql_evo_prod = "SELECT $sql_group_tc as periodo,
                            COUNT(*) as recetado,
                            SUM(CASE WHEN (tc.estado='REALIZADO' OR tc.realizado=1) THEN 1 ELSE 0 END) as realizado
                     FROM tratamiento_cronograma tc
                     INNER JOIN atenciones a ON tc.atencion_id = a.id
                     INNER JOIN citas c ON a.cita_id = c.id
                     $where_p
                     GROUP BY $sql_group_tc ORDER BY $sql_group_tc ASC";
    $res_evo_p = $conn->query($sql_evo_prod);
    $labels_p = []; $data_p_rec = []; $data_p_real = [];
    while($r = $res_evo_p->fetch_assoc()){
        $labels_p[] = $r['periodo']; $data_p_rec[] = $r['recetado']; $data_p_real[] = $r['realizado'];
    }
}

// ==========================================
// PESTAÑA 5: MEGA REPORTE (UNION DE CITAS Y TRATAMIENTOS)
// ==========================================
elseif ($tab == 'mega_reporte') {
    // Filtro adicional de búsqueda
    $busq_sql = "";
    if(!empty($busqueda)) { 
        $busq_sql = " AND (c.nombres LIKE '%$busqueda%' OR c.apellidos LIKE '%$busqueda%' OR c.dni LIKE '%$busqueda%')"; 
    }

    // AQUI ESTA LA MAGIA: UNIMOS LAS DOS TABLAS
    // PARTE 1: CITAS (Salen como 'CONSULTA' o 'CITA MÉDICA')
    // PARTE 2: TRATAMIENTOS (Salen con el nombre del producto 'reconsulta', 'medicamento', etc.)
    
    $sql_mega = "
        (SELECT 
            c.fecha as fecha,
            s.nombre as sede,
            c.doctor,
            c.especialidad,
            CONCAT(c.nombres, ' ', c.apellidos) as paciente,
            c.dni,
            c.telefono,
            'CITA' as tipo_registro,
            'CITA MÉDICA / CONSULTA' as concepto,
            c.estado as estado
         FROM citas c
         LEFT JOIN sedes s ON c.sede_id = s.id
         WHERE $filtro_citas_comun $busq_sql)
         
        UNION ALL
        
        (SELECT 
            tc.fecha_programada as fecha,
            s.nombre as sede,
            c.doctor,
            c.especialidad,
            CONCAT(c.nombres, ' ', c.apellidos) as paciente,
            c.dni,
            c.telefono,
            'TRATAMIENTO' as tipo_registro,
            tc.producto as concepto,
            tc.estado as estado
         FROM tratamiento_cronograma tc
         INNER JOIN atenciones a ON tc.atencion_id = a.id
         INNER JOIN citas c ON a.cita_id = c.id
         LEFT JOIN sedes s ON c.sede_id = s.id
         WHERE $filtro_trat_comun $busq_sql)
         
        ORDER BY fecha DESC, paciente ASC
    ";
    
    $res_mega = $conn->query($sql_mega);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Avanzado de Gestión</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root { 
            --primary: #2f7f77; --primary-light: #e0f2f1; --bg: #f1f5f9; 
            --text: #0f172a; --text-light: #64748b; --border: #e2e8f0; 
            --success: #166534; --danger: #991b1b; --warn: #b45309;
        }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); margin:0; padding: 20px; }
        .container { max-width: 1800px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header h1 { color: var(--primary); margin:0; font-size: 1.6rem; display: flex; align-items: center; gap: 10px; }
        .filter-box { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 25px; display: flex; gap: 15px; flex-wrap: wrap; align-items: end; border:1px solid var(--border); }
        .form-group label { display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-light); margin-bottom: 5px; text-transform: uppercase; }
        .form-control { padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px; width: 160px; font-size: 0.9rem; }
        .btn { padding: 9px 16px; border-radius: 6px; border:none; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-excel { background: #10b981; color: white; }
        .btn-excel:hover { background: #059669; }
        .tabs { display: flex; gap: 10px; margin-bottom: 25px; flex-wrap: wrap; }
        .tab { flex: 1; min-width: 140px; padding: 15px; text-align: center; background: white; border-radius: 8px; text-decoration: none; color: var(--text-light); font-weight: 600; border: 2px solid transparent; transition:0.2s; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
        .tab:hover { transform: translateY(-2px); }
        .tab.active { border-color: var(--primary); background: var(--primary-light); color: var(--primary); }
        .kpi-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .kpi { background: white; padding: 20px; border-radius: 10px; border-left: 4px solid #ccc; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .kpi h4 { margin:0 0 5px 0; font-size: 0.8rem; color: var(--text-light); text-transform: uppercase; }
        .kpi .num { font-size: 2rem; font-weight: 800; color: var(--text); line-height: 1; }
        .kpi .pct { font-size: 0.85rem; margin-top: 5px; font-weight: 600; }
        .txt-green { color: var(--success); } .txt-red { color: var(--danger); } .txt-orange { color: var(--warn); }
        .card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 20px; border: 1px solid var(--border); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .card h3 { margin:0; font-size: 1.1rem; color: var(--text); }
        table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        th { text-align: left; padding: 12px; background: #f8fafc; border-bottom: 2px solid var(--border); color: var(--text-light); font-size: 0.75rem; text-transform: uppercase; white-space: nowrap; }
        td { padding: 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .badge { padding: 4px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; }
        .bg-green { background: #dcfce7; color: #166534; }
        .bg-red { background: #fee2e2; color: #991b1b; }
        .bg-orange { background: #ffedd5; color: #9a3412; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1><i class="ph ph-chart-polar"></i> Analítica Clínica</h1>
        <a href="admin.php" class="btn" style="background:white; color:var(--text); border:1px solid #ccc;">← Volver</a>
    </div>

    <form class="filter-box">
        <input type="hidden" name="tab" value="<?php echo $tab; ?>">
        <div class="form-group">
            <label>Desde</label>
            <input type="date" name="f_ini" value="<?php echo $fecha_inicio; ?>" class="form-control">
        </div>
        <div class="form-group">
            <label>Hasta</label>
            <input type="date" name="f_fin" value="<?php echo $fecha_fin; ?>" class="form-control">
        </div>
        <div class="form-group">
            <label>Sede</label>
            <select name="sede" class="form-control">
                <option value="">Todas</option>
                <?php foreach($sedes_opt as $s): ?>
                    <option value="<?php echo $s['id']; ?>" <?php echo ($sede_id==$s['id'])?'selected':''; ?>><?php echo $s['nombre']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Doctor</label>
            <select name="doctor" class="form-control">
                <option value="">Todos</option>
                <?php foreach($docs_opt as $d): ?>
                    <option value="<?php echo $d; ?>" <?php echo ($doctor_sel==$d)?'selected':''; ?>><?php echo $d; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <?php if($tab == 'evolucion'): ?>
        <div class="form-group">
            <label>Agrupar Por</label>
            <select name="agrup" class="form-control">
                <option value="dia" <?php echo ($agrupacion=='dia')?'selected':''; ?>>Día</option>
                <option value="semana" <?php echo ($agrupacion=='semana')?'selected':''; ?>>Semana</option>
                <option value="mes" <?php echo ($agrupacion=='mes')?'selected':''; ?>>Mes</option>
            </select>
        </div>
        <?php endif; ?>

        <?php if($tab == 'tratamientos' || $tab == 'mega_reporte'): ?>
        <div class="form-group">
            <label>Buscar</label>
            <input type="text" name="q" placeholder="Paciente / DNI..." value="<?php echo $busqueda; ?>" class="form-control">
        </div>
        <?php endif; ?>

        <div class="form-group">
            <label>&nbsp;</label>
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
    </form>

    <div class="tabs">
        <a href="?tab=citas&f_ini=<?php echo $fecha_inicio; ?>&f_fin=<?php echo $fecha_fin; ?>&sede=<?php echo $sede_id; ?>&doctor=<?php echo $doctor_sel; ?>" class="tab <?php echo ($tab=='citas')?'active':''; ?>"><i class="ph ph-users"></i> Citas</a>
        <a href="?tab=produccion&f_ini=<?php echo $fecha_inicio; ?>&f_fin=<?php echo $fecha_fin; ?>&sede=<?php echo $sede_id; ?>&doctor=<?php echo $doctor_sel; ?>" class="tab <?php echo ($tab=='produccion')?'active':''; ?>"><i class="ph ph-stethoscope"></i> Producción</a>
        <a href="?tab=tratamientos&f_ini=<?php echo $fecha_inicio; ?>&f_fin=<?php echo $fecha_fin; ?>&sede=<?php echo $sede_id; ?>&doctor=<?php echo $doctor_sel; ?>" class="tab <?php echo ($tab=='tratamientos')?'active':''; ?>"><i class="ph ph-list-dashes"></i> Detalle</a>
        <a href="?tab=evolucion&f_ini=<?php echo $fecha_inicio; ?>&f_fin=<?php echo $fecha_fin; ?>&sede=<?php echo $sede_id; ?>&doctor=<?php echo $doctor_sel; ?>&agrup=dia" class="tab <?php echo ($tab=='evolucion')?'active':''; ?>"><i class="ph ph-trend-up"></i> Evolución</a>
        <a href="?tab=mega_reporte&f_ini=<?php echo $fecha_inicio; ?>&f_fin=<?php echo $fecha_fin; ?>&sede=<?php echo $sede_id; ?>&doctor=<?php echo $doctor_sel; ?>" class="tab <?php echo ($tab=='mega_reporte')?'active':''; ?>" style="border-color: #7c3aed; color: #7c3aed; background: #f5f3ff;"><i class="ph ph-table"></i> 📊 Mega Reporte</a>
    </div>

    <?php if($tab == 'citas'): ?>
    <div class="kpi-row">
        <div class="kpi" style="border-color:#2563eb;"><h4>Total Citas</h4><div class="num"><?php echo $total_citas; ?></div></div>
        <div class="kpi" style="border-color:var(--success);"><h4>Atendidos</h4><div class="num"><?php echo $stats['ATENDIDO']; ?></div><div class="pct txt-green"><?php echo $p_atend; ?>%</div></div>
        <div class="kpi" style="border-color:var(--danger);"><h4>Cancelados</h4><div class="num"><?php echo $stats['CANCELADO']; ?></div><div class="pct txt-red"><?php echo $p_cancel; ?>%</div></div>
        <div class="kpi" style="border-color:var(--warn);"><h4>Pendientes</h4><div class="num"><?php echo $stats['PROGRAMADO']; ?></div><div class="pct txt-orange"><?php echo $p_pend; ?>%</div></div>
    </div>
    <div class="card">
        <div class="card-header">
            <h3>Registro Detallado de Citas</h3>
            <button onclick="exportTable('tablaCitas', 'Reporte_Citas')" class="btn btn-excel"><i class="ph ph-file-xls"></i> Exportar Excel</button>
        </div>
        <div style="overflow-x:auto;">
            <table id="tablaCitas">
                <thead><tr><th>Fecha</th><th>Paciente</th><th>DNI</th><th>Teléfono</th><th>Sede</th><th>Doctor</th><th>Estado</th></tr></thead>
                <tbody>
                    <?php while($r = $res_list->fetch_assoc()): $bg = ($r['estado']=='ATENDIDO') ? 'bg-green' : (($r['estado']=='CANCELADO') ? 'bg-red' : 'bg-orange'); ?>
                    <tr><td><?php echo $r['fecha']; ?></td><td><b><?php echo $r['paciente']; ?></b></td><td><?php echo $r['dni']; ?></td><td><?php echo $r['telefono']; ?></td><td><?php echo $r['nombre_sede']; ?></td><td><?php echo $r['doctor']; ?></td><td><span class="badge <?php echo $bg; ?>"><?php echo $r['estado']; ?></span></td></tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if($tab == 'produccion'): ?>
    <div class="kpi-row">
        <div class="kpi"><h4>Total Indicaciones</h4><div class="num"><?php echo $total_items; ?></div></div>
        <div class="kpi" style="border-color:var(--success);"><h4>Realizados</h4><div class="num"><?php echo $total_hechos; ?></div><div class="pct txt-green"><?php echo $p_efectividad; ?>%</div></div>
    </div>
    <div class="card">
        <div class="card-header">
            <h3>Desglose de Producción Médica</h3>
            <button onclick="exportTable('tablaProd', 'Produccion_Medica')" class="btn btn-excel"><i class="ph ph-file-xls"></i> Exportar Excel</button>
        </div>
        <div style="overflow-x:auto;">
            <table id="tablaProd">
                <thead><tr><th>Doctor</th><th>Especialidad</th><th>Servicio / Producto</th><th>Tipo</th><th>Recetado</th><th>Realizado</th><th>%</th></tr></thead>
                <tbody>
                    <?php foreach($reporte_doc as $medico => $d): ?>
                        <?php foreach($d['items'] as $it): $efect = ($it['recetado']>0) ? round(($it['realizado']/$it['recetado'])*100) : 0; ?>
                        <tr><td><b><?php echo $medico; ?></b></td><td><?php echo $d['especialidad']; ?></td><td><?php echo $it['producto']; ?></td><td><?php echo $it['tipo']; ?></td><td style="text-align:center;"><?php echo $it['recetado']; ?></td><td style="text-align:center; color:var(--success);"><?php echo $it['realizado']; ?></td><td><?php echo $efect; ?>%</td></tr>
                        <?php endforeach; ?>
                        <tr style="background:#f1f5f9; font-weight:bold;"><td colspan="4" style="text-align:right;">TOTAL:</td><td style="text-align:center;"><?php echo $d['total_rec']; ?></td><td style="text-align:center;"><?php echo $d['total_hecho']; ?></td><td></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if($tab == 'tratamientos'): ?>
    <div class="card">
        <div class="card-header">
            <h3>Base de Datos de Servicios</h3>
            <button onclick="exportTable('tablaDetalle', 'Detalle_Servicios')" class="btn btn-excel"><i class="ph ph-file-xls"></i> Exportar Excel</button>
        </div>
        <div style="overflow-x:auto;">
            <table id="tablaDetalle">
                <thead><tr><th>Fecha</th><th>Sede</th><th>Paciente</th><th>DNI</th><th>Servicio</th><th>Tipo</th><th>Médico</th><th>Estado</th></tr></thead>
                <tbody>
                    <?php while($r = $res_trat->fetch_assoc()): $est = ($r['realizado'] == 1 || $r['estado'] == 'REALIZADO') ? 'REALIZADO' : $r['estado']; ?>
                    <tr><td><?php echo $r['fecha_programada']; ?></td><td><?php echo $r['sede']; ?></td><td><?php echo $r['paciente']; ?></td><td><?php echo $r['dni']; ?></td><td><?php echo $r['producto']; ?></td><td><?php echo $r['tipo']; ?></td><td><?php echo $r['doctor']; ?></td><td><?php echo $est; ?></td></tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if($tab == 'evolucion'): ?>
    <div class="card"><h3>Evolución de Citas</h3><div style="height:350px;"><canvas id="chartEvoCitas"></canvas></div></div>
    <div class="card"><h3>Evolución de Producción</h3><div style="height:350px;"><canvas id="chartEvoProd"></canvas></div></div>
    <?php endif; ?>

    <?php if($tab == 'mega_reporte'): ?>
    <div class="card">
        <div class="card-header">
            <h3>MEGA REPORTE GENERAL (Consolidado)</h3>
            <button onclick="exportTable('tablaMega', 'Mega_Reporte_Completo')" class="btn btn-excel"><i class="ph ph-file-xls"></i> Exportar Todo</button>
        </div>
        <div style="overflow-x:auto;">
            <table id="tablaMega">
                <thead>
                    <tr style="background:#eef2ff;">
                        <th>Fecha</th>
                        <th>Sede</th>
                        <th>Tipo Registro</th>
                        <th>Concepto / Servicio</th>
                        <th>Estado</th>
                        <th>Paciente</th>
                        <th>DNI</th>
                        <th>Teléfono</th>
                        <th>Doctor</th>
                        <th>Especialidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($r = $res_mega->fetch_assoc()): 
                        // Colorear según tipo
                        $colorTipo = ($r['tipo_registro'] == 'CITA') ? '#2563eb' : '#b45309';
                        $bgEstado  = '';
                        $st = strtoupper($r['estado']);
                        if($st=='ATENDIDO' || $st=='REALIZADO') $bgEstado = 'bg-green';
                        elseif($st=='CANCELADO') $bgEstado = 'bg-red';
                        else $bgEstado = 'bg-orange';
                    ?>
                    <tr>
                        <td><?php echo $r['fecha']; ?></td>
                        <td><?php echo $r['sede']; ?></td>
                        <td style="font-weight:bold; color:<?php echo $colorTipo; ?>;"><?php echo $r['tipo_registro']; ?></td>
                        <td><?php echo $r['concepto']; ?></td>
                        <td><span class="badge <?php echo $bgEstado; ?>"><?php echo $r['estado']; ?></span></td>
                        <td><?php echo $r['paciente']; ?></td>
                        <td><?php echo $r['dni']; ?></td>
                        <td><?php echo $r['telefono']; ?></td>
                        <td><?php echo $r['doctor']; ?></td>
                        <td><?php echo $r['especialidad']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
function exportTable(tableID, filename = 'reporte'){
    var tableSelect = document.getElementById(tableID);
    var meta = '<meta http-equiv="content-type" content="application/vnd.ms-excel; charset=UTF-8">';
    var tableHTML = tableSelect.outerHTML;
    var blob = new Blob(['\ufeff', meta + tableHTML], { type: 'application/vnd.ms-excel;charset=utf-8' });
    var url = URL.createObjectURL(blob);
    var downloadLink = document.createElement("a");
    downloadLink.href = url;
    downloadLink.download = filename + '.xls';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

<?php if($tab == 'evolucion'): ?>
    new Chart(document.getElementById('chartEvoCitas'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels_c); ?>,
            datasets: [
                { label: 'Total Citas', data: <?php echo json_encode($data_c_total); ?>, borderColor: '#2563eb', backgroundColor: '#2563eb', tension: 0.3 },
                { label: 'Atendidos', data: <?php echo json_encode($data_c_atend); ?>, borderColor: '#166534', backgroundColor: '#166534', tension: 0.3 }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } } }
    });
    new Chart(document.getElementById('chartEvoProd'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels_p); ?>,
            datasets: [
                { label: 'Indicado', data: <?php echo json_encode($data_p_rec); ?>, borderColor: '#b45309', backgroundColor: '#b45309', tension: 0.3 },
                { label: 'Realizado', data: <?php echo json_encode($data_p_real); ?>, borderColor: '#166534', backgroundColor: '#166534', tension: 0.3 }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } } }
    });
<?php endif; ?>
</script>
</body>
</html>