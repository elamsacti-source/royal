<?php
include 'db_config.php';
header('Content-Type: application/json');
$sup_id = $_GET['supervisor_id'];
$perms = [];
$res = $conn->query("SELECT area_id FROM supervisor_area_permissions WHERE supervisor_id = $sup_id");
while($r = $res->fetch_assoc()) $perms[] = $r['area_id'];
echo json_encode(['supervisor_permissions' => $perms]);
$conn->close();
?>