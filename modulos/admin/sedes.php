<?php
require_once '../../config/db.php';
include '../../includes/header_admin.php';

$mensaje = "";

// GUARDAR NUEVA SEDE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'guardar') {
    $nombre = trim($_POST['nombre']);
    $tipo = $_POST['tipo'];
    
    if(!empty($nombre)){
        $stmt = $pdo->prepare("INSERT INTO sedes (nombre, tipo) VALUES (?, ?)");
        if($stmt->execute([$nombre, $tipo])){
            $mensaje = "<div style='color:#66bb6a; margin-bottom:15px;'>✅ Sede creada correctamente.</div>";
        }
    }
}

// BORRAR SEDE (Solo si no tiene stock)
if (isset($_GET['borrar'])) {
    $id_borrar = $_GET['borrar'];
    // Validar si tiene stock
    $check = $pdo->prepare("SELECT SUM(stock) FROM productos_sedes WHERE id_sede = ?");
    $check->execute([$id_borrar]);
    if($check->fetchColumn() > 0){
        $mensaje = "<div style='color:#ef5350; margin-bottom:15px;'>⚠️ No se puede borrar: La sede tiene productos en stock.</div>";
    } else {
        $pdo->prepare("DELETE FROM sedes WHERE id = ?")->execute([$id_borrar]);
        $mensaje = "<div style='color:#ef5350; margin-bottom:15px;'>🗑️ Sede eliminada.</div>";
    }
}

$sedes = $pdo->query("SELECT * FROM sedes ORDER BY id ASC")->fetchAll();
?>

<div class="fade-in" style="max-width: 800px; margin: 0 auto;">
    <h2 class="page-title">Gestión de Sedes</h2>
    <p style="color:#888;">Administra tus almacenes y puntos de venta.</p>
    <?= $mensaje ?>

    <div class="card">
        <form method="POST" style="display:flex; gap:15px; align-items:flex-end;">
            <input type="hidden" name="accion" value="guardar">
            <div style="flex:2;">
                <label>Nombre de la Sede</label>
                <input type="text" name="nombre" required placeholder="Ej: Tienda Playa" style="margin-bottom:0;">
            </div>
            <div style="flex:1;">
                <label>Tipo</label>
                <select name="tipo" style="margin-bottom:0;">
                    <option value="tienda">Tienda (Ventas)</option>
                    <option value="almacen">Almacén (Depósito)</option>
                </select>
            </div>
            <button type="submit" class="btn-royal" style="width:auto; height:50px;">
                <i class="fa-solid fa-plus"></i> Crear
            </button>
        </form>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($sedes as $s): ?>
                <tr>
                    <td style="color:#666;">#<?= $s['id'] ?></td>
                    <td style="font-weight:bold; color:#fff;"><?= $s['nombre'] ?></td>
                    <td>
                        <?php if($s['tipo']=='almacen'): ?>
                            <span class="badge badge-warning">Almacén</span>
                        <?php else: ?>
                            <span class="badge badge-success">Tienda</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($s['id'] > 2): // Protegemos las 2 principales ?>
                            <a href="?borrar=<?= $s['id'] ?>" onclick="return confirm('¿Borrar esta sede?')" style="color:#ef5350;">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        <?php else: ?>
                            <small style="color:#444;">(Sistema)</small>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../../includes/footer_admin.php'; ?>