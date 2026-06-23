<?php
require_once 'includes/auth.php';
require_once 'db/config.php';

requiereAutenticacion();

$conn = conectarDB();
$usuario_id = $_SESSION['usuario_id'];

$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id); $stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
if (!$usuario) { header('Location: dashboard.php'); exit; }

$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_perfil'])) {
    $nombre           = trim($_POST['nombre']);
    $email            = trim($_POST['email']);
    $usuario_nombre   = trim($_POST['usuario']);
    $password_actual  = trim($_POST['password_actual']);
    $password_nuevo   = trim($_POST['password_nuevo']);
    $password_conf    = trim($_POST['password_confirmar']);

    if (empty($nombre))  $errores[] = "El nombre es obligatorio.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = "El email no es válido.";
    if (empty($usuario_nombre)) $errores[] = "El nombre de usuario es obligatorio.";
    if ($usuario_nombre !== $usuario['usuario']) {
        $s = $conn->prepare("SELECT id FROM usuarios WHERE usuario=? AND id!=?");
        $s->bind_param("si",$usuario_nombre,$usuario_id); $s->execute();
        if ($s->get_result()->fetch_assoc()) $errores[] = "El nombre de usuario ya está en uso.";
    }
    if (!empty($password_nuevo)) {
        if (empty($password_actual)) $errores[] = "Ingrese su contraseña actual para cambiarla.";
        elseif (!password_verify($password_actual, $usuario['password'])) $errores[] = "La contraseña actual es incorrecta.";
        if ($password_nuevo !== $password_conf) $errores[] = "La nueva contraseña y su confirmación no coinciden.";
    }
    if (empty($errores)) {
        try {
            if (!empty($password_nuevo)) {
                $hash = password_hash($password_nuevo, PASSWORD_DEFAULT);
                $s = $conn->prepare("UPDATE usuarios SET nombre=?,email=?,usuario=?,password=? WHERE id=?");
                $s->bind_param("ssssi",$nombre,$email,$usuario_nombre,$hash,$usuario_id);
            } else {
                $s = $conn->prepare("UPDATE usuarios SET nombre=?,email=?,usuario=? WHERE id=?");
                $s->bind_param("sssi",$nombre,$email,$usuario_nombre,$usuario_id);
            }
            $s->execute();
            $_SESSION['usuario_nombre'] = $nombre;
            $_SESSION['mensaje'] = "Perfil actualizado correctamente.";
            $_SESSION['tipo_mensaje'] = "success";
            header('Location: perfil.php'); exit;
        } catch (Exception $e) {
            $_SESSION['mensaje'] = "Error: ".$e->getMessage();
            $_SESSION['tipo_mensaje'] = "danger";
        }
    }
}

$titulo_pagina = 'Mi Perfil';
include 'includes/header.php';
?>

<style>
.page-back { display:inline-flex;align-items:center;gap:8px;color:var(--text-secondary);font-size:13px;text-decoration:none;margin-bottom:20px;transition:color .15s; }
.page-back:hover { color:var(--accent); }
.profile-avatar { width:80px;height:80px;border-radius:50%;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:700;margin:0 auto 16px; }
.section-divider { border-top:1px solid var(--border);margin:24px 0;padding-top:20px; }
</style>

<a href="dashboard.php" class="page-back"><i class="fas fa-arrow-left"></i> Volver al Dashboard</a>

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

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card mb-4" style="text-align:center;padding:32px 24px;">
            <div class="profile-avatar"><?php echo strtoupper(substr($usuario['nombre'],0,1)); ?></div>
            <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($usuario['nombre']); ?></h5>
            <div style="font-size:13px;color:var(--text-secondary);">@<?php echo htmlspecialchars($usuario['usuario']); ?></div>
            <div style="margin-top:8px;">
                <span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;<?php echo $usuario['rol']==='admin' ? 'background:#fef2f2;color:#dc2626;' : 'background:#eff6ff;color:#2563eb;'; ?>">
                    <i class="fas fa-<?php echo $usuario['rol']==='admin' ? 'shield-alt' : 'user'; ?>"></i>
                    <?php echo $usuario['rol']==='admin' ? 'Administrador' : 'Usuario'; ?>
                </span>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="fas fa-user-edit me-2 text-primary"></i>Editar Perfil</div>
            <div class="p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px;font-weight:500;">Nombre Completo</label>
                        <input type="text" class="form-control form-control-sm" name="nombre"
                               value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px;font-weight:500;">Email</label>
                        <input type="email" class="form-control form-control-sm" name="email"
                               value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px;font-weight:500;">Nombre de Usuario</label>
                        <input type="text" class="form-control form-control-sm" name="usuario"
                               value="<?php echo htmlspecialchars($usuario['usuario']); ?>" required>
                    </div>

                    <div class="section-divider">
                        <div style="font-size:14px;font-weight:600;margin-bottom:4px;">Cambiar Contraseña</div>
                        <div style="font-size:12px;color:var(--text-secondary);margin-bottom:16px;">Deje estos campos en blanco si no desea cambiarla.</div>
                        <div class="mb-3">
                            <label class="form-label" style="font-size:13px;font-weight:500;">Contraseña Actual</label>
                            <input type="password" class="form-control form-control-sm" name="password_actual">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="font-size:13px;font-weight:500;">Nueva Contraseña</label>
                            <input type="password" class="form-control form-control-sm" name="password_nuevo">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="font-size:13px;font-weight:500;">Confirmar Nueva Contraseña</label>
                            <input type="password" class="form-control form-control-sm" name="password_confirmar">
                        </div>
                    </div>

                    <button type="submit" name="actualizar_perfil" class="btn btn-primary w-100 btn-sm">
                        <i class="fas fa-save me-1"></i> Guardar Cambios
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
