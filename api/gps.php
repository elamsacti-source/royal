<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
require_once '../config/db.php';

$action = $_GET['action'] ?? '';

// POST: Driver envía ubicación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if($input['id_venta']) {
        $stmt = $pdo->prepare("UPDATE ventas SET driver_lat = ?, driver_lon = ? WHERE id = ?");
        $stmt->execute([$input['lat'], $input['lon'], $input['id_venta']]);
    }
    exit;
}

// GET: Cliente lee ubicación
if ($action === 'leer' && isset($_GET['id_venta'])) {
    $stmt = $pdo->prepare("SELECT driver_lat, driver_lon, estado_delivery FROM ventas WHERE id = ?");
    $stmt->execute([$_GET['id_venta']]);
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    exit;
}
?>