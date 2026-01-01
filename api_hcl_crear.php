<?php
session_start(); // Necesario para obtener el usuario actual
header('Content-Type: application/json');
include 'db_config.php';

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['dni']) || empty($data['nombres'])) {
    echo json_encode(['success' => false, 'error' => 'DNI y Nombres obligatorios']);
    exit;
}

// Verificar duplicados
$check = $conn->query("SELECT id FROM pacientes WHERE dni = '".$data['dni']."'");
if ($check->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'Este DNI ya está registrado']);
    exit;
}

// 1. OBTENER SEDE ACTUAL DEL USUARIO LOGUEADO
$user_id = $_SESSION['user_id'] ?? 0;
$sede_origen = 1; // Default a sede 1 si falla

if ($user_id > 0) {
    // Asumiendo que la tabla usuarios tiene 'sede_id'
    $qSede = $conn->query("SELECT sede_id FROM usuarios WHERE id = $user_id LIMIT 1");
    if ($rowSede = $qSede->fetch_assoc()) {
        $sede_origen = $rowSede['sede_id'];
    }
}

// 2. INSERTAR PACIENTE CON SEDE DE ORIGEN
$zq_manual = !empty($data['zq']) ? $data['zq'] : NULL;

$stmt = $conn->prepare("INSERT INTO pacientes (dni, nombres, apellidos, telefono, codigo_zqclinic, historia_clinica, sede_origen_id, fecha_nacimiento, departamento, provincia, distrito, direccion) VALUES (?,?,?,?,?, 'PENDIENTE', ?, ?,?,?,?,?)");

$stmt->bind_param("sssssisssss", 
    $data['dni'], 
    $data['nombres'], 
    $data['apellidos'], 
    $data['telefono'], 
    $zq_manual,
    $sede_origen, // <--- AQUÍ GUARDAMOS LA SEDE
    $data['nacimiento'],
    $data['departamento'],
    $data['provincia'],
    $data['distrito'],
    $data['direccion']
);

if ($stmt->execute()) {
    $nuevo_id = $stmt->insert_id;
    // Generar HC Automática
    $nueva_hc = "HCL-" . str_pad($nuevo_id, 6, "0", STR_PAD_LEFT);
    $conn->query("UPDATE pacientes SET historia_clinica = '$nueva_hc' WHERE id = $nuevo_id");
    
    echo json_encode(['success' => true, 'id' => $nuevo_id, 'hc_nueva' => $nueva_hc]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}

$conn->close();
?>