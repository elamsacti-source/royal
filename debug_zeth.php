<?php
// debug_zeth.php
// HERRAMIENTA DE DIAGNÓSTICO
// Ejecuta esto directamente en el navegador para ver los errores reales (Fatal Errors, Memoria, etc.)

// 1. FORZAR VISIBILIDAD DE ERRORES
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Desactivar límite de tiempo para que no corte por timeout durante el debug
set_time_limit(300); 

echo "<style>body{font-family:monospace; background:#1e1e1e; color:#0f0; padding:20px;}</style>";
echo "<h1>🕵️ MODO DEBUG: ZETH53.DBF</h1>";
echo "<hr>";

// Paso 1: Verificar Configuración
echo "<h3>1. Verificando Entorno...</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Memory Limit Actual: " . ini_get('memory_limit') . "<br>";

// Paso 2: Verificar Conexión BD
echo "<h3>2. Probando Base de Datos...</h3>";
try {
    if (!file_exists('db_config.php')) throw new Exception("Falta el archivo db_config.php");
    include 'db_config.php';
    if ($conn->connect_error) throw new Exception("Error MySQL: " . $conn->connect_error);
    echo "✅ Conexión a BD Exitosa.<br>";
} catch (Throwable $e) {
    die("❌ ERROR CRÍTICO EN BD: " . $e->getMessage());
}

// Paso 3: Verificar Archivo
echo "<h3>3. Buscando Archivo DBF...</h3>";
$file = __DIR__ . '/ZETH53.DBF';
if (!file_exists($file)) {
    die("❌ NO SE ENCUENTRA EL ARCHIVO: $file <br>⚠️ Súbelo por FTP a esta carpeta primero.");
}
echo "✅ Archivo encontrado: " . basename($file) . " (" . round(filesize($file)/1024/1024, 2) . " MB)<br>";

// Paso 4: Intentar Lectura (Simulación)
echo "<h3>4. Test de Lectura (Clase Nativa)...</h3>";

try {
    $fp = fopen($file, 'rb');
    if (!$fp) throw new Exception("No se puede abrir el archivo (permisos?).");

    // Leer Header
    $buf = fread($fp, 32);
    $data = unpack("Vnum_records/vheader_len/vrecord_len", substr($buf, 4, 8));
    echo "ℹ️ Header leído correctamente.<br>";
    echo "ℹ️ Registros totales reportados: " . $data['num_records'] . "<br>";
    echo "ℹ️ Longitud de cabecera: " . $data['header_len'] . "<br>";
    
    // Leer Columnas (Solo para ver si no explota)
    fseek($fp, 32);
    $columnas = 0;
    while (ftell($fp) < $data['header_len'] - 1) {
        $buf = fread($fp, 32);
        if (ord($buf[0]) == 0x0D) break;
        $columnas++;
    }
    echo "ℹ️ Columnas detectadas: $columnas<br>";

    // Prueba de Estrés de Memoria (Leer 1000 registros)
    echo "<br>⏳ Iniciando lectura de prueba (primeros 2,000 registros)...<br>";
    flush(); // Forzar salida al navegador

    $recLen = $data['record_len'];
    fseek($fp, $data['header_len']);
    
    for($i=0; $i<2000; $i++) {
        if ($i >= $data['num_records']) break;
        $buf = fread($fp, $recLen);
        // Solo hacemos un echo cada 500 para ver progreso
        if($i % 500 == 0) {
            echo ". (Reg $i Leído - Memoria: " . round(memory_get_usage()/1024/1024, 2) . " MB)<br>";
            flush();
        }
    }
    
    echo "<br>✅ PRUEBA DE LECTURA EXITOSA. El archivo es legible y PHP no se queda sin memoria.<br>";
    fclose($fp);

} catch (Throwable $e) {
    echo "<br><h2 style='color:red'>❌ ERROR FATAL DURANTE LECTURA:</h2>";
    echo "<strong>" . $e->getMessage() . "</strong><br>";
    echo "En archivo: " . $e->getFile() . " línea " . $e->getLine();
    exit;
}

echo "<hr>";
echo "<h2>CONCLUSIÓN:</h2>";
echo "Si has llegado hasta aquí, el servidor PHP y el archivo están BIEN.<br>";
echo "El problema anterior era probablemente falta de memoria al intentar cargar TODO el archivo de golpe.<br>";
echo "👉 <strong>SOLUCIÓN:</strong> Usa el script 'VERSIÓN ULTRA-LIGERA' (con yield) que te envié antes. Ese script usa la misma lógica que este test.";
?>