<?php
header('Content-Type: application/json');
include 'db_config.php';

// --- 1. VALIDACIONES ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false, 'message'=>'Método inválido']); exit; }
if (empty($_FILES['archivo']['name'])) { echo json_encode(['success'=>false, 'message'=>'No se subió archivo']); exit; }

// NECESITAMOS el mes/año porque el Excel dice "1-Dic" pero no el año.
$mes_anio = $_POST['mes_anio'] ?? date('Y-m'); 

// --- 2. CARGAR MAPAS ---
$usuariosDB = [];
$res = $conn->query("SELECT id, nombre_completo FROM usuarios");
while($r = $res->fetch_assoc()) {
    $clean = strtoupper(trim($r['nombre_completo']));
    $clean = str_replace(['Á','É','Í','Ó','Ú'], ['A','E','I','O','U'], $clean);
    $usuariosDB[] = ['id' => $r['id'], 'nombre_norm' => $clean];
}

$sedesDB = [];
$resS = $conn->query("SELECT id, nombre FROM sedes WHERE activo=1");
while($r = $resS->fetch_assoc()) {
    $sedesDB[] = ['id' => $r['id'], 'nombre' => strtoupper($r['nombre'])];
}

// --- 3. LEER CSV ---
$file = $_FILES['archivo']['tmp_name'];
$handle = fopen($file, "r");

// Detectar separador (; o ,)
$linea1 = fgets($handle);
$separador = (substr_count($linea1, ';') > substr_count($linea1, ',')) ? ';' : ',';
rewind($handle);

$filas_insertadas = 0;
$idx = ['sede'=>-1, 'nombres'=>-1, 'fecha'=>-1, 'ingreso'=>-1, 'salida'=>-1];

while (($row = fgetcsv($handle, 5000, $separador)) !== FALSE) {
    $row = array_map('trim', $row);

    // A. DETECTAR CABECERA
    if ($idx['nombres'] === -1) {
        foreach($row as $i => $cell) {
            $c = strtoupper($cell);
            if (strpos($c, 'SEDE') !== false) $idx['sede'] = $i;
            if ($c === 'NOMBRES' || $c === 'PERSONAL') $idx['nombres'] = $i;
            if ($c === 'FECHA') $idx['fecha'] = $i;
            if ($c === 'INGRESO' || $c === 'ENTRADA') $idx['ingreso'] = $i;
            if ($c === 'SALIDA') $idx['salida'] = $i;
        }
        continue;
    }

    // B. PROCESAR DATOS
    if ($idx['nombres'] >= 0 && $idx['fecha'] >= 0 && $idx['ingreso'] >= 0) {
        
        $txt_sede = strtoupper($row[$idx['sede']] ?? '');
        $txt_nombre = strtoupper($row[$idx['nombres']] ?? '');
        $txt_fecha = $row[$idx['fecha']] ?? ''; // Viene como "1-Dic"
        $txt_ini = $row[$idx['ingreso']] ?? '';
        $txt_fin = $row[$idx['salida']] ?? '';

        if (empty($txt_ini) || empty($txt_fecha)) continue;

        // 1. OBTENER FECHA CORRECTA
        // Extraemos solo los dígitos iniciales (El día)
        if (preg_match('/^(\d+)/', $txt_fecha, $matches)) {
            $dia = str_pad($matches[1], 2, "0", STR_PAD_LEFT);
            // Combinamos con el Mes/Año seleccionado (Ej: 2025-12 + 01)
            $fecha_sql = $mes_anio . '-' . $dia; 
            
            // Validar que sea fecha real
            if (!checkdate((int)substr($mes_anio, 5), (int)$dia, (int)substr($mes_anio, 0, 4))) continue;
        } else {
            continue; // No pudimos leer el día
        }

        // 2. IDENTIFICAR SEDE
        $sede_id = null;
        foreach ($sedesDB as $s) {
            if (strpos($txt_sede, 'INTEGRA') !== false && strpos($s['nombre'], 'HUACHO') !== false) { $sede_id = $s['id']; break; }
            if (strpos($txt_sede, 'HUAURA') !== false && strpos($s['nombre'], 'HUAURA') !== false) { $sede_id = $s['id']; break; }
            if ((strpos($txt_sede, 'M.M') !== false || strpos($txt_sede, 'MEDIO') !== false) && strpos($s['nombre'], 'MEDIO') !== false) { $sede_id = $s['id']; break; }
            if (strpos($txt_sede, $s['nombre']) !== false) { $sede_id = $s['id']; break; }
        }
        if (!$sede_id) continue;

        // 3. IDENTIFICAR USUARIO
        $usuario_id = null;
        $txt_nombre = str_replace(['LIC.', 'TEC.', 'OBST.', 'ENF.', 'DR.', 'DRA.', '.'], '', $txt_nombre);
        $txt_nombre = str_replace(['Á','É','Í','Ó','Ú'], ['A','E','I','O','U'], $txt_nombre);
        
        foreach ($usuariosDB as $u) {
            if (strpos($u['nombre_norm'], $txt_nombre) !== false || strpos($txt_nombre, $u['nombre_norm']) !== false) {
                $usuario_id = $u['id'];
                break;
            }
        }
        if (!$usuario_id) continue;

        // 4. HORAS
        $hora_sql_ini = date("H:i:s", strtotime($txt_ini));
        $hora_sql_fin = !empty($txt_fin) ? date("H:i:s", strtotime($txt_fin)) : date("H:i:s", strtotime($txt_ini) + 6*3600);

        // Letra Referencial
        $h = (int)substr($hora_sql_ini, 0, 2);
        $turno_letra = 'M';
        if ($h >= 13) $turno_letra = 'T';
        if ($h >= 19) $turno_letra = 'N';
        if ((strtotime($hora_sql_fin) - strtotime($hora_sql_ini)) / 3600 >= 10) $turno_letra = 'C';

        // 5. INSERTAR
        $conn->query("DELETE FROM programacion WHERE usuario_id=$usuario_id AND fecha='$fecha_sql'");
        
        $stmt = $conn->prepare("INSERT INTO programacion (usuario_id, sede_id, fecha, turno, hora_inicio, hora_fin) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissss", $usuario_id, $sede_id, $fecha_sql, $turno_letra, $hora_sql_ini, $hora_sql_fin);
        
        if($stmt->execute()) $filas_insertadas++;
    }
}

fclose($handle);

echo json_encode(['success' => true, 'message' => "Proceso terminado: $filas_insertadas turnos."]);
$conn->close();
?>