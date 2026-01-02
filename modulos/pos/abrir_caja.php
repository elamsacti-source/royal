<?php
require_once '../../config/db.php';
session_start();

// Validar sesión
if (!isset($_SESSION['user_id'])) { header("Location: ../../index.php"); exit; }

$id_usuario = $_SESSION['user_id'];

// 1. Verificar si ya tiene caja abierta
$sql = "SELECT id FROM caja_sesiones WHERE id_usuario = ? AND estado = 'abierta'";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_usuario]);
$caja = $stmt->fetch();

if ($caja) {
    // Si ya está abierta, ir directo a vender
    header("Location: venta.php");
    exit;
}

// 2. Procesar Apertura
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $monto_inicial = $_POST['monto_inicial'];
    
    $sqlInsert = "INSERT INTO caja_sesiones (id_usuario, fecha_apertura, monto_inicial, estado) VALUES (?, NOW(), ?, 'abierta')";
    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->execute([$id_usuario, $monto_inicial]);
    
    $_SESSION['caja_id'] = $pdo->lastInsertId(); // Guardamos ID de sesión
    header("Location: venta.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abrir Caja - Royal</title>
    <link rel="stylesheet" href="/assets/css/estilos.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { justify-content: center; align-items: center; background: radial-gradient(circle, #1a1a1a 0%, #000 100%); }
        .login-box { width: 100%; max-width: 400px; padding: 40px; }
    </style>
</head>
<body>
    <div class="login-box fade-in">
        <div class="card" style="text-align:center; border: 1px solid var(--royal-gold);">
            <div style="font-size: 3rem; color: var(--royal-gold); margin-bottom: 20px;">
                <i class="fa-solid fa-cash-register"></i>
            </div>
            <h2 style="color:#fff; margin-bottom:10px;">Apertura de Turno</h2>
            <p style="color:#888; margin-bottom:30px;">Ingresa el efectivo inicial en caja.</p>

            <form method="POST">
                <label style="text-align:left;">Monto Inicial ($)</label>
                <input type="number" step="0.01" name="monto_inicial" required autofocus style="font-size:1.5rem; text-align:center; font-weight:bold; color:var(--royal-gold);">
                
                <button type="submit" class="btn-royal btn-block" style="margin-top:20px;">
                    <i class="fa-solid fa-check"></i> ABRIR CAJA
                </button>
            </form>
            <br>
            <a href="../../logout.php" style="color:#ef5350; text-decoration:none;">Cancelar y Salir</a>
        </div>
    </div>
</body>
</html>