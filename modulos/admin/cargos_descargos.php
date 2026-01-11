<?php
session_start();
require_once '../../config/db.php';

// Seguridad
if (!isset($_SESSION['user_id'])) { header("Location: ../../index.php"); exit; }

include '../../includes/header_admin.php';

$mensaje = "";

// ---------------------------------------------------------
// LÓGICA DE PROCESAMIENTO (SIMPLIFICADA E INTELIGENTE)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sede_origen = $_POST['sede_origen'];
    $tipo_op     = $_POST['tipo_operacion']; 
    $prod_id     = $_POST['producto'];
    
    // Datos del formulario
    $unidad_medida  = $_POST['unidad_medida']; // 'unidad' o 'caja'
    $cant_ingresada = (float) $_POST['cantidad'];
    
    // Nuevos datos opcionales
    $costo_input    = isset($_POST['costo']) ? (float) $_POST['costo'] : 0;
    $precio_venta   = isset($_POST['precio_venta']) ? (float) $_POST['precio_venta'] : 0;
    $sede_destino   = $_POST['sede_destino'] ?? null;

    if ($cant_ingresada <= 0) {
        $mensaje = "<div class='alert alert-error'>⚠️ La cantidad debe ser mayor a 0.</div>";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. OBTENER DATOS REALES DEL PRODUCTO (FACTOR DE CAJA)
            // Consultamos la BD para evitar errores humanos en el formulario
            $stmtProd = $pdo->prepare("SELECT costo_compra, precio_venta, unidades_caja FROM productos WHERE id = ?");
            $stmtProd->execute([$prod_id]);
            $prodData = $stmtProd->fetch();
            
            // Factor automático (si es 0 o null, asumimos 1)
            $factor_conversion = ($prodData['unidades_caja'] > 0) ? $prodData['unidades_caja'] : 1;
            $costoAntiguoBD    = $prodData['costo_compra'];

            // 2. CALCULAR CANTIDAD REAL EN UNIDADES (BOTELLAS)
            if ($unidad_medida == 'caja') {
                $cantidad_real = $cant_ingresada * $factor_conversion; 
                // Si ingresó costo, asumimos que es el COSTO DE LA CAJA, así que dividimos
                $costo_unitario_real = ($costo_input > 0) ? ($costo_input / $factor_conversion) : 0;
                $nota_extra = " ($cant_ingresada Cajas x $factor_conversion u)";
            } else {
                $cantidad_real = $cant_ingresada;
                $costo_unitario_real = $costo_input;
                $nota_extra = "";
            }

            // 3. ACTUALIZAR PRECIO DE VENTA (Si el usuario lo ingresó)
            if ($precio_venta > 0) {
                $pdo->prepare("UPDATE productos SET precio_venta = ? WHERE id = ?")->execute([$precio_venta, $prod_id]);
            }

            // 4. VALIDAR STOCK PARA SALIDAS
            $es_ingreso = ($tipo_op == 'cargo_compra' || $tipo_op == 'cargo_ajuste');
            
            $stmtStock = $pdo->prepare("SELECT stock FROM productos_sedes WHERE id_producto = ? AND id_sede = ? FOR UPDATE");
            $stmtStock->execute([$prod_id, $sede_origen]);
            $stockData = $stmtStock->fetch();
            $stockOrigen = $stockData ? (float)$stockData['stock'] : 0;

            if (!$es_ingreso && $stockOrigen < $cantidad_real) {
                throw new Exception("Stock insuficiente. Tienes $stockOrigen botellas, intentas sacar $cantidad_real.");
            }

            // 5. ACTUALIZAR COSTO PROMEDIO (SOLO EN COMPRAS)
            $costoParaKardex = $costoAntiguoBD; // Por defecto mantenemos el anterior
            
            if ($tipo_op == 'cargo_compra') {
                $stmtGlobal = $pdo->prepare("SELECT SUM(stock) FROM productos_sedes WHERE id_producto = ?");
                $stmtGlobal->execute([$prod_id]);
                $stockGlobal = $stmtGlobal->fetchColumn() ?: 0;

                $valorAnt = $stockGlobal * $costoAntiguoBD;
                $valorNue = $cantidad_real * $costo_unitario_real;
                
                $nuevoCostoPromedio = ($stockGlobal + $cantidad_real > 0) 
                                      ? ($valorAnt + $valorNue) / ($stockGlobal + $cantidad_real) 
                                      : $costo_unitario_real;

                $pdo->prepare("UPDATE productos SET costo_compra = ? WHERE id = ?")->execute([$nuevoCostoPromedio, $prod_id]);
                $costoParaKardex = $costo_unitario_real; // En el kardex registramos el costo de ESTA compra
            }

            // 6. EJECUTAR MOVIMIENTO
            if ($tipo_op == 'transferencia') {
                // Restar Origen
                movimientoStock($pdo, $prod_id, $sede_origen, -$cantidad_real);
                // Sumar Destino
                movimientoStock($pdo, $prod_id, $sede_destino, $cantidad_real);

                // Registrar Transferencia
                $pdo->prepare("INSERT INTO transferencias (origen_sede_id, destino_sede_id, id_producto, cantidad, id_usuario, fecha) VALUES (?, ?, ?, ?, ?, NOW())")
                    ->execute([$sede_origen, $sede_destino, $prod_id, $cantidad_real, $_SESSION['user_id']]);
                $id_trans = $pdo->lastInsertId();

                registrarKardex($pdo, $prod_id, $sede_origen, 'transferencia_salida', -$cantidad_real, $costoParaKardex, "TRF #$id_trans a Sede #$sede_destino" . $nota_extra);
                registrarKardex($pdo, $prod_id, $sede_destino, 'transferencia_entrada', $cantidad_real, $costoParaKardex, "TRF #$id_trans desde Sede #$sede_origen" . $nota_extra);
                
            } else {
                // Movimiento Simple
                $factor = $es_ingreso ? 1 : -1;
                movimientoStock($pdo, $prod_id, $sede_origen, $cantidad_real * $factor);
                
                $labels = [
                    'cargo_compra' => 'Compra', 'cargo_ajuste' => 'Sobrante',
                    'descargo_merma' => 'Merma', 'descargo_ajuste' => 'Faltante'
                ];
                $nota = ($labels[$tipo_op] ?? 'Ajuste') . $nota_extra;
                
                registrarKardex($pdo, $prod_id, $sede_origen, $tipo_op, $cantidad_real * $factor, $costoParaKardex, $nota);
            }

            $pdo->commit();
            $mensaje = "<div class='alert alert-success'>✅ Operación exitosa.<br>Se procesaron <b>$cantidad_real botellas</b>.</div>";

        } catch (Exception $e) {
            $pdo->rollBack();
            $mensaje = "<div class='alert alert-error'>❌ " . $e->getMessage() . "</div>";
        }
    }
}

// Funciones auxiliares
function movimientoStock($pdo, $prod, $sede, $cant) {
    $check = $pdo->prepare("SELECT id FROM productos_sedes WHERE id_producto = ? AND id_sede = ?");
    $check->execute([$prod, $sede]);
    if (!$check->fetch()) {
        $pdo->prepare("INSERT INTO productos_sedes (id_producto, id_sede, stock) VALUES (?, ?, 0)")->execute([$prod, $sede]);
    }
    $pdo->prepare("UPDATE productos_sedes SET stock = stock + ? WHERE id_producto = ? AND id_sede = ?")->execute([$cant, $prod, $sede]);
}

function registrarKardex($pdo, $prod, $sede, $tipo, $cant, $costo, $nota) {
    $saldo = $pdo->query("SELECT stock FROM productos_sedes WHERE id_producto=$prod AND id_sede=$sede")->fetchColumn();
    $sql = "INSERT INTO kardex (id_producto, id_sede, tipo_movimiento, cantidad, costo_unitario, costo_total, stock_resultante, nota, fecha) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $pdo->prepare($sql)->execute([$prod, $sede, $tipo, $cant, $costo, abs($cant * $costo), $saldo, $nota]);
}

// CARGA DE DATOS PARA JS
$sedes = $pdo->query("SELECT * FROM sedes")->fetchAll();
$productos = $pdo->query("SELECT id, nombre, costo_compra, precio_venta, unidades_caja FROM productos ORDER BY nombre")->fetchAll();

$prodJson = [];
foreach($productos as $p) {
    $prodJson[$p['id']] = [
        'nombre' => $p['nombre'], 
        'costo'  => $p['costo_compra'],
        'venta'  => $p['precio_venta'],
        'factor' => $p['unidades_caja'] // Dato clave para el JS
    ];
}
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

    /* Grid Botones (Tu diseño original) */
    .op-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 15px; margin-bottom: 25px; }
    .op-card { background: #111; border: 2px solid #333; border-radius: 10px; padding: 20px 10px; text-align: center; cursor: pointer; transition: all 0.2s ease; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 120px; }
    .op-card:hover { background: #222; border-color: #555; }
    .op-card.active { border-color: var(--royal-gold); background: rgba(255, 215, 0, 0.05); box-shadow: 0 0 15px rgba(255, 215, 0, 0.1); }
    .op-icon { font-size: 2rem; margin-bottom: 10px; color: #666; transition: color 0.2s; }
    .op-card:hover .op-icon, .op-card.active .op-icon { color: var(--royal-gold); }
    .op-title { font-size: 0.8rem; font-weight: 600; color: #ccc; line-height: 1.2; }
    .op-card.active .op-title { color: #fff; }

    .section-label { color: #888; font-size: 0.85rem; margin-bottom: 8px; display: block; text-transform: uppercase; letter-spacing: 0.5px; }
    .input-group-row { display: flex; gap: 15px; }
    
    /* Etiqueta de Conversión Dinámica */
    .conversion-tag {
        background: rgba(255, 215, 0, 0.1); border: 1px solid var(--royal-gold); color: var(--royal-gold);
        padding: 5px 10px; border-radius: 4px; font-size: 0.8rem; margin-left: 10px; display: none;
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
                
                <div class="input-group-row" style="margin-bottom:15px;">
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
                    <div id="info_extra" style="display:none; font-size:0.9rem; color:#888; text-align:right;">
                        Contenido Caja: <b style="color:#fff;" id="txt_factor">1 Unid</b> | 
                        Costo Actual: <b style="color:#fff;" id="txt_costo">S/ 0.00</b>
                    </div>
                </div>

                <div class="input-group-row" style="margin-bottom:15px; background: #222; padding:15px; border-radius:8px;">
                    <div style="flex:1;">
                        <span class="section-label">Modo de Ingreso</span>
                        <select name="unidad_medida" id="unidad_medida" class="form-control" onchange="calcularTotal()" style="margin-bottom:0;">
                            <option value="unidad">Por Botella (Unidad)</option>
                            <option value="caja" id="opt_caja">Por Caja Cerrada</option>
                        </select>
                    </div>
                    
                    <div style="flex:1;">
                        <span class="section-label">Cantidad <span id="tag_conversion" class="conversion-tag"></span></span>
                        <input type="number" name="cantidad" id="input_cantidad" class="form-control" placeholder="0" step="0.01" required style="font-size:1.2rem; font-weight:bold; margin-bottom:0;" oninput="calcularTotal()">
                    </div>
                </div>

                <div id="div_costo" style="display:none; background:#222; padding:15px; border-radius:10px; border:1px solid #444; margin-bottom:20px;">
                    <div class="input-group-row">
                        <div style="flex:1;">
                            <span class="section-label" style="color:#fff;" id="lbl_costo_input">Nuevo Costo</span>
                            <div style="position:relative;">
                                <span style="position:absolute; left:15px; top:12px; color:#888;">S/</span>
                                <input type="number" name="costo" id="input_costo" class="form-control" step="0.01" placeholder="0.00" style="padding-left:40px;">
                            </div>
                        </div>
                        <div style="flex:1;">
                            <span class="section-label" style="color:var(--royal-gold);">Actualizar Precio Venta</span>
                            <div style="position:relative;">
                                <span style="position:absolute; left:15px; top:12px; color:#888;">S/</span>
                                <input type="number" name="precio_venta" id="input_precio_venta" class="form-control" step="0.01" placeholder="0.00" style="padding-left:40px;">
                            </div>
                        </div>
                    </div>
                    <small style="color:#666; display:block; margin-top:5px;">* El costo se promediará automáticamente.</small>
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
    let factorActual = 1;

    function seleccionarOp(elemento, valor) {
        document.querySelectorAll('.op-card').forEach(c => c.classList.remove('active'));
        elemento.classList.add('active');

        document.getElementById('tipo_operacion').value = valor;
        document.getElementById('mensaje_inicial').style.display = 'none';
        document.getElementById('detalles_form').style.display = 'block';

        const divDestino = document.getElementById('div_destino');
        const divCosto = document.getElementById('div_costo');
        const inputCosto = document.getElementById('input_costo');
        const selectDestino = document.getElementById('sede_destino');
        const lblOrigen = document.getElementById('lbl_origen');
        const btnAccion = document.getElementById('btn_accion');

        // Reset visual
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
            actualizarInfo(); 
        }
        else if (valor.includes('merma')) {
            btnAccion.innerHTML = '<i class="fa-solid fa-trash"></i> REGISTRAR MERMA';
        }
    }

    function actualizarInfo() {
        const id = document.getElementById('producto').value;
        const txtCosto = document.getElementById('txt_costo');
        const txtFactor = document.getElementById('txt_factor');
        const infoDiv = document.getElementById('info_extra');
        const inputVenta = document.getElementById('input_precio_venta');
        const optCaja = document.getElementById('opt_caja');
        const selectMedida = document.getElementById('unidad_medida');

        if (id && productos[id]) {
            const data = productos[id];
            factorActual = parseInt(data.factor) || 1;
            
            txtCosto.innerText = 'S/ ' + parseFloat(data.costo).toFixed(2);
            txtFactor.innerText = factorActual + ' Botellas/Caja';
            infoDiv.style.display = 'block';

            // Configurar opción de caja
            if(factorActual > 1) {
                optCaja.disabled = false;
                optCaja.innerText = `Por Caja (x${factorActual})`;
            } else {
                selectMedida.value = 'unidad';
                optCaja.disabled = true;
                optCaja.innerText = "Solo por Unidad";
            }

            // Prellenar precio venta
            if (document.getElementById('tipo_operacion').value === 'cargo_compra') {
                inputVenta.value = parseFloat(data.venta).toFixed(2);
            }
            calcularTotal();
        } else {
            infoDiv.style.display = 'none';
        }
    }

    function calcularTotal() {
        const cantidad = parseFloat(document.getElementById('input_cantidad').value) || 0;
        const modo = document.getElementById('unidad_medida').value;
        const tag = document.getElementById('tag_conversion');
        const lblCosto = document.getElementById('lbl_costo_input');

        if(modo === 'caja' && cantidad > 0) {
            const totalBotellas = cantidad * factorActual;
            tag.style.display = 'inline-block';
            tag.innerText = `= ${totalBotellas} Botellas`;
            lblCosto.innerText = "Costo Total por Caja";
        } else {
            tag.style.display = 'none';
            lblCosto.innerText = "Costo Unitario";
        }
    }
</script>

<?php include '../../includes/footer_admin.php'; ?>