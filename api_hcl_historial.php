<?php
// api_hcl_historial.php - ACTUALIZADO CON CÁLCULO DE EDAD
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
include 'db_config.php';

if ($conn->connect_error) { ob_clean(); echo json_encode([]); exit; }
$dni = $_GET['dni'] ?? '';
if (empty($dni)) { ob_clean(); echo json_encode([]); exit; }

// 1. Datos Paciente
$sqlP = "SELECT * FROM pacientes WHERE dni = ? LIMIT 1";
$stmtP = $conn->prepare($sqlP);
$stmtP->bind_param("s", $dni);
$stmtP->execute();
$resP = $stmtP->get_result();
$pacienteData = $resP->fetch_assoc();

if (!$pacienteData) {
    // Fallback: Buscar datos básicos si no está en tabla pacientes pero sí en citas
    $stmtF = $conn->prepare("SELECT dni, nombres, apellidos, telefono, historia_clinica, NULL as fecha_nacimiento FROM citas WHERE dni = ? LIMIT 1");
    $stmtF->bind_param("s", $dni);
    $stmtF->execute();
    $pacienteData = $stmtF->get_result()->fetch_assoc();
}

// --- NUEVO: CÁLCULO DE EDAD ---
if ($pacienteData && !empty($pacienteData['fecha_nacimiento'])) {
    try {
        $fecha_nac = new DateTime($pacienteData['fecha_nacimiento']);
        $hoy = new DateTime();
        $diferencia = $hoy->diff($fecha_nac);
        $pacienteData['edad'] = $diferencia->y; // Años
    } catch (Exception $e) {
        $pacienteData['edad'] = null;
    }
} else {
    $pacienteData['edad'] = null;
}

// 2. Historial Completo
$sqlH = "SELECT 
            c.id as cita_id, c.fecha, c.hora, c.especialidad, c.doctor, c.estado as estado_cita,
            COALESCE(a.observaciones, '') as observaciones,
            COALESCE(a.hora_triaje, '') as hora_triaje,
            /* SIGNOS VITALES */
            COALESCE(a.peso, '--') as peso,
            COALESCE(a.talla, '--') as talla,
            COALESCE(a.presion, '--') as presion,
            COALESCE(a.temperatura, '--') as temperatura,
            COALESCE(a.saturacion, '--') as saturacion,
            COALESCE(a.frecuencia_cardiaca, '--') as fc,
            COALESCE(a.frecuencia_respiratoria, '--') as fr,
            
            a.archivo_receta,
            CONCAT_WS(', ', NULLIF(NULLIF(a.suple_prod, ''), '0'), NULLIF(NULLIF(a.proc_prod, ''), '0'), NULLIF(NULLIF(a.med_prod, ''), '0')) as tratamientos
        FROM citas c
        LEFT JOIN atenciones a ON c.id = a.cita_id
        WHERE c.dni = ?
        ORDER BY c.fecha DESC, c.hora DESC";

$stmtH = $conn->prepare($sqlH);
$stmtH->bind_param("s", $dni);
$stmtH->execute();
$resH = $stmtH->get_result();
$historial = [];
while($row = $resH->fetch_assoc()) {
    $row['observaciones'] = utf8_encode($row['observaciones']);
    $row['tratamientos'] = utf8_encode($row['tratamientos']);
    $historial[] = $row;
}

// Codificar utf8 para el paciente también
$pacienteDataLimpio = $pacienteData ? array_map(function($v) {
    return is_string($v) ? utf8_encode($v) : $v;
}, $pacienteData) : null;

$response = ['paciente' => $pacienteDataLimpio, 'historial' => $historial];
ob_clean();
header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);
exit;
?>