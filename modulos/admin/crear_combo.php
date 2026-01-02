<?php
require_once '../../config/db.php';
include '../../includes/header_admin.php';

$stmt = $pdo->query("SELECT id, nombre, codigo_barras FROM productos WHERE es_combo = 0 ORDER BY nombre ASC");
$productos = $stmt->fetchAll();
$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // (Misma lógica PHP de guardado del combo)
    try {
        $pdo->beginTransaction();
        $nombre_combo = $_POST['nombre_combo'];
        $codigo_combo = $_POST['codigo_combo'];
        $precio_venta = $_POST['precio_venta'];
        
        $sqlCombo = "INSERT INTO productos (codigo_barras, nombre, precio_venta, stock_actual, es_combo) VALUES (?, ?, ?, 0, 1)";
        $stmtC = $pdo->prepare($sqlCombo);
        $stmtC->execute([$codigo_combo, $nombre_combo, $precio_venta]);
        $id_combo = $pdo->lastInsertId();

        if (isset($_POST['insumo_id'])) {
            $insumos = $_POST['insumo_id'];
            $cantidades = $_POST['insumo_cant'];
            $sqlDetalle = "INSERT INTO combos_detalle (id_combo, id_producto, cantidad) VALUES (?, ?, ?)";
            $stmtD = $pdo->prepare($sqlDetalle);
            for ($i = 0; $i < count($insumos); $i++) {
                $stmtD->execute([$id_combo, $insumos[$i], $cantidades[$i]]);
            }
        }
        $pdo->commit();
        $mensaje = "<div style='background:rgba(102, 187, 106, 0.2); color:#66bb6a; border:1px solid #66bb6a; padding:15px; margin-bottom:20px; border-radius:10px;'>✅ Pack Creado Exitosamente.</div>";
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensaje = "<div style='background:rgba(239, 83, 80, 0.2); color:#ef5350; border:1px solid #ef5350; padding:15px; margin-bottom:20px; border-radius:10px;'>❌ Error: " . $e->getMessage() . "</div>";
    }
}
?>

<div class="fade-in" style="max-width: 900px; margin: 0 auto;">
    <h2 class="page-title">Diseñador de Packs</h2>
    <p style="color:#888;">Configura ofertas compuestas (Combos).</p>
    <?= $mensaje ?>

    <form method="POST" action="" id="formCombo">
        <div class="card">
            <h4 style="color:#FFD700; margin-bottom:20px; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:10px;">1. Identidad del Pack</h4>
            <div class="row">
                <div class="col">
                    <label>Nombre Comercial</label>
                    <input type="text" name="nombre_combo" required placeholder="Ej: Pack Fin de Semana">
                </div>
                <div class="col">
                    <label>Código Único</label>
                    <input type="text" name="codigo_combo" required placeholder="PACK-001">
                </div>
            </div>
            <div>
                <label style="color: #FFD700;">Precio Oferta ($)</label>
                <input type="number" step="0.01" name="precio_venta" required placeholder="0.00" style="font-size:1.2rem; font-weight:bold;">
            </div>
        </div>

        <div class="card">
            <h4 style="color:#FFD700; margin-bottom:20px; border-bottom:1px solid rgba(255,255,255,0.1); padding-bottom:10px;">2. Composición (Receta)</h4>
            
            <div style="display:flex; gap:10px; align-items:flex-end; background:#0a0a0a; padding:20px; border-radius:15px; border:1px solid #333;">
                <div style="flex:2;">
                    <label>Buscar Botella / Gaseosa</label>
                    <input list="lista_productos" id="input_producto" placeholder="Escribe para buscar..." style="margin-bottom:0;">
                    <datalist id="lista_productos">
                        <?php foreach($productos as $p): ?>
                            <option data-id="<?= $p['id'] ?>" value="<?= $p['nombre'] ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div style="flex:1;">
                    <label>Cantidad</label>
                    <input type="number" id="cantidad_insumo" value="1" min="1" style="margin-bottom:0;">
                </div>
                <button type="button" class="btn-royal" onclick="agregarInsumo()" style="width: auto; height: 50px; display:flex; align-items:center;">
                    <i class="fa-solid fa-plus"></i>
                </button>
            </div>

            <div style="margin-top:20px;">
                <table id="tabla_insumos_combo">
                    <thead>
                        <tr>
                            <th>Producto a descontar</th>
                            <th style="text-align:center;">Unidades</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="tabla_insumos">
                        </tbody>
                </table>
                <div id="empty-msg" style="text-align:center; padding:30px; color:#444;">
                    <i class="fa-solid fa-basket-shopping" style="font-size:2rem; margin-bottom:10px;"></i>
                    <p>No hay productos agregados aún</p>
                </div>
            </div>
        </div>

        <button type="submit" class="btn-royal" style="padding: 20px; font-size:1.1rem;">
            <i class="fa-solid fa-check-circle"></i> CREAR PACK AHORA
        </button>
    </form>
</div>

<script>
    function agregarInsumo() {
        const inputVal = document.getElementById('input_producto').value;
        const cantidad = document.getElementById('cantidad_insumo').value;
        const list = document.getElementById('lista_productos');
        let idProducto = '';

        for (let i = 0; i < list.options.length; i++) {
            if (list.options[i].value === inputVal) {
                idProducto = list.options[i].getAttribute('data-id');
                break;
            }
        }

        if(idProducto === '') return;

        // Ocultar mensaje vacío
        document.getElementById('empty-msg').style.display = 'none';

        const tabla = document.getElementById('tabla_insumos');
        const fila = document.createElement('tr');
        
        fila.innerHTML = `
            <td style="color:#fff; font-weight:500;">
                ${inputVal}
                <input type="hidden" name="insumo_id[]" value="${idProducto}">
            </td>
            <td style="text-align:center; color:#FFD700; font-weight:bold;">
                ${cantidad}
                <input type="hidden" name="insumo_cant[]" value="${cantidad}">
            </td>
            <td style="text-align:right;">
                <button type="button" onclick="this.closest('tr').remove()" style="background:none; border:none; color:#ef5350; cursor:pointer; font-size:1.2rem;">
                    <i class="fa-solid fa-times"></i>
                </button>
            </td>
        `;
        tabla.appendChild(fila);
        document.getElementById('input_producto').value = '';
        document.getElementById('input_producto').focus();
    }
</script>

<?php include '../../includes/footer_admin.php'; ?>