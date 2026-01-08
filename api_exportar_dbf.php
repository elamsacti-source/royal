<?php
// api_exportar_dbf.php
// Exportador ZETH70: Inyecta Nombre, Principio, PREVPU (Precio) y ULCOSREP (Costo).
header("Access-Control-Allow-Origin: *");
include 'db_config.php';

if (!isset($_GET['sede'])) die("Error: Sede no especificada.");
$sede = strtoupper(trim($_GET['sede']));

// 1. Localizar el archivo maestro de Inventario (DBF Original)
$backupDir = __DIR__ . '/dbf_backups/';
$sourceFile = $backupDir . "maestro_inv_" . $sede . ".dbf";

if (!file_exists($sourceFile)) {
    die("Error: No se encuentra el archivo maestro de Inventario para la sede $sede. Por favor, suba el archivo Zeth70 nuevamente en 'Importar'.");
}

// 2. Obtener datos modificados de la BD
$mapCambios = [];
$res = $conn->query("SELECT codigo, nombre, sublin, costo, precio FROM inventario_zeth WHERE sede = '$sede'");
while($row = $res->fetch_assoc()) {
    $mapCambios[trim($row['codigo'])] = [
        'nombre' => $row['nombre'],
        'sublin' => $row['sublin'],
        'costo'  => $row['costo'],  // ULCOSREP
        'precio' => $row['precio']  // PREVPU
    ];
}

// 3. Crear archivo temporal
$tempFile = tempnam(sys_get_temp_dir(), 'dbf_export');
if (!copy($sourceFile, $tempFile)) die("Error al crear archivo temporal.");

$fp = fopen($tempFile, 'r+b');

// --- LEER CABECERA ---
fseek($fp, 8);
$headerData = unpack("vheader_len/vrecord_len", fread($fp, 4));
$headerLen = $headerData['header_len'];
$recordLen = $headerData['record_len'];

// --- DETECTAR COLUMNAS ESPECÍFICAS ---
fseek($fp, 32); 
$fieldOffset = 1; // Byte de borrado

// Posiciones
$posCodigo = -1; $lenCodigo = 0;
$posDescri = -1; $lenDescri = 0;
$posSublin = -1; $lenSublin = 0;
$posCosto  = -1; $lenCosto  = 0; // ULCOSREP
$posPrecio = -1; $lenPrecio = 0; // PREVPU

while (ftell($fp) < $headerLen - 1) {
    $buf = fread($fp, 32);
    if (ord($buf[0]) == 0x0D) break; // Fin de cabecera
    
    $fieldName = strtoupper(trim(substr($buf, 0, 11))); // Nombre del campo
    $fieldLen  = ord($buf[16]); // Longitud
    
    // Mapeo EXACTO según tus indicaciones
    if (in_array($fieldName, ['PRONUM', 'CODIGO'])) { 
        $posCodigo = $fieldOffset; $lenCodigo = $fieldLen; 
    }
    if (in_array($fieldName, ['DESCRI', 'DESCRIPCION'])) { 
        $posDescri = $fieldOffset; $lenDescri = $fieldLen; 
    }
    if (in_array($fieldName, ['SUBLIN', 'SUB_LIN'])) { 
        $posSublin = $fieldOffset; $lenSublin = $fieldLen; 
    }
    
    // AQUÍ ESTÁ EL CAMBIO IMPORTANTE:
    if ($fieldName === 'ULCOSREP') { 
        $posCosto = $fieldOffset; $lenCosto = $fieldLen;
    }
    
    if ($fieldName === 'PREVPU') { 
        $posPrecio = $fieldOffset; $lenPrecio = $fieldLen;
    }

    $fieldOffset += $fieldLen;
}

// Verificación de seguridad
if ($posCodigo === -1 || $posDescri === -1) { 
    fclose($fp); 
    die("Error: El DBF no tiene las columnas PRONUM o DESCRI."); 
}

// --- 4. INYECCIÓN DE DATOS ---
fseek($fp, $headerLen);
$stat = fstat($fp);
$numRecords = floor(($stat['size'] - $headerLen) / $recordLen);

for ($i = 0; $i < $numRecords; $i++) {
    $currentPos = ftell($fp);
    
    // Leer código
    fseek($fp, $currentPos + $posCodigo);
    $code = trim(fread($fp, $lenCodigo));

    if (isset($mapCambios[$code])) {
        $datos = $mapCambios[$code];
        
        // A) NOMBRE
        $nombreEncoded = mb_convert_encoding($datos['nombre'], 'CP850', 'UTF-8');
        $finalNombre = substr(str_pad($nombreEncoded, $lenDescri, ' ', STR_PAD_RIGHT), 0, $lenDescri);
        fseek($fp, $currentPos + $posDescri);
        fwrite($fp, $finalNombre);

        // B) PRINCIPIO (Sublinea)
        if ($posSublin !== -1 && !empty($datos['sublin'])) {
            $sublinEncoded = mb_convert_encoding($datos['sublin'], 'CP850', 'UTF-8');
            $finalSublin = substr(str_pad($sublinEncoded, $lenSublin, ' ', STR_PAD_RIGHT), 0, $lenSublin);
            fseek($fp, $currentPos + $posSublin);
            fwrite($fp, $finalSublin);
        }

        // C) COSTO (ULCOSREP)
        if ($posCosto !== -1) {
            // Formato 123.45 alineado a la derecha
            $costoStr = number_format((float)$datos['costo'], 2, '.', '');
            $finalCosto = substr(str_pad($costoStr, $lenCosto, ' ', STR_PAD_LEFT), 0, $lenCosto);
            fseek($fp, $currentPos + $posCosto);
            fwrite($fp, $finalCosto);
        }

        // D) PRECIO VENTA (PREVPU)
        if ($posPrecio !== -1) {
            $precioStr = number_format((float)$datos['precio'], 2, '.', '');
            $finalPrecio = substr(str_pad($precioStr, $lenPrecio, ' ', STR_PAD_LEFT), 0, $lenPrecio);
            fseek($fp, $currentPos + $posPrecio);
            fwrite($fp, $finalPrecio);
        }
    }
    
    // Siguiente registro
    fseek($fp, $currentPos + $recordLen);
}

fclose($fp);

// --- 5. DESCARGAR ---
header('Content-Description: File Transfer');
header('Content-Type: application/dbase');
header('Content-Disposition: attachment; filename="Inventario_'.$sede.'_'.date('dmY_Hi').'.dbf"');
header('Content-Length: ' . filesize($tempFile));
readfile($tempFile);
unlink($tempFile);
exit;
?>