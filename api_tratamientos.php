<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

ini_set('display_errors', 0);
error_reporting(E_ALL);

include 'db_config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    
    // --- CASO 1: SUBIDA DE EVIDENCIA (FOTO/PDF) ---
    if (isset($_FILES['archivo'])) {
        $id = $_POST['id'];
        $tipo_evidencia = $_POST['tipo_evidencia']; // 'wsp' o 'realizado'
        
        $upload_dir = "uploads/evidencias/"; 
        $server_path = __DIR__ . "/" . $upload_dir;

        if (!is_dir($server_path)) { mkdir($server_path, 0755, true); }
        
        $ext = pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION);
        $nombre_archivo = "evidencia_" . $tipo_evidencia . "_" . $id . "_" . uniqid() . "." . $ext;
        
        $ruta_final_servidor = $server_path . $nombre_archivo;
        $ruta_final_bd = $upload_dir . $nombre_archivo; 
        
        if (move_uploaded_file($_FILES['archivo']['tmp_name'], $ruta_final_servidor)) {
            
            // LÓGICA DE ACTUALIZACIÓN
            if ($tipo_evidencia === 'wsp') {
                // Si es WhatsApp, solo cambia estado a GESTION
                $sql = "UPDATE tratamiento_cronograma SET wsp_enviado = 1, evidencia_wsp = ?, estado = 'GESTION' WHERE id = ?";
            } else {
                // Si es REALIZADO, guardamos la evidencia Y LA FECHA DE EJECUCIÓN ACTUAL (NOW())
                $sql = "UPDATE tratamiento_cronograma SET realizado = 1, evidencia_realizado = ?, estado = 'REALIZADO', fecha_ejecucion = NOW() WHERE id = ?";
            }
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $ruta_final_bd, $id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Error BD: ' . $conn->error]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al mover archivo.']);
        }
        exit;
    }
    
    // --- CASO 2: CANCELAR / RECHAZAR ---
    $input = json_decode(file_get_contents("php://input"), true);
    
    if (isset($input['action']) && $input['action'] === 'refuse') {
        $id = intval($input['id']);
        // Al cancelar, borramos la fecha de ejecución si existía
        $stmt = $conn->prepare("UPDATE tratamiento_cronograma SET estado = 'CANCELADO', fecha_ejecucion = NULL WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error BD: ' . $conn->error]);
        }
        exit;
    }
    
    echo json_encode(['success' => false, 'error' => 'Acción no válida']);
    exit;
}
?>