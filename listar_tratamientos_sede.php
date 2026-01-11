<?php
// listar_tratamientos_sede.php - CORREGIDO
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
include_once 'session.php';
include 'db_config.php';

$es_admin = (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'admin' || stripos($_SESSION['user_name'], 'Administrador') !== false));

$sede = $_GET['sede'] ?? '';
$inicio = $_GET['inicio'] ?? date('Y-m-01');
$fin = $_GET['fin'] ?? date('Y-m-t');

// --- CANDADO DE SEGURIDAD ---
if (!$es_admin && empty($sede)) {
    ob_clean();
    echo json_encode([]);
    exit;
}

$sql = "SELECT 
            tc.*, 
            a.archivo_receta,
            c.nombres, c.apellidos, c.dni, c.telefono, c.doctor as doctor_origen,
            s.nombre as nombre_sede
        FROM tratamiento_cronograma tc
        LEFT JOIN atenciones a ON tc.atencion_id = a.id
        LEFT JOIN citas c ON a.cita_id = c.id
        LEFT JOIN sedes s ON c.sede_id = s.id
        WHERE tc.fecha_programada BETWEEN ? AND ?";

$types = "ss";
$params = [$inicio, $fin];

// SI HAY SEDE ESPECIFICA, FILTRAMOS.
if (!empty($sede)) {
    $sql .= " AND c.sede_id = ?";
    $types .= "i";
    $params[] = $sede;
}

$sql .= " ORDER BY tc.fecha_programada ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($row = $res->fetch_assoc()) {
    array_walk_recursive($row, function(&$item) { 
        if(is_string($item)) $item = utf8_encode($item); 
    });
    $data[] = $row;
}

ob_clean();
header('Content-Type: application/json');
echo json_encode($data);
exit;
?>