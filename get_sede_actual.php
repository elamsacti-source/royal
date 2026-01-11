<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
ini_set('display_errors', 0);
error_reporting(E_ALL);

include 'db_config.php';

// =======================================================================
// FUNCIÓN MEJORADA PARA OBTENER IP REAL
// =======================================================================
function getRealIP() {
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // Verificar si viene de un proxy o balanceador de carga
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Puede venir una lista: "client_ip, proxy1, proxy2"
        // Tomamos solo la primera parte
        $lista = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($lista[0]);
    }
    
    return $ip;
}

$ip_actual = getRealIP();

// =======================================================================
// CASO 1: PETICIÓN DESDE CHECKLIST (ADMIN/SUPERVISOR)
// =======================================================================
if (isset($_GET['fecha'])) {
    $fecha = $_GET['fecha'];
    $sqlSedes = "SELECT id, nombre FROM sedes WHERE activo = 1 ORDER BY nombre";
    $res = $conn->query($sqlSedes);
    $sedes_data = [];

    if ($res) {
        while($sede = $res->fetch_assoc()) {
            $sede_id = $sede['id'];
            $sqlStaff = "SELECT u.nombre_completo FROM programacion p JOIN usuarios u ON p.usuario_id = u.id WHERE p.sede_id = $sede_id AND p.fecha = '$fecha'";
            $resStaff = $conn->query($sqlStaff);
            $nombres = [];
            if ($resStaff) {
                while($r = $resStaff->fetch_assoc()) {
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
    echo json_encode(['success' => true, 'count' => count($sedes_data), 'sedes' => $sedes_data]);
    exit;
}

// =======================================================================
// CASO 2: PETICIÓN DE UN USUARIO (PANEL DE CITAS / INTRANET)
// =======================================================================
if (isset($_GET['id'])) {
    
    // A. Verificar si la IP actual está amarrada a una sede
    // Usamos Prepared Statement para seguridad extra con la IP
    $stmtIP = $conn->prepare("SELECT id, nombre FROM sedes WHERE ip_publica = ? AND activo = 1 LIMIT 1");
    $stmtIP->bind_param("s", $ip_actual);
    $stmtIP->execute();
    $resIP = $stmtIP->get_result();

    if ($rowIP = $resIP->fetch_assoc()) {
        // IP RECONOCIDA: Forzamos esta sede
        echo json_encode([
            'sede_id' => $rowIP['id'],
            'nombre'  => $rowIP['nombre'],
            'origen'  => 'ip_fija_seguridad',
            'debug_ip' => $ip_actual // Útil para ver qué detectó
        ]);
        exit;
    }

    // B. Si la IP no coincide, seguimos lógica normal
    $user_id = intval($_GET['id']);
    $hoy = date('Y-m-d');
    
    // C. Buscar en programación (Rotación)
    $stmt = $conn->prepare("SELECT p.sede_id, s.nombre FROM programacion p JOIN sedes s ON p.sede_id = s.id WHERE p.usuario_id = ? AND p.fecha = ? LIMIT 1");
    $stmt->bind_param("is", $user_id, $hoy);
    $stmt->execute();
    $resProg = $stmt->get_result();
    
    if ($row = $resProg->fetch_assoc()) {
        echo json_encode([
            'sede_id' => $row['sede_id'],
            'nombre'  => $row['nombre'],
            'origen'  => 'rotacion',
            'debug_ip' => $ip_actual
        ]);
        exit;
    }
    
    // D. Buscar Sede Base en perfil
    $stmt2 = $conn->prepare("SELECT u.sede_id, s.nombre FROM usuarios u LEFT JOIN sedes s ON u.sede_id = s.id WHERE u.id = ?");
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $resBase = $stmt2->get_result();

    if ($rowBase = $resBase->fetch_assoc()) {
        if (!empty($rowBase['sede_id'])) {
            echo json_encode([
                'sede_id' => $rowBase['sede_id'],
                'nombre'  => $rowBase['nombre'],
                'origen'  => 'base',
                'debug_ip' => $ip_actual
            ]);
        } else {
            echo json_encode([
                'sede_id' => 1, 
                'nombre'  => 'Huacho (Default)',
                'origen'  => 'default',
                'debug_ip' => $ip_actual
            ]);
        }
    } else {
        echo json_encode(['error' => 'Usuario no encontrado']);
    }
    exit;
}

echo json_encode([]);
$conn->close();
?>