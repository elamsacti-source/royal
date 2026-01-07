<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Punto de Venta - SUARCORP</title>
    <style>
        .pos-container { display: flex; gap: 20px; }
        .product-list { width: 60%; }
        .cart { width: 40%; background: #f4f4f4; padding: 20px; }
        .row-producto { border-bottom: 1px solid #ddd; padding: 10px; cursor: pointer; }
        .row-producto:hover { background: #e9e9e9; }
    </style>
</head>
<body>
    <h1>Caja 01 - Venta</h1>
    <div class="pos-container">
        <div class="product-list">
            <input type="text" id="buscador" placeholder="Buscar producto (Enter)..." style="width: 100%; padding: 10px;">
            <div id="resultados"></div>
        </div>

        <div class="cart">
            <h3>Ticket Actual</h3>
            <table width="100%">
                <thead><tr><th>Prod</th><th>Cant/Peso</th><th>Total</th><th>X</th></tr></thead>
                <tbody id="tabla-carrito"></tbody>
            </table>
            <h2>Total: S/ <span id="gran-total">0.00</span></h2>
            <button onclick="procesarVenta()" style="font-size: 20px; width: 100%;">COBRAR</button>
        </div>
    </div>

<script>
    let carrito = [];

    // 1. BUSCADOR
    document.getElementById('buscador').addEventListener('keyup', async (e) => {
        if (e.key === 'Enter') {
            const res = await fetch(`../api/buscar_producto.php?q=${e.target.value}`);
            const productos = await res.json();
            renderResultados(productos);
        }
    });

    function renderResultados(productos) {
        const div = document.getElementById('resultados');
        div.innerHTML = '';
        productos.forEach(p => {
            // Lógica inteligente: Si es granel, pide monto en soles, si es unidad, agrega 1
            div.innerHTML += `
                <div class="row-producto" onclick="agregarAlCarrito(${p.id}, '${p.nombre}', ${p.precio}, ${p.es_granel})">
                    <b>${p.nombre}</b> - S/ ${p.precio} ${p.es_granel == 1 ? '(x KG)' : '(Unid)'}
                    <br><small>Stock: ${p.stock}</small>
                </div>
            `;
        });
    }

    // 2. AGREGAR CON LÓGICA DE GRANEL
    function agregarAlCarrito(id, nombre, precio, esGranel) {
        let cantidad = 0;
        let subtotal = 0;

        if (esGranel == 1) {
            // CASO ARROZ: Preguntar cuánto dinero quiere gastar el cliente
            let dinero = prompt(`¿Cuánto dinero de ${nombre} desea llevar?`);
            if (!dinero) return;
            
            subtotal = parseFloat(dinero);
            cantidad = subtotal / precio; // Calculadora Inversa: Dinero / Precio Kilo
        } else {
            // CASO GASEOSA
            cantidad = 1;
            subtotal = precio;
        }

        carrito.push({ id, nombre, precio, cantidad, subtotal });
        renderCarrito();
    }

    function renderCarrito() {
        const tbody = document.getElementById('tabla-carrito');
        tbody.innerHTML = '';
        let total = 0;
        carrito.forEach((item, index) => {
            total += item.subtotal;
            tbody.innerHTML += `
                <tr>
                    <td>${item.nombre}</td>
                    <td>${item.cantidad.toFixed(3)}</td>
                    <td>${item.subtotal.toFixed(2)}</td>
                    <td><button onclick="eliminar(${index})">X</button></td>
                </tr>
            `;
        });
        document.getElementById('gran-total').innerText = total.toFixed(2);
    }

    function eliminar(index) {
        carrito.splice(index, 1);
        renderCarrito();
    }

    // 3. PROCESAR VENTA (API)
    async function procesarVenta() {
        if (carrito.length === 0) return alert("Carrito vacío");

        const data = {
            usuario_id: 1, // En un sistema real, esto viene de la sesión PHP
            items: carrito
        };

        const res = await fetch('../api/guardar_venta.php', {
            method: 'POST',
            body: JSON.stringify(data)
        });

        const result = await res.json();
        if (result.success) {
            alert("Venta realizada con éxito");
            carrito = [];
            renderCarrito();
            document.getElementById('buscador').value = '';
            document.getElementById('resultados').innerHTML = '';
        } else {
            alert("Error: " + result.message);
        }
    }
</script>
</body>
</html>