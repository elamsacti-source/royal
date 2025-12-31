<?php
include_once 'session.php';
include 'db_config.php';
header('Content-Type: application/json');

// Solo personal logueado
if (!isset($_SESSION['user_id'])) { 
    echo json_encode(['success'=>false, 'error'=>'No autorizado']); 
    exit; 
}

$method = $_SERVER['REQUEST_METHOD'];

// --- LISTAR RESULTADOS (Últimos 50) ---
if ($method === 'GET') {
    $search = $_GET['q'] ?? '';
    
    $sql = "SELECT id, dni, nombre_paciente, telefono, codigo_acceso, fecha_registro, archivo_pdf 
            FROM resultados_lab 
            WHERE activo = 1 ";
            
    if (!empty($search)) {
        $sql .= "AND (dni LIKE '%$search%' OR nombre_paciente LIKE '%$search%') ";
    }
    
    $sql .= "ORDER BY id DESC LIMIT 50";
    
    $res = $conn->query($sql);
    $data = [];
    
    while($row = $res->fetch_assoc()) {
        // Normalizar archivos para el frontend
        $archivos = json_decode($row['archivo_pdf']);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $archivos = [$row['archivo_pdf']];
        }
        $row['archivos'] = $archivos;
        $data[] = $row;
    }
    
    echo json_encode($data);
    exit;
}

// --- ACTUALIZAR TELÉFONO ---
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['id']) && isset($input['telefono'])) {
        $stmt = $conn->prepare("UPDATE resultados_lab SET telefono = ? WHERE id = ?");
        $stmt->bind_param("si", $input['telefono'], $input['id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success'=>true]);
        } else {
            echo json_encode(['success'=>false, 'error'=>$conn->error]);
        }
    } else {
        echo json_encode(['success'=>false, 'error'=>'Datos faltantes']);
    }
    exit;
}

$conn->close();
?>