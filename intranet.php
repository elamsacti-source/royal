<?php
include_once 'session.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$rol = $_SESSION['user_role'];
$nombre = $_SESSION['user_name'];

$hora = date('G');
$saludo = ($hora < 12) ? "Buenos días" : (($hora < 18) ? "Buenas tardes" : "Buenas noches");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Intranet Corporativa | Elam Medical</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://use.fontawesome.com/releases/v6.5.1/js/all.js" crossorigin="anonymous"></script>

    <style>
        :root{
            --primary:#0d47a1;
            --primary-dark:#08306b;
            --bg:#f4f6f9;
            --text:#334155;
            --muted:#64748b;
            --card:#ffffff;
        }

        body{
            font-family:'Inter',sans-serif;
            background:var(--bg);
            color:var(--text);
        }

        /* NAVBAR */
        .navbar-custom{
            background:linear-gradient(90deg,var(--primary),var(--primary-dark));
            box-shadow:0 4px 20px rgba(0,0,0,.15);
        }

        /* Estilo para los logos en la barra */
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 15px; /* Espacio entre los dos logos */
        }

        .navbar-brand img{
            height: 40px; /* Tamaño equilibrado para dos logos */
            width: auto;
            object-fit: contain;
            /* Si los logos son blancos, se verán bien sobre el fondo azul.
               Si fueran negros, descomenta la siguiente línea para invertirlos a blanco: */
            /* filter: brightness(0) invert(1); */
        }

        .role-badge{
            background:rgba(255,255,255,.15);
            border:1px solid rgba(255,255,255,.25);
            font-size:.75rem;
            padding:4px 12px;
            border-radius:20px;
            text-transform:uppercase;
        }

        /* HEADER */
        .welcome{
            margin:40px 0 30px;
        }

        .welcome h1{
            font-size:1.6rem;
            font-weight:700;
        }

        .welcome p{
            color:var(--muted);
            margin-bottom:0;
        }

        /* SECTIONS */
        .section-title{
            font-size:.75rem;
            font-weight:700;
            letter-spacing:1.5px;
            color:#94a3b8;
            margin:40px 0 20px;
            text-transform:uppercase;
            display:flex;
            align-items:center;
        }

        .section-title::after{
            content:"";
            flex:1;
            height:1px;
            background:#e2e8f0;
            margin-left:15px;
        }

        /* MODULE CARDS */
        .module-card{
            background:var(--card);
            border-radius:14px;
            padding:28px 22px;
            text-decoration:none;
            color:inherit;
            height:100%;
            display:flex;
            flex-direction:column;
            align-items:center;
            text-align:center;
            border:1px solid #e5e7eb;
            transition:.25s;
        }

        .module-card:hover{
            transform:translateY(-4px);
            box-shadow:0 10px 25px rgba(0,0,0,.08);
            border-color:var(--primary);
        }

        .icon-box{
            width:64px;
            height:64px;
            border-radius:16px;
            background:#e3f2fd;
            color:var(--primary);
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:1.7rem;
            margin-bottom:18px;
        }

        .module-title{
            font-weight:700;
            font-size:1.05rem;
            margin-bottom:6px;
        }

        .module-desc{
            font-size:.85rem;
            color:#6b7280;
        }

        /* FOOTER */
        footer{
            margin-top:60px;
            text-align:center;
            font-size:.8rem;
            color:#94a3b8;
            padding-bottom: 30px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark navbar-custom">
    <div class="container">
        <a class="navbar-brand" href="#">
            <img src="imagenes/logoIntegra.png" alt="Integra Salud">
            <div style="height: 25px; width: 1px; background: rgba(255,255,255,0.3);"></div>
            <img src="imagenes/logoLosAngeles.png" alt="Clínica Los Ángeles">
        </a>

        <div class="d-flex align-items-center gap-3 text-white">
            <div class="text-end d-none d-md-block">
                <div class="fw-semibold"><?php echo htmlspecialchars($nombre); ?></div>
                <span class="role-badge"><?php echo strtoupper($rol); ?></span>
            </div>
            <a href="api_logout.php" class="btn btn-sm btn-light fw-semibold rounded-pill">
                <i class="fas fa-sign-out-alt me-1"></i> Salir
            </a>
        </div>
    </div>
</nav>

<div class="container">

    <div class="welcome">
        <h1><?php echo $saludo; ?>, <?php echo explode(' ', $nombre)[0]; ?></h1>
        <p>Bienvenido al sistema interno corporativo</p>
    </div>

    <div class="section-title">Herramientas Operativas</div>

    <div class="row g-4">
        <div class="col-md-6 col-lg-3">
            <a href="panel_citas.php" class="module-card">
                <div class="icon-box"><i class="fas fa-calendar-check"></i></div>
                <div class="module-title">Citas Médicas</div>
                <div class="module-desc">Gestión y programación de citas.</div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="modulo_hcl.php" class="module-card">
                <div class="icon-box"><i class="fas fa-file-medical"></i></div>
                <div class="module-title">Historia Clínica</div>
                <div class="module-desc">Timeline y registro de pacientes.</div>
            </a>
        </div>

        <div class="col-md-6 col-lg-3">
            <a href="registro_resultados.php" class="module-card">
                <div class="icon-box"><i class="fas fa-microscope"></i></div>
                <div class="module-title">Laboratorio</div>
                <div class="module-desc">Carga y envío de resultados.</div>
            </a>
        </div>

        <?php if($rol === 'admin' || isset($_SESSION['supervisor_id'])): ?>
        <div class="col-md-6 col-lg-3">
            <a href="checklist.php" class="module-card">
                <div class="icon-box"><i class="fas fa-clipboard-list"></i></div>
                <div class="module-title">Gestión de Calidad</div>
                <div class="module-desc">Control operativo y calidad.</div>
            </a>
        </div>
        <?php endif; ?>

        <div class="col-md-6 col-lg-3">
            <a href="ver_horario.php" class="module-card">
                <div class="icon-box"><i class="far fa-clock"></i></div>
                <div class="module-title">Horarios</div>
                <div class="module-desc">Consulta de turnos y rotación.</div>
            </a>
        </div>
    </div>

    <?php if($rol === 'admin'): ?>
        <div class="section-title">Administración</div>

        <div class="row g-4">
            
            <div class="col-md-6 col-lg-3">
                <a href="admin_citas.php" class="module-card">
                    <div class="icon-box"><i class="fas fa-user-md"></i></div>
                    <div class="module-title">Panel de Control</div>
                    <div class="module-desc">Usuarios, médicos y sedes.</div>
                </a>
            </div>

            <div class="col-md-6 col-lg-3">
                <a href="admin.php" class="module-card">
                    <div class="icon-box"><i class="fas fa-cogs"></i></div>
                    <div class="module-title">Configuración Gestión de Calidad</div>
                    <div class="module-desc">Plantillas y supervisores.</div>
                </a>
            </div>

            <div class="col-md-6 col-lg-3">
                <a href="admin_inventario.php" class="module-card">
                    <div class="icon-box"><i class="fas fa-boxes-stacked"></i></div>
                    <div class="module-title">Farmacia & Stock</div>
                    <div class="module-desc">Inventario Zeth, Costos y Auditoría.</div>
                </a>
            </div>

            <div class="col-md-6 col-lg-3">
                <a href="reporte.php" class="module-card">
                    <div class="icon-box"><i class="fas fa-chart-pie"></i></div>
                    <div class="module-title">Reportes Check</div>
                    <div class="module-desc">Estadísticas de cumplimiento.</div>
                </a>
            </div>
            
            <div class="col-md-6 col-lg-3">
                <a href="lab_panel.php" class="module-card">
                    <div class="icon-box"><i class="fas fa-vial"></i></div>
                    <div class="module-title">Gestión Lab</div>
                    <div class="module-desc">Editar y borrar resultados.</div>
                </a>
            </div>
            
            <div class="col-md-6 col-lg-3">
                <a href="tramites.php" class="module-card">
                    <div class="icon-box"><i class="fas fa-folder-open"></i></div>
                    <div class="module-title">Trámites</div>
                    <div class="module-desc">Documentación y licencias.</div>
                </a>
            </div>

        </div>
    <?php endif; ?>

    <footer>
        &copy; 2025 Elam Medical del Norte — Sistema Interno
    </footer>

</div>

</body>
</html>