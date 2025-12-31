<?php
// api_update_shift.php
 // --- CORS (pegar al inicio) ---
if (isset($_SERVER['HTTP_ORIGIN'])) {
    $allowed = [
        'https://clinicalosangeles.org',
        'https://www.clinicalosangeles.org',
        'http://localhost:3000',
        'http://127.0.0.1:5500'
    ];
    $origin = $_SERVER['HTTP_ORIGIN'];
    if (in_array($origin, $allowed)) header("Access-Control-Allow-Origin: $origin");
}
header('Access-Control-Allow-Methods: PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
// --- fin CORS ---

header('Content-Type: application/json; charset=utf-8');
require_once 'db_config.php';
$raw = json_decode(file_get_contents('php://input'), true);
if (!$raw || !isset($raw['id'])) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'missing id']); exit; }

$id = intval($raw['id']);
$fields = [];
$params = [];
$types = '';

if (isset($raw['usuario_id'])) { $fields[] = "usuario_id = ?"; $types .= 'i'; $params[] = intval($raw['usuario_id']); }
if (isset($raw['sede_id']))    { $fields[] = "sede_id = ?";    $types .= 'i'; $params[] = intval($raw['sede_id']); }
if (isset($raw['turno']))      { $fields[] = "turno = ?";      $types .= 's'; $params[] = $raw['turno']; }
if (isset($raw['start'])) {
    $ds = new DateTime($raw['start']);
    $fields[] = "fecha = ?"; $types .= 's'; $params[] = $ds->format('Y-m-d');
    $fields[] = "hora_inicio = ?"; $types .= 's'; $params[] = $ds->format('H:i:s');
}
if (isset($raw['end'])) {
    $de = new DateTime($raw['end']);
    $fields[] = "hora_fin = ?"; $types .= 's'; $params[] = $de->format('H:i:s');
}

if (empty($fields)) { echo json_encode(['success'=>false,'error'=>'Nada para actualizar']); exit; }

$sql = "UPDATE programacion SET " . implode(', ', $fields) . " WHERE id = ?";
$types .= 'i'; $params[] = $id;

$stmt = $conn->prepare($sql);
if (!$stmt) { echo json_encode(['success'=>false,'error'=>$conn->error]); exit; }

// bind params dynamically
$stmt->bind_param($types, ...$params);
$ok = $stmt->execute();
if (!$ok) { echo json_encode(['success'=>false,'error'=>$stmt->error]); exit; }
echo json_encode(['success'=>true]);
