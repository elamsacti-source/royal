<?php
require_once '../config/db.php';

// Validar ID
$id_pedido = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_pedido) die("ID inválido");

$stmt = $pdo->prepare("SELECT * FROM ventas WHERE id = ?");
$stmt->execute([$id_pedido]);
$pedido = $stmt->fetch();

if(!$pedido) die("<h2 style='color:white;text-align:center;font-family:sans-serif;margin-top:50px;'>Pedido no encontrado.</h2>");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Tracking #<?= $id_pedido ?></title>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body { margin: 0; padding: 0; background: #000; font-family: 'Poppins', sans-serif; overflow: hidden; }
        #map { height: 100vh; width: 100%; z-index: 1; }

        /* TARJETA INFERIOR */
        .info-card {
            position: absolute; bottom: 0; left: 0; width: 100%;
            background: #1a1a1a; border-radius: 20px 20px 0 0;
            padding: 20px 20px 30px 20px; box-sizing: border-box;
            z-index: 999; box-shadow: 0 -5px 20px rgba(0,0,0,0.5);
            transition: transform 0.3s ease;
        }

        .status-badge {
            display: inline-block; padding: 5px 12px; border-radius: 50px;
            font-size: 0.8rem; font-weight: bold; text-transform: uppercase;
            margin-bottom: 10px; letter-spacing: 1px;
        }
        .pendiente { background: #ef5350; color: #fff; }
        .confirmado { background: #FFD700; color: #000; }
        .en_camino { background: #66bb6a; color: #000; box-shadow: 0 0 10px #66bb6a; }

        .time-estimate { font-size: 1.8rem; font-weight: bold; color: #fff; display: flex; align-items: center; gap: 10px; }
        .time-estimate small { font-size: 0.9rem; color: #888; font-weight: normal; }

        .driver-info { display: flex; align-items: center; gap: 15px; margin-top: 15px; padding-top: 15px; border-top: 1px solid #333; }
        .driver-avatar { width: 45px; height: 45px; background: #333; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; }

        .btn-back {
            position: absolute; top: 20px; left: 20px; z-index: 999;
            background: rgba(0,0,0,0.6); color: #fff; border: none;
            width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
            cursor: pointer; text-decoration: none; backdrop-filter: blur(5px);
        }

        /* MARCADORES PERSONALIZADOS */
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

    <a href="index.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i></a>
    <div id="map"></div>

    <div class="info-card">
        <div style="display:flex; justify-content:space-between; align-items:start;">
            <div>
                <span id="lbl-estado" class="status-badge <?= $pedido['estado_delivery'] ?>">
                    <?= str_replace('_', ' ', strtoupper($pedido['estado_delivery'])) ?>
                </span>
                <div style="color:#aaa; font-size:0.9rem;"><?= $pedido['direccion_entrega'] ?></div>
            </div>
            <div style="text-align:right;">
                <div style="color:var(--royal-gold); font-weight:bold;">#<?= $id_pedido ?></div>
            </div>
        </div>

        <div id="box-tiempo" style="margin-top:15px; display:none;">
            <div class="time-estimate">
                <span id="minutos-txt">--</span> <small>min aprox</small>
            </div>
            <div style="color:#666; font-size:0.85rem;">Llegada estimada</div>
        </div>
        
        <div id="msg-espera" style="display:none; color:#FFD700; margin-top:15px;">
            <i class="fa-solid fa-spinner fa-spin"></i> Buscando repartidor cercano...
        </div>

        <div class="driver-info" id="driver-box" style="display:none;">
            <div class="driver-avatar"><i class="fa-solid fa-motorcycle"></i></div>
            <div style="flex:1;">
                <div style="color:#fff; font-weight:600;">Tu Repartidor</div>
                <div style="color:#66bb6a; font-size:0.8rem;">En camino</div>
            </div>
            <a href="#" class="btn-back" style="position:static; background:#25D366; width:45px; height:45px;">
                <i class="fa-brands fa-whatsapp"></i>
            </a>
        </div>
    </div>

    <script>
        // 1. CONFIGURACIÓN DEL MAPA
        const latCli = <?= $pedido['latitud'] ?: -12.046 ?>;
        const lonCli = <?= $pedido['longitud'] ?: -77.042 ?>;

        const map = L.map('map', { zoomControl: false }).setView([latCli, lonCli], 15);

        // Capa Oscura
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; OpenStreetMap &copy; CartoDB',
            maxZoom: 19
        }).addTo(map);

        // 2. ICONOS
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
        let rutaLinea = null; // Variable para guardar la línea

        // 3. ACTUALIZACIÓN EN VIVO
        function actualizarUbicacion() {
            fetch(`../api/gps.php?action=leer&id_venta=<?= $id_pedido ?>`)
            .then(r => r.json())
            .then(data => {
                const lbl = document.getElementById('lbl-estado');
                lbl.className = `status-badge ${data.estado_delivery}`;
                lbl.innerText = data.estado_delivery.replace('_', ' ').toUpperCase();

                if(data.estado_delivery === 'pendiente' || data.estado_delivery === 'confirmado') {
                    document.getElementById('msg-espera').style.display = 'block';
                    document.getElementById('box-tiempo').style.display = 'none';
                    document.getElementById('driver-box').style.display = 'none';
                    if(markerMoto) map.removeLayer(markerMoto);
                    if(rutaLinea) map.removeLayer(rutaLinea);
                } 
                else if (data.estado_delivery === 'en_camino' && data.driver_lat) {
                    document.getElementById('msg-espera').style.display = 'none';
                    document.getElementById('box-tiempo').style.display = 'block';
                    document.getElementById('driver-box').style.display = 'flex';

                    const dLat = parseFloat(data.driver_lat);
                    const dLon = parseFloat(data.driver_lon);

                    // Mover Moto
                    if (markerMoto) markerMoto.setLatLng([dLat, dLon]);
                    else markerMoto = L.marker([dLat, dLon], { icon: createIcon('moto') }).addTo(map);

                    // --- DIBUJAR LÍNEA DE RUTA (NUEVO) ---
                    if (rutaLinea) {
                        rutaLinea.setLatLngs([[latCli, lonCli], [dLat, dLon]]);
                    } else {
                        rutaLinea = L.polyline([[latCli, lonCli], [dLat, dLon]], {
                            color: '#FFD700', // Color Dorado Royal
                            weight: 4,
                            opacity: 0.7,
                            dashArray: '10, 10', // Línea punteada
                            lineCap: 'round'
                        }).addTo(map);
                    }

                    // Auto Zoom
                    const bounds = L.latLngBounds([[latCli, lonCli], [dLat, dLon]]);
                    map.fitBounds(bounds, { padding: [80, 80], maxZoom: 16 });

                    // Tiempo Estimado
                    const dist = map.distance([latCli, lonCli], [dLat, dLon]);
                    const minutos = Math.ceil(dist / 400); 
                    document.getElementById('minutos-txt').innerText = minutos;
                }
                else if (data.estado_delivery === 'entregado') {
                    document.getElementById('minutos-txt').innerText = "0";
                    document.getElementById('box-tiempo').innerHTML = "<h3 style='color:#66bb6a; margin:0;'>¡Entregado! 🎉</h3>";
                    if(markerMoto) map.removeLayer(markerMoto);
                    if(rutaLinea) map.removeLayer(rutaLinea);
                }
            })
            .catch(e => {});
        }

        setInterval(actualizarUbicacion, 4000);
        actualizarUbicacion();
    </script>
</body>
</html>