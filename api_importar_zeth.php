<?php
// api_importar_zeth.php
// VERSIÓN FINAL BLINDADA Y CORREGIDA:
// 1. Lectura por Nombres + Respaldo por Posición.
// 2. Corrección de Ceros (Padding).
// 3. Solución a Error de Duplicados (ON DUPLICATE KEY UPDATE).

header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');
date_default_timezone_set('America/Lima');
ini_set('memory_limit', '1024M');
set_time_limit(600);

include 'db_config.php';

// --- CLASE LECTORA DBF ---
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
            if ($buf[0] === '*') continue; 

            $record = [];
            $pos = 1; 
            foreach ($this->columns as $col) {
                $val = substr($buf, $pos, $col['len']);
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

// Función auxiliar para buscar valor por claves o fallback
function findVal($row, $keys) {
    foreach ($keys as $k) { if (isset($row[$k]) && $row[$k] !== '') return $row[$k]; }
    return '';
}

// --- PROCESO PRINCIPAL ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['dbf_file'])) {
    try {
        $sede = strtoupper(trim($_POST['sede_destino']));
        $tipo = $_POST['tipo_archivo'];
        $tmpFile = $_FILES['dbf_file']['tmp_name'];
        $fileName = $_FILES['dbf_file']['name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // BACKUP MAESTRO (Solo inventario)
        if ($tipo === 'inventario' && $ext !== 'csv') {
            $backupDir = __DIR__ . '/dbf_backups/';
            if (!file_exists($backupDir)) { mkdir($backupDir, 0777, true); }
            $backupPath = $backupDir . "maestro_inv_" . $sede . ".dbf";
            copy($tmpFile, $backupPath);
        }

        $dataRows = [];
        if ($ext === 'csv' || $ext === 'txt') {
            // Lógica CSV (omitida por brevedad)
        } else {
            $dbf = new NativeDBF($tmpFile);
            $dataRows = $dbf->getRecords();
            $dbf->close();
        }

        // --- CASO 1: ZETH19 (Principios) ---
        if ($tipo === 'principios') {
            $conn->query("DELETE FROM principios_zeth WHERE sede = '$sede'");
            // CORRECCIÓN DUPLICADOS: Si el código existe, actualiza el nombre.
            $stmt = $conn->prepare("INSERT INTO principios_zeth (sede, cod_sub, des_sub) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE des_sub = VALUES(des_sub)");
            $total = 0;
            foreach ($dataRows as $row) {
                $rowValues = array_values($row); 
                
                $cod = trim(findVal($row, ['COD_SUB', 'CODIGO']));
                if ($cod === '' && isset($rowValues[0])) $cod = trim($rowValues[0]);

                $des = trim(findVal($row, ['DES_SUB', 'DESCRIPCION']));
                if ($des === '' && isset($rowValues[1])) $des = trim($rowValues[1]);

                if ($cod !== '') { 
                    $stmt->bind_param("sss", $sede, $cod, $des); 
                    $stmt->execute(); 
                    $total++; 
                }
            }
            echo json_encode(['success'=>true, 'message'=>"ZETH19 (Principios): $total registros cargados en $sede."]);
        } 
        // --- CASO 2: ZETH14 (Lineas / Categorias) ---
        else if ($tipo === 'lineas') {
            $conn->query("DELETE FROM lineas_zeth WHERE sede = '$sede'");
            // CORRECCIÓN DUPLICADOS
            $stmt = $conn->prepare("INSERT INTO lineas_zeth (sede, cod_lin, des_lin) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE des_lin = VALUES(des_lin)");
            $total = 0;
            foreach ($dataRows as $row) {
                $rowValues = array_values($row);

                $cod = trim(findVal($row, ['COD_LIN', 'CODIGO']));
                if ($cod === '' && isset($rowValues[0])) $cod = trim($rowValues[0]);

                $des = trim(findVal($row, ['DES_LIN', 'DESCRIPCION']));
                if ($des === '' && isset($rowValues[1])) $des = trim($rowValues[1]);

                if ($cod !== '') { 
                    $stmt->bind_param("sss", $sede, $cod, $des); 
                    $stmt->execute(); 
                    $total++; 
                }
            }
            echo json_encode(['success'=>true, 'message'=>"ZETH14 (Líneas): $total registros cargados en $sede."]);
        }
        // --- CASO 3: ZETH15 (Marcas / Subcategorias) ---
        else if ($tipo === 'marcas') {
            $conn->query("DELETE FROM marcas_zeth WHERE sede = '$sede'");
            // CORRECCIÓN DUPLICADOS: Esto soluciona el error de 'UPHA'
            $stmt = $conn->prepare("INSERT INTO marcas_zeth (sede, cod_mar, des_mar) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE des_mar = VALUES(des_mar)");
            $total = 0;
            foreach ($dataRows as $row) {
                $rowValues = array_values($row);

                $cod = trim(findVal($row, ['MAR_PRD', 'COD_MAR', 'CODIGO']));
                if ($cod === '' && isset($rowValues[0])) $cod = trim($rowValues[0]);

                $des = trim(findVal($row, ['DES_MAR', 'DESCRIPCION']));
                if ($des === '' && isset($rowValues[1])) $des = trim($rowValues[1]);

                if ($cod !== '') { 
                    $stmt->bind_param("sss", $sede, $cod, $des); 
                    $stmt->execute(); 
                    $total++; 
                }
            }
            echo json_encode(['success'=>true, 'message'=>"ZETH15 (Marcas): $total registros cargados en $sede."]);
        }
        // --- CASO 4: ZETH70 (Inventario) ---
        else {
            $stmtCheck = $conn->prepare("SELECT id FROM inventario_zeth WHERE codigo = ? AND sede = ?");
            $stmtUpd = $conn->prepare("UPDATE inventario_zeth SET nombre=?, sublin=?, stock=?, costo=?, precio=?, lineaz=?, marcaz=? WHERE id=?");
            $stmtIns = $conn->prepare("INSERT INTO inventario_zeth (codigo, nombre, sublin, stock, costo, precio, lineaz, marcaz, sede) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $act = 0; $nue = 0;
            foreach ($dataRows as $row) {
                $rowValues = array_values($row);

                // 1. CÓDIGO (Columna 0 usualmente)
                $codigo = trim(findVal($row, ['PRONUM', 'CODIGO']));
                if ($codigo === '' && isset($rowValues[0])) $codigo = trim($rowValues[0]);

                // 2. NOMBRE (Columna 1 usualmente)
                $nombre = trim(findVal($row, ['DESCRI', 'DESCRIPCION']));
                if ($nombre === '' && isset($rowValues[1])) $nombre = trim($rowValues[1]);

                $sublin = trim(findVal($row, ['SUBLIN'])); 
                
                // 3. MARCA (Columna 4 en ZETH70 -> Índice 3)
                $marcaz = trim(findVal($row, ['MARCAZ', 'MAR_PRD', 'MAR', 'MARCA', 'REF_MARCA']));
                if ($marcaz === '' && isset($rowValues[3])) { 
                    $marcaz = trim($rowValues[3]); 
                }
                
                // CORRECCIÓN DE CEROS: Si es '1', convertir a '0001'
                if ($marcaz !== '' && ctype_digit($marcaz) && strlen($marcaz) < 4) {
                    $marcaz = str_pad($marcaz, 4, '0', STR_PAD_LEFT);
                }

                // 4. LINEA (Columna 5 en ZETH70 -> Índice 4)
                $lineaz = trim(findVal($row, ['LINEAZ', 'COD_LIN', 'LIN', 'LINEA', 'REF_LINEA']));
                if ($lineaz === '' && isset($rowValues[4])) { 
                    $lineaz = trim($rowValues[4]); 
                }

                $stk = floatval(str_replace(',', '', findVal($row, ['TOTSTK', 'STK_ACT', 'STOCK'])));
                
                $costoRaw = findVal($row, ['ULCOSREP']); 
                if (empty($costoRaw)) $costoRaw = findVal($row, ['PRECOS', 'COSTO']);
                $costo = floatval(str_replace(',', '', $costoRaw));
                
                $precio = floatval(str_replace(',', '', findVal($row, ['PREVPU', 'PRECIO', 'PVP'])));

                if ($codigo !== '') {
                    $stmtCheck->bind_param("ss", $codigo, $sede);
                    $stmtCheck->execute();
                    $stmtCheck->store_result();
                    if ($stmtCheck->num_rows > 0) {
                        $stmtCheck->bind_result($eid); $stmtCheck->fetch();
                        $stmtUpd->bind_param("ssdddssi", $nombre, $sublin, $stk, $costo, $precio, $lineaz, $marcaz, $eid);
                        $stmtUpd->execute(); $act++;
                    } else {
                        $stmtIns->bind_param("sssdddsss", $codigo, $nombre, $sublin, $stk, $costo, $precio, $lineaz, $marcaz, $sede);
                        $stmtIns->execute(); $nue++;
                    }
                }
            }
            $stmtCheck->close(); $stmtUpd->close(); $stmtIns->close();
            echo json_encode(['success'=>true, 'message'=>"ZETH70: Inventario actualizado ($act act, $nue nuevos) con Líneas ($lineaz) y Marcas calculadas."]);
        }

    } catch (Exception $e) {
        echo json_encode(['success'=>false, 'message'=>'Error: ' . $e->getMessage()]);
    }
    $conn->close();
} else {
    echo json_encode(['success'=>false, 'message'=>'Archivo no recibido.']);
}
?>