<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

// Desactivar salida de errores HTML para no romper el JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    // 1. Incluir conexión
    if (!file_exists('db_config.php')) {
        throw new Exception("Falta el archivo db_config.php");
    }
    include 'db_config.php';

    if ($conn->connect_error) {
        throw new Exception("Error de conexión BD: " . $conn->connect_error);
    }

    // 2. Método GET: Listar
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $sql = "SELECT u.id, u.nombre_completo, u.usuario, u.rol, u.email, 
                       s.nombre as nombre_sede,
                       (SELECT COUNT(*) FROM supervisores sup WHERE sup.user_id = u.id AND sup.activo = 1) as es_supervisor
                FROM usuarios u 
                LEFT JOIN sedes s ON u.sede_id = s.id
                ORDER BY u.id DESC";
                
        $res = $conn->query($sql);
        if (!$res) throw new Exception("Error SQL al listar: " . $conn->error);

        $out = []; 
        while($r = $res->fetch_assoc()) $out[] = $r;
        echo json_encode($out);
    } 
    
    // 3. Método POST: Crear
    else {
        $input = file_get_contents("php://input");
        $d = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON inválido recibido");
        }

        if(empty($d['nombre']) || empty($d['user']) || empty($d['pass'])) {
            throw new Exception("Faltan datos obligatorios");
        }

        $nombre = $d['nombre'];
        $usuario = $d['user'];
        $pass = $d['pass'];
        $rol = $d['rol'];
        $email = $d['user']; 
        
        // Manejo seguro de nulos
        $sede = !empty($d['sede']) ? $d['sede'] : NULL;
        $horario = !empty($d['horario']) ? $d['horario'] : NULL;

        // Verificar si usuario ya existe
        $check = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        $check->bind_param("s", $usuario);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            throw new Exception("El usuario '$usuario' ya existe.");
        }

        // Insertar
        $stmt = $conn->prepare("INSERT INTO usuarios (nombre_completo, usuario, password, rol, email, sede_id, horario_referencial) VALUES (?,?,?,?,?,?,?)");
        if (!$stmt) throw new Exception("Error preparando SQL: " . $conn->error);

        // Usamos 's' para todo para evitar conflictos de tipos
        $stmt->bind_param("sssssss", $nombre, $usuario, $pass, $rol, $email, $sede, $horario);
        
        if($stmt->execute()) {
            $new_user_id = $stmt->insert_id;
            
            // Crear Supervisor si se marcó
            if (!empty($d['es_supervisor'])) {
                $conn->query("DELETE FROM supervisores WHERE user_id = $new_user_id");
                $stmtSup = $conn->prepare("INSERT INTO supervisores (nombre, user_id, activo) VALUES (?, ?, 1)");
                $stmtSup->bind_param("si", $nombre, $new_user_id);
                $stmtSup->execute();
            }
            echo json_encode(['success'=>true]);
        } else {
            throw new Exception("Error al ejecutar inserción: " . $stmt->error);
        }
    }

} catch (Throwable $e) {
    // Captura cualquier error fatal y lo devuelve como JSON legible
    http_response_code(500); // Opcional, para marcarlo como error
    echo json_encode(['success'=>false, 'error' => $e->getMessage()]);
}

if (isset($conn)) $conn->close();
?>