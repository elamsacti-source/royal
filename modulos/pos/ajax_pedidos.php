<?php
// ARCHIVO: modulos/pos/ajax_pedidos.php
require_once '../../config/db.php';

// CONSULTAR PEDIDOS
// Buscamos los estados activos: pendiente, confirmado, en_camino
$sql = "SELECT v.*, u.nombre as driver_nombre 
        FROM ventas v 
        LEFT JOIN usuarios u ON v.id_driver = u.id
        WHERE tipo_venta = 'delivery' 
        AND estado_delivery IN ('pendiente', 'confirmado', 'en_camino')
        ORDER BY FIELD(estado_delivery, 'pendiente', 'en_camino', 'confirmado'), v.id DESC";
$pedidos = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// SI NO HAY PEDIDOS
if(count($pedidos) == 0): ?>
    <div style="text-align:center; padding:50px; color:#444;">
        <i class="fa-solid fa-check-circle" style="font-size:3rem; margin-bottom:15px;"></i>
        <p>Todo limpio. No hay pedidos activos.</p>
    </div>
<?php endif;

// GENERAR TARJETAS
foreach($pedidos as $p): ?>
    <div class="card-pedido <?= $p['estado_delivery'] ?>" data-id="<?= $p['id'] ?>">
        
        <div style="display:flex; justify-content:space-between; margin-bottom:15px; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:10px;">
            <div>
                <span style="background:#333; color:#fff; padding:3px 8px; border-radius:4px; font-weight:bold; font-size:0.85rem;">#<?= $p['id'] ?></span>
                <span class="lbl-estado" style="margin-left:8px; font-weight:bold; text-transform:uppercase; font-size:0.8rem;">
                    <?= str_replace('_', ' ', $p['estado_delivery']) ?>
                </span>
            </div>
            <div style="color:#FFD700; font-weight:800; font-size:1.2rem;">
                S/ <?= number_format($p['total'], 2) ?>
            </div>
        </div>
        
        <div class="info-row">
            <div class="info-icon"><i class="fa-solid fa-user"></i></div>
            <div><?= $p['nombre_contacto'] ?></div>
        </div>
        
        <div class="info-row">
            <div class="info-icon"><i class="fa-brands fa-whatsapp"></i></div>
            <div>
                <a href="https://wa.me/51<?= preg_replace('/[^0-9]/','',$p['telefono_contacto']) ?>" target="_blank" style="color:#66bb6a; text-decoration:none; font-weight:bold;">
                    <?= $p['telefono_contacto'] ?>
                </a>
            </div>
        </div>

        <div class="info-row">
            <div class="info-icon"><i class="fa-solid fa-map-pin"></i></div>
            <div><?= $p['direccion_entrega'] ?></div>
        </div>

        <div class="product-list">
            <?php 
                $detalles = $pdo->query("SELECT d.cantidad, p.nombre FROM ventas_detalle d JOIN productos p ON d.id_producto = p.id WHERE d.id_venta = {$p['id']}")->fetchAll();
                foreach($detalles as $d) { 
                    echo "<div class='product-item'>
                            <span><span class='product-qty'>{$d['cantidad']}</span> {$d['nombre']}</span>
                          </div>"; 
                }
            ?>
            <div style="margin-top:10px; padding-top:5px; border-top:1px solid #333; color:#FFD700; font-weight:bold; text-align:right;">
                Pago: <?= ucfirst($p['metodo_pago']) ?>
            </div>
        </div>

        <?php if($p['estado_delivery'] == 'pendiente'): ?>
            <div style="display:flex; gap:10px;">
                <button onclick="procesarAccion(<?= $p['id'] ?>, 'confirmar')" class="btn-accion btn-accept">
                    <i class="fa-solid fa-check"></i> ACEPTAR
                </button>
                <button onclick="procesarAccion(<?= $p['id'] ?>, 'cancelar')" class="btn-accion btn-cancel">
                    RECHAZAR
                </button>
            </div>
            
            <?php if($p['latitud']): ?>
                <a href="../../public/track.php?id=<?= $p['id'] ?>" target="_blank" class="gps-btn">
                    <i class="fa-solid fa-map-location-dot"></i> VER MAPA GPS
                </a>
            <?php endif; ?>

        <?php elseif($p['estado_delivery'] == 'confirmado'): ?>
            <div style="text-align:center; color:#FFD700; padding:15px; background:rgba(255, 215, 0, 0.1); border-radius:8px; border:1px dashed #FFD700;">
                <i class="fa-solid fa-spinner fa-spin"></i> Esperando Driver...
            </div>

        <?php elseif($p['estado_delivery'] == 'en_camino'): ?>
            <div style="text-align:center; color:#66bb6a; padding:15px; background:rgba(102, 187, 106, 0.1); border-radius:8px; border:1px solid #66bb6a;">
                <div style="font-weight:bold; margin-bottom:5px;">EN RUTA</div>
                <i class="fa-solid fa-motorcycle"></i> Driver: <b><?= $p['driver_nombre'] ?: 'Asignado' ?></b>
            </div>
        <?php endif; ?>

    </div>
<?php endforeach; ?>