<?php
// royal/api/check_pedidos.php
// Evitar que el navegador guarde caché de esta respuesta
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-Type: application/json');

require_once '../config/db.php';

try {
    // Contar pedidos pendientes (estado 'pendiente')
    $stmt = $pdo->query("SELECT COUNT(*) FROM ventas WHERE tipo_venta = 'delivery' AND estado_delivery = 'pendiente'");
    $pendientes = $stmt->fetchColumn();
    
    echo json_encode(['pendientes' => $pendientes]);

} catch (Exception $e) {
    echo json_encode(['pendientes' => 0, 'error' => $e->getMessage()]);
}
?>