<?php
// royal/api/check_pedidos.php
header('Content-Type: application/json');
require_once '../config/db.php';

// Contar pedidos pendientes (estado 'pendiente')
$stmt = $pdo->query("SELECT COUNT(*) FROM ventas WHERE tipo_venta = 'delivery' AND estado_delivery = 'pendiente'");
$pendientes = $stmt->fetchColumn();

echo json_encode(['pendientes' => $pendientes]);
?>