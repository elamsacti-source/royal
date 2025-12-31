<?php
include 'db_config.php';
header('Content-Type: application/json');

$in = json_decode(file_get_contents('php://input'), true);

if(empty($in['nombre'])) { 
    echo json_encode(['success'=>false, 'error'=>'Nombre vacío']); 
    exit; 
}

$stmt = $conn->prepare("INSERT INTO sedes (nombre, activo) VALUES (?, 1)");
$stmt->bind_param("s", $in['nombre']);

if($stmt->execute()) {
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false, 'error'=>$stmt->error]);
}
$conn->close();
?>