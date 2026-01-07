<?php
require '../config/db.php';
$q = $_GET['q'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM productos WHERE nombre LIKE ? OR codigo = ? LIMIT 10");
$stmt->execute(["%$q%", $q]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>