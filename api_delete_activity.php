<?php
include_once 'session.php'; include 'db_config.php';
header('Content-Type: application/json');

if ($_SESSION['user_role'] !== 'admin') exit;
$in = json_decode(file_get_contents('php://input'), true);

// Borrado lógico (activo = 0) es más seguro
$stmt = $conn->prepare("UPDATE checklist_activities SET activo = 0 WHERE id = ?");
$stmt->bind_param("i", $in['id']);

if($stmt->execute()) echo json_encode(['success'=>true]);
else echo json_encode(['success'=>false]);
$conn->close();
?>