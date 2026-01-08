<?php
// =======================================================
// CONFIGURACIÓN DE BASE DE DATOS - CLINICA LOS ANGELES
// =======================================================

// 1. HOST: En el 99% de los hostings de pago (como el tuyo), esto es 'localhost'
define('DB_HOST', 'sql102.infinityfree.com');

// 2. USUARIO: El usuario que creaste en tu panel de hosting (MySQL Users)
// OJO: No es tu usuario de InfinityFree. Es uno nuevo.
define('DB_USER', 'if0_40513845'); 

// 3. CONTRASEÑA: La contraseña que le asignaste a ese usuario
define('DB_PASS', 'MesaIGS22a');

// 4. NOMBRE BD: El nombre de la base de datos que creaste
define('DB_NAME', 'if0_40513845_citas');      

// =======================================================
// NO TOQUES NADA DEBAJO DE ESTA LÍNEA
// =======================================================

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    // Esto mostrará el error exacto en pantalla si falla
    die("Error Fatal de Conexión: " . $conn->connect_error); 
}

$conn->query("SET time_zone = '-05:00'");
$conn->set_charset("utf8mb4");
?>