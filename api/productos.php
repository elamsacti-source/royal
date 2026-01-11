<?php
// royal/api/productos.php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
require_once '../config/db.php';

// Configuración: Sede Delivery
$id_sede_delivery = 2; 

// MODIFICADO: Agregamos precio_caja y unidades_caja
$sql = "SELECT p.id, p.nombre, p.precio_venta, p.precio_caja, p.unidades_caja, p.imagen, p.categoria, p.es_combo,
        COALESCE(ps.stock, 0) as stock
        FROM productos p
        LEFT JOIN productos_sedes ps ON p.id = ps.id_producto AND ps.id_sede = ?
        WHERE (ps.stock > 0 OR p.es_combo = 1)
        ORDER BY p.categoria DESC, p.nombre ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id_sede_delivery]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>