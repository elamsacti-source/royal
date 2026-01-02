<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Royal Delivery</title>
    <link rel="stylesheet" href="../assets/css/estilos.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #000; padding-bottom: 90px; -webkit-tap-highlight-color: transparent; }
        
        /* Cabecera */
        .header-web { 
            background: rgba(10, 10, 10, 0.95); backdrop-filter: blur(5px);
            padding: 15px 20px; border-bottom: 2px solid var(--royal-gold); 
            position: sticky; top: 0; z-index: 100; display: flex; justify-content: space-between; align-items: center;
        }

        /* Botón Recuperar Pedido (Barra Superior) */
        #barra-recuperar {
            background: #29b6f6; color: #000; padding: 10px; text-align: center;
            font-weight: bold; cursor: pointer; display: none; animation: slideDown 0.5s;
        }
        @keyframes slideDown { from { transform: translateY(-100%); } to { transform: translateY(0); } }

        .container { max-width: 800px; margin: 0 auto; padding: 15px; }
        
        /* Grid de botones */
        .grid-productos { 
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; 
        }
        @media(min-width: 600px) { .grid-productos { grid-template-columns: repeat(4, 1fr); } }

        .card-prod {
            background: #1a1a1a; border: 1px solid #333; border-radius: 8px;
            padding: 15px 10px; display: flex; flex-direction: column; justify-content: space-between;
            text-align: center; height: 100%; cursor: pointer; transition: 0.1s;
        }
        .card-prod:active { background: #252525; transform: scale(0.96); border-color: var(--royal-gold); }

        .prod-nombre { color: #fff; font-weight: 600; font-size: 0.95rem; margin-bottom: 5px; line-height: 1.2; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .prod-precio { color: var(--royal-gold); font-size: 1.1rem; font-weight: bold; }
        .tag-combo { font-size: 0.7rem; background: var(--royal-gold); color: #000; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-bottom: 5px; font-weight: bold; }

        .btn-flotante { position: fixed; bottom: 20px; left: 5%; width: 90%; background: var(--royal-gold); color: #000; padding: 15px; border-radius: 50px; font-weight: bold; text-align: center; box-shadow: 0 5px 20px rgba(255, 215, 0, 0.3); z-index: 200; display: none; font-size: 1.1rem; }

        .modal-bg { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index: 300; justify-content:center; align-items:flex-end; }
        .modal-caja { background: #1a1a1a; width: 100%; max-width: 500px; border-radius: 15px 15px 0 0; border-top: 1px solid var(--royal-gold); max-height: 90vh; display: flex; flex-direction: column; }
        .modal-body { padding: 20px; overflow-y: auto; }
        .form-control { width: 100%; padding: 12px; background: #222; border: 1px solid #444; color: #fff; border-radius: 8px; margin-bottom: 10px; font-size: 1rem; }
    </style>
</head>
<body>

    <div id="barra-recuperar" onclick="irAlUltimoPedido()">
        <i class="fa-solid fa-motorcycle"></i> Tienes un pedido en curso. <u>Ver Mapa</u>
    </div>

    <div class="header-web">
        <h2 style="color:var(--royal-gold); margin:0; font-size:1.4rem;">ROYAL <i class="fa-solid fa-wine-bottle"></i></h2>
        
        <div onclick="buscarPedidoManual()" style="color:#fff; font-size:0.9rem; cursor:pointer; border:1px solid #444; padding:5px 10px; border-radius:20px;">
            <i class="fa-solid fa-magnifying-glass"></i> Mis Pedidos
        </div>
    </div>

    <div class="container">
        <input type="text" id="buscador" placeholder="🔍 Buscar licor..." class="form-control" style="margin-bottom: 15px; background: #111; border-color: #333;" onkeyup="filtrarProductos()">

        <div id="lista-productos" class="grid-productos">
            <p style="color:#666; text-align:center; grid-column:1/-1; padding: 20px;">
                <i class="fa-solid fa-circle-notch fa-spin"></i> Cargando carta...
            </p>
        </div>
    </div>

    <div id="btn-carrito" class="btn-flotante" onclick="abrirCarrito()">
        <i class="fa-solid fa-cart-shopping"></i> Ver Pedido <span id="cant-items" style="background:#000; color:#fff; padding:2px 8px; border-radius:10px; font-size:0.9rem; margin-left: 5px;">0</span>
    </div>

    <div id="modal-carrito" class="modal-bg">
        <div class="modal-caja">
            <div style="padding:15px; background:#111; border-bottom:1px solid #333; display:flex; justify-content:space-between; align-items:center;">
                <h3 style="color:#fff; margin:0;">Tu Canasta</h3>
                <button onclick="cerrarCarrito()" style="background:none; border:none; color:#ef5350; font-size:2rem; padding:0 10px;">&times;</button>
            </div>
            
            <div class="modal-body">
                <div id="items-carrito"></div>
                
                <div style="text-align:right; font-size:1.4rem; color:var(--royal-gold); font-weight:bold; margin: 20px 0; border-top: 1px solid #333; padding-top: 15px;">
                    Total: <span id="txt-total">S/ 0.00</span>
                </div>
                
                <h4 style="color:#fff; margin-bottom:10px;">Datos de Entrega</h4>
                <form id="form-pedido" onsubmit="enviarPedido(event)">
                    <input type="text" id="cli-nombre" placeholder="Nombre" required class="form-control">
                    <input type="tel" id="cli-telefono" placeholder="WhatsApp" required class="form-control">
                    
                    <div style="display:flex; gap:5px;">
                        <input type="text" id="cli-direccion" placeholder="Dirección" required class="form-control" style="margin-bottom:0;">
                        <button type="button" onclick="obtenerUbicacion()" class="btn-royal" style="width:60px; padding:0; display:flex; align-items:center; justify-content:center; background:#333; border:1px solid var(--royal-gold); color:var(--royal-gold); border-radius:8px;">
                            <i class="fa-solid fa-location-crosshairs" style="font-size:1.2rem;"></i>
                        </button>
                    </div>
                    <small id="status-gps" style="color:#66bb6a; display:none; margin-bottom:10px; margin-top:5px;">📍 Ubicación detectada</small>
                    
                    <input type="hidden" id="cli-lat"><input type="hidden" id="cli-lon">
                    
                    <select id="cli-metodo" class="form-control" style="margin-top:15px;">
                        <option value="Yape">Yape / Plin</option>
                        <option value="Efectivo">Efectivo</option>
                    </select>

                    <button type="submit" class="btn-royal btn-block" style="margin-top:20px; padding:18px; font-size:1.1rem; box-shadow: 0 0 15px rgba(255, 215, 0, 0.2);">
                        PEDIR AHORA <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        let productos = [], carrito = [];

        window.onload = () => {
            fetch('../api/productos.php').then(r => r.json()).then(d => { productos = d; render(productos); }).catch(e => {
                document.getElementById('lista-productos').innerHTML = '<p style="color:#666; text-align:center; grid-column:1/-1;">Error al cargar productos.</p>';
            });

            // --- LÓGICA DE RECUPERACIÓN DE PEDIDO ---
            const lastId = localStorage.getItem('royal_last_order');
            if(lastId) {
                // Opcional: Verificar con API si sigue activo, por ahora asumimos que sí
                document.getElementById('barra-recuperar').style.display = 'block';
                document.getElementById('barra-recuperar').innerHTML = `<i class="fa-solid fa-motorcycle"></i> Pedido #${lastId} en curso. <u>Ver Mapa</u>`;
            }
        };

        function irAlUltimoPedido() {
            const lastId = localStorage.getItem('royal_last_order');
            if(lastId) window.location.href = 'track.php?id=' + lastId;
        }

        function buscarPedidoManual() {
            let id = prompt("Ingresa tu Número de Pedido (#ID):");
            if(id) {
                // Guardamos este ID como el último para recordarlo
                localStorage.setItem('royal_last_order', id);
                window.location.href = 'track.php?id=' + id;
            }
        }

        function filtrarProductos() {
            let texto = document.getElementById('buscador').value.toLowerCase();
            let filtrados = productos.filter(p => p.nombre.toLowerCase().includes(texto));
            render(filtrados);
        }

        function render(lista) {
            let div = document.getElementById('lista-productos'); div.innerHTML = '';
            if(lista.length === 0) { div.innerHTML = '<p style="color:#666; text-align:center; grid-column:1/-1;">No encontrado.</p>'; return; }
            lista.forEach(p => {
                let packTag = p.es_combo == 1 ? '<div class="tag-combo">PACK</div>' : '';
                div.innerHTML += `
                    <div class="card-prod" onclick="agregar(${p.id})">
                        ${packTag}
                        <div class="prod-nombre">${p.nombre}</div>
                        <div class="prod-precio">S/ ${parseFloat(p.precio_venta).toFixed(2)}</div>
                        <div style="font-size:0.75rem; color:#666; margin-top:5px;">Toque para agregar</div>
                    </div>`;
            });
        }

        function agregar(id) {
            let p = productos.find(x => x.id == id);
            if(p.stock <= 0 && p.es_combo == 0) return alert('Producto Agotado');
            let ex = carrito.find(x => x.id == id);
            ex ? ex.cantidad++ : carrito.push({id: p.id, nombre: p.nombre, precio: parseFloat(p.precio_venta), cantidad: 1});
            if(navigator.vibrate) navigator.vibrate(50);
            actualizarUI();
        }

        function actualizarUI() {
            let cant = carrito.reduce((a, b) => a + b.cantidad, 0);
            document.getElementById('cant-items').innerText = cant;
            let btn = document.getElementById('btn-carrito');
            if(cant > 0) {
                btn.style.display = 'block';
                btn.style.transform = 'scale(1.05)';
                setTimeout(() => btn.style.transform = 'scale(1)', 100);
            } else {
                btn.style.display = 'none';
                cerrarCarrito();
            }
        }

        function abrirCarrito() {
            let div = document.getElementById('items-carrito'), total = 0; div.innerHTML = '';
            carrito.forEach((i, x) => {
                total += i.precio * i.cantidad;
                div.innerHTML += `
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; border-bottom:1px solid #222; padding-bottom:12px;">
                        <div style="color:#fff;">
                            <div style="font-weight:600; font-size:1rem;">${i.nombre}</div>
                            <small style="color:#888;">${i.cantidad} x S/ ${i.precio.toFixed(2)}</small>
                        </div>
                        <div style="text-align:right;">
                            <div style="color:#fff; font-weight:bold;">S/ ${(i.precio * i.cantidad).toFixed(2)}</div> 
                            <small style="color:#ef5350; font-weight:bold; cursor:pointer; padding:5px;" onclick="carrito.splice(${x},1); abrirCarrito(); actualizarUI();">ELIMINAR</small>
                        </div>
                    </div>`;
            });
            document.getElementById('txt-total').innerText = 'S/ ' + total.toFixed(2);
            document.getElementById('modal-carrito').style.display = 'flex';
        }

        function cerrarCarrito() { document.getElementById('modal-carrito').style.display = 'none'; }

        function obtenerUbicacion() {
            let btn = document.querySelector('button[onclick="obtenerUbicacion()"]');
            if(!navigator.geolocation) return alert('GPS inactivo.');
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            navigator.geolocation.getCurrentPosition(pos => {
                document.getElementById('cli-lat').value = pos.coords.latitude;
                document.getElementById('cli-lon').value = pos.coords.longitude;
                document.getElementById('status-gps').style.display = 'block';
                fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${pos.coords.latitude}&lon=${pos.coords.longitude}`)
                .then(r => r.json()).then(d => { 
                    let direccion = d.display_name.split(',')[0];
                    if(d.address && d.address.road) direccion = d.address.road + (d.address.house_number ? ' ' + d.address.house_number : '');
                    document.getElementById('cli-direccion').value = direccion; 
                    btn.innerHTML = '<i class="fa-solid fa-check"></i>';
                });
            }, err => {
                btn.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i>';
                alert('No se pudo obtener ubicación.');
            }, { enableHighAccuracy: true });
        }

        function enviarPedido(e) {
            e.preventDefault();
            let btn = document.querySelector('#form-pedido button[type="submit"]');
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Enviando...';
            btn.disabled = true;

            let total = carrito.reduce((a, b) => a + (b.precio * b.cantidad), 0);
            let datos = {
                carrito, total, metodo: document.getElementById('cli-metodo').value,
                cliente: {
                    nombre: document.getElementById('cli-nombre').value, telefono: document.getElementById('cli-telefono').value,
                    direccion: document.getElementById('cli-direccion').value, lat: document.getElementById('cli-lat').value, lon: document.getElementById('cli-lon').value
                }
            };
            
            fetch('../api/pedido.php', { method: 'POST', body: JSON.stringify(datos) })
            .then(r => r.json())
            .then(d => {
                if(d.success) {
                    // --- GUARDAR ID EN NAVEGADOR ---
                    localStorage.setItem('royal_last_order', d.id_pedido);
                    
                    if(confirm('✅ ¡PEDIDO RECIBIDO!\n\nTu orden #' + d.id_pedido + ' ha sido enviada.\n\n¿Ver seguimiento en mapa?')) {
                        window.location.href = 'track.php?id=' + d.id_pedido;
                    } else {
                        location.reload();
                    }
                } else {
                    alert('Error: ' + d.message);
                    btn.innerHTML = 'INTENTAR DE NUEVO';
                    btn.disabled = false;
                }
            })
            .catch(err => {
                alert('Error de conexión');
                btn.innerHTML = 'INTENTAR DE NUEVO';
                btn.disabled = false;
            });
        }
    </script>
</body>
</html>