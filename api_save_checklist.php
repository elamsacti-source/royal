<?php
header('Content-Type: application/json');
// Desactivar errores en pantalla para no romper JSON, pero loguearlos si es necesario
ini_set('display_errors', 0);
error_reporting(E_ALL);

include_once 'session.php';
include 'db_config.php';

// Validar Sesión
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false, 'error'=>'No autorizado']);
    exit;
}

$inputJSON = file_get_contents("php://input");
$data = json_decode($inputJSON, true);

if (!$data || !isset($data['session'])) {
    echo json_encode(['success'=>false, 'error'=>'Datos inválidos o vacíos']);
    exit;
}

$sess = $data['session'];
$items = $data['items'];
$sess_id = $data['session_id_to_update'];
$staff_raw = $data['staff_list'] ?? ''; 

// 1. PROCESAR COLABORADORES
$supervisor_actual = $_SESSION['supervisor_name'] ?? $_SESSION['user_name'];

// Limpiar lista de colaboradores (quitar al supervisor si aparece duplicado)
$todos = explode(',', $staff_raw);
$colaboradores = [];
foreach($todos as $nombre) {
    $nombre = trim($nombre);
    if (!empty($nombre) && stripos($supervisor_actual, $nombre) === false && stripos($nombre, $supervisor_actual) === false) {
        $colaboradores[] = $nombre;
    }
}
$col1 = $colaboradores[0] ?? NULL;
$col2 = $colaboradores[1] ?? NULL;
$col3 = $colaboradores[2] ?? NULL;

// 2. CREAR O ACTUALIZAR SESIÓN (CABECERA)
if (!$sess_id) {
    // --- CREAR NUEVA ---
    $stmt = $conn->prepare("INSERT INTO checklist_sessions (fecha, turno, sede_id, supervisor_nombre, colab_1, colab_2, colab_3) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) { echo json_encode(['success'=>false, 'error'=>'Error SQL Session: ' . $conn->error]); exit; }

    $stmt->bind_param("ssissss", $sess['fecha'], $sess['turno'], $sess['sede_id'], $supervisor_actual, $col1, $col2, $col3);
    
    if($stmt->execute()) {
        $sess_id = $stmt->insert_id;
    } else {
        echo json_encode(['success'=>false, 'error'=>'Error al insertar sesión: ' . $stmt->error]); 
        exit;
    }
} else {
    // --- ACTUALIZAR EXISTENTE ---
    $stmt = $conn->prepare("UPDATE checklist_sessions SET supervisor_nombre=?, colab_1=?, colab_2=?, colab_3=? WHERE id=?");
    $stmt->bind_param("ssssi", $supervisor_actual, $col1, $col2, $col3, $sess_id);
    if (!$stmt->execute()) {
        echo json_encode(['success'=>false, 'error'=>'Error al actualizar sesión: ' . $stmt->error]); 
        exit;
    }
}

// 3. GUARDAR ITEMS (DETALLES)
// Primero borramos los anteriores para evitar duplicados en edición
$conn->query("DELETE FROM checklist_items WHERE session_id = $sess_id");

// Preparamos la consulta INSERT corregida (Incluyendo area_id, actividad, criterio, frecuencia)
$sqlInsert = "INSERT INTO checklist_items (session_id, activity_id, area_id, actividad, criterio, frecuencia, estado, observacion, quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmtItem = $conn->prepare($sqlInsert);

if (!$stmtItem) {
    echo json_encode(['success'=>false, 'error'=>'Error SQL Item: ' . $conn->error]);
    exit;
}

foreach ($items as $item) {
    // A. Obtener datos maestros de la actividad (Nombre, Área, etc.) que faltaban
    $actId = intval($item['activity_id']);
    $queryAct = $conn->query("SELECT area_id, nombre, criterio, frecuencia FROM checklist_activities WHERE id = $actId");
    
    if ($rowAct = $queryAct->fetch_assoc()) {
        // Datos recuperados de la tabla maestra
        $area_id = $rowAct['area_id'];
        $actividad_nombre = $rowAct['nombre'];
        $criterio = $rowAct['criterio'];
        $frecuencia = $rowAct['frecuencia'];
    } else {
        // Fallback por si borraron la actividad
        $area_id = 0; 
        $actividad_nombre = 'Desconocida';
        $criterio = '';
        $frecuencia = '';
    }

    $qty = isset($item['quantity']) ? $item['quantity'] : NULL;
    $estado = $item['estado'];
    $obs = $item['observacion'];

    // B. Insertar con todos los datos requeridos por la BD
    // Tipos: i (int), i, i, s (string), s, s, s, s, s
    $stmtItem->bind_param("iiissssss", 
        $sess_id, 
        $actId,
        $area_id,           // Faltaba esto
        $actividad_nombre,  // Faltaba esto
        $criterio,          // Opcional pero bueno tenerlo
        $frecuencia,        // Opcional pero bueno tenerlo
        $estado, 
        $obs, 
        $qty
    );
    
    if (!$stmtItem->execute()) {
        // Si falla uno, reportamos error (útil para debug)
        echo json_encode(['success'=>false, 'error'=>'Error guardando item: ' . $stmtItem->error]);
        exit;
    }
}

echo json_encode(['success'=>true, 'sessionId'=>$sess_id]);
$conn->close();
?>