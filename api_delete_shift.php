<?php
// api_delete_shift.php
// --- CORS ---
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

// IMPORTANTE: Aquí permitimos POST explícitamente para borrar
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
// --- fin CORS ---

header('Content-Type: application/json; charset=utf-8');
require_once 'db_config.php';

// Leer el cuerpo JSON
$raw = json_decode(file_get_contents('php://input'), true);

if (!$raw || !isset($raw['id'])) {
    http_response_code(400);
    echo json_encode(['success'=>false, 'error'=>'Falta el ID']);
    exit;
}

$id = intval($raw['id']);

// Ejecutar el borrado
$stmt = $conn->prepare("DELETE FROM programacion WHERE id = ?");
if (!$stmt) {
    echo json_encode(['success'=>false, 'error'=>$conn->error]);
    exit;
}

$stmt->bind_param('i', $id);
$ok = $stmt->execute();

if (!$ok) {
    echo json_encode(['success'=>false, 'error'=>$stmt->error]);
    exit;
}

echo json_encode(['success'=>true]);
?>
