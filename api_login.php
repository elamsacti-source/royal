<?php
include_once 'session.php';
include 'db_config.php';
header('Content-Type: application/json');

// Recibimos JSON del frontend
$data = json_decode(file_get_contents('php://input'), true);

$user_input = $data['email'] ?? ''; 
$password = $data['password'] ?? '';

if (!$user_input || !$password) { 
    echo json_encode(['success'=>false, 'error'=>'Datos incompletos']); 
    exit; 
}

// Buscamos en la tabla 'usuarios'
$stmt = $conn->prepare("SELECT id, nombre_completo, usuario, rol, password FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $user_input);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Validamos contraseña
    if ($password == $row['password']) {
        
        // Guardar sesión PHP
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['user_name'] = $row['nombre_completo'];
        $_SESSION['user_role'] = $row['rol'];
        $_SESSION['user_email'] = $row['usuario'];

        // Lógica opcional para supervisores
        if ($row['rol'] === 'usuario') {
            // Asumiendo que la tabla supervisores existe y se enlaza
            // Si te da error aquí, borra este bloque IF interno
            $s = $conn->prepare("SELECT id, nombre FROM supervisores WHERE user_id = ?");
            $s->bind_param("i", $row['id']);
            $s->execute();
            $resSup = $s->get_result();
            if ($sup = $resSup->fetch_assoc()) {
                $_SESSION['supervisor_id'] = $sup['id'];
                $_SESSION['supervisor_name'] = $sup['nombre'];
            }
        }
        
        // --- RESPUESTA JSON PARA EL FRONTEND ---
        echo json_encode([
            'success' => true, 
            'id' => $row['id'],        // <--- ¡ESTO FALTABA! Sin esto, no carga la sede.
            'rol' => $row['rol'],      
            'nombre' => $row['nombre_completo']
        ]);

    } else {
        echo json_encode(['success'=>false, 'error'=>'Contraseña incorrecta']);
    }
} else {
    echo json_encode(['success'=>false, 'error'=>'Usuario no encontrado']);
}
$conn->close();
?>