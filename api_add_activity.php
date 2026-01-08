<?php
include_once 'session.php'; include 'db_config.php';
header('Content-Type: application/json');

if ($_SESSION['user_role'] !== 'admin') exit;
$in = json_decode(file_get_contents('php://input'), true);

$stmt = $conn->prepare("INSERT INTO checklist_activities (area_id, nombre, activo) VALUES (?, ?, 1)");
$stmt->bind_param("is", $in['area_id'], $in['nombre']);

if($stmt->execute()) echo json_encode(['success'=>true, 'id'=>$stmt->insert_id]);
else echo json_encode(['success'=>false]);
$conn->close();
?>