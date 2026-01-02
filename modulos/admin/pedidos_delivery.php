<?php
session_start();
require_once '../../config/db.php';
include '../../includes/header_admin.php';

// 1. ACCIONES (Confirmar / Rechazar)
if (isset($_POST['accion'])) {
    $id_venta = $_POST['id_venta'];
    
    if ($_POST['accion'] == 'confirmar') {
        // CORRECCIÓN BLINDAJE: Solo confirmar si sigue pendiente. Si ya está en camino, no lo tocamos.
        $stmt = $pdo->prepare("UPDATE ventas SET estado_delivery = 'confirmado' WHERE id = ? AND estado_delivery = 'pendiente'");
        $stmt->execute([$id_venta]);
    }
    if ($_POST['accion'] == 'cancelar') {
        $stmt = $pdo->prepare("UPDATE ventas SET estado_delivery = 'cancelado' WHERE id = ?");
        $stmt->execute([$id_venta]);
    }
    if ($_POST['accion'] == 'marcar_pagado') {
        $stmt = $pdo->prepare("UPDATE ventas SET metodo_pago = CONCAT(metodo_pago, ' (Verificado)') WHERE id = ?");
        $stmt->execute([$id_venta]);
    }
}

// 2. CONSULTAR PEDIDOS (Pendientes y En Curso)
$sql = "SELECT v.*, u.nombre as driver_nombre 
        FROM ventas v 
        LEFT JOIN usuarios u ON v.id_driver = u.id
        WHERE tipo_venta = 'delivery' 
        AND estado_delivery IN ('pendiente', 'confirmado', 'en_camino')
        ORDER BY FIELD(estado_delivery, 'pendiente', 'en_camino', 'confirmado'), v.id DESC";
$pedidos = $pdo->query($sql)->fetchAll();

// Sonido de alerta si hay pendientes
$hay_pendientes = false;
foreach($pedidos as $p) { if($p['estado_delivery']=='pendiente') $hay_pendientes=true; }
?>

<div class="fade-in">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h2 class="page-title">🛵 Control de Delivery</h2>
        <div style="color:#888;">Actualización automática <i class="fa-solid fa-circle-notch fa-spin"></i></div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
        
        <?php foreach($pedidos as $p): ?>
            <?php 
                // Estilos según estado
                $borde = "#333"; $bg = "#1a1a1a";
                if($p['estado_delivery']=='pendiente') { $borde = "#ef5350"; $bg = "#2a0a0a"; } // Rojo
                if($p['estado_delivery']=='confirmado') { $borde = "#FFD700"; } // Amarillo
                if($p['estado_delivery']=='en_camino') { $borde = "#66bb6a"; } // Verde
            ?>
            
            <div class="card" style="border: 2px solid <?= $borde ?>; background: <?= $bg ?>; margin-bottom:0; padding:15px;">
                <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                    <span style="font-weight:bold; color:#fff;">#<?= $p['id'] ?> - <?= ucfirst($p['estado_delivery']) ?></span>
                    <span style="color:#fff;"><?= date('H:i', strtotime($p['fecha'])) ?></span>
                </div>

                <div style="margin-bottom:10px; color:#ddd;">
                    <i class="fa-solid fa-user"></i> <?= $p['nombre_contacto'] ?><br>
                    <i class="fa-solid fa-phone"></i> 
                    <a href="https://wa.me/51<?= preg_replace('/[^0-9]/','',$p['telefono_contacto']) ?>" target="_blank" style="color:#66bb6a;">
                        <?= $p['telefono_contacto'] ?>
                    </a>
                </div>
                
                <div style="background:#000; padding:10px; border-radius:5px; margin-bottom:10px; font-size:0.9rem; max-height:100px; overflow-y:auto;">
                    <?php
                        $stmtDet = $pdo->prepare("SELECT d.cantidad, p.nombre FROM ventas_detalle d JOIN productos p ON d.id_producto = p.id WHERE d.id_venta = ?");
                        $stmtDet->execute([$p['id']]);
                        $items = $stmtDet->fetchAll();
                        foreach($items as $i) echo "<div>{$i['cantidad']} x {$i['nombre']}</div>";
                    ?>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <div style="color:var(--royal-gold); font-weight:bold; font-size:1.2rem;">S/ <?= $p['total'] ?></div>
                    <div style="font-size:0.8rem; color:#ccc;"><?= $p['metodo_pago'] ?></div>
                </div>

                <?php if(!empty($p['latitud'])): ?>
                    <a href="../public/track.php?id=<?= $p['id'] ?>" target="_blank" class="btn-royal" style="padding:5px; font-size:0.8rem; background:#333; color:#fff; display:block; text-align:center; margin-bottom:10px;">
                        <i class="fa-solid fa-map-location-dot"></i> Ver Mapa GPS
                    </a>
                <?php endif; ?>

                <div style="display:flex; gap:5px;">
                    <?php if($p['estado_delivery'] == 'pendiente'): ?>
                        <form method="POST" style="flex:1;">
                            <input type="hidden" name="id_venta" value="<?= $p['id'] ?>">
                            <button name="accion" value="confirmar" class="btn-royal" style="background:#66bb6a; color:#000; width:100%;">ACEPTAR</button>
                        </form>
                        <form method="POST" style="flex:1;">
                            <input type="hidden" name="id_venta" value="<?= $p['id'] ?>">
                            <button name="accion" value="cancelar" class="btn-royal" style="background:#ef5350; color:#fff; width:100%;">X</button>
                        </form>
                    <?php elseif($p['estado_delivery'] == 'confirmado'): ?>
                        <div style="width:100%; text-align:center; color:#FFD700; padding:10px; border:1px dashed #FFD700; border-radius:5px;">
                            <i class="fa-solid fa-spinner fa-spin"></i> Esperando Driver...
                        </div>
                    <?php elseif($p['estado_delivery'] == 'en_camino'): ?>
                        <div style="width:100%; text-align:center; color:#66bb6a; padding:10px; border:1px solid #66bb6a; border-radius:5px;">
                            <i class="fa-solid fa-motorcycle"></i> Llevado por: <b><?= $p['driver_nombre'] ?></b>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        <?php endforeach; ?>

        <?php if(count($pedidos) == 0): ?>
            <p style="color:#666; grid-column:1/-1; text-align:center;">No hay pedidos activos.</p>
        <?php endif; ?>
    </div>
</div>

<audio id="alerta-nueva" src="../../assets/notification.mp3"></audio>

<script>
    setTimeout(() => {
        location.reload();
    }, 10000);

    <?php if($hay_pendientes): ?>
        window.onload = function() {
            let audio = document.getElementById('alerta-nueva');
            audio.play().catch(e => console.log("Click para activar audio"));
        };
    <?php endif; ?>
</script>

<?php include '../../includes/footer_admin.php'; ?>