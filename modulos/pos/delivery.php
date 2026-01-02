<?php
session_start();
require_once '../../config/db.php';

// Seguridad
if (!isset($_SESSION['user_id'])) { header("Location: ../../index.php"); exit; }

// PROCESAR ACCIONES
if (isset($_POST['accion'])) {
    $id_venta = $_POST['id_venta'];
    
    if ($_POST['accion'] == 'confirmar') {
        // Solo confirmar si sigue en 'pendiente'
        $stmt = $pdo->prepare("UPDATE ventas SET estado_delivery = 'confirmado' WHERE id = ? AND estado_delivery = 'pendiente'");
        $stmt->execute([$id_venta]);
    }
    
    if ($_POST['accion'] == 'cancelar') {
        // Cancelar pedido
        $pdo->prepare("UPDATE ventas SET estado_delivery = 'cancelado' WHERE id = ?")->execute([$id_venta]);
        
        // Devolver Stock
        $items = $pdo->query("SELECT id_producto, cantidad FROM ventas_detalle WHERE id_venta = $id_venta")->fetchAll();
        $id_sede = 2; // Sede Tienda
        foreach($items as $i) {
            $pdo->prepare("UPDATE productos_sedes SET stock = stock + ? WHERE id_producto = ? AND id_sede = ?")
                ->execute([$i['cantidad'], $i['id_producto'], $id_sede]);
            
            // Registrar devolución en Kardex (Opcional, recomendado)
            $pdo->prepare("INSERT INTO kardex (id_producto, id_sede, tipo_movimiento, cantidad, stock_resultante, nota, fecha) VALUES (?, ?, 'entrada_devolucion', ?, 0, ?, NOW())")
                ->execute([$i['id_producto'], $id_sede, $i['cantidad'], "Rechazo Pedido #$id_venta"]);
        }
    }
    // Recargar para evitar reenvío de formulario
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Gestión Delivery</title>
    <link rel="stylesheet" href="../../assets/css/estilos.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    
    <style>
        body { 
            background: #000; 
            padding: 0; margin: 0; 
            font-family: 'Poppins', sans-serif; 
            -webkit-tap-highlight-color: transparent;
            display: block !important; /* Fuerza diseño vertical */
        }

        /* HEADER FIJO */
        .header-top { 
            position: sticky; top: 0; z-index: 100;
            background: rgba(10,10,10,0.95); 
            border-bottom: 1px solid #333; 
            padding: 15px; 
            display:flex; justify-content:space-between; align-items:center; 
            backdrop-filter: blur(10px);
        }
        
        .page-title { color: #fff; margin: 0; font-size: 1.1rem; text-transform: uppercase; font-weight: 800; letter-spacing: 1px; }
        .page-subtitle { color: #666; font-size: 0.75rem; margin-top: 2px; }

        /* CONTENEDOR DE LISTA */
        .lista-pedidos {
            display: flex; 
            flex-direction: column; /* VERTICAL */
            gap: 15px; 
            padding: 15px;
            padding-bottom: 80px;
        }
        
        /* TARJETAS */
        .card-pedido { 
            background: #111; 
            border: 1px solid #333; 
            padding: 20px; 
            border-radius: 12px; 
            position:relative; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }
        
        /* Estados visuales (Bordes de color) */
        .pendiente { border-left: 5px solid #ef5350; background: linear-gradient(90deg, #1a0505 0%, #111 100%); }
        .confirmado { border-left: 5px solid #FFD700; }
        .en_camino { border-left: 5px solid #66bb6a; background: linear-gradient(90deg, #051a05 0%, #111 100%); }

        /* Animación para pendientes */
        .pendiente .lbl-estado { animation: parpadeo 1.5s infinite; color: #ef5350; }
        @keyframes parpadeo { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }

        /* BOTONES */
        .btn-accion { 
            flex: 1; padding: 15px; border: none; font-weight: 800; 
            cursor: pointer; border-radius: 8px; color: #fff; font-size: 1rem; 
            display: flex; justify-content: center; align-items: center; gap: 8px;
            text-transform: uppercase;
        }
        .btn-accept { background: #66bb6a; color: #000; }
        .btn-cancel { background: #2a0a0a; color: #ef5350; border: 1px solid #ef5350; }
        
        .btn-refresh { 
            background: #222; color: #FFD700; border: 1px solid #FFD700; 
            padding: 8px 15px; border-radius: 50px; font-weight: bold; 
            cursor: pointer; text-decoration: none; font-size: 0.8rem; 
            display: flex; align-items: center; gap: 5px;
        }

        .gps-btn {
            display: block; width: 100%; text-align: center;
            background: #222; border: 1px solid #444; color: #4fc3f7;
            padding: 12px; border-radius: 8px; margin-top: 10px;
            text-decoration: none; font-weight: 600;
        }
        
        .info-row { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 8px; color: #ccc; font-size: 0.95rem; }
        .info-icon { width: 20px; text-align: center; color: #666; margin-top: 3px; }

        /* Lista de productos dentro de la tarjeta */
        .product-list {
            background: #000; padding: 12px; border-radius: 8px; 
            margin: 15px 0; border: 1px solid #222; font-size: 0.9rem; color: #aaa;
        }
        .product-item { border-bottom: 1px dashed #333; padding-bottom: 5px; margin-bottom: 5px; display: flex; justify-content: space-between; }
        .product-item:last-child { border: none; margin: 0; padding: 0; }
        .product-qty { color: #fff; font-weight: bold; margin-right: 10px; }

    </style>
</head>
<body>

    <div class="header-top">
        <div>
            <h2 class="page-title"><i class="fa-solid fa-motorcycle"></i> Pedidos Web</h2>
            <div class="page-subtitle">Actualización auto: 5s</div>
        </div>
        
        <div style="display:flex; gap:10px;">
            <a href="venta.php" class="btn-refresh" style="border-color:#444; color:#fff;">POS</a>
            <a href="delivery.php" class="btn-refresh"><i class="fa-solid fa-rotate"></i></a>
        </div>
    </div>

    <div class="lista-pedidos">
        
        <?php if(count($pedidos) == 0): ?>
            <div style="text-align:center; padding:50px; color:#444;">
                <i class="fa-solid fa-check-circle" style="font-size:3rem; margin-bottom:15px;"></i>
                <p>Todo limpio. No hay pedidos pendientes.</p>
            </div>
        <?php endif; ?>

        <?php foreach($pedidos as $p): ?>
            <div class="card-pedido <?= $p['estado_delivery'] ?>">
                
                <div style="display:flex; justify-content:space-between; margin-bottom:15px; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:10px;">
                    <div>
                        <span style="background:#333; color:#fff; padding:3px 8px; border-radius:4px; font-weight:bold; font-size:0.85rem;">#<?= $p['id'] ?></span>
                        <span class="lbl-estado" style="margin-left:8px; font-weight:bold; text-transform:uppercase; font-size:0.8rem;">
                            <?= str_replace('_', ' ', $p['estado_delivery']) ?>
                        </span>
                    </div>
                    <div style="color:#FFD700; font-weight:800; font-size:1.2rem;">
                        S/ <?= number_format($p['total'], 2) ?>
                    </div>
                </div>
                
                <div class="info-row">
                    <div class="info-icon"><i class="fa-solid fa-user"></i></div>
                    <div><?= $p['nombre_contacto'] ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-icon"><i class="fa-brands fa-whatsapp"></i></div>
                    <div>
                        <a href="https://wa.me/51<?= preg_replace('/[^0-9]/','',$p['telefono_contacto']) ?>" target="_blank" style="color:#66bb6a; text-decoration:none; font-weight:bold;">
                            <?= $p['telefono_contacto'] ?>
                        </a>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-icon"><i class="fa-solid fa-map-pin"></i></div>
                    <div><?= $p['direccion_entrega'] ?></div>
                </div>

                <div class="product-list">
                    <?php 
                        $detalles = $pdo->query("SELECT d.cantidad, p.nombre FROM ventas_detalle d JOIN productos p ON d.id_producto = p.id WHERE d.id_venta = {$p['id']}")->fetchAll();
                        foreach($detalles as $d) { 
                            echo "<div class='product-item'>
                                    <span><span class='product-qty'>{$d['cantidad']}</span> {$d['nombre']}</span>
                                  </div>"; 
                        }
                    ?>
                    <div style="margin-top:10px; padding-top:5px; border-top:1px solid #333; color:#FFD700; font-weight:bold; text-align:right;">
                        Pago: <?= ucfirst($p['metodo_pago']) ?>
                    </div>
                </div>

                <?php if($p['estado_delivery'] == 'pendiente'): ?>
                    
                    <div style="display:flex; gap:10px;">
                        <form method="POST" style="flex:1;">
                            <input type="hidden" name="id_venta" value="<?= $p['id'] ?>">
                            <button name="accion" value="confirmar" class="btn-accion btn-accept">
                                <i class="fa-solid fa-check"></i> ACEPTAR
                            </button>
                        </form>
                        <form method="POST" style="flex:1;" onsubmit="return confirm('¿Seguro que deseas rechazar este pedido?');">
                            <input type="hidden" name="id_venta" value="<?= $p['id'] ?>">
                            <button name="accion" value="cancelar" class="btn-accion btn-cancel">
                                RECHAZAR
                            </button>
                        </form>
                    </div>

                    <?php if($p['latitud']): ?>
                        <a href="../../public/track.php?id=<?= $p['id'] ?>" target="_blank" class="gps-btn">
                            <i class="fa-solid fa-map-location-dot"></i> VER MAPA GPS
                        </a>
                    <?php endif; ?>

                <?php elseif($p['estado_delivery'] == 'confirmado'): ?>
                    <div style="text-align:center; color:#FFD700; padding:15px; background:rgba(255, 215, 0, 0.1); border-radius:8px; border:1px dashed #FFD700;">
                        <i class="fa-solid fa-spinner fa-spin"></i> Esperando que un Driver tome el pedido...
                    </div>

                <?php elseif($p['estado_delivery'] == 'en_camino'): ?>
                    <div style="text-align:center; color:#66bb6a; padding:15px; background:rgba(102, 187, 106, 0.1); border-radius:8px; border:1px solid #66bb6a;">
                        <div style="font-weight:bold; margin-bottom:5px;">EN RUTA</div>
                        <i class="fa-solid fa-motorcycle"></i> Driver: <b><?= $p['driver_nombre'] ?: 'Asignado' ?></b>
                        
                        <?php if($p['latitud']): ?>
                            <a href="../../public/track.php?id=<?= $p['id'] ?>" target="_blank" style="display:block; margin-top:10px; color:#66bb6a; text-decoration:underline;">
                                Ver ubicación en vivo
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>
        <?php endforeach; ?>
    </div>

    <script>
        // Actualizar automáticamente cada 5 segundos
        setTimeout(() => { location.reload(); }, 5000);
    </script>
</body>
</html>