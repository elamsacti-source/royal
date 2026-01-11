<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

// 1. Reporte de errores
ini_set('display_errors', 0);
error_reporting(E_ALL);

include 'db_config.php';

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Datos vacíos']);
    exit;
}

// Recibimos el ID (puede ser nulo o 0 si es nueva)
$cita_id = isset($data['cita_id']) ? intval($data['cita_id']) : 0;

// Datos comunes
$ticket = $data['ticket'] ?? '';
$dni = $data['dni'] ?? '';
$ape = $data['apellidos'] ?? '';
$nom = $data['nombres'] ?? '';
$tel = $data['telefono'] ?? '';
$hc = $data['hc'] ?? '';
$fecha = $data['fecha'] ?? date('Y-m-d');
$hora = $data['hora'] ?? '';
$esp = $data['especialidad'] ?? '';
$doc = $data['doctor'] ?? '';
$tipo = $data['tipo'] ?? 'Consulta';
$cons = $data['consultorio'] ?? '';
$sede = $data['sede_id'] ?? 1;
$estado = $data['estado'] ?? 'PROGRAMADO';

// ---------------------------------------------------------
// PASO 1: CREAR O ACTUALIZAR CITA
// ---------------------------------------------------------

if ($cita_id > 0) {
    // === CASO ACTUALIZAR (Si ya existe ID) ===
    $sql = "UPDATE citas SET ticket=?, dni=?, apellidos=?, nombres=?, telefono=?, historia_clinica=?, fecha=?, hora=?, especialidad=?, doctor=?, tipo_atencion=?, consultorio=?, estado=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssssssi", $ticket, $dni, $ape, $nom, $tel, $hc, $fecha, $hora, $esp, $doc, $tipo, $cons, $estado, $cita_id);
    
    if (!$stmt->execute()) { 
        echo json_encode(['success'=>false, 'message'=>'Error al actualizar: '.$stmt->error]); 
        exit; 
    }
} else {
    // === CASO CREAR NUEVA (Si NO hay ID) ===
    $sql = "INSERT INTO citas (sede_id, ticket, dni, apellidos, nombres, telefono, historia_clinica, fecha, hora, especialidad, doctor, tipo_atencion, consultorio, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    // Tipos: i (int), s (string)... total 14 variables
    $stmt->bind_param("isssssssssssss", $sede, $ticket, $dni, $ape, $nom, $tel, $hc, $fecha, $hora, $esp, $doc, $tipo, $cons, $estado);
    
    if ($stmt->execute()) {
        $cita_id = $stmt->insert_id; // Capturamos el ID nuevo
    } else {
        echo json_encode(['success'=>false, 'message'=>'Error al crear cita: '.$stmt->error]); 
        exit;
    }
}
$stmt->close();

// ---------------------------------------------------------
// PASO 2: GESTIÓN DE ATENCIÓN (Opcional al crear cita)
// ---------------------------------------------------------
// Si vienen datos de tratamiento (generalmente al editar), los guardamos.
// Si es una cita nueva, probablemente esta lista venga vacía, y no pasa nada.

$lista = $data['lista_tratamientos'] ?? [];

if (!empty($lista) || !empty($data['triaje'])) {
    
    // Preparar resumen de productos
    $resumen = ['SUPLEMENTO'=>[], 'PROCEDIMIENTO'=>[], 'MEDICAMENTO'=>[]];
    foreach ($lista as $item) {
        if(isset($resumen[$item['tipo']])) {
            $resumen[$item['tipo']][] = $item['producto'] . " (" . $item['dosis'] . " x" . $item['rep'] . ")";
        }
    }
    $s_prod = implode(', ', $resumen['SUPLEMENTO']);
    $p_prod = implode(', ', $resumen['PROCEDIMIENTO']);
    $m_prod = implode(', ', $resumen['MEDICAMENTO']);
    
    // Flags
    $s_c = !empty($resumen['SUPLEMENTO']) ? 1 : 0;
    $p_c = !empty($resumen['PROCEDIMIENTO']) ? 1 : 0;
    $m_c = !empty($resumen['MEDICAMENTO']) ? 1 : 0;

    // Variables
    $triaje = $data['triaje'] ?? '';
    $obs = $data['obs'] ?? '';
    $re_c = !empty($data['re_check']) ? 1 : 0;
    $re_e = $data['re_esp'] ?? '';
    $re_f = !empty($data['re_fecha']) ? $data['re_fecha'] : NULL;
    $int_c = !empty($data['int_check']) ? 1 : 0;
    $int_e = $data['int_esp'] ?? '';
    $f_ini = !empty($data['trat_fecha_ini']) ? $data['trat_fecha_ini'] : NULL;

    // INSERT O UPDATE en tabla 'atenciones'
    $sqlAtencion = "INSERT INTO atenciones (
        cita_id, hora_triaje, observaciones, 
        es_reconsulta, reconsulta_esp, reconsulta_fecha, es_interconsulta, interconsulta_esp,
        suple_check, suple_prod, proc_check, proc_prod, med_check, med_prod, fecha_inicio_tratamiento
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE 
        hora_triaje=?, observaciones=?, 
        es_reconsulta=?, reconsulta_esp=?, reconsulta_fecha=?, es_interconsulta=?, interconsulta_esp=?,
        suple_check=?, suple_prod=?, proc_check=?, proc_prod=?, med_check=?, med_prod=?, fecha_inicio_tratamiento=?";

    $stmt2 = $conn->prepare($sqlAtencion);
    
    // Bindparams complejo: 15 params insert + 14 update = 29
    // Se reutilizan las variables
    $stmt2->bind_param("isssississsissssssississsisss", 
        $cita_id, $triaje, $obs, $re_c, $re_e, $re_f, $int_c, $int_e, $s_c, $s_prod, $p_c, $p_prod, $m_c, $m_prod, $f_ini,
        $triaje, $obs, $re_c, $re_e, $re_f, $int_c, $int_e, $s_c, $s_prod, $p_c, $p_prod, $m_c, $m_prod, $f_ini
    );
    
    $stmt2->execute();
    $atencion_id = $stmt2->insert_id;
    if(!$atencion_id) $atencion_id = $conn->insert_id; // Por si fue update
    $stmt2->close();

    // Guardar Cronograma
    if ($atencion_id) {
        $conn->query("DELETE FROM tratamiento_cronograma WHERE atencion_id = $atencion_id");
        $stmtCron = $conn->prepare("INSERT INTO tratamiento_cronograma (atencion_id, tipo, producto, fecha_programada, estado) VALUES (?, ?, ?, ?, 'PENDIENTE')");
        
        foreach ($lista as $item) {
            $fechasArr = explode(',', $item['fechas']);
            foreach ($fechasArr as $f) {
                if(trim($f)) {
                    $stmtCron->bind_param("isss", $atencion_id, $item['tipo'], $item['producto'], trim($f));
                    $stmtCron->execute();
                }
            }
        }
    }
}

echo json_encode(['success' => true, 'id' => $cita_id]);
$conn->close();
?>