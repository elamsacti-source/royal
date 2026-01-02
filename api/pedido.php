<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
require_once '../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) { echo json_encode(['success'=>false, 'message'=>'Datos vacíos']); exit; }

$cliente = $input['cliente'];
$carrito = $input['carrito'];
$total   = $input['total'];
$metodo  = $input['metodo'];
$lat     = $cliente['lat'] ?? null;
$lon     = $cliente['lon'] ?? null;
$id_sede = 2; // Sede principal

try {
    $pdo->beginTransaction();

    // 1. Crear Venta
    $sqlVenta = "INSERT INTO ventas (total, metodo_pago, fecha, tipo_venta, estado_delivery, 
                 nombre_contacto, telefono_contacto, direccion_entrega, latitud, longitud) 
                 VALUES (?, ?, NOW(), 'delivery', 'pendiente', ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sqlVenta);
    $stmt->execute([$total, $metodo, $cliente['nombre'], $cliente['telefono'], $cliente['direccion'], $lat, $lon]);
    $id_venta = $pdo->lastInsertId();

    // 2. Insertar Detalles y DESCONTAR STOCK
    $sqlDet = "INSERT INTO ventas_detalle (id_venta, id_producto, cantidad, precio_historico, subtotal) VALUES (?, ?, ?, ?, ?)";
    $sqlStock = "UPDATE productos_sedes SET stock = stock - ? WHERE id_producto = ? AND id_sede = ?";
    $sqlKardex = "INSERT INTO kardex (id_producto, id_sede, tipo_movimiento, cantidad, stock_resultante, nota, fecha) VALUES (?, ?, 'venta_web', ?, 0, ?, NOW())";

    $stmtDet = $pdo->prepare($sqlDet);
    $stmtStock = $pdo->prepare($sqlStock);
    $stmtKardex = $pdo->prepare($sqlKardex);

    foreach ($carrito as $item) {
        // Guardar detalle
        $sub = $item['precio'] * $item['cantidad'];
        $stmtDet->execute([$id_venta, $item['id'], $item['cantidad'], $item['precio'], $sub]);
        
        // Descontar Stock
        $stmtStock->execute([$item['cantidad'], $item['id'], $id_sede]);
        
        // Kardex
        $stmtKardex->execute([$item['id'], $id_sede, -$item['cantidad'], "Pedido Web #$id_venta"]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'id_pedido' => $id_venta]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>