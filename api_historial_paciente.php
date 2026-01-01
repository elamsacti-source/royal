<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
// Desactivar errores en pantalla
ini_set('display_errors', 0);
error_reporting(E_ALL);

include 'db_config.php';

$dni = $_GET['dni'] ?? '';

if (empty($dni)) {
    echo json_encode(null);
    exit;
}

// Buscamos el registro más reciente (ID más alto) de este DNI en la tabla citas
$sql = "SELECT telefono, historia_clinica 
        FROM citas 
        WHERE dni = ? 
        ORDER BY id DESC 
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $dni);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    echo json_encode($row); // Devolvemos {telefono: "...", historia_clinica: "..."}
} else {
    echo json_encode(null); // No existe historial previo
}

$stmt->close();
$conn->close();
?>