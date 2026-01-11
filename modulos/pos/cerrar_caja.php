<?php
require_once '../../config/db.php';
session_start();

// 1. Validar Sesión y Caja Abierta
if (!isset($_SESSION['user_id'])) { header("Location: ../../index.php"); exit; }

$id_usuario = $_SESSION['user_id'];

// Buscar sesión activa
$stmt = $pdo->prepare("SELECT * FROM caja_sesiones WHERE id_usuario = ? AND estado = 'abierta'");
$stmt->execute([$id_usuario]);
$caja = $stmt->fetch();

if (!$caja) { header("Location: abrir_caja.php"); exit; }

$id_sesion = $caja['id'];
$monto_inicial = $caja['monto_inicial'];

// 2. CALCULAR VENTAS DEL TURNO
$sqlVentas = "SELECT 
                SUM(CASE WHEN metodo_pago = 'Efectivo' THEN total ELSE 0 END) as total_efectivo,
                SUM(CASE WHEN metodo_pago != 'Efectivo' THEN total ELSE 0 END) as total_digital,
                COUNT(*) as cant_ventas
              FROM ventas 
              WHERE id_caja_sesion = ?";
$stmtV = $pdo->prepare($sqlVentas);
$stmtV->execute([$id_sesion]);
$balance = $stmtV->fetch();

$ventas_efectivo = $balance['total_efectivo'] ?: 0;
$ventas_digital  = $balance['total_digital'] ?: 0;
$total_esperado  = $monto_inicial + $ventas_efectivo; // Dinero físico esperado en cajón

// 3. PROCESAR CIERRE (AJAX)
if (isset($_GET['action']) && $_GET['action'] == 'cerrar') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $monto_real = $input['monto_real']; 
    
    $diferencia = $monto_real - $total_esperado;
    $total_ventas = $ventas_efectivo + $ventas_digital;

    try {
        $sqlCierre = "UPDATE caja_sesiones 
                      SET fecha_cierre = NOW(), 
                          monto_final = ?, 
                          total_ventas = ?, 
                          diferencia = ?, 
                          estado = 'cerrada' 
                      WHERE id = ?";
        $stmtCierre = $pdo->prepare($sqlCierre);
        $stmtCierre->execute([$monto_real, $total_ventas, $diferencia, $id_sesion]);

        unset($_SESSION['caja_id']);
        echo json_encode(['success' => true, 'diff' => $diferencia]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cerrar Caja - Royal</title>
    <link rel="stylesheet" href="../../assets/css/estilos.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { 
            display: flex; justify-content: center; align-items: center; 
            min-height: 100vh; background: #000; 
            background-image: radial-gradient(circle at center, #1a1a1a 0%, #000 100%);
        }
        
        .box-arqueo { 
            width: 100%; max-width: 550px; 
            background: #111; border: 1px solid #333; 
            padding: 40px; border-radius: 20px; 
            box-shadow: 0 0 50px rgba(0,0,0,0.7);
            position: relative;
            overflow: hidden;
        }

        /* Decoración dorada superior */
        .box-arqueo::before {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px;
            background: linear-gradient(90deg, #FFD700, #FFA000);
        }

        .header-icon { font-size: 3rem; color: var(--royal-gold); margin-bottom: 15px; }
        
        .stat-card {
            background: #1a1a1a; padding: 15px; border-radius: 10px; margin-bottom: 10px;
            display: flex; justify-content: space-between; align-items: center;
            border-left: 3px solid #333; transition: 0.3s;
        }
        .stat-card:hover { background: #222; border-left-color: var(--royal-gold); }
        .stat-label { color: #aaa; font-size: 0.9rem; display: flex; align-items: center; gap: 10px; }
        .stat-value { color: #fff; font-weight: bold; font-size: 1.1rem; }

        .total-box {
            background: rgba(255, 215, 0, 0.05); border: 1px dashed var(--royal-gold);
            padding: 20px; border-radius: 12px; margin: 25px 0;
            text-align: center;
        }
        .total-label { color: var(--royal-gold); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; font-weight: bold; }
        .total-amount { font-size: 2.5rem; color: #fff; font-weight: 800; margin: 5px 0; }

        .input-group { position: relative; margin-top: 20px; }
        .input-real { 
            width: 100%; padding: 20px; background: #000; 
            border: 2px solid #444; color: #fff; font-size: 1.8rem; 
            text-align: center; border-radius: 12px; font-weight: bold;
            transition: 0.3s;
        }
        .input-real:focus { border-color: var(--royal-gold); outline: none; box-shadow: 0 0 20px rgba(255, 215, 0, 0.1); }
        
        .diff-badge {
            display: block; text-align: center; margin-top: 10px; font-size: 0.9rem; 
            font-weight: bold; opacity: 0; transition: 0.3s;
        }

        .btn-close {
            background: linear-gradient(45deg, #ef5350, #c62828);
            color: #fff; width: 100%; padding: 18px; border: none; border-radius: 12px;
            font-size: 1.1rem; font-weight: bold; cursor: pointer; text-transform: uppercase;
            margin-top: 20px; transition: 0.3s; letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(239, 83, 80, 0.3);
        }
        .btn-close:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(239, 83, 80, 0.5); }
    </style>
</head>
<body>

<div class="box-arqueo fade-in">
    <div style="text-align:center;">
        <div class="header-icon"><i class="fa-solid fa-vault"></i></div>
        <h2 style="color:#fff; margin:0;">Cierre de Caja</h2>
        <p style="color:#666; margin-top:5px; margin-bottom:30px;">Verifica los montos antes de finalizar.</p>
    </div>

    <div class="stat-card">
        <div class="stat-label"><i class="fa-solid fa-play"></i> Base Inicial</div>
        <div class="stat-value">S/ <?= number_format($monto_inicial, 2) ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label"><i class="fa-solid fa-money-bill-wave"></i> Ventas Efectivo</div>
        <div class="stat-value" style="color:#66bb6a;">+ S/ <?= number_format($ventas_efectivo, 2) ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label"><i class="fa-solid fa-qrcode"></i> Ventas Digitales</div>
        <div class="stat-value" style="color:#4fc3f7;">S/ <?= number_format($ventas_digital, 2) ?></div>
    </div>

    <div class="total-box">
        <div class="total-label">Debe haber en efectivo</div>
        <div class="total-amount">S/ <span id="txt-esperado"><?= number_format($total_esperado, 2) ?></span></div>
        <small style="color:#666;">(Base + Ventas Efectivo)</small>
    </div>

    <div class="input-group">
        <label style="display:block; text-align:center; color:#ccc; margin-bottom:10px;">
            <i class="fa-solid fa-hand-holding-dollar"></i> Ingresa tu conteo real:
        </label>
        <input type="number" step="0.01" id="monto_real" class="input-real" placeholder="0.00" oninput="calcularDiferencia()">
        <span id="diff-msg" class="diff-badge">---</span>
    </div>

    <button onclick="confirmarCierre()" class="btn-close">
        <i class="fa-solid fa-lock"></i> Cerrar Turno
    </button>
    
    <div style="text-align:center; margin-top:20px;">
        <a href="venta.php" style="color:#666; text-decoration:none; font-size:0.9rem;">
            <i class="fa-solid fa-arrow-left"></i> Regresar al POS
        </a>
    </div>
</div>

<script>
    const esperado = <?= $total_esperado ?>;

    function calcularDiferencia() {
        const input = document.getElementById('monto_real');
        const badge = document.getElementById('diff-msg');
        const valor = parseFloat(input.value);

        if (isNaN(valor)) {
            badge.style.opacity = '0';
            return;
        }

        const diff = valor - esperado;
        badge.style.opacity = '1';

        if (diff === 0) {
            badge.style.color = '#66bb6a';
            badge.innerHTML = '<i class="fa-solid fa-check-circle"></i> Caja Cuadrada (Perfecto)';
        } else if (diff > 0) {
            badge.style.color = '#4fc3f7';
            badge.innerHTML = `<i class="fa-solid fa-plus-circle"></i> Sobran S/ ${diff.toFixed(2)}`;
        } else {
            badge.style.color = '#ef5350';
            badge.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i> Faltan S/ ${Math.abs(diff).toFixed(2)}`;
        }
    }

    function confirmarCierre() {
        const monto = document.getElementById('monto_real').value;
        
        if(monto === '') {
            Swal.fire({
                icon: 'warning',
                title: 'Campo vacío',
                text: 'Por favor ingresa el dinero que contaste en caja.',
                background: '#1a1a1a', color: '#fff'
            });
            return;
        }

        Swal.fire({
            title: '¿Cerrar Caja?',
            text: "Esta acción finalizará tu turno y cerrará sesión.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef5350',
            cancelButtonColor: '#333',
            confirmButtonText: 'Sí, Cerrar',
            cancelButtonText: 'Cancelar',
            background: '#1a1a1a', color: '#fff'
        }).then((result) => {
            if (result.isConfirmed) {
                enviarCierre(monto);
            }
        });
    }

    function enviarCierre(monto) {
        Swal.fire({
            title: 'Procesando...',
            text: 'Guardando reporte de cierre',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading() },
            background: '#1a1a1a', color: '#fff'
        });

        fetch('cerrar_caja.php?action=cerrar', {
            method: 'POST',
            body: JSON.stringify({ monto_real: monto })
        })
        .then(r => r.json())
        .then(d => {
            if(d.success) {
                let msgExtra = "";
                if(d.diff < 0) msgExtra = " (Faltante: S/ " + Math.abs(d.diff).toFixed(2) + ")";
                if(d.diff > 0) msgExtra = " (Sobrante: S/ " + d.diff.toFixed(2) + ")";
                
                Swal.fire({
                    icon: 'success',
                    title: '¡Turno Cerrado!',
                    text: 'El sistema se reiniciará ahora.' + msgExtra,
                    timer: 3000,
                    showConfirmButton: false,
                    background: '#1a1a1a', color: '#fff'
                }).then(() => {
                    window.location.href = '../../index.php';
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: d.message,
                    background: '#1a1a1a', color: '#fff'
                });
            }
        });
    }
</script>

</body>
</html>