<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

// Activar reporte de errores para ver qué pasa
ini_set('display_errors', 0);
error_reporting(E_ALL);

// CORRECCIÓN: Usar el nombre correcto del archivo de conexión
include 'db_config.php';

if ($conn->connect_error) {
    echo json_encode(['success'=>false, 'error'=>'Error BD: ' . $conn->connect_error]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $res = $conn->query("SELECT * FROM tipos_servicio ORDER BY nombre");
    $out = []; 
    while($r=$res->fetch_assoc()) $out[]=$r;
    echo json_encode($out);
} 
else {
    // Método POST (Guardar)
    $d = json_decode(file_get_contents("php://input"), true);
    
    if (empty($d['nombre'])) {
        echo json_encode(['success'=>false, 'error'=>'Nombre vacío']);
        exit;
    }

    // Usar Prepared Statement para evitar errores de comillas
    $stmt = $conn->prepare("INSERT INTO tipos_servicio (nombre) VALUES (?)");
    $stmt->bind_param("s", $d['nombre']);
    
    if ($stmt->execute()) {
        echo json_encode(['success'=>true]);
    } else {
        echo json_encode(['success'=>false, 'error'=>$stmt->error]);
    }
}
$conn->close();
?>