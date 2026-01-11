<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

// 1. CONEXIÓN CORRECTA
include 'db_config.php';

// 2. RECIBIR DATOS
$d = json_decode(file_get_contents("php://input"), true);
$act = $d['action'] ?? ''; 
$id = intval($d['id'] ?? 0);

if (!$id) {
    echo json_encode(['success'=>false, 'message'=>'ID no válido']);
    exit;
}

try {
    // --- ELIMINAR USUARIO ---
    if ($act == 'delete_user') {
        // Protección: No borrar al Admin Principal (ID 1)
        if ($id == 1) {
            throw new Exception("No puedes eliminar al Administrador Principal.");
        }

        // Primero borramos sus turnos para evitar error de llave foránea
        $conn->query("DELETE FROM programacion WHERE usuario_id=$id");
        
        // También borramos su perfil de supervisor si existe
        $conn->query("DELETE FROM supervisores WHERE user_id=$id");

        // Finalmente borramos el usuario de la tabla CORRECTA 'usuarios'
        if (!$conn->query("DELETE FROM usuarios WHERE id=$id")) {
            throw new Exception("Error BD: " . $conn->error);
        }
    }

    // --- ELIMINAR SEDE ---
    elseif ($act == 'delete_sede') {
        // Solo marca como inactivo para no romper historiales de citas
        // Si prefieres borrado total usa: DELETE FROM sedes WHERE id=$id
        if (!$conn->query("UPDATE sedes SET activo=0 WHERE id=$id")) {
            throw new Exception("Error BD: " . $conn->error);
        }
    }

    // --- ELIMINAR TURNO (ROTACIÓN) ---
    elseif ($act == 'delete_turno') {
        if (!$conn->query("DELETE FROM programacion WHERE id=$id")) {
            throw new Exception("Error BD: " . $conn->error);
        }
    }

    // --- ELIMINAR DE CATÁLOGO ---
    elseif ($act == 'delete_catalogo') { // Por si acaso usas este nombre en otro lado
        if (!$conn->query("DELETE FROM catalogo WHERE id=$id")) {
            throw new Exception("Error BD: " . $conn->error);
        }
    }

    echo json_encode(['success'=>true]);

} catch (Exception $e) {
    // Captura error de llave foránea (si el usuario ya tiene citas agendadas)
    if (strpos($conn->error, 'foreign key constraint') !== false) {
        $msg = "No se puede eliminar: Este registro tiene citas o datos históricos asociados.";
    } else {
        $msg = $e->getMessage();
    }
    echo json_encode(['success'=>false, 'message'=>$msg]);
}

$conn->close();
?>