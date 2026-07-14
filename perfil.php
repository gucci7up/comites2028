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

$titulo_pagina    = 'Mi Perfil';
$subtitulo_pagina = 'Datos de tu cuenta';
include 'includes/header.php';
?>

<style>
.perfil-layout { display:grid; grid-template-columns:300px minmax(0,1fr); gap:22px; align-items:start; }
@media (max-width:900px) { .perfil-layout { grid-template-columns:1fr; } }
.step-card { background:#fff; border-radius:var(--radius-card); padding:26px; box-shadow:var(--shadow-sm); }
.step-title { font-size:15px; font-weight:700; margin-bottom:18px; }
.field-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }
@media (max-width:560px) { .field-row { grid-template-columns:1fr; } }
.profile-avatar { width:96px;height:96px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent-light));color:#fff;display:flex;align-items:center;justify-content:center;font-size:34px;font-weight:700;margin:0 auto 16px; }
.profile-role-badge { display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px;background:var(--accent-tint);color:var(--accent); }
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

<div class="perfil-layout">
  <!-- AVATAR CARD -->
  <div class="step-card" style="text-align:center;">
    <div class="profile-avatar"><?php echo strtoupper(substr($usuario['nombre'],0,1)); ?></div>
    <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($usuario['nombre']); ?></h5>
    <div style="font-size:13px;color:var(--text-tertiary);margin-bottom:12px;">@<?php echo htmlspecialchars($usuario['usuario']); ?></div>
    <span class="profile-role-badge">
        <i class="fas fa-<?php echo $usuario['rol']==='admin' ? 'shield-alt' : ($usuario['rol']==='supervisor' ? 'user-check' : 'user'); ?>"></i>
        <?php echo ucfirst($usuario['rol']); ?>
    </span>
  </div>

  <!-- FORM -->
  <div style="min-width:0;display:flex;flex-direction:column;gap:22px;">
    <div class="step-card">
        <div class="step-title">Información personal</div>
        <form method="POST">
            <div class="field-row">
                <div>
                    <label class="lbl">Nombre Completo</label>
                    <input type="text" class="fld" name="nombre"
                           value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                </div>
                <div>
                    <label class="lbl">Email</label>
                    <input type="email" class="fld" name="email"
                           value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                </div>
            </div>
            <div style="margin-bottom:22px;">
                <label class="lbl">Nombre de Usuario</label>
                <input type="text" class="fld" name="usuario"
                       value="<?php echo htmlspecialchars($usuario['usuario']); ?>" required>
            </div>

            <div style="border-top:1px solid #f0f0f6;padding-top:20px;margin-bottom:20px;">
                <div style="font-size:14px;font-weight:700;margin-bottom:4px;">Cambiar Contraseña</div>
                <div style="font-size:12px;color:var(--text-tertiary);margin-bottom:16px;">Deje estos campos en blanco si no desea cambiarla.</div>
                <div style="margin-bottom:16px;">
                    <label class="lbl">Contraseña Actual</label>
                    <input type="password" class="fld" name="password_actual">
                </div>
                <div class="field-row" style="margin-bottom:0;">
                    <div>
                        <label class="lbl">Nueva Contraseña</label>
                        <input type="password" class="fld" name="password_nuevo">
                    </div>
                    <div>
                        <label class="lbl">Confirmar Nueva Contraseña</label>
                        <input type="password" class="fld" name="password_confirmar">
                    </div>
                </div>
            </div>

            <button type="submit" name="actualizar_perfil" class="btn btn-primary w-100">
                <i class="fas fa-save me-1"></i> Guardar Cambios
            </button>
        </form>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
