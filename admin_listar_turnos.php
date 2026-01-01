<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
include 'db_config.php';

$sql = "SELECT p.id, 
               p.fecha, 
               p.turno, 
               p.hora_inicio, 
               p.hora_fin, 
               u.nombre_completo as medico, 
               s.nombre as sede
        FROM programacion p
        JOIN usuarios u ON p.usuario_id = u.id
        JOIN sedes s ON p.sede_id = s.id
        ORDER BY p.fecha DESC, p.hora_inicio ASC";

$result = $conn->query($sql);

$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Si tiene turno asignado (texto), lo usamos. Si no, mostramos las horas.
        if (!empty($row['turno'])) {
            $row['horario_mostrar'] = $row['turno'];
        } else {
            // Fallback para registros antiguos
            $row['horario_mostrar'] = substr($row['hora_inicio'], 0, 5) . ' - ' . substr($row['hora_fin'], 0, 5);
        }
        $data[] = $row;
    }
}

echo json_encode($data);
$conn->close();
?>