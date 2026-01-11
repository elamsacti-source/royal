<?php
// royal/api/login.php
header('Content-Type: application/json');
require_once '../config/db.php';

// Recibir datos JSON desde Android
$input = json_decode(file_get_contents('php://input'), true);
$usuario = $input['usuario'] ?? '';
$password = $input['password'] ?? '';

// Verificar credenciales
$stmt = $pdo->prepare("SELECT id, nombre, rol FROM usuarios WHERE usuario = ? AND activo = 1");
$stmt->execute([$usuario]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && $password == $input['password']) { // Ojo: Si usas hash en el futuro, cambia esto
    if ($user['rol'] == 'driver') {
        echo json_encode([
            'success' => true,
            'message' => 'Bienvenido Driver',
            'user_id' => $user['id'],
            'nombre' => $user['nombre']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Solo acceso para Drivers']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Usuario o clave incorrectos']);
}
?>