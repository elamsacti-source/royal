<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
ini_set('display_errors', 0);
error_reporting(E_ALL);

include 'db_config.php';

$method = $_SERVER['REQUEST_METHOD'];

// --- OBTENER LISTA PARA EL TARIFARIO ---
if ($method === 'GET') {
    // CORRECCIÓN: Agregamos el cálculo (venta - costo) as ganancia
    $sql = "SELECT c.id, 
                   c.precio_venta, 
                   c.precio_costo, 
                   (c.precio_venta - c.precio_costo) as ganancia, 
                   c.horario_referencial,
                   d.nombre_completo as doc_nombre, 
                   d.telefono as doc_tel, 
                   e.nombre as esp_nombre, 
                   s.nombre as sede_nombre, 
                   t.nombre as tipo_nombre 
            FROM catalogo c
            LEFT JOIN doctores d ON c.doctor_id = d.id
            LEFT JOIN especialidades e ON d.especialidad_id = e.id
            LEFT JOIN sedes s ON c.sede_id = s.id
            LEFT JOIN tipos_servicio t ON c.tipo_servicio_id = t.id
            WHERE 1=1
            ORDER BY c.id DESC";
            
    $res = $conn->query($sql);
    $out = [];
    
    if ($res) {
        while($r = $res->fetch_assoc()) {
            // Formateo de seguridad por si hay nulos
            $r['ganancia'] = $r['ganancia'] ?? '0.00'; 
            $out[] = $r;
        }
    }
    echo json_encode($out);
    exit;
}

// --- GUARDAR / ELIMINAR ITEMS DEL CATÁLOGO ---
if ($method === 'POST') {
    $input = file_get_contents("php://input");
    $d = json_decode($input, true);
    
    $action = $d['action'] ?? '';

    // 1. CREAR NUEVO PRECIO
    if ($action === 'create') {
        // Recibimos datos
        $doc_id = $d['doc_id'];
        $sede_id = $d['sede'];
        $tipo_id = $d['tipo_id'];
        $pv = $d['pv'];
        $pc = $d['pc'];
        $horario = $d['hor']; // Texto HTML del horario
        
        // Calculamos ganancia para guardarla también (opcional, ya que la calculamos al leer)
        $ganancia = floatval($pv) - floatval($pc);

        $stmt = $conn->prepare("INSERT INTO catalogo (doctor_id, sede_id, tipo_servicio_id, precio_venta, precio_costo, ganancia, horario_referencial) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiddss", $doc_id, $sede_id, $tipo_id, $pv, $pc, $ganancia, $horario);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
    }
    
    // 2. ELIMINAR PRECIO
    elseif ($action === 'delete') {
        $id = $d['id'];
        if($conn->query("DELETE FROM catalogo WHERE id=$id")){
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
    }
    else {
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
    }
}

$conn->close();
?>