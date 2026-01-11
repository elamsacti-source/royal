<?php
// api_export.php - CON FILTRO DE COLABORADORES
include_once 'session.php'; 
include 'db_config.php';

// Validar fechas y filtros
$ini = $_GET['fecha_inicio'] ?? '';
$fin = $_GET['fecha_fin'] ?? '';
$sede = $_GET['sede_id'] ?? '';
$sup = $_GET['supervisor_nombre'] ?? '';
$colab = $_GET['colaborador'] ?? ''; // <--- NUEVO PARÁMETRO
$est = $_GET['estado'] ?? '';

$json = isset($_GET['json']);

// Consulta Principal
$sql = "SELECT s.fecha, 
               s.turno,
               sed.nombre as sede_nombre, 
               s.supervisor_nombre, 
               s.colab_1, s.colab_2, s.colab_3,
               act.nombre as actividad_nombre, 
               i.estado, 
               i.observacion,
               i.quantity
        FROM checklist_sessions s 
        JOIN sedes sed ON s.sede_id = sed.id
        JOIN checklist_items i ON s.id = i.session_id 
        LEFT JOIN checklist_activities act ON i.activity_id = act.id
        WHERE 1=1";

// --- APLICAR FILTROS ---

if($ini) $sql .= " AND s.fecha >= '$ini'";
if($fin) $sql .= " AND s.fecha <= '$fin'";
if($sede) $sql .= " AND s.sede_id = $sede";

// Filtro específico de Supervisor (Dropdown existente)
if($sup) $sql .= " AND s.supervisor_nombre = '$sup'";

// Filtro General de Personal (NUEVO)
// Busca si el texto ingresado coincide con el Supervisor O con cualquier Colaborador
if($colab) {
    $sql .= " AND (
        s.supervisor_nombre LIKE '%$colab%' OR 
        s.colab_1 LIKE '%$colab%' OR 
        s.colab_2 LIKE '%$colab%' OR 
        s.colab_3 LIKE '%$colab%'
    )";
}

if($est) $sql .= " AND i.estado = '$est'";

$sql .= " ORDER BY s.fecha DESC, s.turno ASC, act.orden ASC LIMIT 1500";

$res = $conn->query($sql);
$data = [];

if ($res) {
    while($r = $res->fetch_assoc()) {
        if (empty($r['actividad_nombre'])) {
            $r['actividad_nombre'] = "Actividad (ID: " . ($r['activity_id'] ?? '?') . ")";
        }
        $data[] = $r;
    }
} else {
    if($json) { echo json_encode(['error' => $conn->error]); exit; }
}

if ($json) {
    header('Content-Type: application/json');
    echo json_encode($data);
} else {
    // Exportar a Excel
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=reporte_checklist.csv');
    echo "\xEF\xBB\xBF"; 
    
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Fecha', 'Sede', 'Turno', 'Supervisor', 'Equipo', 'Actividad', 'Estado', 'Observacion', 'Cantidad'], ';');
    
    foreach ($data as $row) {
        $equipo = implode(', ', array_filter([$row['colab_1'], $row['colab_2'], $row['colab_3']]));
        
        fputcsv($out, [
            $row['fecha'],
            $row['sede_nombre'],
            $row['turno'],
            $row['supervisor_nombre'],
            $equipo,
            $row['actividad_nombre'],
            $row['estado'],
            $row['observacion'],
            $row['quantity']
        ], ';');
    }
    fclose($out);
}
$conn->close();
?>