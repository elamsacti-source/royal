<?php
session_start();
require_once '../../config/db.php';

// 1. SEGURIDAD: Solo Drivers
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] != 'driver') {
    header("Location: ../../index.php");
    exit;
}

$id_driver = $_SESSION['user_id'];
$mensaje = "";

// 2. LÓGICA DE ACCIONES (POST)

// A) Aceptar un pedido disponible
if (isset($_POST['accion']) && $_POST['accion'] == 'aceptar') {
    $id_venta = $_POST['id_venta'];
    
    // Validar que nadie más lo haya tomado en el último segundo
    $check = $pdo->prepare("SELECT id FROM ventas WHERE id = ? AND id_driver IS NULL");
    $check->execute([$id_venta]);
    
    if ($check->fetch()) {
        $stmt = $pdo->prepare("UPDATE ventas SET id_driver = ?, estado_delivery = 'en_camino' WHERE id = ?");
        $stmt->execute([$id_driver, $id_venta]);
        
        // Redirección limpia para evitar reenvíos
        header("Location: index.php"); 
        exit;
    } else {
        $mensaje = "<div class='alerta error'>⚠️ Lo siento, otro driver tomó este pedido.</div>";
    }
}

// B) Marcar como Entregado (LÓGICA BLINDADA)
if (isset($_POST['accion']) && $_POST['accion'] == 'entregar') {
    $id_venta = $_POST['id_venta'];
    
    // 1. Verificar estado actual
    $check = $pdo->prepare("SELECT estado_delivery FROM ventas WHERE id = ?");
    $check->execute([$id_venta]);
    $actual = $check->fetchColumn();

    if ($actual == 'entregado') {
        // Ya estaba listo, no hacemos nada más que redirigir
        header("Location: index.php");
        exit;
    } else {
        // 2. Actualizar
        $stmt = $pdo->prepare("UPDATE ventas SET estado_delivery = 'entregado' WHERE id = ? AND id_driver = ?");
        if ($stmt->execute([$id_venta, $id_driver])) {
            // Éxito: Redirigir para limpiar POST
            header("Location: index.php");
            exit;
        } else {
            $mensaje = "<div class='alerta error'>❌ Error al conectar. Intenta de nuevo.</div>";
        }
    }
}

// 3. CONSULTAS SQL

// Mis pedidos ACTIVOS (En camino)
$sqlMisPedidos = "SELECT * FROM ventas WHERE id_driver = ? AND estado_delivery = 'en_camino'";
$stmt = $pdo->prepare($sqlMisPedidos);
$stmt->execute([$id_driver]);
$mis_pedidos = $stmt->fetchAll();

// Pedidos DISPONIBLES (Confirmados por admin, sin driver)
$sqlDisponibles = "SELECT * FROM ventas WHERE tipo_venta = 'delivery' AND estado_delivery = 'confirmado' AND id_driver IS NULL ORDER BY id ASC";
$disponibles = $pdo->query($sqlDisponibles)->fetchAll();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Panel Driver - Royal</title>
    <link rel="stylesheet" href="../../assets/css/estilos.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #000; padding: 15px; padding-bottom: 50px; }
        
        .header-driver {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 20px; border-bottom: 1px solid #333; padding-bottom: 10px;
        }
        
        .card-pedido {
            background: #151515; border: 1px solid #333; border-radius: 12px;
            padding: 20px; margin-bottom: 20px; position: relative;
            box-shadow: 0 4px 10px rgba(0,0,0,0.5);
        }
        .card-pedido.activo { border: 2px solid var(--royal-gold); background: #1a1a00; }
        
        .badge { padding: 5px 10px; border-radius: 4px; font-weight: bold; font-size: 0.8rem; text-transform: uppercase; }
        .badge-info { background: #29b6f6; color: #000; }
        .badge-warning { background: var(--royal-gold); color: #000; }

        .btn-grande {
            width: 100%; padding: 15px; font-size: 1.1rem; font-weight: bold;
            border: none; border-radius: 8px; cursor: pointer; margin-top: 15px;
            display: flex; justify-content: center; align-items: center; gap: 10px;
            transition: opacity 0.3s;
        }
        .btn-grande:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-verde { background: #66bb6a; color: #000; }
        .btn-rojo { background: #ef5350; color: #fff; }

        .dato-fila { display: flex; gap: 10px; margin-bottom: 12px; align-items: center; color: #ccc; }
        .icono-fijo { width: 25px; text-align: center; color: var(--royal-gold); font-size: 1.2rem; }

        .alerta { padding: 15px; border-radius: 8px; margin-bottom: 20px; color: #fff; font-weight: bold; text-align: center; }
        .error { background: rgba(239, 83, 80, 0.2); border: 1px solid #ef5350; }
        
        .radar-gps {
            width: 12px; height: 12px; background: #66bb6a; border-radius: 50%;
            display: inline-block; margin-right: 10px;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(102, 187, 106, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(102, 187, 106, 0); }
            100% { box-shadow: 0 0 0 0 rgba(102, 187, 106, 0); }
        }
    </style>
</head>
<body>

    <div class="header-driver">
        <div>
            <h2 style="color:var(--royal-gold); margin:0;">ROYAL GO <i class="fa-solid fa-motorcycle"></i></h2>
            <small style="color:#888;"><?= $_SESSION['nombre'] ?></small>
        </div>
        <a href="../../logout.php" style="color:#ef5350; text-decoration:none; border:1px solid #ef5350; padding:5px 10px; border-radius:5px;">
            <i class="fa-solid fa-power-off"></i>
        </a>
    </div>

    <?= $mensaje ?>

    <?php if(count($mis_pedidos) > 0): ?>
        <h3 style="color:#fff; border-left:4px solid #66bb6a; padding-left:10px; margin-bottom:15px;">
            <span class="radar-gps"></span> En Ruta (Activo)
        </h3>
        
        <?php foreach($mis_pedidos as $p): ?>
            <div class="card-pedido activo">
                <div style="display:flex; justify-content:space-between; margin-bottom:15px;">
                    <span class="badge badge-warning">Pedido #<?= $p['id'] ?></span>
                    <span style="color:#66bb6a; font-weight:bold; font-size:1.2rem;">S/ <?= number_format($p['total'], 2) ?></span>
                </div>

                <div class="dato-fila">
                    <div class="icono-fijo"><i class="fa-solid fa-user"></i></div>
                    <div style="font-weight:bold; color:#fff;"><?= $p['nombre_contacto'] ?></div>
                </div>
                
                <div class="dato-fila">
                    <div class="icono-fijo"><i class="fa-brands fa-whatsapp"></i></div>
                    <a href="https://wa.me/51<?= preg_replace('/[^0-9]/', '', $p['telefono_contacto']) ?>?text=Hola, soy el delivery de Royal. Estoy en camino." 
                       class="btn-royal" style="background:#25D366; color:#fff; width:auto; flex:1; text-align:center; padding:8px; font-size:0.9rem;">
                        Contactar WhatsApp
                    </a>
                </div>

                <div class="dato-fila">
                    <div class="icono-fijo"><i class="fa-solid fa-location-dot"></i></div>
                    <div style="flex:1;">
                        <div style="color:#fff; margin-bottom:5px;"><?= $p['direccion_entrega'] ?></div>
                        <a href="https://waze.com/ul?ll=<?= $p['latitud'] ?>,<?= $p['longitud'] ?>&navigate=yes" 
                           target="_blank" style="color:#4fc3f7; text-decoration:none; font-size:0.9rem;">
                           <i class="fa-solid fa-diamond-turn-right"></i> Abrir en Waze / Maps
                        </a>
                    </div>
                </div>
                
                <div class="dato-fila">
                    <div class="icono-fijo"><i class="fa-solid fa-wallet"></i></div>
                    <div style="color: #FFD700; font-weight:bold;">Método: <?= $p['metodo_pago'] ?></div>
                </div>

                <form method="POST" onsubmit="return confirmarEntrega(this);">
                    <input type="hidden" name="accion" value="entregar">
                    <input type="hidden" name="id_venta" value="<?= $p['id'] ?>">
                    <button type="submit" class="btn-grande btn-verde">
                        <i class="fa-solid fa-check-circle"></i> MARCAR ENTREGADO
                    </button>
                </form>

                <script>
                    function enviarUbicacion() {
                        if (navigator.geolocation) {
                            navigator.geolocation.getCurrentPosition(pos => {
                                fetch('../../api/gps.php', {
                                    method: 'POST',
                                    headers: {'Content-Type': 'application/json'},
                                    body: JSON.stringify({
                                        id_venta: <?= $p['id'] ?>,
                                        lat: pos.coords.latitude,
                                        lon: pos.coords.longitude
                                    })
                                }).catch(e => {}); // Silencio errores de red para no molestar
                            });
                        }
                    }
                    enviarUbicacion();
                    setInterval(enviarUbicacion, 5000);
                </script>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>


    <h3 style="color:#fff; border-left:4px solid #29b6f6; padding-left:10px; margin-top:30px; margin-bottom:15px;">
        Disponibles (<?= count($disponibles) ?>)
    </h3>

    <?php if(count($disponibles) == 0): ?>
        <p style="color:#666; text-align:center; padding:20px;">No hay pedidos nuevos por ahora.</p>
    <?php endif; ?>

    <?php foreach($disponibles as $d): ?>
        <div class="card-pedido">
            <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                <span class="badge badge-info">Nuevo #<?= $d['id'] ?></span>
                <span style="color:#fff; font-weight:bold;">S/ <?= number_format($d['total'], 2) ?></span>
            </div>

            <div class="dato-fila">
                <div class="icono-fijo"><i class="fa-solid fa-location-dot"></i></div>
                <div style="color:#aaa;"><?= $d['direccion_entrega'] ?></div>
            </div>

            <div class="dato-fila">
                <div class="icono-fijo"><i class="fa-solid fa-money-bill"></i></div>
                <div>Pago: <b style="color:#fff;"><?= $d['metodo_pago'] ?></b></div>
            </div>

            <form method="POST">
                <input type="hidden" name="accion" value="aceptar">
                <input type="hidden" name="id_venta" value="<?= $d['id'] ?>">
                <button type="submit" class="btn-grande" style="background:var(--royal-gold); color:#000;">
                    TOMAR PEDIDO <i class="fa-solid fa-hand-pointer"></i>
                </button>
            </form>
        </div>
    <?php endforeach; ?>

    <script>
        // Función para evitar doble envío en entrega
        function confirmarEntrega(form) {
            if (confirm('¿Confirmas que entregaste el pedido y recibiste el pago?')) {
                let btn = form.querySelector('button[type="submit"]');
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> PROCESANDO...';
                btn.style.opacity = '0.7';
                return true;
            }
            return false;
        }

        // Recarga automática para ver nuevos pedidos (solo si no estoy ocupado)
        setTimeout(() => {
            if(!document.querySelector('.card-pedido.activo')) { 
                location.reload();
            }
        }, 15000);
    </script>

</body>
</html>