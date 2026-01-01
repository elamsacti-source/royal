<?php
include_once 'session.php'; include 'db_config.php';
header('Content-Type: application/json');

if ($_SESSION['user_role'] !== 'admin') exit;
$in = json_decode(file_get_contents('php://input'), true);

$id = $in['id']; $field = $in['field']; $val = $in['value'];
$allowed = ['nombre','criterio','frecuencia','requires_quantity','specific_date','fecha_inicio'];

if (in_array($field, $allowed)) {
    if ($val === '') $val = NULL;
    $stmt = $conn->prepare("UPDATE checklist_activities SET $field = ? WHERE id = ?");
    $stmt->bind_param("si", $val, $id);
    if($stmt->execute()) echo json_encode(['success'=>true]);
    else echo json_encode(['success'=>false, 'error'=>$stmt->error]);
}
$conn->close();
?>