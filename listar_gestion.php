<?php
// listar_gestion.php - VERSIÓN CORREGIDA PARA ACORDEONES
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
include_once 'session.php';
include 'db_config.php';
date_default_timezone_set('America/Lima');

// Detectar Admin
$es_admin = (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'admin' || stripos($_SESSION['user_name'], 'Administrador') !== false));

$fecha_inicio = $_GET['inicio'] ?? date('Y-m-d');
$fecha_fin    = $_GET['fin'] ?? date('Y-m-d');
$sede_id      = $_GET['sede'] ?? '';
$especialidad = $_GET['esp'] ?? '';
$doctor       = $_GET['doc'] ?? '';

// --- CANDADO DE SEGURIDAD ---
// Si NO es admin Y no especifica sede, bloqueamos.
if (!$es_admin && empty($sede_id)) {
    echo json_encode([]);
    exit;
}

// CORRECCIÓN: Agregamos c.sede_id a la selección SQL
$sql = "SELECT c.id, c.sede_id, c.estado, c.hora, c.fecha, c.nombres, c.apellidos, 
               c.telefono, c.historia_clinica, c.doctor, c.especialidad,
               a.hora_triaje, s.nombre as nombre_sede
        FROM citas c 
        LEFT JOIN atenciones a ON c.id = a.cita_id 
        LEFT JOIN sedes s ON c.sede_id = s.id
        WHERE c.fecha BETWEEN ? AND ? 
        AND c.estado IN ('ATENDIDO', 'CANCELADO')";

$types = "ss";
$params = [$fecha_inicio, $fecha_fin];

if (!empty($sede_id)) {
    $sql .= " AND c.sede_id = ?";
    $types .= "i";
    $params[] = $sede_id;
}

if (!empty($especialidad) && $especialidad !== 'Todas') {
    $sql .= " AND c.especialidad = ?";
    $types .= "s";
    $params[] = $especialidad;
}

if (!empty($doctor) && $doctor !== 'Todos') {
    $sql .= " AND c.doctor LIKE ?";
    $types .= "s";
    $params[] = "%" . $doctor . "%";
}

// Ordenamiento optimizado para la vista de acordeón
if ($es_admin && empty($sede_id)) {
    $sql .= " ORDER BY s.nombre ASC, c.fecha DESC, c.hora DESC";
} else {
    $sql .= " ORDER BY c.fecha DESC, c.hora DESC";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while($row = $result->fetch_assoc()) {
    $data[] = [
        'id' => $row['id'],
        'sede_id' => $row['sede_id'], // <--- ESTO ES LO QUE FALTABA
        'sede_nombre' => utf8_encode($row['nombre_sede'] ?? 'Sin Sede'),
        'estado' => $row['estado'],
        'fecha' => $row['fecha'],
        'hora' => $row['hora'],
        'paciente' => utf8_encode($row['nombres'] . ' ' . $row['apellidos']),
        'tel' => $row['telefono'],
        'hc' => $row['historia_clinica'],
        'doc' => utf8_encode($row['doctor']),
        'esp' => utf8_encode($row['especialidad']),
        'triaje' => $row['hora_triaje'] ?? '-'
    ];
}
echo json_encode($data);
$stmt->close(); $conn->close();
?>