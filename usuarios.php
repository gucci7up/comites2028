<?php
require_once 'includes/auth.php';
require_once 'includes/funciones.php';
require_once 'db/config.php';

// Verificar que el usuario esté autenticado y sea administrador
requiereAdmin();

$conn = conectarDB();

// Procesar formulario de creación/edición de usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_usuario'])) {
    $usuario_id = isset($_POST['usuario_id']) ? $_POST['usuario_id'] : null;
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['password']);
    $rol = $_POST['rol'];
    
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
    
    if (empty($usuario)) {
        $errores[] = "El nombre de usuario es obligatorio.";
    }
    
    // Verificar si el usuario ya existe (solo para nuevos usuarios)
    if (!$usuario_id) {
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $resultado = $stmt->get_result();
        if ($resultado->fetch_assoc()) {
            $errores[] = "El nombre de usuario ya está en uso.";
        }
        
        if (empty($password)) {
            $errores[] = "La contraseña es obligatoria para nuevos usuarios.";
        }
    }
    
    // Si no hay errores, guardar el usuario
    if (empty($errores)) {
        try {
            if ($usuario_id) {
                // Actualizar usuario existente
                if (!empty($password)) {
                    // Si se proporcionó una nueva contraseña, actualizarla
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, email = ?, usuario = ?, password = ?, rol = ? WHERE id = ?");
                    $stmt->bind_param("sssssi", $nombre, $email, $usuario, $hash, $rol, $usuario_id);
                    $stmt->execute();
                } else {
                    // Si no se proporcionó contraseña, mantener la actual
                    $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, email = ?, usuario = ?, rol = ? WHERE id = ?");
                    $stmt->bind_param("ssssi", $nombre, $email, $usuario, $rol, $usuario_id);
                    $stmt->execute();
                }
                
                $_SESSION['mensaje'] = "Usuario actualizado correctamente.";
            } else {
                // Crear nuevo usuario
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, usuario, password, rol, fecha_registro) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("sssss", $nombre, $email, $usuario, $hash, $rol);
                $stmt->execute();
                
                $_SESSION['mensaje'] = "Usuario creado correctamente.";
            }
            
            $_SESSION['tipo_mensaje'] = "success";
            
        } catch (Exception $e) {
            $_SESSION['mensaje'] = "Error al guardar el usuario: " . $e->getMessage();
            $_SESSION['tipo_mensaje'] = "danger";
        }
        
        // Redireccionar para evitar reenvío del formulario
        header('Location: usuarios.php');
        exit;
    }
}

// Procesar eliminación de usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_usuario'])) {
    $usuario_id = $_POST['usuario_id'];
    
    // No permitir eliminar al propio usuario
    if ($usuario_id == $_SESSION['usuario_id']) {
        $_SESSION['mensaje'] = "No puede eliminar su propio usuario.";
        $_SESSION['tipo_mensaje'] = "danger";
    } else {
        try {
            $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
            
            $_SESSION['mensaje'] = "Usuario eliminado correctamente.";
            $_SESSION['tipo_mensaje'] = "success";
            
        } catch (Exception $e) {
            $_SESSION['mensaje'] = "Error al eliminar el usuario: " . $e->getMessage();
            $_SESSION['tipo_mensaje'] = "danger";
        }
    }
    
    // Redireccionar
    header('Location: usuarios.php');
    exit;
}

// Obtener todos los usuarios
$stmt = $conn->prepare("SELECT * FROM usuarios ORDER BY fecha_registro DESC");
$stmt->execute();
$resultado = $stmt->get_result();
$usuarios = [];
while ($fila = $resultado->fetch_assoc()) {
    $usuarios[] = $fila;
}

// Título de la página
$titulo_pagina = 'Gestión de Usuarios';

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

<!-- Botón para crear nuevo usuario -->
<div class="mb-4">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#usuarioModal">
        <i class="fas fa-user-plus"></i> Nuevo Usuario
    </button>
</div>

<!-- Lista de Usuarios -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Usuarios del Sistema</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Fecha Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['usuario']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $usuario['rol'] === 'admin' ? 'danger' : 'primary'; ?>">
                                <?php echo $usuario['rol'] === 'admin' ? 'Administrador' : 'Usuario'; ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary" onclick="editarUsuario(<?php echo $usuario['id']; ?>, '<?php echo htmlspecialchars(addslashes($usuario['nombre'])); ?>', '<?php echo htmlspecialchars(addslashes($usuario['email'])); ?>', '<?php echo htmlspecialchars(addslashes($usuario['usuario'])); ?>', '<?php echo $usuario['rol']; ?>')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if ($usuario['id'] != $_SESSION['usuario_id']): ?>
                            <button type="button" class="btn btn-sm btn-danger" onclick="confirmarEliminar(<?php echo $usuario['id']; ?>, '<?php echo htmlspecialchars(addslashes($usuario['nombre'])); ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para Crear/Editar Usuario -->
<div class="modal fade" id="usuarioModal" tabindex="-1" aria-labelledby="usuarioModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="usuarioModalLabel">Nuevo Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="usuarioForm" method="POST" action="">
                    <input type="hidden" id="usuario_id" name="usuario_id">
                    
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre Completo</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="usuario" class="form-label">Nombre de Usuario</label>
                        <input type="text" class="form-control" id="usuario" name="usuario" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password">
                        <div id="passwordHelp" class="form-text">Dejar en blanco para mantener la contraseña actual (solo al editar).</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="rol" class="form-label">Rol</label>
                        <select class="form-select" id="rol" name="rol" required>
                            <option value="usuario">Usuario</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" name="guardar_usuario" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Confirmar Eliminación -->
<div class="modal fade" id="eliminarModal" tabindex="-1" aria-labelledby="eliminarModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eliminarModalLabel">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ¿Está seguro que desea eliminar al usuario <span id="usuario-nombre"></span>? Esta acción no se puede deshacer.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" action="">
                    <input type="hidden" id="eliminar_usuario_id" name="usuario_id">
                    <input type="hidden" name="eliminar_usuario" value="1">
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Inicializar DataTable
$(document).ready(function() {
    $('#dataTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json'
        }
    });
});

// Función para editar usuario
function editarUsuario(id, nombre, email, usuario, rol) {
    // Cambiar título del modal
    document.getElementById('usuarioModalLabel').textContent = 'Editar Usuario';
    
    // Llenar el formulario con los datos del usuario
    document.getElementById('usuario_id').value = id;
    document.getElementById('nombre').value = nombre;
    document.getElementById('email').value = email;
    document.getElementById('usuario').value = usuario;
    document.getElementById('password').value = '';
    document.getElementById('rol').value = rol;
    
    // Mostrar el modal
    var modal = new bootstrap.Modal(document.getElementById('usuarioModal'));
    modal.show();
}

// Función para confirmar eliminación
function confirmarEliminar(id, nombre) {
    document.getElementById('eliminar_usuario_id').value = id;
    document.getElementById('usuario-nombre').textContent = nombre;
    var modal = new bootstrap.Modal(document.getElementById('eliminarModal'));
    modal.show();
}

// Resetear el formulario cuando se abre el modal para crear un nuevo usuario
document.getElementById('usuarioModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('usuarioForm').reset();
    document.getElementById('usuario_id').value = '';
    document.getElementById('usuarioModalLabel').textContent = 'Nuevo Usuario';
});
</script>

<?php include 'includes/footer.php'; ?>