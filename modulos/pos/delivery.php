<?php
session_start();
require_once '../../config/db.php';

// Seguridad
if (!isset($_SESSION['user_id'])) { header("Location: ../../index.php"); exit; }

// PROCESAR ACCIONES
if (isset($_POST['accion'])) {
    $id_venta = $_POST['id_venta'];
    
    if ($_POST['accion'] == 'confirmar') {
        // BLINDAJE: Solo confirmar si sigue en 'pendiente'. 
        // Si ya está 'en_camino', NO hacemos nada para no patear al driver.
        $stmt = $pdo->prepare("UPDATE ventas SET estado_delivery = 'confirmado' WHERE id = ? AND estado_delivery = 'pendiente'");
        $stmt->execute([$id_venta]);
    }
    
    if ($_POST['accion'] == 'cancelar') {
        // Cancelar y devolver stock
        $pdo->prepare("UPDATE ventas SET estado_delivery = 'cancelado' WHERE id = ?")->execute([$id_venta]);
        
        // Devolver Stock
        $items = $pdo->query("SELECT id_producto, cantidad FROM ventas_detalle WHERE id_venta = $id_venta")->fetchAll();
        $id_sede = 2; 
        foreach($items as $i) {
            $pdo->prepare("UPDATE productos_sedes SET stock = stock + ? WHERE id_producto = ? AND id_sede = ?")
                ->execute([$i['cantidad'], $i['id_producto'], $id_sede]);
            
            $pdo->prepare("INSERT INTO kardex (id_producto, id_sede, tipo_movimiento, cantidad, stock_resultante, nota, fecha) VALUES (?, ?, 'entrada_devolucion', ?, 0, ?, NOW())")
                ->execute([$i['id_producto'], $id_sede, $i['cantidad'], "Rechazo Pedido #$id_venta"]);
        }
    }
    // Recargar rápido para ver cambios
    header("Location: delivery.php");
    exit;
}

// CONSULTAR PEDIDOS
$sql = "SELECT v.*, u.nombre as driver_nombre 
        FROM ventas v 
        LEFT JOIN usuarios u ON v.id_driver = u.id
        WHERE tipo_venta = 'delivery' 
        AND estado_delivery IN ('pendiente', 'confirmado', 'en_camino')
        ORDER BY FIELD(estado_delivery, 'pendiente', 'en_camino', 'confirmado'), v.id DESC";
$pedidos = $pdo->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión Delivery</title>
    <link rel="stylesheet" href="../../assets/css/estilos.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #111; padding: 20px; font-family: sans-serif; }
        .header-top { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid #333; padding-bottom:15px; }
        
        .card-pedido { background: #1a1a1a; border: 1px solid #333; padding: 15px; border-radius: 10px; margin-bottom: 15px; position:relative; }
        
        /* Estados visuales */
        .pendiente { border: 2px solid #ef5350; animation: parpadeo 2s infinite; }
        .confirmado { border: 2px solid #FFD700; }
        .en_camino { border: 2px solid #66bb6a; background: #0f1f0f; }

        @keyframes parpadeo { 0% { box-shadow: 0 0 5px #ef5350; } 50% { box-shadow: 0 0 15px #ef5350; } 100% { box-shadow: 0 0 5px #ef5350; } }

        .btn-accion { flex: 1; padding: 12px; border: none; font-weight: bold; cursor: pointer; border-radius: 5px; color: #fff; font-size: 1rem; }
        .btn-accept { background: #66bb6a; color: #000; }
        .btn-cancel { background: #ef5350; }
        .btn-refresh { background: var(--royal-gold); color: #000; border: none; padding: 10px 20px; border-radius: 30px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; }
    </style>
</head>
<body>

    <div class="header-top">
        <div>
            <h2 style="color:#fff; margin:0;"><i class="fa-solid fa-motorcycle"></i> Pedidos Web</h2>
            <small style="color:#888;">Se actualiza automáticamente cada 5s</small>
        </div>
        
        <div style="display:flex; gap:10px;">
            <a href="delivery.php" class="btn-refresh"><i class="fa-solid fa-rotate"></i> REFRESCAR</a>
            <a href="venta.php" class="btn-royal" style="width:auto; background:#333; color:#fff;">Volver al POS</a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px;">
        <?php foreach($pedidos as $p): ?>
            <div class="card-pedido <?= $p['estado_delivery'] ?>">
                
                <div style="display:flex; justify-content:space-between; font-weight:bold; font-size:1.1rem; color:#fff; margin-bottom:10px;">
                    <span>#<?= $p['id'] ?></span>
                    <span>S/ <?= number_format($p['total'], 2) ?></span>
                </div>
                
                <div style="color:#ccc; margin-bottom:10px; font-size:0.95rem;">
                    <div style="margin-bottom:5px;"><i class="fa-solid fa-user"></i> <?= $p['nombre_contacto'] ?></div>
                    <div style="margin-bottom:5px;">
                        <i class="fa-brands fa-whatsapp"></i> 
                        <a href="https://wa.me/51<?= preg_replace('/[^0-9]/','',$p['telefono_contacto']) ?>" target="_blank" style="color:#66bb6a; text-decoration:none;">
                            <?= $p['telefono_contacto'] ?>
                        </a>
                    </div>
                    <div><i class="fa-solid fa-map-pin"></i> <?= $p['direccion_entrega'] ?></div>
                    
                    <?php if($p['latitud']): ?>
                        <div style="margin-top:8px;">
                            <a href="../../public/track.php?id=<?= $p['id'] ?>" target="_blank" class="btn-royal" style="padding:5px 10px; font-size:0.8rem; background:#333; color:#4fc3f7; display:inline-block; width:auto;">
                                <i class="fa-solid fa-map-location-dot"></i> Ver Mapa GPS
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="background:#000; padding:10px; border-radius:5px; margin-bottom:15px; font-size:0.9rem; color:#888;">
                    <?php 
                        $detalles = $pdo->query("SELECT d.cantidad, p.nombre FROM ventas_detalle d JOIN productos p ON d.id_producto = p.id WHERE d.id_venta = {$p['id']}")->fetchAll();
                        foreach($detalles as $d) { echo "<div>{$d['cantidad']} x {$d['nombre']}</div>"; }
                    ?>
                    <div style="margin-top:5px; color:#FFD700; font-weight:bold;">Pago: <?= $p['metodo_pago'] ?></div>
                </div>

                <?php if($p['estado_delivery'] == 'pendiente'): ?>
                    <div style="display:flex; gap:10px;">
                        <form method="POST" style="flex:1;">
                            <input type="hidden" name="id_venta" value="<?= $p['id'] ?>">
                            <button name="accion" value="confirmar" class="btn-accion btn-accept">ACEPTAR</button>
                        </form>
                        <form method="POST" style="flex:1;">
                            <input type="hidden" name="id_venta" value="<?= $p['id'] ?>">
                            <button name="accion" value="cancelar" class="btn-accion btn-cancel" onclick="return confirm('¿Rechazar?');">RECHAZAR</button>
                        </form>
                    </div>

                <?php elseif($p['estado_delivery'] == 'confirmado'): ?>
                    <div style="text-align:center; color:#FFD700; border:1px dashed #FFD700; padding:10px; border-radius:5px;">
                        <i class="fa-solid fa-spinner fa-spin"></i> Esperando Driver...
                    </div>

                <?php elseif($p['estado_delivery'] == 'en_camino'): ?>
                    <div style="text-align:center; color:#66bb6a; border:1px solid #66bb6a; padding:10px; border-radius:5px; background:rgba(102, 187, 106, 0.1);">
                        <i class="fa-solid fa-motorcycle"></i> EN RUTA<br>
                        <small>Driver: <b><?= $p['driver_nombre'] ?: 'Asignado' ?></b></small>
                    </div>
                <?php endif; ?>

            </div>
        <?php endforeach; ?>

        <?php if(count($pedidos) == 0): ?>
            <p style="color:#666; text-align:center; grid-column:1/-1; margin-top:50px;">No hay pedidos activos.</p>
        <?php endif; ?>
    </div>

    <script>
        // Actualizar cada 5 segundos para que no se quede pegado
        setTimeout(() => { location.reload(); }, 5000);
    </script>
</body>
</html>