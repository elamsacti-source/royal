<?php
include_once 'session.php'; include 'db_config.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $sup_id = $_GET['supervisor_id'] ?? ($_SESSION['supervisor_id'] ?? null);
    if (!$sup_id) { echo json_encode([]); exit; }
    
    $res = $conn->query("SELECT * FROM supervisor_schedule WHERE supervisor_id = $sup_id");
    $sched = [];
    while($row = $res->fetch_assoc()) {
        $sched[$row['dia_semana']][] = $row;
    }
    echo json_encode(['success'=>true, 'schedule'=>$sched]);
}

if ($method === 'POST') {
    // Solo admin guarda
    if ($_SESSION['user_role'] !== 'admin') exit;
    $in = json_decode(file_get_contents('php://input'), true);
    $sup_id = $in['supervisor_id'];
    
    $conn->query("DELETE FROM supervisor_schedule WHERE supervisor_id = $sup_id");
    $stmt = $conn->prepare("INSERT INTO supervisor_schedule (supervisor_id, dia_semana, sede_id, turno) VALUES (?,?,?,?)");
    foreach ($in['schedule'] as $item) {
        $stmt->bind_param("isis", $sup_id, $item['dia_semana'], $item['sede_id'], $item['turno']);
        $stmt->execute();
    }
    echo json_encode(['success'=>true]);
}
$conn->close();
?>