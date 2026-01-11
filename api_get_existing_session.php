<?php
// Desactivar que los errores de PHP se impriman en pantalla (rompen el JSON)
ini_set('display_errors', 0);
error_reporting(E_ALL);

include_once 'session.php';
include 'db_config.php';

header('Content-Type: application/json');

// 1. Validar parámetros de entrada
$fecha = $_GET['fecha'] ?? '';
$turno = $_GET['turno'] ?? '';
$sede = $_GET['sede_id'] ?? '';

if(empty($fecha) || empty($turno) || empty($sede)) {
    // Devolver array vacío en vez de error para no romper el flujo
    echo json_encode(['success'=>true, 'session'=>null]);
    exit;
}

try {
    // 2. Buscar la sesión (Cabecera)
    $stmt = $conn->prepare("SELECT * FROM checklist_sessions WHERE fecha=? AND turno=? AND sede_id=?");
    if(!$stmt) {
        throw new Exception("Error al preparar consulta: " . $conn->error);
    }
    
    $stmt->bind_param("ssi", $fecha, $turno, $sede);
    
    if(!$stmt->execute()) {
        throw new Exception("Error al ejecutar consulta: " . $stmt->error);
    }
    
    $res = $stmt->get_result();

    if ($session = $res->fetch_assoc()) {
        // 3. Si existe la sesión, buscar sus items (Detalle)
        // IMPORTANTE: Agregamos 'quantity' que faltaba antes
        $sessionId = (int)$session['id'];
        $sqlItems = "SELECT activity_id, estado, observacion, quantity FROM checklist_items WHERE session_id = $sessionId";
        
        $iRes = $conn->query($sqlItems);
        if(!$iRes) {
            throw new Exception("Error al cargar items: " . $conn->error);
        }
        
        $items = [];
        while($r = $iRes->fetch_assoc()) {
            $items[] = $r;
        }
        
        echo json_encode(['success'=>true, 'session'=>$session, 'items'=>$items]);
        
    } else {
        // No hay sesión guardada todavía (es normal)
        echo json_encode(['success'=>true, 'session'=>null]);
    }
    
    $stmt->close();

} catch (Exception $e) {
    // Si algo falla, devolver error 500 pero con mensaje JSON claro
    http_response_code(500);
    echo json_encode(['success'=>false, 'error'=>$e->getMessage()]);
}

$conn->close();
?>