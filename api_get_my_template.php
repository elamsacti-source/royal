<?php
// Evitar que advertencias de PHP rompan el JSON
error_reporting(0); 
ini_set('display_errors', 0);

include 'db_config.php';
header('Content-Type: application/json');

$sql = "SELECT a.id AS area_id, a.codigo, a.nombre AS area_nombre, a.emoji, 
               act.id AS activity_id, act.nombre AS activity_nombre, act.criterio, 
               act.frecuencia, act.requires_quantity, act.fecha_inicio, act.specific_date 
        FROM checklist_areas a 
        LEFT JOIN checklist_activities act ON a.id = act.area_id AND act.activo = 1 
        ORDER BY a.orden, act.orden";

$result = $conn->query($sql);

// Si la consulta falla, devolver array vacio en vez de error
if (!$result) {
    echo json_encode([]);
    exit;
}

$areas = [];

while($row = $result->fetch_assoc()) {
    $aid = $row['area_id'];
    if (!isset($areas[$aid])) {
        $areas[$aid] = [
            'id' => $aid, 'codigo' => $row['codigo'], 
            'nombre' => $row['area_nombre'], 'icon' => $row['emoji'] ?? '📋', 
            'tag' => 'Área', 'actividades' => []
        ];
    }
    if ($row['activity_id']) {
        $areas[$aid]['actividades'][] = [
            'id' => $row['activity_id'],
            'nombre' => $row['activity_nombre'],
            'criterio' => $row['criterio'],
            'frecuencia' => $row['frecuencia'],
            'requires_quantity' => (bool)$row['requires_quantity'],
            'fecha_inicio' => $row['fecha_inicio'],
            'specific_date' => $row['specific_date']
        ];
    }
}
echo json_encode(array_values($areas));
$conn->close();
?>