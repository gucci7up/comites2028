<?php
require_once 'includes/auth.php';
require_once 'includes/funciones.php';
require_once 'db/config.php';

// Verificar que el usuario esté autenticado
requiereAutenticacion();

// Procesar el formulario de creación de comité
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $municipio = trim($_POST['municipio']);
    $usuario_id = $_SESSION['usuario_id'];
    
    // Validar datos
    $errores = [];
    
    if (empty($nombre)) {
        $errores[] = "El nombre del comité es obligatorio.";
    }
    
    if (empty($municipio)) {
        $errores[] = "El municipio es obligatorio.";
    }
    
    // Si no hay errores, crear el comité
    if (empty($errores)) {
        try {
            $conn = conectarDB();
            
            $stmt = $conn->prepare("INSERT INTO comites (nombre, municipio, creado_por, fecha_creacion) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("ssi", $nombre, $municipio, $usuario_id);
            $stmt->execute();
            
            $comite_id = $conn->insert_id;
            
            // Redirigir a la página de edición del comité
            $_SESSION['mensaje'] = "Comité creado correctamente. Ahora puede agregar un coordinador y miembros.";
            $_SESSION['tipo_mensaje'] = "success";
            
            header("Location: editar_comite.php?id=$comite_id");
            exit;
            
        } catch (Exception $e) {
            $errores[] = "Error al crear el comité: " . $e->getMessage();
        }
    }
}

// Título de la página
$titulo_pagina = 'Crear Comité';

// Incluir el header
include 'includes/header.php';
?>

<!-- Mensajes de error -->
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

<!-- Botones de Acción -->
<div class="mb-4">
    <a href="comites.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Volver a Comités
    </a>
</div>

<!-- Formulario de Creación -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Nuevo Comité</h6>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre del Comité</label>
                <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo isset($nombre) ? htmlspecialchars($nombre) : ''; ?>" required>
                <div class="form-text">Ejemplo: Comité Afectivo Zona Norte</div>
            </div>
            
            <div class="mb-3">
                <label for="municipio" class="form-label">Municipio</label>
                <input type="text" class="form-control" id="municipio" name="municipio" value="<?php echo isset($municipio) ? htmlspecialchars($municipio) : ''; ?>" required>
                <div class="form-text">Ejemplo: Santo Domingo Este</div>
            </div>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Después de crear el comité, podrá asignar un coordinador y agregar miembros.
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Crear Comité
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Instrucciones -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Instrucciones</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card border-left-primary h-100">
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <i class="fas fa-clipboard-list fa-3x text-primary mb-3"></i>
                        </div>
                        <h5 class="text-center">1. Crear Comité</h5>
                        <p class="text-center">Complete el formulario con el nombre del comité y el municipio al que pertenece.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card border-left-success h-100">
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <i class="fas fa-user-tie fa-3x text-success mb-3"></i>
                        </div>
                        <h5 class="text-center">2. Asignar Coordinador</h5>
                        <p class="text-center">Busque y asigne un coordinador para el comité utilizando su número de cédula.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card border-left-info h-100">
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <i class="fas fa-users fa-3x text-info mb-3"></i>
                        </div>
                        <h5 class="text-center">3. Agregar Miembros</h5>
                        <p class="text-center">Busque y agregue miembros al comité utilizando sus números de cédula o manualmente.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>