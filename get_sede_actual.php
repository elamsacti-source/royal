<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
ini_set('display_errors', 0);
error_reporting(E_ALL);

include 'db_config.php';

// =======================================================================
// CASO 1: PETICIÓN DESDE CHECKLIST (ADMIN/SUPERVISOR)
// Esta sección DEBE ir primero para evitar el bloqueo por IP.
// El checklist envía el parámetro 'fecha', pero NO envía 'id' de usuario.
// =======================================================================
if (isset($_GET['fecha'])) {
    
    $fecha = $_GET['fecha'];

    // 1. Obtener todas las sedes activas (Sin restricción de IP)
    $sqlSedes = "SELECT id, nombre FROM sedes WHERE activo = 1 ORDER BY nombre";
    $res = $conn->query($sqlSedes);

    $sedes_data = [];

    if ($res) {
        while($sede = $res->fetch_assoc()) {
            $sede_id = $sede['id'];
            
            // 2. Buscar personal programado en esa sede y fecha (Para mostrar en el checklist)
            $sqlStaff = "SELECT u.nombre_completo 
                         FROM programacion p
                         JOIN usuarios u ON p.usuario_id = u.id
                         WHERE p.sede_id = $sede_id 
                         AND p.fecha = '$fecha'";
                         
            $resStaff = $conn->query($sqlStaff);
            $nombres = [];
            
            if ($resStaff) {
                while($r = $resStaff->fetch_assoc()) {
                    // Formatear nombre corto para que entre en el input
                    $partes = explode(' ', trim($r['nombre_completo']));
                    $corto = $partes[0];
                    if (count($partes) >= 3) $corto .= ' ' . $partes[count($partes)-2];
                    elseif (count($partes) == 2) $corto .= ' ' . $partes[1];
                    
                    $nombres[] = ucwords(strtolower($corto));
                }
            }
            
            $sedes_data[] = [
                'sede_id' => $sede['id'],
                'nombre' => $sede['nombre'],
                'staff' => empty($nombres) ? '' : implode(', ', array_unique($nombres))
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'count' => count($sedes_data),
        'sedes' => $sedes_data
    ]);
    exit; // ¡IMPORTANTE! Salir aquí para no ejecutar la lógica de IP abajo
}

// =======================================================================
// CASO 2: PETICIÓN DE UN USUARIO (PANEL DE CITAS / INTRANET)
// Aquí SÍ aplicamos la seguridad por IP porque es para operar el sistema.
// =======================================================================

// --- 1. OBTENER IP REAL ---
function getRealIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return $_SERVER['HTTP_X_FORWARDED_FOR'];
    return $_SERVER['REMOTE_ADDR'];
}
$ip_actual = getRealIP();

// --- 2. VALIDACIÓN POR IP (CANDADO DE SEGURIDAD) ---
// Solo si se está consultando por un usuario (?id=...)
if (isset($_GET['id'])) {
    
    // A. Verificar si la IP actual está amarrada a una sede
    $sqlIP = "SELECT id, nombre FROM sedes WHERE ip_publica = '$ip_actual' AND activo = 1 LIMIT 1";
    $resIP = $conn->query($sqlIP);

    if ($rowIP = $resIP->fetch_assoc()) {
        // IP RECONOCIDA: Forzamos esta sede
        echo json_encode([
            'sede_id' => $rowIP['id'],
            'nombre'  => $rowIP['nombre'],
            'origen'  => 'ip_fija_seguridad'
        ]);
        exit;
    }

    // B. Si la IP no coincide, seguimos con la lógica normal (Rotación o Base)
    // (Opcional: Si quieres bloquear acceso externo, pon un 'exit' aquí)

    $user_id = intval($_GET['id']);
    $hoy = date('Y-m-d');
    
    // C. Buscar en programación (Rotación)
    $sqlProg = "SELECT p.sede_id, s.nombre 
                FROM programacion p 
                JOIN sedes s ON p.sede_id = s.id 
                WHERE p.usuario_id = ? AND p.fecha = ? 
                LIMIT 1";
                
    $stmt = $conn->prepare($sqlProg);
    $stmt->bind_param("is", $user_id, $hoy);
    $stmt->execute();
    $resProg = $stmt->get_result();
    
    if ($row = $resProg->fetch_assoc()) {
        echo json_encode([
            'sede_id' => $row['sede_id'],
            'nombre'  => $row['nombre'],
            'origen'  => 'rotacion'
        ]);
        exit;
    }
    
    // D. Buscar Sede Base en perfil
    $sqlBase = "SELECT u.sede_id, s.nombre 
                FROM usuarios u 
                LEFT JOIN sedes s ON u.sede_id = s.id 
                WHERE u.id = ?";
                
    $stmt2 = $conn->prepare($sqlBase);
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $resBase = $stmt2->get_result();

    if ($rowBase = $resBase->fetch_assoc()) {
        if (!empty($rowBase['sede_id'])) {
            echo json_encode([
                'sede_id' => $rowBase['sede_id'],
                'nombre'  => $rowBase['nombre'],
                'origen'  => 'base'
            ]);
        } else {
            // Default
            echo json_encode([
                'sede_id' => 1, 
                'nombre'  => 'Huacho (Default)',
                'origen'  => 'default'
            ]);
        }
    } else {
        echo json_encode(['error' => 'Usuario no encontrado']);
    }
    exit;
}

// Default fallback si no hay parámetros
echo json_encode([]);
$conn->close();
?>