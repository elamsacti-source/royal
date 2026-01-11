<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
include 'db_config.php'; // IMPORTANTE

$method = $_SERVER['REQUEST_METHOD'];

// --- GET: Listar Sedes ---
if ($method === 'GET') {
    $res = $conn->query("SELECT * FROM sedes WHERE activo = 1");
    $out = []; 
    while($r=$res->fetch_assoc()) $out[]=$r;
    echo json_encode($out);
    exit;
} 

// --- POST: Crear o Actualizar ---
if ($method === 'POST') {
    $d = json_decode(file_get_contents("php://input"), true);
    $action = $d['action'] ?? 'create';

    // A. ACTUALIZAR IP DE SEGURIDAD
    if ($action === 'update_ip') {
        $id = intval($d['id']);
        $ip = $d['ip']; // Puede ser null o string
        
        $stmt = $conn->prepare("UPDATE sedes SET ip_publica = ? WHERE id = ?");
        $stmt->bind_param("si", $ip, $id);
        
        if ($stmt->execute()) echo json_encode(['success'=>true]);
        else echo json_encode(['success'=>false, 'error'=>$stmt->error]);
        exit;
    }

    // B. CREAR NUEVA SEDE (Lógica original)
    if (!empty($d['nombre'])) {
        $stmt = $conn->prepare("INSERT INTO sedes (nombre, direccion) VALUES (?, ?)");
        $stmt->bind_param("ss", $d['nombre'], $d['direccion']);
        if ($stmt->execute()) echo json_encode(['success'=>true]);
        else echo json_encode(['success'=>false, 'error'=>$stmt->error]);
        exit;
    }
}
$conn->close();
?>