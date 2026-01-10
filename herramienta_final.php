<?php
// herramienta_final.php
// HERRAMIENTA DE DIAGNÓSTICO Y REPARACIÓN
// Permite subir el archivo localmente y procesarlo, ignorando problemas de FTP.

// Aumentar límites para InfinityFree
ini_set('memory_limit', '512M');
set_time_limit(300);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$mensaje = "";
$tipo_msg = ""; // success, error

// --- 1. PROCESAR SUBIDA DE EMERGENCIA ---
if (isset($_FILES['archivo_emergencia'])) {
    $f = $_FILES['archivo_emergencia'];
    if ($f['error'] === UPLOAD_ERR_OK) {
        $destino = __DIR__ . '/ZETH53.DBF'; // Forzamos el nombre correcto
        if (move_uploaded_file($f['tmp_name'], $destino)) {
            $mensaje = "✅ Archivo subido correctamente a esta carpeta como ZETH53.DBF";
            $tipo_msg = "success";
        } else {
            $mensaje = "❌ Error al mover el archivo. Verifica permisos de carpeta.";
            $tipo_msg = "error";
        }
    } else {
        $mensaje = "❌ Error en la subida. Código: " . $f['error'];
        $tipo_msg = "error";
    }
}

// --- 2. LÓGICA DE IMPORTACIÓN (INCLUIDA AQUÍ MISMO) ---
if (isset($_POST['ejecutar_importacion'])) {
    include 'db_config.php';
    
    // Clase NativeDBF Minimizada
    class NativeDBF_Final {
        private $fp, $header; public $columns = [];
        public function __construct($file) {
            $this->fp = fopen($file, 'rb');
            $buf = fread($this->fp, 32);
            $this->header = unpack("Vnum_records/vheader_len/vrecord_len", substr($buf, 4, 8));
            fseek($this->fp, 32);
            while (ftell($this->fp) < $this->header['header_len'] - 1) {
                $buf = fread($this->fp, 32);
                if (ord($buf[0]) == 0x0D) break;
                $this->columns[] = ['name' => strtoupper(trim(substr($buf, 0, 11))), 'len' => ord($buf[16])];
            }
        }
        public function getRecords() {
            fseek($this->fp, $this->header['header_len']);
            $limit = 0;
            while (!feof($this->fp)) {
                $buf = fread($this->fp, $this->header['record_len']);
                if (strlen($buf) < $this->header['record_len']) break;
                if ($buf[0] === '*') continue;
                $row = []; $pos = 1;
                foreach ($this->columns as $col) {
                    $row[$col['name']] = trim(substr($buf, $pos, $col['len']));
                    $pos += $col['len'];
                }
                yield $row;
            }
        }
    }

    try {
        $archivo = __DIR__ . '/ZETH53.DBF';
        if (!file_exists($archivo)) throw new Exception("¡Aún no has subido el archivo! Usa el formulario de arriba.");

        $conn->query("TRUNCATE TABLE kardex_zeth");
        
        $dbf = new NativeDBF_Final($archivo);
        $values = []; $count = 0; $batch = 200; $fecha_corte = '20250301';

        foreach ($dbf->getRecords() as $row) {
            $fecha = $row['DTOMOV'] ?? '';
            if ($fecha < $fecha_corte) continue;

            $cod = $conn->real_escape_string($row['PRONUM'] ?? '');
            $tip = $conn->real_escape_string($row['TYPMOV'] ?? '');
            $cant = floatval($row['STKSED'] ?? 0);
            $f_fmt = (strlen($fecha)==8) ? substr($fecha,0,4).'-'.substr($fecha,4,2).'-'.substr($fecha,6,2) : date('Y-m-d');

            if ($cod) {
                $values[] = "('$cod','$tip','$cant','$f_fmt')";
                $count++;
            }

            if (count($values) >= $batch) {
                $conn->query("INSERT INTO kardex_zeth (codigo_producto, tipo_movimiento, cantidad, fecha_movimiento) VALUES " . implode(',', $values));
                $values = [];
                usleep(50000); // Pausa para CPU
            }
        }
        if (!empty($values)) {
            $conn->query("INSERT INTO kardex_zeth (codigo_producto, tipo_movimiento, cantidad, fecha_movimiento) VALUES " . implode(',', $values));
        }

        $mensaje = "🎉 ¡ÉXITO TOTAL! Se importaron $count registros.";
        $tipo_msg = "success";

    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_msg = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Herramienta Final Kardex</title>
    <style>
        body { font-family: -apple-system, system-ui, sans-serif; background: #f0f2f5; padding: 20px; max-width: 800px; margin: 0 auto; }
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        h2 { margin-top: 0; color: #1a1a1a; }
        .path { background: #e2e8f0; padding: 10px; font-family: monospace; border-radius: 6px; font-size: 0.9rem; word-break: break-all; }
        .btn { background: #2563eb; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; width: 100%; }
        .btn:hover { background: #1d4ed8; }
        .btn-green { background: #10b981; } .btn-green:hover { background: #059669; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; color: white; font-weight: bold; }
        .success { background: #10b981; } .error { background: #ef4444; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        td, th { padding: 10px; border-bottom: 1px solid #e5e7eb; text-align: left; }
    </style>
</head>
<body>

    <?php if($mensaje): ?>
        <div class="alert <?= $tipo_msg ?>"><?= $mensaje ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>1. Diagnóstico de Carpeta</h2>
        <p>Esta herramienta está ejecutándose en:</p>
        <div class="path"><?= __DIR__ ?></div>
        
        <h3>Archivos DBF encontrados aquí:</h3>
        <table>
            <?php
            $files = scandir(__DIR__);
            $found = false;
            foreach($files as $f) {
                if(strtoupper(pathinfo($f, PATHINFO_EXTENSION)) === 'DBF') {
                    $found = true;
                    $size = round(filesize($f)/1024/1024, 2);
                    echo "<tr><td><b>$f</b></td><td>$size MB</td><td style='color:green'>LISTO</td></tr>";
                }
            }
            if(!$found) echo "<tr><td colspan='3' style='color:red; text-align:center;'>NINGUNO. Debes subir el archivo abajo.</td></tr>";
            ?>
        </table>
    </div>

    <div class="card">
        <h2>2. Subida de Emergencia</h2>
        <p>Si el archivo no aparece arriba, súbelo aquí directamente:</p>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="archivo_emergencia" accept=".dbf" required style="margin-bottom: 15px; width: 100%;">
            <button type="submit" class="btn">SUBIR ARCHIVO A ESTA CARPETA</button>
        </form>
    </div>

    <div class="card" style="border: 2px solid #10b981;">
        <h2>3. Ejecutar Importación</h2>
        <p>Una vez que veas <b>ZETH53.DBF</b> en la lista de arriba, presiona este botón:</p>
        <form method="POST">
            <input type="hidden" name="ejecutar_importacion" value="1">
            <button type="submit" class="btn btn-green">PROCESAR KARDEX AHORA</button>
        </form>
    </div>

</body>
</html>