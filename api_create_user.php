<?php
include_once 'session.php'; 
include 'db_config.php'; // Asegura usar db_config
header('Content-Type: application/json');

if ($_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success'=>false, 'error'=>'No autorizado']);
    exit;
}

$in = json_decode(file_get_contents('php://input'), true);

$nombre = $in['name'];
$usuario = $in['email']; // Usaremos el email como nombre de usuario
$pass = $in['password'];
$rol = $in['role'];

// Insertar en la tabla CORRECTA: usuarios
$stmt = $conn->prepare("INSERT INTO usuarios (nombre_completo, usuario, password, rol, email) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $nombre, $usuario, $pass, $rol, $usuario);

if($stmt->execute()) {
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false, 'error'=>$stmt->error]);
}
$conn->close();
?>