<?php
session_start();
require_once '../../config/db.php';

// Seguridad
if (!isset($_SESSION['user_id'])) { header("Location: ../../index.php"); exit; }

// PROCESAR ACCIONES (Confirmar / Rechazar)
if (isset($_POST['accion'])) {
    $id_venta = $_POST['id_venta'];
    
    if ($_POST['accion'] == 'confirmar') {
        $stmt = $pdo->prepare("UPDATE ventas SET estado_delivery = 'confirmado' WHERE id = ? AND estado_delivery = 'pendiente'");
        $stmt->execute([$id_venta]);
    }
    
    if ($_POST['accion'] == 'cancelar') {
        $pdo->prepare("UPDATE ventas SET estado_delivery = 'cancelado' WHERE id = ?")->execute([$id_venta]);
        // Devolver Stock
        $items = $pdo->query("SELECT id_producto, cantidad FROM ventas_detalle WHERE id_venta = $id_venta")->fetchAll();
        $id_sede = 2; // Sede Tienda
        foreach($items as $i) {
            $pdo->prepare("UPDATE productos_sedes SET stock = stock + ? WHERE id_producto = ? AND id_sede = ?")
                ->execute([$i['cantidad'], $i['id_producto'], $id_sede]);
            
            $pdo->prepare("INSERT INTO kardex (id_producto, id_sede, tipo_movimiento, cantidad, stock_resultante, nota, fecha) VALUES (?, ?, 'entrada_devolucion', ?, 0, ?, NOW())")
                ->execute([$i['id_producto'], $id_sede, $i['cantidad'], "Rechazo Pedido #$id_venta"]);
        }
    }
    // Recarga limpia
    header("Location: delivery.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Gestión Delivery</title>
    <link rel="stylesheet" href="../../assets/css/estilos.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* RESET TOTAL PARA UNA SOLA COLUMNA */
        html, body {
            background: #000; 
            margin: 0; padding: 0;
            width: 100%; height: 100%;
            font-family: 'Poppins', sans-serif; 
            -webkit-tap-highlight-color: transparent;
            display: block !important; /* IMPORTANTE: Rompe el flex del admin */
            overflow-x: hidden;
        }

        /* HEADER FIJO */
        .header-top { 
            position: sticky; top: 0; z-index: 1000;
            background: rgba(10,10,10,0.98); 
            border-bottom: 1px solid #333; 
            padding: 15px; 
            display:flex; justify-content:space-between; align-items:center; 
            width: 100%; box-sizing: border-box;
        }
        
        .page-title { color: #fff; margin: 0; font-size: 1.2rem; text-transform: uppercase; font-weight: 800; letter-spacing: 1px; }
        .live-badge { background: #ef5350; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; vertical-align: middle; animation: pulse 2s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }

        /* CONTENEDOR DE LISTA - FORZADO A COLUMNA */
        .lista-pedidos {
            display: flex; 
            flex-direction: column !important; /* Fuerza vertical */
            width: 100%;
            max-width: 800px; /* Ancho máximo para que no se estire demasiado en PC */
            margin: 0 auto;   /* Centrado en PC */
            padding: 15px;
            box-sizing: border-box;
            gap: 15px;
            padding-bottom: 100px;
        }
        
        /* TARJETA - FORZADA AL 100% DEL ANCHO DISPONIBLE */
        .card-pedido { 
            background: #111; 
            border: 1px solid #333; 
            padding: 20px; 
            border-radius: 12px; 
            position: relative; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            width: 100%; /* Ocupa todo el ancho */
            box-sizing: border-box;
            display: block; /* Asegura bloque */
        }
        
        /* Bordes de Estado */
        .pendiente { border-left: 5px solid #ef5350; background: linear-gradient(90deg, #1a0505 0%, #111 100%); }
        .confirmado { border-left: 5px solid #FFD700; }
        .en_camino { border-left: 5px solid #66bb6a; background: linear-gradient(90deg, #051a05 0%, #111 100%); }

        /* Botones */
        .btn-accion { 
            flex: 1; padding: 15px; border: none; font-weight: 800; 
            cursor: pointer; border-radius: 8px; color: #fff; font-size: 1rem; 
            display: flex; justify-content: center; align-items: center; gap: 8px;
            text-transform: uppercase;
        }
        .btn-accept { background: #66bb6a; color: #000; }
        .btn-cancel { background: #2a0a0a; color: #ef5350; border: 1px solid #ef5350; }
        
        .btn-refresh { 
            background: #222; color: #FFD700; border: 1px solid #FFD700; 
            padding: 8px 15px; border-radius: 50px; font-weight: bold; 
            cursor: pointer; text-decoration: none; font-size: 0.8rem; 
            display: flex; align-items: center; gap: 5px;
        }

        .gps-btn {
            display: block; width: 100%; text-align: center;
            background: #222; border: 1px solid #444; color: #4fc3f7;
            padding: 12px; border-radius: 8px; margin-top: 10px;
            text-decoration: none; font-weight: 600;
        }
        
        /* Detalles */
        .info-row { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 8px; color: #ccc; font-size: 0.95rem; }
        .info-icon { width: 20px; text-align: center; color: #666; margin-top: 3px; }
        
        .product-list { background: #000; padding: 12px; border-radius: 8px; margin: 15px 0; border: 1px solid #222; font-size: 0.9rem; color: #aaa; }
        .product-item { border-bottom: 1px dashed #333; padding-bottom: 5px; margin-bottom: 5px; display: flex; justify-content: space-between; }
        .product-item:last-child { border: none; margin: 0; padding: 0; }
        .product-qty { color: #fff; font-weight: bold; margin-right: 10px; }
    </style>
</head>
<body>

    <div class="header-top">
        <div>
            <h2 class="page-title"><i class="fa-solid fa-motorcycle"></i> Pedidos <span class="live-badge">EN VIVO</span></h2>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="venta.php" class="btn-refresh" style="border-color:#444; color:#fff;">POS</a>
        </div>
    </div>

    <div class="lista-pedidos" id="contenedor-pedidos">
        <p style="text-align:center; color:#666; padding:30px;">Cargando pedidos...</p>
    </div>

    <form id="form-accion" method="POST" style="display:none;">
        <input type="hidden" name="id_venta" id="input_id">
        <input type="hidden" name="accion" id="input_accion">
    </form>

    <audio id="audio-alerta" src="../../assets/notification.mp3"></audio>

    <script>
        let ultimoPedidoId = 0;

        function cargarPedidos() {
            // Cache Buster
            fetch('ajax_pedidos.php?t=' + Date.now())
                .then(response => response.text())
                .then(html => {
                    document.getElementById('contenedor-pedidos').innerHTML = html;
                    
                    // Detectar nuevos
                    const primerPendiente = document.querySelector('.card-pedido.pendiente');
                    if(primerPendiente) {
                        const idActual = parseInt(primerPendiente.getAttribute('data-id'));
                        if(idActual > ultimoPedidoId) {
                            if(ultimoPedidoId !== 0) notificarNuevoPedido(idActual);
                            ultimoPedidoId = idActual;
                        }
                    }
                })
                .catch(err => console.error('Error cargando:', err));
        }

        function notificarNuevoPedido(id) {
            // Sonido
            const audio = document.getElementById('audio-alerta');
            audio.play().catch(e => {});

            // Voz (TTS)
            if ('speechSynthesis' in window) {
                let msg = new SpeechSynthesisUtterance("¡Atención! Nuevo pedido de delivery.");
                window.speechSynthesis.speak(msg);
            }

            // Alerta Visual
            Swal.fire({
                title: '¡NUEVO PEDIDO!',
                text: 'Orden #' + id + ' recibida.',
                icon: 'success',
                confirmButtonText: 'VER AHORA',
                background: '#111', color: '#fff',
                confirmButtonColor: '#66bb6a',
                toast: true, position: 'top-end',
                timer: 8000, timerProgressBar: true
            });
            
            if(navigator.vibrate) navigator.vibrate([200, 100, 200]);
        }

        function procesarAccion(id, accion) {
            if(accion === 'cancelar') {
                Swal.fire({
                    title: '¿Rechazar Pedido?',
                    text: "Se devolverá el stock al inventario.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33', cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, rechazar',
                    background: '#1a1a1a', color: '#fff'
                }).then((result) => {
                    if (result.isConfirmed) enviarFormulario(id, accion);
                })
            } else {
                enviarFormulario(id, accion);
            }
        }

        function enviarFormulario(id, accion) {
            document.getElementById('input_id').value = id;
            document.getElementById('input_accion').value = accion;
            document.getElementById('form-accion').submit();
        }

        setInterval(cargarPedidos, 4000);
        setTimeout(cargarPedidos, 500); // Carga rápida al inicio
    </script>
</body>
</html>