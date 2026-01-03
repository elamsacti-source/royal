<?php
// royal/api/pedido.php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
require_once '../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) { echo json_encode(['success'=>false, 'message'=>'Datos vacíos']); exit; }

$cliente = $input['cliente'];
$carrito = $input['carrito'];
$total   = $input['total'];
$metodo  = $input['metodo'];
$id_cli  = $input['id_cliente'] ?? null;
$lat     = $cliente['lat'] ?? null;
$lon     = $cliente['lon'] ?? null;
$id_sede = 2; 

try {
    $pdo->beginTransaction();

    // --- 1. VALIDACIÓN DE STOCK ---
    foreach ($carrito as $item) {
        // Información del producto y factor caja
        $stmtInfo = $pdo->prepare("SELECT es_combo, nombre, unidades_caja FROM productos WHERE id = ?");
        $stmtInfo->execute([$item['id']]);
        $infoProd = $stmtInfo->fetch();

        // Determinar cantidad real a descontar (Unidades o Cajas convertidas)
        $cantidad_real = $item['cantidad'];
        if (isset($item['modo']) && $item['modo'] === 'caja') {
            $factor = $infoProd['unidades_caja'] > 0 ? $infoProd['unidades_caja'] : 1;
            $cantidad_real = $item['cantidad'] * $factor;
        }

        if ($infoProd['es_combo'] == 0) {
            $stmtCheck = $pdo->prepare("SELECT stock FROM productos_sedes WHERE id_producto = ? AND id_sede = ? FOR UPDATE");
            $stmtCheck->execute([$item['id'], $id_sede]);
            $stockReal = $stmtCheck->fetchColumn();

            if ($stockReal < $cantidad_real) {
                throw new Exception("Stock insuficiente para: " . $infoProd['nombre'] . " (Pides $cantidad_real unid, quedan $stockReal)");
            }
        }
    }

    // --- 2. INSERTAR VENTA ---
    $sqlVenta = "INSERT INTO ventas (id_cliente, total, metodo_pago, fecha, tipo_venta, estado_delivery, 
                 nombre_contacto, telefono_contacto, direccion_entrega, latitud, longitud) 
                 VALUES (?, ?, ?, NOW(), 'delivery', 'pendiente', ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sqlVenta);
    $stmt->execute([
        $id_cli, $total, $metodo, $cliente['nombre'], $cliente['telefono'], $cliente['direccion'], $lat, $lon
    ]);
    $id_venta = $pdo->lastInsertId();

    // --- 3. PROCESAR DETALLES Y KARDEX ---
    $sqlDet = "INSERT INTO ventas_detalle (id_venta, id_producto, cantidad, precio_historico, subtotal) VALUES (?, ?, ?, ?, ?)";
    $stmtDet = $pdo->prepare($sqlDet);
    
    $stmtReceta = $pdo->prepare("SELECT id_producto, cantidad FROM combos_detalle WHERE id_combo = ?");

    foreach ($carrito as $item) {
        // Insertar Detalle (Tal cual lo pidió el cliente: "2 Cajas")
        $sub = $item['precio'] * $item['cantidad'];
        $stmtDet->execute([$id_venta, $item['id'], $item['cantidad'], $item['precio'], $sub]);
        
        // Calcular Descuento Real
        $es_combo = isset($item['es_combo']) ? $item['es_combo'] : 0;
        $modo = isset($item['modo']) ? $item['modo'] : 'unidad';
        $nota_extra = "";

        if ($es_combo == 1) {
            // Descontar ingredientes del combo
            $stmtReceta->execute([$item['id']]);
            $insumos = $stmtReceta->fetchAll();
            foreach ($insumos as $ins) {
                $cant_total = $ins['cantidad'] * $item['cantidad'];
                procesarMovimientoStock($pdo, $ins['id_producto'], $id_sede, $cant_total, "Venta Web Pack #$id_venta");
            }
        } else {
            // Producto Simple: Verificar modo caja
            $stmtFactor = $pdo->prepare("SELECT unidades_caja FROM productos WHERE id = ?");
            $stmtFactor->execute([$item['id']]);
            $factor = $stmtFactor->fetchColumn() ?: 1;

            $cantidad_desc = $item['cantidad'];
            
            if ($modo === 'caja') {
                $cantidad_desc = $item['cantidad'] * $factor;
                $nota_extra = " ({$item['cantidad']} Cajas x $factor)";
            }

            procesarMovimientoStock($pdo, $item['id'], $id_sede, $cantidad_desc, "Venta Web #$id_venta" . $nota_extra);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'id_pedido' => $id_venta]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function procesarMovimientoStock($pdo, $id_prod, $id_sede, $cantidad, $nota) {
    // 1. Descontar
    $pdo->prepare("UPDATE productos_sedes SET stock = stock - ? WHERE id_producto = ? AND id_sede = ?")
        ->execute([$cantidad, $id_prod, $id_sede]);

    // 2. Saldo
    $stmtGet = $pdo->prepare("SELECT stock FROM productos_sedes WHERE id_producto = ? AND id_sede = ?");
    $stmtGet->execute([$id_prod, $id_sede]);
    $nuevo_saldo = $stmtGet->fetchColumn();

    // 3. Kardex
    $pdo->prepare("INSERT INTO kardex (id_producto, id_sede, tipo_movimiento, cantidad, stock_resultante, nota, fecha) 
                   VALUES (?, ?, 'venta_web', ?, ?, ?, NOW())")
        ->execute([$id_prod, $id_sede, -$cantidad, $nuevo_saldo, $nota]);
}
?>