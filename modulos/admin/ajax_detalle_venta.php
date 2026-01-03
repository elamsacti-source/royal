<?php
require_once '../../config/db.php';
if(!isset($_GET['id'])) exit('ID no proporcionado');
$id = $_GET['id'];

$sql = "SELECT d.*, p.nombre FROM ventas_detalle d JOIN productos p ON d.id_producto = p.id WHERE d.id_venta = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$detalles = $stmt->fetchAll();

$stmtV = $pdo->prepare("SELECT total FROM ventas WHERE id = ?");
$stmtV->execute([$id]);
$venta = $stmtV->fetch();
?>

<div style="font-family: monospace; color:#ccc;">
    <?php foreach($detalles as $d): ?>
        <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #333;">
            <div style="flex:1;">
                <div style="color:#fff;"><?= $d['nombre'] ?></div>
                <small>S/ <?= number_format($d['precio_historico'], 2) ?> x <?= $d['cantidad'] ?></small>
            </div>
            <div style="font-weight:bold;">
                S/ <?= number_format($d['subtotal'], 2) ?>
            </div>
        </div>
    <?php endforeach; ?>
    
    <div style="display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: bold; color: var(--royal-gold); margin-top: 15px; padding-top: 10px; border-top: 2px solid #333;">
        <span>TOTAL PAGADO</span>
        <span>S/ <?= number_format($venta['total'], 2) ?></span>
    </div>
</div>