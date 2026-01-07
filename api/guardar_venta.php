<?php
require '../config/db.php';
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) { echo json_encode(['success'=>false]); exit; }

try {
    $pdo->beginTransaction(); // Iniciar transacción segura

    // 1. Cabecera Venta
    $total = 0;
    foreach($input['items'] as $i) $total += $i['subtotal'];
    
    $stmt = $pdo->prepare("INSERT INTO ventas (total, usuario_id) VALUES (?, ?)");
    $stmt->execute([$total, $input['usuario_id']]);
    $venta_id = $pdo->lastInsertId();

    // 2. Detalles y Kardex
    foreach ($input['items'] as $item) {
        // Obtener costo actual para reporte de ganancias
        $stmtProd = $pdo->prepare("SELECT costo, stock FROM productos WHERE id = ?");
        $stmtProd->execute([$item['id']]);
        $productoDB = $stmtProd->fetch();

        // Verificar stock (opcional, aquí permitimos negativo pero idealmente validamos)
        
        // Insertar Detalle
        $stmtDet = $pdo->prepare("INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_venta, costo_historico) VALUES (?, ?, ?, ?, ?)");
        $stmtDet->execute([$venta_id, $item['id'], $item['cantidad'], $item['precio'], $productoDB['costo']]);

        // Descontar Stock
        $nuevoStock = $productoDB['stock'] - $item['cantidad'];
        $pdo->prepare("UPDATE productos SET stock = ? WHERE id = ?")->execute([$nuevoStock, $item['id']]);

        // Escribir en KARDEX
        $stmtKardex = $pdo->prepare("INSERT INTO kardex (producto_id, tipo, cantidad, stock_saldo) VALUES (?, 'VENTA', ?, ?)");
        $stmtKardex->execute([$item['id'], $item['cantidad'], $nuevoStock]);
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>