<?php
include 'db_config.php';
header('Content-Type: application/json');
$in = json_decode(file_get_contents('php://input'), true);
$stmt = $conn->prepare("UPDATE sedes SET activo=0 WHERE id=?");
$stmt->bind_param("i", $in['id']);
if($stmt->execute()) echo json_encode(['success'=>true]);
$conn->close();
?>