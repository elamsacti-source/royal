<?php
session_start();
require_once '../../config/db.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../../index.php"); exit; }
include '../../includes/header_admin.php';

$fecha_inicio = $_GET['inicio'] ?? date('Y-m-d');
$fecha_fin    = $_GET['fin'] ?? date('Y-m-d');
$usuario_id   = $_GET['usuario'] ?? '';

$sql = "SELECT v.id, v.fecha, v.total, v.metodo_pago, u.nombre as cajero 
        FROM ventas v 
        JOIN caja_sesiones c ON v.id_caja_sesion = c.id 
        JOIN usuarios u ON c.id_usuario = u.id 
        WHERE DATE(v.fecha) BETWEEN ? AND ?";
$params = [$fecha_inicio, $fecha_fin];

if ($usuario_id != '') { $sql .= " AND u.id = ?"; $params[] = $usuario_id; }
$sql .= " ORDER BY v.fecha DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ventas = $stmt->fetchAll();
$total_periodo = array_sum(array_column($ventas, 'total'));
$usuarios = $pdo->query("SELECT id, nombre FROM usuarios")->fetchAll();
?>

<style>
    .page-title { color: var(--royal-gold, #FFD700); text-align: center; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px; }
    .card { background: #1a1a1a; padding: 25px; border-radius: 10px; border: 1px solid #333; margin-bottom: 20px; }
    .filter-bar { display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; background: #111; padding: 20px; border-radius: 10px; border: 1px solid #333; margin-bottom: 20px; }
    .filter-group input, .filter-group select { width: 100%; padding: 10px; background: #222; border: 1px solid #444; color: #fff; border-radius: 5px; }
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; color: #888; padding: 15px; border-bottom: 1px solid #333; }
    td { padding: 15px; border-bottom: 1px solid #222; color: #eee; }
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center; }
    .modal-content { background: #1a1a1a; width: 90%; max-width: 500px; border-radius: 10px; border: 1px solid var(--royal-gold); }
</style>

<div class="fade-in" style="max-width: 1000px; margin: auto; padding-top: 20px;">
    <h2 class="page-title"><i class="fa-solid fa-clock-rotate-left"></i> Historial de Ventas</h2>

    <form method="GET" class="filter-bar">
        <div class="filter-group"><label>Inicio</label><input type="date" name="inicio" value="<?= $fecha_inicio ?>"></div>
        <div class="filter-group"><label>Fin</label><input type="date" name="fin" value="<?= $fecha_fin ?>"></div>
        <div class="filter-group"><label>Cajero</label>
            <select name="usuario"><option value="">Todos</option>
                <?php foreach($usuarios as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $usuario_id == $u['id'] ? 'selected' : '' ?>><?= $u['nombre'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn-royal" style="height: 42px;">Filtrar</button>
    </form>

    <div style="display:flex; justify-content:flex-end; margin-bottom:15px;">
        <div style="background:#222; padding:10px 20px; border-radius:50px; border:1px solid var(--royal-gold); color:var(--royal-gold); font-weight:bold;">
            Total Periodo: S/ <?= number_format($total_periodo, 2) ?>
        </div>
    </div>

    <div class="card" style="overflow-x:auto;">
        <table>
            <thead><tr><th>Ticket #</th><th>Fecha</th><th>Cajero</th><th>Pago</th><th style="text-align:right;">Total</th><th></th></tr></thead>
            <tbody>
                <?php foreach($ventas as $v): ?>
                <tr>
                    <td style="font-family:monospace; color:#888;">#<?= str_pad($v['id'], 6, '0', STR_PAD_LEFT) ?></td>
                    <td><?= date('d/m/Y h:i A', strtotime($v['fecha'])) ?></td>
                    <td><?= $v['cajero'] ?></td>
                    <td><?= $v['metodo_pago'] ?></td>
                    <td style="text-align:right; font-weight:bold; color:#fff;">S/ <?= number_format($v['total'], 2) ?></td>
                    <td style="text-align:center;"><button onclick="verDetalle(<?= $v['id'] ?>)" class="btn-royal" style="padding:5px 10px; font-size:0.8rem;"><i class="fa-solid fa-eye"></i></button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="modalDetalle" class="modal-overlay" onclick="if(event.target==this)cerrarModal()">
    <div class="modal-content">
        <div style="padding:15px; border-bottom:1px solid #333; display:flex; justify-content:space-between;">
            <h3 style="color:#fff; margin:0;">Ticket #<span id="lblTicket"></span></h3>
            <button onclick="cerrarModal()" style="background:none; border:none; color:#ef5350; font-size:1.5rem;">&times;</button>
        </div>
        <div id="cuerpoDetalle" style="padding:20px;"></div>
    </div>
</div>

<script>
    function verDetalle(id) {
        document.getElementById('modalDetalle').style.display = 'flex';
        document.getElementById('lblTicket').innerText = id.toString().padStart(6, '0');
        fetch('ajax_detalle_venta.php?id=' + id).then(r=>r.text()).then(h=>{ document.getElementById('cuerpoDetalle').innerHTML = h; });
    }
    function cerrarModal() { document.getElementById('modalDetalle').style.display = 'none'; }
</script>
<?php include '../../includes/footer_admin.php'; ?>