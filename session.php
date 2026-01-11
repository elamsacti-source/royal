<?php
// Configurar para que la cookie de sesión se destruya al cerrar el navegador
ini_set('session.cookie_lifetime', 0);
ini_set('session.use_strict_mode', 1);

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0, // Muere al cerrar el navegador
        'path' => '/',
        'domain' => '', 
        'secure' => false, // Cambiar a true si usas HTTPS (recomendado en producción)
        'httponly' => true
    ]);
    session_start();
}

// Configurar Zona Horaria
date_default_timezone_set('America/Lima');

// --- ANTI-CACHÉ (OBLIGA A RECARGAR AL DAR ATRÁS) ---
// Estas cabeceras le dicen al navegador: "Nunca guardes una copia de esto".
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Fecha en el pasado
?>