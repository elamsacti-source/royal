<?php
require_once '../config/db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$id_user = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

// --- LISTAR ---
if ($action == 'listar') {
    $stmt = $pdo->prepare("SELECT * FROM direcciones_usuarios WHERE id_usuario = ? ORDER BY id DESC");
    $stmt->execute([$id_user]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// --- GUARDAR (AHORA CON LAT/LON) ---
if ($action == 'guardar' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $etiqueta   = trim($_POST['etiqueta']);
    $direccion  = trim($_POST['direccion']); // Dirección escrita (referencial)
    $referencia = trim($_POST['referencia']);
    $lat        = $_POST['lat'] ?? null;
    $lon        = $_POST['lon'] ?? null;

    if (empty($direccion) || empty($etiqueta)) {
        echo json_encode(['success' => false, 'message' => 'Falta el nombre o la dirección']);
        exit;
    }

    // Guardamos las coordenadas
    $sql = "INSERT INTO direcciones_usuarios (id_usuario, etiqueta, direccion, referencia, lat, lon) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$id_user, $etiqueta, $direccion, $referencia, $lat, $lon])) {
        echo json_encode(['success' => true, 'message' => 'Ubicación guardada con éxito']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar en BD']);
    }
    exit;
}

// --- BORRAR ---
if ($action == 'borrar') {
    $id = $_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM direcciones_usuarios WHERE id = ? AND id_usuario = ?");
    if ($stmt->execute([$id, $id_user])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}
?>