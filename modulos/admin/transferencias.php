<?php
session_start();
require_once '../../config/db.php';

// 1. Seguridad
if (!isset($_SESSION['user_id'])) { header("Location: ../../index.php"); exit; }

include '../../includes/header_admin.php';

$mensaje = "";

// ---------------------------------------------------------
// PROCESAR TRANSFERENCIA (BACKEND)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $origen  = $_POST['origen'];
    $destino = $_POST['destino'];
    $prod_id = $_POST['producto'];
    $cantidad = (float) $_POST['cantidad']; // Permitir decimales si es necesario (ej. kg)

    if ($origen == $destino) {
        $mensaje = "<div class='alert alert-error'>⚠️ La sede de origen y destino no pueden ser iguales.</div>";
    } elseif ($cantidad <= 0) {
        $mensaje = "<div class='alert alert-error'>⚠️ La cantidad debe ser mayor a 0.</div>";
    } else {
        try {
            $pdo->beginTransaction();

            // A. Validar Stock Real en BD (Seguridad Backend)
            $stmtCheck = $pdo->prepare("SELECT stock FROM productos_sedes WHERE id_producto = ? AND id_sede = ? FOR UPDATE");
            $stmtCheck->execute([$prod_id, $origen]);
            $stockOrigen = $stmtCheck->fetchColumn();

            // Si no hay registro o stock es menor al solicitado
            if ($stockOrigen === false || $stockOrigen < $cantidad) {
                throw new Exception("Stock insuficiente. El sistema indica que solo hay $stockOrigen unidades en el origen.");
            }

            // B. RESTAR de Origen
            $pdo->prepare("UPDATE productos_sedes SET stock = stock - ? WHERE id_producto = ? AND id_sede = ?")->execute([$cantidad, $prod_id, $origen]);
            
            // C. SUMAR a Destino (Crear fila si no existe)
            $checkDest = $pdo->prepare("SELECT id FROM productos_sedes WHERE id_producto = ? AND id_sede = ?");
            $checkDest->execute([$prod_id, $destino]);
            if (!$checkDest->fetch()) {
                $pdo->prepare("INSERT INTO productos_sedes (id_producto, id_sede, stock) VALUES (?, ?, 0)")->execute([$prod_id, $destino]);
            }
            $pdo->prepare("UPDATE productos_sedes SET stock = stock + ? WHERE id_producto = ? AND id_sede = ?")->execute([$cantidad, $prod_id, $destino]);

            // D. Registro Cabecera Transferencia
            $pdo->prepare("INSERT INTO transferencias (origen_sede_id, destino_sede_id, id_producto, cantidad, id_usuario, fecha) VALUES (?, ?, ?, ?, ?, NOW())")
                ->execute([$origen, $destino, $prod_id, $cantidad, $_SESSION['user_id']]);
            $id_trans = $pdo->lastInsertId();
            
            // E. KARDEX (DOBLE ASIENTO)
            
            // 1. Salida Origen
            $stockFinalOrigen = $stockOrigen - $cantidad;
            $sqlK1 = "INSERT INTO kardex (id_producto, id_sede, tipo_movimiento, cantidad, stock_resultante, nota, fecha) VALUES (?, ?, 'transferencia_salida', ?, ?, ?, NOW())";
            $pdo->prepare($sqlK1)->execute([$prod_id, $origen, -$cantidad, $stockFinalOrigen, "TRF #$id_trans: Envío a Sede Destino"]);

            // 2. Entrada Destino
            $stmtDestStock = $pdo->prepare("SELECT stock FROM productos_sedes WHERE id_producto = ? AND id_sede = ?");
            $stmtDestStock->execute([$prod_id, $destino]);
            $stockFinalDestino = $stmtDestStock->fetchColumn();

            $sqlK2 = "INSERT INTO kardex (id_producto, id_sede, tipo_movimiento, cantidad, stock_resultante, nota, fecha) VALUES (?, ?, 'transferencia_entrada', ?, ?, ?, NOW())";
            $pdo->prepare($sqlK2)->execute([$prod_id, $destino, $cantidad, $stockFinalDestino, "TRF #$id_trans: Recepción desde Origen"]);

            $pdo->commit();
            $mensaje = "<div class='alert alert-success'>✅ Transferencia de <b>$cantidad</b> unidades realizada con éxito.</div>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $mensaje = "<div class='alert alert-error'>❌ Error: " . $e->getMessage() . "</div>";
        }
    }
}

// ---------------------------------------------------------
// CARGA DE DATOS PARA EL FORMULARIO
// ---------------------------------------------------------
$sedes = $pdo->query("SELECT * FROM sedes")->fetchAll(PDO::FETCH_ASSOC);
$productos = $pdo->query("SELECT id, nombre, codigo_barras FROM productos ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Generar MAPA de Stock para JavaScript
// Creamos un array PHP: [ 'sede_id_producto_id' => stock_cantidad ]
$stockMap = [];
$qStock = $pdo->query("SELECT id_sede, id_producto, stock FROM productos_sedes");
while ($row = $qStock->fetch(PDO::FETCH_ASSOC)) {
    $stockMap[$row['id_sede'] . '_' . $row['id_producto']] = $row['stock'];
}
?>

<style>
    .page-title { color: var(--royal-gold, #FFD700); text-align: center; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px; }
    .card { background: #1a1a1a; padding: 25px; border-radius: 10px; border: 1px solid #333; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
    
    label { display: block; margin-bottom: 5px; color: #aaa; font-size: 0.9rem; }
    
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

    .stock-info {
        text-align: right; font-size: 0.85rem; margin-top: -10px; margin-bottom: 15px; height: 20px;
    }
</style>

<div class="fade-in" style="max-width:600px; margin:auto; padding-top:20px;">
    
    <h2 class="page-title"><i class="fa-solid fa-arrow-right-arrow-left"></i> Transferencia de Stock</h2>
    
    <?= $mensaje ?>

    <div class="card">
        <form method="POST" id="transferForm">
            
            <div style="display:flex; gap:20px;">
                <div style="flex:1;">
                    <label>De (Origen)</label>
                    <select name="origen" id="origen" class="form-control" onchange="actualizarStockDisponible()">
                        <?php foreach($sedes as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= $s['nombre'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="flex:0 0 40px; display:flex; align-items:center; justify-content:center; padding-top:10px;">
                    <i class="fa-solid fa-arrow-right" style="color:#666;"></i>
                </div>
                <div style="flex:1;">
                    <label>A (Destino)</label>
                    <select name="destino" id="destino" class="form-control">
                        <?php foreach($sedes as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= $s['id']==2?'selected':'' ?>><?= $s['nombre'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <label>Seleccionar Producto</label>
            <select name="producto" id="producto" class="form-control" onchange="actualizarStockDisponible()">
                <option value="">-- Seleccione un producto --</option>
                <?php foreach($productos as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= $p['nombre'] ?></option>
                <?php endforeach; ?>
            </select>

            <div class="stock-info" id="stockDisplay">
                Seleccione sede y producto para ver stock.
            </div>

            <label>Cantidad a Transferir</label>
            <input type="number" name="cantidad" id="cantidad" class="form-control" min="1" placeholder="0" required>

            <button type="submit" class="btn-royal" id="btnSubmit">
                <i class="fa-solid fa-check"></i> CONFIRMAR TRANSFERENCIA
            </button>
        </form>
    </div>
</div>

<script>
    // 1. Convertir el array PHP a Objeto JS
    const inventario = <?= json_encode($stockMap) ?>;

    function actualizarStockDisponible() {
        const origenId = document.getElementById('origen').value;
        const prodId = document.getElementById('producto').value;
        const display = document.getElementById('stockDisplay');
        const inputCant = document.getElementById('cantidad');
        const btn = document.getElementById('btnSubmit');

        if (origenId && prodId) {
            // Buscamos en el objeto JS usando la clave 'sede_producto'
            const key = origenId + '_' + prodId;
            const stock = inventario[key] !== undefined ? parseFloat(inventario[key]) : 0;

            if (stock > 0) {
                display.innerHTML = `<span style="color:#66bb6a"><i class="fa-solid fa-box"></i> Stock Disponible en Origen: <b>${stock}</b></span>`;
                
                // Configurar límites del input para que no puedan escribir más de lo que hay
                inputCant.max = stock;
                inputCant.disabled = false;
                inputCant.placeholder = "Máximo " + stock;
                btn.disabled = false;
                btn.style.opacity = "1";
            } else {
                display.innerHTML = `<span style="color:#ef5350"><i class="fa-solid fa-circle-xmark"></i> Sin stock en esta sede.</span>`;
                
                inputCant.value = '';
                inputCant.disabled = true;
                inputCant.placeholder = "Sin stock";
                btn.disabled = true;
                btn.style.opacity = "0.5";
            }
        } else {
            display.innerHTML = "Seleccione sede y producto.";
            inputCant.disabled = true;
        }
    }

    // Ejecutar al cargar por si el navegador guarda valores
    document.addEventListener('DOMContentLoaded', actualizarStockDisponible);

    // Validación extra antes de enviar
    document.getElementById('transferForm').addEventListener('submit', function(e) {
        const origen = document.getElementById('origen').value;
        const destino = document.getElementById('destino').value;
        
        if (origen === destino) {
            e.preventDefault();
            alert("⚠️ Error: La sede de origen y destino son la misma.");
        }
    });
</script>

<?php include '../../includes/footer_admin.php'; ?>