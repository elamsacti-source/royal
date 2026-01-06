<?php
header('Content-Type: application/json');
include_once 'session.php'; // Necesitamos la sesión para saber quién pide los datos
include 'db_config.php';

// Validar sesión
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$current_role = $_SESSION['user_role'];

// Recibir fechas
$start_date = $_GET['start'] ?? date('Y-m-d', strtotime('monday this week'));
$end_date = date('Y-m-d', strtotime($start_date . ' + 6 days'));

// 1. CONSTRUIR CONSULTA SQL DINÁMICA
$sql = "SELECT p.fecha, p.turno, u.id as user_id, u.nombre_completo, s.nombre as sede_nombre
        FROM programacion p
        JOIN usuarios u ON p.usuario_id = u.id
        JOIN sedes s ON p.sede_id = s.id
        WHERE p.fecha BETWEEN '$start_date' AND '$end_date'";

// FILTRO DE SEGURIDAD:
// Si NO es admin, agregamos una cláusula para que solo vea sus propios registros.
if ($current_role !== 'admin') {
    $sql .= " AND p.usuario_id = $current_user_id";
}

$sql .= " ORDER BY u.nombre_completo, p.fecha";

$res = $conn->query($sql);

$grid = [];
$users = [];

while($r = $res->fetch_assoc()) {
    // Guardamos la info básica del usuario
    if (!isset($users[$r['user_id']])) {
        $users[$r['user_id']] = $r['nombre_completo'];
    }
    
    // Organizar por Usuario -> Fecha
    $grid[$r['user_id']][$r['fecha']][] = [
        'sede' => $r['sede_nombre'],
        'turno' => $r['turno']
    ];
}

// Si es usuario normal y no tiene turnos, forzamos que aparezca su nombre vacío
// para que no vea una tabla en blanco triste.
if ($current_role !== 'admin' && empty($users)) {
    $users[$current_user_id] = $_SESSION['user_name'];
}

// Estructurar respuesta
$output = [];
foreach ($users as $uid => $name) {
    $row = ['id' => $uid, 'nombre' => $name, 'dias' => []];
    
    // Rellenar cada día de la semana
    $current = $start_date;
    for ($i = 0; $i < 7; $i++) {
        $assignments = $grid[$uid][$current] ?? [];
        $row['dias'][$current] = $assignments;
        $current = date('Y-m-d', strtotime($current . ' + 1 day'));
    }
    $output[] = $row;
}

echo json_encode(['data' => $output, 'start' => $start_date, 'end' => $end_date]);
$conn->close();
?>