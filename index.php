<?php 
include_once 'session.php'; 

// Lógica inteligente: No redirigir, sino detectar estado
$is_logged = isset($_SESSION['user_id']);
$dashboard_url = ($is_logged && ($_SESSION['user_role'] ?? '') === 'admin') ? 'admin.php' : 'checklist.php';
$user_name = $_SESSION['user_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Clínica Los Ángeles - Edición Navidad 🎄</title>
    <link rel="icon" type="image/x-icon" href="imagenes/icon.png" />
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,600,700" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Mountains+of+Christmas:wght@700&display=swap" rel="stylesheet">
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    
    <style>
        /* --- ESTILOS GENERALES Y NAVIDEÑOS --- */
        :root {
            --xmas-red: #D32F2F; --xmas-dark-red: #B71C1C; --xmas-green: #2E7D32;
            --xmas-gold: #FFD700; --snow-white: #f8f9fa; --fb-color: #1877F2; --tt-color: #000000;
        }
        body { font-family: 'Roboto', sans-serif; background-color: var(--snow-white); overflow-x: hidden; padding-top: 0; }
        h1, h2, h3, h4, h5 { font-family: 'Montserrat', sans-serif; color: var(--xmas-red); }
        .merry-font { font-family: 'Mountains of Christmas', cursive; font-weight: bold; }
        
        /* NAVBAR PERSONALIZADA */
        .custom-navbar { background: linear-gradient(90deg, var(--xmas-red) 0%, var(--xmas-dark-red) 100%); padding: 8px 0; border-bottom: 3px solid var(--xmas-gold); position: relative; z-index: 1000; }
        
        /* LOGO CENTRADO */
        .brand-center { display: flex; align-items: center; gap: 20px; white-space: nowrap; text-decoration: none; justify-content: center; }
        .logo-img { filter: brightness(0) invert(1); transition: transform 0.3s; width: auto; }
        .logo-integra { height: 65px; } 
        .logo-angeles { height: 55px; }

        /* --- ELEMENTOS DE ESCRITORIO --- */
        .desktop-btn-container { display: flex; align-items: center; gap: 8px; width: 220px; }
        .justify-end { justify-content: flex-end; }
        
        /* Botón Login/Panel (Derecha) */
        .btn-panel { background: rgba(255,255,255,0.2); color: white; border: 1px solid white; font-weight: 600; font-size: 0.9rem; text-decoration: none; padding: 8px 16px; border-radius: 25px; transition: 0.3s; white-space: nowrap; }
        .btn-panel:hover { background: white; color: var(--xmas-red); }
        .btn-logout-nav { background: #B71C1C; color: white; border: 1px solid #ff9999; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: 0.3s; }
        .btn-logout-nav:hover { background: red; }
        
        /* Botones de Navegación (Izquierda) */
        .btn-nav-custom { 
            border: 1px solid var(--xmas-gold); 
            color: var(--xmas-gold) !important; 
            font-weight: 600; text-decoration: none; 
            padding: 6px 14px; border-radius: 20px; 
            transition: 0.3s; display: inline-flex; 
            align-items: center; gap: 6px; 
            background: rgba(0,0,0,0.2); 
            white-space: nowrap;
            font-size: 0.85rem;
        }
        .btn-nav-custom:hover { background-color: var(--xmas-gold); color: #B71C1C !important; transform: translateY(-2px); }

        /* --- BOTÓN HAMBURGUESA (MÓVIL) --- */
        .navbar-toggler { border: 1px solid var(--xmas-gold); padding: 4px 8px; margin-left: auto; }
        .navbar-toggler:focus { box-shadow: none; outline: 2px solid var(--xmas-gold); }
        .navbar-toggler-icon { background-image: url("data:image/svg+xml;charset=utf8,%3Csvg viewBox='0 0 30 30' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath stroke='rgba(255, 215, 0, 1)' stroke-width='2' stroke-linecap='round' stroke-miterlimit='10' d='M4 7h22M4 15h22M4 23h22'/%3E%3C/svg%3E"); }

        /* --- MENÚ DESPLEGABLE MÓVIL --- */
        .mobile-nav-box { background-color: rgba(0, 0, 0, 0.2); border-radius: 10px; padding: 10px; margin-top: 10px; border: 1px solid rgba(255,255,255,0.1); }
        .nav-link-mobile { display: block; color: white; font-weight: bold; padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.15); text-decoration: none; transition: 0.3s; font-size: 1rem; text-align: left; }
        .nav-link-mobile:hover { background-color: rgba(255,255,255,0.1); color: var(--xmas-gold); border-radius: 5px; }
        .nav-link-mobile:last-child { border-bottom: none; }
        .nav-link-mobile i { margin-right: 10px; width: 20px; text-align: center; }

        /* --- CONTROL RESPONSIVE (MEDIA QUERIES ESTRICTOS) --- */
        @media (min-width: 992px) {
            #navbarMobileMenu { display: none !important; }
            .navbar-toggler { display: none !important; }
            .desktop-only { display: flex !important; }
            .brand-center { position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); margin: 0; }
            .navbar-container { position: relative; height: 60px; display: flex; align-items: center; justify-content: space-between; width: 100%; }
        }

        @media (max-width: 991px) {
            .desktop-only { display: none !important; }
            .navbar-container { display: flex; align-items: center; justify-content: flex-end; width: 100%; position: relative; min-height: 50px; }
            .brand-center { position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); gap: 8px; width: auto; margin: 0; }
            .logo-integra { height: 32px; } 
            .logo-angeles { height: 28px; }
            .custom-navbar { padding: 5px 0; }
            .navbar-toggler { padding: 3px 6px; font-size: 0.9rem; z-index: 1001; }
            .navbar-toggler-icon { width: 22px; height: 22px; }
            .carousel-item { height: auto !important; min-height: auto !important; }
            .carousel-item img { height: auto !important; max-height: 80vh; object-fit: contain !important; }
        }

        /* --- OTROS ESTILOS --- */
        .snow-container { position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 9999; overflow: hidden; }
        .snowflake { color: #fff; font-size: 1em; font-family: Arial, sans-serif; text-shadow: 0 0 5px #000; position: fixed; top: -10%; z-index: 9999; animation: snowflakes-fall 10s linear infinite, snowflakes-shake 3s ease-in-out infinite; }
        @keyframes snowflakes-fall { 0% { top: -10%; } 100% { top: 100%; } }
        @keyframes snowflakes-shake { 0%, 100% { transform: translateX(0); } 50% { transform: translateX(80px); } }
        .snowflake:nth-of-type(0) { left: 1%; animation-delay: 0s, 0s; } .snowflake:nth-of-type(9) { left: 90%; animation-delay: 3s, 1.5s; }

        .carousel-item { height: 85vh; min-height: 500px; position: relative; background-color: #000; }
        .carousel-item img { width: 100%; height: 100%; object-fit: cover; }
        
        .service-card { border: none; border-radius: 15px; overflow: hidden; background: #fff; box-shadow: 0 10px 25px rgba(0,0,0,0.08); height: 100%; display: flex; flex-direction: column; }
        .img-container { width: 100%; height: auto; min-height: 400px; display: flex; align-items: center; justify-content: center; background: #fff; padding: 10px; }
        .service-card img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .card-overlay-btn { margin: 15px auto 20px; width: 80%; animation: pulseBtn 2s infinite; border: 2px solid white; font-weight: bold; text-transform: uppercase; }
        
        /* --- AJUSTE DE TAMAÑO VIDEO FACEBOOK --- */
        .video-card { 
            border-radius: 15px; overflow: hidden; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); 
            background: #000; height: 100%; 
            max-width: 280px;
            margin: 0 auto; 
        }
        .fb-video-container { position: relative; padding-bottom: 177.77%; height: 0; overflow: hidden; background: #000; }
        .fb-video-container iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0; }

        .modal-header-xmas { background: linear-gradient(90deg, var(--xmas-red), var(--xmas-dark-red)); color: white; }
        .btn-wsp-sede { display: flex; align-items: center; justify-content: center; gap: 10px; font-size: 1rem; padding: 12px; border-radius: 8px; margin-bottom: 8px; color: white; font-weight: bold; border: none; width: 100%; transition: 0.3s; }
        .btn-wsp-sede:hover { opacity: 0.9; transform: translateY(-2px); color: white; }
        .btn-huacho { background-color: #25D366; } .btn-huaura { background-color: #128C7E; } .btn-medio { background-color: #075E54; }
        
        footer { background-color: #1b5e20; border-top: 5px solid var(--xmas-red); }
        .location-box { background: #fff; padding: 20px; border-radius: 15px; height: 100%; box-shadow: 0 5px 15px rgba(0,0,0,0.05); transition: transform 0.3s; }
        .location-box:hover { transform: translateY(-5px); }
        .whatsapp-float { position: fixed; width: 60px; height: 60px; bottom: 25px; right: 25px; background-color: #25d366; color: #FFF; border-radius: 50%; font-size: 30px; box-shadow: 0 4px 10px rgba(0,0,0,0.3); z-index: 1000; display: flex; align-items: center; justify-content: center; text-decoration: none; border: 2px solid white; }
        
        .map-container { width: 100%; height: 250px; border-radius: 10px; overflow: hidden; margin-bottom: 15px; border: 1px solid #ddd; }
        .nav-pills .nav-link { color: var(--xmas-red); font-weight: bold; }
        .nav-pills .nav-link.active { background-color: var(--xmas-green); color: white; }
        
        .social-btn-footer { transition: transform 0.3s, background-color 0.3s; border: 2px solid rgba(255,255,255,0.3); }
        .social-btn-footer:hover { transform: scale(1.1) rotate(10deg); background-color: rgba(255,255,255,0.2); }
        #socialButtonsContainer { display: flex; flex-direction: column; gap: 12px; padding: 5px; }
        .social-card { display: flex; align-items: center; padding: 15px 20px; background: #fff; border: 1px solid #eee; border-radius: 15px; text-decoration: none; color: #444; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); box-shadow: 0 5px 15px rgba(0,0,0,0.05); opacity: 0; transform: translateX(-20px); animation: slideInRight 0.5s forwards; }
        .sede-icon { width: 45px; height: 45px; border-radius: 50%; background: #f0f2f5; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: #666; margin-right: 15px; transition: all 0.3s; }
        .social-text { flex-grow: 1; display: flex; flex-direction: column; }
        .arrow-icon { opacity: 0; transform: translateX(-10px); transition: all 0.3s; color: inherit; }
        .social-fb:hover { background-color: var(--fb-color); color:white; transform: translateX(5px); }
        .social-fb:hover .sede-icon { background: rgba(255,255,255,0.2); color: white; }
        .social-fb:hover .arrow-icon { opacity: 1; transform: translateX(0); }
        @keyframes slideInRight { to { opacity: 1; transform: translateX(0); } }

        /* Estilos tarjeta Ánfora */
        .anfora-card { background: rgba(255,255,255,0.95); border-radius: 12px; padding: 12px; display: flex; align-items: center; gap: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); margin-bottom: 12px; transition: 0.3s; border-left: 5px solid; text-decoration: none; }
        .anfora-card:hover { transform: translateX(5px); background: #fff; text-decoration: none; }
        .anfora-huacho { border-left-color: #D32F2F; }
        .anfora-huaura { border-left-color: #2E7D32; }
        .anfora-medio { border-left-color: #FFD700; }
    </style>
</head>

<body>
    <div class="snow-container" aria-hidden="true">
        <div class="snowflake">❅</div><div class="snowflake">❆</div><div class="snowflake">❅</div><div class="snowflake">❆</div><div class="snowflake">❅</div><div class="snowflake">❆</div><div class="snowflake">❅</div><div class="snowflake">❆</div><div class="snowflake">❅</div><div class="snowflake">❆</div>
    </div>

    <nav class="navbar custom-navbar sticky-top shadow-sm">
        <div class="container-fluid" style="max-width: 1400px;">
            <div class="navbar-container">
                
                <div class="desktop-btn-container desktop-only">
                    <a class="btn-nav-custom" href="resultados.php" title="Consultar Resultados de Laboratorio">
                        <i class="fas fa-file-medical-alt"></i> <span class="d-none d-xxl-inline">Resultados</span>
                    </a>
                </div>

                <a class="brand-center" href="index.php">
                    <img src="imagenes/logoIntegra.png" alt="Integra Salud" class="logo-img logo-integra">
                    <img src="imagenes/logoLosAngeles.png" alt="Clínica Los Ángeles" class="logo-img logo-angeles">
                </a>

                <div class="desktop-btn-container desktop-only justify-end">
                    <?php if ($is_logged): ?>
                        <a href="<?php echo $dashboard_url; ?>" class="btn-panel me-2">
                            <i class="fas fa-user-md me-1"></i> Mi Panel
                        </a>
                        <a href="api_logout.php" class="btn-logout-nav" title="Cerrar Sesión">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn-panel">
                            <i class="fas fa-user-circle me-1"></i> Intranet
                        </a>
                    <?php endif; ?>
                </div>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMobileMenu" aria-controls="navbarMobileMenu" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>

            <div class="collapse navbar-collapse" id="navbarMobileMenu">
                <div class="mobile-nav-box">
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link-mobile" href="resultados.php">
                                <i class="fas fa-file-medical-alt text-warning"></i> Consultar Resultados
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <?php if ($is_logged): ?>
                                <a class="nav-link-mobile" href="<?php echo $dashboard_url; ?>">
                                    <i class="fas fa-user-md text-success"></i> Entrar a Intranet
                                </a>
                                <a class="nav-link-mobile text-danger" href="api_logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                                </a>
                            <?php else: ?>
                                <a class="nav-link-mobile" href="login.php">
                                    <i class="fas fa-user-circle text-white"></i> Acceso Intranet
                                </a>
                            <?php endif; ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <header>
        <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></button>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
            </div>
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <img src="imagenes/car1.jpg" class="d-none d-md-block" alt="Navidad PC">
                    <img src="imagenes/cel1.jpg" class="d-block d-md-none" alt="Navidad Celular">
                </div>
                <div class="carousel-item">
                    <img src="imagenes/car2.jpg" class="d-none d-md-block" alt="Salud PC">
                    <img src="imagenes/cel2.jpg" class="d-block d-md-none" alt="Salud Celular">
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
            <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
        </div>
    </header>

    <section class="py-5" style="background: linear-gradient(135deg, #D32F2F 0%, #a00000 100%); color: white !important; border-bottom: 4px solid #FFD700;">
        <div class="container">
            <div class="row align-items-center">
                
                <div class="col-lg-5 mb-4 mb-lg-0 text-center">
                    <div style="border: 4px solid #FFD700; border-radius: 20px; overflow: hidden; box-shadow: 0 15px 40px rgba(0,0,0,0.4); background: #000; max-width: 320px; margin: 0 auto;">
                        <video style="width: 100%; height: auto; display: block;" 
                               controls playsinline preload="auto">
                            <source src="imagenes/video_promo.mp4" type="video/mp4">
                            Tu navegador no soporta videos HTML5.
                        </video>
                    </div>
                    <p class="small mt-2 opacity-75 text-center text-white"><i class="fas fa-play-circle me-1"></i> Mira los premios del sorteo</p>
                </div>

                <div class="col-lg-7 text-white">
                    <h2 class="fw-bold display-5 merry-font mb-2 text-center text-lg-start" style="color: #ffffff !important;">🎁 ¡Gran Sorteo Navideño! 🎄</h2>
                    <p class="lead opacity-75 mb-4 text-center text-lg-start text-white">Sigue los pasos para participar:</p>

                    <div class="mb-4">
                        <h4 class="h5 fw-bold text-warning mb-3"><i class="fas fa-thumbs-up me-2"></i>1. Síguenos en redes:</h4>
                        <div class="d-flex gap-2 flex-wrap justify-content-center justify-content-lg-start">
                            <button onclick="openSocial('facebook')" class="btn btn-light rounded-pill px-3 fw-bold text-primary">
                                <i class="fab fa-facebook me-2"></i>Facebook
                            </button>
                            <button onclick="openSocial('instagram')" class="btn btn-light rounded-pill px-3 fw-bold text-danger">
                                <i class="fab fa-instagram me-2"></i>Instagram
                            </button>
                            <button onclick="openSocial('tiktok')" class="btn btn-light rounded-pill px-3 fw-bold text-dark">
                                <i class="fab fa-tiktok me-2"></i>TikTok
                            </button>
                        </div>
                    </div>

                    <div>
                        <h4 class="h5 fw-bold text-warning mb-3"><i class="fas fa-envelope-open-text me-2"></i>2. Deposita tu ticket en las Ánforas:</h4>
                        
                        <div class="anfora-card anfora-huacho">
                            <div class="text-danger fs-3"><i class="fas fa-clinic-medical"></i></div>
                            <div class="flex-grow-1 text-dark">
                                <strong class="d-block text-uppercase">Sede Huacho</strong>
                                <small>Jr. José Arambulo la Rosa N° 156</small>
                            </div>
                            <button onclick="openMapTab('pills-huacho-tab')" class="btn btn-sm btn-outline-danger rounded-pill fw-bold" style="white-space: nowrap;">
                                <i class="fas fa-map-marked-alt me-1"></i>Ver Mapa
                            </button>
                        </div>

                        <div class="anfora-card anfora-huaura">
                            <div class="text-success fs-3"><i class="fas fa-hospital"></i></div>
                            <div class="flex-grow-1 text-dark">
                                <strong class="d-block text-uppercase">Sede Huaura</strong>
                                <small>Av. San Martin 392</small>
                            </div>
                            <button onclick="openMapTab('pills-huaura-tab')" class="btn btn-sm btn-outline-success rounded-pill fw-bold" style="white-space: nowrap;">
                                <i class="fas fa-map-marked-alt me-1"></i>Ver Mapa
                            </button>
                        </div>

                        <div class="anfora-card anfora-medio">
                            <div class="text-warning fs-3"><i class="fas fa-star"></i></div>
                            <div class="flex-grow-1 text-dark">
                                <strong class="d-block text-uppercase">Sede Medio Mundo</strong>
                                <small>Av. Ezequiel Gago Mz. H Lt. 19 "B"</small>
                            </div>
                            <button onclick="openMapTab('pills-medio-tab')" class="btn btn-sm btn-outline-warning rounded-pill fw-bold text-dark" style="white-space: nowrap;">
                                <i class="fas fa-map-marked-alt me-1"></i>Ver Mapa
                            </button>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </section>

    <section class="py-5 bg-white">
        <div class="container">
            <div class="text-center mb-5">
                <span class="badge bg-danger px-3 py-2 rounded-pill mb-2">🎁 Especial Diciembre</span>
                <h2 class="fw-bold display-6 merry-font">Esta Navidad, Regala Salud</h2>
                <div style="width: 80px; height: 4px; background-color: var(--xmas-green); margin: 15px auto;"></div>
                <p class="text-muted col-lg-8 mx-auto lead">Aprovecha nuestros descuentos exclusivos para cuidar a quienes más amas.</p>
            </div>

            <div class="row g-4">
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="service-card h-100">
                        <div class="img-container"><img src="imagenes/oferta1.jpg" alt="Paquete Adulto Mayor"></div>
                        <button type="button" class="btn btn-danger rounded-pill card-overlay-btn shadow" onclick="abrirModalOferta('Paquete Adulto Mayor - S/50')">Ver Oferta</button>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="service-card h-100">
                        <div class="img-container"><img src="imagenes/oferta2.jpg" alt="Medicina General"></div>
                        <button type="button" class="btn btn-success rounded-pill card-overlay-btn shadow" onclick="abrirModalOferta('Consulta Medicina General - Dr. Molina')">Ver Oferta</button>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="service-card h-100">
                        <div class="img-container"><img src="imagenes/oferta3.jpg" alt="Paquetes Oncológicos"></div>
                        <button type="button" class="btn btn-danger rounded-pill card-overlay-btn shadow" onclick="abrirModalOferta('Paquetes Oncológicos - 40% Dscto')">Ver Oferta</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5" style="background-color: #fcfcfc;">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold display-6 merry-font" style="color: var(--xmas-dark-red);">Conoce más de Nosotros</h2>
                <div style="width: 60px; height: 3px; background-color: var(--xmas-gold); margin: 10px auto;"></div>
                <p class="text-muted">Descubre nuestras instalaciones y servicios.</p>
            </div>

            <div class="row g-4 justify-content-center">
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="video-card">
                        <div class="fb-video-container"><iframe src="https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2Freel%2F1301332364773613&show_text=0&width=300" scrolling="no" allowfullscreen="true" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"></iframe></div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="video-card">
                        <div class="fb-video-container"><iframe src="https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2Freel%2F1271378437499615&show_text=0&width=300" scrolling="no" allowfullscreen="true" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"></iframe></div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="video-card">
                        <div class="fb-video-container"><iframe src="https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2Freel%2F4215522392100757&show_text=0&width=300" scrolling="no" allowfullscreen="true" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"></iframe></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5" style="background-color: #f8fdf8;">
        <div class="container">
            <div class="row mb-4 align-items-end">
                <div class="col-md-8">
                    <h2 class="fw-bold mb-0 merry-font">Nuestras Sedes</h2>
                    <p class="text-muted mb-0">Te esperamos con espíritu navideño.</p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <button type="button" class="btn btn-outline-success rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalMapas">
                        <i class="fas fa-map-marked-alt me-2"></i>Consultar Ubicación
                    </button>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="location-box">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-danger text-white rounded-circle p-2 me-3"><i class="fas fa-map-marker-alt"></i></div>
                            <h5 class="fw-bold mb-0">Huacho</h5>
                        </div>
                        <p class="text-muted small mb-0">Policlínico Integra Salud<br>Jr. José Arambulo la Rosa N° 156</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="location-box">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-success text-white rounded-circle p-2 me-3"><i class="fas fa-tree"></i></div>
                            <h5 class="fw-bold mb-0">Huaura</h5>
                        </div>
                        <p class="text-muted small mb-0">Clínica Los Ángeles<br>Av. San Martin 392</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="location-box">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-danger text-white rounded-circle p-2 me-3"><i class="fas fa-star"></i></div>
                            <h5 class="fw-bold mb-0">Medio Mundo</h5>
                        </div>
                        <p class="text-muted small mb-0">Clínica Los Ángeles<br>Av. Ezequiel Gago Mz. H Lt. 19 "B"</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="modalSedes" tabindex="-1" aria-labelledby="modalSedesLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header modal-header-xmas">
                    <h5 class="modal-title fw-bold" id="modalSedesLabel">🎄 Reclamar Oferta</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted text-center mb-3">Completa tus datos y elige la sede para contactarnos por WhatsApp:</p>
                    <form id="formOferta">
                        <input type="hidden" id="nombreOferta">
                        <div class="mb-3">
                            <label for="leadNombre" class="form-label small fw-bold">Nombres y Apellidos</label>
                            <input type="text" class="form-control" id="leadNombre" placeholder="Ej: Juan Pérez" required>
                        </div>
                        <div class="mb-3">
                            <label for="leadDni" class="form-label small fw-bold">DNI</label>
                            <input type="tel" class="form-control" id="leadDni" placeholder="Ej: 12345678" maxlength="8" required>
                        </div>
                        <div class="mb-3">
                            <label for="leadTelefono" class="form-label small fw-bold">Teléfono</label>
                            <input type="tel" class="form-control" id="leadTelefono" placeholder="Ej: 999999999" maxlength="9" required>
                        </div>
                        <hr class="my-4">
                        <p class="text-center fw-bold text-danger">Elige la sede para enviar:</p>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-wsp-sede btn-huacho" onclick="enviarWsp('970826071', 'Huacho')"><i class="fab fa-whatsapp fa-lg"></i> Sede Huacho</button>
                            <button type="button" class="btn btn-wsp-sede btn-huaura" onclick="enviarWsp('997670532', 'Huaura')"><i class="fab fa-whatsapp fa-lg"></i> Sede Huaura</button>
                            <button type="button" class="btn btn-wsp-sede btn-medio" onclick="enviarWsp('940599312', 'Medio Mundo')"><i class="fab fa-whatsapp fa-lg"></i> Sede Medio Mundo</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalMapas" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header modal-header-xmas">
                    <h5 class="modal-title fw-bold text-white"><i class="fas fa-map-marked-alt me-2"></i>Nuestras Ubicaciones</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <ul class="nav nav-pills mb-3 justify-content-center" id="pills-tab" role="tablist">
                        <li class="nav-item"><button class="nav-link active rounded-pill" id="pills-huacho-tab" data-bs-toggle="pill" data-bs-target="#pills-huacho" type="button"><i class="fas fa-clinic-medical me-2"></i>Huacho</button></li>
                        <li class="nav-item"><button class="nav-link rounded-pill" id="pills-huaura-tab" data-bs-toggle="pill" data-bs-target="#pills-huaura" type="button"><i class="fas fa-hospital me-2"></i>Huaura</button></li>
                        <li class="nav-item"><button class="nav-link rounded-pill" id="pills-medio-tab" data-bs-toggle="pill" data-bs-target="#pills-medio" type="button"><i class="fas fa-star me-2"></i>M. Mundo</button></li>
                    </ul>
                    <div class="tab-content" id="pills-tabContent">
                        <div class="tab-pane fade show active" id="pills-huacho" role="tabpanel">
                            <div class="text-center mb-3"><h5 class="fw-bold text-danger">Policlínico Integra Salud</h5><p class="text-muted">Jr. José Arambulo la Rosa N° 156, Huacho</p></div>
                            <div class="map-container"><iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3905.823907767784!2d-77.6103856!3d-11.1116667!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x9106df90f6745161%3A0x6b44585c5758557!2sJr.%20Jos%C3%A9%20T.%20Garcia%20156%2C%20Huacho%2015136!5e0!3m2!1ses!2spe!4v1701234567890" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe></div>
                            <div class="d-grid"><a href="https://www.google.com/maps/dir//Jr.+José+Arambulo+la+Rosa+156,+Huacho" target="_blank" class="btn btn-danger btn-lg rounded-pill">Cómo llegar</a></div>
                        </div>
                        <div class="tab-pane fade" id="pills-huaura" role="tabpanel">
                            <div class="text-center mb-3"><h5 class="fw-bold text-success">Clínica Los Ángeles - Sede Huaura</h5><p class="text-muted">Av. San Martin 392, Huaura</p></div>
                            <div class="map-container"><iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3906.3!2d-77.595!3d-11.070!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMTHCsDA0JzEyLjAiUyA3N8KwMzUnNDIuMCJX!5e0!3m2!1ses!2spe!4v1600000000000" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe></div>
                            <div class="d-grid"><a href="https://www.google.com/maps/dir//Av.+San+Martin+392,+Huaura" target="_blank" class="btn btn-success btn-lg rounded-pill">Cómo llegar</a></div>
                        </div>
                        <div class="tab-pane fade" id="pills-medio" role="tabpanel">
                            <div class="text-center mb-3"><h5 class="fw-bold text-warning" style="color:#d4a017 !important;">Clínica Los Ángeles - Medio Mundo</h5><p class="text-muted">Av. Ezequiel Gago Mz. H Lt. 19 "B"</p></div>
                            <div class="map-container"><iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3908.5!2d-77.65!3d-10.95!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMTDCsDU3JzAwLjAiUyA3N8KwMzknMDAuMCJX!5e0!3m2!1ses!2spe!4v1600000000000" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe></div>
                            <div class="d-grid"><a href="https://www.google.com/maps/dir//Av.+Ezequiel+Gago,+Medio+Mundo" target="_blank" class="btn btn-warning btn-lg rounded-pill text-white fw-bold" style="background-color:#d4a017;">Cómo llegar</a></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="socialModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0 pb-4">
                    <div class="text-center mb-4">
                        <h4 class="fw-bold mb-1" id="socialModalTitle">Elige tu Sede</h4>
                        <p class="small text-muted mb-0">Síguenos para más novedades:</p>
                    </div>
                    <div id="socialButtonsContainer">
                        </div>
                </div>
            </div>
        </div>
    </div>

    <a href="https://wa.me/51997670532" class="whatsapp-float" target="_blank"><i class="fab fa-whatsapp"></i></a>

    <footer class="text-white pt-5 pb-4">
        <div class="container">
            <div class="row mb-4 text-center">
                <div class="col-12">
                    <h4 class="text-uppercase fw-bold text-warning mb-3">Síguenos en redes</h4>
                    <div>
                        <button onclick="openSocial('facebook')" class="btn btn-outline-light btn-sm rounded-circle mx-1 social-btn-footer" style="width: 45px; height: 45px;"><i class="fab fa-facebook-f fa-lg"></i></button>
                        <button onclick="openSocial('instagram')" class="btn btn-outline-light btn-sm rounded-circle mx-1 social-btn-footer" style="width: 45px; height: 45px;"><i class="fab fa-instagram fa-lg"></i></button>
                        <button onclick="openSocial('tiktok')" class="btn btn-outline-light btn-sm rounded-circle mx-1 social-btn-footer" style="width: 45px; height: 45px;"><i class="fab fa-tiktok fa-lg"></i></button>
                    </div>
                </div>
            </div>
            <hr class="border-secondary my-4">
            <div class="row gy-4">
                <div class="col-md-4 text-center text-md-start footer-divider">
                    <h5 class="text-warning fw-bold mb-3"><i class="fas fa-clinic-medical me-2"></i>Sede Huacho</h5>
                    <p class="mb-1 fw-bold small">Policlínico Integra Salud</p>
                    <p class="small opacity-75 mb-1"><i class="fas fa-map-marker-alt me-2"></i>Jr. José Arambulo la Rosa N° 156</p>
                    <p class="small opacity-75 mb-1"><i class="fas fa-phone me-2"></i>983 872 227 / 970 826 071</p>
                    <p class="small opacity-75 mb-1"><i class="fas fa-clock me-2"></i>8:00 am - 7:00 pm</p>
                </div>
                <div class="col-md-4 text-center text-md-start footer-divider">
                    <h5 class="text-warning fw-bold mb-3"><i class="fas fa-hospital me-2"></i>Sede Huaura</h5>
                    <p class="mb-1 fw-bold small">Clínica Los Ángeles</p>
                    <p class="small opacity-75 mb-1"><i class="fas fa-map-marker-alt me-2"></i>Av. San Martin 392</p>
                    <p class="small opacity-75 mb-1"><i class="fas fa-phone me-2"></i>997 670 532 / 928 752 213</p>
                    <p class="small opacity-75 mb-1"><i class="fas fa-clock me-2"></i>8:00 am - 8:00 pm</p>
                </div>
                <div class="col-md-4 text-center text-md-start">
                    <h5 class="text-warning fw-bold mb-3"><i class="fas fa-star me-2"></i>Sede Medio Mundo</h5>
                    <p class="mb-1 fw-bold small">Clínica Los Ángeles</p>
                    <p class="small opacity-75 mb-1"><i class="fas fa-map-marker-alt me-2"></i>Av. Ezequiel Gago Mz. H Lt. 19 "B"</p>
                    <p class="small opacity-75 mb-1"><i class="fas fa-phone me-2"></i>992 982 658 / 940 599 312</p>
                    <p class="small opacity-75 mb-1"><i class="fas fa-clock me-2"></i>8:00 am - 6:00 pm</p>
                </div>
            </div>
            <hr class="border-secondary my-4">
            <div class="text-center small opacity-50">
                <p class="mb-0">© 2025 Elam Medical del Norte.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        let modalSedes;
        let socialModal;
        let mapModal;

        const socialNetworks = {
            facebook: {
                title: "Facebook",
                icon: "fab fa-facebook-f",
                colorClass: "social-fb",
                links: [
                    { name: "Sede Huaura", desc: "Clínica Los Ángeles", url: "https://www.facebook.com/LosAngelesHuaura", icon: "fas fa-hospital" },
                    { name: "Sede Huacho", desc: "Integra Salud", url: "https://www.facebook.com/IntegraSaludHuacho", icon: "fas fa-clinic-medical" },
                    { name: "Sede Medio Mundo", desc: "Clínica Los Ángeles", url: "https://www.facebook.com/LosAngelesMedioMundo", icon: "fas fa-star" }
                ]
            },
            instagram: {
                title: "Instagram",
                icon: "fab fa-instagram",
                colorClass: "social-ig",
                links: [
                    { name: "Clínica Los Ángeles", desc: "Cuenta Oficial", url: "https://www.instagram.com/clinicalosangelesoficial/", icon: "fas fa-hospital-user" },
                    { name: "Integra Salud", desc: "Policlínico Huacho", url: "https://www.instagram.com/integrasaludoficial/", icon: "fas fa-user-md" }
                ]
            },
            tiktok: {
                title: "TikTok",
                icon: "fab fa-tiktok",
                colorClass: "social-tt",
                links: [
                    { name: "Clínica Los Ángeles", desc: "Videos Oficiales", url: "https://www.tiktok.com/@clinicalosangelesoficial", icon: "fas fa-video" },
                    { name: "Integra Salud", desc: "Videos Oficiales", url: "https://www.tiktok.com/@integrasaludoficial", icon: "fas fa-film" }
                ]
            }
        };
        
        document.addEventListener('DOMContentLoaded', function() {
            modalSedes = new bootstrap.Modal(document.getElementById('modalSedes'));
            socialModal = new bootstrap.Modal(document.getElementById('socialModal'));
            mapModal = new bootstrap.Modal(document.getElementById('modalMapas'));
        });

        function abrirModalOferta(nombreOferta) {
            document.getElementById('nombreOferta').value = nombreOferta;
            modalSedes.show();
        }

        // Nueva función para abrir el mapa y seleccionar la pestaña correcta
        function openMapTab(tabId) {
            // 1. Mostrar el modal
            mapModal.show();
            
            // 2. Esperar un poco a que el modal se inicie (opcional, pero seguro)
            // y disparar el click en la pestaña correspondiente
            const triggerEl = document.querySelector('#' + tabId);
            if(triggerEl) {
                const tab = new bootstrap.Tab(triggerEl);
                tab.show();
            }
        }

        function openSocial(platform) {
            const data = socialNetworks[platform];
            const container = document.getElementById('socialButtonsContainer');
            const title = document.getElementById('socialModalTitle');
            title.innerHTML = `<i class="${data.icon} me-2"></i>${data.title}`;
            container.innerHTML = '';
            
            data.links.forEach((link, index) => {
                const card = document.createElement('a');
                card.href = link.url;
                card.target = "_blank";
                card.className = `social-card ${data.colorClass}`;
                card.style.animationDelay = `${index * 0.15}s`;

                card.innerHTML = `
                    <div class="sede-icon"><i class="${link.icon}"></i></div>
                    <div class="social-text"><strong>${link.name}</strong><small>${link.desc}</small></div>
                    <i class="fas fa-chevron-right arrow-icon"></i>
                `;
                container.appendChild(card);
            });
            socialModal.show();
        }

        function enviarWsp(numeroSede, nombreSede) {
            const nombre = document.getElementById('leadNombre').value.trim();
            const dni = document.getElementById('leadDni').value.trim();
            const telefono = document.getElementById('leadTelefono').value.trim();
            const oferta = document.getElementById('nombreOferta').value;
            if (!nombre) { alert("Por favor, ingresa tu nombre."); return; }
            if (dni.length < 8) { alert("El DNI debe tener 8 dígitos."); return; }
            if (telefono.length < 9) { alert("El teléfono debe tener 9 dígitos."); return; }
            const mensaje = `Hola Clínica Los Ángeles (${nombreSede}) 🎄.\nMi nombre es ${nombre}, DNI: ${dni}.\nMe interesa la oferta: *${oferta}*.\nMi número de contacto es: ${telefono}.\n¡Quedo atento a su respuesta!`;
            const url = `https://wa.me/51${numeroSede}?text=${encodeURIComponent(mensaje)}`;
            window.open(url, '_blank');
        }
    </script>
</body>
</html>