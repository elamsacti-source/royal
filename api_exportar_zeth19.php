<?php
// api_exportar_zeth19.php
// Exporta el Maestro de Principios (Zeth19) agregando (APPEND) los registros nuevos.
// VERSIÓN CORREGIDA: Mejor detección de columnas y limpieza de cabeceras.

header("Access-Control-Allow-Origin: *");
include 'db_config.php';

if (!isset($_GET['sede'])) die("Error: Sede no especificada.");
$sede = strtoupper(trim($_GET['sede']));

// 1. Localizar el archivo maestro de principios
$backupDir = __DIR__ . '/dbf_backups/';
// Asegúrate que este nombre coincida con como lo guarda el importador
$sourceFile = $backupDir . "maestro_principios_" . $sede . ".dbf";

if (!file_exists($sourceFile)) {
    die("Error: No se encuentra el archivo maestro de principios para $sede. Por favor, vaya a 'Importar' y suba nuevamente el archivo de Principios (Zeth19).");
}

// 2. Obtener TODOS los principios de la Base de Datos
$dbPrincipios = [];
$res = $conn->query("SELECT cod_sub, des_sub FROM principios_zeth WHERE sede = '$sede'");
while($row = $res->fetch_assoc()) {
    $dbPrincipios[trim($row['cod_sub'])] = trim($row['des_sub']);
}

// 3. Crear copia de trabajo
$tempFile = tempnam(sys_get_temp_dir(), 'dbf_p_export');
if (!copy($sourceFile, $tempFile)) die("Error creando archivo temporal.");

$fp = fopen($tempFile, 'r+b');

// --- LEER CABECERA DBF ---
fseek($fp, 4);
$numRecords = unpack("V", fread($fp, 4))[1];

$headerData = unpack("vheader_len/vrecord_len", fread($fp, 4));
$headerLen = $headerData['header_len'];
$recordLen = $headerData['record_len'];

// --- ANALIZAR COLUMNAS ---
fseek($fp, 32);
$fieldOffset = 1; // +1 por el byte de borrado inicial

$posCodigo = -1; $lenCodigo = 0;
$posDescri = -1; $lenDescri = 0;

// Listas de posibles nombres para identificar las columnas (Mayúsculas)
$nombresCodigo = ['COD_SUB', 'CODIGO', 'SUBLIN', 'PRONUM', 'ID', 'COD', 'C_SUB', 'KEY'];
$nombresDescri = ['DES_SUB', 'DESCRIPCION', 'DESCRI', 'NOMBRE', 'DETALLE', 'D_SUB', 'DESCRIPT'];

$columnasEncontradas = []; // Para depuración

while (ftell($fp) < $headerLen - 1) {
    $buf = fread($fp, 32);
    if (ord($buf[0]) == 0x0D) break; // Fin de cabecera

    // LIMPIEZA AGRESIVA: Solo caracteres ASCII imprimibles y trim
    $rawName = substr($buf, 0, 11);
    $fieldName = strtoupper(trim(preg_replace('/[^\x20-\x7E]/', '', $rawName)));
    $fieldLen = ord($buf[16]);
    
    $columnasEncontradas[] = $fieldName;

    // Buscar coincidencia en listas de alias
    if (in_array($fieldName, $nombresCodigo)) {
        $posCodigo = $fieldOffset; 
        $lenCodigo = $fieldLen;
    }
    if (in_array($fieldName, $nombresDescri)) {
        $posDescri = $fieldOffset; 
        $lenDescri = $fieldLen;
    }
    
    $fieldOffset += $fieldLen;
}

if ($posCodigo === -1 || $posDescri === -1) {
    fclose($fp);
    // Mensaje de error detallado para ayudar a corregir
    die("Error: No se pudieron identificar las columnas de Código y Descripción en el DBF.<br>" .
        "Columnas detectadas: [ " . implode(', ', $columnasEncontradas) . " ]<br>" .
        "Esperaba alguna de estas para Código: " . implode(',', $nombresCodigo) . "<br>" .
        "Esperaba alguna de estas para Descripción: " . implode(',', $nombresDescri));
}

// --- 4. DETECTAR CÓDIGOS YA EXISTENTES EN EL DBF ---
// Para no duplicarlos si ya están en el archivo original
$codigosEnDBF = [];
fseek($fp, $headerLen); // Ir al inicio de los datos

for ($i = 0; $i < $numRecords; $i++) {
    // Leer solo el código de cada registro
    fseek($fp, ftell($fp) + $posCodigo);
    $codeLeido = fread($fp, $lenCodigo);
    $codigosEnDBF[trim($codeLeido)] = true;
    
    // Saltar al siguiente registro (calculamos posición absoluta para ser precisos)
    fseek($fp, $headerLen + (($i + 1) * $recordLen));
}

// --- 5. AGREGAR (APPEND) REGISTROS NUEVOS ---
// Posicionarse al final exacto del archivo
$finalPos = $headerLen + ($numRecords * $recordLen);
fseek($fp, $finalPos);

$nuevosContador = 0;

foreach ($dbPrincipios as $cod => $desc) {
    // Solo agregar si NO existe en el archivo original
    if (!isset($codigosEnDBF[$cod])) {
        
        // a) Crear registro vacío (espacios)
        $blankRecord = str_repeat(" ", $recordLen);
        
        // b) Inyectar Código
        $codEncoded = mb_convert_encoding($cod, 'CP850', 'UTF-8');
        $codFmt = substr(str_pad($codEncoded, $lenCodigo, ' ', STR_PAD_RIGHT), 0, $lenCodigo);
        $blankRecord = substr_replace($blankRecord, $codFmt, $posCodigo, $lenCodigo);
        
        // c) Inyectar Descripción
        $descEncoded = mb_convert_encoding($desc, 'CP850', 'UTF-8');
        $descFmt = substr(str_pad($descEncoded, $lenDescri, ' ', STR_PAD_RIGHT), 0, $lenDescri);
        $blankRecord = substr_replace($blankRecord, $descFmt, $posDescri, $lenDescri);
        
        // d) Escribir
        fwrite($fp, $blankRecord);
        $nuevosContador++;
    }
}

// --- 6. ACTUALIZAR CABECERA ---
if ($nuevosContador > 0) {
    $totalRecords = $numRecords + $nuevosContador;
    
    // Actualizar conteo de registros (Bytes 4-7)
    fseek($fp, 4);
    fwrite($fp, pack("V", $totalRecords));
    
    // Escribir EOF (0x1A) al nuevo final
    fseek($fp, $headerLen + ($totalRecords * $recordLen));
    fwrite($fp, chr(0x1A));
}

fclose($fp);

// 7. Descargar
header('Content-Description: File Transfer');
header('Content-Type: application/dbase');
header('Content-Disposition: attachment; filename="Principios_'.$sede.'_'.date('dmY').'.dbf"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($tempFile));

readfile($tempFile);
unlink($tempFile);
exit;
?>