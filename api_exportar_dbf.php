<?php
// api_exportar_dbf.php
// Sistema de Inyección Binaria: Modifica Zeth70 (Inventario) inyectando nombre y vínculo al principio.
header("Access-Control-Allow-Origin: *");
include 'db_config.php';

if (!isset($_GET['sede'])) die("Error: Sede no especificada.");
$sede = strtoupper(trim($_GET['sede']));

// Localizar el archivo maestro de Inventario
$backupDir = __DIR__ . '/dbf_backups/';
$sourceFile = $backupDir . "maestro_inv_" . $sede . ".dbf";

if (!file_exists($sourceFile)) {
    die("Error: No se encuentra el archivo maestro de Inventario para la sede $sede. Por favor, suba el archivo Zeth70 nuevamente en 'Importar'.");
}

// Obtener datos modificados
$mapCambios = [];
$res = $conn->query("SELECT codigo, nombre, sublin FROM inventario_zeth WHERE sede = '$sede'");
while($row = $res->fetch_assoc()) {
    $mapCambios[trim($row['codigo'])] = [
        'nombre' => $row['nombre'],
        'sublin' => $row['sublin']
    ];
}

$tempFile = tempnam(sys_get_temp_dir(), 'dbf_export');
if (!copy($sourceFile, $tempFile)) die("Error al crear archivo temporal.");

$fp = fopen($tempFile, 'r+b');

// Header
fseek($fp, 8);
$headerData = unpack("vheader_len/vrecord_len", fread($fp, 4));
$headerLen = $headerData['header_len'];
$recordLen = $headerData['record_len'];

// Columnas
fseek($fp, 32); 
$fieldOffset = 1;
$posCodigo = -1; $lenCodigo = 0;
$posDescri = -1; $lenDescri = 0;
$posSublin = -1; $lenSublin = 0;

while (ftell($fp) < $headerLen - 1) {
    $buf = fread($fp, 32);
    if (ord($buf[0]) == 0x0D) break;
    $fieldName = strtoupper(trim(substr($buf, 0, 11)));
    $fieldLen = ord($buf[16]);

    if ($fieldName == 'PRONUM' || $fieldName == 'CODIGO') { $posCodigo = $fieldOffset; $lenCodigo = $fieldLen; }
    if ($fieldName == 'DESCRI' || $fieldName == 'DESCRIPCION') { $posDescri = $fieldOffset; $lenDescri = $fieldLen; }
    if ($fieldName == 'SUBLIN' || $fieldName == 'SUB_LIN') { $posSublin = $fieldOffset; $lenSublin = $fieldLen; }

    $fieldOffset += $fieldLen;
}

if ($posCodigo === -1 || $posDescri === -1) { fclose($fp); die("Error: Columnas PRONUM/DESCRI no encontradas."); }

// Inyección
fseek($fp, $headerLen);
$stat = fstat($fp);
$numRecords = floor(($stat['size'] - $headerLen) / $recordLen);

for ($i = 0; $i < $numRecords; $i++) {
    $currentPos = ftell($fp);
    fseek($fp, $currentPos + $posCodigo);
    $code = trim(fread($fp, $lenCodigo));

    if (isset($mapCambios[$code])) {
        $datos = $mapCambios[$code];
        
        // 1. Nombre
        $nombreEncoded = mb_convert_encoding($datos['nombre'], 'CP850', 'UTF-8');
        $finalNombre = substr(str_pad($nombreEncoded, $lenDescri, ' ', STR_PAD_RIGHT), 0, $lenDescri);
        fseek($fp, $currentPos + $posDescri);
        fwrite($fp, $finalNombre);

        // 2. Sublinea (Principio)
        if ($posSublin !== -1 && !empty($datos['sublin'])) {
            $sublinEncoded = mb_convert_encoding($datos['sublin'], 'CP850', 'UTF-8');
            $finalSublin = substr(str_pad($sublinEncoded, $lenSublin, ' ', STR_PAD_RIGHT), 0, $lenSublin);
            fseek($fp, $currentPos + $posSublin);
            fwrite($fp, $finalSublin);
        }
    }
    fseek($fp, $currentPos + $recordLen);
}

fclose($fp);

header('Content-Description: File Transfer');
header('Content-Type: application/dbase');
header('Content-Disposition: attachment; filename="Inventario_'.$sede.'_'.date('dmY').'.dbf"');
header('Content-Length: ' . filesize($tempFile));
readfile($tempFile);
unlink($tempFile);
exit;
?>