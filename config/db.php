<?php
// config/db.php
$host = 'sql310.infinityfree.com';
$db   = 'if0_40786255_royal';
$user = 'if0_40786255';
$pass = 'MesaIGS22a'; 
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // En producción, evita mostrar el error real por seguridad
    die("Error de conexión: " . $e->getMessage());
}
?>