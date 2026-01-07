<?php
// Credenciales extraídas de tu panel de InfinityFree
$host = 'sql310.infinityfree.com';
$db   = 'if0_40786255_suarcorp';
$user = 'if0_40786255';
$pass = 'MesaIGS22a';
$port = '3306'; 

try {
    // Hemos agregado el puerto al DSN por seguridad, ya que tu host lo especifica
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8;port=$port";
    
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    // En producción, evita mostrar el error completo al usuario final, 
    // pero para desarrollo es útil ver qué falló.
    die("Error de conexión con la Base de Datos: " . $e->getMessage());
}
?>