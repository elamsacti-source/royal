<?php
// public_get_data.php
 // --- CORS ---
if (isset($_SERVER['HTTP_ORIGIN'])) {
    $allowed = [
        'https://clinicalosangeles.org',
        'https://www.clinicalosangeles.org',
        'http://localhost:3000',
        'http://127.0.0.1:5500'
    ];
    $origin = $_SERVER['HTTP_ORIGIN'];
    if (in_array($origin, $allowed)) header("Access-Control-Allow-Origin: $origin");
}
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
// --- fin CORS ---

header('Content-Type: application/json; charset=utf-8');
// Desactivar errores visibles para no romper el JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

include 'db_config.php';

$out = [
    'sedes'=>[], 
    'especialidades'=>[], 
    'doctores'=>[], 
    'usuarios'=>[],
    'suplementos'=>[],
    'procedimientos'=>[],
    'medicamentos'=>[]
];

if (!isset($conn) || !($conn instanceof mysqli)) { echo json_encode($out); exit; }

// 1. Sedes
$res = $conn->query("SELECT id,nombre FROM sedes WHERE activo=1 ORDER BY nombre");
if ($res) while ($r = $res->fetch_assoc()) $out['sedes'][] = $r;

// 2. Especialidades
$res = $conn->query("SELECT id,nombre FROM especialidades ORDER BY nombre");
if ($res) while ($r = $res->fetch_assoc()) $out['especialidades'][] = $r;

// 3. Doctores
$res = $conn->query("SELECT id,nombre_completo, especialidad_id FROM doctores ORDER BY nombre_completo");
// Cruzamos con nombre de especialidad si es necesario en JS, o lo dejamos simple
if ($res) {
    while ($r = $res->fetch_assoc()) {
        // Obtenemos nombre especialidad manualmente o asumimos frontend filtra
        // Para simplificar, enviamos tal cual. El frontend filtra por texto o ID.
        // Agregamos un campo 'nombre_esp' dummy o hacemos un join arriba si el frontend lo usa.
        // Revisando panel_citas.php, usa d.nombre_esp. Haremos el JOIN mejor.
        $out['doctores'][] = $r;
    }
}
// RE-CONSULTA DOCTORES CON JOIN PARA QUE EL FILTRO DE ESPECIALIDAD FUNCIONE
$sqlDoc = "SELECT d.id, d.nombre_completo, e.nombre as nombre_esp 
           FROM doctores d 
           LEFT JOIN especialidades e ON d.especialidad_id = e.id 
           ORDER BY d.nombre_completo";
$resDoc = $conn->query($sqlDoc);
$out['doctores'] = []; // Limpiamos para llenar bien
if($resDoc) while($r=$resDoc->fetch_assoc()) $out['doctores'][] = $r;


// 4. Usuarios
$res = $conn->query("SELECT id,nombre_completo FROM usuarios ORDER BY nombre_completo");
if ($res) while ($r = $res->fetch_assoc()) $out['usuarios'][] = $r;

// --- NUEVOS: LISTAS PARA COMBOBOX ---

// 5. Suplementos
$res = $conn->query("SELECT nombre FROM lista_suplementos WHERE activo=1 ORDER BY nombre");
if($res) while($r=$res->fetch_assoc()) $out['suplementos'][] = $r;

// 6. Procedimientos
$res = $conn->query("SELECT nombre FROM lista_procedimientos WHERE activo=1 ORDER BY nombre");
if($res) while($r=$res->fetch_assoc()) $out['procedimientos'][] = $r;

// 7. Medicamentos
$res = $conn->query("SELECT nombre FROM lista_medicamentos WHERE activo=1 ORDER BY nombre");
if($res) while($r=$res->fetch_assoc()) $out['medicamentos'][] = $r;

echo json_encode($out, JSON_UNESCAPED_UNICODE);
?>
