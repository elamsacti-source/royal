<?php
// api_get_work_schedule.php
// Última versión corregida para clinicalosangeles.org
// Lee tabla `programacion` y usa `usuarios.nombre_completo` (según tu SQL).

// --- CORS (pegar al inicio) ---
if (isset($_SERVER['HTTP_ORIGIN'])) {
    $allowed = [
        'https://clinicalosangeles.org',
        'https://www.clinicalosangeles.org',
        'http://localhost:3000',
        'http://127.0.0.1:5500'
    ];
    $origin = $_SERVER['HTTP_ORIGIN'];
    if (in_array($origin, $allowed, true)) {
        header("Access-Control-Allow-Origin: $origin");
    }
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, PATCH, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
// --- fin CORS ---

header('Content-Type: application/json; charset=utf-8');

$logfile = __DIR__ . '/logs/api_get_work_schedule.log';
if (!is_dir(dirname($logfile))) @mkdir(dirname($logfile), 0755, true);

try {
    require_once 'db_config.php'; // Debe definir $conn (mysqli)

    if (!isset($conn) || !($conn instanceof mysqli)) {
        $msg = "db_config.php no definió \$conn como mysqli";
        error_log(date('c') . " - {$msg}\n", 3, $logfile);
        echo json_encode(['success' => false, 'error' => $msg]);
        exit;
    }

    // Parámetros
    $inicio = isset($_GET['inicio']) ? $_GET['inicio'] : date('Y-m-01');
    $fin    = isset($_GET['fin'])    ? $_GET['fin']    : date('Y-m-d');
    $sede   = isset($_GET['sede']) && $_GET['sede'] !== '' ? intval($_GET['sede']) : null;
    $turno  = isset($_GET['turno']) && $_GET['turno'] !== '' ? trim($_GET['turno']) : null;
    $usuario_id = isset($_GET['usuario_id']) && $_GET['usuario_id'] !== '' ? intval($_GET['usuario_id']) : null;

    // Validación básica de fechas (YYYY-MM-DD)
    $d1 = DateTime::createFromFormat('Y-m-d', $inicio);
    $d2 = DateTime::createFromFormat('Y-m-d', $fin);
    if (!$d1 || !$d2) {
        throw new Exception("Formato fechas inválido. Uso: inicio=YYYY-MM-DD&fin=YYYY-MM-DD");
    }

    // SQL fijo (usa usuarios.nombre_completo según tu SQL)
    $sql = "
      SELECT 
        p.id,
        p.usuario_id,
        u.nombre_completo AS licenciada_name,
        p.sede_id,
        s.nombre AS sede_name,
        p.fecha,
        p.turno,
        p.hora_inicio,
        p.hora_fin
      FROM programacion p
      LEFT JOIN usuarios u ON u.id = p.usuario_id
      LEFT JOIN sedes s ON s.id = p.sede_id
      WHERE p.fecha BETWEEN ? AND ?
    ";

    $types = "ss";
    $params = [$inicio, $fin];

    if (!is_null($sede)) {
        $sql .= " AND p.sede_id = ?";
        $types .= "i";
        $params[] = $sede;
    }
    if (!is_null($turno)) {
        $sql .= " AND p.turno = ?";
        $types .= "s";
        $params[] = $turno;
    }
    if (!is_null($usuario_id)) {
        $sql .= " AND p.usuario_id = ?";
        $types .= "i";
        $params[] = $usuario_id;
    }

    $sql .= " ORDER BY p.fecha ASC, p.hora_inicio ASC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $err = $conn->error;
        error_log(date('c') . " - prepare error: {$err}\nSQL: {$sql}\n", 3, $logfile);
        throw new Exception("Error preparando consulta SQL: " . $err);
    }

    // bind_param con referencias (compatible con versiones de PHP/mysqli en InfinityFree)
    if (count($params) > 0) {
        $bind_names = [];
        $bind_names[] = $types;
        foreach ($params as $i => $value) {
            $varName = "bind_param_{$i}";
            $$varName = $value;
            $bind_names[] = &$$varName;
        }
        call_user_func_array([$stmt, 'bind_param'], $bind_names);
    }

    if (!$stmt->execute()) {
        $err = $stmt->error;
        error_log(date('c') . " - execute error: {$err}\n", 3, $logfile);
        throw new Exception("Error ejecutando consulta SQL: " . $err);
    }

    $res = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);

    $out = [];
    foreach ($rows as $r) {
        $fecha = $r['fecha'];
        $hora_i = $r['hora_inicio'] ?? '00:00:00';
        $hora_f = $r['hora_fin'] ?? null;
        if (!$hora_f || $hora_f === '00:00:00') {
            // si no hay hora_fin, sumar 6 horas por defecto (ajusta si quieres otra regla)
            $hora_f = date('H:i:s', strtotime($hora_i) + 60*60*6);
        }

        $start = $fecha . 'T' . (strlen($hora_i) === 8 ? $hora_i : ($hora_i . ':00'));
        $end   = $fecha . 'T' . (strlen($hora_f) === 8 ? $hora_f : ($hora_f . ':00'));

        $tipo = 'OTRO';
        if (!empty($r['turno'])) {
            $tlow = mb_strtolower($r['turno']);
            if (mb_strpos($tlow, 'mañ') !== false || mb_strpos($tlow, 'man') !== false) $tipo = 'MAÑANA';
            elseif (mb_strpos($tlow, 'tard') !== false) $tipo = 'TARDE';
            elseif (mb_strpos($tlow, 'noch') !== false || mb_strpos($tlow, 'noche') !== false) $tipo = 'NOCHE';
            else $tipo = strtoupper($r['turno']);
        }

        $out[] = [
            'id' => $r['id'],
            'licenciada_id' => $r['usuario_id'],
            'licenciada_name' => $r['licenciada_name'] ?? ('Usuario ' . ($r['usuario_id'] ?? '')),
            'sede_id' => $r['sede_id'],
            'sede_name' => $r['sede_name'] ?? null,
            'turno' => $r['turno'],
            'tipo' => $tipo,
            'start' => $start,
            'end' => $end,
            'hora_inicio' => $hora_i,
            'hora_fin' => $hora_f
        ];
    }

    echo json_encode($out, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $msg = $e->getMessage();
    error_log(date('c') . " - EXCEPTION: {$msg}\n", 3, $logfile);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

