<?php
// royal/api/pedido.php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
require_once '../config/db.php'; //

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) { echo json_encode(['success'=>false, 'message'=>'Datos vacíos']); exit; }

$cliente = $input['cliente'];
$carrito = $input['carrito'];
$total   = $input['total'];
$metodo  = $input['metodo'];
$lat     = $cliente['lat'] ?? null;
$lon     = $cliente['lon'] ?? null;
$id_sede = 2; // Sede principal (Delivery)

try {
    $pdo->beginTransaction();

    // --- 1. VALIDACIÓN DE SEGURIDAD (STOCK) ---
    foreach ($carrito as $item) {
        // Consultamos el stock ACTUAL en este milisegundo (bloqueando fila para evitar race conditions)
        $stmtCheck = $pdo->prepare("SELECT stock FROM productos_sedes WHERE id_producto = ? AND id_sede = ? FOR UPDATE");
        $stmtCheck->execute([$item['id'], $id_sede]);
        $stockReal = $stmtCheck->fetchColumn();

        // Consultamos si es combo (Los combos a veces tienen stock 0 pero se componen de otros)
        $stmtInfo = $pdo->prepare("SELECT es_combo, nombre FROM productos WHERE id = ?");
        $stmtInfo->execute([$item['id']]);
        $infoProd = $stmtInfo->fetch();

        // REGLA: Si NO es combo y piden más de lo que hay -> ERROR
        if ($infoProd['es_combo'] == 0) {
            if ($stockReal < $item['cantidad']) {
                throw new Exception("Stock insuficiente para: " . $infoProd['nombre'] . " (Quedan: " . (int)$stockReal . ")");
            }
        }
    }

    // --- 2. CREAR VENTA (Si pasó la validación) ---
    $sqlVenta = "INSERT INTO ventas (total, metodo_pago, fecha, tipo_venta, estado_delivery, 
                 nombre_contacto, telefono_contacto, direccion_entrega, latitud, longitud) 
                 VALUES (?, ?, NOW(), 'delivery', 'pendiente', ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sqlVenta);
    $stmt->execute([$total, $metodo, $cliente['nombre'], $cliente['telefono'], $cliente['direccion'], $lat, $lon]);
    $id_venta = $pdo->lastInsertId();

    // --- 3. INSERTAR DETALLES Y DESCONTAR ---
    $sqlDet = "INSERT INTO ventas_detalle (id_venta, id_producto, cantidad, precio_historico, subtotal) VALUES (?, ?, ?, ?, ?)";
    $sqlStock = "UPDATE productos_sedes SET stock = stock - ? WHERE id_producto = ? AND id_sede = ?";
    $sqlKardex = "INSERT INTO kardex (id_producto, id_sede, tipo_movimiento, cantidad, stock_resultante, nota, fecha) VALUES (?, ?, 'venta_web', ?, 0, ?, NOW())";

    $stmtDet = $pdo->prepare($sqlDet);
    $stmtStock = $pdo->prepare($sqlStock);
    $stmtKardex = $pdo->prepare($sqlKardex);

    foreach ($carrito as $item) {
        // Detalle
        $sub = $item['precio'] * $item['cantidad'];
        $stmtDet->execute([$id_venta, $item['id'], $item['cantidad'], $item['precio'], $sub]);
        
        // Descontar Stock (Aquí ya sabemos que hay suficiente por la validación del paso 1)
        // OJO: Si es Combo, la lógica debería ser descontar insumos (como en backend_venta.php). 
        // Por simplicidad en web, aquí descontamos el ID directo. Si usas combos complejos, avísame para ajustar esto.
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