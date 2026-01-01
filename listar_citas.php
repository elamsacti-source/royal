<?php
// listar_citas.php - CORREGIDO CON SEGURIDAD DE ROLES
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
include_once 'session.php'; // Necesario para validar el rol
include 'db_config.php';

if ($conn->connect_error) { ob_clean(); echo json_encode([]); exit; }

// Detectar si es Admin
$es_admin = (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'admin' || stripos($_SESSION['user_name'], 'Administrador') !== false));

$inicio = $_GET['inicio'] ?? date('Y-m-d');
$fin = $_GET['fin'] ?? date('Y-m-d');
$esp = $_GET['esp'] ?? '';
$doc = $_GET['doc'] ?? '';
$sede = $_GET['sede'] ?? ''; 

// --- CANDADO DE SEGURIDAD ---
// Si NO es admin Y la sede viene vacía (intenta ver todo), bloqueamos la consulta.
if (!$es_admin && empty($sede)) {
    ob_clean();
    echo json_encode([]); // Devolvemos lista vacía por seguridad
    exit;
}

// Consulta Base
$sql = "SELECT c.*, s.nombre as nombre_sede 
        FROM citas c
        LEFT JOIN sedes s ON c.sede_id = s.id 
        WHERE c.fecha BETWEEN ? AND ? 
        AND c.estado IN ('PROGRAMADO', 'REPROGRAMADO', 'CONFIRMADO')";

$types = "ss";
$params = [$inicio, $fin];

if (!empty($esp) && $esp !== 'Todas') { $sql .= " AND c.especialidad = ?"; $types .= "s"; $params[] = $esp; }
if (!empty($doc) && $doc !== 'Todos') { $sql .= " AND c.doctor = ?"; $types .= "s"; $params[] = $doc; }

// Filtro de Sede
if (!empty($sede)) { 
    $sql .= " AND c.sede_id = ?"; 
    $types .= "i"; 
    $params[] = $sede; 
}

$sql .= " ORDER BY c.fecha ASC, c.hora ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($row = $res->fetch_assoc()) {
    $row['nom'] = utf8_encode($row['nombres'] . ' ' . $row['apellidos']);
    $row['doc'] = utf8_encode($row['doctor']);
    $row['esp'] = utf8_encode($row['especialidad']);
    $row['sede_nombre'] = utf8_encode($row['nombre_sede']);
    $row['tel'] = $row['telefono']; 
    $row['hc'] = $row['historia_clinica'];
    $data[] = $row;
}

ob_clean();
header('Content-Type: application/json');
echo json_encode($data);
exit;
?>