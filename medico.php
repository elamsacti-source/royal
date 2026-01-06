<?php
// 1. Activar reporte de errores al máximo
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>🚑 Diagnóstico del Sistema</h1>";

// 2. Verificar si existe la configuración
if (!file_exists('db_config.php')) {
    die("<h2 style='color:red'>❌ FATAL: No encuentro el archivo db_config.php</h2>");
}
echo "<p>✅ Archivo <b>db_config.php</b> encontrado.</p>";

// 3. Intentar incluir la configuración
// Si hay espacios en blanco antes de <?php en db_config, esto fallará o imprimirá basura
ob_start();
include 'db_config.php';
$output = ob_get_clean();

if (!empty(trim($output))) {
    echo "<h2 style='color:red'>❌ ERROR CRÍTICO: Espacios en blanco detectados</h2>";
    echo "<p>Tu archivo <b>db_config.php</b> tiene espacios o líneas vacías antes de <code>&lt;?php</code>. Tienes que borrarlos.</p>";
    echo "<p>Lo que está estorbando es esto: <pre style='background:#eee;padding:10px'>[" . htmlspecialchars($output) . "]</pre></p>";
    exit;
}
echo "<p>✅ Archivo db_config.php está limpio (sin espacios ocultos).</p>";

// 4. Probar Conexión a Base de Datos
if (!isset($conn)) {
    die("<h2 style='color:red'>❌ ERROR: La variable \$conn no existe. Revisa db_config.php</h2>");
}

if ($conn->connect_error) {
    die("<h2 style='color:red'>❌ ERROR DE CONEXIÓN BD:</h2><p>" . $conn->connect_error . "</p><p>Revisa tu contraseña en db_config.php</p>");
}
echo "<p>✅ Conexión a Base de Datos: <b>EXITOSA</b>.</p>";

// 5. Probar si existen las tablas
// --- CORRECCIÓN APLICADA: 'usuarios' en lugar de 'users' ---
$tablas = ['usuarios', 'checklist_activities', 'company_documents'];

foreach ($tablas as $tabla) {
    $sql = "SELECT count(*) FROM $tabla";
    $res = $conn->query($sql);
    if ($res) {
        echo "<p>✅ Tabla <b>$tabla</b>: OK.</p>";
    } else {
        echo "<p style='color:red'>❌ Tabla <b>$tabla</b>: NO EXISTE o Error SQL (" . $conn->error . ")</p>";
    }
}

echo "<h2>🏁 Conclusión</h2>";
echo "<p>Si ves todo en verde, tu sistema DEBERÍA funcionar. Vuelve a intentar entrar.</p>";
?>