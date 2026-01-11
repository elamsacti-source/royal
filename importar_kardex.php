<?php
// importar_kardex.php
// Ejecuta este script para cargar/actualizar los datos del DBF a MySQL.
set_time_limit(300); // 5 minutos máx
include 'db_config.php';

$dbf_file = 'ZETH53.DBF';

if (!file_exists($dbf_file)) die("Error: No se encuentra $dbf_file");
if (!function_exists('dbase_open')) die("Error: La extensión PHP 'dbase' no está activa.");

echo "<h3>Iniciando importación de Kardex...</h3>";
flush();

// 1. Limpiar tabla anterior (Carga total)
$conn->query("TRUNCATE TABLE kardex_zeth");

// 2. Leer DBF
$dbf = dbase_open($dbf_file, 0);
$num_registros = dbase_numrecords($dbf);
echo "Registros encontrados: $num_registros<br>";

$batch_size = 500;
$values = [];
$count = 0;

for ($i = 1; $i <= $num_registros; $i++) {
    $row = dbase_get_record_with_names($dbf, $i);
    
    // Mapeo según tu indicación:
    $codigo = $conn->real_escape_string(trim($row['PRONUM']));
    $tipo   = $conn->real_escape_string(trim($row['TYPMOV']));
    $cant   = floatval($row['STKSED']);
    $fecha  = trim($row['DTOMOV']); // Formato YYYYMMDD
    
    // Formatear Fecha
    if(strlen($fecha) == 8) {
        $fecha_fmt = substr($fecha,0,4).'-'.substr($fecha,4,2).'-'.substr($fecha,6,2);
    } else {
        $fecha_fmt = date('Y-m-d'); // Fallback
    }

    if ($codigo !== '') {
        $values[] = "('$codigo', '$tipo', '$cant', '$fecha_fmt')";
        $count++;
    }

    // Insertar en bloques
    if (count($values) >= $batch_size) {
        $sql = "INSERT INTO kardex_zeth (codigo_producto, tipo_movimiento, cantidad, fecha_movimiento) VALUES " . implode(',', $values);
        $conn->query($sql);
        $values = [];
    }
}

// Insertar restantes
if (!empty($values)) {
    $sql = "INSERT INTO kardex_zeth (codigo_producto, tipo_movimiento, cantidad, fecha_movimiento) VALUES " . implode(',', $values);
    $conn->query($sql);
}

dbase_close($dbf);
echo "<h3 style='color:green'>¡Importación Completada! $count movimientos procesados.</h3>";
?>