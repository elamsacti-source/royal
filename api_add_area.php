<?php
include 'db_config.php';
header('Content-Type: application/json');
$in = json_decode(file_get_contents('php://input'), true);

$emoji = $in['emoji'] ?? '📋';
$stmt = $conn->prepare("INSERT INTO checklist_areas (nombre, codigo, emoji) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $in['nombre'], $in['codigo'], $emoji);

if($stmt->execute()) echo json_encode(['success'=>true]);
else echo json_encode(['success'=>false, 'error'=>$stmt->error]);
$conn->close();
?>