<?php
// royal/modulos/admin/productos_lista.php
require_once '../../config/db.php';
include '../../includes/header_admin.php';

$stmtSedes = $pdo->query("SELECT id, nombre FROM sedes ORDER BY id ASC");
$sedes = $stmtSedes->fetchAll();

// AGREGAMOS precio_caja Y unidades_caja A LA CONSULTA
$sql = "SELECT p.id, p.codigo_barras, p.nombre, p.precio_venta, p.es_combo, p.categoria,
        p.precio_caja, p.unidades_caja,
        (SELECT SUM(stock) FROM productos_sedes WHERE id_producto = p.id) as stock_total
        FROM productos p 
        ORDER BY p.nombre ASC";
$productos = $pdo->query($sql)->fetchAll();
?>

<div class="fade-in">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
        <div>
            <h2 class="page-title">Inventario Global</h2>
            <p style="color:#888;">Vista panorámica de stock y precios.</p>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="sedes.php" class="btn-royal" style="background:#333; color:#fff; width:auto; font-size:0.9rem;">
                <i class="fa-solid fa-store"></i> Sedes
            </a>
            <a href="productos_nuevo.php" class="btn-royal" style="width:auto; font-size:0.9rem;">
                <i class="fa-solid fa-plus"></i> Nuevo
            </a>
        </div>
    </div>

    <div class="card" style="overflow-x:auto;">
        
        <div style="margin-bottom:20px; position:relative;">
            <i class="fa-solid fa-search" style="position:absolute; left:15px; top:15px; color:#666;"></i>
            <input type="text" id="buscador" placeholder="Buscar..." style="padding-left:45px; background:#111; margin-bottom:0;">
        </div>

        <table id="tablaProductos" style="min-width: 1000px;">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Total Global</th>
                    <?php foreach($sedes as $s): ?>
                        <th style="color:var(--royal-gold);"><?= $s['nombre'] ?></th>
                    <?php endforeach; ?>
                    <th>Precios (S/)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($productos as $p): ?>
                    <tr>
                        <td style="font-family:monospace; color:#888;"><?= $p['codigo_barras'] ?></td>
                        <td>
                            <div style="font-weight:600; color:#fff;"><?= $p['nombre'] ?></div>
                            <?php if($p['es_combo']): ?><span class="badge badge-warning">Pack</span><?php endif; ?>
                        </td>
                        
                        <td style="font-weight:bold; font-size:1.1rem;">
                            <?= $p['es_combo'] ? '-' : ($p['stock_total'] ?: 0) ?>
                        </td>

                        <?php foreach($sedes as $s): ?>
                            <?php
                                $stmtStock = $pdo->prepare("SELECT stock FROM productos_sedes WHERE id_producto = ? AND id_sede = ?");
                                $stmtStock->execute([$p['id'], $s['id']]);
                                $stockSede = $stmtStock->fetchColumn() ?: 0;
                                $color = ($stockSede > 0) ? '#66bb6a' : '#444';
                            ?>
                            <td style="color:<?= $color ?>; font-weight:bold;">
                                <?= $p['es_combo'] ? '' : $stockSede ?>
                            </td>
                        <?php endforeach; ?>

                        <td>
                            <div style="font-weight:bold; color:#fff;">S/ <?= number_format($p['precio_venta'], 2) ?></div>
                            
                            <?php if($p['unidades_caja'] > 1): ?>
                                <div style="font-size:0.75rem; color:#FFD700; margin-top:2px;">
                                    <i class="fa-solid fa-box"></i> x<?= $p['unidades_caja'] ?>: S/ <?= number_format($p['precio_caja'], 2) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('buscador').addEventListener('keyup', function() {
    let filtro = this.value.toLowerCase();
    document.querySelectorAll('#tablaProductos tbody tr').forEach(fila => {
        let texto = fila.innerText.toLowerCase();
        fila.style.display = texto.includes(filtro) ? '' : 'none';
    });
});
</script>

<?php include '../../includes/footer_admin.php'; ?>