<?php
include 'db_config.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// --- BUSCAR RESULTADOS (PÚBLICO) ---
if ($method === 'GET') {
    $dni = $_GET['dni'] ?? '';
    $codigo = $_GET['codigo'] ?? '';

    if (!$dni || !$codigo) {
        echo json_encode(['success'=>false, 'error'=>'Faltan datos']);
        exit;
    }

    $stmt = $conn->prepare("SELECT nombre_paciente, archivo_pdf, fecha_registro FROM resultados_lab WHERE dni = ? AND codigo_acceso = ? AND activo = 1");
    $stmt->bind_param("ss", $dni, $codigo);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        // Decodificar si es JSON (multiples archivos) o string normal (antiguo)
        $archivos = json_decode($row['archivo_pdf']);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Si falla al decodificar, es un archivo único antiguo, lo convertimos a array
            $archivos = [$row['archivo_pdf']];
        }
        $row['archivos'] = $archivos; // Enviamos el array limpio
        unset($row['archivo_pdf']); // Quitamos el campo crudo por seguridad

        echo json_encode(['success'=>true, 'data'=>$row]);
    } else {
        echo json_encode(['success'=>false, 'error'=>'No se encontraron resultados o código incorrecto.']);
    }
    exit;
}

// --- SUBIR RESULTADOS MÚLTIPLES (ADMIN) ---
if ($method === 'POST') {
    // Validar archivos
    if (!isset($_FILES['pdf']) || empty($_FILES['pdf']['name'][0])) {
        echo json_encode(['success'=>false, 'error'=>'Debe seleccionar al menos un archivo']);
        exit;
    }

    $dni = $_POST['dni'];
    $nombre = $_POST['nombre'];
    $telefono = $_POST['telefono'];
    $codigo = rand(1000, 9999); // Generar código

    $dir = "uploads/resultados/";
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $rutas_guardadas = [];
    $total_files = count($_FILES['pdf']['name']);
    $errores = 0;

    // Bucle para subir cada archivo
    for ($i = 0; $i < $total_files; $i++) {
        if ($_FILES['pdf']['error'][$i] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['pdf']['name'][$i], PATHINFO_EXTENSION);
            // Nombre único: DNI_indice_uniqid.pdf
            $new_name = $dni . "_" . $i . "_" . uniqid() . "." . $ext;
            $ruta_final = $dir . $new_name;

            if (move_uploaded_file($_FILES['pdf']['tmp_name'][$i], $ruta_final)) {
                $rutas_guardadas[] = $ruta_final;
            } else {
                $errores++;
            }
        }
    }

    if (empty($rutas_guardadas)) {
        echo json_encode(['success'=>false, 'error'=>'No se pudo subir ningún archivo.']);
        exit;
    }

    // Convertir array de rutas a JSON para guardar en BD
    $json_rutas = json_encode($rutas_guardadas);

    $stmt = $conn->prepare("INSERT INTO resultados_lab (dni, nombre_paciente, telefono, codigo_acceso, archivo_pdf) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $dni, $nombre, $telefono, $codigo, $json_rutas);
    
    if ($stmt->execute()) {
        echo json_encode(['success'=>true, 'codigo'=>$codigo]); 
    } else {
        echo json_encode(['success'=>false, 'error'=>$stmt->error]);
    }
}
$conn->close();
?>