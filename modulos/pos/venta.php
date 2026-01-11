<?php
require_once '../../config/db.php';
session_start();

// 1. Validar Sesión
if (!isset($_SESSION['user_id'])) { header("Location: ../../index.php"); exit; }

// 2. Verificar Caja Abierta
$id_usuario = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id FROM caja_sesiones WHERE id_usuario = ? AND estado = 'abierta'");
$stmt->execute([$id_usuario]);
$caja = $stmt->fetch();

if (!$caja) { header("Location: abrir_caja.php"); exit; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>POS Royal - Venta</title>
    <link rel="stylesheet" href="../../assets/css/estilos.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* =========================================
           CORRECCIÓN DE SCROLL Y LAYOUT
        ========================================= */
        * { box-sizing: border-box; }

        html, body {
            margin: 0; padding: 0;
            height: 100%; 
            height: 100dvh; /* Altura dinámica para móviles */
            width: 100%;
            overflow: hidden; /* Bloquea scroll general */
            background: #000;
            font-family: 'Poppins', sans-serif;
            overscroll-behavior: none; /* Evita rebote en iOS */
        }

        .pos-container { 
            display: flex; 
            height: 100%; 
            width: 100%; 
        }

        /* --- PANEL IZQUIERDO (PRODUCTOS) --- */
        .pos-left { 
            flex: 7; 
            background: #0a0a0a; 
            display: flex; 
            flex-direction: column; 
            padding: 15px; 
            border-right: 1px solid #222;
            height: 100%; 
            overflow: hidden; /* Contiene a los hijos */
        }

        .left-header { 
            flex: 0 0 auto; 
            margin-bottom: 10px; 
        }

        /* SCROLL HORIZONTAL CATEGORÍAS */
        .category-bar {
            display: flex; gap: 10px; 
            overflow-x: auto; 
            padding-bottom: 5px; margin-bottom: 10px; 
            flex: 0 0 auto;
            scrollbar-width: none; 
            -webkit-overflow-scrolling: touch;
        }
        .category-bar::-webkit-scrollbar { display: none; }

        .cat-btn {
            background: #1f1f1f; color: #888; border: 1px solid #333; padding: 8px 15px;
            border-radius: 20px; cursor: pointer; white-space: nowrap; font-weight: 600;
            transition: 0.2s; text-transform: uppercase; font-size: 0.8rem;
        }
        .cat-btn:hover, .cat-btn.active {
            background: var(--royal-gold); color: #000; border-color: var(--royal-gold);
            box-shadow: 0 0 10px rgba(255, 193, 7, 0.3);
        }

        /* ZONA DE LISTA DE PRODUCTOS (SCROLLABLE) */
        .product-list-container { 
            flex: 1; 
            overflow-y: auto; /* Permite deslizar verticalmente */
            padding-right: 5px; 
            min-height: 0; /* Fix para Flexbox */
            -webkit-overflow-scrolling: touch; /* Suavidad en iPhone */
            padding-bottom: 80px; /* Espacio final para no chocar con bordes */
        }
        .product-list-container::-webkit-scrollbar { width: 6px; }
        .product-list-container::-webkit-scrollbar-thumb { background: #333; border-radius: 3px; }

        .product-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); 
            gap: 10px; 
        }

        .product-card { 
            background: #151515; padding: 10px; border-radius: 10px; border: 1px solid #2a2a2a; 
            cursor: pointer; text-align: center; position: relative; transition: transform 0.1s;
            display: flex; flex-direction: column; justify-content: space-between; height: 100%; min-height: 110px;
        }
        .product-card:active { transform: scale(0.96); border-color: var(--royal-gold); }
        .product-card.agotado { cursor: not-allowed; border-color: #555; background: #0f0f0f; opacity: 0.5; }

        .combo-tag { position: absolute; top: 5px; right: 5px; color: #FFD700; font-size: 0.8rem; }
        .cat-badge { font-size: 0.65rem; color: #666; display: block; margin-bottom: 2px; text-transform: uppercase; letter-spacing: 1px; }

        /* --- PANEL DERECHO (CARRITO) --- */
        .pos-right { 
            flex: 3; 
            background: #111; 
            display: flex; 
            flex-direction: column; 
            height: 100%; 
            border-left: 1px solid #333; 
            z-index: 50;
        }

        .ticket-header { flex: 0 0 auto; padding: 15px; background: #000; border-bottom: 1px solid #222; text-align: center; }
        
        .ticket-body { 
            flex: 1; 
            overflow-y: auto; 
            padding: 10px; 
            min-height: 0;
            -webkit-overflow-scrolling: touch;
        }
        .ticket-body::-webkit-scrollbar { width: 5px; }
        .ticket-body::-webkit-scrollbar-thumb { background: #333; }
        
        .ticket-footer { flex: 0 0 auto; padding: 20px; background: #0d0d0d; border-top: 1px solid #222; }

        .ticket-item { 
            background: #1a1a1a; margin-bottom: 8px; padding: 10px; border-radius: 8px; border-left: 3px solid var(--royal-gold);
            display: flex; justify-content: space-between; align-items: flex-start; 
        }

        .cart-controls { display: flex; gap: 5px; margin-top: 8px; align-items: center; }
        .cart-qty-input {
            width: 50px; background: #222; border: 1px solid #444; color: #fff;
            text-align: center; padding: 5px; border-radius: 4px; font-weight: bold; font-size: 1rem;
        }
        .cart-mode-select {
            background: #222; border: 1px solid #444; color: var(--royal-gold);
            padding: 5px; border-radius: 4px; font-size: 0.85rem; width: auto; font-weight: bold;
        }
        .cart-mode-select:focus, .cart-qty-input:focus { outline: none; border-color: var(--royal-gold); }

        .mobile-cart-btn { display: none; }

        /* BOTÓN DELIVERY */
        #btn-delivery-notify {
            background: #333; color: #888; border: 1px solid #555; 
            padding: 8px 15px; border-radius: 5px; text-decoration: none; 
            font-size: 0.9rem; display: flex; align-items: center; gap: 8px;
            transition: 0.3s; margin-right: 10px;
        }
        #btn-delivery-notify.activo {
            background: #ef5350; color: #fff; border-color: #ef5350;
            animation: latido 0.8s infinite; 
        }
        @keyframes latido { 0% { transform: scale(1); } 50% { transform: scale(1.1); box-shadow: 0 0 15px #ef5350; } 100% { transform: scale(1); } }

        /* ESTILOS DE IMPRESIÓN */
        #ticket-impresion { display: none; }
        @media print {
            body * { visibility: hidden; height: 0; }
            #ticket-impresion, #ticket-impresion * { visibility: visible; height: auto; overflow: visible; }
            @page { margin: 0; size: auto; }
            html, body { margin: 0; padding: 0; background: #fff; height: auto; overflow: visible; }

            #ticket-impresion {
                display: block !important; position: absolute; top: 0; left: 0; width: 60mm; margin-left: 2mm;
                font-family: 'Courier New', Courier, monospace; font-size: 11px; font-weight: bold; color: #000 !important;
                text-transform: uppercase; line-height: 1.2;
            }
            .t-center { text-align: center; }
            .t-right { text-align: right; }
            .t-line { border-bottom: 1px dashed #000; margin: 5px 0; display: block; }
            .t-row { display: flex; justify-content: space-between; white-space: nowrap; }
            .col-prod { flex: 1; overflow: hidden; text-overflow: clip; padding-right: 5px; }
            .col-cant { flex: 0 0 20px; text-align: center; }
            .col-total { flex: 0 0 50px; text-align: right; }
        }

        /* RESPONSIVE (MÓVIL) */
        @media (max-width: 768px) {
            .pos-container { flex-direction: column; }
            
            /* Ajuste para que la lista de productos ocupe todo y haga scroll */
            .pos-left { 
                flex: 1; 
                width: 100%; 
                padding-bottom: 0; /* Quitamos padding excesivo */
                overflow: hidden; /* Importante para que el hijo haga scroll */
            }
            
            .product-list-container {
                padding-bottom: 100px; /* Espacio para que el último producto se vea encima del botón flotante */
            }

            .pos-right { 
                position: fixed; bottom: 0; left: 0; width: 100%; height: auto; 
                max-height: 85dvh; border-top: 2px solid var(--royal-gold); 
                transform: translateY(100%); transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
                z-index: 2000;
                box-shadow: 0 -5px 20px rgba(0,0,0,0.8);
            }
            .pos-right.open { transform: translateY(0); }
            
            .mobile-cart-btn {
                display: flex; position: absolute; top: -50px; left: 0; width: 100%; height: 50px;
                background: var(--royal-gold); color: #000; align-items: center; justify-content: space-between;
                padding: 0 20px; font-weight: bold; cursor: pointer;
                box-shadow: 0 -2px 10px rgba(0,0,0,0.5);
            }
        }
    </style>
</head>
<body>

<div id="ticket-impresion">
    <br>
    <div class="t-center">
        <div style="font-size: 14px;">ROYAL LICORERIA</div>
        <div style="font-size: 10px;">Huacho - Lima</div>
        <div>--------------------------</div>
    </div>
    <div>FEC: <?= date('d/m/Y H:i') ?></div>
    <div>TKT: <span id="print-id-venta">---</span></div>
    <div>CAJ: <?= isset($_SESSION['nombre']) ? substr($_SESSION['nombre'], 0, 15) : 'Cajero' ?></div>
    <div class="t-line"></div>
    <div class="t-row"><div class="col-prod">DESC</div><div class="col-cant">C</div><div class="col-total">TOT</div></div>
    <div class="t-line"></div>
    <div id="print-items"></div>
    <div class="t-line"></div>
    <div class="t-row" style="font-size: 14px;"><div style="flex:1">TOTAL:</div><div class="t-right" id="print-total">S/ 0.00</div></div>
    <div class="t-center" id="print-metodo" style="margin-top:5px;"></div>
    <br><div class="t-center">GRACIAS POR SU COMPRA</div><br><br>.
</div>

<div class="pos-container">
    
    <div class="pos-left">
        <div class="left-header">
            <div style="display:flex; justify-content:space-between; margin-bottom:10px; align-items:center;">
                <h3 style="color:var(--royal-gold); margin:0; font-size:1.2rem;">ROYAL POS</h3>
                
                <div style="display:flex; align-items:center;">
                    <a href="cerrar_caja.php" class="btn-royal" style="background:#ef5350; color:#fff; padding:4px 10px; border-radius:5px; text-decoration:none; margin-right:10px; font-size:0.9rem;">
                        <i class="fa-solid fa-calculator"></i> Cerrar
                    </a>

                    <a href="delivery.php" id="btn-delivery-notify">
                        <i class="fa-solid fa-motorcycle"></i> <span id="delivery-count">0</span>
                    </a>

                    <a href="../../logout.php" style="color:#ef5350; text-decoration:none; font-size:0.9rem; border:1px solid #ef5350; padding:4px 10px; border-radius:5px;">
                        <i class="fa-solid fa-power-off"></i>
                    </a>
                </div>
            </div>
            
            <div style="display:flex; gap:10px; margin-bottom:10px;">
                <input type="text" id="buscador" placeholder="🔍 Buscar producto..." autocomplete="off" 
                       style="flex:1; padding:12px; background:#1a1a1a; border:1px solid #333; color:#fff; border-radius:30px; margin-bottom:0; outline:none;">
                <button onclick="startScanner()" class="btn-royal" style="width:50px; padding:0; border-radius:50%; min-width:unset;"><i class="fa-solid fa-camera"></i></button>
            </div>

            <div class="category-bar" id="cat-container">
                <button class="cat-btn active" onclick="filtrarCategoria('todas', this)">TODOS</button>
            </div>
        </div>

        <div class="product-list-container">
            <div id="resultados" class="product-grid">
                <p style="color:#666; text-align:center;">Cargando inventario...</p>
            </div>
        </div>
    </div>

    <div class="pos-right" id="cartPanel">
        <div class="mobile-cart-btn" onclick="toggleCart()">
            <span><i class="fa-solid fa-chevron-up"></i> Ver Cuenta</span>
            <span id="mobile-total">S/ 0.00</span>
        </div>
        <div class="ticket-header"><h4 style="color:#fff; margin:0;">Orden en Curso</h4></div>
        <div class="ticket-body" id="carrito-lista"></div>
        <div class="ticket-footer">
            <div style="display:flex; justify-content:space-between; font-size:1.5rem; color:#fff; margin-bottom:15px;">
                <span>Total</span>
                <span id="total-amount" style="color:var(--royal-gold); font-weight:bold;">S/ 0.00</span>
            </div>
            <button class="btn-royal btn-block" onclick="cobrar()" style="padding:15px; font-size:1.2rem; border-radius:10px; width:100%;">
                COBRAR <i class="fa-solid fa-money-bill"></i>
            </button>
        </div>
    </div>
</div>

<div id="payment-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.95); z-index:3000; align-items:center; justify-content:center; flex-direction:column;">
    <h2 style="color:#fff; margin-bottom:20px;">Método de Pago</h2>
    <div style="font-size:2.5rem; color:var(--royal-gold); font-weight:bold; margin-bottom:30px;" id="modal-total-amount">S/ 0.00</div>
    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:15px; width:90%; max-width:600px;">
        <button onclick="procesarPago('Efectivo')" style="padding:25px; border:none; border-radius:15px; background:#2e7d32; color:white; font-size:1rem; cursor:pointer; display:flex; flex-direction:column; align-items:center; gap:10px;">
            <i class="fa-solid fa-money-bill-wave" style="font-size:2rem;"></i> EFECTIVO
        </button>
        <button onclick="procesarPago('Yape')" style="padding:25px; border:none; border-radius:15px; background:#6a1b9a; color:white; font-size:1rem; cursor:pointer; display:flex; flex-direction:column; align-items:center; gap:10px;">
            <i class="fa-solid fa-qrcode" style="font-size:2rem;"></i> YAPE/PLIN
        </button>
        <button onclick="procesarPago('Transferencia')" style="padding:25px; border:none; border-radius:15px; background:#1565c0; color:white; font-size:1rem; cursor:pointer; display:flex; flex-direction:column; align-items:center; gap:10px;">
            <i class="fa-solid fa-building-columns" style="font-size:2rem;"></i> BANCO
        </button>
    </div>
    <button onclick="document.getElementById('payment-modal').style.display='none'" style="margin-top:30px; background:transparent; border:1px solid #555; color:#888; padding:10px 40px; border-radius:20px; cursor:pointer;">Cancelar</button>
</div>

<div id="scanner-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.95); z-index:4000; align-items:center; justify-content:center; flex-direction:column;">
    <div id="reader" style="width:100%; max-width:400px; border:2px solid var(--royal-gold);"></div>
    <button onclick="stopScanner()" style="margin-top:20px; padding:10px 30px; background:#ef5350; border:none; color:white; border-radius:20px;">Cerrar Cámara</button>
</div>

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

<script>
    // --- LÓGICA DE NOTIFICACIÓN DELIVERY ---
    let lastCount = 0;
    let audioAlerta = new Audio('../../assets/notification.mp3');

    function checkPedidos() {
        fetch('../../api/check_pedidos.php?t=' + Date.now())
            .then(r => r.json())
            .then(data => {
                const btn = document.getElementById('btn-delivery-notify');
                const span = document.getElementById('delivery-count');
                const count = parseInt(data.pendientes);

                if(span) span.innerText = count;

                if (count > 0) {
                    btn.classList.add('activo');
                    btn.innerHTML = `<i class="fa-solid fa-motorcycle"></i> ${count} PEDIDOS`;
                    if (count > lastCount) {
                        dispararAlerta();
                    }
                } else {
                    btn.classList.remove('activo');
                    btn.innerHTML = `<i class="fa-solid fa-motorcycle"></i> 0`;
                }
                lastCount = count;
            })
            .catch(() => {});
    }

    function dispararAlerta() {
        audioAlerta.play().catch(() => {});
        if ('speechSynthesis' in window) {
            let msg = new SpeechSynthesisUtterance("Nuevo pedido delivery.");
            window.speechSynthesis.speak(msg);
        }
        Swal.fire({
            icon: 'warning', title: '¡NUEVO PEDIDO!', toast: true, position: 'top-end',
            showConfirmButton: true, confirmButtonText: 'VER', confirmButtonColor: '#FFD700',
            background: '#1a1a1a', color: '#fff', timer: 10000,
            didOpen: (toast) => { toast.addEventListener('click', () => { window.location.href = 'delivery.php'; }); }
        });
    }

    setInterval(checkPedidos, 4000);
    setTimeout(checkPedidos, 1000);
    
    // --- POS LOGIC ---
    let carrito = [];
    let productos = [];
    let categorias = new Set();
    let scanner;

    fetch('backend_venta.php?action=listar')
        .then(res => res.json())
        .then(data => {
            productos = data;
            data.forEach(p => { if(p.categoria) categorias.add(p.categoria); });
            const catDiv = document.getElementById('cat-container');
            categorias.forEach(cat => {
                const btn = document.createElement('button');
                btn.className = 'cat-btn';
                btn.innerText = cat;
                btn.onclick = function() { filtrarCategoria(cat, this); };
                catDiv.appendChild(btn);
            });
            renderProductos(data);
        });

    function filtrarCategoria(cat, btn) {
        document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const lista = (cat === 'todas') ? productos : productos.filter(p => p.categoria === cat);
        renderProductos(lista);
    }

    function renderProductos(lista) {
        const div = document.getElementById('resultados');
        div.innerHTML = '';
        
        if(lista.length === 0) {
            div.innerHTML = '<p style="color:#666; grid-column:1/-1; text-align:center;">Sin resultados.</p>';
            return;
        }

        lista.forEach(p => {
            let sinStock = (p.stock_actual <= 0);
            let claseExtra = sinStock ? 'agotado' : '';
            let packIcon = (p.es_combo == 1) ? '<i class="fa-solid fa-gift combo-tag"></i>' : '';
            let catLabel = p.categoria ? `<span class="cat-badge">${p.categoria}</span>` : '';
            let stockHtml = sinStock ? `<small style="color:#ef5350;">AGOTADO</small>` : `<small style="color:#66bb6a;">${p.stock_actual} un.</small>`;
            
            div.innerHTML += `
            <div class="product-card ${claseExtra}" onclick="agregar(${p.id})">
                ${packIcon} ${catLabel}
                <div style="font-size:0.85rem; color:#fff; font-weight:600; line-height:1.2; overflow:hidden;">${p.nombre}</div>
                <div style="margin-top:auto;">
                    <div style="color:var(--royal-gold); font-weight:bold;">S/ ${parseFloat(p.precio_venta).toFixed(2)}</div>
                    ${stockHtml}
                </div>
            </div>`;
        });
    }

    function agregar(id) {
        const p = productos.find(x => x.id == id);
        if(p.stock_actual <= 0) return Swal.fire({icon: 'error', title: 'Agotado', toast:true, position:'top-end', showConfirmButton:false, timer:1500, background:'#222', color:'#fff'});
        
        const item = carrito.find(x => x.id == id);
        if(item) validarYActualizarCantidad(item, item.cant + 1);
        else {
            carrito.push({
                id: p.id, nombre: p.nombre, precio_unitario: parseFloat(p.precio_venta),
                precio_caja: parseFloat(p.precio_caja || 0), unidades_caja: parseInt(p.unidades_caja || 1),
                precio_aplicado: parseFloat(p.precio_venta), cant: 1, modo: 'unidad',
                es_combo: p.es_combo, stock_max: p.stock_actual
            });
        }
        actualizarCarrito();
    }

    function validarYActualizarCantidad(item, nuevaCant) {
        let descuentoTotal = nuevaCant * (item.modo === 'caja' ? item.unidades_caja : 1);
        if (descuentoTotal > item.stock_max && item.es_combo == 0) {
            Swal.fire({icon:'warning', title:'Stock', text:`Max: ${item.stock_max}`, toast:true, position:'center', showConfirmButton:false, timer:2000, background:'#222', color:'#fff'});
            return;
        }
        item.cant = nuevaCant;
    }

    function actualizarCarrito() {
        const div = document.getElementById('carrito-lista');
        div.innerHTML = '';
        let total = 0;
        
        carrito.slice().reverse().forEach((p, idx) => {
            const realIdx = carrito.length - 1 - idx;
            const subtotal = p.precio_aplicado * p.cant;
            total += subtotal;

            let selector = '';
            if (p.unidades_caja > 1 && p.es_combo == 0) {
                selector = `<select class="cart-mode-select" onchange="cambiarModo(${realIdx}, this.value)">
                    <option value="unidad" ${p.modo==='unidad'?'selected':''}>Unid.</option>
                    <option value="caja" ${p.modo==='caja'?'selected':''}>Caja x${p.unidades_caja}</option>
                </select>`;
            }

            div.innerHTML += `
            <div class="ticket-item">
                <div style="flex:1;">
                    <div style="color:#fff; font-size:0.9rem; margin-bottom:4px;">${p.nombre}</div>
                    <div class="cart-controls">
                        <input type="number" class="cart-qty-input" value="${p.cant}" min="1" onchange="cambiarCantidad(${realIdx}, this.value)">
                        ${selector}
                    </div>
                </div>
                <div style="text-align:right;">
                    <div style="color:var(--royal-gold); font-weight:bold;">S/ ${subtotal.toFixed(2)}</div>
                    <i class="fa-solid fa-trash" onclick="eliminar(${realIdx})" style="color:#ef5350; cursor:pointer; margin-top:8px;"></i>
                </div>
            </div>`;
        });
        
        const txt = 'S/ ' + total.toFixed(2);
        document.getElementById('total-amount').innerText = txt;
        document.getElementById('mobile-total').innerText = txt;
        document.getElementById('modal-total-amount').innerText = txt;
    }

    function cambiarCantidad(idx, val) {
        const nueva = parseFloat(val);
        if (nueva > 0) validarYActualizarCantidad(carrito[idx], nueva);
        actualizarCarrito(); 
    }

    function cambiarModo(idx, modo) {
        const item = carrito[idx];
        item.modo = modo;
        item.precio_aplicado = (modo === 'caja' && item.precio_caja > 0) ? item.precio_caja : (modo === 'caja' ? item.precio_unitario * item.unidades_caja : item.precio_unitario);
        validarYActualizarCantidad(item, item.cant);
        actualizarCarrito();
    }

    function eliminar(idx) { carrito.splice(idx, 1); actualizarCarrito(); }
    function toggleCart() { document.getElementById('cartPanel').classList.toggle('open'); }

    function cobrar() {
        if(carrito.length === 0) return Swal.fire({title:'Carrito vacío', icon:'warning', toast:true, timer:1500, showConfirmButton:false, background:'#222', color:'#fff'});
        document.getElementById('payment-modal').style.display = 'flex';
    }

    function procesarPago(metodo) {
        document.getElementById('payment-modal').innerHTML = '<h2 style="color:#fff;">Procesando...</h2>';
        let totalStr = document.getElementById('total-amount').innerText.replace('S/', '').trim(); 

        fetch('backend_venta.php?action=procesar', {
            method: 'POST', 
            body: JSON.stringify({ 
                items: carrito.map(i => ({ id: i.id, cantidad: i.cant, precio: i.precio_aplicado, es_combo: i.es_combo, modo: i.modo })), 
                total: totalStr, metodo_pago: metodo
            })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                document.getElementById('print-id-venta').innerText = data.id_venta;
                document.getElementById('print-metodo').innerText = "PAGO: " + metodo.toUpperCase();
                document.getElementById('print-total').innerText = 'S/ ' + totalStr;
                let html = '';
                carrito.forEach(i => { html += `<div class="t-row"><div class="col-prod">${i.nombre}</div><div class="col-cant">${i.cant}</div><div class="col-total">${(i.precio_aplicado*i.cant).toFixed(2)}</div></div>`; });
                document.getElementById('print-items').innerHTML = html;
                window.print();
                location.reload();
            } else { 
                alert(data.message); location.reload(); 
            }
        })
        .catch(() => { alert('Error de red'); location.reload(); });
    }

    document.getElementById('buscador').addEventListener('keyup', (e) => {
        const t = e.target.value.toLowerCase();
        if(e.key === 'Enter') {
            const ex = productos.find(p => p.codigo_barras === t);
            if(ex) { agregar(ex.id); e.target.value=''; return; }
        }
        renderProductos(productos.filter(p => p.nombre.toLowerCase().includes(t) || (p.codigo_barras && p.codigo_barras.includes(t))));
    });

    function startScanner() {
        document.getElementById('scanner-modal').style.display = 'flex';
        scanner = new Html5Qrcode("reader");
        scanner.start({ facingMode: "environment" }, { fps: 10 }, (txt) => {
            const p = productos.find(x => x.codigo_barras === txt);
            if(p) { agregar(p.id); stopScanner(); } else { alert('No encontrado'); stopScanner(); }
        });
    }
    function stopScanner() { if(scanner) scanner.stop().then(()=>document.getElementById('scanner-modal').style.display='none'); }
</script>
</body>
</html>