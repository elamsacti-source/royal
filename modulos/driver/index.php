<?php
session_start();
require_once '../../config/db.php';

// --- PROTECCIÓN DE SESIÓN ---
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'driver') {
    header("Location: ../../index.php");
    exit;
}

$id_user = $_SESSION['user_id'];

// 1. DATOS DRIVER
$stmtDriver = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmtDriver->execute([$id_user]);
$driver = $stmtDriver->fetch();
if (!$driver) { session_destroy(); header("Location: ../../index.php"); exit; }

// 2. ACCIONES
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $id_venta = $_GET['id'];
    
    if ($_GET['action'] == 'tomar') {
        $sql = "UPDATE ventas SET id_driver = ?, estado_delivery = 'en_camino' WHERE id = ? AND estado_delivery = 'pendiente'";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$id_user, $id_venta])) {
            if ($stmt->rowCount() > 0) echo json_encode(['success'=>true, 'message'=>'✅ Pedido asignado.']);
            else echo json_encode(['success'=>false, 'message'=>'❌ Ya fue tomado.']);
        } else {
            echo json_encode(['success'=>false, 'message'=>'Error BD.']);
        }
        exit;
    }

    if ($_GET['action'] == 'finalizar') {
        $sql = "UPDATE ventas SET estado_delivery = 'entregado' WHERE id = ? AND id_driver = ?";
        if ($pdo->prepare($sql)->execute([$id_venta, $id_user])) {
            echo json_encode(['success'=>true, 'message'=>'✅ Entrega registrada.']);
        } else {
            echo json_encode(['success'=>false, 'message'=>'❌ Error al finalizar.']);
        }
        exit;
    }
}

// 3. CONSULTAS
$stmtActivos = $pdo->prepare("SELECT * FROM ventas WHERE id_driver = ? AND estado_delivery = 'en_camino' ORDER BY fecha ASC");
$stmtActivos->execute([$id_user]);
$misPedidos = $stmtActivos->fetchAll();

$stmtPend = $pdo->prepare("SELECT * FROM ventas WHERE estado_delivery = 'pendiente' ORDER BY fecha DESC");
$stmtPend->execute();
$pendientes = $stmtPend->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Driver Pro - Royal</title>
    <link rel="stylesheet" href="../../assets/css/estilos.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* =========================================
           1. RESETEO TOTAL (Bye Bye Sidebar)
        ========================================= */
        html, body {
            margin: 0 !important; padding: 0 !important;
            width: 100% !important; height: 100% !important;
            background-color: #050505 !important; /* Fondo casi negro */
            font-family: 'Inter', sans-serif !important;
            overflow-x: hidden;
            display: block !important;
        }
        /* Ocultar elementos del admin global */
        .sidebar, nav, .main-content { display: none !important; } 

        /* =========================================
           2. ESTILOS APP PROFESIONAL
        ========================================= */
        .app-wrapper {
            max-width: 500px;
            margin: 0 auto;
            padding-bottom: 120px;
        }

        /* HEADER */
        .header-pro {
            background: rgba(20, 20, 20, 0.95);
            backdrop-filter: blur(10px);
            padding: 15px 20px;
            position: sticky; top: 0; z-index: 1000;
            border-bottom: 1px solid #333;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
        }
        .driver-profile { display: flex; align-items: center; gap: 12px; }
        .avatar-box {
            width: 42px; height: 42px; border-radius: 50%;
            background: linear-gradient(45deg, #FFD700, #b7892b);
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; color: #000; font-size: 1.2rem;
        }
        .driver-text h3 { margin: 0; font-size: 1rem; color: #fff; font-weight: 700; }
        .driver-text span { font-size: 0.75rem; color: #FFD700; text-transform: uppercase; letter-spacing: 1px; }
        
        .btn-exit { color: #ef5350; font-size: 1.4rem; cursor: pointer; transition: transform 0.2s; }
        .btn-exit:active { transform: scale(0.9); }

        /* CONTENEDOR DE TARJETAS */
        .stack-container {
            display: flex; flex-direction: column; gap: 20px;
            padding: 20px;
        }

        .section-heading {
            font-size: 0.85rem; color: #666; font-weight: 600; 
            text-transform: uppercase; letter-spacing: 1px; margin-left: 5px;
        }

        /* TARJETA MAESTRA */
        .card {
            background: #121212;
            border-radius: 16px;
            padding: 20px;
            position: relative;
            display: flex; flex-direction: column; gap: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            border: 1px solid #252525;
            transition: transform 0.2s;
        }
        .card:active { transform: scale(0.98); }

        /* ESTILOS ESPECÍFICOS: ACTIVO */
        .card-active {
            background: linear-gradient(160deg, #1b2e1e 0%, #0a0a0a 100%);
            border: 1px solid #2e7d32;
        }
        .card-active::before {
            content: ''; position: absolute; left: 0; top: 15px; bottom: 15px; width: 4px;
            background: #4caf50; border-radius: 0 4px 4px 0;
        }

        /* ESTILOS ESPECÍFICOS: PENDIENTE */
        .card-pending {
            border-left: 4px solid #FFD700;
        }

        /* DATA DE LA TARJETA */
        .card-header { display: flex; justify-content: space-between; align-items: center; }
        
        .badge { 
            padding: 5px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .badge-ruta { background: rgba(76, 175, 80, 0.2); color: #4caf50; border: 1px solid rgba(76, 175, 80, 0.3); }
        .badge-wait { background: rgba(255, 215, 0, 0.1); color: #FFD700; border: 1px solid rgba(255, 215, 0, 0.2); }

        .price-display { font-size: 1.5rem; font-weight: 800; color: #fff; }
        
        .address-box { display: flex; gap: 12px; align-items: flex-start; }
        .icon-pin { color: #FFD700; font-size: 1.2rem; margin-top: 3px; }
        .addr-text { font-size: 1.05rem; line-height: 1.4; color: #e0e0e0; font-weight: 500; }
        
        .meta-info { 
            display: flex; gap: 15px; font-size: 0.85rem; color: #888; 
            border-top: 1px solid #222; padding-top: 12px;
        }
        .meta-item { display: flex; align-items: center; gap: 6px; }

        /* BOTONES DE ACCIÓN (Stack Vertical) */
        .action-buttons {
            display: flex; flex-direction: column; gap: 10px; margin-top: 5px;
        }

        .btn {
            width: 100%; padding: 14px; border-radius: 12px; border: none;
            font-size: 0.95rem; font-weight: 700; cursor: pointer; text-decoration: none;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            transition: background 0.2s;
        }

        .btn-call { background: #222; color: #fff; border: 1px solid #333; }
        .btn-waze { background: #00c2ff; color: #000; box-shadow: 0 4px 15px rgba(0, 194, 255, 0.2); }
        .btn-success { background: #4caf50; color: #fff; box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3); }
        .btn-gold { background: #FFD700; color: #000; box-shadow: 0 4px 15px rgba(255, 215, 0, 0.2); }

        /* FAB REFRESH */
        .fab-refresh {
            position: fixed; bottom: 30px; right: 25px;
            width: 56px; height: 56px; border-radius: 50%;
            background: #111; border: 2px solid #FFD700; color: #FFD700;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; box-shadow: 0 5px 20px rgba(0,0,0,0.6);
            z-index: 200; cursor: pointer; transition: transform 0.3s;
        }
        .fab-refresh:active { transform: rotate(360deg); }

        /* EMPTY STATE */
        .empty-state { text-align: center; padding: 60px 20px; color: #444; }
        .empty-state i { font-size: 3rem; margin-bottom: 15px; opacity: 0.5; }

    </style>
</head>
<body>

<div class="app-wrapper">
    
    <header class="header-pro">
        <div class="driver-profile">
            <div class="avatar-box">
                <?= strtoupper(substr($driver['nombre'], 0, 1)) ?>
            </div>
            <div class="driver-text">
                <h3><?= explode(' ', $driver['nombre'])[0] ?></h3>
                <span><i class="fa-solid fa-motorcycle"></i> <?= $driver['vehiculo_placa'] ?: 'S/P' ?></span>
            </div>
        </div>
        <a href="../../logout.php" class="btn-exit"><i class="fa-solid fa-power-off"></i></a>
    </header>

    <div class="stack-container">

        <?php if (count($misPedidos) > 0): ?>
            <div class="section-heading">EN TU RUTA ACTUAL (<?= count($misPedidos) ?>)</div>

            <?php foreach($misPedidos as $p): ?>
                <div class="card card-active fade-in">
                    <div class="card-header">
                        <span class="badge badge-ruta">EN CAMINO #<?= $p['id'] ?></span>
                        <div class="price-display">S/ <?= number_format($p['total'], 2) ?></div>
                    </div>

                    <div class="address-box">
                        <i class="fa-solid fa-location-dot icon-pin"></i>
                        <div class="addr-text"><?= $p['direccion_entrega'] ?></div>
                    </div>

                    <div class="meta-info">
                        <div class="meta-item"><i class="fa-solid fa-user"></i> <?= $p['nombre_contacto'] ?></div>
                        <div class="meta-item"><i class="fa-solid fa-phone"></i> Contactar</div>
                    </div>

                    <div class="action-buttons">
                        <a href="tel:<?= $p['telefono_contacto'] ?>" class="btn btn-call">
                            <i class="fa-solid fa-phone"></i> LLAMAR
                        </a>
                        
                        <?php 
                            $waze = "https://waze.com/ul?q=" . urlencode($p['direccion_entrega']);
                            if(!empty($p['latitud'])) $waze = "https://waze.com/ul?ll={$p['latitud']},{$p['longitud']}&navigate=yes";
                        ?>
                        <a href="<?= $waze ?>" target="_blank" class="btn btn-waze">
                            <i class="fa-brands fa-waze"></i> NAVEGAR
                        </a>

                        <button onclick="accion('finalizar', <?= $p['id'] ?>)" class="btn btn-success">
                            <i class="fa-solid fa-check-circle"></i> CONFIRMAR ENTREGA
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="section-heading">DISPONIBLES PARA TI (<?= count($pendientes) ?>)</div>

        <?php if (count($pendientes) > 0): ?>
            <?php foreach($pendientes as $p): ?>
                <div class="card card-pending fade-in">
                    <div class="card-header">
                        <span class="badge badge-wait"><?= strtoupper($p['metodo_pago']) ?></span>
                        <div class="price-display" style="color:#FFD700;">S/ <?= number_format($p['total'], 2) ?></div>
                    </div>

                    <div class="address-box">
                        <i class="fa-regular fa-map icon-pin" style="color:#666;"></i>
                        <div class="addr-text" style="color:#ccc;"><?= $p['direccion_entrega'] ?: 'Sin dirección exacta' ?></div>
                    </div>

                    <div class="meta-info">
                        <div class="meta-item"><i class="fa-regular fa-clock"></i> <?= date('H:i', strtotime($p['fecha'])) ?></div>
                        <div class="meta-item"><i class="fa-solid fa-user"></i> <?= explode(' ', $p['nombre_contacto'])[0] ?></div>
                    </div>

                    <div class="action-buttons">
                        <button onclick="accion('tomar', <?= $p['id'] ?>)" class="btn btn-gold">
                            TOMAR PEDIDO <i class="fa-solid fa-hand-pointer"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-road"></i>
                <p>Todo tranquilo por ahora.<br>No hay pedidos pendientes.</p>
            </div>
        <?php endif; ?>

    </div>

</div>

<div class="fab-refresh" onclick="location.reload()">
    <i class="fa-solid fa-rotate-right"></i>
</div>

<script>
    // GPS Background Worker
    function gps() {
        if ("geolocation" in navigator) {
            navigator.geolocation.getCurrentPosition(pos => {
                fetch('../../api/gps.php', {
                    method: 'POST', 
                    headers: {'Content-Type':'application/x-www-form-urlencoded'},
                    body: `lat=${pos.coords.latitude}&lon=${pos.coords.longitude}&id_driver=<?= $id_user ?>`
                });
            });
        }
    }
    setInterval(gps, 30000); gps();

    // Lógica de Botones
    function accion(tipo, id) {
        let configs = {
            'tomar': { title: '¿Aceptar Viaje?', text: 'Se agregará a tu ruta actual.', btn: 'Sí, Aceptar', color: '#FFD700' },
            'finalizar': { title: '¿Entrega Completada?', text: 'Confirmarás que el cliente recibió el pedido.', btn: 'Sí, Entregado', color: '#4caf50' }
        };
        let cfg = configs[tipo];

        Swal.fire({
            title: cfg.title,
            text: cfg.text,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: cfg.color,
            cancelButtonColor: '#333',
            confirmButtonText: cfg.btn,
            background: '#1a1a1a', color: '#fff'
        }).then((res) => {
            if(res.isConfirmed) {
                Swal.showLoading();
                fetch(`index.php?action=${tipo}&id=${id}`)
                    .then(r => r.json())
                    .then(d => {
                        if(d.success) location.reload();
                        else Swal.fire({ icon:'error', title:'Error', text:d.message, background:'#222', color:'#fff' });
                    });
            }
        });
    }
</script>

</body>
</html>