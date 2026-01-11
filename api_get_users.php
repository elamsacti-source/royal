<?php
// api_get_users.php
 // --- CORS (pegar al inicio) ---
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
require_once 'db_config.php';
if (!isset($conn) || !($conn instanceof mysqli)) { echo json_encode([]); exit; }

$sql = "SELECT id, nombre_completo FROM usuarios ORDER BY nombre_completo";
$res = $conn->query($sql);
$out = [];
if ($res) {
    while ($r = $res->fetch_assoc()) $out[] = $r;
}
echo json_encode($out, JSON_UNESCAPED_UNICODE);
