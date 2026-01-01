<?php
// enviar_wsp.php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

// 1. Reporte de errores para ver si falla algo interno
ini_set('display_errors', 0);
error_reporting(E_ALL);

// 2. Recibir datos del Frontend
$inputJSON = file_get_contents("php://input");
$input = json_decode($inputJSON, true);

// 3. Validar que llegue el teléfono
if (!$input || empty($input['telefono'])) {
    echo json_encode(['error' => 'Falta teléfono o datos vacíos']);
    exit;
}

// 4. Tu Token Global (Factiliza)
$bearerToken = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIzOTMyNiIsImh0dHA6Ly9zY2hlbWFzLm1pY3Jvc29mdC5jb20vd3MvMjAwOC8wNi9pZGVudGl0eS9jbGFpbXMvcm9sZSI6ImNvbnN1bHRvciJ9.0RziSP13ToPB8u7ouUnbC2Eli8P9QeQvvVPIUmHWIbo";

// 5. Limpieza del número (Formato Perú 51)
$numero = preg_replace("/[^0-9]/", "", $input['telefono']);
if (strlen($numero) == 9) $numero = "51" . $numero;

// 6. Datos del mensaje
$paciente = $input['paciente'] ?? 'Paciente';
$fecha = $input['fecha'] ?? '--';
$hora = $input['hora'] ?? '--';
$doctor = $input['doctor'] ?? '--';
$especialidad = $input['especialidad'] ?? '--';
$sede_id = isset($input['sede_id']) ? (int)$input['sede_id'] : 2; // Por defecto 2 (Huaura)

// 7. Configuración Dinámica por Sede
$instancia_id = "";
$imgUrl = "";
$direccion = "";
$telefono_sede = "";
$fb = "";
$sede_nombre = "";

switch ($sede_id) {
    case 1: // HUACHO (Integra Salud)
        $instancia_id = "NTE5ODM4NzIyMjc%3D"; 
        $imgUrl = "https://i.ibb.co/PsbsRnyL/5bf65607-4652-4f48-94eb-481cfddb77bf.jpg";
        $direccion = "Calle Arambulo La Rosa N° 156 - Huacho";
        $telefono_sede = "983872227";
        $fb = "https://www.facebook.com/IntegraSaludHuacho/";
        $sede_nombre = "Policlínico Integra Salud";
        break;

    case 3: // MEDIO MUNDO
        $instancia_id = "NTE5OTc2NzA1MzI%3D"; // Usa el mismo token que Huaura
        $imgUrl = "https://i.ibb.co/RG4bqCpv/Whats-App-Image-2025-09-11-at-16-38-21.jpg";
        $direccion = "Av. Ezequiel Gago Mz. H Lote 19 B";
        $telefono_sede = "992982658";
        $fb = "https://www.facebook.com/LosAngelesMedioMundo/";
        $sede_nombre = "Clínica Los Ángeles (Medio Mundo)";
        break;

    default: // HUAURA (ID 2 o cualquiera por defecto)
        $instancia_id = "NTE5OTc2NzA1MzI%3D";
        $imgUrl = "https://i.ibb.co/r2S5m41y/cebac8af-1482-46f3-92f5-2ef19d67514f.jpg";
        $direccion = "Av. San Martín N° 392 Huaura";
        $telefono_sede = "997670532";
        $fb = "https://www.facebook.com/LosAngelesHuaura/";
        $sede_nombre = "Clínica Los Ángeles";
        break;
}

// 8. Construir el Texto del Mensaje
$mensaje = "*Cita Médica Programada - {$sede_nombre}* 🎄\n\n" .
           "👤 {$paciente}\n" .
           "🩺 {$especialidad}\n" .
           "👨‍⚕️ {$doctor}\n" .
           "🗓 {$fecha} a las {$hora}\n" .
           "============================\n" .
           "🚩 {$direccion}\n" .
           "📱 {$telefono_sede}\n" .
           "🔵 {$fb}\n\n" .
           "_Por favor llegar 15 min antes._";

// 9. Preparar envío a Factiliza
$data = [
    "number" => $numero,
    "mediatype" => "image",
    "filename" => "recordatorio.jpg",
    "media" => $imgUrl,
    "caption" => $mensaje
];

$urlApi = "https://apiwsp.factiliza.com/v1/message/sendmedia/" . $instancia_id;

// 10. Ejecutar CURL
$ch = curl_init($urlApi);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . trim($bearerToken),
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo json_encode(['error' => 'Error CURL: ' . curl_error($ch)]);
} else {
    // Devolvemos la respuesta de la API para saber si salió bien
    echo json_encode([
        'success' => true,
        'http_code' => $httpCode,
        'api_response' => json_decode($response)
    ]);
}
curl_close($ch);
?>