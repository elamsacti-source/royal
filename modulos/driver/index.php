<?php
session_start();
require_once '../../config/db.php';

// 1. SEGURIDAD
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'driver') {
    header("Location: ../../index.php");
    exit;
}

$id_driver = $_SESSION['user_id'];
$mensaje = "";

// 2. LÓGICA DE ACCIONES
if (isset($_POST['accion'])) {
    $id_venta = $_POST['id_venta'];
    
    // ACEPTAR PEDIDO
    if ($_POST['accion'] == 'aceptar') {
        // Verificamos que siga libre
        $check = $pdo->prepare("SELECT id FROM ventas WHERE id = ? AND id_driver IS NULL");
        $check->execute([$id_venta]);
        if ($check->fetch()) {
            $stmt = $pdo->prepare("UPDATE ventas SET id_driver = ?, estado_delivery = 'en_camino' WHERE id = ?");
            $stmt->execute([$id_driver, $id_venta]);
            // Recargar para evitar reenvío de formulario
            header("Location: index.php"); exit;
        } else {
            $mensaje = "<div class='alerta error'>⚠️ El pedido ya fue tomado por otro driver.</div>";
        }
    }

    // ENTREGAR PEDIDO
    if ($_POST['accion'] == 'entregar') {
        $stmt = $pdo->prepare("UPDATE ventas SET estado_delivery = 'entregado' WHERE id = ? AND id_driver = ?");
        if ($stmt->execute([$id_venta, $id_driver])) {
            header("Location: index.php"); exit;
        }
    }

    // LIBERAR PEDIDO (CANCELAR COMO DRIVER)
    if ($_POST['accion'] == 'liberar') {
        // Regresa el pedido a estado 'confirmado' y quita al driver (id_driver = NULL)
        $stmt = $pdo->prepare("UPDATE ventas SET id_driver = NULL, estado_delivery = 'confirmado' WHERE id = ? AND id_driver = ?");
        if ($stmt->execute([$id_venta, $id_driver])) {
            header("Location: index.php"); exit;
        }
    }
}

// 3. CONSULTAS
// CORRECCIÓN PRINCIPAL: Busca pedidos asignados a mí, incluso si el estado se regresó a 'confirmado' por error.
$stmt = $pdo->prepare("SELECT * FROM ventas WHERE id_driver = ? AND estado_delivery IN ('en_camino', 'confirmado')");
$stmt->execute([$id_driver]);
$mis_pedidos = $stmt->fetchAll();

// Pedidos disponibles (Sin driver)
$disponibles = $pdo->query("SELECT * FROM ventas WHERE tipo_venta = 'delivery' AND estado_delivery = 'confirmado' AND id_driver IS NULL ORDER BY id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Royal Driver</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    
    <style>
        /* RESET BÁSICO */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body { 
            background-color: #000; 
            color: #fff; 
            font-family: 'Poppins', sans-serif;
            padding-bottom: 50px;
        }

        /* HEADER */
        .app-bar {
            position: sticky; top: 0; z-index: 1000;
            background: #111; border-bottom: 1px solid #333;
            padding: 15px 20px; display: flex; justify-content: space-between; align-items: center;
            height: 70px; box-shadow: 0 4px 10px rgba(0,0,0,0.5);
        }
        .logo-text { color: #FFD700; font-weight: 800; font-size: 1.2rem; text-transform: uppercase; letter-spacing: 1px; }
        .user-panel { display: flex; align-items: center; gap: 15px; }
        .user-info { text-align: right; font-size: 0.8rem; color: #aaa; }
        .user-info b { display: block; color: #fff; font-size: 0.9rem; }
        .btn-off { color: #ef5350; border: 1px solid #ef5350; width: 35px; height: 35px; border-radius: 8px; display: flex; align-items: center; justify-content: center; text-decoration: none; }

        /* CONTENEDOR PRINCIPAL */
        .main-list { display: flex; flex-direction: column; padding: 20px; width: 100%; max-width: 600px; margin: 0 auto; }
        .section-label { color: #666; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; margin: 20px 0 10px 0; display: flex; align-items: center; gap: 8px; }
        .dot { width: 8px; height: 8px; background: #66bb6a; border-radius: 50%; animation: blink 1s infinite; }
        @keyframes blink { 50% { opacity: 0.5; } }

        /* TARJETAS */
        .order-card { background: #151515; border: 1px solid #333; border-radius: 12px; padding: 20px; margin-bottom: 20px; position: relative; }
        .order-card.active { border: 2px solid #FFD700; background: #1a1a08; }

        .card-row-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px dashed #333; padding-bottom: 10px; }
        .badge-id { background: #333; color: #fff; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 0.9rem; }
        .big-price { font-size: 1.5rem; font-weight: 800; color: #fff; }

        .card-row { display: flex; gap: 15px; margin-bottom: 12px; align-items: flex-start; }
        .icon { width: 20px; text-align: center; color: #FFD700; font-size: 1.1rem; margin-top: 2px; }
        .text { flex: 1; color: #ccc; font-size: 0.95rem; }
        .text strong { color: #fff; }

        /* BOTONES */
        .btn-action { width: 100%; padding: 15px; border: none; border-radius: 8px; font-weight: 800; font-size: 1rem; text-transform: uppercase; cursor: pointer; margin-top: 10px; display: flex; justify-content: center; align-items: center; gap: 10px; }
        .btn-gold { background: #FFD700; color: #000; }
        .btn-green { background: #66bb6a; color: #000; }
        .btn-cancel { background: #2a0a0a; color: #ef5350; border: 1px solid #ef5350; font-size: 0.9rem; padding: 12px; margin-top: 15px; }
        
        .btn-link { display: block; width: 100%; text-align: center; padding: 12px; background: #000; color: #4fc3f7; text-decoration: none; font-weight: bold; border: 1px solid #4fc3f7; border-radius: 8px; margin-top: 10px; }

        .empty-state { text-align: center; padding: 40px; color: #555; background: #111; border-radius: 12px; }
        .alerta { background: #333; padding: 10px; text-align: center; border-radius: 8px; margin-bottom: 20px; }
        .error { color: #ef5350; border: 1px solid #ef5350; }
    </style>
</head>
<body>

    <div class="app-bar">
        <div class="logo-text"><i class="fa-solid fa-motorcycle"></i> Royal Go</div>
        <div class="user-panel">
            <div class="user-info">Hola, <b><?= explode(' ', $_SESSION['nombre'])[0] ?></b></div>
            <a href="../../logout.php" class="btn-off"><i class="fa-solid fa-power-off"></i></a>
        </div>
    </div>

    <div class="main-list">
        
        <?= $mensaje ?>

        <?php if(count($mis_pedidos) > 0): ?>
            <div class="section-label"><div class="dot"></div> PEDIDO EN CURSO</div>
            
            <?php foreach($mis_pedidos as $p): ?>
                <div class="order-card active">
                    <div class="card-row-top">
                        <span class="badge-id" style="background:#FFD700; color:#000;">#<?= $p['id'] ?></span>
                        <span class="big-price">S/ <?= number_format($p['total'], 2) ?></span>
                    </div>

                    <div class="card-row">
                        <div class="icon"><i class="fa-solid fa-user"></i></div>
                        <div class="text">
                            <strong><?= $p['nombre_contacto'] ?></strong><br>
                            <small>Cliente</small>
                        </div>
                    </div>

                    <div class="card-row">
                        <div class="icon"><i class="fa-brands fa-whatsapp"></i></div>
                        <div class="text">
                            <a href="https://wa.me/51<?= preg_replace('/[^0-9]/', '', $p['telefono_contacto']) ?>" target="_blank" style="color:#25D366; text-decoration:none; font-weight:bold;">
                                Enviar Mensaje
                            </a>
                        </div>
                    </div>

                    <div class="card-row">
                        <div class="icon"><i class="fa-solid fa-location-dot"></i></div>
                        <div class="text">
                            <?= $p['direccion_entrega'] ?>
                            
                            <?php 
                                if (!empty($p['latitud']) && !empty($p['longitud'])) {
                                    $wazeUrl = "https://waze.com/ul?ll={$p['latitud']},{$p['longitud']}&navigate=yes";
                                    $textoWaze = "NAVEGAR CON GPS (AUTOMÁTICO)";
                                } else {
                                    $wazeUrl = "https://waze.com/ul?q=" . urlencode($p['direccion_entrega']) . "&navigate=yes";
                                    $textoWaze = "BUSCAR EN WAZE";
                                }
                            ?>
                            <a href="<?= $wazeUrl ?>" target="_blank" class="btn-link">
                                <i class="fa-brands fa-waze"></i> <?= $textoWaze ?>
                            </a>
                        </div>
                    </div>

                    <div style="background:#000; padding:10px; border-radius:6px; margin-top:10px; margin-bottom:15px; display:flex; justify-content:space-between;">
                        <span style="color:#888;">Pago:</span>
                        <strong style="color:#FFD700;"><?= $p['metodo_pago'] ?></strong>
                    </div>

                    <form method="POST" onsubmit="return confirm('¿Confirmas que ya entregaste el pedido y cobraste?');">
                        <input type="hidden" name="accion" value="entregar">
                        <input type="hidden" name="id_venta" value="<?= $p['id'] ?>">
                        <button class="btn-action btn-green">
                            <i class="fa-solid fa-check"></i> CONFIRMAR ENTREGA
                        </button>
                    </form>

                    <form method="POST" onsubmit="return confirm('¿Seguro que deseas soltar este pedido? Volverá a la lista de disponibles.');">
                        <input type="hidden" name="accion" value="liberar">
                        <input type="hidden" name="id_venta" value="<?= $p['id'] ?>">
                        <button class="btn-action btn-cancel">
                            <i class="fa-solid fa-triangle-exclamation"></i> NO PUEDO ENTREGAR (LIBERAR)
                        </button>
                    </form>

                    <script>
                        setInterval(() => {
                            if(navigator.geolocation) {
                                navigator.geolocation.getCurrentPosition(pos => {
                                    fetch('../../api/gps.php', { 
                                        method: 'POST', 
                                        body: JSON.stringify({ 
                                            id_venta: <?= $p['id'] ?>, 
                                            lat: pos.coords.latitude, 
                                            lon: pos.coords.longitude 
                                        }) 
                                    });
                                }, null, { enableHighAccuracy: true });
                            }
                        }, 10000);
                    </script>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>


        <div class="section-label">DISPONIBLES (<?= count($disponibles) ?>)</div>

        <?php if(count($disponibles) == 0): ?>
            <div class="empty-state">
                <i class="fa-solid fa-mug-hot" style="font-size:2rem; margin-bottom:10px;"></i>
                <p>No hay pedidos pendientes...</p>
            </div>
        <?php endif; ?>

        <?php foreach($disponibles as $d): ?>
            <div class="order-card">
                <div class="card-row-top">
                    <span class="badge-id">Nuevo #<?= $d['id'] ?></span>
                    <span class="big-price">S/ <?= number_format($d['total'], 2) ?></span>
                </div>

                <div class="card-row">
                    <div class="icon"><i class="fa-solid fa-location-dot"></i></div>
                    <div class="text"><?= $d['direccion_entrega'] ?></div>
                </div>

                <div class="card-row">
                    <div class="icon"><i class="fa-solid fa-money-bill"></i></div>
                    <div class="text">Cobrar: <strong><?= $d['metodo_pago'] ?></strong></div>
                </div>

                <form method="POST">
                    <input type="hidden" name="accion" value="aceptar">
                    <input type="hidden" name="id_venta" value="<?= $d['id'] ?>">
                    <button class="btn-action btn-gold">
                        TOMAR PEDIDO
                    </button>
                </form>
            </div>
        <?php endforeach; ?>

    </div>

    <script>
        setTimeout(() => { 
            if(!document.querySelector('.order-card.active')) location.reload(); 
        }, 15000);
    </script>
</body>
</html>