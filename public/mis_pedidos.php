<?php
// public/mis_pedidos.php
session_start();
require_once '../config/db.php';

// Validar sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$id_cliente = $_SESSION['user_id'];
$pagina     = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limite     = 10;
$inicio     = ($pagina - 1) * $limite;
$tab        = isset($_GET['tab']) ? $_GET['tab'] : 'activos';

// Filtro por estado
if($tab == 'activos') {
    $condicion = "AND estado_delivery IN ('pendiente', 'confirmado', 'en_camino')";
} else {
    $condicion = "AND estado_delivery IN ('entregado', 'cancelado')";
}

// Paginación
$sqlCount = "SELECT COUNT(*) FROM ventas WHERE id_cliente = ? $condicion";
$stmtC = $pdo->prepare($sqlCount);
$stmtC->execute([$id_cliente]);
$total_registros = $stmtC->fetchColumn();
$total_paginas   = ceil($total_registros / $limite);

// Obtener pedidos
$sql = "SELECT * FROM ventas WHERE id_cliente = ? $condicion ORDER BY id DESC LIMIT $inicio, $limite";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_cliente]);
$pedidos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        body { background: #050505; color: #fff; font-family: 'Poppins', sans-serif; padding: 20px; max-width: 600px; margin: 0 auto; }
        .header { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; }
        .back-btn { background: #222; color: #fff; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; }
        
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid #333; padding-bottom: 10px; }
        .tab { padding: 8px 15px; border-radius: 20px; text-decoration: none; color: #888; font-size: 0.9rem; }
        .tab.active { background: #FFD700; color: #000; font-weight: bold; }

        .card { background: #161616; border: 1px solid #333; border-radius: 12px; padding: 15px; margin-bottom: 15px; }
        .status { padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; text-transform: uppercase; font-weight: bold; }
        .status.pendiente { background: #333; color: #ccc; }
        .status.en_camino { background: #66bb6a; color: #000; }
        .status.entregado { background: #222; color: #666; border: 1px solid #444; }

        .btn-track { display: block; width: 100%; text-align: center; background: #222; border: 1px solid #FFD700; color: #FFD700; padding: 10px; border-radius: 8px; margin-top: 10px; text-decoration: none; font-weight: bold; }
        .pagination { display: flex; justify-content: center; gap: 10px; margin-top: 20px; }
        .page-link { background: #222; color: #fff; padding: 8px 12px; text-decoration: none; border-radius: 5px; }
        .page-link.active { background: #FFD700; color: #000; }
    </style>
</head>
<body>
    <div class="header">
        <a href="index.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i></a>
        <h2>Mis Pedidos</h2>
    </div>

    <div class="tabs">
        <a href="?tab=activos" class="tab <?= $tab=='activos'?'active':'' ?>">En Curso</a>
        <a href="?tab=historial" class="tab <?= $tab=='historial'?'active':'' ?>">Historial</a>
    </div>

    <?php if(count($pedidos) == 0): ?>
        <p style="text-align:center; color:#666; padding:30px;">No hay pedidos en esta sección.</p>
    <?php endif; ?>

    <?php foreach($pedidos as $p): ?>
        <div class="card">
            <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                <span style="color:#FFD700; font-weight:bold;">#<?= str_pad($p['id'], 5, '0', STR_PAD_LEFT) ?></span>
                <span class="status <?= $p['estado_delivery'] ?>"><?= str_replace('_', ' ', $p['estado_delivery']) ?></span>
            </div>
            <div style="font-size:0.9rem; color:#ccc; margin-bottom:5px;">
                <i class="fa-regular fa-calendar"></i> <?= date('d/m/Y h:i A', strtotime($p['fecha'])) ?>
            </div>
            <div style="font-size:1.1rem; font-weight:bold;">Total: S/ <?= number_format($p['total'], 2) ?></div>
            
            <?php if($tab == 'activos'): ?>
                <a href="track.php?id=<?= $p['id'] ?>" class="btn-track">VER SEGUIMIENTO</a>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <?php if($total_paginas > 1): ?>
        <div class="pagination">
            <?php for($i=1; $i<=$total_paginas; $i++): ?>
                <a href="?tab=<?= $tab ?>&page=<?= $i ?>" class="page-link <?= $i==$pagina?'active':'' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</body>
</html>