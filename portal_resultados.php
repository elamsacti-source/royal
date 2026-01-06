<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Portal de Resultados | Clínica Los Ángeles 🎄</title>
    <link rel="icon" type="image/x-icon" href="imagenes/icon.png" />
    
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&family=Mountains+of+Christmas:wght@700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <style>
        /* --- VARIABLES DE DISEÑO --- */
        :root {
            --xmas-red: #D32F2F;
            --xmas-dark: #8a1c1c;
            --xmas-green: #2E7D32;
            --xmas-gold: #FFD700; 
            --text-dark: #1e293b;
            --bg-gradient: radial-gradient(circle at center, #b71c1c 0%, #5d0f0f 100%);
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            padding: 20px; overflow-x: hidden; position: relative;
        }

        /* --- NIEVE (Sutil) --- */
        .snow-container { position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 0; }
        .snowflake { color: rgba(255,255,255,0.4); font-size: 1.2em; position: fixed; top: -10%; z-index: 0; animation: snowflakes-fall 12s linear infinite, snowflakes-shake 4s ease-in-out infinite; }
        @keyframes snowflakes-fall { 0% { top: -10%; } 100% { top: 100%; } }
        @keyframes snowflakes-shake { 0%, 100% { transform: translateX(0); } 50% { transform: translateX(80px); } }
        
        .snowflake:nth-of-type(1) { left: 5%; animation-duration: 10s; }
        .snowflake:nth-of-type(2) { left: 20%; animation-duration: 15s; animation-delay: 2s; }
        .snowflake:nth-of-type(3) { left: 50%; animation-duration: 12s; animation-delay: 4s; }
        .snowflake:nth-of-type(4) { left: 80%; animation-duration: 14s; animation-delay: 1s; }

        /* --- CONTENEDOR PRINCIPAL --- */
        .portal-wrapper {
            width: 100%; max-width: 1000px;
            position: relative; z-index: 10;
            display: flex; flex-direction: column;
            align-items: center; flex-grow: 1; justify-content: center;
        }

        /* --- CABECERA --- */
        .header-section { text-align: center; margin-bottom: 35px; }
        
        .logos-container {
            display: flex; justify-content: center; align-items: center; gap: 30px; margin-bottom: 20px;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.3));
        }
        .logo-img { height: 75px; width: auto; transition: transform 0.3s ease; }
        .logo-img:hover { transform: scale(1.05); }

        .main-title {
            font-family: 'Mountains of Christmas', cursive;
            font-size: 3.8rem;
            color: var(--xmas-gold);
            text-shadow: 2px 2px 4px rgba(0,0,0,0.4);
            margin: 0; line-height: 1.1;
        }
        .subtitle {
            color: #fff8e1; font-size: 1.1rem;
            margin-top: 5px; font-weight: 400;
            letter-spacing: 1px; opacity: 0.9;
        }

        /* --- TARJETAS --- */
        .card-option {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 40px 30px;
            text-align: center; text-decoration: none;
            color: var(--text-dark);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            height: 100%; display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            position: relative; overflow: hidden;
        }
        .card-option::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 5px;
            background: linear-gradient(90deg, var(--xmas-green), var(--xmas-red), var(--xmas-gold));
        }
        .card-option:hover { transform: translateY(-8px); box-shadow: 0 25px 50px rgba(0,0,0,0.35); background: #ffffff; }

        /* --- CÍRCULOS DE ÍCONOS (CON EMOJIS FLOTANTES) --- */
        .icon-circle {
            width: 110px; height: 110px; /* Tamaño generoso */
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 3rem; margin-bottom: 20px;
            box-shadow: inset 0 0 15px rgba(0,0,0,0.05), 0 10px 20px rgba(0,0,0,0.1);
            border: 1px solid rgba(0,0,0,0.05);
            transition: all 0.4s ease;
            position: relative; z-index: 2;
        }
        
        .card-option:hover .icon-circle { transform: scale(1.05); box-shadow: 0 15px 30px rgba(0,0,0,0.15); }

        /* Estilos para los Emojis Flotantes */
        .mini-emoji {
            position: absolute;
            font-size: 1.4rem;
            animation: floatEmoji 3s ease-in-out infinite;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }
        .emoji-top-right { top: 10px; right: 5px; animation-delay: 0s; }
        .emoji-bottom-left { bottom: 10px; left: 5px; animation-delay: 1.5s; }
        
        @keyframes floatEmoji { 
            0%, 100% { transform: translateY(0); } 
            50% { transform: translateY(-6px); } 
        }

        .card-title { font-weight: 700; font-size: 1.4rem; margin-bottom: 8px; color: var(--text-dark); }
        .card-text { font-size: 0.9rem; color: #64748b; line-height: 1.5; margin-bottom: 25px; }

        .btn-access {
            background: var(--text-dark); color: white;
            padding: 10px 25px; border-radius: 50px;
            font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px;
            transition: 0.3s; width: 100%; max-width: 180px; border: none;
        }

        /* --- COLORES ESPECÍFICOS --- */
        .card-patient .icon-circle { 
            background: radial-gradient(circle at 30% 30%, #ffffff, #e8f5e9);
            color: var(--xmas-green); border-color: #c8e6c9;
        }
        .card-patient .btn-access { background: var(--xmas-green); }
        .card-patient:hover .btn-access { background: #1b5e20; }

        .card-staff .icon-circle { 
            background: radial-gradient(circle at 30% 30%, #ffffff, #ffebee);
            color: var(--xmas-red); border-color: #ffcdd2;
        }
        .card-staff .btn-access { background: var(--xmas-red); }
        .card-staff:hover .btn-access { background: #b71c1c; }

        /* --- BOTÓN VOLVER --- */
        .back-link-container { width: 100%; display: flex; justify-content: flex-start; margin-bottom: 10px; }
        .back-link {
            background: rgba(0, 0, 0, 0.25);
            padding: 8px 16px; border-radius: 30px;
            color: rgba(255,255,255,0.9); text-decoration: none;
            font-weight: 600; font-size: 0.85rem; transition: 0.3s;
            display: inline-flex; align-items: center; gap: 8px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .back-link:hover { background: rgba(0, 0, 0, 0.4); color: var(--xmas-gold); border-color: var(--xmas-gold); }

        /* --- WHATSAPP --- */
        .wsp-float {
            position: fixed; bottom: 25px; right: 25px;
            background: #25D366; color: white;
            width: 50px; height: 50px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            transition: 0.3s; z-index: 100; border: 2px solid white;
            text-decoration: none;
        }
        .wsp-float:hover { transform: scale(1.1); background: #128C7E; }

        .legal-footer {
            margin-top: 40px; text-align: center;
            color: rgba(255,255,255,0.5); font-size: 0.75rem;
            width: 100%; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.1);
        }
        .legal-footer strong { color: rgba(255,255,255,0.7); }

        @media (max-width: 768px) {
            .main-title { font-size: 2.8rem; }
            .logos-container { flex-wrap: wrap; gap: 15px; }
            .logo-img { height: 50px; }
            .card-option { padding: 30px 20px; }
            .back-link-container { justify-content: center; margin-bottom: 20px; }
        }
    </style>
</head>
<body>

    <div class="snow-container" aria-hidden="true">
        <div class="snowflake">❅</div><div class="snowflake">❆</div><div class="snowflake">❅</div>
        <div class="snowflake">❆</div>
    </div>

    <div class="portal-wrapper animate__animated animate__fadeIn">
        
        <div class="back-link-container">
            <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Volver al Inicio</a>
        </div>

        <header class="header-section">
            <div class="logos-container animate__animated animate__fadeInDown">
                <img src="imagenes/logoIntegra.png" alt="Integra Salud" class="logo-img">
                <img src="imagenes/logoLosAngeles.png" alt="Clínica Los Ángeles" class="logo-img">
            </div>
            <h1 class="main-title">Centro de Resultados</h1>
            <p class="subtitle">🎄 Sistema de Gestión en Línea 🎅</p>
        </header>

        <div class="row w-100 justify-content-center g-4">
            
            <div class="col-md-6 col-lg-5">
                <a href="resultados.php" class="card-option card-patient animate__animated animate__fadeInLeft animate__delay-1s">
                    <div class="icon-circle">
                        <i class="fas fa-user-injured"></i>
                        <span class="mini-emoji emoji-top-right">🧪</span>
                        <span class="mini-emoji emoji-bottom-left">📄</span>
                    </div>
                    <div class="card-title">Soy Paciente</div>
                    <div class="card-text">
                        Consulte sus informes de laboratorio con su DNI y código de seguridad.
                    </div>
                    <button class="btn-access">Ingresar</button>
                </a>
            </div>

            <div class="col-md-6 col-lg-5">
                <a href="registro_resultados.php" class="card-option card-staff animate__animated animate__fadeInRight animate__delay-1s">
                    <div class="icon-circle">
                        <i class="fas fa-user-md"></i>
                        <span class="mini-emoji emoji-top-right">🩺</span>
                        <span class="mini-emoji emoji-bottom-left">💻</span>
                    </div>
                    <div class="card-title">Soy Personal</div>
                    <div class="card-text">
                        Acceso exclusivo para médicos y licenciadas. Carga segura de archivos.
                    </div>
                    <button class="btn-access">Administrar</button>
                </a>
            </div>

        </div>

        <footer class="legal-footer animate__animated animate__fadeInUp animate__delay-2s">
            <p class="mb-1">© 2025 Propiedad de <strong>Elam Medical del Norte SAC</strong></p>
            <p class="mb-0 small">Todos los derechos reservados.</p>
        </footer>

    </div>

    <a href="https://wa.me/51997670532" target="_blank" class="wsp-float">
        <i class="fab fa-whatsapp"></i>
    </a>

</body>
</html>