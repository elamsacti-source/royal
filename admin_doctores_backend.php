<?php
header('Access-Control-Allow-Origin: *');
include 'db_config.php'; // IMPORTANTE

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    $res = $conn->query("SELECT d.*, e.nombre as nombre_esp FROM doctores d LEFT JOIN especialidades e ON d.especialidad_id=e.id");
    $out = []; 
    while($r=$res->fetch_assoc()) $out[]=$r;
    echo json_encode($out);
} 
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cv_path = null;
    if (isset($_FILES['cv']) && $_FILES['cv']['error'] === 0) {
        $dir = 'uploads/';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $path = $dir . uniqid() . '_' . basename($_FILES['cv']['name']);
        if(move_uploaded_file($_FILES['cv']['tmp_name'], $path)) $cv_path = $path;
    }
    
    $stmt = $conn->prepare("INSERT INTO doctores (dni, nombre_completo, especialidad_id, telefono, cmp, rne, cv_path) VALUES (?,?,?,?,?,?,?)");
    $stmt->bind_param("ssissss", $_POST['dni'], $_POST['nombre'], $_POST['esp_id'], $_POST['tel'], $_POST['cmp'], $_POST['rne'], $cv_path);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => $stmt->execute()]);
}
?>