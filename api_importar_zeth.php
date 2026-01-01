<?php
// api_importar_zeth.php
// CORREGIDO: Prioriza ULCOSREP para costo y limpia datos para evitar lag.
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');
date_default_timezone_set('America/Lima');
ini_set('memory_limit', '1024M');
set_time_limit(600);

include 'db_config.php';

// --- CLASE LECTORA DE DBF NATIVA ---
class NativeDBF {
    private $fp;
    private $header;
    public $columns = [];

    public function __construct($file) {
        if (!file_exists($file)) throw new Exception("Archivo no encontrado.");
        $this->fp = fopen($file, 'rb');
        if (!$this->fp) throw new Exception("No se puede leer el archivo.");
        $this->readHeader();
    }

    private function readHeader() {
        $buf = fread($this->fp, 32);
        $data = unpack("Vnum_records/vheader_len/vrecord_len", substr($buf, 4, 8));
        $this->header = $data;

        fseek($this->fp, 32);
        while (ftell($this->fp) < $this->header['header_len'] - 1) {
            $buf = fread($this->fp, 32);
            if (ord($buf[0]) == 0x0D) break; 
            
            $rawName = substr($buf, 0, 11);
            // Limpieza de nombres de columna
            $name = strtoupper(trim(preg_replace('/[^\x20-\x7E]/', '', $rawName)));
            $len = ord($buf[16]);
            
            $this->columns[] = ['name' => $name, 'len' => $len];
        }
    }

    public function getRecords() {
        $records = [];
        $count = $this->header['num_records'];
        $recLen = $this->header['record_len'];
        fseek($this->fp, $this->header['header_len']);

        for ($i = 0; $i < $count; $i++) {
            $buf = fread($this->fp, $recLen);
            if (strlen($buf) < $recLen) break;
            
            if ($buf[0] === '*') continue; // Registro borrado

            $record = [];
            $pos = 1; 
            foreach ($this->columns as $col) {
                $val = substr($buf, $pos, $col['len']);
                // Convertir a UTF-8 y limpiar espacios AQUI para evitar LAG después
                $val = mb_convert_encoding($val, 'UTF-8', 'CP850');
                $record[$col['name']] = trim($val); 
                $pos += $col['len'];
            }
            $records[] = $record;
        }
        return $records;
    }
    public function close() { if ($this->fp) fclose($this->fp); }
}

// --- LECTOR CSV ---
function readCSV($file) {
    $rows = [];
    if (($handle = fopen($file, "r")) !== FALSE) {
        $line1 = fgets($handle);
        $delimiter = (substr_count($line1, ';') > substr_count($line1, ',')) ? ';' : ',';
        rewind($handle);
        $header = null;
        while (($data = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
            $data = array_map(function($d) { return mb_convert_encoding(trim($d), 'UTF-8', 'ISO-8859-1'); }, $data);
            if (!$header) { 
                $header = array_map(function($h) { return strtoupper(trim($h)); }, $data); 
            } else { 
                if (count($header) === count($data)) $rows[] = array_combine($header, $data); 
            }
        }
        fclose($handle);
    }
    return $rows;
}

// --- BÚSQUEDA ---
function findVal($row, $keys) {
    foreach ($keys as $k) {
        if (isset($row[$k]) && $row[$k] !== '') return $row[$k];
    }
    return '';
}

// --- PROCESO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['dbf_file'])) {
    try {
        $sede = strtoupper(trim($_POST['sede_destino']));
        $tipo = $_POST['tipo_archivo'];
        $tmpFile = $_FILES['dbf_file']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['dbf_file']['name'], PATHINFO_EXTENSION));

        $dataRows = [];
        if ($ext === 'csv' || $ext === 'txt') {
            $dataRows = readCSV($tmpFile);
        } else {
            $dbf = new NativeDBF($tmpFile);
            $dataRows = $dbf->getRecords();
            $dbf->close();
        }

        $totalProcesados = 0;

        if ($tipo === 'principios') {
            $conn->query("DELETE FROM principios_zeth WHERE sede = '$sede'");
            $stmt = $conn->prepare("INSERT INTO principios_zeth (sede, cod_sub, des_sub) VALUES (?, ?, ?)");
            
            foreach ($dataRows as $row) {
                // Limpieza vital para JOIN rápido
                $cod = trim(findVal($row, ['COD_SUB', 'CODIGO', 'SUBLIN']));
                $des = trim(findVal($row, ['DES_SUB', 'DESCRIPCION', 'DESCRI']));

                if ($cod !== '') {
                    $stmt->bind_param("sss", $sede, $cod, $des);
                    $stmt->execute();
                    $totalProcesados++;
                }
            }
            $stmt->close();
            echo json_encode(['success'=>true, 'message'=>"ZETH19: $totalProcesados principios cargados."]);
        } 
        else {
            // MODO INVENTARIO
            $stmtCheck = $conn->prepare("SELECT id FROM inventario_zeth WHERE codigo = ? AND sede = ?");
            // Orden correcto para bind_param: sublin, nombre, categoria, stock, costo, precio, id
            $stmtUpd = $conn->prepare("UPDATE inventario_zeth SET sublin=?, nombre=?, categoria=?, stock=?, costo=?, precio=? WHERE id=?");
            // Orden correcto para bind_param: codigo, nombre, categoria, sublin, stock, costo, precio, sede
            $stmtIns = $conn->prepare("INSERT INTO inventario_zeth (codigo, nombre, categoria, sublin, stock, costo, precio, sede) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

            $act = 0; $nue = 0;

            foreach ($dataRows as $row) {
                $codigo = trim(findVal($row, ['PRONUM', 'CODIGO']));
                $nombre = trim(findVal($row, ['DESCRI', 'DESCRIPCION']));
                $sublin = trim(findVal($row, ['SUBLIN', 'SUB_LIN']));
                $lin    = strtoupper(trim(findVal($row, ['LINEAZ', 'LINEA'])));
                
                $stk = floatval(str_replace(',', '', findVal($row, ['TOTSTK', 'STK_ACT'])));
                
                // PRIORIDAD DE COSTO: 1. ULCOSREP, 2. PRECOS
                $costoRaw = findVal($row, ['ULCOSREP']); // Tu columna clave
                if (empty($costoRaw) || floatval($costoRaw) == 0) {
                    $costoRaw = findVal($row, ['PRECOS', 'PRE_COS']);
                }
                $costo = floatval(str_replace(',', '', $costoRaw));
                
                // Precio Venta
                $precio = floatval(str_replace(',', '', findVal($row, ['PREVPU', 'PRE_VTA', 'PRECIO'])));

                // Clasificación
                $cat = 'OTROS'; 
                if (strpos($lin, 'IM') !== false || strpos($lin, 'INS') !== false || strpos($lin, 'MAT') !== false) $cat = 'INSUMOS MEDICOS';
                elseif (strpos($lin, 'SU') !== false) $cat = 'SUPLEMENTOS';
                elseif (strpos($lin, 'FR') !== false || strpos($lin, 'FA') !== false || strpos($lin, 'A') !== false) $cat = 'FARMACIA';

                if ($codigo !== '') {
                    $stmtCheck->bind_param("ss", $codigo, $sede);
                    $stmtCheck->execute();
                    $stmtCheck->store_result();
                    
                    if ($stmtCheck->num_rows > 0) {
                        $stmtCheck->bind_result($eid);
                        $stmtCheck->fetch();
                        $stmtUpd->bind_param("ssssddi", $sublin, $nombre, $cat, $stk, $costo, $precio, $eid);
                        $stmtUpd->execute();
                        $act++;
                    } else {
                        $stmtIns->bind_param("ssssddds", $codigo, $nombre, $cat, $sublin, $stk, $costo, $precio, $sede);
                        $stmtIns->execute();
                        $nue++;
                    }
                }
            }
            
            // Actualizar Historial
            $totalVal = $conn->query("SELECT SUM(stock * costo) as t FROM inventario_zeth WHERE sede='$sede'")->fetch_assoc()['t'];
            $conn->query("INSERT INTO historial_cargas (sede, total_valor) VALUES ('$sede', '$totalVal')");
            $conn->query("INSERT INTO configuracion_sedes (sede, ultima_actualizacion) VALUES ('$sede', NOW()) ON DUPLICATE KEY UPDATE ultima_actualizacion = NOW()");

            $stmtCheck->close(); $stmtUpd->close(); $stmtIns->close();
            echo json_encode(['success'=>true, 'message'=>"Procesado. Precios (ULCOSREP) actualizados. Act: $act, Nuevos: $nue."]);
        }

    } catch (Exception $e) {
        echo json_encode(['success'=>false, 'message'=>'Error: ' . $e->getMessage()]);
    }
    $conn->close();
} else {
    echo json_encode(['success'=>false, 'message'=>'Archivo no recibido.']);
}
?>