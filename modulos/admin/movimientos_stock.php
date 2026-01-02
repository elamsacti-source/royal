<?php
session_start();
require_once '../../config/db.php';

// Seguridad
if (!isset($_SESSION['user_id'])) { header("Location: ../../index.php"); exit; }

include '../../includes/header_admin.php';

$mensaje = "";

// ---------------------------------------------------------
// LÓGICA DE PROCESAMIENTO
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sede_id     = $_POST['sede'];
    $tipo_mov    = $_POST['tipo_movimiento']; // 'compra', 'entrada_ajuste', 'salida_ajuste', 'siniestro'
    $prod_id     = $_POST['producto'];
    $cantidad    = (float) $_POST['cantidad'];
    $costo_nuevo = isset($_POST['costo']) ? (float) $_POST['costo'] : 0;

    if ($cantidad <= 0) {
        $mensaje = "<div class='alert alert-error'>⚠️ La cantidad debe ser mayor a 0.</div>";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. OBTENER DATOS ACTUALES DEL PRODUCTO Y STOCK
            $stmtProd = $pdo->prepare("SELECT costo_compra FROM productos WHERE id = ?");
            $stmtProd->execute([$prod_id]);
            $prodData = $stmtProd->fetch();
            $costoActual = $prodData['costo_compra'];

            // Obtener stock actual en esa sede (para calcular ponderado o validar resta)
            $stmtStock = $pdo->prepare("SELECT stock FROM productos_sedes WHERE id_producto = ? AND id_sede = ? FOR UPDATE");
            $stmtStock->execute([$prod_id, $sede_id]);
            $stockData = $stmtStock->fetch();
            
            // Si no existe fila en esa sede, asumimos stock 0
            if (!$stockData) {
                $pdo->prepare("INSERT INTO productos_sedes (id_producto, id_sede, stock) VALUES (?, ?, 0)")->execute([$prod_id, $sede_id]);
                $stockEnSede = 0;
            } else {
                $stockEnSede = (float) $stockData['stock'];
            }

            // 2. DETERMINAR SI SUMA O RESTA
            $esEntrada = ($tipo_mov == 'compra' || $tipo_mov == 'entrada_ajuste');
            
            // VALIDACIÓN PARA SALIDAS
            if (!$esEntrada && $stockEnSede < $cantidad) {
                throw new Exception("Stock insuficiente. Tienes $stockEnSede, intentas sacar $cantidad.");
            }

            // 3. CÁLCULO DE VALORIZACIÓN (SOLO EN COMPRAS)
            // Usamos Promedio Ponderado: ((StockActual * CostoAntiguo) + (CantidadNueva * CostoNuevo)) / (StockTotalNuevo)
            if ($tipo_mov == 'compra') {
                // Calcular stock TOTAL de la empresa para el costo promedio (o puedes hacerlo por sede si prefieres)
                // Usualmente el costo base es global.
                $stmtGlobal = $pdo->prepare("SELECT SUM(stock) as total FROM productos_sedes WHERE id_producto = ?");
                $stmtGlobal->execute([$prod_id]);
                $stockGlobal = $stmtGlobal->fetchColumn() ?: 0;

                $valorTotalAntiguo = $stockGlobal * $costoActual;
                $valorTotalNuevo   = $cantidad * $costo_nuevo;
                $nuevoStockGlobal  = $stockGlobal + $cantidad;

                if ($nuevoStockGlobal > 0) {
                    $nuevoCostoPonderado = ($valorTotalAntiguo + $valorTotalNuevo) / $nuevoStockGlobal;
                } else {
                    $nuevoCostoPonderado = $costo_nuevo;
                }

                // Actualizamos el costo maestro del producto
                $pdo->prepare("UPDATE productos SET costo_compra = ? WHERE id = ?")->execute([$nuevoCostoPonderado, $prod_id]);
                
                // Para el registro del kardex usamos el costo de esta compra específica
                $costoRegistro = $costo_nuevo;
            } else {
                // Si es salida o ajuste simple, usamos el costo que ya tenía
                $costoRegistro = $costoActual;
            }

            // 4. ACTUALIZAR STOCK EN SEDE
            if ($esEntrada) {
                $sqlStock = "UPDATE productos_sedes SET stock = stock + ? WHERE id_producto = ? AND id_sede = ?";
                $factor = 1; // Para Kardex
            } else {
                $sqlStock = "UPDATE productos_sedes SET stock = stock - ? WHERE id_producto = ? AND id_sede = ?";
                $factor = -1; // Para Kardex
            }
            $pdo->prepare($sqlStock)->execute([$cantidad, $prod_id, $sede_id]);

            // 5. INSERTAR EN KARDEX
            $stockResultante = $stockEnSede + ($cantidad * $factor);
            
            $sqlKardex = "INSERT INTO kardex (id_producto, id_sede, tipo_movimiento, cantidad, costo_unitario, costo_total, stock_resultante, nota, fecha) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            // Nota automática
            $nota = ucfirst(str_replace('_', ' ', $tipo_mov));
            
            $pdo->prepare($sqlKardex)->execute([
                $prod_id, 
                $sede_id, 
                $tipo_mov, 
                ($cantidad * $factor), // Guardamos negativo si es salida
                $costoRegistro, 
                ($costoRegistro * $cantidad), 
                $stockResultante, 
                $nota
            ]);

            $pdo->commit();
            $mensaje = "<div class='alert alert-success'>✅ Movimiento registrado. Stock actualizado.</div>";

        } catch (Exception $e) {
            $pdo->rollBack();
            $mensaje = "<div class='alert alert-error'>❌ Error: " . $e->getMessage() . "</div>";
        }
    }
}

// CARGA DE DATOS
$sedes = $pdo->query("SELECT * FROM sedes")->fetchAll();
$productos = $pdo->query("SELECT id, nombre, codigo_barras, costo_compra FROM productos ORDER BY nombre")->fetchAll();

// Array JS para mostrar costo actual al seleccionar
$prodJson = [];
foreach($productos as $p) {
    $prodJson[$p['id']] = ['nombre'=>$p['nombre'], 'costo'=>$p['costo_compra']];
}
?>

<style>
    .page-title { color: var(--royal-gold, #FFD700); text-align: center; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px; }
    .card { background: #1a1a1a; padding: 25px; border-radius: 10px; border: 1px solid #333; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
    .form-control {
        width: 100%; padding: 12px; background: #2a2a2a; border: 1px solid #444; 
        color: #fff; border-radius: 5px; margin-bottom: 15px; font-size: 1rem;
    }
    .form-control:focus { outline: none; border-color: var(--royal-gold, #FFD700); }
    
    .btn-royal {
        background: linear-gradient(45deg, #b7892b, #FFD700); color: #000; font-weight: bold;
        border: none; padding: 15px; width: 100%; border-radius: 5px; cursor: pointer;
        font-size: 1rem; text-transform: uppercase; transition: 0.3s;
    }
    .btn-royal:hover { transform: scale(1.02); box-shadow: 0 0 15px rgba(255, 215, 0, 0.4); }

    .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center; font-weight: bold; }
    .alert-success { background: rgba(46, 125, 50, 0.2); color: #81c784; border: 1px solid #2e7d32; }
    .alert-error { background: rgba(198, 40, 40, 0.2); color: #e57373; border: 1px solid #c62828; }
    
    /* Input especial para dinero */
    .input-group { position: relative; }
    .input-icon { position: absolute; left: 10px; top: 12px; color: #888; }
    .input-money { padding-left: 35px; }
</style>

<div class="fade-in" style="max-width:600px; margin:auto; padding-top:20px;">
    
    <h2 class="page-title"><i class="fa-solid fa-boxes-packing"></i> Movimientos de Inventario</h2>
    
    <?= $mensaje ?>

    <div class="card">
        <form method="POST" id="movForm">
            
            <label style="color:#aaa;">Sede Afectada</label>
            <select name="sede" class="form-control" required>
                <?php foreach($sedes as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= $s['nombre'] ?></option>
                <?php endforeach; ?>
            </select>

            <label style="color:#aaa;">Tipo de Movimiento</label>
            <select name="tipo_movimiento" id="tipo_movimiento" class="form-control" onchange="toggleCosto()" required>
                <option value="compra">🟢 COMPRA (Ingreso de Mercadería)</option>
                <option value="entrada_ajuste">🟡 AJUSTE POSITIVO (Sobrante)</option>
                <option value="salida_ajuste">🟠 AJUSTE NEGATIVO (Corrección)</option>
                <option value="siniestro">🔴 SINIESTRO / MERMA / ROBO</option>
            </select>

            <label style="color:#aaa;">Producto</label>
            <select name="producto" id="producto" class="form-control" onchange="mostrarInfoProducto()" required>
                <option value="">-- Seleccione --</option>
                <?php foreach($productos as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= $p['nombre'] ?></option>
                <?php endforeach; ?>
            </select>

            <div id="infoCosto" style="margin-bottom:15px; font-size:0.9rem; color:#888; display:none;">
                Costo Promedio Actual: <span id="txtCosto" style="color:#fff; font-weight:bold;">S/ 0.00</span>
            </div>

            <label style="color:#aaa;">Cantidad</label>
            <input type="number" name="cantidad" step="0.01" class="form-control" placeholder="0" required>

            <div id="divCosto" style="display:block;">
                <label style="color:var(--royal-gold);">Nuevo Costo de Compra (Unitario)</label>
                <div class="input-group">
                    <span class="input-icon">S/</span>
                    <input type="number" name="costo" id="inputCosto" step="0.01" class="form-control input-money" placeholder="0.00">
                </div>
                <small style="color:#666;">* Esto recalculará el costo promedio del inventario.</small>
            </div>

            <button type="submit" class="btn-royal" style="margin-top:20px;">
                <i class="fa-solid fa-save"></i> GUARDAR MOVIMIENTO
            </button>
        </form>
    </div>
</div>

<script>
    // Datos de productos para JS
    const productosData = <?= json_encode($prodJson) ?>;

    function toggleCosto() {
        const tipo = document.getElementById('tipo_movimiento').value;
        const divCosto = document.getElementById('divCosto');
        const inputCosto = document.getElementById('inputCosto');

        // Solo pedimos costo si es una COMPRA
        if (tipo === 'compra') {
            divCosto.style.display = 'block';
            inputCosto.required = true;
        } else {
            divCosto.style.display = 'none';
            inputCosto.required = false;
            inputCosto.value = '';
        }
    }

    function mostrarInfoProducto() {
        const id = document.getElementById('producto').value;
        const infoDiv = document.getElementById('infoCosto');
        const txt = document.getElementById('txtCosto');
        const inputCosto = document.getElementById('inputCosto');

        if (id && productosData[id]) {
            const costo = parseFloat(productosData[id].costo).toFixed(2);
            txt.innerText = 'S/ ' + costo;
            infoDiv.style.display = 'block';
            
            // Si el usuario selecciona compra, sugerimos el último costo
            if(document.getElementById('tipo_movimiento').value === 'compra') {
                inputCosto.value = costo;
            }
        } else {
            infoDiv.style.display = 'none';
        }
    }
    
    // Inicializar estado
    toggleCosto();
</script>

<?php include '../../includes/footer_admin.php'; ?>