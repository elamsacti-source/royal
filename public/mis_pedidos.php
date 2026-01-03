<?php
// public/mis_pedidos.php
session_start();
require_once '../config/db.php';

// Seguridad: Solo usuarios logueados
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$id_user = $_SESSION['user_id'];

// Obtener historial
$sql = "SELECT * FROM ventas WHERE id_cliente = ? ORDER BY fecha DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_user]);
$pedidos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos - Royal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&family=Cinzel:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background: #050505; color: #fff; font-family: 'Inter', sans-serif; padding: 20px; }
        .container { max-width: 600px; margin: auto; }

        .page-title { 
            color: #FFD700; font-family: 'Cinzel', serif; text-align: center; 
            margin-bottom: 30px; border-bottom: 1px solid #333; padding-bottom: 15px;
        }

        .order-card {
            background: #141414; border: 1px solid #333; border-radius: 12px;
            padding: 20px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;
            transition: 0.2s;
        }
        .order-card:active { background: #222; transform: scale(0.98); }

        .order-info h4 { margin: 0 0 5px 0; font-size: 1rem; color: #fff; }
        .order-date { font-size: 0.8rem; color: #888; }
        .order-total { color: #FFD700; font-weight: bold; font-size: 1.1rem; margin-top: 5px; }

        .status-badge {
            font-size: 0.7rem; padding: 4px 10px; border-radius: 20px; text-transform: uppercase; font-weight: bold; margin-bottom: 5px; display: inline-block;
        }
        .st-pendiente { background: #333; color: #ccc; }
        .st-en_camino { background: rgba(255, 215, 0, 0.15); color: #FFD700; border: 1px solid #FFD700; }
        .st-entregado { background: rgba(76, 175, 80, 0.2); color: #4caf50; }

        .btn-track {
            background: #222; border: 1px solid #FFD700; color: #FFD700;
            width: 45px; height: 45px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; font-size: 1.2rem; transition: 0.3s;
        }
        .btn-track:hover { background: #FFD700; color: #000; }
        
        .btn-back { display: block; text-align: center; color: #666; margin-top: 30px; text-decoration: none; }
    </style>
</head>
<body>

<div class="container">
    <h2 class="page-title">HISTORIAL DE PEDIDOS</h2>

    <?php if (count($pedidos) > 0): ?>
        <?php foreach($pedidos as $p): ?>
            <?php 
                $estadoClass = 'st-pendiente';
                $estadoIcon = 'fa-clock';
                if($p['estado_delivery'] == 'en_camino') { $estadoClass = 'st-en_camino'; $estadoIcon = 'fa-motorcycle'; }
                if($p['estado_delivery'] == 'entregado') { $estadoClass = 'st-entregado'; $estadoIcon = 'fa-check'; }
            ?>
            <div class="order-card">
                <div class="order-info">
                    <span class="status-badge <?= $estadoClass ?>">
                        <i class="fa-solid <?= $estadoIcon ?>"></i> <?= strtoupper(str_replace('_', ' ', $p['estado_delivery'])) ?>
                    </span>
                    <h4>Pedido #<?= $p['id'] ?></h4>
                    <div class="order-date"><i class="fa-regular fa-calendar"></i> <?= date('d/m/Y H:i', strtotime($p['fecha'])) ?></div>
                    <div class="order-total">S/ <?= number_format($p['total'], 2) ?></div>
                </div>
                
                <a href="track.php?id=<?= $p['id'] ?>" class="btn-track">
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="text-align:center; color:#666; padding:50px;">
            <i class="fa-solid fa-box-open" style="font-size:3rem; margin-bottom:15px;"></i>
            <p>Aún no has realizado pedidos.</p>
        </div>
    <?php endif; ?>

    <a href="index.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Volver a la Tienda</a>
</div>

</body>
</html>