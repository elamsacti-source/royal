<?php
include 'db_config.php';
header('Content-Type: application/json');

$data = ['users' => [], 'supervisores' => []];

// 1. Buscamos en 'usuarios' (la tabla correcta)
// Usamos alias (AS) para que el frontend no se rompa, ya que espera 'name' y 'email'
$ru = $conn->query("SELECT id, nombre_completo as name, usuario as email FROM usuarios WHERE rol='usuario' ORDER BY nombre_completo");
while($r = $ru->fetch_assoc()) $data['users'][] = $r;

// 2. Buscamos supervisores
$rs = $conn->query("SELECT id, nombre, user_id FROM supervisores WHERE activo=1 ORDER BY nombre");
while($r = $rs->fetch_assoc()) $data['supervisores'][] = $r;

echo json_encode($data);
$conn->close();
?>