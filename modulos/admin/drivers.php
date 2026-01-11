<?php
// modulos/admin/drivers.php
session_start();
require_once '../../config/db.php';

// Seguridad
if (!isset($_SESSION['user_id'])) { header("Location: ../../index.php"); exit; }

include '../../includes/header_admin.php';

$mensaje = "";

// --- PROCESAR CREACIÓN DE DRIVER ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'crear') {
    $nombre    = trim($_POST['nombre']);
    $dni       = trim($_POST['dni']);
    $telefono  = trim($_POST['telefono']);
    $usuario   = trim($_POST['usuario']);
    $password  = trim($_POST['password']);
    
    // Datos Vehículo
    $v_tipo    = $_POST['vehiculo_tipo'];
    $v_placa   = strtoupper(trim($_POST['vehiculo_placa']));
    $v_modelo  = trim($_POST['vehiculo_modelo']);
    $licencia  = trim($_POST['licencia_conducir']);
    
    // Validar duplicados
    $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ? OR dni = ?");
    $stmtCheck->execute([$usuario, $dni]);
    
    if ($stmtCheck->fetch()) {
        $mensaje = "<div class='alert alert-error'>⚠️ El usuario o DNI ya existen.</div>";
    } else {
        // Insertar Driver
        $sql = "INSERT INTO usuarios (nombre, dni, telefono, usuario, password, rol, vehiculo_tipo, vehiculo_placa, vehiculo_modelo, licencia_conducir, activo, id_sede) 
                VALUES (?, ?, ?, ?, ?, 'driver', ?, ?, ?, ?, 1, 2)"; // Sede 2 por defecto (Tienda)
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$nombre, $dni, $telefono, $usuario, $password, $v_tipo, $v_placa, $v_modelo, $licencia])) {
            $mensaje = "<div class='alert alert-success'>✅ Driver <b>$nombre</b> registrado correctamente.</div>";
        } else {
            $mensaje = "<div class='alert alert-error'>❌ Error al registrar en BD.</div>";
        }
    }
}

// --- BORRAR DRIVER ---
if (isset($_GET['borrar'])) {
    $id = $_GET['borrar'];
    $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$id]);
    $mensaje = "<div class='alert alert-success'>🗑️ Driver eliminado.</div>";
}

// --- CONSULTAR DRIVERS ---
$drivers = $pdo->query("SELECT * FROM usuarios WHERE rol = 'driver' ORDER BY activo DESC, nombre ASC")->fetchAll();
?>

<style>
    .page-title { color: var(--royal-gold, #FFD700); text-align: center; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px; }
    
    /* GRID DE TARJETAS */
    .driver-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 25px;
        margin-top: 30px;
    }

    /* TARJETA PROFESIONAL */
    .driver-card {
        background: #151515;
        border: 1px solid #333;
        border-radius: 15px;
        overflow: hidden;
        position: relative;
        transition: transform 0.3s, box-shadow 0.3s;
    }
    .driver-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.5);
        border-color: var(--royal-gold);
    }
    
    .card-header {
        background: linear-gradient(135deg, #222 0%, #000 100%);
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
        border-bottom: 1px solid #333;
    }
    
    .avatar {
        width: 60px; height: 60px;
        background: #333;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.8rem; color: var(--royal-gold);
        border: 2px solid var(--royal-gold);
    }
    
    .driver-info h4 { margin: 0; color: #fff; font-size: 1.1rem; }
    .driver-info small { color: #888; display: block; margin-top: 3px; }
    .status-badge {
        font-size: 0.7rem; padding: 2px 8px; border-radius: 4px; 
        background: #2e7d32; color: #fff; display: inline-block; margin-top: 5px;
    }

    .card-body { padding: 20px; }
    
    .data-row {
        display: flex; justify-content: space-between;
        margin-bottom: 12px; font-size: 0.9rem; color: #ccc;
        border-bottom: 1px dashed #333; padding-bottom: 5px;
    }
    .data-row span { font-weight: bold; color: #fff; }

    .vehicle-tag {
        background: #222; border: 1px solid #444;
        padding: 10px; border-radius: 8px;
        text-align: center; margin-top: 15px;
        display: flex; align-items: center; justify-content: center; gap: 10px;
    }
    .plate {
        background: #FFD700; color: #000; padding: 2px 8px;
        border-radius: 4px; font-weight: 800; font-family: monospace; letter-spacing: 1px;
    }

    .card-actions {
        background: #0a0a0a; padding: 15px;
        display: flex; justify-content: space-between;
        border-top: 1px solid #222;
    }
    .btn-icon { color: #888; text-decoration: none; font-size: 1.1rem; transition: 0.2s; }
    .btn-icon:hover { color: #fff; }
    .btn-del:hover { color: #ef5350; }

    /* FORMULARIO FLOTANTE */
    .modal-overlay {
        display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.8); z-index: 2000; align-items: center; justify-content: center;
        backdrop-filter: blur(5px);
    }
    .modal-content {
        background: #1a1a1a; width: 90%; max-width: 600px;
        border-radius: 15px; border: 1px solid var(--royal-gold);
        padding: 30px; box-shadow: 0 0 50px rgba(255, 215, 0, 0.2);
    }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    .full-width { grid-column: 1 / -1; }
</style>

<div class="fade-in" style="max-width: 1200px; margin: auto; padding-top: 20px;">
    
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2 class="page-title" style="margin:0; text-align:left;"><i class="fa-solid fa-helmet-safety"></i> Flota de Drivers</h2>
        <button onclick="abrirModal()" class="btn-royal" style="width:auto; padding: 12px 25px;">
            <i class="fa-solid fa-plus"></i> NUEVO DRIVER
        </button>
    </div>
    
    <?= $mensaje ?>

    <div class="driver-grid">
        <?php foreach($drivers as $d): ?>
            <div class="driver-card">
                <div class="card-header">
                    <div class="avatar">
                        <?php if($d['foto_selfie']): ?>
                            <img src="../../assets/uploads/usuarios/<?= $d['foto_selfie'] ?>" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">
                        <?php else: ?>
                            <i class="fa-solid fa-user-astronaut"></i>
                        <?php endif; ?>
                    </div>
                    <div class="driver-info">
                        <h4><?= $d['nombre'] ?></h4>
                        <small><?= $d['usuario'] ?></small>
                        <span class="status-badge">ACTIVO</span>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="data-row">
                        <i class="fa-solid fa-id-card"></i> DNI: <span><?= $d['dni'] ?></span>
                    </div>
                    <div class="data-row">
                        <i class="fa-brands fa-whatsapp"></i> Tel: <span><?= $d['telefono'] ?></span>
                    </div>
                    <div class="data-row">
                        <i class="fa-solid fa-file-contract"></i> Licencia: <span><?= $d['licencia_conducir'] ?: '---' ?></span>
                    </div>

                    <div class="vehicle-tag">
                        <i class="fa-solid fa-motorcycle" style="color:var(--royal-gold);"></i>
                        <span style="color:#aaa;"><?= $d['vehiculo_modelo'] ?: 'Modelo Genérico' ?></span>
                        <div class="plate"><?= $d['vehiculo_placa'] ?: 'S/P' ?></div>
                    </div>
                </div>

                <div class="card-actions">
                    <a href="https://wa.me/51<?= preg_replace('/[^0-9]/','',$d['telefono']) ?>" target="_blank" class="btn-icon">
                        <i class="fa-solid fa-message"></i> Contactar
                    </a>
                    <a href="?borrar=<?= $d['id'] ?>" onclick="return confirm('¿Dar de baja a este driver?')" class="btn-icon btn-del">
                        <i class="fa-solid fa-trash"></i> Dar de Baja
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</div>

<div id="modalDriver" class="modal-overlay">
    <div class="modal-content fade-in">
        <h3 style="color:var(--royal-gold); margin-bottom:20px; text-align:center;">Alta de Nuevo Driver</h3>
        
        <form method="POST" class="form-grid">
            <input type="hidden" name="accion" value="crear">
            
            <div class="full-width" style="text-align:center; color:#666; font-size:0.8rem; margin-bottom:10px;">DATOS PERSONALES</div>
            
            <input type="text" name="nombre" placeholder="Nombre Completo" required class="form-control">
            <input type="text" name="dni" placeholder="DNI" required class="form-control">
            <input type="tel" name="telefono" placeholder="Celular / WhatsApp" required class="form-control">
            <input type="text" name="licencia_conducir" placeholder="N° Licencia" class="form-control">

            <div class="full-width" style="text-align:center; color:#666; font-size:0.8rem; margin:10px 0;">DATOS DEL VEHÍCULO</div>

            <select name="vehiculo_tipo" class="form-control">
                <option value="Moto">Motocicleta</option>
                <option value="Auto">Automóvil</option>
            </select>
            <input type="text" name="vehiculo_placa" placeholder="Placa (Ej: 1234-AB)" required class="form-control" style="text-transform:uppercase;">
            <div class="full-width">
                <input type="text" name="vehiculo_modelo" placeholder="Modelo (Ej: Honda CB190)" class="form-control">
            </div>

            <div class="full-width" style="text-align:center; color:#666; font-size:0.8rem; margin:10px 0;">ACCESO AL SISTEMA</div>
            
            <input type="text" name="usuario" placeholder="Usuario Login" required class="form-control">
            <input type="password" name="password" placeholder="Contraseña" required class="form-control">

            <div class="full-width" style="display:flex; gap:10px; margin-top:15px;">
                <button type="button" onclick="cerrarModal()" class="btn-royal" style="background:#333; color:#fff;">CANCELAR</button>
                <button type="submit" class="btn-royal">REGISTRAR</button>
            </div>
        </form>
    </div>
</div>

<script>
    function abrirModal() { document.getElementById('modalDriver').style.display = 'flex'; }
    function cerrarModal() { document.getElementById('modalDriver').style.display = 'none'; }
</script>

<?php include '../../includes/footer_admin.php'; ?>