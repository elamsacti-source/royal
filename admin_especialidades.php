<?php
header('Content-Type: application/json');
include 'db_config.php'; // IMPORTANTE

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $res = $conn->query("SELECT * FROM especialidades ORDER BY nombre");
    $out = []; while($r=$res->fetch_assoc()) $out[]=$r;
    echo json_encode($out);
} else {
    $d = json_decode(file_get_contents("php://input"), true);
    $conn->query("INSERT INTO especialidades (nombre) VALUES ('".$d['nombre']."')");
    echo json_encode(['success'=>true]);
}
?>