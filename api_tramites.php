<?php
include_once 'session.php';
include 'db_config.php';
header('Content-Type: application/json');
date_default_timezone_set('America/Lima');

if (!isset($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['success'=>false]); exit; }

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list_docs';
    
    if ($action === 'list_entidades') {
        $res = $conn->query("SELECT nombre FROM entidades WHERE activo=1 ORDER BY nombre");
        $out = []; while($r = $res->fetch_assoc()) $out[] = $r;
        echo json_encode($out); exit;
    }
    if ($action === 'list_nombres') {
        $res = $conn->query("SELECT nombre FROM nombres_documentos WHERE activo=1 ORDER BY nombre");
        $out = []; while($r = $res->fetch_assoc()) $out[] = $r;
        echo json_encode($out); exit;
    }

    // Listar Docs
    $sql = "SELECT d.*, s.nombre AS sede_nombre FROM company_documents d LEFT JOIN sedes s ON d.sede_id = s.id ORDER BY d.fecha_vencimiento ASC";
    $res = $conn->query($sql);
    $docs = [];
    $hoy = new DateTime(); $hoy->setTime(0,0,0);
    while($row = $res->fetch_assoc()) {
        $venc = new DateTime($row['fecha_vencimiento']); $venc->setTime(0,0,0);
        $dias = (int)$hoy->diff($venc)->format('%r%a');
        $row['dias_restantes'] = $dias;
        $row['estado_calculado'] = ($dias < 0) ? 'expired' : (($dias <= 30) ? 'warning' : 'ok');
        $row['sede_nombre'] = $row['sede_nombre'] ?? 'General';
        $docs[] = $row;
    }
    echo json_encode(['success'=>true, 'data'=>$docs]);
    exit;
}

if ($method === 'POST') {
    if ($_SESSION['user_role'] !== 'admin') { echo json_encode(['success'=>false]); exit; }
    
    // Subida de Archivo
    if (strpos($_SERVER["CONTENT_TYPE"] ?? '', 'multipart') !== false) {
        $ruta_pdf = NULL;
        if (isset($_FILES['archivo_pdf']) && $_FILES['archivo_pdf']['error'] === UPLOAD_ERR_OK) {
            if (!is_dir('uploads')) mkdir('uploads', 0755, true);
            $name = uniqid('doc_').'.pdf';
            if(move_uploaded_file($_FILES['archivo_pdf']['tmp_name'], "uploads/$name")) $ruta_pdf = "uploads/$name";
        }
        $sede = !empty($_POST['sede_id']) ? $_POST['sede_id'] : NULL;
        $stmt = $conn->prepare("INSERT INTO company_documents (nombre, entidad, fecha_emision, fecha_vencimiento, archivo_pdf, sede_id) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("sssssi", $_POST['nombre'], $_POST['entidad'], $_POST['fecha_emision'], $_POST['fecha_vencimiento'], $ruta_pdf, $sede);
        echo json_encode(['success'=>$stmt->execute()]);
        $stmt->close();
    } 
    // JSON Actions (Add Catálogo / Delete)
    else {
        $in = json_decode(file_get_contents('php://input'), true);
        if (($in['action']??'') === 'add_entidad') {
            $conn->prepare("INSERT IGNORE INTO entidades (nombre) VALUES (?)")->execute([$in['nombre']]);
            echo json_encode(['success'=>true]);
        }
        elseif (($in['action']??'') === 'add_nombre') {
            $conn->prepare("INSERT IGNORE INTO nombres_documentos (nombre) VALUES (?)")->execute([$in['nombre']]);
            echo json_encode(['success'=>true]);
        }
        elseif (($in['action']??'') === 'delete') {
            $id = (int)$in['id'];
            $r = $conn->query("SELECT archivo_pdf FROM company_documents WHERE id=$id")->fetch_assoc();
            if($r && $r['archivo_pdf'] && file_exists($r['archivo_pdf'])) unlink($r['archivo_pdf']);
            $conn->query("DELETE FROM company_documents WHERE id=$id");
            echo json_encode(['success'=>true]);
        }
    }
}
$conn->close();
?>