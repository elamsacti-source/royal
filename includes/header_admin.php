<?php
// 1. INICIO DE SESIÓN Y SEGURIDAD
// Evitar conflicto si la sesión ya fue iniciada en otro archivo
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    // Si no hay sesión, redireccionar al login (ajusta la ruta si es necesario)
    header("Location: /index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Royal Licorería - Admin</title>
    
    <link rel="stylesheet" href="/assets/css/estilos.css?v=<?php echo time(); ?>">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body>

<div class="mobile-nav-toggle">
    <div class="mobile-logo">
        ROYAL <i class="fa-solid fa-wine-bottle" style="color:var(--royal-gold); margin-left: 5px;"></i>
    </div>
    <button class="hamburger" onclick="toggleMenu()">
        <i class="fa-solid fa-bars"></i>
    </button>
</div>

<div class="overlay" id="overlay" onclick="toggleMenu()"></div>

<nav class="sidebar" id="sidebar">
    <div class="brand">
        <h2>ROYAL <i class="fa-solid fa-wine-bottle"></i></h2>
        <small style="display:block; font-size:12px; color:#666; margin-top:5px;">
            Hola, <?= isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Admin' ?>
        </small>
    </div>
    
    <div class="menu">
        <a href="/modulos/admin/dashboard.php">
            <i class="fa-solid fa-chart-pie"></i> Dashboard
        </a>
        
        <a href="/modulos/admin/productos_nuevo.php">
            <i class="fa-solid fa-plus-circle"></i> Nuevo Producto
        </a>
        
        <a href="/modulos/admin/productos_lista.php">
            <i class="fa-solid fa-boxes-stacked"></i> Inventario Global
        </a>
        
        <a href="/modulos/admin/crear_combo.php">
            <i class="fa-solid fa-gift"></i> Crear Pack
        </a>

        <a href="/modulos/admin/cargos_descargos.php">
            <i class="fa-solid fa-right-left"></i> Cargos y Descargos
        </a>

        <a href="/modulos/admin/sedes.php">
            <i class="fa-solid fa-store"></i> Gestionar Sedes
        </a>

        <a href="/modulos/admin/usuarios.php">
            <i class="fa-solid fa-users-gear"></i> Usuarios y Permisos
        </a>

        <a href="/modulos/admin/kardex.php">
            <i class="fa-solid fa-list-check"></i> Reporte Kardex
        </a>
        
        <br>
        
        <a href="/logout.php" style="color: #ef5350; border: 1px solid #ef5350; margin-top:auto;">
            <i class="fa-solid fa-power-off"></i> Cerrar Sesión
        </a>
    </div>
</nav>

<main class="main-content">

<script>
    // Función para abrir/cerrar menú en celular
    function toggleMenu() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    }

    // Script para resaltar automáticamente la página actual en el menú
    const currentPath = window.location.pathname;
    document.querySelectorAll('.menu a').forEach(link => {
        if(link.getAttribute('href') === currentPath) {
            link.style.background = '#1a1a1a';
            link.style.color = '#fff';
            link.style.borderLeft = '4px solid var(--royal-gold)';
        }
    });
</script>