<?php
require_once '../../config/db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false, 'message'=>'Sesión caducada']); exit; }

// Obtener la sede del usuario
$id_usuario = $_SESSION['user_id'];
$stmtSede = $pdo->prepare("SELECT id_sede FROM usuarios WHERE id = ?");
$stmtSede->execute([$id_usuario]);
$usuario_data = $stmtSede->fetch();
$id_sede = $usuario_data['id_sede'] ?? 2;

$action = $_GET['action'] ?? '';

// ============================================================================
// 1. LISTAR CON CÁLCULO DE STOCK VIRTUAL PARA COMBOS Y DATOS DE CAJA
// ============================================================================
if ($action == 'listar') {
    // MODIFICADO: Agregamos precio_caja y unidades_caja a la consulta
    $sql = "SELECT p.id, p.codigo_barras, p.nombre, p.categoria, p.precio_venta, 
            p.precio_caja, p.unidades_caja, p.es_combo,
            COALESCE(ps.stock, 0) as stock_actual
            FROM productos p
            LEFT JOIN productos_sedes ps ON p.id = ps.id_producto AND ps.id_sede = ?
            ORDER BY p.nombre ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_sede]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($productos as &$prod) {
        $prod['descripcion_combo'] = "";
        
        // --- LÓGICA DE CÁLCULO DE STOCK PARA PACKS ---
        if ($prod['es_combo'] == 1) {
            $sqlDet = "SELECT p.nombre, cd.cantidad, cd.id_producto 
                       FROM combos_detalle cd 
                       JOIN productos p ON cd.id_producto = p.id 
                       WHERE cd.id_combo = ?";
            $stmtDet = $pdo->prepare($sqlDet);
            $stmtDet->execute([$prod['id']]);
            $ingredientes = $stmtDet->fetchAll();

            $textos = [];
            $maximos_posibles = []; 

            foreach ($ingredientes as $ing) {
                $textos[] = $ing['cantidad'] . " " . $ing['nombre'];
                
                $stmtStockIng = $pdo->prepare("SELECT stock FROM productos_sedes WHERE id_producto = ? AND id_sede = ?");
                $stmtStockIng->execute([$ing['id_producto'], $id_sede]);
                $stock_insumo = $stmtStockIng->fetchColumn() ?: 0;
                
                if ($ing['cantidad'] > 0) {
                    $alcanza_para = floor($stock_insumo / $ing['cantidad']);
                    $maximos_posibles[] = $alcanza_para;
                } else {
                    $maximos_posibles[] = 0;
                }
            }

            if (count($maximos_posibles) > 0) {
                $prod['stock_actual'] = min($maximos_posibles);
            } else {
                $prod['stock_actual'] = 0; 
            }

            if(count($textos) > 0) $prod['descripcion_combo'] = "(" . implode(" + ", $textos) . ")";
        }
    }
    
    echo json_encode($productos);
    exit;
}

// ============================================================================
// 2. PROCESAR VENTA
// ============================================================================
if ($action == 'procesar') {
    $input = json_decode(file_get_contents('php://input'), true);
    $items = $input['items'] ?? [];
    $total = $input['total'] ?? 0;
    $metodo_pago = $input['metodo_pago'] ?? 'Efectivo';

    // Validar Caja
    $stmt = $pdo->prepare("SELECT id FROM caja_sesiones WHERE id_usuario = ? AND estado = 'abierta'");
    $stmt->execute([$id_usuario]);
    $caja = $stmt->fetch();
    if (!$caja) { echo json_encode(['success'=>false, 'message'=>'Caja cerrada.']); exit; }

    try {
        $pdo->beginTransaction();

        $sqlVenta = "INSERT INTO ventas (id_caja_sesion, total, metodo_pago, fecha) VALUES (?, ?, ?, NOW())";
        $stmtV = $pdo->prepare($sqlVenta);
        $stmtV->execute([$caja['id'], $total, $metodo_pago]);
        $id_venta = $pdo->lastInsertId();

        foreach ($items as $item) {
            $id_prod = $item['id'];
            $cant = $item['cantidad'];
            $modo = $item['modo'] ?? 'unidad'; // MODIFICADO: Recibimos el modo (caja/unidad)
            
            $sqlDet = "INSERT INTO ventas_detalle (id_venta, id_producto, cantidad, precio_historico, subtotal) VALUES (?, ?, ?, ?, ?)";
            $pdo->prepare($sqlDet)->execute([$id_venta, $id_prod, $cant, $item['precio'], $item['precio']*$cant]);

            // DESCONTAR
            if ($item['es_combo'] == 1) {
                $stmtReceta = $pdo->prepare("SELECT id_producto, cantidad FROM combos_detalle WHERE id_combo = ?");
                $stmtReceta->execute([$id_prod]);
                $insumos = $stmtReceta->fetchAll();
                foreach ($insumos as $insumo) {
                    descontarStockSede($pdo, $insumo['id_producto'], $id_sede, $insumo['cantidad'] * $cant, "Venta Pack #$id_venta");
                }
            } else {
                // MODIFICADO: Lógica para descontar Cajas vs Unidades
                $cantidad_a_descontar = $cant;
                $nota_kardex = "Venta #$id_venta";

                if ($modo === 'caja') {
                    // Consultamos el factor de conversión real de la BD por seguridad
                    $stmtFactor = $pdo->prepare("SELECT unidades_caja FROM productos WHERE id = ?");
                    $stmtFactor->execute([$id_prod]);
                    $factor = $stmtFactor->fetchColumn() ?: 1;
                    
                    $cantidad_a_descontar = $cant * $factor;
                    $nota_kardex .= " ($cant Cajas x $factor)";
                } else {
                    // Modo unidad normal
                }

                descontarStockSede($pdo, $id_prod, $id_sede, $cantidad_a_descontar, $nota_kardex);
            }
        }

        $pdo->commit();
        echo json_encode(['success'=>true, 'id_venta'=>$id_venta]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
    }
    exit;
}

function descontarStockSede($pdo, $id_prod, $id_sede, $cantidad, $nota) {
    // Asegurar registro
    $check = $pdo->prepare("SELECT id FROM productos_sedes WHERE id_producto = ? AND id_sede = ?");
    $check->execute([$id_prod, $id_sede]);
    if(!$check->fetch()) {
        $pdo->prepare("INSERT INTO productos_sedes (id_producto, id_sede, stock) VALUES (?, ?, 0)")->execute([$id_prod, $id_sede]);
    }

    $sqlUpd = "UPDATE productos_sedes SET stock = stock - ? WHERE id_producto = ? AND id_sede = ?";
    $pdo->prepare($sqlUpd)->execute([$cantidad, $id_prod, $id_sede]);

    // Kardex
    $sqlK = "INSERT INTO kardex (id_producto, id_sede, tipo_movimiento, cantidad, stock_resultante, nota, fecha) VALUES (?, ?, 'venta', ?, 0, ?, NOW())";
    $pdo->prepare($sqlK)->execute([$id_prod, $id_sede, -$cantidad, $nota]);
}
?>