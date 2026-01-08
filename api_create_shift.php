<?php
// api_create_shift.php
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
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
// --- fin CORS ---

header('Content-Type: application/json; charset=utf-8');
require_once 'db_config.php';
$raw = json_decode(file_get_contents('php://input'), true);

if (!$raw) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'No JSON body']); exit; }

$usuario_id = isset($raw['usuario_id']) ? intval($raw['usuario_id']) : null;
$sede_id    = isset($raw['sede_id']) ? intval($raw['sede_id']) : null;
$start_iso  = $raw['start'] ?? null;
$end_iso    = $raw['end'] ?? null;
$turno      = $raw['turno'] ?? null;

if (!$usuario_id || !$sede_id || !$start_iso || !$end_iso) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'Faltan campos obligatorios']);
    exit;
}

// parse fecha y horas
$ds = new DateTime($start_iso);
$de = new DateTime($end_iso);
$fecha = $ds->format('Y-m-d');
$hora_inicio = $ds->format('H:i:s');
$hora_fin = $de->format('H:i:s');

$sql = "INSERT INTO programacion (usuario_id, sede_id, fecha, turno, hora_inicio, hora_fin) VALUES (?,?,?,?,?,?)";
$stmt = $conn->prepare($sql);
if (!$stmt) { echo json_encode(['success'=>false,'error'=>$conn->error]); exit; }
$stmt->bind_param('iissss', $usuario_id, $sede_id, $fecha, $turno, $hora_inicio, $hora_fin);
$ok = $stmt->execute();
if (!$ok) {
    echo json_encode(['success'=>false,'error'=>$stmt->error]);
    exit;
}
$id = $conn->insert_id;
echo json_encode(['success'=>true,'id'=>$id]);
