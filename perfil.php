<?php
require_once 'includes/auth.php';
require_once 'db/config.php';

// Verificar que el usuario esté autenticado
requiereAutenticacion();

$conn = conectarDB();
$usuario_id = $_SESSION['usuario_id'];

// Obtener datos del usuario
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();

if (!$usuario) {
    // Si no se encuentra el usuario, redirigir al dashboard
    header('Location: dashboard.php');
    exit;
}

// Procesar formulario de actualización de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_perfil'])) {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $usuario_nombre = trim($_POST['usuario']);
    $password_actual = trim($_POST['password_actual']);
    $password_nuevo = trim($_POST['password_nuevo']);
    $password_confirmar = trim($_POST['password_confirmar']);
    
    // Validar datos
    $errores = [];
    
    if (empty($nombre)) {
        $errores[] = "El nombre es obligatorio.";
    }
    
    if (empty($email)) {
        $errores[] = "El email es obligatorio.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El email no es válido.";
    }
    
    if (empty($usuario_nombre)) {
        $errores[] = "El nombre de usuario es obligatorio.";
    }
    
    // Verificar si el usuario ya existe (solo si se cambió)
    if ($usuario_nombre !== $usuario['usuario']) {
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ? AND id != ?");
        $stmt->bind_param("si", $usuario_nombre, $usuario_id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        if ($resultado->fetch_assoc()) {
            $errores[] = "El nombre de usuario ya está en uso.";
        }
    }
    
    // Si se quiere cambiar la contraseña
    if (!empty($password_nuevo)) {
        // Verificar contraseña actual
        if (empty($password_actual)) {
            $errores[] = "Debe ingresar su contraseña actual para cambiarla.";
        } elseif (!password_verify($password_actual, $usuario['password'])) {
            $errores[] = "La contraseña actual es incorrecta.";
        }
        
        // Verificar que la nueva contraseña y la confirmación coincidan
        if ($password_nuevo !== $password_confirmar) {
            $errores[] = "La nueva contraseña y su confirmación no coinciden.";
        }
    }
    
    // Si no hay errores, actualizar el perfil
    if (empty($errores)) {
        try {
            if (!empty($password_nuevo)) {
                // Actualizar con nueva contraseña
                $hash = password_hash($password_nuevo, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, email = ?, usuario = ?, password = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $nombre, $email, $usuario_nombre, $hash, $usuario_id);
                $stmt->execute();
            } else {
                // Actualizar sin cambiar contraseña
                $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, email = ?, usuario = ? WHERE id = ?");
                $stmt->bind_param("sssi", $nombre, $email, $usuario_nombre, $usuario_id);
                $stmt->execute();
            }
            
            // Actualizar datos de sesión
            $_SESSION['usuario_nombre'] = $usuario_nombre;
            $_SESSION['nombre'] = $nombre;
            
            $_SESSION['mensaje'] = "Perfil actualizado correctamente.";
            $_SESSION['tipo_mensaje'] = "success";
            
            // Redireccionar para evitar reenvío del formulario
            header('Location: perfil.php');
            exit;
            
        } catch (Exception $e) {
            $_SESSION['mensaje'] = "Error al actualizar el perfil: " . $e->getMessage();
            $_SESSION['tipo_mensaje'] = "danger";
        }
    }
}

// Título de la página
$titulo_pagina = 'Mi Perfil';

// Incluir el header
include 'includes/header.php';
?>

<!-- Mensajes de alerta -->
<?php if (isset($_SESSION['mensaje'])): ?>
<div class="alert alert-<?php echo $_SESSION['tipo_mensaje']; ?> alert-dismissible fade show" role="alert">
    <?php echo $_SESSION['mensaje']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php 
    unset($_SESSION['mensaje']);
    unset($_SESSION['tipo_mensaje']);
endif; 
?>

<!-- Mensajes de error del formulario -->
<?php if (!empty($errores)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <ul class="mb-0">
        <?php foreach ($errores as $error): ?>
        <li><?php echo $error; ?></li>
        <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Mi Perfil</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre Completo</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="usuario" class="form-label">Nombre de Usuario</label>
                        <input type="text" class="form-control" id="usuario" name="usuario" value="<?php echo htmlspecialchars($usuario['usuario']); ?>" required>
                    </div>
                    
                    <hr class="my-4">
                    <h5>Cambiar Contraseña</h5>
                    <p class="text-muted small">Deje estos campos en blanco si no desea cambiar su contraseña.</p>
                    
                    <div class="mb-3">
                        <label for="password_actual" class="form-label">Contraseña Actual</label>
                        <input type="password" class="form-control" id="password_actual" name="password_actual">
                    </div>
                    
                    <div class="mb-3">
                        <label for="password_nuevo" class="form-label">Nueva Contraseña</label>
                        <input type="password" class="form-control" id="password_nuevo" name="password_nuevo">
                    </div>
                    
                    <div class="mb-3">
                        <label for="password_confirmar" class="form-label">Confirmar Nueva Contraseña</label>
                        <input type="password" class="form-control" id="password_confirmar" name="password_confirmar">
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" name="actualizar_perfil" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver al Dashboard
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>