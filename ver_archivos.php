<?php
// ver_archivos.php
// Script para ver qué archivos REALMENTE existen en la carpeta del servidor
header('Content-Type: text/html; charset=utf-8');
echo "<style>body{font-family:sans-serif;background:#f4f4f4;padding:20px;}</style>";
echo "<h2>📂 Explorador de Archivos (Debug)</h2>";

$dir = __DIR__;
echo "<p><strong>Carpeta Actual:</strong> $dir</p>";

$archivos = scandir($dir);
echo "<table border='1' cellpadding='10' style='border-collapse:collapse; background:white; width:100%;'>";
echo "<tr style='background:#ddd'><th>Nombre Archivo</th><th>Tamaño</th><th>Permisos</th><th>¿Es DBF?</th></tr>";

$encontrado = false;

foreach ($archivos as $archivo) {
    if ($archivo == '.' || $archivo == '..') continue;
    
    $ruta = $dir . '/' . $archivo;
    $size = round(filesize($ruta) / 1024 / 1024, 2) . " MB";
    $perms = substr(sprintf('%o', fileperms($ruta)), -4);
    $ext = strtoupper(pathinfo($archivo, PATHINFO_EXTENSION));
    
    $es_dbf = ($ext === 'DBF') ? '<b style="color:green">SÍ</b>' : '-';
    $bg = ($ext === 'DBF') ? 'style="background:#e6fffa"' : '';

    if ($ext === 'DBF') $encontrado = true;

    echo "<tr $bg>";
    echo "<td><strong>$archivo</strong></td>";
    echo "<td>$size</td>";
    echo "<td>$perms</td>";
    echo "<td>$es_dbf</td>";
    echo "</tr>";
}
echo "</table>";

if (!$encontrado) {
    echo "<h3 style='color:red'>❌ ALERTA: No veo ningún archivo .DBF en esta carpeta.</h3>";
    echo "<p>Asegúrate de subir el archivo <b>ZETH53.DBF</b> dentro de la carpeta: <br><code>$dir</code></p>";
} else {
    echo "<h3 style='color:green'>✅ Se encontraron archivos DBF. El script de importación debería verlos.</h3>";
}
?>