<?php
// royal/public/index.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Royal Delivery</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    
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
            max-width: 500px; /* Ancho de un celular grande */
            margin: 0 auto;   /* Centrado en pantalla */
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
            cursor: pointer; transition: 0.3s;
        }
        .btn-mis-pedidos:active { background: #333; color: #fff; }

        /* --- BUSCADOR --- */
        .search-container { padding: 20px; position: sticky; top: 60px; z-index: 90; background: var(--bg-body); }
        .search-box {
            position: relative;
            width: 100%;
        }
        .search-box input {
            width: 100%;
            background: #222;
            border: 1px solid #333;
            padding: 15px 15px 15px 45px;
            border-radius: 12px;
            color: #fff;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
        }
        .search-box input::placeholder { color: #555; }
        .search-box i {
            position: absolute; left: 15px; top: 50%; transform: translateY(-50%);
            color: var(--royal-gold);
        }

        /* --- GRID DE PRODUCTOS --- */
        .grid-productos { 
            display: grid; 
            grid-template-columns: repeat(2, 1fr); /* 2 Columnas Estándar */
            gap: 15px; 
            padding: 0 20px;
        }

        .card-prod {
            background: var(--bg-card);
            border: 1px solid #2a2a2a;
            border-radius: 15px;
            padding: 15px;
            display: flex; 
            flex-direction: column; 
            justify-content: space-between;
            height: 100%;
            cursor: pointer; 
            transition: transform 0.1s, border-color 0.2s;
            position: relative;
            overflow: hidden;
        }
        .card-prod:active { transform: scale(0.98); border-color: var(--royal-gold); }

        .tag-combo { 
            position: absolute; top: 0; left: 0;
            font-size: 0.65rem; 
            background: var(--royal-gold); 
            color: #000; 
            padding: 3px 8px; 
            border-bottom-right-radius: 10px; 
            font-weight: 800; 
        }

        .prod-icon {
            font-size: 2rem; color: #333; margin-bottom: 10px; text-align: center;
        }

        .prod-nombre { 
            color: #fff; font-weight: 500; font-size: 0.9rem; 
            margin-bottom: 8px; line-height: 1.3; 
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
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
            z-index: 200; display: none; font-size: 1rem; letter-spacing: 0.5px;
            cursor: pointer;
        }

        /* --- MODALES --- */
        .modal-bg { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index: 300; justify-content:center; align-items:flex-end; backdrop-filter: blur(5px); }
        .modal-caja { background: #111; width: 100%; max-width: 500px; border-radius: 20px 20px 0 0; border-top: 1px solid #333; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 -5px 30px rgba(0,0,0,0.5); }
        .modal-body { padding: 25px; overflow-y: auto; }
        
        .form-control { width: 100%; padding: 15px; background: #222; border: 1px solid #333; color: #fff; border-radius: 12px; margin-bottom: 12px; font-size: 1rem; font-family: 'Poppins', sans-serif; }
        .form-control:focus { border-color: var(--royal-gold); }

        .btn-royal { width: 100%; border:none; padding: 18px; font-size:1rem; background: var(--royal-gold); color:#000; font-weight:800; border-radius:12px; cursor:pointer; text-transform: uppercase; }

        /* Mensajes de estado */
        .msg-loading { text-align:center; padding: 50px; color:#444; }
    </style>
</head>
<body>

<div class="app-container">
    
    <div class="header-web">
        <div class="brand-logo">ROYAL <i class="fa-solid fa-wine-glass"></i></div>
        <div class="btn-mis-pedidos" onclick="buscarPedidoManual()">
            <i class="fa-solid fa-clock-rotate-left"></i> Mis Pedidos
        </div>
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
                <input type="text" id="cli-nombre" placeholder="Tu Nombre" required class="form-control">
                <input type="tel" id="cli-telefono" placeholder="WhatsApp de contacto" required class="form-control">
                
                <div style="display:flex; gap:10px;">
                    <input type="text" id="cli-direccion" placeholder="Dirección exacta" required class="form-control" style="margin-bottom:0;">
                    <button type="button" onclick="obtenerUbicacion()" style="width:60px; background:#222; border:1px solid var(--royal-gold); color:var(--royal-gold); border-radius:12px; cursor:pointer;">
                        <i class="fa-solid fa-location-crosshairs"></i>
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

<script>
    let productos = [], carrito = [];

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

    function buscarPedidoManual() {
        let id = prompt("Ingresa el ID de tu pedido para rastrearlo:");
        if(id) window.location.href = 'track.php?id=' + id;
    }

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
            // Icono según categoría (simple)
            let icono = 'fa-wine-bottle';
            if(p.nombre.toLowerCase().includes('cerveza')) icono = 'fa-beer-mug-empty';
            if(p.nombre.toLowerCase().includes('cigarro')) icono = 'fa-smoking';
            if(p.nombre.toLowerCase().includes('hielo')) icono = 'fa-icicles';
            
            // Texto de Stock
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

    function agregar(id) {
        let p = productos.find(x => x.id == id);
        
        // 1. VALIDACIÓN STOCK CERO
        if(p.stock <= 0 && p.es_combo == 0) return alert('Lo sentimos, producto agotado.');
        
        let ex = carrito.find(x => x.id == id);
        let cantidad_actual = ex ? ex.cantidad : 0;

        // 2. VALIDACIÓN DE LÍMITE
        if (p.es_combo == 0 && (cantidad_actual + 1) > p.stock) {
            return alert(`Solo quedan ${p.stock} unidades disponibles.`);
        }

        if(ex) {
            ex.cantidad++;
        } else {
            carrito.push({
                id: p.id, 
                nombre: p.nombre, 
                precio: parseFloat(p.precio_venta), 
                cantidad: 1,
                es_combo: p.es_combo 
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
            div.innerHTML += `
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #222; padding-bottom:15px;">
                    <div>
                        <div style="color:#fff; font-weight:600;">${i.nombre}</div>
                        <small style="color:#888;">${i.cantidad} x S/ ${i.precio.toFixed(2)}</small>
                    </div>
                    <div style="text-align:right;">
                        <div style="color:#fff; font-weight:bold;">S/ ${(i.precio * i.cantidad).toFixed(2)}</div> 
                        <small style="color:#ef5350; cursor:pointer;" onclick="eliminar(${x})">Eliminar</small>
                    </div>
                </div>`;
        });
        document.getElementById('txt-total').innerText = 'S/ ' + total.toFixed(2);
        document.getElementById('modal-carrito').style.display = 'flex';
    }

    function eliminar(idx) { carrito.splice(idx, 1); actualizarCarrito(); if(carrito.length>0) abrirCarrito(); }
    function cerrarCarrito() { document.getElementById('modal-carrito').style.display = 'none'; }

    function obtenerUbicacion() {
        let btn = document.querySelector('button[onclick="obtenerUbicacion()"]');
        if(!navigator.geolocation) return alert('GPS no disponible.');
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
        
        navigator.geolocation.getCurrentPosition(pos => {
            document.getElementById('cli-lat').value = pos.coords.latitude;
            document.getElementById('cli-lon').value = pos.coords.longitude;
            document.getElementById('status-gps').style.display = 'block';
            
            // Reverse Geocoding (Opcional)
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${pos.coords.latitude}&lon=${pos.coords.longitude}`)
            .then(r => r.json()).then(d => { 
                let direccion = d.display_name.split(',')[0]; 
                document.getElementById('cli-direccion').value = direccion;
                btn.innerHTML = '<i class="fa-solid fa-check"></i>';
            });
        }, err => {
            btn.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i>';
            alert('No pudimos detectar tu ubicación. Escríbela manualmente.');
        }, { enableHighAccuracy: true });
    }

    function enviarPedido(e) {
        e.preventDefault();
        let btn = document.querySelector('#form-pedido button[type="submit"]');
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ENVIANDO...';
        btn.disabled = true;

        let total = carrito.reduce((a, b) => a + (b.precio * b.cantidad), 0);
        let datos = {
            carrito, total, metodo: document.getElementById('cli-metodo').value,
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
                if(confirm('✅ ¡PEDIDO ENVIADO!\n\nTu orden #' + d.id_pedido + ' está siendo procesada.\n\n¿Quieres ver el seguimiento?')) {
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