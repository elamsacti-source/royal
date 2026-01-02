<?php
require_once '../../config/db.php';

if(!isset($_GET['id'])) exit('ID no proporcionado');
$id = $_GET['id'];

// Obtener detalles
$sql = "SELECT d.*, p.nombre 
        FROM ventas_detalle d 
        JOIN productos p ON d.id_producto = p.id 
        WHERE d.id_venta = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$detalles = $stmt->fetchAll();

// Obtener cabecera para total
$stmtV = $pdo->prepare("SELECT total FROM ventas WHERE id = ?");
$stmtV->execute([$id]);
$venta = $stmtV->fetch();
?>

<div style="font-family: monospace; color:#ccc;">
    <?php foreach($detalles as $d): ?>
        <div class="item-row">
            <div style="flex:1;">
                <div style="color:#fff;"><?= $d['nombre'] ?></div>
                <small>$<?= number_format($d['precio_historico'], 2) ?> x <?= $d['cantidad'] ?></small>
            </div>
            <div style="font-weight:bold;">
                $<?= number_format($d['subtotal'], 2) ?>
            </div>
        </div>
    <?php endforeach; ?>
    
    <div class="total-row">
        <span>TOTAL PAGADO</span>
        <span>$<?= number_format($venta['total'], 2) ?></span>
    </div>
</div>