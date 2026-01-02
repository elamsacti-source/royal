<?php
session_start();
require_once '../../config/db.php';

// Seguridad
if (!isset($_SESSION['user_id'])) { header("Location: ../../index.php"); exit; }

include '../../includes/header_admin.php';

$mensaje = "";

// --- PROCESAR FORMULARIO (CREAR USUARIO) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'crear') {
    $nombre   = trim($_POST['nombre']);
    $usuario  = trim($_POST['usuario']);
    $password = trim($_POST['password']);
    $rol      = $_POST['rol'];
    $id_sede  = $_POST['sede'];

    if ($nombre && $usuario && $password) {
        // Verificar si el usuario ya existe
        $check = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        $check->execute([$usuario]);
        
        if ($check->fetch()) {
            $mensaje = "<div class='alert alert-error'>⚠️ El usuario '$usuario' ya existe.</div>";
        } else {
            // Insertar nuevo usuario (Nota: Para este ejemplo usamos texto plano como en tu login actual. 
            // Lo ideal sería usar password_hash() en el futuro).
            $sql = "INSERT INTO usuarios (nombre, usuario, password, rol, id_sede, activo) VALUES (?, ?, ?, ?, ?, 1)";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$nombre, $usuario, $password, $rol, $id_sede])) {
                $mensaje = "<div class='alert alert-success'>✅ Usuario creado y asignado correctamente.</div>";
            } else {
                $mensaje = "<div class='alert alert-error'>❌ Error al crear usuario.</div>";
            }
        }
    }
}

// --- BORRAR USUARIO ---
if (isset($_GET['borrar'])) {
    $id_borrar = $_GET['borrar'];
    if ($id_borrar != $_SESSION['user_id']) { // Evitar auto-borrarse
        $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$id_borrar]);
        $mensaje = "<div class='alert alert-success'>🗑️ Usuario eliminado.</div>";
    } else {
        $mensaje = "<div class='alert alert-error'>⚠️ No puedes eliminar tu propia cuenta.</div>";
    }
}

// --- CONSULTAS ---
$sedes = $pdo->query("SELECT * FROM sedes")->fetchAll();
// Traemos usuarios con el nombre de su sede
$sqlUsers = "SELECT u.*, s.nombre as nombre_sede 
             FROM usuarios u 
             LEFT JOIN sedes s ON u.id_sede = s.id 
             ORDER BY u.nombre ASC";
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
</style>

<div class="fade-in" style="max-width: 900px; margin: auto; padding-top: 20px;">
    
    <h2 class="page-title"><i class="fa-solid fa-users-gear"></i> Gestión de Usuarios</h2>
    <?= $mensaje ?>

    <div class="card">
        <h3 style="color:#fff; margin-bottom:15px; border-bottom:1px solid #333; padding-bottom:10px;">Nuevo Usuario</h3>
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
                </select>
            </div>

            <div style="grid-column: 1 / -1;">
                <label style="color:var(--royal-gold);">Asignar Sede (Lugar de Trabajo)</label>
                <select name="sede" class="form-control" required>
                    <?php foreach($sedes as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= $s['nombre'] ?> (<?= ucfirst($s['tipo']) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <small style="color:#666;">* El inventario se descontará de esta sede cuando el usuario venda.</small>
            </div>

            <div style="grid-column: 1 / -1;">
                <button type="submit" class="btn-royal">
                    <i class="fa-solid fa-user-plus"></i> CREAR USUARIO
                </button>
            </div>
        </form>
    </div>

    <div class="card" style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th style="color:var(--royal-gold);">Sede Asignada</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($usuarios as $u): ?>
                <tr>
                    <td style="font-weight:bold; color:#fff;"><?= $u['nombre'] ?></td>
                    <td style="color:#ccc;"><?= $u['usuario'] ?></td>
                    <td>
                        <?php if($u['rol'] == 'admin'): ?>
                            <span class="badge" style="background:#FFD700; color:#000; padding:2px 8px; border-radius:4px; font-weight:bold; font-size:0.8rem;">ADMIN</span>
                        <?php else: ?>
                            <span class="badge" style="background:#333; color:#fff; padding:2px 8px; border-radius:4px; font-size:0.8rem;">CAJERO</span>
                        <?php endif; ?>
                    </td>
                    <td style="color:var(--royal-gold); font-weight:bold;">
                        <i class="fa-solid fa-store"></i> <?= $u['nombre_sede'] ?: 'Sin Asignar' ?>
                    </td>
                    <td>
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