<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
include 'db_config.php';

$data = json_decode(file_get_contents("php://input"), true);

// Validación básica
if (empty($data['usuario']) || empty($data['sede']) || empty($data['fecha'])) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios']);
    exit;
}

// Recibimos los datos ya calculados desde el Frontend
$turno_texto = $data['turno_texto'] ?? 'Turno';

// Agregamos segundos para formato TIME de MySQL si vienen solo "08:00"
$h_inicio = $data['hora_inicio'] . ":00"; 
$h_fin    = $data['hora_fin'] . ":00";

$stmt = $conn->prepare("INSERT INTO programacion (usuario_id, sede_id, fecha, turno, hora_inicio, hora_fin) VALUES (?, ?, ?, ?, ?, ?)");

$stmt->bind_param("iissss", 
    $data['usuario'], 
    $data['sede'], 
    $data['fecha'], 
    $turno_texto,
    $h_inicio, 
    $h_fin
);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error SQL: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>