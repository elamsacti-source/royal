<?php
include_once 'session.php'; include 'db_config.php';
header('Content-Type: application/json');
if ($_SESSION['user_role'] !== 'admin') exit;

$in = json_decode(file_get_contents('php://input'), true);
$sup_id = $in['supervisor_id'];
$areas = $in['area_ids'];

$conn->query("DELETE FROM supervisor_area_permissions WHERE supervisor_id = $sup_id");
$stmt = $conn->prepare("INSERT INTO supervisor_area_permissions (supervisor_id, area_id) VALUES (?, ?)");
foreach($areas as $aid) {
    $stmt->bind_param("ii", $sup_id, $aid);
    $stmt->execute();
}
echo json_encode(['success'=>true]);
$conn->close();
?>