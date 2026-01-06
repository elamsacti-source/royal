<?php
include_once 'session.php'; 

// SEGURIDAD
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_rol = $_SESSION['rol'] ?? $_SESSION['cargo'] ?? $_SESSION['type'] ?? 'user';

if (stripos($user_name, 'Administrador') !== false) {
    $user_rol = 'admin';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ELAM Citas | Panel de Citas</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Mountains+of+Christmas:wght@700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/airbnb.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>

    <style>
        :root {
            --primary: #1e40af;           
            --primary-soft: #eff6ff;
            --gold: #38bdf8;              
            --green: #15803d;             
            --dark: #0f172a;
            --gray: #64748b;
            --bg-body: #f1f5f9;
            --glass: rgba(255, 255, 255, 0.98);
            --glass-border: #e5e7eb;
            --radius: 16px;
            --shadow: 0 10px 30px -10px rgba(15,23,42,0.12);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Outfit', sans-serif; }
        .merry-font { font-family: 'Outfit', sans-serif; font-weight: 700; letter-spacing: 0.5px; }

        body { 
            background-color: var(--bg-body); 
            background-image: radial-gradient(at 0% 0%, rgba(30, 64, 175, 0.05) 0px, transparent 50%), radial-gradient(at 100% 100%, rgba(56, 189, 248, 0.08) 0px, transparent 50%);
            color: var(--dark); 
            min-height: 100vh; 
            display: flex; flex-direction: column; font-size: 14px; 
        }

        .hidden { display: none !important; }
        .flex { display: flex; } .flex-col { flex-direction: column; }
        .gap-2 { gap: 0.5rem; } .gap-3 { gap: 0.75rem; } .gap-4 { gap: 1rem; }
        .w-full { width: 100%; } .mb-3 { margin-bottom: 0.75rem; } .mb-4 { margin-bottom: 1rem; }
        .items-center { align-items: center; } .justify-between { justify-content: space-between; }

        /* --- BOTONES --- */
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 0.6rem 1.2rem; border-radius: 12px; font-weight: 600; font-size: 0.9rem; cursor: pointer; border: none; transition: all 0.25s ease; outline: none; }
        .btn-primary { background: linear-gradient(135deg, var(--primary) 0%, #1d4ed8 100%); color: white; box-shadow: 0 4px 15px rgba(30,64,175,0.3); }
        .btn-secondary { background: white; border: 1px solid #e2e8f0; color: var(--gray); }
        .btn-icon { padding: 0; width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0; }

        /* --- ACORDEONES --- */
        .sede-accordion { border: 1px solid var(--glass-border); border-radius: 12px; margin-bottom: 12px; overflow: hidden; background: white; box-shadow: var(--shadow); transition: all 0.3s ease; }
        .sede-summary { padding: 14px 20px; background: linear-gradient(to right, #ffffff, #f8fafc); cursor: pointer; font-weight: 700; display: flex; justify-content: space-between; align-items: center; color: var(--primary); list-style: none; border-bottom: 1px solid #f1f5f9; }
        .sede-summary:hover { background: var(--primary-soft); }
        .sede-summary::-webkit-details-marker { display: none; }
        .sede-summary .count-badge { background: var(--primary); color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 800; }
        details[open] .sede-summary { background: var(--primary-soft); border-bottom-color: #dbeafe; }

        /* --- TABLAS --- */
        .table-container { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 0.9rem; }
        th { text-align: left; padding: 1rem; color: var(--gray); font-weight: 700; background: #f8fafc; border-bottom: 2px solid #e2e8f0; font-size: 0.75rem; text-transform: uppercase; white-space: nowrap; }
        td { padding: 1rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        tbody tr:hover td { background-color: #eef2ff; cursor: pointer; }

        /* --- LAYOUT & CARDS --- */
        .top-bar { display: flex; justify-content: space-between; align-items: center; background: var(--glass); backdrop-filter: blur(10px); padding: 0.8rem 2rem; border-radius: 20px; box-shadow: var(--shadow); border: 1px solid var(--glass-border); margin: 20px; }
        
        .logo-original { 
            height: 38px; width: auto; object-fit: contain; 
            filter: brightness(0) saturate(100%) invert(18%) sepia(96%) saturate(2863%) hue-rotate(218deg) brightness(93%) contrast(97%);
            transition: all 0.3s ease; opacity: 0.9;
        }
        .logo-original:hover { filter: none; opacity: 1; }

        .panel { background: white; border-radius: 20px; border: 1px solid #f1f5f9; box-shadow: var(--shadow); display: flex; flex-direction: column; overflow: hidden; }
        .panel-header { padding: 1rem 1.5rem; background: linear-gradient(to right, #ffffff, #eff6ff); border-bottom: 1px solid #f1f5f9; font-weight: 700; color: var(--primary); display: flex; justify-content: space-between; align-items: center; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; padding: 0 20px; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 20px; box-shadow: var(--shadow); display: flex; align-items: center; gap: 1.2rem; }
        .stat-icon { width: 50px; height: 50px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; flex-shrink: 0; }
        .stat-blue .stat-icon { background: #eff6ff; color: #2563eb; }
        .stat-green .stat-icon { background: #f0fdf4; color: var(--green); }
        .stat-red .stat-icon { background: #fef2f2; color: var(--primary); }
        .stat-purple .stat-icon { background: #f3e8ff; color: #9333ea; }
        .badge { padding: 6px 12px; border-radius: 30px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
        .prog { background: #eff6ff; color: #1d4ed8; } .atendido { background: #f0fdf4; color: #15803d; } .warning { background: #fffbeb; color: #b45309; } .cancel { background: #fef2f2; color: #b91c1c; }
        
        /* --- MODALES --- */
        .modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); z-index: 2000; display: none; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
        .modal-overlay.show { display: flex; }
        
        .modal { background: white; width: 95%; max-width: 550px; border-radius: 24px; display: flex; flex-direction: column; max-height: 90vh; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3); }
        .modal-xl-custom { max-width: 1350px !important; width: 95% !important; height: 90vh; border-radius: 24px; }
        
        .modal-header { padding: 1.2rem 1.5rem; background: white; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 1.5rem; overflow-y: auto; }
        .modal-footer { padding: 1rem 1.5rem; background: #f8fafc; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 10px; }

        .input-label { display: block; font-size: 0.75rem; font-weight: 700; color: var(--gray); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
        input, select, textarea { width: 100%; padding: 0.6rem 1rem; border-radius: 12px; border: 1px solid #e2e8f0; background: white; color: var(--dark); outline: none; font-size: 0.9rem; height: 42px; transition: 0.25s; }
        textarea { height: auto; }
        
        /* ESTILO PARA INPUTS DESHABILITADOS */
        input:disabled, select:disabled { 
            background-color: #f1f5f9; 
            color: #94a3b8; 
            cursor: not-allowed; 
            border-color: #e2e8f0; 
        }

        #admin-sede-select { border: 2px solid var(--gold); font-weight: 700; color: var(--primary); }

        .mini-stats { display: flex; gap: 10px; margin-bottom: 15px; overflow-x: auto; }
        .mini-card { flex: 1; min-width: 90px; background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 10px; display: flex; flex-direction: column; align-items: center; text-align: center; }
        .mc-val { font-size: 1.1rem; font-weight: 800; color: var(--dark); line-height: 1; }
        .mc-lbl { font-size: 0.6rem; color: var(--gray); text-transform: uppercase; font-weight: 700; margin-top: 4px; }
        
        .btn-status { font-size: 0.75rem; font-weight: 700; padding: 4px 10px; border-radius: 20px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; border: 1px solid transparent; }
        .btn-status.upload { background: #fff; color: #2563eb; border-color: #2563eb; }
        .btn-status.view { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }
        .btn-status.refuse { background: #fff; color: #ef4444; border-color: #fecaca; }
        
        .add-treatment-box { background: #f8fafc; padding: 15px; border-radius: 12px; border: 1px dashed #cbd5e1; margin-bottom: 15px; }
        .treatment-row { display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; }
        .treatment-row > div { flex: 1; min-width: 140px; } 
        .treatment-row > .btn-add { flex: 0 0 42px; } 

        .treatment-list { list-style: none; padding: 0; margin: 0; }
        .treatment-item { display: flex; flex-direction: column; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; margin-bottom: 8px; position: relative; }
        .treatment-item .t-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
        .treatment-item .t-dates { display: flex; flex-wrap: wrap; gap: 4px; }
        .date-chip { font-size: 0.75rem; background: white; color: var(--dark); padding: 4px 10px; border-radius: 6px; border: 1px solid #cbd5e1; font-weight: 600; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
        .btn-remove-item { color: #ef4444; cursor: pointer; position: absolute; top: 10px; right: 10px; font-size: 1.2rem; }

        .filters-global-container { background: #f1f5f9; padding: 10px; border-radius: 10px; display: flex; gap: 10px; align-items: center; border: 1px solid #e2e8f0; flex-wrap: wrap; }
        .filters-global-container > * { flex: 1; min-width: 150px; } 
        .filters-global-container > button { flex: 0 0 auto; min-width: auto; }

        #toast-container { position: fixed; bottom: 30px; right: 30px; z-index: 3000; }
        .toast { background: white; border-left: 5px solid var(--primary); padding: 1rem 1.5rem; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); margin-top: 10px; display: flex; align-items: center; gap: 10px; font-weight: 600; }
        
        .section-title { font-weight: 700; color: var(--primary); border-bottom: 1px solid #e2e8f0; padding-bottom: 5px; margin-bottom: 15px; margin-top: 10px; }
        
        /* --- ESTILOS MEJORADOS PARA PREVISUALIZACIÓN --- */
        .preview-header-bar {
            background: #2d2d2d; color: #fff; padding: 15px 25px; 
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid #444;
        }
    </style>
</head>
<body>

    <input type="file" id="hidden-file-input" accept="image/*,application/pdf" style="display:none">

    <div id="app-screen" class="w-full">
        <main class="main-content">
            <div class="top-bar">
                <div class="flex gap-3 items-center">
                    <div class="flex gap-3 align-items-center">
                        <img src="imagenes/logoIntegra.png" class="logo-original" alt="Integra">
                        <img src="imagenes/logoLosAngeles.png" class="logo-original" alt="Angeles">
                    </div>
                    <div style="width: 1px; height: 30px; background: #e2e8f0; margin: 0 10px;"></div>
                    <div>
                        <h1 class="merry-font" style="font-size:1.4rem; color:var(--primary); margin:0;">🎄 ELAM Citas</h1>
                        <small style="color:var(--gray); font-size:0.75rem;">Panel de Recepción</small>
                    </div>
                    
                    <div id="sede-badge" style="background:var(--gold); color:#1e40af; padding:4px 12px; border-radius:20px; font-weight:800; font-size:0.75rem; box-shadow:0 2px 5px rgba(0,0,0,0.1);">Cargando...</div>
                </div>
                <div class="flex gap-3 items-center">
                    <a href="intranet.php" class="btn btn-secondary" style="text-decoration:none; padding: 0.5rem 1rem;">
                        <i class="ph ph-squares-four" style="font-size:1.2rem; margin-right:5px"></i> Menú
                    </a>
                    <span id="user-name-display" style="font-weight:600; font-size:0.9rem; color:var(--dark)">
                        <?php echo htmlspecialchars($user_name); ?>
                    </span>
                    <a href="api_logout.php" class="btn btn-secondary btn-icon" title="Cerrar Sesión"><i class="ph ph-sign-out" style="font-size:1.2rem"></i></a>
                </div>
            </div>

            <div class="stats-grid mb-4">
                <div class="stat-card stat-blue"><div class="stat-icon"><i class="ph ph-calendar"></i></div><div class="stat-info"><h3 id="stat-total">0</h3><p>Total Hoy</p></div></div>
                <div class="stat-card stat-green"><div class="stat-icon"><i class="ph ph-check-circle"></i></div><div class="stat-info"><h3 id="stat-atendidos">0</h3><p>Atendidos</p></div></div>
                <div class="stat-card stat-red"><div class="stat-icon"><i class="ph ph-x-circle"></i></div><div class="stat-info"><h3 id="stat-cancelados">0</h3><p>Cancelados</p></div></div>
                <div class="stat-card stat-purple"><div class="stat-icon"><i class="ph ph-users-three"></i></div><div class="stat-info"><h3 id="stat-pacientes">0</h3><p>Pacientes</p></div></div>
            </div>

            <div style="display: grid; grid-template-columns: 320px 1fr; gap: 20px; padding: 0 20px; align-items: start;">
                <div class="flex flex-col gap-4">
                    <div class="panel">
                        <div class="panel-header">
                            <span class="flex items-center gap-2"><i class="ph ph-user text-lg"></i> Datos del Paciente</span>
                            <button class="btn btn-secondary" style="padding:0.3rem 0.8rem; font-size:0.75rem" onclick="limpiarForm('paciente')">Limpiar</button>
                        </div>
                        <div class="panel-body" style="padding: 1.5rem;">
                            <div class="flex gap-3 mb-3">
                                <div style="flex:1"><label class="input-label">Doc</label><select id="tipo-doc"><option>DNI</option><option>CE</option></select></div>
                                <div style="flex:2.5"><label class="input-label">Número</label><div class="flex gap-2"><input id="dni-input" placeholder="8 dígitos"><button class="btn btn-primary btn-icon" id="btn-buscar-dni" onclick="buscarDNI()"><i class="ph ph-magnifying-glass text-lg"></i></button></div></div>
                            </div>
                            <div class="mb-3">
                                <label class="input-label">Apellidos</label>
                                <div class="flex gap-2"><input id="ape-input" readonly placeholder="Paterno Materno"><button class="btn btn-secondary btn-icon" onclick="toggleLock('ape-input', this)"><i class="ph ph-lock-key"></i></button></div>
                            </div>
                            <div class="mb-3">
                                <label class="input-label">Nombres</label>
                                <div class="flex gap-2"><input id="nom-input" readonly placeholder="Nombres Completos"><button class="btn btn-secondary btn-icon" onclick="toggleLock('nom-input', this)"><i class="ph ph-lock-key"></i></button></div>
                            </div>
                            <div class="flex gap-3">
                                <div class="w-full"><label class="input-label">Teléfono</label><input id="tel-input"></div>
                                <div class="w-full"><label class="input-label">H. Clínica</label><input id="hc-input"></div>
                            </div>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel-header">
                            <span class="flex items-center gap-2"><i class="ph ph-calendar-plus text-lg"></i> Nueva Cita</span>
                            <button class="btn btn-secondary" style="padding:0.3rem 0.8rem; font-size:0.75rem" onclick="abrirCatalogo()">
                                <i class="ph ph-currency-dollar"></i> Tarifario
                            </button>
                        </div>
                        <div class="panel-body" style="padding: 1.5rem;">
                            <div class="flex gap-3 mb-3">
                                <div class="w-full"><label class="input-label">Fecha</label><input type="date" id="fecha-cita"></div>
                                <div class="w-full"><label class="input-label">Hora</label><select id="hora-cita"><option>Seleccione...</option></select></div>
                            </div>
                            
                            <div class="mb-3"><label class="input-label">Especialidad</label><select id="esp-cita" onchange="filtrarDoctores()"><option value="">Seleccione...</option></select></div>
                            <div class="mb-3"><label class="input-label">Doctor</label><select id="doc-cita"><option>-- Seleccione --</option></select></div>

                            <div class="flex gap-3 mb-3">
                                <div class="w-full"><label class="input-label">Tipo</label><select id="tipo-cita"><option>Consulta</option><option>Procedimiento</option></select></div>
                                <div class="w-full"><label class="input-label">Consul.</label><select id="consultorio-cita"><option>101</option><option>102</option></select></div>
                            </div>
                            <div class="mb-4">
                                <label class="input-label">Ticket</label>
                                <div style="position:relative">
                                    <i class="ph ph-ticket" style="position:absolute; left:12px; top:12px; color:var(--primary); font-size:1.2rem"></i>
                                    <input id="ticket-input" placeholder="A-01" style="padding-left:40px; font-weight:700; color:var(--primary); letter-spacing:1px;">
                                </div>
                            </div>
                            <button class="btn btn-primary w-full" id="btn-save" onclick="guardarCita()">
                                <i class="ph ph-check-circle text-lg"></i> AGENDAR CITA
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-4">
                    
                    <div class="panel" style="flex:1; min-height:450px">
                        <div class="panel-header">
                            <span><i class="ph ph-hourglass"></i> Agenda Pendiente (Por Sede)</span>
                            <div class="flex gap-2">
                                <button class="btn" style="background:#dcfce7; color:#166534; border:1px solid #bbf7d0; padding:0.4rem 0.8rem; font-size:0.8rem;" onclick="abrirSeguimientoGlobal()">
                                    <i class="ph ph-list-checks text-lg"></i> Seguimiento Global
                                </button>
                                <button class="btn btn-secondary btn-icon" onclick="aplicarFiltros()"><i class="ph ph-arrows-clockwise text-lg"></i></button>
                            </div>
                        </div>
                        
                        <div style="padding:1rem; background:#fff; border-bottom:1px solid #f1f5f9; display:flex; gap:10px; align-items:end; flex-wrap:wrap;">
                            <div id="div-sede-filter" class="hidden" style="flex:1; min-width:150px;">
                                <label class="input-label" style="color:var(--primary)">🏢 Filtrar Sede</label>
                                <select id="admin-sede-select" onchange="cambiarSedeAdmin()">
                                    <option value="">🏢 Todas las Sedes</option>
                                    <option value="1">Sede 1 (Principal)</option>
                                    <option value="2">Sede 2</option>
                                    <option value="3">Sede 3</option>
                                </select>
                            </div>

                            <div style="flex:1.2; min-width:180px;">
                                <label class="input-label">Rango Fechas</label>
                                <div style="position:relative">
                                    <input id="rango-fechas" placeholder="Seleccionar..." style="padding-left:36px">
                                    <i class="ph ph-calendar" style="position:absolute; left:10px; top:10px; color:var(--gray)"></i>
                                </div>
                            </div>
                            <div style="flex:1; min-width:150px;">
                                <label class="input-label">Especialidad</label>
                                <select id="filtro-esp" onchange="cargarDoctoresFiltro()"><option value="">Todas</option></select>
                            </div>
                            <div style="flex:1; min-width:150px;">
                                <label class="input-label">Doctor</label>
                                <select id="filtro-doc" onchange="aplicarFiltros()"><option value="">Todos</option></select>
                            </div>
                        </div>

                        <div id="accordion-citas-container" style="padding:15px; overflow-y:auto; max-height:600px;"></div>
                    </div>

                    <div class="panel">
                        <div class="panel-header"><span><i class="ph ph-check-circle"></i> Finalizadas (Por Sede)</span></div>
                        <div id="accordion-gestion-container" style="padding:15px;"></div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div class="modal-overlay" id="modal-detalle">
        <div class="modal">
            <div class="modal-header">
                <div>
                    <h2 class="merry-font" style="font-size:1.5rem; margin:0; color:var(--primary)">Atender Cita</h2>
                    <p id="modal-paciente-info" style="font-size:0.9rem; color:var(--gray); margin-top:4px;">Cargando paciente...</p>
                </div>
                <button class="btn btn-secondary btn-icon" onclick="closeModal()"><i class="ph ph-x text-lg"></i></button>
            </div>
            
            <div class="modal-body">
                <input type="hidden" id="modal-id">
                
                <div class="section-title">Datos Generales</div>
                <div class="flex gap-4 mb-4" style="flex-wrap:wrap">
                    <div style="flex:1"><label class="input-label">Ticket</label><input id="modal-ticket" onchange="quickUpdate('ticket')" style="font-weight:bold;color:var(--primary); text-align:center;"></div>
                    <div style="flex:1"><label class="input-label">H. Clínica</label><input id="modal-hc" onchange="quickUpdate('historia_clinica')"></div>
                    <div style="flex:1.5">
                        <label class="input-label">Estado</label>
                        <select id="modal-estado" onchange="verificarReprogramacion()">
                            <option value="ATENDIDO">ATENDIDO</option>
                            <option value="CANCELADO">CANCELADO</option>
                            <option value="REPROGRAMADO">REPROGRAMADO</option>
                            <option value="PROGRAMADO">PROGRAMADO</option>
                        </select>
                    </div>
                    <div style="flex:1"><label class="input-label">H. Triaje</label><input type="time" id="modal-triaje"></div>
                </div>

                <div class="flex gap-4 mb-4" style="background:#fff7ed; padding:10px; border-radius:8px; border:1px solid #ffedd5;">
                    <div style="flex:1">
                        <label class="input-label" style="color:#c2410c">📅 Nueva Fecha</label>
                        <input type="date" id="modal-fecha" style="border-color:#fdba74" disabled>
                    </div>
                    <div style="flex:1">
                        <label class="input-label" style="color:#c2410c">⏰ Nueva Hora</label>
                        <select id="modal-hora-cita" style="border-color:#fdba74" disabled></select>
                    </div>
                </div>

                <div class="section-title">Triaje / Signos Vitales</div>
                <div style="background:#f0fdf4; padding:15px; border-radius:12px; border:1px solid #bbf7d0; margin-bottom:1.5rem;">
                    <div class="flex gap-3 mb-3" style="flex-wrap:wrap;">
                        <div style="flex:1"><label class="input-label" style="color:#166534">Peso (kg)</label><input id="modal-peso" type="number" step="0.01" style="border-color:#bbf7d0;"></div>
                        <div style="flex:1"><label class="input-label" style="color:#166534">Talla (m)</label><input id="modal-talla" type="number" step="0.01" style="border-color:#bbf7d0;"></div>
                        <div style="flex:1"><label class="input-label" style="color:#166534">P. Arterial</label><input id="modal-presion" placeholder="120/80" style="border-color:#bbf7d0;"></div>
                    </div>
                    <div class="flex gap-3" style="flex-wrap:wrap;">
                        <div style="flex:1"><label class="input-label" style="color:#166534">Temp. (°C)</label><input id="modal-temp" type="number" step="0.1" style="border-color:#bbf7d0;"></div>
                        <div style="flex:1"><label class="input-label" style="color:#166534">Sat. O2 (%)</label><input id="modal-sat" type="number" style="border-color:#bbf7d0;"></div>
                        <div style="flex:1"><label class="input-label" style="color:#166534">FC (LPM)</label><input id="modal-fc" type="number" style="border-color:#bbf7d0;"></div>
                        <div style="flex:1"><label class="input-label" style="color:#166534">FR (RPM)</label><input id="modal-fr" type="number" style="border-color:#bbf7d0;"></div>
                    </div>
                </div>

                <div class="section-title">Derivaciones</div>
                <div style="background:#f8fafc; padding:15px; border-radius:12px; border:1px solid #e2e8f0; margin-bottom:1.5rem;">
                    <div class="flex gap-4 mb-3" style="align-items:center; flex-wrap:wrap"><label class="flex gap-2 items-center" style="font-size:0.9rem; font-weight:600;"><input type="checkbox" id="check-re" style="width:18px; height:18px;"> Reconsulta</label><select id="re-esp" style="flex:1"><option value="">Especialidad...</option></select><input type="date" id="re-fecha" style="width:140px"></div>
                    <div class="flex gap-4" style="align-items:center; flex-wrap:wrap"><label class="flex gap-2 items-center" style="font-size:0.9rem; font-weight:600;"><input type="checkbox" id="check-int" style="width:18px; height:18px;"> Interconsulta</label><select id="int-esp" style="flex:1"><option value="">Especialidad...</option></select></div>
                </div>
                
                <div class="section-title">Plan de Tratamiento</div>
                <div class="mb-3"><label class="input-label" style="color:var(--primary)">Inicio de Tratamiento</label><input type="date" id="trat-fecha-ini"></div>
                
                <div class="mb-3">
                    <label class="input-label" style="color:#d32f2f; font-weight:700;"><i class="ph ph-file-text"></i> Foto de Receta (Obligatorio) *</label>
                    <input type="file" id="modal-receta" accept="image/*,application/pdf" class="form-control" style="padding:10px; border:1px solid #cbd5e1; width:100%; border-radius:8px;">
                </div>

                <div class="add-treatment-box">
                    <div class="treatment-row">
                        <div>
                            <label class="input-label">Tipo</label>
                            <select id="add-tipo" onchange="actualizarDatalist()">
                                <option value="SUPLEMENTO">Suplemento</option>
                                <option value="PROCEDIMIENTO">Procedimiento</option>
                                <option value="MEDICAMENTO">Medicamento</option>
                            </select>
                        </div>
                        <div style="flex-grow: 2;">
                            <label class="input-label">Producto</label>
                            <input list="dl-prods" id="add-prod" placeholder="Nombre...">
                            <datalist id="dl-prods"></datalist>
                        </div>
                        <div><label class="input-label">Dosis</label><select id="add-dosis"><option value="Diario">Diario</option><option value="Interdiario">Interdiario</option><option value="Semanal">Semanal</option><option value="Quincenal">Quincenal</option><option value="Mensual">Mensual</option></select></div>
                        <div style="flex: 0 0 60px;"><label class="input-label">Rep.</label><input type="number" id="add-rep" value="1" min="1"></div>
                        <button class="btn btn-primary btn-add" style="height: 42px; width: 42px; padding: 0;" onclick="agregarTratamientoUI()"><i class="ph ph-plus"></i></button>
                    </div>
                </div>

                <ul id="lista-tratamientos-ui" class="treatment-list"></ul>

                <div class="section-title">Observaciones</div>
                <textarea id="modal-obs" rows="3" placeholder="Notas clínicas adicionales..." style="resize:none; padding:15px;"></textarea>
            </div>
            
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                <button class="btn btn-primary" onclick="guardarAtencion()"><i class="ph ph-floppy-disk text-lg"></i> Guardar y Finalizar</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="modal-catalogo">
        <div class="modal modal-xl-custom">
            <div class="modal-header">
                <div>
                    <h2 class="merry-font" style="font-size:1.5rem; margin:0; color:var(--primary)">Tarifario Médico</h2>
                    <p style="font-size:0.9rem; color:var(--gray); margin-top:4px;">Precios y horarios referenciales</p>
                </div>
                <button class="btn btn-secondary btn-icon" onclick="cerrarCatalogo()"><i class="ph ph-x text-lg"></i></button>
            </div>
            <div class="modal-body">
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:10px; margin-bottom: 15px;">
                    <select id="filtro-cat-sede" onchange="filtrarCatalogo()" style="width: 100%;">
                        <option value="">Todas las Sedes</option>
                    </select>
                    <select id="filtro-cat-esp" onchange="filtrarCatalogo()" style="width: 100%;">
                        <option value="">Todas las Especialidades</option>
                    </select>
                    <select id="filtro-cat-doc" onchange="filtrarCatalogo()" style="width: 100%;">
                        <option value="">Todos los Doctores</option>
                    </select>
                </div>

                <div style="margin-bottom: 15px;">
                    <input type="text" id="buscar-precio" placeholder="🔍 Buscar servicio específico..." 
                           onkeyup="filtrarCatalogo()" 
                           style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1;">
                </div>

                <div class="table-container">
                    <table id="tabla-precios">
                        <thead>
                            <tr>
                                <th>Sede</th>
                                <th>Especialidad</th>
                                <th>Doctor</th>
                                <th>Servicio</th>
                                <th>Horario</th>
                                <th style="text-align:right; color:#64748b">Costo (S/)</th>
                                <th style="text-align:right; color:var(--green)">P.V.P (S/)</th>
                            </tr>
                        </thead>
                        <tbody id="body-precios">
                            </tbody>
                    </table>
                </div>
            </div>
            
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="cerrarCatalogo()">Cerrar</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="modal-seguimiento-global">
        <div class="modal modal-xl-custom">
            <div class="modal-header" style="background:#f0fdf4; border-bottom:2px solid var(--green);">
                <div>
                    <h2 class="merry-font" style="font-size:1.4rem; margin:0; color:var(--green)">
                        <i class="ph ph-pill"></i> Seguimiento Global
                    </h2>
                    <p style="font-size:0.85rem; color:var(--gray); margin-top:2px;">Gestión de Tratamientos, Reconsultas e Interconsultas.</p>
                </div>
                <button class="btn btn-secondary btn-icon" onclick="cerrarSeguimientoGlobal()"><i class="ph ph-x text-lg"></i></button>
            </div>
            
            <div class="modal-body" style="padding:0; background:#f8fafc;">
                
                <div style="padding:15px; border-bottom:1px solid #e2e8f0; background:white;">
                    
                    <div class="mini-stats">
                        <div class="mini-card"><span class="mc-val" id="count-total">0</span><span class="mc-lbl">Total</span></div>
                        <div class="mini-card"><span class="mc-val" id="count-pending" style="color:#eab308">0</span><span class="mc-lbl">Pendientes</span></div>
                        <div class="mini-card"><span class="mc-val" id="count-gestion" style="color:#3b82f6">0</span><span class="mc-lbl">Gestión</span></div>
                        <div class="mini-card"><span class="mc-val" id="count-done" style="color:#16a34a">0</span><span class="mc-lbl">Listos</span></div>
                        <div class="mini-card" style="border-color:#fecaca;"><span class="mc-val" id="count-cancelled" style="color:#dc2626">0</span><span class="mc-lbl">Cancelados</span></div>
                    </div>

                    <div class="btn-group w-100 mb-3" role="group" style="box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-radius: 8px; overflow: hidden;">
                        <button type="button" class="btn btn-primary active" id="tab-btn-suple" onclick="switchTab('suple')" style="flex:1; border-radius:0;">💊 Supl/Med</button>
                        <button type="button" class="btn btn-secondary" id="tab-btn-proc" onclick="switchTab('proc')" style="flex:1; border-radius:0;">💉 Proced.</button>
                        <button type="button" class="btn btn-secondary" id="tab-btn-re" onclick="switchTab('re')" style="flex:1; border-radius:0;">🔄 Reconsultas</button>
                        <button type="button" class="btn btn-secondary" id="tab-btn-int" onclick="switchTab('int')" style="flex:1; border-radius:0;">📋 Intercon.</button>
                    </div>

                    <div class="filters-global-container">
                        
                        <div style="flex: 2; min-width: 250px; position: relative;">
                            <i class="ph ph-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #64748b;"></i>
                            <input id="filtro-paciente" placeholder="Buscar paciente..." 
                                   style="width: 100%; padding: 0.5rem 0.5rem 0.5rem 35px; border-radius: 8px; border: 1px solid #cbd5e1; outline: none;" 
                                   onkeyup="aplicarFiltrosGlobales()">
                        </div>

                        <div class="filter-item-flex">
                            <select id="filtro-estado" style="width: 100%; padding: 0.5rem; border-radius: 8px; border: 1px solid #cbd5e1; outline: none; background: white;" onchange="aplicarFiltrosGlobales()">
                                <option value="">Estado: Todos</option>
                                <option value="PENDIENTE">Pendiente</option>
                                <option value="GESTION">En Gestión</option>
                                <option value="REALIZADO">Realizado</option>
                                <option value="CANCELADO">Cancelado</option>
                            </select>
                        </div>

                        <div class="filter-item-flex">
                            <select id="filtro-doc-global" style="width: 100%; padding: 0.5rem; border-radius: 8px; border: 1px solid #cbd5e1; outline: none; background: white;" onchange="aplicarFiltrosGlobales()">
                                <option value="">Doctor: Todos</option>
                            </select>
                        </div>

                        <div class="filter-item-flex">
                            <select id="filtro-prod-global" style="width: 100%; padding: 0.5rem; border-radius: 8px; border: 1px solid #cbd5e1; outline: none; background: white;" onchange="aplicarFiltrosGlobales()">
                                <option value="">Producto: Todos</option>
                            </select>
                        </div>

                        <div style="width: auto;">
                            <input type="month" id="filtro-mes-seg" style="padding: 0.45rem; border-radius: 8px; border: 1px solid #cbd5e1; outline: none;" onchange="cargarTratamientosSede()">
                        </div>

                        <button class="btn btn-secondary btn-sm" onclick="cargarTratamientosSede()" title="Actualizar lista" style="height: 38px; width: 38px; padding: 0; display: flex; align-items: center; justify-content: center;">
                            <i class="ph ph-arrows-clockwise" style="font-size: 1.2rem;"></i>
                        </button>
                    </div>
                </div>

                <div class="table-container" style="height: 500px; overflow-y: auto;">
                    
                    <div id="view-suple" style="display:block;">
                        <table style="width:100%">
                            <thead style="background:#f1f5f9; position:sticky; top:0; z-index:10;">
                                <tr>
                                    <th>Sede</th>
                                    <th style="padding:10px;">Fecha</th>
                                    <th>Paciente</th>
                                    <th>Producto / Dosis</th>
                                    <th>Dr. Origen</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Evidencias</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="body-suple"></tbody>
                        </table>
                    </div>

                    <div id="view-proc" style="display:none;">
                        <table style="width:100%">
                            <thead style="background:#fff7ed; position:sticky; top:0; z-index:10;">
                                <tr>
                                    <th>Sede</th>
                                    <th style="padding:10px;">Fecha</th>
                                    <th>Paciente</th>
                                    <th>Procedimiento</th>
                                    <th>Dr. Origen</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Evidencias</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="body-proc"></tbody>
                        </table>
                    </div>

                    <div id="view-re" style="display:none;">
                        <table style="width:100%">
                            <thead style="background:#e0f2fe; position:sticky; top:0; z-index:10;">
                                <tr>
                                    <th>Sede</th>
                                    <th style="padding:10px;">Fecha Prog.</th>
                                    <th>Paciente</th>
                                    <th>Especialidad Retorno</th>
                                    <th>Dr. Origen</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Gestión WSP</th>
                                    <th class="text-center">Confirmar</th>
                                </tr>
                            </thead>
                            <tbody id="body-re"></tbody>
                        </table>
                    </div>

                    <div id="view-int" style="display:none;">
                        <table style="width:100%">
                            <thead style="background:#fef3c7; position:sticky; top:0; z-index:10;">
                                <tr>
                                    <th>Sede</th>
                                    <th style="padding:10px;">Fecha Solicitud</th>
                                    <th>Paciente</th>
                                    <th>Especialidad Destino</th>
                                    <th>Dr. Origen</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Gestión WSP</th>
                                    <th class="text-center">Derivación</th>
                                </tr>
                            </thead>
                            <tbody id="body-int"></tbody>
                        </table>
                    </div>

                </div>
            </div>
            
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="cerrarSeguimientoGlobal()">Cerrar</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="modal-preview" style="z-index: 3000;">
        <div class="modal-card" style="width: 95%; max-width: 1000px; height: 90vh; display: flex; flex-direction: column; background: #000; box-shadow: 0 0 30px rgba(0,0,0,0.5);">
            <div class="preview-header-bar">
                <h3 style="margin:0; font-size: 1.2rem; color: #fff;">👁️ Vista Previa</h3>
                <div style="display:flex; gap: 10px;">
                    <a id="btn-download-preview" href="#" download class="btn btn-primary" style="padding: 5px 15px; font-size: 0.8rem;">⬇ Descargar</a>
                    <button class="btn btn-secondary" onclick="cerrarPreview()" style="padding: 5px 15px; font-size: 0.8rem; background: #444; color: white; border: none;">✖ Cerrar</button>
                </div>
            </div>
            <div class="modal-body" style="flex: 1; padding: 0; display: flex; align-items: center; justify-content: center; overflow: hidden; background: #1a1a1a;">
                <div id="preview-content" style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;"></div>
            </div>
        </div>
    </div>

    <div id="toast-container"></div>

    <script>
        const USER_ID = <?php echo $user_id; ?>;
        const USER_ROL = "<?php echo $user_rol; ?>";
        let SEDES_NOM = { 1: "Sede Huacho", 2: "Sede Huaura", 3: "Sede Medio Mundo" };
        let DB_CITAS=[], DB_GESTION=[], calendar, CURRENT_SEDE=1, ALL_DOCTORS=[];
        let DATA_CATALOGO = [];
        let DATA_INVENTARIO = {}; 
        let TRATAMIENTOS_TEMP = []; 
        let tempTratamientoId = null; 
        let RAW_TRATAMIENTOS = [];

        async function initApp() {
            try {
                const r = await fetch(`get_sede_actual.php?id=${USER_ID}`);
                const d = await r.json();
                if(d.sede_id) {
                    CURRENT_SEDE = d.sede_id;
                    document.getElementById('sede-badge').innerText = d.nombre || ("Sede: " + CURRENT_SEDE);
                }
                const rol = USER_ROL.toLowerCase();
                if(rol.includes('admin') || rol.includes('gerente')) {
                    const container = document.getElementById('div-sede-filter');
                    if(container) container.classList.remove('hidden');
                }
            } catch(e) {}

            document.getElementById('fecha-cita').valueAsDate = new Date();
            generarHorarios();
            await cargarDatosPublicos(); 
            await cargarCatalogoSilencioso();

            const hoy = new Date();
            calendar = flatpickr("#rango-fechas", { 
                mode:"range", 
                dateFormat:"Y-m-d", 
                defaultDate:[hoy, hoy], 
                locale:"es", 
                theme:"airbnb", 
                onChange:()=>aplicarFiltros() 
            });
            await aplicarFiltros();
        }

        // --- LÓGICA DE REPROGRAMACIÓN ---
        function verificarReprogramacion() {
            const estado = document.getElementById('modal-estado').value;
            const esReprogramado = (estado === 'REPROGRAMADO');
            
            const inputFecha = document.getElementById('modal-fecha');
            const inputHora = document.getElementById('modal-hora-cita');
            
            inputFecha.disabled = !esReprogramado;
            inputHora.disabled = !esReprogramado;
            
            if(esReprogramado) {
                inputFecha.style.backgroundColor = '#fff';
            } else {
                inputFecha.style.backgroundColor = '#f3f4f6';
            }
        }

        function getSedeFiltro() {
            const adminSel = document.getElementById('admin-sede-select');
            const wrapper = document.getElementById('div-sede-filter');
            if(wrapper && !wrapper.classList.contains('hidden') && adminSel) {
                return adminSel.value; 
            }
            return CURRENT_SEDE;
        }

        function cambiarSedeAdmin() {
            aplicarFiltros(); 
            if(document.getElementById('modal-seguimiento-global').classList.contains('show')) {
                cargarTratamientosSede();
            }
        }

        // --- CÓDIGO EXISTENTE ---
        async function cargarCatalogoSilencioso() { try { const r = await fetch('admin_catalogo_backend.php?action=list'); DATA_CATALOGO = await r.json(); } catch(e) { console.log("No se pudo cargar horarios del catalogo"); } }
        function formatoDoctor(nombre) { if(!nombre) return '-'; let limpio = nombre.replace(/^Dr\.\s*/i, '').replace(/^Dra\.\s*/i, ''); const partes = limpio.trim().split(/\s+/); const cap = (s) => s.charAt(0).toUpperCase() + s.slice(1).toLowerCase(); if (partes.length > 2) return `Dr. ${cap(partes[0])} ${cap(partes[partes.length - 2])}`; else if (partes.length === 2) return `Dr. ${cap(partes[0])} ${cap(partes[1])}`; else return `Dr. ${cap(partes[0])}`; }
        async function cargarDatosPublicos() { try { const r = await fetch('public_get_data.php'); const d = await r.json(); const fills = [document.getElementById('esp-cita'), document.getElementById('filtro-esp'), document.getElementById('re-esp'), document.getElementById('int-esp')]; fills.forEach(sel => { if(!sel) return; const def = sel.id === 'filtro-esp' ? 'Todas' : 'Seleccione...'; sel.innerHTML = `<option value="">${def}</option>`; d.especialidades.forEach(e => sel.add(new Option(e.nombre, e.nombre))); }); ALL_DOCTORS = d.doctores; DATA_INVENTARIO = { 'SUPLEMENTO': d.suplementos || [], 'PROCEDIMIENTO': d.procedimientos || [], 'MEDICAMENTO': d.medicamentos || [] }; actualizarDatalist(); if(d.sedes) { SEDES_NOM = {}; d.sedes.forEach(s => SEDES_NOM[s.id] = s.nombre); } } catch(e) {} }
        function actualizarDatalist() { const tipo = document.getElementById('add-tipo').value; const dl = document.getElementById('dl-prods'); dl.innerHTML = ''; const items = DATA_INVENTARIO[tipo] || []; items.forEach(x => { const op = document.createElement('option'); op.value = x.nombre; dl.appendChild(op); }); document.getElementById('add-prod').value = ''; }
        function filtrarDoctores() { const esp = document.getElementById('esp-cita').value; const sel = document.getElementById('doc-cita'); sel.innerHTML = '<option value="">-- Seleccione --</option>'; const filtrados = ALL_DOCTORS.filter(d => d.nombre_esp === esp); filtrados.forEach(d => { const opt = document.createElement('option'); opt.value = d.nombre_completo; opt.text = formatoDoctor(d.nombre_completo); sel.appendChild(opt); }); }
        function cargarDoctoresFiltro() { const esp = document.getElementById('filtro-esp').value; const sel = document.getElementById('filtro-doc'); sel.innerHTML = '<option value="">Todos</option>'; const filtrados = (esp === '' || esp === 'Todas') ? ALL_DOCTORS : ALL_DOCTORS.filter(d => d.nombre_esp === esp); filtrados.forEach(d => { const opt = document.createElement('option'); opt.value = d.nombre_completo; opt.text = formatoDoctor(d.nombre_completo); sel.appendChild(opt); }); aplicarFiltros(); }

        async function aplicarFiltros() {
            const f=calendar.selectedDates; let i='',n=''; 
            if(f.length>0){i=f[0].toISOString().split('T')[0];n=f.length===2?f[1].toISOString().split('T')[0]:i;}
            const esp = document.getElementById('filtro-esp').value;
            const doc = document.getElementById('filtro-doc').value;
            const sede = getSedeFiltro();
            const p=new URLSearchParams({inicio:i, fin:n, esp:esp, doc:doc, sede:sede});
            try { 
                const r1=await fetch(`listar_citas.php?${p.toString()}`); DB_CITAS=await r1.json(); renderTableCitas(); 
                const r2=await fetch(`listar_gestion.php?${p.toString()}`); DB_GESTION=await r2.json(); renderTableGestion(); 
                actualizarStats(); 
            } catch(e){ showToast('error','Error listas'); }
        }

        function enviarWhatsApp(d) { 
            if(!d.telefono || d.telefono.length < 9) return; 
            const payload = {
                telefono: d.telefono,
                paciente: d.nombres + ' ' + d.apellidos,
                fecha: d.fecha,
                hora: d.hora,
                especialidad: d.especialidad,
                doctor: formatoDoctor(d.doctor_nombre),
                sede_id: CURRENT_SEDE 
            };
            fetch('enviar_wsp.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload) }); 
        }

        async function guardarCita() { 
            const selDoc = document.getElementById('doc-cita');
            const docName = selDoc.value !== '-- Seleccione --' ? selDoc.value : '';
            let sedeAgenda = CURRENT_SEDE;
            const adminSelect = document.getElementById('admin-sede-select');
            const wrapper = document.getElementById('div-sede-filter');
            if(wrapper && !wrapper.classList.contains('hidden') && adminSelect.value !== "") {
                sedeAgenda = adminSelect.value;
            }
            const data={
                ticket:document.getElementById('ticket-input').value, 
                dni:document.getElementById('dni-input').value, 
                apellidos:document.getElementById('ape-input').value, 
                nombres:document.getElementById('nom-input').value, 
                telefono:document.getElementById('tel-input').value, 
                hc:document.getElementById('hc-input').value, 
                fecha:document.getElementById('fecha-cita').value, 
                hora:document.getElementById('hora-cita').value, 
                especialidad:document.getElementById('esp-cita').value, 
                doctor:docName, doctor_nombre:docName, tipo:document.getElementById('tipo-cita').value, 
                consultorio:document.getElementById('consultorio-cita').value, sede_id: sedeAgenda
            };
            if(!data.ticket||!data.dni)return alert('Datos faltantes');
            try{
                const r=await fetch('guardar_cita.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
                const res=await r.json();
                if(res.success){enviarWhatsApp(data);limpiarForm('cita');limpiarForm('paciente');aplicarFiltros();showToast('success','Guardado');}
                else alert(res.message);
            }catch(e){showToast('error','Error red');}
        }

        function actualizarStats() { const t=[...DB_CITAS,...DB_GESTION]; document.getElementById('stat-total').innerText=t.length; document.getElementById('stat-atendidos').innerText=t.filter(c=>c.estado==='ATENDIDO').length; document.getElementById('stat-cancelados').innerText=t.filter(c=>c.estado==='CANCELADO').length; document.getElementById('stat-pacientes').innerText=new Set(t.map(c=>c.paciente||c.nom)).size; }
        
        function renderTableCitas() {
            const container = document.getElementById('accordion-citas-container'); container.innerHTML = '';
            const agrupado = {};
            DB_CITAS.forEach(c => { const sId = c.sede_id || '0'; if(!agrupado[sId]) agrupado[sId] = []; agrupado[sId].push(c); });
            if(Object.keys(agrupado).length === 0) { container.innerHTML = '<div style="text-align:center; padding:20px; color:#64748b;">No hay citas pendientes.</div>'; return; }
            Object.keys(agrupado).forEach(sedeId => {
                const lista = agrupado[sedeId]; const nombreSede = SEDES_NOM[sedeId] || 'Sede '+sedeId;
                const details = document.createElement('details'); details.className = 'sede-accordion'; details.open = true;
                let rows = '';
                lista.forEach((c, ix) => {
                    const badgeClass = c.estado === 'REPROGRAMADO' ? 'warning' : 'prog';
                    rows += `<tr onclick="openModal(${c.id})"><td>${ix+1}</td><td>${c.fecha}<br><small style="color:var(--gray)">${c.hora}</small></td><td><span class="badge ${badgeClass}">${c.estado}</span></td><td style="font-weight:600;">${c.nom}</td><td>${c.tel||'-'}<br><b>${c.hc||'-'}</b></td><td>${formatoDoctor(c.doc)}</td></tr>`;
                });
                details.innerHTML = `<summary class="sede-summary"><span>📍 ${nombreSede}</span><span class="count-badge">${lista.length} citas</span></summary><div class="table-container"><table><thead><tr><th>#</th><th>Fecha/Hora</th><th>Estado</th><th>Paciente</th><th>HC</th><th>Doctor</th></tr></thead><tbody>${rows}</tbody></table></div>`;
                container.appendChild(details);
            });
        }

        function renderTableGestion() {
            const container = document.getElementById('accordion-gestion-container'); container.innerHTML = '';
            const agrupado = {};
            DB_GESTION.forEach(g => { const sId = g.sede_id || '0'; if(!agrupado[sId]) agrupado[sId] = []; agrupado[sId].push(g); });
            if(Object.keys(agrupado).length === 0) { container.innerHTML = '<div style="text-align:center; padding:20px; color:#64748b;">No hay atenciones finalizadas.</div>'; return; }
            Object.keys(agrupado).forEach(sedeId => {
                const lista = agrupado[sedeId]; const nombreSede = SEDES_NOM[sedeId] || 'Sede '+sedeId;
                const details = document.createElement('details'); details.className = 'sede-accordion';
                let rows = '';
                lista.forEach((g, ix) => {
                    const badgeClass = g.estado === 'ATENDIDO' ? 'atendido' : 'cancel';
                    rows += `<tr><td>${ix+1}</td><td>${g.fecha}<br><small>${g.hora}</small></td><td><span class="badge ${badgeClass}">${g.estado}</span></td><td style="font-weight:600;">${g.paciente}</td><td>${g.triaje||'-'}</td><td>${formatoDoctor(g.doc)}</td></tr>`;
                });
                details.innerHTML = `<summary class="sede-summary"><span>✅ ${nombreSede}</span><span class="count-badge">${lista.length} reg.</span></summary><div class="table-container"><table><thead><tr><th>#</th><th>Fecha</th><th>Estado</th><th>Paciente</th><th>Triaje</th><th>Doctor</th></tr></thead><tbody>${rows}</tbody></table></div>`;
                container.appendChild(details);
            });
        }

        function generarHorarios() { const s=document.getElementById('hora-cita'); s.innerHTML=''; let st=8*60; while(st<=22*60){const h=Math.floor(st/60),m=st%60,t=`${h.toString().padStart(2,'0')}:${m.toString().padStart(2,'0')}`;s.add(new Option(t,t));st+=15;}}
        function toggleLock(id,b){const e=document.getElementById(id);if(e.hasAttribute('readonly')){e.removeAttribute('readonly');b.querySelector('i').classList.replace('ph-lock-key','ph-lock-key-open');e.focus();}else{e.setAttribute('readonly',true);b.querySelector('i').classList.replace('ph-lock-key-open','ph-lock-key');}}
        async function buscarDNI() { const d = document.getElementById('dni-input').value; if (d.length !== 8) { alert("Ingrese un DNI de 8 dígitos"); return; } try { const r = await fetch(`proxy.php?dni=${d}`); const j = await r.json(); const i = j.data || j; if (i.nombres) { document.getElementById('nom-input').value = i.nombres; document.getElementById('ape-input').value = (i.apellido_paterno + ' ' + i.apellido_materno).trim(); } else { alert("No encontrado en RENIEC"); } } catch(e) {} try { const r2 = await fetch(`api_historial_paciente.php?dni=${d}`); const dataHist = await r2.json(); if (dataHist) { if(dataHist.telefono) document.getElementById('tel-input').value = dataHist.telefono; if(dataHist.historia_clinica) document.getElementById('hc-input').value = dataHist.historia_clinica; } } catch(e) {} }
        
        function openModal(id) { 
            const c=DB_CITAS.find(x=>x.id==id); 
            if(!c)return; 
            
            document.getElementById('modal-id').value = id; 
            document.getElementById('modal-paciente-info').innerText=c.nom; 
            document.getElementById('modal-ticket').value=c.ticket; 
            document.getElementById('modal-hc').value=c.hc; 
            
            // --- NUEVO: Cargar Fecha y Hora para reprogramación ---
            document.getElementById('modal-fecha').value = c.fecha;
            
            // Llenar selector de hora si está vacío
            const selHora = document.getElementById('modal-hora-cita');
            if(selHora.options.length === 0) {
                let st = 8 * 60; 
                while (st <= 22 * 60) {
                    const h = Math.floor(st / 60).toString().padStart(2, '0');
                    const m = (st % 60).toString().padStart(2, '0');
                    const t = `${h}:${m}`;
                    selHora.add(new Option(t, t));
                    st += 15;
                }
            }
            // Seleccionar hora exacta (solo HH:MM)
            const horaLimpia = (c.hora || '00:00').substring(0, 5);
            selHora.value = horaLimpia;

            document.getElementById('modal-estado').value = (c.estado === 'PROGRAMADO' ? 'ATENDIDO' : c.estado); 
            
            // Limpiar campos de atención
            ['modal-peso','modal-talla','modal-presion','modal-temp','modal-sat','modal-fc','modal-fr','modal-receta'].forEach(k => document.getElementById(k).value = '');
            document.getElementById('trat-fecha-ini').value = c.fecha; 
            
            TRATAMIENTOS_TEMP = []; 
            renderTratamientosList(); 
            
            // Verificar estado inicial para habilitar/deshabilitar
            verificarReprogramacion();

            document.getElementById('modal-detalle').classList.add('show'); 
        }

        function closeModal(){ document.getElementById('modal-detalle').classList.remove('show'); }
        function agregarTratamientoUI() { const tipo = document.getElementById('add-tipo').value; const prod = document.getElementById('add-prod').value; const dosis = document.getElementById('add-dosis').value; const rep = parseInt(document.getElementById('add-rep').value) || 1; const ini = document.getElementById('trat-fecha-ini').value; if(!prod || !ini) return alert("Ingrese producto y fecha de inicio"); let base = new Date(ini + 'T00:00:00'); let fechas = []; for (let i = 0; i < rep; i++) { let n = new Date(base); if (dosis === 'Diario') n.setDate(base.getDate() + (1 * i)); else if (dosis === 'Interdiario') n.setDate(base.getDate() + (2 * i)); else if (dosis === 'Semanal') n.setDate(base.getDate() + (7 * i)); else if (dosis === 'Quincenal') n.setDate(base.getDate() + (15 * i)); else if (dosis === 'Mensual') n.setMonth(base.getMonth() + (1 * i)); const iso = n.toISOString().split('T')[0]; fechas.push(iso); } TRATAMIENTOS_TEMP.push({ tipo, producto: prod, dosis, rep, fechas: fechas.join(',') }); renderTratamientosList(); document.getElementById('add-prod').value = ''; document.getElementById('add-rep').value = '1'; }
        function renderTratamientosList() { const ul = document.getElementById('lista-tratamientos-ui'); ul.innerHTML = ''; TRATAMIENTOS_TEMP.forEach((t, index) => { const li = document.createElement('li'); li.className = 'treatment-item'; const chips = t.fechas.split(',').map(f => `<span class="date-chip">${f}</span>`).join(''); li.innerHTML = `<div class="t-header"><strong>${t.tipo}: ${t.producto}</strong><span style="font-size:0.8rem; color:#64748b">${t.dosis} x ${t.rep}</span></div><div class="t-dates">${chips}</div><i class="ph ph-trash btn-remove-item" onclick="removeTratamiento(${index})"></i>`; ul.appendChild(li); }); }
        function removeTratamiento(idx) { TRATAMIENTOS_TEMP.splice(idx, 1); renderTratamientosList(); }
        
        // --- GUARDAR ATENCIÓN Y REPROGRAMACIÓN ---
        async function guardarAtencion() { 
            const idVal = document.getElementById('modal-id').value; if(!idVal) return alert("Error interno: No hay ID de cita.");
            const estadoVal = document.getElementById('modal-estado').value; const fileInput = document.getElementById('modal-receta');
            
            // Validar Receta: Solo obligatoria si es ATENDIDO
            if (estadoVal === 'ATENDIDO' && fileInput.files.length === 0) { 
                alert("⚠️ ATENCIÓN:\n\nDebe subir la FOTO DE LA RECETA obligatoriamente."); 
                fileInput.focus(); fileInput.style.border = "2px solid #ef4444"; return; 
            } else { fileInput.style.border = "1px solid #cbd5e1"; }
            
            const fd = new FormData(); 
            fd.append('cita_id', idVal); 
            fd.append('estado', estadoVal); 
            
            // --- ENVIAR FECHA Y HORA ---
            fd.append('fecha', document.getElementById('modal-fecha').value);
            fd.append('hora', document.getElementById('modal-hora-cita').value);

            fd.append('ticket', document.getElementById('modal-ticket').value); 
            fd.append('hc', document.getElementById('modal-hc').value); 
            fd.append('triaje', document.getElementById('modal-triaje').value); 
            fd.append('peso', document.getElementById('modal-peso').value); 
            fd.append('talla', document.getElementById('modal-talla').value); 
            fd.append('presion', document.getElementById('modal-presion').value); 
            fd.append('temperatura', document.getElementById('modal-temp').value); 
            fd.append('saturacion', document.getElementById('modal-sat').value); 
            fd.append('fc', document.getElementById('modal-fc').value); 
            fd.append('fr', document.getElementById('modal-fr').value); 
            fd.append('obs', document.getElementById('modal-obs').value); 
            fd.append('re_check', document.getElementById('check-re').checked ? 1 : 0); 
            fd.append('re_esp', document.getElementById('re-esp').value); 
            fd.append('re_fecha', document.getElementById('re-fecha').value); 
            fd.append('int_check', document.getElementById('check-int').checked ? 1 : 0); 
            fd.append('int_esp', document.getElementById('int-esp').value); 
            fd.append('trat_fecha_ini', document.getElementById('trat-fecha-ini').value); 
            fd.append('lista_tratamientos', JSON.stringify(TRATAMIENTOS_TEMP));
            
            if(fileInput.files.length > 0) fd.append('receta', fileInput.files[0]);
            
            try { 
                const r = await fetch('guardar_atencion.php', { method:'POST', body:fd }); 
                const d = await r.json(); 
                if(d.success) { 
                    closeModal(); 
                    aplicarFiltros(); 
                    showToast('success','Finalizado'); 

                    // --- ENVIAR WSP SI ES REPROGRAMACIÓN ---
                    if (estadoVal === 'REPROGRAMADO') {
                        const citaOriginal = DB_CITAS.find(x => x.id == idVal);
                        if (citaOriginal) {
                            const payloadWsp = {
                                telefono: citaOriginal.tel, 
                                paciente: citaOriginal.nom, 
                                fecha: document.getElementById('modal-fecha').value, 
                                hora: document.getElementById('modal-hora-cita').value,
                                especialidad: citaOriginal.esp,
                                doctor: formatoDoctor(citaOriginal.doc),
                                sede_id: CURRENT_SEDE 
                            };
                            fetch('enviar_wsp.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payloadWsp) });
                            showToast('success', '📅 Notificación enviada');
                        }
                    }
                } else alert(d.message); 
            } catch(e) { alert("Error al guardar"); } 
        }

        function showToast(t,m){const d=document.createElement('div');d.className='toast';d.innerHTML=`<span>${m}</span>`;d.style.borderLeftColor=t==='error'?'#ef4444':'#10b981';document.getElementById('toast-container').appendChild(d);setTimeout(()=>d.remove(),3000);}
        function limpiarForm(t){if(t==='paciente')['dni-input','ape-input','nom-input','tel-input','hc-input'].forEach(i=>document.getElementById(i).value='');else{document.getElementById('ticket-input').value='';document.getElementById('esp-cita').value=''}}
        async function quickUpdate(campo) { const id = document.getElementById('modal-id').value; const inputId = campo === 'ticket' ? 'modal-ticket' : 'modal-hc'; const valor = document.getElementById(inputId).value; if(!id) return; const inputEl = document.getElementById(inputId); const originalBorder = inputEl.style.borderColor; inputEl.style.borderColor = "#fbbf24"; try { const res = await fetch('api_quick_update.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ id: id, campo: campo, valor: valor }) }); const data = await res.json(); if(data.success) { inputEl.style.borderColor = "#10b981"; showToast('success', 'Actualizado: ' + (campo==='ticket'?'Ticket':'Historia')); const citaLocal = DB_CITAS.find(x => x.id == id); if(citaLocal) { if(campo === 'ticket') citaLocal.ticket = valor; if(campo === 'historia_clinica') citaLocal.hc = valor; renderTableCitas(); } } else { inputEl.style.borderColor = "#ef4444"; alert("Error: " + data.message); } } catch(e) { inputEl.style.borderColor = "#ef4444"; } finally { setTimeout(() => { inputEl.style.borderColor = originalBorder || '#e2e8f0'; }, 1500); } }
        async function abrirCatalogo() { const m = document.getElementById('modal-catalogo'); document.getElementById('body-precios').innerHTML='<tr><td colspan="7" style="text-align:center">Cargando...</td></tr>'; m.classList.add('show'); try{const r=await fetch('admin_catalogo_backend.php?action=list');DATA_CATALOGO=await r.json();llenarFiltrosCatalogo(DATA_CATALOGO);renderizarCatalogo(DATA_CATALOGO);}catch(e){}}
        function llenarFiltrosCatalogo(d){const s=new Set(),e=new Set(),doct=new Set();d.forEach(i=>{if(i.sede_nombre)s.add(i.sede_nombre);if(i.esp_nombre)e.add(i.esp_nombre);if(i.doc_nombre)doct.add(i.doc_nombre)});const f=(i,v)=>{const el=document.getElementById(i);el.innerHTML=`<option value="">${el.options[0].text}</option>`;Array.from(v).sort().forEach(x=>el.add(new Option(x,x)))};f('filtro-cat-sede',s);f('filtro-cat-esp',e);f('filtro-cat-doc',doct);}
        function cerrarCatalogo(){document.getElementById('modal-catalogo').classList.remove('show');}
        function renderizarCatalogo(l){const b=document.getElementById('body-precios');b.innerHTML='';if(l.length===0){b.innerHTML='<tr><td colspan="7">No hay datos</td></tr>';return;}l.forEach(i=>{const tr=document.createElement('tr');tr.innerHTML=`<td>${i.sede_nombre||'-'}</td><td>${i.esp_nombre||'-'}</td><td>${formatoDoctor(i.doc_nombre)}</td><td>${i.tipo_nombre||'Consulta'}</td><td>${i.horario_referencial||'-'}</td><td align="right">S/ ${i.precio_costo}</td><td align="right">S/ ${i.precio_venta}</td>`;b.appendChild(tr);});}
        function filtrarCatalogo(){const s=document.getElementById('filtro-cat-sede').value,e=document.getElementById('filtro-cat-esp').value,d=document.getElementById('filtro-cat-doc').value,t=document.getElementById('buscar-precio').value.toLowerCase();const f=DATA_CATALOGO.filter(i=>{return(s===""||(i.sede_nombre||'')===s)&&(e===""||(i.esp_nombre||'')===e)&&(d===""||(i.doc_nombre||'')===d)&&(t===""||(i.tipo_nombre||'').toLowerCase().includes(t)||(i.doc_nombre||'').toLowerCase().includes(t)||(i.esp_nombre||'').toLowerCase().includes(t));});renderizarCatalogo(f);}
        async function guardarEdicionItem(id) { const row = document.querySelector(`tr[data-id="${id}"]`); if(!row) return; const fechaVal = row.querySelector('.edit-date').value; const detVal = row.querySelector('.edit-prod').value; try { const r = await fetch('api_agendar_seguimiento.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ id: id, fecha: fechaVal, detalle: detVal }) }); const d = await r.json(); if(d.success) showToast('success', 'Actualizado'); else alert(d.error); } catch(e) { alert("Error"); } }
        const fileInput = document.getElementById('hidden-file-input'); fileInput.addEventListener('change', async (e) => { if(e.target.files.length > 0 && tempTratamientoId) { const fd = new FormData(); fd.append('archivo', e.target.files[0]); fd.append('id', tempTratamientoId.id); fd.append('tipo_evidencia', tempTratamientoId.tipo); try { const res = await fetch('api_tratamientos.php', { method: 'POST', body: fd }); const d = await res.json(); if(d.success) { showToast('success', 'Evidencia subida'); cargarTratamientosSede(); } else { alert("Error: " + d.error); } } catch(err) { alert("Error de red"); } fileInput.value = ''; tempTratamientoId = null; } });
        function triggerUpload(id, tipo) { tempTratamientoId = { id, tipo }; fileInput.click(); }
        
        // --- VISOR DE EVIDENCIAS MEJORADO ---
        function verEvidencia(url) { 
            if (!url || url === 'null') { alert("No hay archivo adjunto."); return; } 
            url = url.trim(); 
            const safeUrl = url.replace(/\\/g, '/'); 
            const ext = safeUrl.split('.').pop().toLowerCase(); 
            
            const content = document.getElementById('preview-content'); 
            const downloadBtn = document.getElementById('btn-download-preview');
            
            // Configurar botón de descarga
            downloadBtn.href = safeUrl;

            content.innerHTML = ''; 
            if(ext === 'pdf') {
                content.innerHTML = `<iframe src="${safeUrl}" width="100%" height="100%" style="border:none;"></iframe>`; 
            } else {
                content.innerHTML = `<img src="${safeUrl}" style="max-width:100%; max-height:100%; object-fit:contain; box-shadow: 0 0 20px rgba(0,0,0,0.5);">`; 
            }
            
            document.getElementById('modal-preview').classList.add('show'); 
        }
        function cerrarPreview() { document.getElementById('modal-preview').classList.remove('show'); }
        
        async function marcarNoDeseado(id) { if(!confirm("¿Seguro que el paciente NO desea el tratamiento?")) return; try { await fetch('api_tratamientos.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ action: 'refuse', id: id }) }); cargarTratamientosSede(); } catch(e) { alert("Error"); } }

        function abrirSeguimientoGlobal() { 
            document.getElementById('modal-seguimiento-global').classList.add('show'); 
            if(!document.getElementById('filtro-mes-seg').value) { 
                const d = new Date(); 
                const mo = String(d.getMonth() + 1).padStart(2, '0');
                document.getElementById('filtro-mes-seg').value = `${d.getFullYear()}-${mo}`; 
            } 
            cargarTratamientosSede(); 
        }
        function cerrarSeguimientoGlobal() { document.getElementById('modal-seguimiento-global').classList.remove('show'); }
        function switchTab(t) { 
            ['suple','proc','re','int'].forEach(v => {
                document.getElementById(`view-${v}`).style.display = 'none';
                document.getElementById(`tab-btn-${v}`).classList.remove('btn-primary', 'active');
                document.getElementById(`tab-btn-${v}`).classList.add('btn-secondary');
            }); 
            document.getElementById(`view-${t}`).style.display = 'block';
            document.getElementById(`tab-btn-${t}`).classList.add('btn-primary', 'active');
            document.getElementById(`tab-btn-${t}`).classList.remove('btn-secondary'); 
        }
        function cargarTratamientosSede() {
            const inputMes = document.getElementById('filtro-mes-seg').value;
            let i, n;
            if (inputMes) {
                const [y, mo] = inputMes.split('-');
                const lastDay = new Date(y, mo, 0).getDate();
                i = `${y}-${mo}-01`;
                n = `${y}-${mo}-${lastDay}`;
            } else {
                const hoy = new Date();
                const y = hoy.getFullYear();
                const mo = String(hoy.getMonth() + 1).padStart(2, '0');
                const lastDay = new Date(y, hoy.getMonth() + 1, 0).getDate();
                i = `${y}-${mo}-01`;
                n = `${y}-${mo}-${lastDay}`;
                document.getElementById('filtro-mes-seg').value = `${y}-${mo}`;
            }
            const sede = getSedeFiltro(); 
            fetch(`listar_tratamientos_sede.php?sede=${sede}&inicio=${i}&fin=${n}`)
                .then(r=>r.json())
                .then(data => { RAW_TRATAMIENTOS = Array.isArray(data) ? data : []; aplicarFiltrosGlobales(); })
                .catch(e => RAW_TRATAMIENTOS=[]);
        }
        function aplicarFiltrosGlobales() {
            const fPac = document.getElementById('filtro-paciente').value.toLowerCase();
            const fEst = document.getElementById('filtro-estado').value;
            const fDoc = document.getElementById('filtro-doc-global').value;
            const fProd = document.getElementById('filtro-prod-global').value;

            if(document.getElementById('filtro-doc-global').options.length === 1 && RAW_TRATAMIENTOS.length > 0) {
                const docs = new Set(), prods = new Set();
                RAW_TRATAMIENTOS.forEach(t => { if(t.doctor_origen) docs.add(t.doctor_origen); if(t.producto) prods.add(t.producto); });
                Array.from(docs).sort().forEach(d => document.getElementById('filtro-doc-global').add(new Option(d, d)));
                Array.from(prods).sort().forEach(p => document.getElementById('filtro-prod-global').add(new Option(p, p)));
            }

            const filteredData = RAW_TRATAMIENTOS.filter(t => {
                let status = 'PENDIENTE';
                if(t.estado === 'CANCELADO' || t.estado === 'NO_DESEADO') status = 'CANCELADO'; 
                else if(t.realizado == 1) status = 'REALIZADO'; 
                else if(t.wsp_enviado == 1) status = 'GESTION';
                if(fEst !== '' && status !== fEst) return false;
                if(fDoc !== '' && t.doctor_origen !== fDoc) return false;
                if(fProd !== '' && t.producto !== fProd) return false;
                const fullName = (t.nombres + ' ' + t.apellidos + ' ' + t.dni).toLowerCase();
                if(fPac && !fullName.includes(fPac)) return false;
                return true;
            });

            let stats = { total: filteredData.length, pending: 0, gestion: 0, done: 0, cancelled: 0 };
            filteredData.forEach(t => { 
                if(t.estado === 'CANCELADO' || t.estado === 'NO_DESEADO') stats.cancelled++; 
                else if(t.realizado == 1) stats.done++; 
                else if(t.wsp_enviado == 1) stats.gestion++; 
                else stats.pending++; 
            });
            document.getElementById('count-total').innerText = stats.total; document.getElementById('count-pending').innerText = stats.pending; document.getElementById('count-gestion').innerText = stats.gestion; document.getElementById('count-done').innerText = stats.done; document.getElementById('count-cancelled').innerText = stats.cancelled;

            ['body-suple','body-proc','body-re','body-int'].forEach(id=>document.getElementById(id).innerHTML='');
            filteredData.forEach(t => {
                const row = crearFilaHTML(t);
                if (t.tipo === 'RECONSULTA') document.getElementById('body-re').appendChild(row);
                else if (t.tipo === 'INTERCONSULTA') document.getElementById('body-int').appendChild(row);
                else if (t.tipo === 'PROCEDIMIENTO') document.getElementById('body-proc').appendChild(row);
                else document.getElementById('body-suple').appendChild(row);
            });
        }

        function crearFilaHTML(t) {
            const tr = document.createElement('tr'); tr.dataset.id = t.id;
            let bgRow = "#fff"; const hoyIso = new Date().toISOString().split('T')[0];
            if (t.estado === 'CANCELADO' || t.estado === 'NO_DESEADO') bgRow = "#f3f4f6"; else if (t.realizado == 1) bgRow = "#f0fdf4"; else if (t.fecha_programada < hoyIso) bgRow = "#fef2f2"; 
            tr.style.backgroundColor = bgRow;
            const safeWsp = (t.evidencia_wsp||'').replace(/\\/g, '/').replace(/'/g, "\\'");
            const safeReal = (t.evidencia_realizado||'').replace(/\\/g, '/').replace(/'/g, "\\'");
            const safeReceta = (t.archivo_receta||'').replace(/\\/g, '/').replace(/'/g, "\\'");
            let btnWsp = `<button class="btn-status upload" onclick="triggerUpload(${t.id}, 'wsp')">📸 Subir</button>`; if(t.wsp_enviado == 1) btnWsp = `<button class="btn-status view" onclick="verEvidencia('${safeWsp}')">✅ Ver</button>`;
            let btnReal = `<button class="btn-status upload" onclick="triggerUpload(${t.id}, 'realizado')">📸 Subir</button>`; if(t.realizado == 1) btnReal = `<button class="btn-status view" onclick="verEvidencia('${safeReal}')">✅ Listo</button>`;
            let statusLabel = `<span class="badge warning">PENDIENTE</span>`; if(t.wsp_enviado == 1) statusLabel = `<span class="badge prog">GESTIÓN</span>`; if(t.realizado == 1) statusLabel = `<span class="badge atendido">OK</span>`;
            let btnRefuse = (t.realizado != 1 && t.estado != 'CANCELADO' && t.estado != 'NO_DESEADO') ? `<button class="btn-status refuse" title="Cancelar" onclick="marcarNoDeseado(${t.id})">❌</button>` : '';
            if (t.estado === 'CANCELADO' || t.estado === 'NO_DESEADO') { statusLabel = `<span class="badge cancel">CANCELADO</span>`; btnWsp = '-'; btnReal = '-'; btnRefuse = ''; }
            const linkWsp = `https://wa.me/51${t.telefono}?text=Hola`;
            let displayFechaHtml = `<div style="font-weight:700;">${t.fecha_programada}</div>`;
            if (t.realizado == 1 && t.fecha_ejecucion) {
                let fechaReal = t.fecha_ejecucion.split(' ')[0]; let horaReal = t.fecha_ejecucion.split(' ')[1].substring(0,5);
                let colorReal = (fechaReal !== t.fecha_programada) ? '#d97706' : '#16a34a'; 
                displayFechaHtml += `<div style="font-size:0.7rem; color:${colorReal}; margin-top:2px;"><i class="ph ph-check-circle"></i> ${fechaReal} <span style="color:#94a3b8">(${horaReal})</span></div>`;
            }
            let cellFecha = `<td style="padding:12px;">${displayFechaHtml}</td>`;
            let cellProd = `<td><small style="color:#64748b; font-size:0.7rem; font-weight:700;">${t.tipo}</small><br><b>${t.producto}</b></td>`;
            if (t.realizado != 1 && t.estado != 'CANCELADO' && t.estado != 'NO_DESEADO') {
                if (t.tipo === 'RECONSULTA') { cellFecha = `<td><input type="date" class="table-input edit-date" value="${t.fecha_programada}" onchange="guardarEdicionItem(${t.id})"></td>`; cellProd = `<td><small style="color:#64748b">Retorno con:</small><br><b>${formatoDoctor(t.doctor_origen)}</b><input type="hidden" class="table-input edit-prod" value="${t.producto}"></td>`; }
                else if (t.tipo === 'INTERCONSULTA') {
                    cellFecha = `<td><input type="date" class="table-input edit-date" value="${t.fecha_programada}" onchange="guardarEdicionItem(${t.id})"></td>`;
                    let especialidadBase = t.producto; if(t.producto.includes(" - ")) { const partes = t.producto.split(" - "); especialidadBase = partes[partes.length - 1]; }
                    const docsFiltro = ALL_DOCTORS.filter(d => d.nombre_esp === especialidadBase);
                    if(docsFiltro.length > 0) {
                        let opts = `<option value="${especialidadBase}">-- Asignar --</option>`;
                        docsFiltro.forEach(doc => { const valGuardar = `${doc.nombre_completo} - ${doc.nombre_esp}`; const isSelected = (t.producto === valGuardar) ? 'selected' : ''; let horario = ''; if (DATA_CATALOGO.length > 0) { const match = DATA_CATALOGO.find(c => c.doc_nombre === doc.nombre_completo && c.esp_nombre === doc.nombre_esp && c.horario_referencial); if (match) horario = ` 🕒 [${match.horario_referencial}]`; } opts += `<option value="${valGuardar}" ${isSelected}>${formatoDoctor(doc.nombre_completo)}${horario}</option>`; });
                        cellProd = `<td><div style="font-size:0.7rem; color:#64748b; font-weight:700; margin-bottom:2px;">Solicitado: <span style="color:#1e40af">${especialidadBase}</span></div><select class="table-input edit-prod" onchange="guardarEdicionItem(${t.id})" style="width:100%; border-color:#3b82f6;">${opts}</select></td>`;
                    } else { cellProd = `<td><small style="color:#ef4444">No hay doctores de: <b>${especialidadBase}</b></small><input type="hidden" class="edit-prod" value="${t.producto}"></td>`; }
                }
            }
            const drOrigen = `<small>${formatoDoctor(t.doctor_origen)}</small>`;
            let btnReceta = t.archivo_receta ? `<button onclick="verEvidencia('${safeReceta}')" title="Ver Receta" style="border:none; background:#fef3c7; color:#d97706; border-radius:50%; width:24px; height:24px; cursor:pointer; margin-left:5px;"><i class="ph ph-file-text"></i></button>` : '';
            let sedeBadge = `<span class="sede-tag">${t.nombre_sede || 'Sede '+(t.sede_id||CURRENT_SEDE)}</span>`;
            tr.innerHTML = `<td>${sedeBadge}</td>${cellFecha}<td>${t.nombres} ${t.apellidos} ${btnReceta}<br><a href="${linkWsp}" target="_blank" style="text-decoration:none;color:#64748b"><i class="ph ph-whatsapp-logo"></i> ${t.telefono}</a></td>${cellProd}<td>${drOrigen}</td><td style="text-align:center">${statusLabel}</td><td style="text-align:center;">${btnWsp}</td><td style="text-align:center; display:flex; gap:5px; justify-content:center;">${btnReal} ${btnRefuse}</td>`;
            return tr;
        }

        initApp();
    </script>
</body>
</html>