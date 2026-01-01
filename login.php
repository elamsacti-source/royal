<?php 
include_once 'session.php'; 

// Si ya está logueado, enviar directo a la Intranet
if (isset($_SESSION['user_id'])) {
    header('Location: intranet.php'); // <--- CAMBIO AQUÍ
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acceso Corporativo 🎄</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="imagenes/icon.png" />
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Mountains+of+Christmas:wght@700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet"/>

    <style>
        :root {
            /* Paleta Navideña */
            --primary: #D42426; /* Rojo Santa */
            --primary-dark: #a81c1e;
            --accent: #165B33; /* Verde Pino */
            --bg-gradient: linear-gradient(135deg, #165B33 0%, #0f3e23 100%);
            --surface: #ffffff;
            --text-main: #1e293b;
            --text-light: #64748b;
            --border: #e2e8f0;
            --danger: #ef4444;
            --gold: #FFD700;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            overflow: hidden;
            position: relative;
        }

        /* Efecto de Nieve */
        .snow-container {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            pointer-events: none;
            z-index: 0;
        }
        .snowflake {
            position: absolute;
            top: -10px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 1em;
            animation: fall linear infinite;
        }
        @keyframes fall {
            0% { transform: translateY(-10vh) translateX(0px); opacity: 1; }
            100% { transform: translateY(110vh) translateX(20px); opacity: 0.3; }
        }

        /* Tarjeta de Login */
        .login-card {
            background: var(--surface);
            width: 100%;
            max-width: 420px;
            padding: 30px;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            border: 4px solid rgba(255,255,255,0.3); /* Borde translúcido */
            position: relative;
            z-index: 10;
        }

        /* --- CONTENEDOR DE LOGOS (CORREGIDO) --- */
        .logos-box {
            background: linear-gradient(135deg, #D42426 0%, #a81c1e 100%); /* Fondo Rojo Festivo */
            border-radius: 16px;
            padding: 15px;
            
            /* Flexbox para alinear los logos uno al lado del otro */
            display: flex;
            justify-content: center; /* Centrar horizontalmente */
            align-items: center;     /* Centrar verticalmente */
            gap: 20px;               /* Espacio entre logos */
            
            margin-bottom: 25px;
            box-shadow: inset 0 2px 10px rgba(0,0,0,0.2);
            border: 2px solid var(--gold); /* Borde dorado */
            width: 100%;
        }
        
        .logo-img {
            height: auto;
            max-height: 55px; /* Altura controlada */
            max-width: 45%;   /* Para que entren los dos sin salirse */
            object-fit: contain;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
            transition: transform 0.3s;
        }
        .logo-img:hover { transform: scale(1.05); }

        .title-area { text-align: center; margin-bottom: 25px; }
        
        h1 {
            font-family: 'Mountains of Christmas', cursive;
            color: var(--primary);
            font-size: 2.2rem;
            font-weight: 700;
            margin: 0;
        }
        p.subtitle {
            color: var(--text-light);
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 5px;
        }

        .form-group { margin-bottom: 20px; position: relative; }
        .form-label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-main); margin-bottom: 8px; }

        .input-wrapper { position: relative; display: flex; align-items: center; }
        
        .input-icon {
            position: absolute; left: 12px;
            color: var(--primary); width: 20px; height: 20px;
            pointer-events: none;
        }

        input {
            width: 100%;
            /* Padding derecho extra para que el texto no toque a Santa */
            padding: 12px 45px 12px 40px; 
            border: 2px solid var(--border);
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.2s;
            background: #f8fafc;
            color: var(--text-main);
            outline: none;
        }

        input:focus {
            border-color: var(--accent);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(22, 91, 51, 0.1);
        }

        /* --- BOTÓN MOSTRAR CONTRASEÑA (SANTA) --- */
        .toggle-pass {
            position: absolute; 
            right: 10px; /* Pegado a la derecha */
            background: none; border: none;
            cursor: pointer; padding: 0; 
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; /* Tamaño del emoji */
            transition: transform 0.2s;
            z-index: 5;
        }
        .toggle-pass:hover { transform: scale(1.2) rotate(10deg); }
        .toggle-pass:active { transform: scale(0.9); }

        button[type="submit"] {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--accent) 0%, #0f3e23 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-family: 'Mountains of Christmas', cursive;
            font-weight: 700;
            letter-spacing: 1px;
            cursor: pointer;
            transition: transform 0.2s;
            margin-top: 10px;
            box-shadow: 0 4px 10px rgba(22, 91, 51, 0.3);
            border: 1px solid rgba(255,255,255,0.2);
        }

        button[type="submit"]:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(22, 91, 51, 0.4); filter: brightness(1.1); }
        button[type="submit"]:active { transform: scale(0.98); }
        button[type="submit"]:disabled { opacity: 0.7; cursor: wait; transform: none; }

        .error-msg {
            background-color: #fef2f2; color: var(--danger);
            border: 1px solid #fee2e2; padding: 12px;
            border-radius: 8px; font-size: 0.9rem;
            text-align: center; margin-top: 20px;
            display: none; animation: headShake 0.5s;
        }

        .footer-links { text-align: center; margin-top: 25px; font-size: 0.8rem; color: var(--text-light); }
        .footer-links a { color: var(--primary); text-decoration: none; font-weight: 600; }
        .footer-links a:hover { text-decoration: underline; }

        /* Ajuste para móviles muy pequeños */
        @media (max-width: 380px) {
            .logo-img { max-height: 40px; }
            .login-card { padding: 20px; }
        }

    </style>
</head>
<body>

    <div class="snow-container" aria-hidden="true">
        <div class="snowflake" style="left: 10%; animation-duration: 10s;">❄</div>
        <div class="snowflake" style="left: 20%; animation-duration: 12s; animation-delay: 2s;">❅</div>
        <div class="snowflake" style="left: 50%; animation-duration: 8s; animation-delay: 4s;">❆</div>
        <div class="snowflake" style="left: 70%; animation-duration: 11s; animation-delay: 1s;">❄</div>
        <div class="snowflake" style="left: 85%; animation-duration: 9s; animation-delay: 3s;">❅</div>
    </div>

    <div class="login-card animate__animated animate__fadeInUp">
        
        <div class="logos-box">
            <img src="imagenes/logoIntegra.png" alt="Integra Salud" class="logo-img">
            <img src="imagenes/logoLosAngeles.png" alt="Clínica Los Ángeles" class="logo-img">
        </div>

        <div class="title-area">
            <h1>Acceso al Sistema</h1>
            <p class="subtitle">🎄 Gestión & Checklist 🎅</p>
        </div>

        <form id="loginForm">
            <div class="form-group">
                <label class="form-label" for="email">Correo Electrónico</label>
                <div class="input-wrapper">
                    <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <input type="email" id="email" placeholder="usuario@clinica.com" required autocomplete="email">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Contraseña</label>
                <div class="input-wrapper">
                    <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    
                    <input type="password" id="password" placeholder="••••••••" required>
                    
                    <button type="button" class="toggle-pass" onclick="togglePassword()" title="Mostrar/Ocultar" aria-label="Mostrar Contraseña">
                        <span id="pass-icon">🎅</span>
                    </button>
                </div>
            </div>

            <button type="submit" id="btn">
                <span id="btn-text">Iniciar Sesión</span>
            </button>
        </form>

        <div id="msg" class="error-msg"></div>

        <div class="footer-links">
            <p>© 2025 Elam Medical del Norte</p>
            <a href="index.php">← Volver al inicio</a>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('pass-icon');
            
            if (input.type === 'password') {
                input.type = 'text'; // Mostrar contraseña
                icon.textContent = '🍪'; // Galleta (Sorpresa)
            } else {
                input.type = 'password'; // Ocultar contraseña
                icon.textContent = '🎅'; // Santa (Secreto)
            }
        }

        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn');
            const btnText = document.getElementById('btn-text');
            const msg = document.getElementById('msg');
            
            btn.disabled = true; 
            btnText.textContent = "Verificando..."; 
            msg.style.display = 'none';

            try {
                const res = await fetch('api_login.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        email: document.getElementById('email').value,
                        password: document.getElementById('password').value
                    })
                });
                
                const data = await res.json();
                
                if (data.success) {
                    btnText.textContent = "¡Bienvenido! 🎁";
                    setTimeout(() => {
                        window.location.href = 'intranet.php';
                    }, 800);
                } else {
                    throw new Error(data.error || 'Credenciales incorrectas');
                }
            } catch (err) {
                msg.innerHTML = `⚠️ ${err.message}`;
                msg.style.display = 'block';
                btn.disabled = false; 
                btnText.textContent = "Iniciar Sesión";
            }
        });
    </script>
</body>
</html>