<?php
// admin_importar_kardex.php
// V18.0: Corrección de Cruce de Sedes. 
// Agrega columna 'sede' a la BD y filtra el borrado por sede.

header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');
ini_set('display_errors', 0);
ini_set('memory_limit', '512M');
set_time_limit(600);

$uploadDir = __DIR__ . '/';
$finalName = 'ZETH53.DBF';

// --- FASE 1: RECEPCIÓN DE TROZOS (Chunk Upload) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_chunk'])) {
    $chunk = $_FILES['file_chunk'];
    $chunkNum = isset($_POST['chunk_index']) ? intval($_POST['chunk_index']) : 0;
    $tempPath = $uploadDir . $finalName . '.part';

    if ($chunkNum === 0 && file_exists($tempPath)) unlink($tempPath);

    $input = fopen($chunk['tmp_name'], 'rb');
    $output = fopen($tempPath, 'ab'); 
    
    if ($input && $output) {
        while ($buff = fread($input, 4096)) fwrite($output, $buff);
        fclose($input);
        fclose($output);
        
        if (isset($_POST['is_last']) && $_POST['is_last'] === 'true') {
            if (file_exists($uploadDir . $finalName)) unlink($uploadDir . $finalName);
            rename($tempPath, $uploadDir . $finalName);
            echo json_encode(['status' => 'done', 'message' => 'Subida completada.']);
        } else {
            echo json_encode(['status' => 'ok', 'chunk' => $chunkNum]);
        }
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Error escribiendo archivo.']);
    }
    exit;
}

// --- FASE 2: IMPORTACIÓN A BASE DE DATOS ---
if (isset($_POST['action']) && $_POST['action'] === 'importar') {
    include 'db_config.php';
    
    // 1. Recibir la Sede (Obligatorio)
    $sede = isset($_POST['sede']) ? $conn->real_escape_string($_POST['sede']) : 'HUAURA';
    
    // 2. AUTO-CORRECCIÓN: Crear columna sede si no existe
    // Esto soluciona el problema de que todos los datos se mezclen
    $checkCol = $conn->query("SHOW COLUMNS FROM kardex_zeth LIKE 'sede'");
    if ($checkCol && $checkCol->num_rows == 0) {
        $conn->query("ALTER TABLE kardex_zeth ADD COLUMN sede VARCHAR(50) DEFAULT 'HUAURA'");
        $conn->query("CREATE INDEX idx_kardex_sede ON kardex_zeth(sede, codigo_producto)");
    }

    // 3. Clase Lector DBF Nativo
    class NativeDBF_Final {
        private $fp, $header; public $columns = [];
        public function __construct($file) {
            if (!file_exists($file)) throw new Exception("El archivo no se encuentra. Error en subida.");
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
        public function getRecordsGenerator() {
            fseek($this->fp, $this->header['header_len']);
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
        $file = $uploadDir . $finalName;
        $dbf = new NativeDBF_Final($file);
        
        // 4. LIMPIEZA CORRECTA: Borrar SOLO datos de ESTA sede
        // Así no borramos Huacho cuando subimos Huaura
        $conn->query("DELETE FROM kardex_zeth WHERE sede = '$sede'");
        
        $values = []; 
        $total = 0; 
        $batch = 200; 
        $f_corte = '20250301'; 
        
        foreach ($dbf->getRecordsGenerator() as $r) {
            $fecha = $r['DTOMOV'] ?? '';
            if ($fecha < $f_corte) continue;
            
            $cod = $conn->real_escape_string($r['PRONUM']??'');
            $tip = $conn->real_escape_string($r['TYPMOV']??'');
            $cant = floatval($r['STKSED']??0);
            $f_sql = (strlen($fecha)==8) ? substr($fecha,0,4).'-'.substr($fecha,4,2).'-'.substr($fecha,6,2) : date('Y-m-d');
            
            if($cod) {
                // Insertamos CON la sede
                $values[] = "('$cod','$tip','$cant','$f_sql','$sede')";
                $total++;
            }
            
            if (count($values) >= $batch) {
                $sql = "INSERT INTO kardex_zeth (codigo_producto, tipo_movimiento, cantidad, fecha_movimiento, sede) VALUES " . implode(',', $values);
                if (!$conn->query($sql)) throw new Exception("Error MySQL: " . $conn->error);
                $values = [];
                usleep(50000); 
            }
        }
        
        if (!empty($values)) {
            $sql = "INSERT INTO kardex_zeth (codigo_producto, tipo_movimiento, cantidad, fecha_movimiento, sede) VALUES " . implode(',', $values);
            if (!$conn->query($sql)) throw new Exception("Error MySQL Final: " . $conn->error);
        }
        
        echo json_encode(['success' => true, 'count' => $total, 'message' => "Importados $total registros para la sede $sede."]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>