<?php
// uploader_pro.php
// HERRAMIENTA DE SUBIDA POR FRAGMENTOS (Bypass PHP Upload Limit)
// Corta el archivo en trozos de 1MB y los une en el servidor.

header("Access-Control-Allow-Origin: *");
ini_set('display_errors', 0);
ini_set('memory_limit', '512M');
set_time_limit(300);

$uploadDir = __DIR__ . '/';
$finalName = 'ZETH53.DBF';

// --- 1. RECEPCIÓN DE TROZOS (BACKEND) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_chunk'])) {
    $chunk = $_FILES['file_chunk'];
    $chunkNum = isset($_POST['chunk_index']) ? intval($_POST['chunk_index']) : 0;
    
    // Nombre temporal mientras se sube
    $tempPath = $uploadDir . $finalName . '.part';

    // Si es el primer trozo, creamos/limpiamos el archivo
    if ($chunkNum === 0) {
        if (file_exists($tempPath)) unlink($tempPath);
    }

    // Pegar el trozo al final del archivo temporal
    $input = fopen($chunk['tmp_name'], 'rb');
    $output = fopen($tempPath, 'ab'); // 'ab' = append binary
    
    if ($input && $output) {
        while ($buff = fread($input, 4096)) {
            fwrite($output, $buff);
        }
        fclose($input);
        fclose($output);
        
        // Si es el último trozo (según bandera de JS), renombramos al final
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

// --- 2. LÓGICA DE IMPORTACIÓN (BACKEND) ---
if (isset($_POST['action']) && $_POST['action'] === 'importar') {
    include 'db_config.php';
    
    class NativeDBF_Simple {
        private $fp, $header; public $columns = [];
        public function __construct($file) {
            if (!file_exists($file)) throw new Exception("Archivo no encontrado.");
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
        $dbf = new NativeDBF_Simple($file);
        
        $conn->query("TRUNCATE TABLE kardex_zeth");
        
        $values = []; $total = 0; $batch = 200; $f_corte = '20250301';
        
        foreach ($dbf->getRecords() as $r) {
            $fecha = $r['DTOMOV'] ?? '';
            if ($fecha < $f_corte) continue;
            
            $cod = $conn->real_escape_string($r['PRONUM']??'');
            $tip = $conn->real_escape_string($r['TYPMOV']??'');
            $cant = floatval($r['STKSED']??0);
            $f_sql = (strlen($fecha)==8)?substr($fecha,0,4).'-'.substr($fecha,4,2).'-'.substr($fecha,6,2):date('Y-m-d');
            
            if($cod) {
                $values[] = "('$cod','$tip','$cant','$f_sql')";
                $total++;
            }
            
            if (count($values) >= $batch) {
                $conn->query("INSERT INTO kardex_zeth (codigo_producto, tipo_movimiento, cantidad, fecha_movimiento) VALUES " . implode(',', $values));
                $values = [];
                usleep(50000); 
            }
        }
        if (!empty($values)) {
            $conn->query("INSERT INTO kardex_zeth (codigo_producto, tipo_movimiento, cantidad, fecha_movimiento) VALUES " . implode(',', $values));
        }
        
        echo json_encode(['success' => true, 'count' => $total]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uploader PRO ZETH</title>
    <style>
        body { font-family: sans-serif; background: #f8fafc; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 400px; text-align: center; }
        h2 { color: #0f172a; margin-top: 0; }
        .btn { background: #2563eb; color: white; border: none; padding: 12px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; width: 100%; font-size: 1rem; margin-top: 15px; }
        .btn:disabled { background: #cbd5e1; cursor: not-allowed; }
        .btn-success { background: #10b981; }
        #progress-container { width: 100%; background: #e2e8f0; height: 10px; border-radius: 5px; margin-top: 20px; display: none; overflow: hidden; }
        #progress-bar { height: 100%; background: #2563eb; width: 0%; transition: width 0.2s; }
        #status { margin-top: 10px; font-size: 0.9rem; color: #64748b; }
        .file-box { border: 2px dashed #cbd5e1; padding: 20px; border-radius: 8px; cursor: pointer; transition: 0.2s; }
        .file-box:hover { border-color: #2563eb; background: #eff6ff; }
    </style>
</head>
<body>

<div class="card">
    <h2>Subida Inteligente</h2>
    <p style="color:#64748b; font-size:0.9rem;">Evade el error "Código 1" subiendo por partes.</p>
    
    <div class="file-box" onclick="document.getElementById('fileInput').click()">
        <span id="fileName">📂 Toca para elegir ZETH53.DBF</span>
    </div>
    <input type="file" id="fileInput" accept=".dbf" style="display:none" onchange="handleFile()">

    <div id="progress-container"><div id="progress-bar"></div></div>
    <div id="status">Esperando archivo...</div>

    <button id="btnUpload" class="btn" onclick="startUpload()" disabled>Iniciar Subida</button>
    <button id="btnImport" class="btn btn-success" onclick="startImport()" style="display:none;">⚡ PROCESAR KARDEX</button>
</div>

<script>
    let selectedFile = null;
    const CHUNK_SIZE = 1024 * 1024; // 1MB por trozo

    function handleFile() {
        const input = document.getElementById('fileInput');
        if (input.files.length > 0) {
            selectedFile = input.files[0];
            document.getElementById('fileName').innerText = "📄 " + selectedFile.name + " (" + (selectedFile.size/1024/1024).toFixed(2) + " MB)";
            document.getElementById('btnUpload').disabled = false;
            document.getElementById('status').innerText = "Archivo listo.";
        }
    }

    async function startUpload() {
        if (!selectedFile) return;
        
        const btn = document.getElementById('btnUpload');
        btn.disabled = true;
        btn.innerText = "Subiendo...";
        document.getElementById('progress-container').style.display = 'block';
        
        const totalChunks = Math.ceil(selectedFile.size / CHUNK_SIZE);
        let chunkIndex = 0;

        // Función recursiva para subir trozos
        async function uploadNextChunk() {
            const start = chunkIndex * CHUNK_SIZE;
            const end = Math.min(start + CHUNK_SIZE, selectedFile.size);
            const chunk = selectedFile.slice(start, end);

            const formData = new FormData();
            formData.append('file_chunk', chunk);
            formData.append('chunk_index', chunkIndex);
            formData.append('is_last', (chunkIndex === totalChunks - 1));

            try {
                const res = await fetch('uploader_pro.php', { method: 'POST', body: formData });
                const json = await res.json();

                if (json.status === 'ok' || json.status === 'done') {
                    chunkIndex++;
                    const percent = Math.round((chunkIndex / totalChunks) * 100);
                    document.getElementById('progress-bar').style.width = percent + '%';
                    document.getElementById('status').innerText = `Subiendo: ${percent}%`;

                    if (chunkIndex < totalChunks) {
                        uploadNextChunk(); // Siguiente trozo
                    } else {
                        // TERMINADO
                        document.getElementById('status').innerText = "✅ Subida completada con éxito.";
                        btn.style.display = 'none';
                        document.getElementById('btnImport').style.display = 'block';
                    }
                } else {
                    alert("Error: " + json.message);
                    btn.disabled = false;
                }
            } catch (e) {
                alert("Error de red: " + e);
                btn.disabled = false;
            }
        }

        uploadNextChunk();
    }

    async function startImport() {
        const btn = document.getElementById('btnImport');
        btn.disabled = true;
        btn.innerText = "⏳ Procesando... (No cierres)";
        
        const formData = new FormData();
        formData.append('action', 'importar');

        try {
            const res = await fetch('uploader_pro.php', { method: 'POST', body: formData });
            const json = await res.json();
            
            if (json.success) {
                alert("¡IMPORTACIÓN EXITOSA!\nSe cargaron " + json.count + " registros.");
                document.getElementById('status').innerText = "🎉 Proceso terminado correctamente.";
                btn.innerText = "¡Listo!";
            } else {
                alert("Error importando: " + json.message);
                btn.disabled = false;
                btn.innerText = "Reintentar";
            }
        } catch (e) {
            alert("Error fatal: " + e);
        }
    }
</script>
</body>
</html>