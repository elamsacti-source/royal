<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
include 'db_config.php';

$in = json_decode(file_get_contents('php://input'), true);

if (empty($in['id']) || empty($in['fecha'])) {
    echo json_encode(['success' => false, 'error' => 'Faltan datos']);
    exit;
}

$id = intval($in['id']);
$fecha = $in['fecha'];
$producto_detalle = $in['detalle']; // Aquí vendrá "Especialidad - Dr. Tal"

// Actualizamos fecha y el detalle (producto)
$sql = "UPDATE tratamiento_cronograma SET fecha_programada = ?, producto = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $fecha, $producto_detalle, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}
$conn->close();
?>