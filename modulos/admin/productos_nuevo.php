<?php
// royal/modulos/admin/productos_nuevo.php
require_once '../../config/db.php';
include '../../includes/header_admin.php'; 

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo = trim($_POST['codigo']);
    $nombre = trim($_POST['nombre']);
    
    // Precios Unitarios
    $p_compra = $_POST['precio_compra'];
    $p_venta  = $_POST['precio_venta'];
    
    // Precios Caja (NUEVO)
    $p_caja   = !empty($_POST['precio_caja']) ? $_POST['precio_caja'] : 0;
    $u_caja   = !empty($_POST['unidades_caja']) ? $_POST['unidades_caja'] : 1;
    
    $stock_ini = $_POST['stock_inicial'];

    if(!empty($codigo) && !empty($nombre)){
        try {
            $pdo->beginTransaction();

            // INSERT ACTUALIZADO CON DATOS DE CAJA
            $sql = "INSERT INTO productos (codigo_barras, nombre, precio_compra, precio_venta, precio_caja, unidades_caja, stock_actual, es_combo) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 0)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$codigo, $nombre, $p_compra, $p_venta, $p_caja, $u_caja, $stock_ini]);
            
            $id_producto = $pdo->lastInsertId();

            // Kardex Inicial
            if ($stock_ini > 0) {
                $sqlK = "INSERT INTO kardex (id_producto, tipo_movimiento, cantidad, stock_resultante, nota) 
                         VALUES (?, 'entrada', ?, ?, 'Inventario Inicial')";
                $stmtK = $pdo->prepare($sqlK);
                $stmtK->execute([$id_producto, $stock_ini, $stock_ini]);
            }

            $pdo->commit();
            $mensaje = "<div style='background:rgba(102, 187, 106, 0.2); color:#66bb6a; border:1px solid #66bb6a; padding:15px; margin-bottom:20px; border-radius:10px;'>
                            ✅ Producto <b>$nombre</b> registrado correctamente.
                        </div>";
        
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
            if (strpos($error, 'Duplicate entry') !== false) {
                $mensaje = "<div style='background:rgba(239, 83, 80, 0.2); color:#ef5350; border:1px solid #ef5350; padding:15px; margin-bottom:20px; border-radius:10px;'>
                                ⚠️ El código de barras <b>$codigo</b> ya existe.
                            </div>";
            } else {
                $mensaje = "<div style='background:rgba(239, 83, 80, 0.2); color:#ef5350; border:1px solid #ef5350; padding:15px; margin-bottom:20px; border-radius:10px;'>
                                ❌ Error: $error
                            </div>";
            }
        }
    }
}
?>

<div class="fade-in" style="max-width: 800px; margin: 0 auto;">
    
    <div style="text-align:center; margin-bottom:30px;">
        <h2 class="page-title">Nuevo Producto</h2>
        <p style="color:#888;">Registra botellas individuales y configura sus precios.</p>
    </div>

    <?= $mensaje ?>

    <div class="card">
        <form method="POST" action="">
            
            <div style="background: rgba(255, 215, 0, 0.05); padding:20px; border-radius:12px; margin-bottom:25px; border:1px dashed #FFD700;">
                <label style="color:#FFD700;">Código de Barras</label>
                <div style="display: flex; gap: 10px;">
                    <div style="position: relative; flex: 1;">
                        <input type="text" id="codigo_input" name="codigo" required autofocus 
                               placeholder="Escanea o escribe..." autocomplete="off" 
                               style="margin-bottom:0; font-size:1.1rem; letter-spacing:1px; font-family:monospace; text-align:center; height: 50px;">
                    </div>
                    <button type="button" onclick="startScanner('codigo_input')" class="btn-royal" 
                            style="min-width: 60px; width: 60px; padding: 0; display: flex; align-items: center; justify-content: center; height: 50px;">
                        <i class="fa-solid fa-camera" style="font-size: 1.5rem;"></i>
                    </button>
                </div>
            </div>

            <label>Nombre del Producto</label>
            <input type="text" name="nombre" required placeholder="Ej: Whisky Black Label 750ml">

            <div class="stat-grid" style="grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                <div>
                    <label style="color: #FFD700;">Precio Venta Unitario (S/)</label>
                    <div style="position:relative;">
                        <span style="position:absolute; left:15px; top:13px; color:#FFD700; font-weight:bold;">S/</span>
                        <input type="number" step="0.01" name="precio_venta" required placeholder="0.00" 
                               style="padding-left:40px; font-weight:bold; font-size:1.1rem;">
                    </div>
                </div>
                <div>
                    <label>Costo Unitario (S/)</label>
                    <input type="number" step="0.01" name="precio_compra" value="0.00">
                </div>
            </div>

            <div style="background:#222; padding:15px; border-radius:10px; border:1px solid #333; margin-bottom:20px;">
                <label style="color:#aaa; text-transform:uppercase; font-size:0.8rem; letter-spacing:1px; margin-bottom:10px; display:block;">
                    <i class="fa-solid fa-box-open"></i> Configuración de Caja (Opcional)
                </label>
                <div style="display:flex; gap:15px;">
                    <div style="flex:1;">
                        <label>Unidades por Caja</label>
                        <input type="number" name="unidades_caja" value="1" min="1" placeholder="Ej: 12">
                    </div>
                    <div style="flex:1;">
                        <label style="color:#66bb6a;">Precio Venta Caja (S/)</label>
                        <input type="number" step="0.01" name="precio_caja" placeholder="0.00">
                    </div>
                </div>
                <small style="color:#666;">* Si llenas esto, en el POS podrás vender por "Caja" automáticamente.</small>
            </div>

            <label>Stock Inicial (Físico en Unidades)</label>
            <input type="number" name="stock_inicial" value="0" min="0">

            <br>
            <button type="submit" class="btn-royal btn-block">
                <i class="fa-solid fa-save"></i> Guardar Producto
            </button>

        </form>
    </div>
</div>

<?php include '../../includes/footer_admin.php'; ?>