<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
// Desactivar errores en pantalla
ini_set('display_errors', 0);
error_reporting(E_ALL);

include 'db_config.php';

$input = json_decode(file_get_contents("php://input"), true);

if (empty($input['id'])) {
    echo json_encode(['success' => false, 'message' => 'Falta ID']);
    exit;
}

$id = $input['id'];
$campo = $input['campo']; // 'ticket' o 'hc'
$valor = $input['valor'];

// Validar que el campo sea seguro
$campos_permitidos = ['ticket', 'historia_clinica'];
if (!in_array($campo, $campos_permitidos)) {
    echo json_encode(['success' => false, 'message' => 'Campo no permitido']);
    exit;
}

// Actualizar solo ese campo en la tabla citas
$stmt = $conn->prepare("UPDATE citas SET $campo = ? WHERE id = ?");
$stmt->bind_param("si", $valor, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}

$stmt->close();
$conn->close();
?>