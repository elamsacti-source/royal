<?php
include_once 'session.php'; include 'db_config.php';
header('Content-Type: application/json');

if ($_SESSION['user_role'] !== 'admin') exit;
$in = json_decode(file_get_contents('php://input'), true);

$sup_id = $in['supervisor_id'];
$user_id = ($in['user_id'] === 'NULL') ? NULL : $in['user_id'];

// Limpiar vinculos previos de ese usuario
if ($user_id) $conn->query("UPDATE supervisores SET user_id = NULL WHERE user_id = $user_id");

$stmt = $conn->prepare("UPDATE supervisores SET user_id = ? WHERE id = ?");
$stmt->bind_param("ii", $user_id, $sup_id);

if($stmt->execute()) echo json_encode(['success'=>true]);
$conn->close();
?>