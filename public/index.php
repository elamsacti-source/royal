<?php
session_start();
require_once '../config/db.php';

// DATOS DEL USUARIO
$cliente_id = $_SESSION['user_id'] ?? null;
$nombre_cli = $_SESSION['nombre'] ?? '';
$pedidos_activos = [];
$historial = [];

if ($cliente_id) {
    // Buscar Pedido ACTIVO
    $stmt = $pdo->prepare("SELECT * FROM ventas WHERE id_cliente = ? AND estado_delivery IN ('pendiente','confirmado','en_camino') ORDER BY id DESC LIMIT 1");
    $stmt->execute([$cliente_id]);
    $pedidos_activos = $stmt->fetchAll();

    // Buscar HISTORIAL
    $stmtHist = $pdo->prepare("SELECT * FROM ventas WHERE id_cliente = ? AND estado_delivery IN ('entregado','cancelado') ORDER BY id DESC LIMIT 5");
    $stmtHist->execute([$cliente_id]);
    $historial = $stmtHist->fetchAll();
    
    // Telefono
    $stmtUser = $pdo->prepare("SELECT telefono FROM usuarios WHERE id = ?");
    $stmtUser->execute([$cliente_id]);
    $uData = $stmtUser->fetch();
    $telefono_cli = $uData['telefono'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Royal Delivery</title>
    <link rel="stylesheet" href="../assets/css/estilos.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    
    <style>
        /* --- CORRECCIÓN CRÍTICA PARA CELULAR --- */
        body { 
            display: block !important; /* Rompe las columnas laterales */
            background: #000; 
            padding-bottom: 90px; 
            -webkit-tap-highlight-color: transparent; 
            font-family: 'Poppins', sans-serif; 
            margin: 0;
            overflow-x: hidden;
        }
        
        /* HEADER FIJO */
        .app-bar {
            position: sticky; top: 0; z-index: 100;
            background: rgba(10,10,10,0.98); border-bottom: 1px solid #333;
            padding: 15px 20px; 
            display: flex; justify-content: space-between; align-items: center;
            width: 100%; box-sizing: border-box;
        }
        .logo-text { color: #FFD700; font-weight: 800; font-size: 1.3rem; text-transform: uppercase; letter-spacing: 1px; }
        .user-chip { background: #222; padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; color: #fff; border: 1px solid #333; display: flex; align-items: center; gap: 8px; }

        .container { width: 100%; max-width: 100%; padding: 15px; box-sizing: border-box; }

        /* BUSCADOR */
        .search-bar-container { position: sticky; top: 68px; z-index: 90; background: #000; padding: 10px 0; }
        .search-input { width: 100%; background: #1a1a1a; border: 1px solid #333; padding: 12px 15px; border-radius: 50px; color: #fff; outline: none; font-size: 1rem; box-sizing: border-box; }
        .search-input:focus { border-color: #FFD700; }

        /* LISTA VERTICAL */
        #lista-productos { display: flex; flex-direction: column; gap: 15px; margin-top: 10px; }

        .prod-card-list {
            display: flex; align-items: center;
            background: #111; border: 1px solid #222; border-radius: 12px;
            padding: 12px; width: 100%; position: relative; box-sizing: border-box;
        }
        .prod-card-list:active { background: #1a1a1a; border-color: #444; }

        .prod-img {
            width: 70px; height: 70px; background: #1a1a1a; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem; color: #444; margin-right: 15px; flex-shrink: 0;
        }
        
        .prod-info { flex: 1; display: flex; flex-direction: column; justify-content: center; overflow: hidden; }
        .prod-title { color: #fff; font-weight: 600; font-size: 1rem; line-height: 1.2; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .prod-cat { color: #666; font-size: 0.75rem; text-transform: uppercase; }
        .prod-price { color: #FFD700; font-weight: 700; font-size: 1.1rem; margin-top: 5px; }

        .btn-add {
            width: 35px; height: 35px; border-radius: 50%;
            background: #333; border: 1px solid #555; color: #fff;
            display: flex; align-items: center; justify-content: center;
            margin-left: 10px; cursor: pointer; transition: 0.2s; flex-shrink: 0;
        }
        .btn-add:active { background: #FFD700; color: #000; transform: scale(1.1); }

        /* PEDIDO ACTIVO */
        .active-order {
            background: linear-gradient(135deg, #1a1a08 0%, #000 100%);
            border: 1px solid #FFD700; border-radius: 12px; padding: 15px;
            margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;
        }
        .status-dot { width: 8px; height: 8px; background: #66bb6a; border-radius: 50%; display: inline-block; margin-right: 5px; animation: blink 1s infinite; }
        @keyframes blink { 50% { opacity: 0.5; } }

        /* BOTÓN FLOTANTE CARRITO */
        .btn-flotante { 
            position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); 
            width: 90%; max-width: 400px;
            background: #FFD700; color: #000; padding: 15px; 
            border-radius: 50px; font-weight: 800; text-align: center; 
            box-shadow: 0 5px 20px rgba(255, 215, 0, 0.3); 
            z-index: 9999; display: none; 
            font-size: 1rem; text-transform: uppercase; letter-spacing: 1px; cursor: pointer;
        }

        /* MODAL */
        .modal-bg { 
            display: none; position: fixed; top:0; left:0; width:100%; height:100%; 
            background:rgba(0,0,0,0.9); z-index: 10000; 
            justify-content:center; align-items:flex-end; 
        }
        .modal-caja { 
            background: #1a1a1a; width: 100%; max-width: 500px; 
            border-radius: 20px 20px 0 0; border-top: 1px solid #333; 
            max-height: 90vh; display: flex; flex-direction: column; 
            box-shadow: 0 -5px 30px rgba(0,0,0,0.8);
        }
        .form-control { width: 100%; padding: 15px; background: #222; border: 1px solid #444; color: #fff; border-radius: 8px; margin-bottom: 12px; font-size: 1rem; outline: none; box-sizing: border-box; }
    </style>
</head>
<body>

    <div class="app-bar">
        <div class="logo-text"><i class="fa-solid fa-wine-bottle"></i> Royal</div>
        
        <?php if($cliente_id): ?>
            <div class="user-chip" onclick="location.href='../logout.php'">
                <i class="fa-solid fa-user"></i> <?= explode(' ', $nombre_cli)[0] ?>
            </div>
        <?php else: ?>
            <a href="../index.php" style="color:#FFD700; text-decoration:none; font-weight:bold; font-size:0.85rem;">
                LOGIN
            </a>
        <?php endif; ?>
    </div>

    <div class="container">
        
        <?php if(count($pedidos_activos) > 0): $p = $pedidos_activos[0]; ?>
            <div class="active-order" onclick="window.location.href='track.php?id=<?= $p['id'] ?>'">
                <div>
                    <div style="color:#888; font-size:0.75rem; text-transform:uppercase; margin-bottom:3px;">Pedido en curso</div>
                    <div style="font-size:1.1rem; color:#fff; font-weight:700;">
                        <span class="status-dot"></span> <?= str_replace('_', ' ', ucfirst($p['estado_delivery'])) ?>
                    </div>
                </div>
                <div style="text-align:right;">
                    <div style="color:#FFD700; font-weight:bold;">#<?= $p['id'] ?></div>
                    <div style="color:#666; font-size:0.8rem;">Mapa <i class="fa-solid fa-chevron-right"></i></div>
                </div>
            </div>
        <?php endif; ?>

        <div class="search-bar-container">
            <input type="text" id="buscador" placeholder="🔍 Buscar licor o snack..." class="search-input" onkeyup="filtrarProductos()">
        </div>

        <div id="lista-productos">
            <p style="text-align:center; color:#666; padding:20px;">Cargando carta...</p>
        </div>

        <?php if(count($historial) > 0): ?>
            <div style="margin-top:30px; border-top:1px solid #333; padding-top:20px;">
                <h4 style="color:#888; text-transform:uppercase; font-size:0.8rem; margin-bottom:15px;">Tus últimos pedidos</h4>
                <?php foreach($historial as $h): ?>
                    <div style="background:#111; padding:12px; border-radius:8px; margin-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <div style="color:#fff; font-weight:bold;">#<?= $h['id'] ?></div>
                            <small style="color:#666;"><?= date('d/m', strtotime($h['fecha'])) ?> • S/ <?= $h['total'] ?></small>
                        </div>
                        <span style="font-size:0.75rem; color:<?= $h['estado_delivery']=='entregado'?'#66bb6a':'#ef5350' ?>; font-weight:bold; text-transform:uppercase;">
                            <?= $h['estado_delivery'] ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <br><br>
    </div>

    <div id="btn-carrito" class="btn-flotante" onclick="abrirCarrito()">
        VER CANASTA (<span id="cant-items">0</span>)
    </div>

    <div id="modal-carrito" class="modal-bg">
        <div class="modal-caja">
            <div style="padding:15px 20px; border-bottom:1px solid #333; display:flex; justify-content:space-between; align-items:center;">
                <h3 style="color:#fff; margin:0;">Tu Canasta</h3>
                <button onclick="cerrarCarrito()" style="background:none; border:none; color:#ef5350; font-size:2rem; line-height:1;">&times;</button>
            </div>
            
            <div style="padding:20px; overflow-y:auto; flex:1;">
                <div id="items-carrito"></div>
                
                <div style="display:flex; justify-content:space-between; font-size:1.2rem; color:#fff; font-weight:bold; margin:20px 0; padding-top:15px; border-top:1px dashed #444;">
                    <span>Total a Pagar</span>
                    <span id="txt-total" style="color:#FFD700;">S/ 0.00</span>
                </div>
                
                <form id="form-pedido" onsubmit="enviarPedido(event)">
                    <input type="text" id="cli-nombre" placeholder="Tu Nombre" required class="form-control" value="<?= $nombre_cli ?>">
                    <input type="tel" id="cli-telefono" placeholder="WhatsApp / Celular" required class="form-control" value="<?= $telefono_cli ?>">
                    
                    <div style="display:flex; gap:10px;">
                        <input type="text" id="cli-direccion" placeholder="Dirección de entrega" required class="form-control" style="margin-bottom:0;">
                        <button type="button" onclick="obtenerUbicacion()" style="background:#333; color:#FFD700; border:1px solid #FFD700; width:55px; border-radius:8px; cursor:pointer;">
                            <i class="fa-solid fa-location-dot"></i>
                        </button>
                    </div>
                    <small id="gps-status" style="color:#66bb6a; display:none; margin-top:5px; margin-bottom:10px;">📍 Ubicación Detectada</small>
                    <input type="hidden" id="cli-lat"><input type="hidden" id="cli-lon">
                    
                    <select id="cli-metodo" class="form-control" style="margin-top:15px;">
                        <option value="Yape">Yape / Plin</option>
                        <option value="Efectivo">Efectivo</option>
                    </select>

                    <button type="submit" class="btn-flotante" style="position:static; display:block; width:100%; margin-top:20px; box-shadow:none;">
                        CONFIRMAR PEDIDO
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        let productos = [], carrito = [];

        window.onload = () => {
            fetch('../api/productos.php').then(r => r.json()).then(d => { 
                productos = d; 
                render(productos); 
            });
        };

        function filtrarProductos() {
            let txt = document.getElementById('buscador').value.toLowerCase();
            render(productos.filter(p => p.nombre.toLowerCase().includes(txt)));
        }

        function render(lista) {
            let div = document.getElementById('lista-productos'); div.innerHTML = '';
            if(lista.length === 0) { div.innerHTML = '<p style="color:#666; text-align:center;">No encontrado :(</p>'; return; }
            
            lista.forEach(p => {
                let packBadge = p.es_combo ? '<span style="color:#000; background:#FFD700; font-size:0.6rem; padding:2px 4px; border-radius:3px; font-weight:bold; margin-left:5px;">PACK</span>' : '';
                let icon = p.es_combo ? 'fa-gift' : 'fa-wine-bottle';
                
                // TARJETA DE LISTA VERTICAL
                div.innerHTML += `
                    <div class="prod-card-list">
                        <div class="prod-img"><i class="fa-solid ${icon}"></i></div>
                        <div class="prod-info">
                            <div class="prod-title">${p.nombre} ${packBadge}</div>
                            <div class="prod-cat">${p.categoria}</div>
                            <div class="prod-price">S/ ${parseFloat(p.precio_venta).toFixed(2)}</div>
                        </div>
                        <div class="btn-add" onclick="agregar(${p.id})">
                            <i class="fa-solid fa-plus"></i>
                        </div>
                    </div>`;
            });
        }

        function agregar(id) {
            let p = productos.find(x => x.id == id);
            if(p.stock <= 0 && p.es_combo == 0) return alert('Ups, Agotado.');
            
            let ex = carrito.find(x => x.id == id);
            ex ? ex.cantidad++ : carrito.push({id: p.id, nombre: p.nombre, precio: parseFloat(p.precio_venta), cantidad: 1});
            
            if(navigator.vibrate) navigator.vibrate(50);
            actualizarUI();
        }

        function actualizarUI() {
            let cant = carrito.reduce((a,b)=>a+b.cantidad, 0);
            document.getElementById('cant-items').innerText = cant;
            document.getElementById('btn-carrito').style.display = cant > 0 ? 'block' : 'none';
        }

        function abrirCarrito() {
            let div = document.getElementById('items-carrito'), total = 0; div.innerHTML = '';
            carrito.forEach((i, x) => {
                total += i.precio * i.cantidad;
                div.innerHTML += `
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px; border-bottom:1px solid #333; padding-bottom:10px;">
                        <div>
                            <div style="color:#fff;">${i.nombre}</div>
                            <small style="color:#888;">${i.cantidad} x S/ ${i.precio.toFixed(2)}</small>
                        </div>
                        <div style="text-align:right;">
                            <div style="color:#fff;">S/ ${(i.precio * i.cantidad).toFixed(2)}</div>
                            <small style="color:#ef5350; cursor:pointer;" onclick="carrito.splice(${x},1); abrirCarrito(); actualizarUI()">Quitar</small>
                        </div>
                    </div>`;
            });
            document.getElementById('txt-total').innerText = 'S/ ' + total.toFixed(2);
            document.getElementById('modal-carrito').style.display = 'flex';
        }

        function cerrarCarrito() { document.getElementById('modal-carrito').style.display = 'none'; }

        function obtenerUbicacion() {
            let status = document.getElementById('gps-status');
            status.style.display = 'block'; status.innerText = 'Buscando GPS...'; status.style.color = '#FFD700';
            
            navigator.geolocation.getCurrentPosition(pos => {
                document.getElementById('cli-lat').value = pos.coords.latitude;
                document.getElementById('cli-lon').value = pos.coords.longitude;
                status.innerText = 'Ubicación Detectada'; status.style.color = '#66bb6a';
                
                fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${pos.coords.latitude}&lon=${pos.coords.longitude}`)
                .then(r=>r.json()).then(d=>{
                     if(d.address) document.getElementById('cli-direccion').value = d.address.road || 'Ubicación GPS';
                });
            }, err => {
                status.innerText = 'Error GPS (Actívalo)'; status.style.color = '#ef5350';
            }, {enableHighAccuracy: true});
        }

        function enviarPedido(e) {
            e.preventDefault();
            let btn = document.querySelector('#form-pedido button[type="submit"]');
            btn.disabled = true; btn.innerText = 'ENVIANDO...';

            let data = {
                cliente: {
                    nombre: document.getElementById('cli-nombre').value,
                    telefono: document.getElementById('cli-telefono').value,
                    direccion: document.getElementById('cli-direccion').value,
                    lat: document.getElementById('cli-lat').value,
                    lon: document.getElementById('cli-lon').value
                },
                carrito: carrito,
                total: carrito.reduce((a,b)=>a+(b.precio*b.cantidad),0),
                metodo: document.getElementById('cli-metodo').value
            };

            fetch('../api/pedido.php', { method:'POST', body: JSON.stringify(data) })
            .then(r=>r.json())
            .then(d => {
                if(d.success) {
                    window.location.href = 'track.php?id=' + d.id_pedido;
                } else {
                    alert('Error: ' + d.message);
                    btn.disabled = false; btn.innerText = 'CONFIRMAR PEDIDO';
                }
            })
            .catch(e => {
                alert('Error de conexión');
                btn.disabled = false; btn.innerText = 'REINTENTAR';
            });
        }
    </script>
</body>
</html>