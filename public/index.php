<?php
session_start();
require_once '../config/db.php';

// Obtener ID de usuario
$id_usuario = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Royal - Catálogo</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&family=Cinzel:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* =========================================
           CORRECCIÓN Z-INDEX (SUPERIOR A TODO)
        ========================================= */
        .swal2-container {
            z-index: 99999 !important; /* MÁS ALTO QUE EL CARRITO */
        }

        /* =========================================
           ESTILO ROYAL DARK MOBILE PRO
        ========================================= */
        :root {
            --bg-body: #050505;
            --bg-card: #141414;
            --gold: #FFD700;
            --text-main: #ffffff;
            --text-muted: #888888;
        }

        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }

        body { 
            background-color: var(--bg-body); 
            font-family: 'Inter', sans-serif; 
            margin: 0; padding: 0;
            color: var(--text-main);
            padding-bottom: 100px; /* Espacio para el carrito */
        }

        /* HEADER PREMIUM CON BOTONES CUADRADOS */
        .royal-header {
            background: rgba(15, 15, 15, 0.95);
            backdrop-filter: blur(10px);
            padding: 15px 20px;
            position: sticky; top: 0; z-index: 1000;
            border-bottom: 1px solid #333;
            display: flex; justify-content: space-between; align-items: center;
        }
        .brand { 
            font-family: 'Cinzel', serif; color: var(--gold); 
            font-size: 1.5rem; text-decoration: none; 
            display: flex; align-items: center; gap: 8px;
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.3);
        }

        .header-actions { display: flex; gap: 10px; }

        /* BOTONES DEL HEADER */
        .btn-icon-header {
            width: 40px; height: 40px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid #333;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 1.1rem;
            text-decoration: none; transition: 0.3s;
        }
        .btn-icon-header:active { transform: scale(0.95); background: var(--gold); color: #000; }
        .btn-icon-header.moto { border-color: var(--gold); color: var(--gold); } 

        /* BANNER */
        .hero-mini {
            text-align: center; padding: 25px 15px;
            background: radial-gradient(circle at center, #1a1a1a 0%, #000 100%);
            border-bottom: 1px solid #222; margin-bottom: 20px;
        }
        .hero-mini h2 { font-family: 'Cinzel', serif; margin: 0; color: #fff; font-size: 1.6rem; }
        .hero-mini p { color: var(--gold); font-size: 0.75rem; letter-spacing: 3px; text-transform: uppercase; margin: 5px 0 0; }

        /* BUSCADOR */
        .search-container { padding: 0 15px; margin-bottom: 25px; }
        .search-input {
            width: 100%; background: #111; border: 1px solid #333;
            padding: 15px 20px; border-radius: 12px; color: #fff; font-size: 1rem;
            outline: none; transition: 0.3s;
        }
        .search-input:focus { border-color: var(--gold); background: #1a1a1a; box-shadow: 0 0 15px rgba(255, 215, 0, 0.1); }

        /* GRID DE PRODUCTOS (CUADROS) */
        .container { padding: 0 15px; max-width: 900px; margin: auto; }
        
        .product-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr); /* 2 Columnas en celular */
            gap: 15px;
        }
        @media (min-width: 768px) { .product-grid { grid-template-columns: repeat(4, 1fr); } }

        /* TARJETA DE PRODUCTO */
        .prod-card {
            background: var(--bg-card);
            border: 1px solid #222;
            border-radius: 16px;
            padding: 15px;
            display: flex; flex-direction: column; align-items: center; text-align: center;
            position: relative; overflow: hidden;
            transition: transform 0.2s, border-color 0.2s;
            height: 100%;
        }
        .prod-card:active { transform: scale(0.96); background: #1a1a1a; }
        
        .prod-icon-area {
            height: 80px; width: 80px;
            background: radial-gradient(circle, #222 0%, transparent 70%);
            border-radius: 50%; margin-bottom: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 2.5rem; color: #444; transition: 0.3s;
        }
        .prod-card:hover .prod-icon-area { color: var(--gold); transform: scale(1.1); }

        .prod-cat { font-size: 0.6rem; color: #666; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
        .prod-title { font-weight: 600; font-size: 0.95rem; line-height: 1.3; color: #eee; margin-bottom: 8px; flex: 1; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .prod-price { color: var(--gold); font-weight: 800; font-size: 1.2rem; margin-bottom: 12px; }

        .btn-add-card {
            width: 100%; background: #222; border: 1px solid #333; color: #fff;
            padding: 10px; border-radius: 8px; font-weight: bold; font-size: 0.8rem;
            cursor: pointer; transition: 0.2s; text-transform: uppercase;
        }
        .btn-add-card:active { background: var(--gold); color: #000; border-color: var(--gold); }

        /* CARRITO FLOTANTE */
        .floating-cart {
            position: fixed; bottom: 25px; right: 20px;
            background: var(--gold); color: #000;
            padding: 12px 25px; border-radius: 50px;
            font-weight: 800; font-size: 1rem;
            box-shadow: 0 5px 25px rgba(255, 215, 0, 0.4);
            cursor: pointer; z-index: 2000; display: flex; align-items: center; gap: 10px;
            transition: transform 0.2s;
        }
        .floating-cart:active { transform: scale(0.9); }

        /* MODAL CARRITO */
        .cart-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.9); z-index: 3000;
            display: none; flex-direction: column;
        }
        .cart-content {
            background: #111; flex: 1; display: flex; flex-direction: column;
            margin-top: 40px; border-radius: 20px 20px 0 0; border-top: 2px solid var(--gold);
            animation: slideUp 0.3s;
        }
        @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }

        .cart-header { padding: 20px; border-bottom: 1px solid #222; display: flex; justify-content: space-between; align-items: center; }
        .cart-body { flex: 1; overflow-y: auto; padding: 20px; }
        .cart-footer { padding: 20px; background: #080808; border-top: 1px solid #222; }

        .cart-item { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px dashed #333; padding-bottom: 10px; }
        .qty-control { display: flex; gap: 10px; align-items: center; background: #222; padding: 5px 10px; border-radius: 20px; }

        /* Formulario */
        .form-control { width: 100%; padding: 14px; background: #1a1a1a; border: 1px solid #333; color: #fff; border-radius: 8px; margin-bottom: 12px; outline: none; }
        .btn-main { width: 100%; background: linear-gradient(45deg, var(--gold), #b7892b); color: #000; padding: 16px; border: none; border-radius: 10px; font-weight: bold; font-size: 1.1rem; text-transform: uppercase; cursor: pointer; }

        /* Campo Dirección Readonly */
        .input-dir-readonly { background: #0f2213; border: 1px dashed var(--gold); color: var(--gold); cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>

    <header class="royal-header">
        <a href="index.php" class="brand"><i class="fa-solid fa-wine-bottle"></i> ROYAL</a>
        
        <div class="header-actions">
            <?php if($id_usuario > 0): ?>
                
                <a href="mis_pedidos.php" class="btn-icon-header moto" title="Historial de Pedidos">
                    <i class="fa-solid fa-motorcycle"></i>
                </a>
                
                <a href="direcciones.php" class="btn-icon-header" title="Mis Direcciones">
                    <i class="fa-solid fa-map-location-dot"></i>
                </a>
                
                <a href="../logout.php" class="btn-icon-header" style="color:#ef5350; border-color:#552222;">
                    <i class="fa-solid fa-power-off"></i>
                </a>
            <?php else: ?>
                <a href="../index.php" class="btn-icon-header">
                    <i class="fa-regular fa-user"></i>
                </a>
            <?php endif; ?>
        </div>
    </header>

    <div class="hero-mini">
        <h2>CATÁLOGO DIGITAL</h2>
        <p>Selecciona tus favoritos</p>
    </div>

    <div class="container">
        <div class="search-container">
            <input type="text" id="buscador" class="search-input" placeholder="🔍 Buscar licor, cerveza...">
        </div>

        <div id="grid-productos" class="product-grid">
            <div style="grid-column:1/-1; text-align:center; padding:50px; color:#666;">
                <i class="fa-solid fa-circle-notch fa-spin fa-2x"></i>
            </div>
        </div>
    </div>

    <div class="floating-cart" onclick="toggleCart()">
        <i class="fa-solid fa-bag-shopping"></i> 
        <span id="float-total">S/ 0.00</span>
    </div>

    <div class="cart-overlay" id="cartModal">
        <div style="flex:1;" onclick="toggleCart()"></div>
        <div class="cart-content">
            <div class="cart-header">
                <h3 style="color:#fff; margin:0;">Tu Pedido</h3>
                <button onclick="toggleCart()" style="background:none; border:none; color:#fff; font-size:1.5rem;">&times;</button>
            </div>
            
            <div class="cart-body" id="cartItems"></div>

            <div class="cart-footer">
                <div style="display:flex; justify-content:space-between; font-size:1.2rem; font-weight:bold; margin-bottom:15px; color:#fff;">
                    <span>TOTAL</span>
                    <span style="color:var(--gold);" id="final-total">S/ 0.00</span>
                </div>

                <div id="checkout-box">
                    <label style="color:#888; font-size:0.8rem; display:block; margin-bottom:5px;">DIRECCIÓN (GPS)</label>
                    <div onclick="abrirAgenda()">
                        <input type="text" id="cli_dir" class="form-control input-dir-readonly" placeholder="Seleccionar Ubicación..." readonly>
                        <input type="hidden" id="cli_lat"><input type="hidden" id="cli_lon">
                    </div>

                    <input type="text" id="cli_nom" class="form-control" placeholder="Tu Nombre" value="<?= isset($_SESSION['nombre']) ? $_SESSION['nombre'] : '' ?>">
                    <input type="tel" id="cli_tel" class="form-control" placeholder="Teléfono">
                    <select id="cli_pago" class="form-control">
                        <option value="Yape">Yape / Plin</option>
                        <option value="Efectivo">Efectivo</option>
                        <option value="Tarjeta">Tarjeta</option>
                    </select>

                    <button onclick="enviar()" class="btn-main">CONFIRMAR PEDIDO</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let carrito = [];
        let productos = [];
        const userId = <?= $id_usuario ?>;
        
        // 1. Cargar Productos
        fetch('../api/productos.php?action=listar_publico')
            .then(r => r.json())
            .then(data => {
                productos = data;
                render(data);
            });

        function render(lista) {
            const grid = document.getElementById('grid-productos');
            grid.innerHTML = '';
            
            lista.forEach(p => {
                // Icono dinámico según categoría
                let icon = 'fa-wine-bottle';
                if(p.categoria && p.categoria.includes('Cerveza')) icon = 'fa-beer-mug-empty';
                if(p.categoria && p.categoria.includes('Whisky')) icon = 'fa-glass-water';
                
                grid.innerHTML += `
                <div class="prod-card">
                    <div class="prod-icon-area">
                        <i class="fa-solid ${icon}"></i>
                    </div>
                    
                    <div class="prod-cat">${p.categoria || 'Licor'}</div>
                    <div class="prod-title">${p.nombre}</div>
                    <div class="prod-price">S/ ${parseFloat(p.precio_venta).toFixed(2)}</div>
                    
                    <button class="btn-add-card" onclick="add(${p.id})">
                        AGREGAR <i class="fa-solid fa-plus"></i>
                    </button>
                </div>`;
            });
        }

        // Buscador
        document.getElementById('buscador').addEventListener('input', (e) => {
            const t = e.target.value.toLowerCase();
            render(productos.filter(p => p.nombre.toLowerCase().includes(t)));
        });

        // 2. Carrito
        function add(id) {
            const p = productos.find(x => x.id == id);
            const ex = carrito.find(x => x.id == id);
            if(ex) ex.cantidad++; else carrito.push({...p, cantidad:1});
            updCart();
            
            // Animación botón flotante
            const btn = document.querySelector('.floating-cart');
            btn.style.transform = 'scale(1.2)';
            setTimeout(()=>btn.style.transform='scale(1)', 200);
        }

        function updCart() {
            const div = document.getElementById('cartItems');
            div.innerHTML = '';
            let total = 0;

            carrito.forEach((p, i) => {
                total += p.precio_venta * p.cantidad;
                div.innerHTML += `
                <div class="cart-item">
                    <div>
                        <div style="color:#fff; font-size:0.9rem;">${p.nombre}</div>
                        <small style="color:#666;">S/ ${p.precio_venta} x ${p.cantidad}</small>
                    </div>
                    <div class="qty-control">
                        <i class="fa-solid fa-minus" onclick="mod(${i},-1)" style="color:#888; cursor:pointer;"></i>
                        <span style="color:#fff; font-weight:bold; min-width:20px; text-align:center;">${p.cantidad}</span>
                        <i class="fa-solid fa-plus" onclick="mod(${i},1)" style="color:#fff; cursor:pointer;"></i>
                    </div>
                </div>`;
            });

            const txt = 'S/ ' + total.toFixed(2);
            document.getElementById('float-total').innerText = txt;
            document.getElementById('final-total').innerText = txt;
        }

        function mod(i, d) {
            carrito[i].cantidad += d;
            if(carrito[i].cantidad <= 0) carrito.splice(i,1);
            updCart();
        }

        function toggleCart() {
            const m = document.getElementById('cartModal');
            m.style.display = (m.style.display === 'flex') ? 'none' : 'flex';
        }

        // 3. Direcciones GPS
        function abrirAgenda() {
            if(userId == 0) return Swal.fire('Inicia Sesión', 'Debes ingresar para usar el GPS.', 'info').then(()=>location.href='../index.php');

            Swal.fire({ title: 'Cargando...', didOpen: () => Swal.showLoading(), background:'#1a1a1a', color:'#fff' });
            
            // Fix Z-Index SweetAlert
            document.querySelector('.swal2-container').style.zIndex = '99999';

            fetch('../api/direcciones.php?action=listar')
                .then(r => r.json())
                .then(d => {
                    if (d.length === 0) {
                        return Swal.fire({
                            icon: 'info', title: 'Sin direcciones', text: 'Registra tu Casa o Trabajo.',
                            confirmButtonText: 'Nueva Dirección', confirmButtonColor: '#FFD700', background:'#1a1a1a', color:'#fff'
                        }).then((res) => { if(res.isConfirmed) location.href = 'direcciones.php'; });
                    }
                    
                    let html = '<div style="display:flex; flex-direction:column; gap:10px;">';
                    d.forEach(x => {
                        let icon = x.etiqueta == 'Casa' ? 'fa-house' : 'fa-map-pin';
                        html += `
                        <div onclick="usarDir('${x.direccion}', '${x.lat}', '${x.lon}')" 
                             style="background:#222; padding:12px; border-radius:10px; border:1px solid #333; cursor:pointer; text-align:left; display:flex; gap:12px; align-items:center;">
                            <i class="fa-solid ${icon}" style="color:#FFD700; font-size:1.2rem;"></i>
                            <div>
                                <div style="font-weight:bold; color:#fff;">${x.etiqueta}</div>
                                <div style="font-size:0.8rem; color:#aaa;">${x.direccion}</div>
                            </div>
                        </div>`;
                    });
                    html += '</div><br><a href="direcciones.php" style="color:#FFD700;">+ Agregar nueva</a>';

                    Swal.fire({ title: 'Selecciona Ubicación', html: html, showConfirmButton: false, showCloseButton: true, background:'#111', color:'#fff' });
                });
        }

        function usarDir(dir, lat, lon) {
            document.getElementById('cli_dir').value = dir;
            document.getElementById('cli_lat').value = lat;
            document.getElementById('cli_lon').value = lon;
            Swal.close();
        }

        // 4. Enviar
        function enviar() {
            const dir = document.getElementById('cli_dir').value;
            const nom = document.getElementById('cli_nom').value;
            const tel = document.getElementById('cli_tel').value;
            
            if(carrito.length===0) return Swal.fire('Carrito vacío','','warning');
            if(!dir) return Swal.fire('Falta Dirección','Selecciona una ubicación.','warning');
            if(!nom || !tel) return Swal.fire('Faltan Datos','Completa los campos.','warning');

            Swal.showLoading();
            const data = {
                productos: carrito.map(p=>({id:p.id, cantidad:p.cantidad, precio:p.precio_venta})),
                cliente: { nombre:nom, telefono:tel, direccion:dir, lat:document.getElementById('cli_lat').value, lon:document.getElementById('cli_lon').value },
                metodo_pago: document.getElementById('cli_pago').value,
                total: document.getElementById('final-total').innerText.replace('S/ ','')
            };

            fetch('../api/pedido.php', {
                method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data)
            }).then(r=>r.json()).then(res => {
                if(res.success) {
                    carrito=[]; updCart(); toggleCart();
                    Swal.fire({icon:'success', title:'¡Pedido Enviado!', text:'Driver notificado.', confirmButtonColor:'#FFD700', background:'#1a1a1a', color:'#fff'})
                    .then(() => location.href=`track.php?id=${res.id_pedido}`);
                } else Swal.fire('Error', res.message, 'error');
            });
        }
    </script>
</body>
</html>