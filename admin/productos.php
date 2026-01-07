<?php
require '../config/db.php';
// Aquí iría la validación de sesión (omitida por brevedad)

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo = $_POST['codigo'];
    $nombre = $_POST['nombre'];
    $es_granel = isset($_POST['es_granel']) ? 1 : 0;
    $costo = $_POST['costo'];
    $precio = $_POST['precio'];
    $stock = $_POST['stock'];

    $sql = "INSERT INTO productos (codigo, nombre, es_granel, costo, precio, stock) VALUES (?,?,?,?,?,?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$codigo, $nombre, $es_granel, $costo, $precio, $stock]);
    
    // Registrar entrada inicial en Kardex
    $pid = $pdo->lastInsertId();
    $pdo->query("INSERT INTO kardex (producto_id, tipo, cantidad, stock_saldo) VALUES ($pid, 'INICIAL', $stock, $stock)");
    echo "<p>Producto registrado!</p>";
}
?>

<!DOCTYPE html>
<html>
<head><title>Admin - Productos</title></head>
<body>
    <h1>Nuevo Producto - SUARCORP</h1>
    <form method="POST">
        <input type="text" name="codigo" placeholder="Código Barras" required><br>
        <input type="text" name="nombre" placeholder="Nombre Producto" required><br>
        <label>
            <input type="checkbox" name="es_granel"> ¿Es venta a Granel (Peso)?
        </label><br>
        <input type="number" step="0.01" name="costo" placeholder="Precio Costo" required>
        <input type="number" step="0.01" name="precio" placeholder="Precio Venta" required><br>
        <input type="number" step="0.001" name="stock" placeholder="Stock Inicial" required><br>
        <button type="submit">Guardar Producto</button>
    </form>
</body>
</html>