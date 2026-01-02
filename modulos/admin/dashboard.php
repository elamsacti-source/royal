<?php
require_once '../../config/db.php';
include '../../includes/header_admin.php';

// --- CONSULTAS DE DATOS --- //

// 1. Total Productos Físicos
$stmt = $pdo->query("SELECT COUNT(*) FROM productos WHERE es_combo = 0");
$total_productos = $stmt->fetchColumn();

// 2. Valor Inventario Total
$stmt = $pdo->query("SELECT SUM(precio_compra * stock_actual) FROM productos");
$valor_inventario = $stmt->fetchColumn() ?: 0;

// 3. Productos con Stock Bajo (Crítico < 5)
$stmt = $pdo->query("SELECT COUNT(*) FROM productos WHERE stock_actual <= 5 AND es_combo = 0");
$stock_bajo = $stmt->fetchColumn();

// 4. Total Packs Creados
$stmt = $pdo->query("SELECT COUNT(*) FROM productos WHERE es_combo = 1");
$total_packs = $stmt->fetchColumn();

// 5. Ventas del Día (Ejemplo con fecha de hoy)
$hoy = date('Y-m-d');
$stmt = $pdo->query("SELECT SUM(total) FROM ventas WHERE DATE(fecha) = '$hoy'");
$venta_dia = $stmt->fetchColumn() ?: 0;
?>

<div class="fade-in">
    <div style="margin-bottom:40px;">
        <h2 class="page-title">Panel General</h2>
        <p style="color:#888;">Bienvenido al centro de mando Royal.</p>
    </div>

    <div class="stat-grid">
        
        <div class="stat-card">
            <div class="stat-info">
                <span>Ventas del Día</span>
                <h3 style="color: #FFD700;">$<?= number_format($venta_dia, 2) ?></h3>
            </div>
            <div class="stat-icon"><i class="fa-solid fa-cash-register"></i></div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <span>Dinero en Licor</span>
                <h3>$<?= number_format($valor_inventario, 0) ?></h3>
            </div>
            <div class="stat-icon"><i class="fa-solid fa-sack-dollar"></i></div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <span>Estado de Caja</span>
                <h3 style="color: #66bb6a;">Abierta</h3>
            </div>
            <div class="stat-icon" style="color:#66bb6a; background:rgba(102,187,106,0.1);"><i class="fa-solid fa-unlock"></i></div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <span>Botellas Únicas</span>
                <h3><?= $total_productos ?></h3>
            </div>
            <div class="stat-icon"><i class="fa-solid fa-wine-bottle"></i></div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <span>Combos Activos</span>
                <h3><?= $total_packs ?></h3>
            </div>
            <div class="stat-icon"><i class="fa-solid fa-gift"></i></div>
        </div>

        <div class="stat-card" style="<?= ($stock_bajo > 0) ? 'border-color:#ef5350;' : '' ?>">
            <div class="stat-info">
                <span>Stock Crítico</span>
                <h3 style="color: <?= ($stock_bajo > 0) ? '#ef5350' : '#66bb6a' ?>;">
                    <?= $stock_bajo ?> <small style="font-size:0.5em; color:#888;">Items</small>
                </h3>
            </div>
            <div class="stat-icon" style="<?= ($stock_bajo > 0) ? 'color:#ef5350; background:rgba(239,83,80,0.1);' : '' ?>">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
        </div>

    </div>

    <h3 style="color:#fff; margin-bottom:20px; font-weight:300;">Acciones Rápidas</h3>
    <div style="display:flex; gap:20px; flex-wrap:wrap;">
        <a href="productos_nuevo.php" class="card" style="flex:1; text-align:center; padding:30px; text-decoration:none; color:#fff; transition:0.3s; margin-bottom:0; display:block;">
            <i class="fa-solid fa-plus-circle" style="font-size:2rem; color:#FFD700; margin-bottom:15px;"></i>
            <h4 style="margin:0;">Nueva Botella</h4>
        </a>
        <a href="crear_combo.php" class="card" style="flex:1; text-align:center; padding:30px; text-decoration:none; color:#fff; transition:0.3s; margin-bottom:0; display:block;">
            <i class="fa-solid fa-gifts" style="font-size:2rem; color:#FFD700; margin-bottom:15px;"></i>
            <h4 style="margin:0;">Crear Combo</h4>
        </a>
        <a href="kardex.php" class="card" style="flex:1; text-align:center; padding:30px; text-decoration:none; color:#fff; transition:0.3s; margin-bottom:0; display:block;">
            <i class="fa-solid fa-list-check" style="font-size:2rem; color:#FFD700; margin-bottom:15px;"></i>
            <h4 style="margin:0;">Ver Kardex</h4>
        </a>
    </div>

</div>

<?php include '../../includes/footer_admin.php'; ?>