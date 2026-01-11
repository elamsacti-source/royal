<?php
require_once '../../config/db.php';
include '../../includes/header_admin.php';

// Consulta con JOIN a Sede
$sql = "SELECT k.*, p.nombre as producto, p.codigo_barras, s.nombre as sede 
        FROM kardex k 
        JOIN productos p ON k.id_producto = p.id 
        LEFT JOIN sedes s ON k.id_sede = s.id
        ORDER BY k.fecha DESC LIMIT 100";
$movimientos = $pdo->query($sql)->fetchAll();
?>

<div class="fade-in">
    <h2 class="page-title">Kardex Multisede</h2>
    <div class="card" style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Sede</th>
                    <th>Producto</th>
                    <th>Movimiento</th>
                    <th>Cant.</th>
                    <th>Saldo</th>
                    <th>Nota</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($movimientos as $m): ?>
                    <?php 
                        $color = '#fff'; $icon = '';
                        if(strpos($m['tipo_movimiento'], 'entrada') !== false) { $color='#66bb6a'; $icon='fa-arrow-up'; }
                        elseif(strpos($m['tipo_movimiento'], 'salida') !== false) { $color='#ef5350'; $icon='fa-arrow-down'; }
                        elseif($m['tipo_movimiento'] == 'venta') { $color='#29b6f6'; $icon='fa-cash-register'; }
                    ?>
                    <tr>
                        <td style="color:#888; font-size:0.85rem;"><?= date('d/m/Y H:i', strtotime($m['fecha'])) ?></td>
                        <td><span class="badge" style="background:#333; color:#ccc;"><?= $m['sede'] ?: 'General' ?></span></td>
                        <td>
                            <div style="font-weight:600; color:#fff;"><?= $m['producto'] ?></div>
                            <small style="color:#555;"><?= $m['codigo_barras'] ?></small>
                        </td>
                        <td style="color:<?= $color ?>; text-transform:uppercase; font-size:0.8rem; font-weight:bold;">
                            <i class="fa-solid <?= $icon ?>"></i> <?= str_replace('_', ' ', $m['tipo_movimiento']) ?>
                        </td>
                        <td style="font-weight:bold; font-size:1.1rem; color:<?= $color ?>;"><?= $m['cantidad'] > 0 ? '+'.$m['cantidad'] : $m['cantidad'] ?></td>
                        <td style="color:#FFD700; font-weight:bold;"><?= $m['stock_resultante'] ?></td>
                        <td style="color:#666; font-style:italic; font-size:0.9rem;"><?= $m['nota'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include '../../includes/footer_admin.php'; ?>