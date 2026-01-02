<?php
session_start();
require_once '../../config/db.php';
// Seguridad
if (!isset($_SESSION['user_id'])) { header("Location: ../../index.php"); exit; }
include '../../includes/header_admin.php';

// --- FILTROS ---
$fecha_inicio = $_GET['inicio'] ?? date('Y-m-d');
$fecha_fin    = $_GET['fin'] ?? date('Y-m-d');
$usuario_id   = $_GET['usuario'] ?? '';

// --- CONSULTA DE VENTAS ---
$sql = "SELECT v.id, v.fecha, v.total, v.metodo_pago, u.nombre as cajero 
        FROM ventas v 
        JOIN caja_sesiones c ON v.id_caja_sesion = c.id 
        JOIN usuarios u ON c.id_usuario = u.id 
        WHERE DATE(v.fecha) BETWEEN ? AND ?";
$params = [$fecha_inicio, $fecha_fin];

if ($usuario_id != '') {
    $sql .= " AND u.id = ?";
    $params[] = $usuario_id;
}
$sql .= " ORDER BY v.fecha DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ventas = $stmt->fetchAll();

// Calcular total del periodo
$total_periodo = array_sum(array_column($ventas, 'total'));

// Obtener lista de usuarios para el filtro
$usuarios = $pdo->query("SELECT id, nombre FROM usuarios")->fetchAll();
?>

<style>
    .page-title { color: var(--royal-gold, #FFD700); text-align: center; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px; }
    .card { background: #1a1a1a; padding: 25px; border-radius: 10px; border: 1px solid #333; margin-bottom: 20px; }
    
    /* Filtros */
    .filter-bar { display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; background: #111; padding: 20px; border-radius: 10px; border: 1px solid #333; margin-bottom: 20px; }
    .filter-group { flex: 1; min-width: 150px; }
    .filter-group label { display: block; color: #888; font-size: 0.85rem; margin-bottom: 5px; }
    .filter-group input, .filter-group select { width: 100%; padding: 10px; background: #222; border: 1px solid #444; color: #fff; border-radius: 5px; margin-bottom: 0; }
    
    /* Tabla */
    .table-container { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; color: #888; padding: 15px; border-bottom: 1px solid #333; font-size: 0.9rem; }
    td { padding: 15px; border-bottom: 1px solid #222; color: #eee; }
    tr:hover td { background: #1f1f1f; }

    /* Modal Detalle */
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center; }
    .modal-content { background: #1a1a1a; width: 90%; max-width: 500px; border-radius: 10px; border: 1px solid var(--royal-gold); overflow: hidden; animation: fadeIn 0.3s; }
    .modal-header { background: #000; padding: 15px 20px; border-bottom: 1px solid #333; display: flex; justify-content: space-between; align-items: center; }
    .modal-body { padding: 20px; max-height: 60vh; overflow-y: auto; }
    .modal-footer { padding: 15px; background: #111; text-align: right; border-top: 1px solid #333; }
    
    .item-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #333; font-size: 0.95rem; }
    .item-row:last-child { border-bottom: none; }
    .total-row { display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: bold; color: var(--royal-gold); margin-top: 15px; padding-top: 10px; border-top: 2px solid #333; }
</style>

<div class="fade-in" style="max-width: 1000px; margin: auto; padding-top: 20px;">
    
    <h2 class="page-title"><i class="fa-solid fa-clock-rotate-left"></i> Historial de Ventas</h2>

    <form method="GET" class="filter-bar">
        <div class="filter-group">
            <label>Fecha Inicio</label>
            <input type="date" name="inicio" value="<?= $fecha_inicio ?>">
        </div>
        <div class="filter-group">
            <label>Fecha Fin</label>
            <input type="date" name="fin" value="<?= $fecha_fin ?>">
        </div>
        <div class="filter-group">
            <label>Cajero</label>
            <select name="usuario">
                <option value="">Todos</option>
                <?php foreach($usuarios as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $usuario_id == $u['id'] ? 'selected' : '' ?>>
                        <?= $u['nombre'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn-royal" style="width: auto; height: 42px; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-filter"></i> Filtrar
        </button>
    </form>

    <div style="display:flex; justify-content:flex-end; margin-bottom:15px;">
        <div style="background:#222; padding:10px 20px; border-radius:50px; border:1px solid var(--royal-gold); color:var(--royal-gold); font-weight:bold;">
            Total Periodo: $<?= number_format($total_periodo, 2) ?>
        </div>
    </div>

    <div class="card table-container">
        <table>
            <thead>
                <tr>
                    <th>Ticket #</th>
                    <th>Fecha / Hora</th>
                    <th>Cajero</th>
                    <th>Método Pago</th>
                    <th style="text-align:right;">Total</th>
                    <th style="text-align:center;">Detalle</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($ventas) > 0): ?>
                    <?php foreach($ventas as $v): ?>
                    <tr>
                        <td style="font-family:monospace; color:#888;">#<?= str_pad($v['id'], 6, '0', STR_PAD_LEFT) ?></td>
                        <td><?= date('d/m/Y h:i A', strtotime($v['fecha'])) ?></td>
                        <td><?= $v['cajero'] ?></td>
                        <td>
                            <?php 
                                $icon = 'fa-money-bill';
                                if($v['metodo_pago']=='Yape') $icon = 'fa-qrcode';
                                if($v['metodo_pago']=='Tarjeta') $icon = 'fa-credit-card';
                            ?>
                            <i class="fa-solid <?= $icon ?>"></i> <?= $v['metodo_pago'] ?>
                        </td>
                        <td style="text-align:right; font-weight:bold; color:#fff;">$<?= number_format($v['total'], 2) ?></td>
                        <td style="text-align:center;">
                            <button onclick="verDetalle(<?= $v['id'] ?>)" class="btn-royal" style="width:auto; padding:5px 15px; font-size:0.8rem; height:auto; min-width:auto;">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center; padding:30px; color:#666;">No hay ventas en este rango de fechas.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<div id="modalDetalle" class="modal-overlay" onclick="if(event.target == this) cerrarModal()">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="color:#fff; margin:0;">Ticket #<span id="lblTicket"></span></h3>
            <button onclick="cerrarModal()" style="background:none; border:none; color:#ef5350; cursor:pointer; font-size:1.5rem;">&times;</button>
        </div>
        <div class="modal-body" id="cuerpoDetalle">
            <p style="text-align:center; color:#888;">Cargando...</p>
        </div>
        <div class="modal-footer">
            <button onclick="cerrarModal()" style="background:#333; color:#fff; border:none; padding:10px 20px; border-radius:5px; cursor:pointer;">Cerrar</button>
        </div>
    </div>
</div>

<script>
    function verDetalle(idVenta) {
        document.getElementById('modalDetalle').style.display = 'flex';
        document.getElementById('lblTicket').innerText = idVenta.toString().padStart(6, '0');
        document.getElementById('cuerpoDetalle').innerHTML = '<p style="text-align:center; color:#888;"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</p>';

        // Petición AJAX para obtener el detalle (Crearemos un pequeño backend para esto)
        fetch('ajax_detalle_venta.php?id=' + idVenta)
            .then(res => res.text())
            .then(html => {
                document.getElementById('cuerpoDetalle').innerHTML = html;
            });
    }

    function cerrarModal() {
        document.getElementById('modalDetalle').style.display = 'none';
    }
</script>

<?php include '../../includes/footer_admin.php'; ?>