<?php
// 1. Configuración y Conexión
require_once '../../config/db.php';
include '../../includes/header_admin.php'; 

$mensaje = "";

// 2. Procesar Formulario al Guardar
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo = trim($_POST['codigo']);
    $nombre = trim($_POST['nombre']);
    $p_compra = $_POST['precio_compra'];
    $p_venta = $_POST['precio_venta'];
    $stock_ini = $_POST['stock_inicial'];

    if(!empty($codigo) && !empty($nombre)){
        try {
            // Iniciar transacción (Todo o nada)
            $pdo->beginTransaction();

            // A. Insertar el Producto
            $sql = "INSERT INTO productos (codigo_barras, nombre, precio_compra, precio_venta, stock_actual, es_combo) 
                    VALUES (?, ?, ?, ?, ?, 0)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$codigo, $nombre, $p_compra, $p_venta, $stock_ini]);
            
            $id_producto = $pdo->lastInsertId();

            // B. Si hay stock inicial, crear registro en Kardex
            if ($stock_ini > 0) {
                $sqlK = "INSERT INTO kardex (id_producto, tipo_movimiento, cantidad, stock_resultante, nota) 
                         VALUES (?, 'entrada', ?, ?, 'Inventario Inicial')";
                $stmtK = $pdo->prepare($sqlK);
                $stmtK->execute([$id_producto, $stock_ini, $stock_ini]);
            }

            $pdo->commit(); // Confirmar cambios
            $mensaje = "<div style='background:rgba(102, 187, 106, 0.2); color:#66bb6a; border:1px solid #66bb6a; padding:15px; margin-bottom:20px; border-radius:10px;'>
                            ✅ Producto <b>$nombre</b> guardado con éxito.
                        </div>";
        
        } catch (Exception $e) {
            $pdo->rollBack(); // Deshacer si hay error
            $error = $e->getMessage();
            // Verificar si es error de código duplicado
            if (strpos($error, 'Duplicate entry') !== false) {
                $mensaje = "<div style='background:rgba(239, 83, 80, 0.2); color:#ef5350; border:1px solid #ef5350; padding:15px; margin-bottom:20px; border-radius:10px;'>
                                ⚠️ El código de barras <b>$codigo</b> ya existe.
                            </div>";
            } else {
                $mensaje = "<div style='background:rgba(239, 83, 80, 0.2); color:#ef5350; border:1px solid #ef5350; padding:15px; margin-bottom:20px; border-radius:10px;'>
                                ❌ Error del sistema: $error
                            </div>";
            }
        }
    }
}
?>

<div class="fade-in" style="max-width: 800px; margin: 0 auto;">
    
    <div style="text-align:center; margin-bottom:30px;">
        <h2 class="page-title">Nuevo Producto</h2>
        <p style="color:#888;">Registra botellas individuales para el stock.</p>
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

                <p style="text-align:center; font-size:0.8rem; color:#666; margin-top:10px;">
                    <i class="fa-solid fa-info-circle"></i> Usa lector USB o toca la cámara en celular.
                </p>
            </div>

            <label>Nombre del Producto</label>
            <input type="text" name="nombre" required placeholder="Ej: Whisky Black Label 750ml">

            <div class="stat-grid" style="grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 0;">
                <div>
                    <label style="color: #FFD700;">Precio Venta ($)</label>
                    <div style="position:relative;">
                        <span style="position:absolute; left:15px; top:13px; color:#FFD700; font-weight:bold;">$</span>
                        <input type="number" step="0.01" name="precio_venta" required placeholder="0.00" 
                               style="padding-left:30px; font-weight:bold; font-size:1.1rem;">
                    </div>
                </div>
                <div>
                    <label>Costo Unitario ($)</label>
                    <input type="number" step="0.01" name="precio_compra" value="0.00">
                </div>
            </div>

            <label>Stock Inicial (Físico)</label>
            <input type="number" name="stock_inicial" value="0" min="0">

            <br>
            <button type="submit" class="btn-royal btn-block">
                <i class="fa-solid fa-save"></i> Guardar Producto
            </button>

        </form>
    </div>
</div>

<?php include '../../includes/footer_admin.php'; ?>