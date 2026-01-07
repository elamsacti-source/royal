<?php
// api_importar_zeth.php
// IMPORTADOR MULTI-ARCHIVO: Zeth70 (Inv), Zeth19 (Principios), Zeth14 (Lineas), Zeth15 (Marcas)
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

        // BACKUP MAESTRO (Solo para Inventario Zeth70 por ahora, o extender si se desea)
        if ($tipo === 'inventario' && $ext !== 'csv') {
            $backupDir = __DIR__ . '/dbf_backups/';
            if (!file_exists($backupDir)) { mkdir($backupDir, 0777, true); }
            $backupPath = $backupDir . "maestro_inv_" . $sede . ".dbf";
            copy($tmpFile, $backupPath);
        }

        $dataRows = [];
        if ($ext === 'csv' || $ext === 'txt') {
            // Lógica CSV simple si fuera necesario...
        } else {
            $dbf = new NativeDBF($tmpFile);
            $dataRows = $dbf->getRecords();
            $dbf->close();
        }

        // --- CASO 1: ZETH19 (Principios) ---
        if ($tipo === 'principios') {
            $conn->query("DELETE FROM principios_zeth WHERE sede = '$sede'");
            $stmt = $conn->prepare("INSERT INTO principios_zeth (sede, cod_sub, des_sub) VALUES (?, ?, ?)");
            $total = 0;
            foreach ($dataRows as $row) {
                $cod = trim(findVal($row, ['COD_SUB', 'CODIGO']));
                $des = trim(findVal($row, ['DES_SUB', 'DESCRIPCION']));
                if ($cod !== '') { $stmt->bind_param("sss", $sede, $cod, $des); $stmt->execute(); $total++; }
            }
            echo json_encode(['success'=>true, 'message'=>"ZETH19 (Principios): $total registros cargados en $sede."]);
        } 
        // --- CASO 2: ZETH14 (Lineas / Categorias) ---
        else if ($tipo === 'lineas') {
            $conn->query("DELETE FROM lineas_zeth WHERE sede = '$sede'");
            $stmt = $conn->prepare("INSERT INTO lineas_zeth (sede, cod_lin, des_lin) VALUES (?, ?, ?)");
            $total = 0;
            foreach ($dataRows as $row) {
                $cod = trim(findVal($row, ['COD_LIN', 'CODIGO']));
                $des = trim(findVal($row, ['DES_LIN', 'DESCRIPCION']));
                if ($cod !== '') { $stmt->bind_param("sss", $sede, $cod, $des); $stmt->execute(); $total++; }
            }
            echo json_encode(['success'=>true, 'message'=>"ZETH14 (Líneas): $total registros cargados en $sede."]);
        }
        // --- CASO 3: ZETH15 (Marcas / Subcategorias) ---
        else if ($tipo === 'marcas') {
            $conn->query("DELETE FROM marcas_zeth WHERE sede = '$sede'");
            $stmt = $conn->prepare("INSERT INTO marcas_zeth (sede, cod_mar, des_mar) VALUES (?, ?, ?)");
            $total = 0;
            foreach ($dataRows as $row) {
                // User info: MAR_PRD y DES_MAR
                $cod = trim(findVal($row, ['MAR_PRD', 'COD_MAR', 'CODIGO']));
                $des = trim(findVal($row, ['DES_MAR', 'DESCRIPCION']));
                if ($cod !== '') { $stmt->bind_param("sss", $sede, $cod, $des); $stmt->execute(); $total++; }
            }
            echo json_encode(['success'=>true, 'message'=>"ZETH15 (Marcas): $total registros cargados en $sede."]);
        }
        // --- CASO 4: ZETH70 (Inventario) ---
        else {
            $stmtCheck = $conn->prepare("SELECT id FROM inventario_zeth WHERE codigo = ? AND sede = ?");
            // Se añaden campos lineaz y marcaz para guardarlos
            // Asegúrate de tener estas columnas en tu tabla inventario_zeth o el UPDATE fallará si no existen.
            // Si no existen, el script las ignora o da error. Asumiremos que existen o las añadiste.
            // Para asegurar compatibilidad, voy a actualizar solo lo básico + lo nuevo si es posible.
            // Como no puedo hacer ALTER TABLE aquí, asumiré que usas las columnas 'categoria' para LINEAZ y alguna otra para MARCAZ si quieres,
            // PERO lo ideal es añadir las columnas reales a la tabla.
            
            // Voy a usar campos genéricos o adaptar. 
            // NOTA: Para que funcione la visualización, necesitamos guardar LINEAZ y MARCAZ en inventario_zeth.
            // Ejecuta: ALTER TABLE inventario_zeth ADD COLUMN IF NOT EXISTS lineaz VARCHAR(10);
            // Ejecuta: ALTER TABLE inventario_zeth ADD COLUMN IF NOT EXISTS marcaz VARCHAR(10);
            
            // Query UPDATE optimizada con las nuevas columnas
            $stmtUpd = $conn->prepare("UPDATE inventario_zeth SET nombre=?, sublin=?, stock=?, costo=?, precio=?, lineaz=?, marcaz=? WHERE id=?");
            $stmtIns = $conn->prepare("INSERT INTO inventario_zeth (codigo, nombre, sublin, stock, costo, precio, lineaz, marcaz, sede) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $act = 0; $nue = 0;
            foreach ($dataRows as $row) {
                $codigo = trim(findVal($row, ['PRONUM', 'CODIGO']));
                $nombre = trim(findVal($row, ['DESCRI', 'DESCRIPCION']));
                $sublin = trim(findVal($row, ['SUBLIN'])); // Principio
                
                // Nuevos campos de enlace
                $lineaz = trim(findVal($row, ['LINEAZ', 'COD_LIN']));
                $marcaz = trim(findVal($row, ['MARCAZ', 'MAR_PRD']));

                $stk = floatval(str_replace(',', '', findVal($row, ['TOTSTK', 'STK_ACT'])));
                
                $costoRaw = findVal($row, ['ULCOSREP']); 
                if (empty($costoRaw)) $costoRaw = findVal($row, ['PRECOS']);
                $costo = floatval(str_replace(',', '', $costoRaw));
                
                $precio = floatval(str_replace(',', '', findVal($row, ['PREVPU', 'PRECIO'])));

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
            echo json_encode(['success'=>true, 'message'=>"ZETH70: Inventario actualizado ($act act, $nue nuevos) con Líneas y Marcas."]);
        }

    } catch (Exception $e) {
        echo json_encode(['success'=>false, 'message'=>'Error: ' . $e->getMessage()]);
    }
    $conn->close();
} else {
    echo json_encode(['success'=>false, 'message'=>'Archivo no recibido.']);
}
?>