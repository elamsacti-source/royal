<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
ini_set('display_errors', 0);
error_reporting(E_ALL);

include 'db_config.php';

$data = $_POST;
$cita_id = intval($data['cita_id'] ?? 0);

if ($cita_id === 0) { echo json_encode(['success' => false, 'message' => 'Falta ID']); exit; }

$lista = isset($data['lista_tratamientos']) ? json_decode($data['lista_tratamientos'], true) : [];

// 1. RECETA (Subida de archivo)
$ruta_receta = null;
if (isset($_FILES['receta']) && $_FILES['receta']['error'] === UPLOAD_ERR_OK) {
    $dir = 'uploads/recetas/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $ext = pathinfo($_FILES['receta']['name'], PATHINFO_EXTENSION);
    $nombre_archivo = 'receta_' . $cita_id . '_' . uniqid() . '.' . $ext;
    if (move_uploaded_file($_FILES['receta']['tmp_name'], $dir . $nombre_archivo)) {
        $ruta_receta = $dir . $nombre_archivo;
    }
}

// 2. ACTUALIZAR CITA (INCLUYENDO FECHA Y HORA PARA REPROGRAMACIONES)
// Si viene fecha/hora en el POST (Reprogramación), las usamos. Si no, usamos las actuales o defaults.
// Nota: En una reprogramación real, estos datos vienen del modal.
$nueva_fecha = !empty($data['fecha']) ? $data['fecha'] : date('Y-m-d');
$nueva_hora = !empty($data['hora']) ? $data['hora'] : '00:00';

$stmt1 = $conn->prepare("UPDATE citas SET estado=?, ticket=?, historia_clinica=?, fecha=?, hora=? WHERE id=?");
// Bind: s (estado), s (ticket), s (hc), s (fecha), s (hora), i (id)
$stmt1->bind_param("sssssi", $data['estado'], $data['ticket'], $data['hc'], $nueva_fecha, $nueva_hora, $cita_id);
$stmt1->execute();
$stmt1->close();

// 3. PREPARAR DATOS DE ATENCIÓN (Triaje y Tratamientos)
$resumen = ['SUPLEMENTO'=>[], 'PROCEDIMIENTO'=>[], 'MEDICAMENTO'=>[]];
if(is_array($lista)){
    foreach ($lista as $item) {
        if(isset($resumen[$item['tipo']])) $resumen[$item['tipo']][] = $item['producto']." (".$item['dosis'].")";
    }
}
$s_prod = !empty($resumen['SUPLEMENTO']) ? implode(', ', $resumen['SUPLEMENTO']) : NULL;
$p_prod = !empty($resumen['PROCEDIMIENTO']) ? implode(', ', $resumen['PROCEDIMIENTO']) : NULL;
$m_prod = !empty($resumen['MEDICAMENTO']) ? implode(', ', $resumen['MEDICAMENTO']) : NULL;
$s_c = !empty($resumen['SUPLEMENTO']) ? 1 : 0;
$p_c = !empty($resumen['PROCEDIMIENTO']) ? 1 : 0;
$m_c = !empty($resumen['MEDICAMENTO']) ? 1 : 0;

// TRIAJE COMPLETO
$triaje = $data['triaje'] ?? '';
$peso = !empty($data['peso']) ? $data['peso'] : NULL;
$talla = !empty($data['talla']) ? $data['talla'] : NULL;
$presion = !empty($data['presion']) ? $data['presion'] : NULL;
$temp = !empty($data['temperatura']) ? $data['temperatura'] : NULL;
$sat = !empty($data['saturacion']) ? $data['saturacion'] : NULL;
$fc = !empty($data['fc']) ? $data['fc'] : NULL;
$fr = !empty($data['fr']) ? $data['fr'] : NULL;

$obs = $data['obs'] ?? '';
$re_c = !empty($data['re_check']) ? 1 : 0; $re_e = $data['re_esp'] ?? ''; $re_f = !empty($data['re_fecha']) ? $data['re_fecha'] : NULL;
$int_c = !empty($data['int_check']) ? 1 : 0; $int_e = $data['int_esp'] ?? '';
$f_ini = !empty($data['trat_fecha_ini']) ? $data['trat_fecha_ini'] : NULL;

// 4. INSERTAR O ACTUALIZAR EN TABLA ATENCIONES
$check = $conn->query("SELECT id FROM atenciones WHERE cita_id = $cita_id");

if($row = $check->fetch_assoc()) {
    $atencion_id = $row['id'];
    $sqlReceta = $ruta_receta ? ", archivo_receta='$ruta_receta'" : "";
    
    $sql = "UPDATE atenciones SET 
            hora_triaje=?, peso=?, talla=?, presion=?, temperatura=?, saturacion=?, frecuencia_cardiaca=?, frecuencia_respiratoria=?, observaciones=?, 
            es_reconsulta=?, reconsulta_esp=?, reconsulta_fecha=?, 
            es_interconsulta=?, interconsulta_esp=?, 
            suple_check=?, suple_prod=?, proc_check=?, proc_prod=?, med_check=?, med_prod=?, 
            fecha_inicio_tratamiento=? $sqlReceta WHERE id=?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssissisississsi", $triaje, $peso, $talla, $presion, $temp, $sat, $fc, $fr, $obs, $re_c, $re_e, $re_f, $int_c, $int_e, $s_c, $s_prod, $p_c, $p_prod, $m_c, $m_prod, $f_ini, $atencion_id);
    $stmt->execute();
} else {
    $sql = "INSERT INTO atenciones (
            cita_id, hora_triaje, peso, talla, presion, temperatura, saturacion, frecuencia_cardiaca, frecuencia_respiratoria, observaciones, 
            es_reconsulta, reconsulta_esp, reconsulta_fecha, 
            es_interconsulta, interconsulta_esp, 
            suple_check, suple_prod, proc_check, proc_prod, med_check, med_prod, 
            fecha_inicio_tratamiento, archivo_receta
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssssssssissisississss", $cita_id, $triaje, $peso, $talla, $presion, $temp, $sat, $fc, $fr, $obs, $re_c, $re_e, $re_f, $int_c, $int_e, $s_c, $s_prod, $p_c, $p_prod, $m_c, $m_prod, $f_ini, $ruta_receta);
    $stmt->execute();
    $atencion_id = $stmt->insert_id;
}

// 5. ACTUALIZAR CRONOGRAMA DE TRATAMIENTOS
if ($atencion_id > 0) {
    $conn->query("DELETE FROM tratamiento_cronograma WHERE atencion_id = $atencion_id");
    $stmtCron = $conn->prepare("INSERT INTO tratamiento_cronograma (atencion_id, tipo, producto, fecha_programada, estado) VALUES (?, ?, ?, ?, 'PENDIENTE')");
    
    if (!empty($lista)) {
        foreach ($lista as $item) {
            $fechasArr = explode(',', $item['fechas'] ?? '');
            foreach ($fechasArr as $f) {
                if(trim($f)) { $stmtCron->bind_param("isss", $atencion_id, $item['tipo'], $item['producto'], trim($f)); $stmtCron->execute(); }
            }
        }
    }
    // Agregar Reconsulta al cronograma si aplica
    if ($re_c == 1 && !empty($re_f)) { 
        $t="RECONSULTA"; $p=$re_e?:'General'; 
        $stmtCron->bind_param("isss", $atencion_id, $t, $p, $re_f); 
        $stmtCron->execute(); 
    }
    // Agregar Interconsulta al cronograma si aplica (se pone hoy como fecha de solicitud)
    if ($int_c == 1) { 
        $t="INTERCONSULTA"; $p=$int_e?:'Especialidad'; $f=date('Y-m-d'); 
        $stmtCron->bind_param("isss", $atencion_id, $t, $p, $f); 
        $stmtCron->execute(); 
    }
}

echo json_encode(['success' => true]);
$conn->close();
?>