<?php
session_start();
require_once '../../config/db.php';

// Seguridad
if (!isset($_SESSION['user_id'])) { header("Location: ../../index.php"); exit; }

include '../../includes/header_admin.php';

$mensaje = "";

// ---------------------------------------------------------
// LÓGICA DE PROCESAMIENTO (Igual que antes, robusta y segura)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sede_origen = $_POST['sede_origen'];
    $tipo_op     = $_POST['tipo_operacion']; 
    $prod_id     = $_POST['producto'];
    $cantidad    = (float) $_POST['cantidad'];
    $costo_nuevo = isset($_POST['costo']) ? (float) $_POST['costo'] : 0;
    $sede_destino = isset($_POST['sede_destino']) ? $_POST['sede_destino'] : null;

    if ($cantidad <= 0) {
        $mensaje = "<div class='alert alert-error'>⚠️ La cantidad debe ser mayor a 0.</div>";
    } elseif ($tipo_op == 'transferencia' && $sede_origen == $sede_destino) {
        $mensaje = "<div class='alert alert-error'>⚠️ El origen y destino no pueden ser iguales.</div>";
    } elseif (empty($tipo_op)) {
        $mensaje = "<div class='alert alert-error'>⚠️ Selecciona un tipo de operación.</div>";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. DATOS PREVIOS
            $stmtProd = $pdo->prepare("SELECT costo_compra FROM productos WHERE id = ?");
            $stmtProd->execute([$prod_id]);
            $costoActual = $stmtProd->fetchColumn();

            // 2. VALIDAR STOCK ORIGEN
            $es_ingreso = ($tipo_op == 'cargo_compra' || $tipo_op == 'cargo_ajuste');
            
            $stmtStock = $pdo->prepare("SELECT stock FROM productos_sedes WHERE id_producto = ? AND id_sede = ? FOR UPDATE");
            $stmtStock->execute([$prod_id, $sede_origen]);
            $stockData = $stmtStock->fetch();
            $stockOrigen = $stockData ? (float)$stockData['stock'] : 0;

            if (!$es_ingreso && $stockOrigen < $cantidad) {
                throw new Exception("Stock insuficiente en origen. Disponibles: $stockOrigen");
            }

            // 3. PROCESAR SEGÚN TIPO
            if ($tipo_op == 'cargo_compra') {
                // Recalcular Costo Promedio
                $stmtGlobal = $pdo->prepare("SELECT SUM(stock) FROM productos_sedes WHERE id_producto = ?");
                $stmtGlobal->execute([$prod_id]);
                $stockGlobal = $stmtGlobal->fetchColumn() ?: 0;

                $valorAnt = $stockGlobal * $costoActual;
                $valorNue = $cantidad * $costo_nuevo;
                // Evitar división por cero
                $divisor = $stockGlobal + $cantidad;
                $nuevoCosto = ($divisor > 0) ? ($valorAnt + $valorNue) / $divisor : $costo_nuevo;

                $pdo->prepare("UPDATE productos SET costo_compra = ? WHERE id = ?")->execute([$nuevoCosto, $prod_id]);
                $costoRegistro = $costo_nuevo;
            } else {
                $costoRegistro = $costoActual;
            }

            if ($tipo_op == 'transferencia') {
                // Restar Origen
                $pdo->prepare("UPDATE productos_sedes SET stock = stock - ? WHERE id_producto = ? AND id_sede = ?")->execute([$cantidad, $prod_id, $sede_origen]);
                // Sumar Destino
                $checkDest = $pdo->prepare("SELECT id FROM productos_sedes WHERE id_producto = ? AND id_sede = ?");
                $checkDest->execute([$prod_id, $sede_destino]);
                if (!$checkDest->fetch()) {
                    $pdo->prepare("INSERT INTO productos_sedes (id_producto, id_sede, stock) VALUES (?, ?, 0)")->execute([$prod_id, $sede_destino]);
                }
                $pdo->prepare("UPDATE productos_sedes SET stock = stock + ? WHERE id_producto = ? AND id_sede = ?")->execute([$cantidad, $prod_id, $sede_destino]);

                // Registrar Transferencia
                $pdo->prepare("INSERT INTO transferencias (origen_sede_id, destino_sede_id, id_producto, cantidad, id_usuario, fecha) VALUES (?, ?, ?, ?, ?, NOW())")
                    ->execute([$sede_origen, $sede_destino, $prod_id, $cantidad, $_SESSION['user_id']]);
                
                registrarKardex($pdo, $prod_id, $sede_origen, 'transferencia_salida', -$cantidad, $costoRegistro, "TRF a Sede #$sede_destino");
                registrarKardex($pdo, $prod_id, $sede_destino, 'transferencia_entrada', $cantidad, $costoRegistro, "TRF desde Sede #$sede_origen");

            } else {
                // Operación Simple
                $factor = $es_ingreso ? 1 : -1;
                
                if ($es_ingreso && !$stockData) {
                    $pdo->prepare("INSERT INTO productos_sedes (id_producto, id_sede, stock) VALUES (?, ?, 0)")->execute([$prod_id, $sede_origen]);
                }

                $pdo->prepare("UPDATE productos_sedes SET stock = stock + ? WHERE id_producto = ? AND id_sede = ?")->execute([$cantidad * $factor, $prod_id, $sede_origen]);

                $notaMap = [
                    'cargo_compra' => 'Compra de Mercadería',
                    'cargo_ajuste' => 'Ajuste (Sobrante)',
                    'descargo_ajuste' => 'Ajuste (Faltante)',
                    'descargo_merma' => 'Baja por Merma/Deterioro'
                ];
                $nota = $notaMap[$tipo_op] ?? 'Movimiento Manual';
                
                registrarKardex($pdo, $prod_id, $sede_origen, $tipo_op, $cantidad * $factor, $costoRegistro, $nota);
            }

            $pdo->commit();
            $mensaje = "<div class='alert alert-success'>✅ Operación registrada con éxito.</div>";

        } catch (Exception $e) {
            $pdo->rollBack();
            $mensaje = "<div class='alert alert-error'>❌ Error: " . $e->getMessage() . "</div>";
        }
    }
}

function registrarKardex($pdo, $prod, $sede, $tipo, $cant, $costo, $nota) {
    $stmt = $pdo->prepare("SELECT stock FROM productos_sedes WHERE id_producto = ? AND id_sede = ?");
    $stmt->execute([$prod, $sede]);
    $saldo = $stmt->fetchColumn();

    $sql = "INSERT INTO kardex (id_producto, id_sede, tipo_movimiento, cantidad, costo_unitario, costo_total, stock_resultante, nota, fecha) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $pdo->prepare($sql)->execute([$prod, $sede, $tipo, $cant, $costo, abs($cant * $costo), $saldo, $nota]);
}

$sedes = $pdo->query("SELECT * FROM sedes")->fetchAll();
$productos = $pdo->query("SELECT id, nombre, costo_compra FROM productos ORDER BY nombre")->fetchAll();
$prodJson = [];
foreach($productos as $p) $prodJson[$p['id']] = ['nombre'=>$p['nombre'], 'costo'=>$p['costo_compra']];
?>

<style>
    .page-title { color: var(--royal-gold, #FFD700); text-align: center; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px; }
    .card { background: #1a1a1a; padding: 30px; border-radius: 15px; border: 1px solid #333; box-shadow: 0 4px 15px rgba(0,0,0,0.5); }
    
    .form-control { width: 100%; padding: 12px; background: #2a2a2a; border: 1px solid #444; color: #fff; border-radius: 5px; margin-bottom: 15px; font-size: 1rem; }
    .form-control:focus { outline: none; border-color: var(--royal-gold); }
    
    .btn-royal { background: linear-gradient(45deg, #b7892b, #FFD700); color: #000; font-weight: bold; border: none; padding: 15px; width: 100%; border-radius: 5px; cursor: pointer; text-transform: uppercase; font-size: 1rem; margin-top: 10px; transition: 0.3s; }
    .btn-royal:hover { transform: scale(1.02); box-shadow: 0 0 15px rgba(255, 215, 0, 0.4); }

    .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center; font-weight: bold; }
    .alert-success { background: rgba(46, 125, 50, 0.2); color: #81c784; border: 1px solid #2e7d32; }
    .alert-error { background: rgba(198, 40, 40, 0.2); color: #e57373; border: 1px solid #c62828; }

    /* --- ESTILOS DEL GRID DE BOTONES --- */
    .op-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 15px;
        margin-bottom: 25px;
    }

    .op-card {
        background: #111;
        border: 2px solid #333;
        border-radius: 10px;
        padding: 20px 10px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 120px;
    }

    .op-card:hover {
        background: #222;
        border-color: #555;
    }

    .op-card.active {
        border-color: var(--royal-gold);
        background: rgba(255, 215, 0, 0.05);
        box-shadow: 0 0 15px rgba(255, 215, 0, 0.1);
    }

    .op-icon {
        font-size: 2rem;
        margin-bottom: 10px;
        color: #666;
        transition: color 0.2s;
    }

    .op-card:hover .op-icon, .op-card.active .op-icon {
        color: var(--royal-gold);
    }

    .op-title {
        font-size: 0.8rem;
        font-weight: 600;
        color: #ccc;
        line-height: 1.2;
    }

    .op-card.active .op-title {
        color: #fff;
    }

    .section-label {
        color: #888;
        font-size: 0.85rem;
        margin-bottom: 8px;
        display: block;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
</style>

<div class="fade-in" style="max-width:800px; margin:auto; padding-top:20px;">
    
    <h2 class="page-title"><i class="fa-solid fa-boxes-packing"></i> Centro de Movimientos</h2>
    <?= $mensaje ?>

    <div class="card">
        <form method="POST" id="mainForm">
            
            <input type="hidden" name="tipo_operacion" id="tipo_operacion" required>

            <span class="section-label">1. Selecciona el Tipo de Operación</span>
            
            <div class="op-grid">
                <div class="op-card" onclick="seleccionarOp(this, 'cargo_compra')">
                    <i class="fa-solid fa-cart-flatbed op-icon"></i>
                    <span class="op-title">COMPRA /<br>REPOSICIÓN</span>
                </div>
                
                <div class="op-card" onclick="seleccionarOp(this, 'cargo_ajuste')">
                    <i class="fa-solid fa-circle-plus op-icon"></i>
                    <span class="op-title">AJUSTE<br>SOBRANTE (+)</span>
                </div>

                <div class="op-card" onclick="seleccionarOp(this, 'descargo_merma')">
                    <i class="fa-solid fa-trash-can op-icon"></i>
                    <span class="op-title">MERMA /<br>DETERIORO</span>
                </div>

                <div class="op-card" onclick="seleccionarOp(this, 'descargo_ajuste')">
                    <i class="fa-solid fa-circle-minus op-icon"></i>
                    <span class="op-title">AJUSTE<br>FALTANTE (-)</span>
                </div>

                <div class="op-card" onclick="seleccionarOp(this, 'transferencia')">
                    <i class="fa-solid fa-truck-fast op-icon"></i>
                    <span class="op-title">TRANSFERENCIA<br>ENTRE SEDES</span>
                </div>
            </div>

            <div id="detalles_form" style="display:none; animation: fadeIn 0.5s;">
                
                <div style="display:flex; gap:20px; margin-bottom:15px;">
                    <div style="flex:1;">
                        <span class="section-label" id="lbl_origen">Sede Origen</span>
                        <select name="sede_origen" class="form-control" required>
                            <?php foreach($sedes as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= $s['nombre'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="flex:1; display:none;" id="div_destino">
                        <span class="section-label" style="color:var(--royal-gold);">➡ Sede Destino</span>
                        <select name="sede_destino" id="sede_destino" class="form-control">
                            <?php foreach($sedes as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= $s['nombre'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom:15px;">
                    <span class="section-label">Producto</span>
                    <select name="producto" id="producto" class="form-control" onchange="actualizarInfo()" required>
                        <option value="">-- Buscar Producto --</option>
                        <?php foreach($productos as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= $p['nombre'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div id="info_costo" style="display:none; font-size:0.9rem; color:#888; text-align:right;">
                        Costo Actual: <b style="color:#fff;" id="txt_costo">S/ 0.00</b>
                    </div>
                </div>

                <div style="margin-bottom:15px;">
                    <span class="section-label">Cantidad</span>
                    <input type="number" name="cantidad" class="form-control" placeholder="0" step="0.01" required style="font-size:1.2rem; font-weight:bold;">
                </div>

                <div id="div_costo" style="display:none; background:#222; padding:15px; border-radius:10px; border:1px solid #444; margin-bottom:20px;">
                    <span class="section-label" style="color:var(--royal-gold);">Nuevo Precio de Costo (Unitario)</span>
                    <div style="position:relative;">
                        <span style="position:absolute; left:15px; top:12px; color:#888;">S/</span>
                        <input type="number" name="costo" id="input_costo" class="form-control" step="0.01" placeholder="0.00" style="padding-left:40px; margin-bottom:0;">
                    </div>
                    <small style="color:#666; display:block; margin-top:5px;">* Esto actualizará el promedio ponderado del inventario.</small>
                </div>

                <button type="submit" class="btn-royal" id="btn_accion">
                    <i class="fa-solid fa-check"></i> CONFIRMAR OPERACIÓN
                </button>
            </div>

            <div id="mensaje_inicial" style="text-align:center; padding:30px; color:#666;">
                <i class="fa-solid fa-arrow-up" style="font-size:1.5rem; margin-bottom:10px;"></i>
                <p>Selecciona una opción arriba para comenzar</p>
            </div>

        </form>
    </div>
</div>

<script>
    const productos = <?= json_encode($prodJson) ?>;

    function seleccionarOp(elemento, valor) {
        // 1. Visual: Quitar activo a todos y poner al clickeado
        document.querySelectorAll('.op-card').forEach(c => c.classList.remove('active'));
        elemento.classList.add('active');

        // 2. Lógica: Poner valor en input oculto
        document.getElementById('tipo_operacion').value = valor;

        // 3. Mostrar Formulario
        document.getElementById('mensaje_inicial').style.display = 'none';
        document.getElementById('detalles_form').style.display = 'block';

        // 4. Configurar campos específicos
        const divDestino = document.getElementById('div_destino');
        const divCosto = document.getElementById('div_costo');
        const inputCosto = document.getElementById('input_costo');
        const selectDestino = document.getElementById('sede_destino');
        const lblOrigen = document.getElementById('lbl_origen');
        const btnAccion = document.getElementById('btn_accion');

        // Reset
        divDestino.style.display = 'none';
        selectDestino.required = false;
        divCosto.style.display = 'none';
        inputCosto.required = false;
        lblOrigen.innerText = "Sede Afectada";
        btnAccion.innerHTML = '<i class="fa-solid fa-check"></i> REGISTRAR MOVIMIENTO';

        if (valor === 'transferencia') {
            divDestino.style.display = 'block';
            selectDestino.required = true;
            lblOrigen.innerText = "Sede Origen (Sale)";
            btnAccion.innerHTML = '<i class="fa-solid fa-truck-fast"></i> PROCESAR TRANSFERENCIA';
        } 
        else if (valor === 'cargo_compra') {
            divCosto.style.display = 'block';
            inputCosto.required = true;
            btnAccion.innerHTML = '<i class="fa-solid fa-cart-plus"></i> REGISTRAR COMPRA';
            actualizarInfo(); // Prellenar costo si ya hay producto
        }
        else if (valor.includes('merma')) {
            btnAccion.innerHTML = '<i class="fa-solid fa-trash"></i> REGISTRAR MERMA';
        }
    }

    function actualizarInfo() {
        const id = document.getElementById('producto').value;
        const txtCosto = document.getElementById('txt_costo');
        const infoDiv = document.getElementById('info_costo');
        const inputCosto = document.getElementById('input_costo');
        const tipo = document.getElementById('tipo_operacion').value;

        if (id && productos[id]) {
            const costo = parseFloat(productos[id].costo).toFixed(2);
            txtCosto.innerText = 'S/ ' + costo;
            infoDiv.style.display = 'block';

            // Si es compra, sugerimos el costo actual
            if (tipo === 'cargo_compra') {
                inputCosto.value = costo;
            }
        } else {
            infoDiv.style.display = 'none';
        }
    }
</script>

<?php include '../../includes/footer_admin.php'; ?>