<?php
require_once 'includes/auth.php';
require_once 'includes/funciones.php';
require_once 'db/config.php';

requiereAdmin();
$conn = conectarDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_usuario'])) {
    $usuario_id = $_POST['usuario_id'] ?? null;
    $nombre  = trim($_POST['nombre']);
    $email   = trim($_POST['email']);
    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['password']);
    $rol     = $_POST['rol'];
    $errores = [];
    if (empty($nombre))  $errores[] = "El nombre es obligatorio.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = "El email no es válido.";
    if (empty($usuario)) $errores[] = "El nombre de usuario es obligatorio.";
    if (!$usuario_id) {
        $s = $conn->prepare("SELECT id FROM usuarios WHERE usuario=?"); $s->bind_param("s",$usuario); $s->execute();
        if ($s->get_result()->fetch_assoc()) $errores[] = "El nombre de usuario ya está en uso.";
        if (empty($password)) $errores[] = "La contraseña es obligatoria para nuevos usuarios.";
    }
    if (empty($errores)) {
        try {
            if ($usuario_id) {
                if (!empty($password)) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $s = $conn->prepare("UPDATE usuarios SET nombre=?,email=?,usuario=?,password=?,rol=? WHERE id=?");
                    $s->bind_param("sssssi",$nombre,$email,$usuario,$hash,$rol,$usuario_id);
                } else {
                    $s = $conn->prepare("UPDATE usuarios SET nombre=?,email=?,usuario=?,rol=? WHERE id=?");
                    $s->bind_param("ssssi",$nombre,$email,$usuario,$rol,$usuario_id);
                }
                $s->execute();
                $_SESSION['mensaje'] = "Usuario actualizado.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $s = $conn->prepare("INSERT INTO usuarios (nombre,email,usuario,password,rol,fecha_registro) VALUES (?,?,?,?,?,NOW())");
                $s->bind_param("sssss",$nombre,$email,$usuario,$hash,$rol);
                $s->execute();
                $_SESSION['mensaje'] = "Usuario creado.";
            }
            $_SESSION['tipo_mensaje'] = "success";
        } catch (Exception $e) {
            $_SESSION['mensaje'] = "Error: " . $e->getMessage();
            $_SESSION['tipo_mensaje'] = "danger";
        }
        header('Location: usuarios.php'); exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_usuario'])) {
    $uid = (int)$_POST['usuario_id'];
    if ($uid == $_SESSION['usuario_id']) {
        $_SESSION['mensaje'] = "No puede eliminar su propio usuario."; $_SESSION['tipo_mensaje'] = "danger";
    } else {
        try {
            $s = $conn->prepare("DELETE FROM usuarios WHERE id=?"); $s->bind_param("i",$uid); $s->execute();
            $_SESSION['mensaje'] = "Usuario eliminado."; $_SESSION['tipo_mensaje'] = "success";
        } catch (Exception $e) { $_SESSION['mensaje'] = "Error: ".$e->getMessage(); $_SESSION['tipo_mensaje'] = "danger"; }
    }
    header('Location: usuarios.php'); exit;
}

$usuarios = $conn->query("SELECT * FROM usuarios ORDER BY fecha_registro DESC")->fetch_all(MYSQLI_ASSOC);
$titulo_pagina = 'Gestión de Usuarios';
include 'includes/header.php';
?>

<style>
.role-badge { display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px; }
.role-admin      { background:#fef2f2;color:#dc2626; }
.role-supervisor { background:#fefce8;color:#ca8a04; }
.role-user       { background:#eff6ff;color:#2563eb; }
.action-link { width:28px;height:28px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;border:1px solid var(--border);color:var(--text-secondary);text-decoration:none;background:none;cursor:pointer;transition:all .15s; }
.action-link:hover { border-color:var(--accent);color:var(--accent);background:#eff6ff; }
.action-link.danger:hover { border-color:var(--danger);color:var(--danger);background:#fef2f2; }
</style>

<?php if (isset($_SESSION['mensaje'])): ?>
<div class="alert alert-<?php echo $_SESSION['tipo_mensaje']; ?> alert-dismissible fade show mb-4">
    <?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje'],$_SESSION['tipo_mensaje']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if (!empty($errores)): ?>
<div class="alert alert-danger alert-dismissible fade show mb-4">
    <ul class="mb-0"><?php foreach($errores as $e): ?><li><?php echo $e; ?></li><?php endforeach; ?></ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <span><i class="fas fa-users-cog me-2 text-primary"></i>Usuarios del Sistema</span>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#usuarioModal">
            <i class="fas fa-user-plus me-1"></i> Nuevo Usuario
        </button>
    </div>
    <div class="table-responsive">
        <table class="table mb-0" id="dataTable">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Registro</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($usuarios as $u): ?>
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:32px;height:32px;border-radius:50%;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;">
                            <?php echo strtoupper(substr($u['nombre'],0,1)); ?>
                        </div>
                        <div>
                            <div style="font-weight:500;font-size:13px;"><?php echo htmlspecialchars($u['nombre']); ?></div>
                            <div style="font-size:11px;color:var(--text-secondary);">@<?php echo htmlspecialchars($u['usuario']); ?></div>
                        </div>
                    </div>
                </td>
                <td style="font-size:13px;"><?php echo htmlspecialchars($u['email']); ?></td>
                <td>
                    <?php
                        $rc = ['admin'=>'role-admin','supervisor'=>'role-supervisor','digitador'=>'role-user'];
                        $ri = ['admin'=>'shield-alt','supervisor'=>'user-check','digitador'=>'keyboard'];
                        $rl = ['admin'=>'Administrador','supervisor'=>'Supervisor','digitador'=>'Digitador'];
                    ?>
                    <span class="role-badge <?php echo $rc[$u['rol']]??'role-user'; ?>">
                        <i class="fas fa-<?php echo $ri[$u['rol']]??'user'; ?>"></i>
                        <?php echo $rl[$u['rol']]??ucfirst($u['rol']); ?>
                    </span>
                </td>
                <td style="font-size:12px;color:var(--text-secondary);"><?php echo date('d/m/Y', strtotime($u['fecha_registro'])); ?></td>
                <td>
                    <div class="d-flex gap-1">
                        <button class="action-link" title="Editar"
                            onclick="editarUsuario(<?php echo $u['id']; ?>,'<?php echo htmlspecialchars(addslashes($u['nombre'])); ?>','<?php echo htmlspecialchars(addslashes($u['email'])); ?>','<?php echo htmlspecialchars(addslashes($u['usuario'])); ?>','<?php echo $u['rol']; ?>')">
                            <i class="fas fa-pen"></i>
                        </button>
                        <?php if ($u['id'] != $_SESSION['usuario_id']): ?>
                        <button class="action-link danger" title="Eliminar"
                            onclick="confirmarEliminar(<?php echo $u['id']; ?>,'<?php echo htmlspecialchars(addslashes($u['nombre'])); ?>')">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create/Edit Modal -->
<div class="modal fade" id="usuarioModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:var(--radius);border:1px solid var(--border);">
            <div class="modal-header" style="border-bottom:1px solid var(--border);">
                <h6 class="modal-title fw-semibold" id="usuarioModalLabel">Nuevo Usuario</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="usuarioForm" method="POST">
                    <input type="hidden" id="usuario_id" name="usuario_id">
                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px;font-weight:500;">Nombre Completo</label>
                        <input type="text" class="form-control form-control-sm" id="nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px;font-weight:500;">Email</label>
                        <input type="email" class="form-control form-control-sm" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px;font-weight:500;">Nombre de Usuario</label>
                        <input type="text" class="form-control form-control-sm" id="usuario" name="usuario" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px;font-weight:500;">Contraseña</label>
                        <input type="password" class="form-control form-control-sm" id="password" name="password">
                        <div class="form-text" style="font-size:11px;">Dejar en blanco para mantener la contraseña actual.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" style="font-size:13px;font-weight:500;">Rol</label>
                        <select class="form-select form-select-sm" id="rol" name="rol" required>
                            <option value="digitador">Digitador</option>
                            <option value="supervisor">Supervisor</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                    <button type="submit" name="guardar_usuario" class="btn btn-primary w-100 btn-sm">
                        <i class="fas fa-save me-1"></i> Guardar Usuario
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="eliminarModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content" style="border-radius:var(--radius);border:1px solid var(--border);">
            <div class="modal-header" style="border-bottom:1px solid var(--border);">
                <h6 class="modal-title fw-semibold">Eliminar usuario</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="font-size:13px;">¿Eliminar a <strong id="usuario-nombre"></strong>? Esta acción no se puede deshacer.</div>
            <div class="modal-footer" style="border-top:1px solid var(--border);gap:8px;">
                <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" class="d-inline">
                    <input type="hidden" id="eliminar_usuario_id" name="usuario_id">
                    <input type="hidden" name="eliminar_usuario" value="1">
                    <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editarUsuario(id, nombre, email, usuario, rol) {
    document.getElementById('usuarioModalLabel').textContent = 'Editar Usuario';
    document.getElementById('usuario_id').value = id;
    document.getElementById('nombre').value    = nombre;
    document.getElementById('email').value     = email;
    document.getElementById('usuario').value   = usuario;
    document.getElementById('password').value  = '';
    document.getElementById('rol').value       = rol;
    new bootstrap.Modal(document.getElementById('usuarioModal')).show();
}
function confirmarEliminar(id, nombre) {
    document.getElementById('eliminar_usuario_id').value = id;
    document.getElementById('usuario-nombre').textContent = nombre;
    new bootstrap.Modal(document.getElementById('eliminarModal')).show();
}
document.getElementById('usuarioModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('usuarioForm').reset();
    document.getElementById('usuario_id').value = '';
    document.getElementById('usuarioModalLabel').textContent = 'Nuevo Usuario';
});
</script>

<?php include 'includes/footer.php'; ?>
