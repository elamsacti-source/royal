<?php
header('Content-Type: application/json');
include 'db_config.php';

$term = $_GET['q'] ?? '';

if (strlen($term) < 3) { echo json_encode([]); exit; }

$term = "%$term%";

// Buscamos en la tabla maestra de PACIENTES
$sql = "SELECT * FROM pacientes 
        WHERE (nombres LIKE ? OR apellidos LIKE ? OR dni LIKE ? OR historia_clinica LIKE ?)
        ORDER BY apellidos ASC 
        LIMIT 15";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $term, $term, $term, $term);
$stmt->execute();
$res = $stmt->get_result();

$pacientes = [];
while($row = $res->fetch_assoc()) {
    $pacientes[] = $row;
}

echo json_encode($pacientes);
$conn->close();
?>