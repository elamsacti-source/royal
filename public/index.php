<?php
// public/index.php
session_start(); // 1. INICIO DE SESIÓN PARA DETECTAR CLIENTE
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Royal Delivery</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <style>
        /* =========================================
           ESTILOS GENERALES (TEMA OSCURO PREMIUM)
           ========================================= */
        :root {
            --bg-body: #050505;
            --bg-card: #161616;
            --royal-gold: #FFD700;
            --text-main: #ffffff;
            --text-muted: #888888;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; outline: none; }

        body { 
            background-color: var(--bg-body); 
            color: var(--text-main); 
            font-family: 'Poppins', sans-serif; 
            padding-bottom: 90px; 
            -webkit-tap-highlight-color: transparent; 
        }

        /* --- CONTENEDOR CENTRAL TIPO APP --- */
        .app-container {
            max-width: 500px;
            margin: 0 auto;
            background: var(--bg-body);
            min-height: 100vh;
            border-left: 1px solid #1a1a1a;
            border-right: 1px solid #1a1a1a;
        }

        /* --- HEADER --- */
        .header-web { 
            background: rgba(22, 22, 22, 0.9); 
            backdrop-filter: blur(10px);
            padding: 15px 20px; 
            position: sticky; top: 0; z-index: 100; 
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid #333;
            box-shadow: 0 4px 15px rgba(0,0,0,0.5);
        }

        .brand-logo { font-weight: 800; font-size: 1.2rem; letter-spacing: 1px; color: var(--text-main); }
        .brand-logo i { color: var(--royal-gold); }

        .btn-mis-pedidos {
            font-size: 0.85rem; color: var(--text-muted); 
            border: 1px solid #333; padding: 6px 12px; border-radius: 20px; 
            cursor: pointer; transition: 0.3s; text-decoration: none;
            display: flex; align-items: center; gap: 5px;
        }
        .btn-mis-pedidos:active, .btn-mis-pedidos:hover { background: #333; color: #fff; }

        /* --- BUSCADOR --- */
        .search-container { padding: 20px; position: sticky; top: 60px; z-index: 90; background: var(--bg-body); }
        .search-box { position: relative; width: 100%; }
        .search-box input {
            width: 100%; background: #222; border: 1px solid #333;
            padding: 15px 15px 15px 45px; border-radius: 12px;
            color: #fff; font-size: 1rem; font-family: 'Poppins', sans-serif;
        }
        .search-box input::placeholder { color: #555; }
        .search-box i {
            position: absolute; left: 15px; top: 50%; transform: translateY(-50%);
            color: var(--royal-gold);
        }

        /* --- GRID DE PRODUCTOS --- */
        .grid-productos { 
            display: grid; grid-template-columns: repeat(2, 1fr); 
            gap: 15px; padding: 0 20px;
        }

        .card-prod {
            background: var(--bg-card); border: 1px solid #2a2a2a;
            border-radius: 15px; padding: 15px;
            display: flex; flex-direction: column; justify-content: space-between;
            height: 100%; cursor: pointer; position: relative; overflow: hidden;
        }
        .card-prod:active { transform: scale(0.98); border-color: var(--royal-gold); }

        .tag-combo { 
            position: absolute; top: 0; left: 0; font-size: 0.65rem; 
            background: var(--royal-gold); color: #000; padding: 3px 8px; 
            border-bottom-right-radius: 10px; font-weight: 800; 
        }

        .prod-icon { font-size: 2rem; color: #333; margin-bottom: 10px; text-align: center; }
        .prod-nombre { 
            color: #fff; font-weight: 500; font-size: 0.9rem; margin-bottom: 8px; 
            line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; 
            -webkit-box-orient: vertical; overflow: hidden;
        }
        
        .prod-footer { display: flex; justify-content: space-between; align-items: center; margin-top: auto; }
        .prod-precio { color: var(--royal-gold); font-size: 1.1rem; font-weight: 700; }
        .btn-add { 
            background: #222; color: #fff; width: 30px; height: 30px; 
            border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem;
        }

        /* --- BOTÓN FLOTANTE --- */
        .btn-flotante { 
            position: fixed; bottom: 25px; left: 50%; transform: translateX(-50%); 
            width: 90%; max-width: 460px;
            background: linear-gradient(135deg, #FFD700 0%, #FFA000 100%); 
            color: #000; padding: 16px; border-radius: 50px; 
            font-weight: 800; text-align: center; 
            box-shadow: 0 10px 25px rgba(255, 193, 7, 0.3); 
            z-index: 200; display: none; font-size: 1rem; cursor: pointer;
        }

        /* --- MODALES --- */
        .modal-bg { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index: 300; justify-content:center; align-items:flex-end; backdrop-filter: blur(5px); }
        .modal-caja { background: #111; width: 100%; max-width: 500px; border-radius: 20px 20px 0 0; border-top: 1px solid #333; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 -5px 30px rgba(0,0,0,0.5); }
        .modal-body { padding: 25px; overflow-y: auto; }
        
        .form-control { width: 100%; padding: 15px; background: #222; border: 1px solid #333; color: #fff; border-radius: 12px; margin-bottom: 12px; font-size: 1rem; }
        .form-control:focus { border-color: var(--royal-gold); }

        .btn-royal { width: 100%; border:none; padding: 18px; font-size:1rem; background: var(--royal-gold); color:#000; font-weight:800; border-radius:12px; cursor:pointer; text-transform: uppercase; }

        /* --- MAPA MODAL --- */
        #modal-mapa { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 2000; background: #000; }
        #map-selector { width: 100%; height: 85vh; }
        .map-footer { height: 15vh; background: #111; padding: 15px; display: flex; align-items: center; justify-content: space-between; border-top: 1px solid #333; }

        .msg-loading { text-align:center; padding: 50px; color:#444; }
    </style>
</head>
<body>

<div class="app-container">
    
    <div class="header-web">
        <div class="brand-logo">ROYAL <i class="fa-solid fa-wine-glass"></i></div>
        
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="mis_pedidos.php" class="btn-mis-pedidos">
                <i class="fa-solid fa-user-check"></i> Hola, <?= explode(' ', $_SESSION['nombre'])[0] ?>
            </a>
        <?php else: ?>
            <a href="../index.php" class="btn-mis-pedidos">
                <i class="fa-solid fa-arrow-right-to-bracket"></i> Entrar
            </a>
        <?php endif; ?>
    </div>

    <div class="search-container">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="buscador" placeholder="¿Qué vas a tomar hoy?" autocomplete="off" onkeyup="filtrarProductos()">
        </div>
    </div>

    <div id="lista-productos" class="grid-productos">
        <div class="msg-loading">
            <i class="fa-solid fa-circle-notch fa-spin" style="font-size: 2rem; margin-bottom: 15px;"></i><br>
            Cargando la carta...
        </div>
    </div>

</div>

<div id="btn-carrito" class="btn-flotante" onclick="abrirCarrito()">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <span style="background:#000; color:#fff; width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center;" id="cant-items">0</span>
        <span>VER MI PEDIDO</span>
        <span id="btn-total-preview">S/ 0.00</span>
    </div>
</div>

<div id="modal-carrito" class="modal-bg">
    <div class="modal-caja">
        <div style="padding:20px; border-bottom:1px solid #222; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="color:#fff; margin:0; font-size:1.2rem;">Tu Canasta</h3>
            <div onclick="cerrarCarrito()" style="color:#555; cursor:pointer; font-size:1.5rem;"><i class="fa-solid fa-xmark"></i></div>
        </div>
        
        <div class="modal-body">
            <div id="items-carrito"></div>
            
            <div style="display:flex; justify-content:space-between; font-size:1.3rem; color:#fff; font-weight:800; margin: 20px 0; border-top: 1px dashed #333; padding-top: 20px;">
                <span>Total</span>
                <span id="txt-total" style="color:var(--royal-gold);">S/ 0.00</span>
            </div>
            
            <h4 style="color:#888; margin-bottom:15px; font-size:0.9rem; text-transform:uppercase;">Datos de Entrega</h4>
            <form id="form-pedido" onsubmit="enviarPedido(event)">
                
                <?php 
                    $nombrePre = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : '';
                    $telPre    = isset($_SESSION['telefono']) ? $_SESSION['telefono'] : ''; 
                ?>
                <input type="text" id="cli-nombre" placeholder="Tu Nombre" required class="form-control" value="<?= $nombrePre ?>">
                <input type="tel" id="cli-telefono" placeholder="WhatsApp de contacto" required class="form-control" value="<?= $telPre ?>">
                
                <div style="display:flex; gap:10px; margin-bottom:10px;">
                    <input type="text" id="cli-direccion" placeholder="Dirección exacta" required class="form-control" style="margin-bottom:0;">
                </div>
                
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:15px;">
                    <button type="button" onclick="obtenerUbicacionGPS()" style="background:#222; border:1px solid #444; color:#fff; padding:10px; border-radius:8px; cursor:pointer; font-size:0.8rem;">
                        <i class="fa-solid fa-location-crosshairs"></i> Usar mi GPS
                    </button>
                    <button type="button" onclick="abrirMapaSelector()" style="background:#222; border:1px solid #FFD700; color:#FFD700; padding:10px; border-radius:8px; cursor:pointer; font-size:0.8rem;">
                        <i class="fa-solid fa-map-location-dot"></i> Elegir en Mapa
                    </button>
                </div>
                
                <small id="status-gps" style="color:#66bb6a; display:none; margin-bottom:15px; margin-top:5px; font-size:0.8rem;">📍 Ubicación GPS detectada</small>
                
                <input type="hidden" id="cli-lat"><input type="hidden" id="cli-lon">
                
                <select id="cli-metodo" class="form-control" style="margin-top:15px;">
                    <option value="Yape">Pago con Yape / Plin</option>
                    <option value="Efectivo">Pago en Efectivo</option>
                </select>

                <button type="submit" class="btn-royal" style="margin-top:15px;">
                    CONFIRMAR PEDIDO <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<div id="modal-mapa">
    <div id="map-selector"></div>
    <div class="map-footer">
        <div style="color:#fff;">
            <small style="color:#888;">Ubicación seleccionada</small><br>
            <b style="color:#FFD700;">Mueve el pin rojo</b>
        </div>
        <button onclick="confirmarMapa()" class="btn-royal" style="width: auto; padding: 10px 20px;">
            CONFIRMAR
        </button>
    </div>
</div>

<script>
    let productos = [], carrito = [];

    // Cargar productos al iniciar
    window.onload = () => {
        fetch('../api/productos.php')
            .then(r => r.json())
            .then(d => { 
                productos = d; 
                render(productos); 
            })
            .catch(e => {
                document.getElementById('lista-productos').innerHTML = '<div class="msg-loading">Error de conexión.</div>';
            });
    };

    function filtrarProductos() {
        let texto = document.getElementById('buscador').value.toLowerCase();
        let filtrados = productos.filter(p => p.nombre.toLowerCase().includes(texto));
        render(filtrados);
    }

    function render(lista) {
        let div = document.getElementById('lista-productos'); 
        div.innerHTML = '';
        
        if(lista.length === 0) { 
            div.innerHTML = '<div class="msg-loading">No encontramos ese licor :(</div>'; 
            return; 
        }

        lista.forEach(p => {
            let packTag = p.es_combo == 1 ? '<div class="tag-combo">PACK</div>' : '';
            let icono = 'fa-wine-bottle';
            if(p.nombre.toLowerCase().includes('cerveza')) icono = 'fa-beer-mug-empty';
            if(p.nombre.toLowerCase().includes('cigarro')) icono = 'fa-smoking';
            if(p.nombre.toLowerCase().includes('hielo')) icono = 'fa-icicles';
            
            let stockHtml = '';
            if(p.es_combo == 0) {
                if(p.stock > 5) stockHtml = `<span style="font-size:0.75rem; color:#444;">Stock disponible</span>`;
                else if(p.stock > 0) stockHtml = `<span style="font-size:0.75rem; color:#FFD700;">¡Quedan ${p.stock}!</span>`;
                else stockHtml = `<span style="font-size:0.75rem; color:#ef5350;">Agotado</span>`;
            }

            div.innerHTML += `
                <div class="card-prod" onclick="agregar(${p.id})">
                    ${packTag}
                    <div class="prod-icon"><i class="fa-solid ${icono}"></i></div>
                    <div class="prod-nombre">${p.nombre}</div>
                    <div class="prod-footer">
                        <div class="prod-precio">S/ ${parseFloat(p.precio_venta).toFixed(2)}</div>
                        <div class="btn-add"><i class="fa-solid fa-plus"></i></div>
                    </div>
                    <div style="margin-top:5px;">${stockHtml}</div>
                </div>`;
        });
    }

    // --- LÓGICA DE CARRITO (Unidades/Cajas) ---
    function agregar(id) {
        let p = productos.find(x => x.id == id);
        
        if(p.stock <= 0 && p.es_combo == 0) return alert('Lo sentimos, producto agotado.');
        
        let ex = carrito.find(x => x.id == id);
        
        if(ex) {
            // Validar stock sumando lo que ya se pidió
            let factor = (ex.modo === 'caja') ? p.unidades_caja : 1;
            let totalPedido = (ex.cantidad + 1) * factor;

            if (p.es_combo == 0 && totalPedido > p.stock) {
                return alert(`Stock insuficiente. Solo quedan ${p.stock} unidades.`);
            }
            ex.cantidad++;
        } else {
            // Nuevo item: por defecto Unidad
            carrito.push({
                id: p.id, 
                nombre: p.nombre, 
                precio_unitario: parseFloat(p.precio_venta),
                precio_caja: parseFloat(p.precio_caja || 0),
                unidades_caja: parseInt(p.unidades_caja || 1),
                precio: parseFloat(p.precio_venta),
                cantidad: 1,
                es_combo: p.es_combo,
                modo: 'unidad'
            });
        }
        
        if(navigator.vibrate) navigator.vibrate(50);
        actualizarUI();
    }

    function actualizarUI() {
        let cant = carrito.reduce((a, b) => a + b.cantidad, 0);
        let total = carrito.reduce((a, b) => a + (b.precio * b.cantidad), 0);

        document.getElementById('cant-items').innerText = cant;
        document.getElementById('btn-total-preview').innerText = 'S/ ' + total.toFixed(2);
        
        let btn = document.getElementById('btn-carrito');
        if(cant > 0) {
            btn.style.display = 'block';
        } else {
            btn.style.display = 'none';
            cerrarCarrito();
        }
    }

    function abrirCarrito() {
        let div = document.getElementById('items-carrito'), total = 0; div.innerHTML = '';
        
        carrito.forEach((i, x) => {
            total += i.precio * i.cantidad;
            
            // Selector de Unidad/Caja si aplica
            let selector = '';
            if(i.unidades_caja > 1 && i.es_combo == 0) {
                selector = `
                <select onchange="cambiarModo(${x}, this.value)" style="background:#222; color:#FFD700; border:1px solid #444; padding:2px; border-radius:4px; font-size:0.8rem; margin-top:5px;">
                    <option value="unidad" ${i.modo === 'unidad' ? 'selected' : ''}>Unidad</option>
                    <option value="caja" ${i.modo === 'caja' ? 'selected' : ''}>Caja x${i.unidades_caja}</option>
                </select>`;
            }

            div.innerHTML += `
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:15px; border-bottom:1px solid #222; padding-bottom:15px;">
                    <div>
                        <div style="color:#fff; font-weight:600;">${i.nombre}</div>
                        <small style="color:#888;">${i.cantidad} x S/ ${i.precio.toFixed(2)}</small>
                        <div>${selector}</div>
                    </div>
                    <div style="text-align:right;">
                        <div style="color:#fff; font-weight:bold;">S/ ${(i.precio * i.cantidad).toFixed(2)}</div> 
                        <small style="color:#ef5350; cursor:pointer; display:block; margin-top:5px;" onclick="eliminar(${x})">Eliminar</small>
                    </div>
                </div>`;
        });
        document.getElementById('txt-total').innerText = 'S/ ' + total.toFixed(2);
        document.getElementById('modal-carrito').style.display = 'flex';
    }

    function cambiarModo(index, nuevoModo) {
        let item = carrito[index];
        item.modo = nuevoModo;
        
        if(nuevoModo === 'caja') {
            item.precio = (item.precio_caja > 0) ? item.precio_caja : (item.precio_unitario * item.unidades_caja);
        } else {
            item.precio = item.precio_unitario;
        }
        
        actualizarUI();
        abrirCarrito();
    }

    function eliminar(idx) { carrito.splice(idx, 1); actualizarCarrito(); if(carrito.length>0) abrirCarrito(); }
    function cerrarCarrito() { document.getElementById('modal-carrito').style.display = 'none'; }

    // --- LÓGICA DE MAPA (LEAFLET) ---
    let mapSel, markerSel;
    
    function abrirMapaSelector() {
        document.getElementById('modal-mapa').style.display = 'block';
        if(!mapSel) {
            // Iniciar mapa en Lima por defecto
            mapSel = L.map('map-selector').setView([-12.046, -77.042], 13);
            L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png').addTo(mapSel);
            
            // Icono personalizado (Pin Rojo)
            const pinIcon = L.divIcon({
                className: 'custom-div-icon',
                html: `<div style='background:#ef5350; width:30px; height:30px; border-radius:50% 50% 50% 0; transform:rotate(-45deg); border:2px solid #fff;'></div>`,
                iconSize: [30, 30], iconAnchor: [15, 30]
            });
            
            markerSel = L.marker(mapSel.getCenter(), {draggable: true, icon: pinIcon}).addTo(mapSel);
            
            // Ir a GPS actual si es posible
            navigator.geolocation.getCurrentPosition(pos => {
                mapSel.setView([pos.coords.latitude, pos.coords.longitude], 16);
                markerSel.setLatLng([pos.coords.latitude, pos.coords.longitude]);
            });

            // Mover pin al hacer click
            mapSel.on('click', function(e) { markerSel.setLatLng(e.latlng); });
        }
        setTimeout(() => { mapSel.invalidateSize(); }, 200);
    }

    function confirmarMapa() {
        const pos = markerSel.getLatLng();
        document.getElementById('cli-lat').value = pos.lat;
        document.getElementById('cli-lon').value = pos.lng;
        document.getElementById('status-gps').style.display = 'block';
        document.getElementById('status-gps').innerText = "📍 Ubicación de mapa fijada";
        document.getElementById('modal-mapa').style.display = 'none';
        
        // Autocompletar dirección
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${pos.lat}&lon=${pos.lng}`)
            .then(r=>r.json()).then(d => { 
                if(d.display_name) document.getElementById('cli-direccion').value = d.display_name.split(',')[0]; 
            });
    }

    function obtenerUbicacionGPS() {
        let btn = document.querySelector('button[onclick="obtenerUbicacionGPS()"]');
        if(!navigator.geolocation) return alert('GPS no soportado.');
        
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
        
        navigator.geolocation.getCurrentPosition(pos => {
            document.getElementById('cli-lat').value = pos.coords.latitude;
            document.getElementById('cli-lon').value = pos.coords.longitude;
            document.getElementById('status-gps').style.display = 'block';
            document.getElementById('status-gps').innerText = "📍 GPS Actual detectado";
            
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${pos.coords.latitude}&lon=${pos.coords.longitude}`)
            .then(r => r.json()).then(d => { 
                document.getElementById('cli-direccion').value = d.display_name.split(',')[0];
                btn.innerHTML = '<i class="fa-solid fa-check"></i> GPS Listo';
            });
        }, err => {
            alert('Error al obtener GPS.');
            btn.innerHTML = 'Reintentar GPS';
        }, { enableHighAccuracy: true });
    }

    // --- ENVIAR PEDIDO ---
    // Inyectamos ID de sesión desde PHP
    const idUsuarioSesion = <?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null' ?>;

    function enviarPedido(e) {
        e.preventDefault();
        let btn = document.querySelector('#form-pedido button[type="submit"]');
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ENVIANDO...';
        btn.disabled = true;

        let total = carrito.reduce((a, b) => a + (b.precio * b.cantidad), 0);
        let datos = {
            carrito, 
            total, 
            metodo: document.getElementById('cli-metodo').value,
            id_cliente: idUsuarioSesion, // Enviamos ID para historial
            cliente: {
                nombre: document.getElementById('cli-nombre').value, 
                telefono: document.getElementById('cli-telefono').value,
                direccion: document.getElementById('cli-direccion').value, 
                lat: document.getElementById('cli-lat').value, 
                lon: document.getElementById('cli-lon').value
            }
        };
        
        fetch('../api/pedido.php', { method: 'POST', body: JSON.stringify(datos) })
        .then(r => r.json())
        .then(d => {
            if(d.success) {
                if(confirm('✅ PEDIDO ENVIADO.\n\n¿Quieres ver el seguimiento en tiempo real?')) {
                    window.location.href = 'track.php?id=' + d.id_pedido;
                } else {
                    location.reload();
                }
            } else {
                alert('Error: ' + d.message);
                btn.innerHTML = 'INTENTAR NUEVAMENTE';
                btn.disabled = false;
            }
        })
        .catch(err => {
            alert('Error de conexión.');
            btn.innerHTML = 'INTENTAR NUEVAMENTE';
            btn.disabled = false;
        });
    }
</script>
</body>
</html>