<?php
session_start();
require_once '../../config/db.php';

// Seguridad
if (!isset($_SESSION['user_id'])) { header("Location: ../../index.php"); exit; }

include '../../includes/header_admin.php';

$mensaje = "";

// --- PROCESAR FORMULARIO (CREAR USUARIO INTERNO) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'crear') {
    $nombre   = trim($_POST['nombre']);
    $usuario  = trim($_POST['usuario']);
    $password = trim($_POST['password']);
    $rol      = strtolower(trim($_POST['rol'])); // Normalizamos a minúsculas
    $id_sede  = $_POST['sede'];

    if ($nombre && $usuario && $password) {
        // Verificar duplicados
        $check = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        $check->execute([$usuario]);
        
        if ($check->fetch()) {
            $mensaje = "<div class='alert alert-error'>⚠️ El usuario '$usuario' ya existe.</div>";
        } else {
            // Insertar
            $sql = "INSERT INTO usuarios (nombre, usuario, password, rol, id_sede, activo) VALUES (?, ?, ?, ?, ?, 1)";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$nombre, $usuario, $password, $rol, $id_sede])) {
                $mensaje = "<div class='alert alert-success'>✅ Usuario creado correctamente.</div>";
            } else {
                $mensaje = "<div class='alert alert-error'>❌ Error al crear usuario.</div>";
            }
        }
    }
}

// --- BORRAR USUARIO ---
if (isset($_GET['borrar'])) {
    $id_borrar = $_GET['borrar'];
    if ($id_borrar != $_SESSION['user_id']) { 
        $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$id_borrar]);
        $mensaje = "<div class='alert alert-success'>🗑️ Usuario eliminado.</div>";
    } else {
        $mensaje = "<div class='alert alert-error'>⚠️ No puedes eliminar tu propia cuenta.</div>";
    }
}

// --- CONSULTAS ---
$sedes = $pdo->query("SELECT * FROM sedes")->fetchAll();
$sqlUsers = "SELECT u.*, s.nombre as nombre_sede 
             FROM usuarios u 
             LEFT JOIN sedes s ON u.id_sede = s.id 
             ORDER BY u.rol ASC, u.nombre ASC"; // Ordenamos por rol para agrupar
$usuarios = $pdo->query($sqlUsers)->fetchAll();
?>

<style>
    .page-title { color: var(--royal-gold, #FFD700); text-align: center; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px; }
    .card { background: #1a1a1a; padding: 25px; border-radius: 10px; border: 1px solid #333; margin-bottom: 20px; }
    .form-control { width: 100%; padding: 10px; background: #2a2a2a; border: 1px solid #444; color: #fff; border-radius: 5px; margin-bottom: 10px; }
    .btn-royal { background: linear-gradient(45deg, #b7892b, #FFD700); color: #000; font-weight: bold; border: none; padding: 12px; width: 100%; border-radius: 5px; cursor: pointer; }
    .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center; font-weight: bold; }
    .alert-success { background: rgba(46, 125, 50, 0.2); color: #81c784; border: 1px solid #2e7d32; }
    .alert-error { background: rgba(198, 40, 40, 0.2); color: #e57373; border: 1px solid #c62828; }
    label { color: #aaa; font-size: 0.9rem; margin-bottom: 5px; display: block; }
    
    /* Badges de Roles */
    .badge { padding: 3px 10px; border-radius: 4px; font-weight: bold; font-size: 0.75rem; text-transform: uppercase; }
    .bg-admin { background: #FFD700; color: #000; }
    .bg-driver { background: #29b6f6; color: #000; }
    .bg-cajero { background: #333; color: #fff; border: 1px solid #555; }
    .bg-cliente { background: #4caf50; color: #fff; } /* Verde para clientes */
    .bg-otro { background: #555; color: #ccc; }
</style>

<div class="fade-in" style="max-width: 900px; margin: auto; padding-top: 20px;">
    
    <h2 class="page-title"><i class="fa-solid fa-users-gear"></i> Gestión de Usuarios</h2>
    <?= $mensaje ?>

    <div class="card">
        <h3 style="color:#fff; margin-bottom:15px; border-bottom:1px solid #333; padding-bottom:10px;">Nuevo Personal</h3>
        <form method="POST" style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
            <input type="hidden" name="accion" value="crear">
            
            <div>
                <label>Nombre Completo</label>
                <input type="text" name="nombre" class="form-control" required placeholder="Ej: Juan Pérez">
            </div>

            <div>
                <label>Usuario (Login)</label>
                <input type="text" name="usuario" class="form-control" required placeholder="Ej: jperez">
            </div>

            <div>
                <label>Contraseña</label>
                <input type="password" name="password" class="form-control" required placeholder="******">
            </div>

            <div>
                <label>Rol de Sistema</label>
                <select name="rol" class="form-control">
                    <option value="cajero">Cajero (Ventas)</option>
                    <option value="admin">Administrador (Total)</option>
                    <option value="driver">Driver (Repartidor)</option>
                    </select>
            </div>

            <div style="grid-column: 1 / -1;">
                <label style="color:var(--royal-gold);">Asignar Sede</label>
                <select name="sede" class="form-control" required>
                    <?php foreach($sedes as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= $s['nombre'] ?> (<?= ucfirst($s['tipo']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="grid-column: 1 / -1;">
                <button type="submit" class="btn-royal">
                    <i class="fa-solid fa-user-plus"></i> GUARDAR USUARIO
                </button>
            </div>
        </form>
    </div>

    <div class="card" style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; color:#fff;">
            <thead>
                <tr style="text-align:left; border-bottom:1px solid #444;">
                    <th style="padding:10px;">Nombre</th>
                    <th style="padding:10px;">Usuario</th>
                    <th style="padding:10px;">Rol</th>
                    <th style="padding:10px; color:var(--royal-gold);">Sede</th>
                    <th style="padding:10px;">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($usuarios as $u): ?>
                <tr style="border-bottom:1px solid #222;">
                    <td style="padding:10px; font-weight:bold;"><?= $u['nombre'] ?></td>
                    <td style="padding:10px; color:#ccc;"><?= $u['usuario'] ?></td>
                    <td style="padding:10px;">
                        <?php 
                            // Lógica de visualización corregida
                            $r = strtolower($u['rol']); 
                            if($r == 'admin') echo '<span class="badge bg-admin">ADMIN</span>';
                            elseif($r == 'driver') echo '<span class="badge bg-driver">DRIVER</span>';
                            elseif($r == 'cajero') echo '<span class="badge bg-cajero">CAJERO</span>';
                            elseif($r == 'cliente' || $r == 'publico') echo '<span class="badge bg-cliente">CLIENTE</span>';
                            else echo '<span class="badge bg-otro">'.strtoupper($r).'</span>';
                        ?>
                    </td>
                    <td style="padding:10px; color:var(--royal-gold);">
                        <i class="fa-solid fa-store"></i> <?= $u['nombre_sede'] ?: 'N/A' ?>
                    </td>
                    <td style="padding:10px;">
                        <?php if($u['id'] != $_SESSION['user_id']): ?>
                            <a href="?borrar=<?= $u['id'] ?>" onclick="return confirm('¿Eliminar a este usuario?')" style="color:#ef5350; font-size:1.1rem;">
                                <i class="fa-solid fa-trash-can"></i>
                            </a>
                        <?php else: ?>
                            <small style="color:#666;">(Tú)</small>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<?php include '../../includes/footer_admin.php'; ?>