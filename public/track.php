<?php
require_once '../config/db.php';

// 1. VALIDAR ID
$id_pedido = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_pedido) die("ID inválido");

// 2. CONSULTAR DATOS DEL PEDIDO
$stmt = $pdo->prepare("SELECT v.*, u.nombre as driver_nombre, u.telefono as driver_telefono 
                       FROM ventas v 
                       LEFT JOIN usuarios u ON v.id_driver = u.id 
                       WHERE v.id = ?");
$stmt->execute([$id_pedido]);
$pedido = $stmt->fetch();

if(!$pedido) die("<h2 style='color:white;text-align:center;font-family:sans-serif;margin-top:50px;'>Pedido no encontrado.</h2>");

// 3. CONSULTAR DETALLES DEL PRODUCTO
$stmtDet = $pdo->prepare("SELECT d.*, p.nombre FROM ventas_detalle d 
                          JOIN productos p ON d.id_producto = p.id 
                          WHERE d.id_venta = ?");
$stmtDet->execute([$id_pedido]);
$detalles = $stmtDet->fetchAll();

// 4. CALCULAR ESTADO PARA LA BARRA DE PROGRESO (0 a 3)
$estado = $pedido['estado_delivery'];
$step = 0;
if ($estado == 'pendiente') $step = 1;
if ($estado == 'confirmado') $step = 2;
if ($estado == 'en_camino') $step = 3;
if ($estado == 'entregado') $step = 4;
// Si es cancelado, manejamos una vista especial
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Seguimiento #<?= $id_pedido ?></title>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body { margin: 0; padding: 0; background: #000; font-family: 'Poppins', sans-serif; overflow: hidden; }
        
        /* MAPA DE FONDO */
        #map { height: 100vh; width: 100%; z-index: 1; opacity: 0.6; }

        /* HEADER FLOTANTE */
        .track-header {
            position: absolute; top: 0; left: 0; width: 100%; z-index: 10;
            padding: 20px; background: linear-gradient(180deg, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0) 100%);
            display: flex; align-items: center; gap: 15px;
        }
        .btn-back {
            background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);
            color: #fff; width: 40px; height: 40px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; backdrop-filter: blur(5px);
        }

        /* PANEL INFERIOR (DESLIZABLE) */
        .bottom-sheet {
            position: absolute; bottom: 0; left: 0; width: 100%;
            background: #111; border-radius: 25px 25px 0 0;
            padding: 25px; box-sizing: border-box; z-index: 20;
            box-shadow: 0 -10px 40px rgba(0,0,0,0.8);
            border-top: 1px solid #333;
            max-height: 80vh; overflow-y: auto;
            transition: 0.3s;
        }

        /* BARRA DE PROGRESO (STEPPER) */
        .stepper { display: flex; justify-content: space-between; margin-bottom: 25px; position: relative; }
        .stepper::before {
            content: ''; position: absolute; top: 15px; left: 0; width: 100%; height: 2px;
            background: #333; z-index: 0;
        }
        .step { position: relative; z-index: 1; text-align: center; width: 25%; }
        .step-icon {
            width: 32px; height: 32px; background: #222; border: 2px solid #444;
            border-radius: 50%; margin: 0 auto 5px auto; display: flex;
            align-items: center; justify-content: center; color: #666; font-size: 0.8rem;
            transition: 0.3s;
        }
        .step-label { font-size: 0.65rem; color: #666; text-transform: uppercase; font-weight: bold; }
        
        /* ESTADO ACTIVO */
        .step.active .step-icon { background: #FFD700; border-color: #FFD700; color: #000; box-shadow: 0 0 15px rgba(255, 215, 0, 0.4); }
        .step.active .step-label { color: #FFD700; }
        .step.completed .step-icon { background: #66bb6a; border-color: #66bb6a; color: #000; }

        /* TARJETA CONDUCTOR */
        .driver-card {
            background: #1a1a1a; padding: 15px; border-radius: 12px;
            display: flex; align-items: center; gap: 15px; margin-bottom: 20px;
            border: 1px solid #333;
        }
        .driver-img {
            width: 50px; height: 50px; background: #333; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; color: #888;
        }
        .btn-call {
            background: #25D366; color: #000; width: 40px; height: 40px;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            text-decoration: none; font-size: 1.2rem;
        }

        /* RESUMEN PEDIDO */
        .order-summary { border-top: 1px dashed #333; padding-top: 15px; }
        .order-item { display: flex; justify-content: space-between; margin-bottom: 8px; color: #ccc; font-size: 0.9rem; }
        .order-total { display: flex; justify-content: space-between; margin-top: 15px; font-size: 1.2rem; font-weight: bold; color: #fff; }

        /* STATUS CANCELADO */
        .cancelled-box { text-align: center; color: #ef5350; padding: 20px; border: 1px solid #ef5350; border-radius: 10px; background: rgba(239, 83, 80, 0.1); margin-bottom: 20px; }

        /* ICONOS MAPA */
        .marker-pin {
            width: 40px; height: 40px; border-radius: 50% 50% 50% 0;
            background: #FFD700; position: absolute; transform: rotate(-45deg);
            left: 50%; top: 50%; margin: -20px 0 0 -20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.4);
            display: flex; align-items: center; justify-content: center;
        }
        .marker-pin::after { content: ''; width: 24px; height: 24px; margin: 8px 0 0 8px; background: #fff; position: absolute; border-radius: 50%; }
        .marker-icon { transform: rotate(45deg); color: #000; font-size: 1.2rem; z-index: 10; }
        .moto-pin { background: #66bb6a; }
        .casa-pin { background: #4fc3f7; }
    </style>
</head>
<body>

    <div class="track-header">
        <a href="index.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i></a>
        <div style="color:#fff;">
            <div style="font-size:0.8rem; color:#aaa;">ORDEN #<?= str_pad($id_pedido, 6, '0', STR_PAD_LEFT) ?></div>
            <div style="font-weight:bold;"><?= date('d/m/Y h:i A', strtotime($pedido['fecha'])) ?></div>
        </div>
    </div>

    <div id="map"></div>

    <div class="bottom-sheet">
        
        <?php if($estado == 'cancelado'): ?>
            <div class="cancelled-box">
                <i class="fa-solid fa-circle-xmark" style="font-size:2rem; margin-bottom:10px;"></i>
                <h3>PEDIDO CANCELADO</h3>
                <p>Lo sentimos, tu pedido fue cancelado.</p>
            </div>
        <?php else: ?>
            <div class="stepper">
                <div class="step <?= $step >= 1 ? ($step > 1 ? 'completed' : 'active') : '' ?>">
                    <div class="step-icon"><i class="fa-solid fa-clipboard-check"></i></div>
                    <div class="step-label">Recibido</div>
                </div>
                <div class="step <?= $step >= 2 ? ($step > 2 ? 'completed' : 'active') : '' ?>">
                    <div class="step-icon"><i class="fa-solid fa-box-open"></i></div>
                    <div class="step-label">Preparando</div>
                </div>
                <div class="step <?= $step >= 3 ? ($step > 3 ? 'completed' : 'active') : '' ?>">
                    <div class="step-icon"><i class="fa-solid fa-motorcycle"></i></div>
                    <div class="step-label">En Camino</div>
                </div>
                <div class="step <?= $step >= 4 ? 'completed active' : '' ?>">
                    <div class="step-icon"><i class="fa-solid fa-flag-checkered"></i></div>
                    <div class="step-label">Entregado</div>
                </div>
            </div>

            <?php if($pedido['id_driver']): ?>
                <div class="driver-card">
                    <div class="driver-img"><i class="fa-solid fa-user"></i></div>
                    <div style="flex:1;">
                        <div style="color:#666; font-size:0.75rem;">TU REPARTIDOR</div>
                        <div style="color:#fff; font-weight:bold; font-size:1.1rem;"><?= $pedido['driver_nombre'] ?></div>
                    </div>
                    <?php if($pedido['driver_telefono']): ?>
                        <a href="https://wa.me/51<?= preg_replace('/[^0-9]/','',$pedido['driver_telefono']) ?>" target="_blank" class="btn-call">
                            <i class="fa-brands fa-whatsapp"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php elseif($estado == 'confirmado'): ?>
                <div style="text-align:center; color:#888; margin-bottom:20px; font-size:0.9rem;">
                    <i class="fa-solid fa-spinner fa-spin"></i> Asignando repartidor...
                </div>
            <?php endif; ?>

            <div id="box-tiempo" style="text-align:center; margin-bottom:20px; display:none;">
                <span style="font-size:2rem; font-weight:800; color:#fff;" id="minutos-txt">--</span>
                <span style="color:#888;">min aprox</span>
            </div>

            <div class="order-summary">
                <h4 style="color:#FFD700; margin:0 0 15px 0;">Resumen del Pedido</h4>
                <?php foreach($detalles as $d): ?>
                    <div class="order-item">
                        <span><?= $d['cantidad'] ?> x <?= $d['nombre'] ?></span>
                        <span>S/ <?= number_format($d['subtotal'], 2) ?></span>
                    </div>
                <?php endforeach; ?>
                <div class="order-total">
                    <span>Total a Pagar</span>
                    <span style="color:#FFD700;">S/ <?= number_format($pedido['total'], 2) ?></span>
                </div>
                <div style="text-align:right; font-size:0.8rem; color:#666; margin-top:5px;">
                    Pago: <?= ucfirst($pedido['metodo_pago']) ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // CONFIGURACIÓN DEL MAPA
        const latCli = <?= $pedido['latitud'] ?: -12.046 ?>;
        const lonCli = <?= $pedido['longitud'] ?: -77.042 ?>;

        const map = L.map('map', { zoomControl: false, attributionControl: false }).setView([latCli, lonCli], 15);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            maxZoom: 19
        }).addTo(map);

        // Iconos
        const createIcon = (tipo) => {
            let colorClass = (tipo === 'moto') ? 'moto-pin' : 'casa-pin';
            let iconClass = (tipo === 'moto') ? 'fa-motorcycle' : 'fa-house';
            return L.divIcon({
                className: 'custom-div-icon',
                html: `<div class='marker-pin ${colorClass}'><i class='fa-solid ${iconClass} marker-icon'></i></div>`,
                iconSize: [40, 42],
                iconAnchor: [20, 42]
            });
        };

        const markerCliente = L.marker([latCli, lonCli], { icon: createIcon('casa') }).addTo(map);
        let markerMoto = null;
        let rutaLinea = null;

        // ACTUALIZAR EN VIVO
        function actualizarUbicacion() {
            fetch(`../api/gps.php?action=leer&id_venta=<?= $id_pedido ?>`)
            .then(r => r.json())
            .then(data => {
                // Si está en camino y hay coordenadas
                if (data.estado_delivery === 'en_camino' && data.driver_lat) {
                    document.getElementById('box-tiempo').style.display = 'block';
                    
                    const dLat = parseFloat(data.driver_lat);
                    const dLon = parseFloat(data.driver_lon);

                    if (markerMoto) markerMoto.setLatLng([dLat, dLon]);
                    else markerMoto = L.marker([dLat, dLon], { icon: createIcon('moto') }).addTo(map);

                    if (rutaLinea) rutaLinea.setLatLngs([[latCli, lonCli], [dLat, dLon]]);
                    else rutaLinea = L.polyline([[latCli, lonCli], [dLat, dLon]], { color: '#FFD700', weight: 4, dashArray: '10, 10' }).addTo(map);

                    // Ajustar zoom para ver ambos puntos
                    // map.fitBounds(L.latLngBounds([[latCli, lonCli], [dLat, dLon]]), { padding: [50, 50] });

                    // Calcular tiempo simple (distancia recta)
                    const dist = map.distance([latCli, lonCli], [dLat, dLon]);
                    const minutos = Math.ceil(dist / 300); // 300m por minuto aprox
                    document.getElementById('minutos-txt').innerText = minutos;
                } else {
                    document.getElementById('box-tiempo').style.display = 'none';
                }
                
                // Recargar si cambia de estado para actualizar la barra de progreso
                if (data.estado_delivery !== '<?= $estado ?>') {
                    location.reload();
                }
            })
            .catch(e => {});
        }

        setInterval(actualizarUbicacion, 5000);
        actualizarUbicacion();
    </script>
</body>
</html>