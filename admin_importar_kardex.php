<?php
// admin_importar_kardex.php
// Recibe el archivo ZETH53.DBF subido desde el frontend y lo procesa.

header('Content-Type: application/json');
include 'db_config.php';

// Verificar extensión dbase
if (!function_exists('dbase_open')) {
    echo json_encode(['success' => false, 'message' => 'Error Crítico: La extensión "dbase" de PHP no está activada en este servidor.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['dbf_file'])) {
    $file = $_FILES['dbf_file'];
    
    // Validaciones básicas
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Error al subir el archivo. Código: ' . $file['error']]);
        exit;
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'dbf') {
        echo json_encode(['success' => false, 'message' => 'El archivo debe ser un .DBF válido.']);
        exit;
    }

    $tmpPath = $file['tmp_name'];

    // 1. Limpiar tabla anterior (Reemplazo total)
    $conn->query("TRUNCATE TABLE kardex_zeth");

    // 2. Procesar DBF
    $dbf = @dbase_open($tmpPath, 0);
    if (!$dbf) {
        echo json_encode(['success' => false, 'message' => 'No se pudo leer el archivo DBF. Verifica que no esté corrupto.']);
        exit;
    }

    $num_registros = dbase_numrecords($dbf);
    $batch_size = 500;
    $values = [];
    $count = 0;

    for ($i = 1; $i <= $num_registros; $i++) {
        $row = dbase_get_record_with_names($dbf, $i);
        
        // Mapeo de campos ZETH53
        // PRONUM -> codigo_producto
        // TYPMOV -> tipo_movimiento (CC, DV, etc)
        // STKSED -> cantidad
        // DTOMOV -> fecha (YYYYMMDD)

        $codigo = $conn->real_escape_string(trim($row['PRONUM'] ?? ''));
        $tipo   = $conn->real_escape_string(trim($row['TYPMOV'] ?? ''));
        $cant   = floatval($row['STKSED'] ?? 0);
        $fechaRaw  = trim($row['DTOMOV'] ?? '');

        // Formatear Fecha
        $fecha_fmt = date('Y-m-d'); // Default hoy
        if (strlen($fechaRaw) === 8) {
            $fecha_fmt = substr($fechaRaw, 0, 4) . '-' . substr($fechaRaw, 4, 2) . '-' . substr($fechaRaw, 6, 2);
        }

        if ($codigo !== '') {
            $values[] = "('$codigo', '$tipo', '$cant', '$fecha_fmt')";
            $count++;
        }

        // Insertar en Bloques para velocidad
        if (count($values) >= $batch_size) {
            $sql = "INSERT INTO kardex_zeth (codigo_producto, tipo_movimiento, cantidad, fecha_movimiento) VALUES " . implode(',', $values);
            if (!$conn->query($sql)) {
                // Si falla un bloque, continuamos con el siguiente (o podrías detener)
            }
            $values = [];
        }
    }

    // Insertar restantes
    if (!empty($values)) {
        $sql = "INSERT INTO kardex_zeth (codigo_producto, tipo_movimiento, cantidad, fecha_movimiento) VALUES " . implode(',', $values);
        $conn->query($sql);
    }

    dbase_close($dbf);

    echo json_encode([
        'success' => true, 
        'message' => "Proceso completado. Se importaron $count movimientos al Kardex."
    ]);
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'No se recibió ningún archivo.']);
    exit;
}
?>