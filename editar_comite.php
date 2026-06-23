<?php
require_once 'includes/auth.php';
require_once 'includes/funciones.php';
require_once 'db/config.php';

// Verificar que el usuario esté autenticado
requiereAutenticacion();

// Verificar que se proporcionó un ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: comites.php');
    exit;
}

$comite_id = $_GET['id'];
$conn = conectarDB();

// Obtener información del comité
$stmt = $conn->prepare("SELECT * FROM comites WHERE id = ?");
$stmt->bind_param("i", $comite_id);
$stmt->execute();
$resultado = $stmt->get_result();
$comite = $resultado->fetch_assoc();

// Verificar que el comité existe
if (!$comite) {
    header('Location: comites.php');
    exit;
}

// Obtener información del coordinador
$coordinador = null;
$stmt = $conn->prepare("SELECT c.* FROM coordinadores c 
                      JOIN comite_coordinador cc ON c.id = cc.coordinador_id 
                      WHERE cc.comite_id = ?");
$stmt->bind_param("i", $comite_id);
$stmt->execute();
$resultado = $stmt->get_result();
$coordinador = $resultado->fetch_assoc();

// Obtener miembros del comité
$stmt = $conn->prepare("SELECT m.* FROM miembros m 
                      JOIN comite_miembro cm ON m.id = cm.miembro_id 
                      WHERE cm.comite_id = ? ORDER BY m.fecha_registro");
$stmt->bind_param("i", $comite_id);
$stmt->execute();
$resultado = $stmt->get_result();
$miembros = [];
while ($fila = $resultado->fetch_assoc()) {
    $miembros[] = $fila;
}

// Procesar formulario de actualización del comité
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_comite'])) {
    $nombre = trim($_POST['nombre']);
    $municipio = trim($_POST['municipio']);
    
    // Validar datos
    $errores = [];
    
    if (empty($nombre)) {
        $errores[] = "El nombre del comité es obligatorio.";
    }
    
    if (empty($municipio)) {
        $errores[] = "El municipio es obligatorio.";
    }
    
    // Si no hay errores, actualizar el comité
    if (empty($errores)) {
        try {
            $stmt = $conn->prepare("UPDATE comites SET nombre = ?, municipio = ?, fecha_modificacion = NOW() WHERE id = ?");
            $stmt->bind_param("ssi", $nombre, $municipio, $comite_id);
            $stmt->execute();
            
            // Actualizar datos en memoria
            $comite['nombre'] = $nombre;
            $comite['municipio'] = $municipio;
            
            $_SESSION['mensaje'] = "Comité actualizado correctamente.";
            $_SESSION['tipo_mensaje'] = "success";
            
        } catch (Exception $e) {
            $_SESSION['mensaje'] = "Error al actualizar el comité: " . $e->getMessage();
            $_SESSION['tipo_mensaje'] = "danger";
        }
    } else {
        $_SESSION['mensaje'] = implode("<br>", $errores);
        $_SESSION['tipo_mensaje'] = "danger";
    }
}

// Procesar eliminación de miembro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_miembro'])) {
    $miembro_id = $_POST['miembro_id'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM comite_miembro WHERE miembro_id = ? AND comite_id = ?");
        $stmt->bind_param("ii", $miembro_id, $comite_id);
        $stmt->execute();
        
        // Actualizar lista de miembros
        $stmt = $conn->prepare("SELECT m.* FROM miembros m 
                              JOIN comite_miembro cm ON m.id = cm.miembro_id 
                              WHERE cm.comite_id = ? ORDER BY m.fecha_registro");
        $stmt->bind_param("i", $comite_id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $miembros = [];
        while ($fila = $resultado->fetch_assoc()) {
            $miembros[] = $fila;
        }
        
        $_SESSION['mensaje'] = "Miembro eliminado correctamente.";
        $_SESSION['tipo_mensaje'] = "success";
        
    } catch (Exception $e) {
        $_SESSION['mensaje'] = "Error al eliminar el miembro: " . $e->getMessage();
        $_SESSION['tipo_mensaje'] = "danger";
    }
}

// Título de la página
$titulo_pagina = 'Editar Comité';
include 'includes/header.php';
?>

<style>
.page-back { display:inline-flex;align-items:center;gap:8px;color:var(--text-secondary);font-size:13px;text-decoration:none;margin-bottom:20px;transition:color .15s; }
.page-back:hover { color:var(--accent); }
.coord-avatar-lg { width:80px;height:80px;border-radius:50%;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:700;margin:0 auto 12px; }
</style>

<?php if (isset($_SESSION['mensaje'])): ?>
<div class="alert alert-<?php echo $_SESSION['tipo_mensaje']; ?> alert-dismissible fade show mb-4" role="alert">
    <?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje'],$_SESSION['tipo_mensaje']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <a href="comites.php" class="page-back"><i class="fas fa-arrow-left"></i> Volver a Comités</a>
    <a href="ver_comite.php?id=<?php echo $comite_id; ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-eye me-1"></i> Ver Comité
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-pen me-2 text-primary"></i>Información del Comité</div>
            <div class="p-4">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre del Comité</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($comite['nombre']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="municipio" class="form-label">Municipio</label>
                        <input type="text" class="form-control" id="municipio" name="municipio" value="<?php echo htmlspecialchars($comite['municipio']); ?>" required>
                    </div>
                    
                    <button type="submit" name="actualizar_comite" class="btn btn-primary w-100 btn-sm">
                        <i class="fas fa-save me-1"></i> Guardar Cambios
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header">
                <span><i class="fas fa-user-tie me-2 text-primary"></i>Coordinador</span>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#buscarCoordinadorModal">
                    <i class="fas fa-search me-1"></i> Buscar
                </button>
            </div>
            <div class="p-4" id="coordinador-info">
                <?php if ($coordinador): ?>
                <div class="text-center mb-3">
                    <?php if (!empty($coordinador['foto'])): ?>
                    <img src="data:image/jpeg;base64,<?php echo $coordinador['foto']; ?>" alt="Foto"
                         style="width:80px;height:80px;border-radius:50%;object-fit:cover;margin-bottom:8px;">
                    <?php else: ?>
                    <div class="coord-avatar-lg"><?php echo strtoupper(substr($coordinador['nombre_completo'] ?? $coordinador['nombre'] ?? '?', 0, 1)); ?></div>
                    <?php endif; ?>
                    <div style="font-weight:600;font-size:14px;"><?php echo htmlspecialchars($coordinador['nombre_completo'] ?? $coordinador['nombre'] ?? '—'); ?></div>
                </div>
                <div style="font-size:13px;" class="mb-3">
                    <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-secondary">Cédula</span><span><?php echo htmlspecialchars($coordinador['cedula'] ?? '—'); ?></span></div>
                    <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-secondary">Teléfono</span><span><?php echo htmlspecialchars($coordinador['telefono'] ?? '—'); ?></span></div>
                    <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-secondary">Municipio</span><span><?php echo htmlspecialchars($coordinador['municipio'] ?? '—'); ?></span></div>
                    <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-secondary">Recinto</span><span><?php echo htmlspecialchars($coordinador['recinto'] ?? '—'); ?></span></div>
                    <div class="d-flex justify-content-between py-1"><span class="text-secondary">Colegio</span><span><?php echo htmlspecialchars($coordinador['colegio'] ?? '—'); ?></span></div>
                </div>
                <button class="btn btn-outline-danger w-100 btn-sm" data-bs-toggle="modal" data-bs-target="#eliminarCoordinadorModal">
                    <i class="fas fa-user-minus me-1"></i> Quitar Coordinador
                </button>
                <?php else: ?>
                <div style="text-align:center;padding:30px 0;color:var(--text-secondary);">
                    <i class="fas fa-user-slash" style="font-size:36px;opacity:.2;display:block;margin-bottom:12px;"></i>
                    <p style="font-size:13px;margin:0 0 12px;">Sin coordinador asignado.</p>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#buscarCoordinadorModal">
                        <i class="fas fa-user-plus me-1"></i> Asignar Coordinador
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Members -->
<div class="card mb-4" id="miembros">
    <div class="card-header">
        <span><i class="fas fa-users me-2 text-primary"></i>Miembros del Comité
            <span style="font-size:12px;color:var(--text-secondary);font-weight:400;"> — <?php echo count($miembros); ?></span>
        </span>
        <div class="d-flex gap-2">
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#buscarMiembroModal">
                <i class="fas fa-id-card me-1"></i> Por Cédula
            </button>
            <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#agregarMiembroModal">
                <i class="fas fa-user-plus me-1"></i> Manual
            </button>
        </div>
    </div>
    <?php if (count($miembros) > 0): ?>
    <div class="table-responsive">
        <table class="table mb-0" id="miembrosTable">
            <thead>
                <tr>
                    <th>Nombre</th><th>Cédula</th><th>Teléfono</th>
                    <th>Municipio</th><th>Recinto</th><th>Colegio</th><th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($miembros as $miembro): ?>
                <tr>
                    <td style="font-weight:500;font-size:13px;"><?php echo htmlspecialchars($miembro['nombre_completo'] ?? $miembro['nombre'] ?? '—'); ?></td>
                    <td style="font-size:13px;"><?php echo htmlspecialchars($miembro['cedula'] ?? '—'); ?></td>
                    <td style="font-size:13px;"><?php echo htmlspecialchars($miembro['telefono'] ?? '—'); ?></td>
                    <td style="font-size:13px;"><?php echo htmlspecialchars($miembro['municipio'] ?? '—'); ?></td>
                    <td style="font-size:13px;"><?php echo htmlspecialchars($miembro['recinto'] ?? '—'); ?></td>
                    <td style="font-size:13px;"><?php echo htmlspecialchars($miembro['colegio'] ?? '—'); ?></td>
                    <td>
                        <button class="action-link danger" onclick="confirmarEliminarMiembro(<?php echo $miembro['id']; ?>,'<?php echo htmlspecialchars(addslashes($miembro['nombre_completo'] ?? $miembro['nombre'] ?? '')); ?>')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:40px 20px;color:var(--text-secondary);">
        <i class="fas fa-users-slash" style="font-size:36px;opacity:.2;display:block;margin-bottom:12px;"></i>
        <p style="font-size:13px;margin:0 0 12px;">No hay miembros registrados.</p>
        <div class="d-flex gap-2 justify-content-center">
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#buscarMiembroModal">Por Cédula</button>
            <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#agregarMiembroModal">Manual</button>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.action-link { width:28px;height:28px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;border:1px solid var(--border);color:var(--text-secondary);background:none;cursor:pointer;transition:all .15s; }
.action-link.danger:hover { border-color:var(--danger);color:var(--danger);background:#fef2f2; }
</style>

<!-- Modal para Buscar Coordinador -->
<div class="modal fade" id="buscarCoordinadorModal" tabindex="-1" aria-labelledby="buscarCoordinadorModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="buscarCoordinadorModalLabel">Buscar Coordinador por Cédula</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="cedula-coordinador" class="form-label">Cédula</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="cedula-coordinador" placeholder="Ingrese la cédula">
                        <button class="btn btn-primary" type="button" id="buscar-coordinador-btn">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                    <div class="form-text">Ingrese la cédula sin guiones. Ejemplo: 00112345678</div>
                </div>
                
                <div id="resultado-coordinador" class="d-none">
                    <div class="text-center mb-3">
                        <img id="foto-coordinador" src="" alt="Foto" class="img-fluid rounded-circle mb-2" style="width: 120px; height: 120px; object-fit: cover;">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Nombre:</strong> <span id="nombre-coordinador"></span></p>
                            <p><strong>Cédula:</strong> <span id="cedula-coordinador-resultado"></span></p>
                            <p><strong>Fecha Nac.:</strong> <span id="fecha-nac-coordinador"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Municipio:</strong> <span id="municipio-coordinador"></span></p>
                            <p><strong>Recinto:</strong> <span id="recinto-coordinador"></span></p>
                            <p><strong>Colegio:</strong> <span id="colegio-coordinador"></span></p>
                        </div>
                    </div>
                    
                    <form id="form-guardar-coordinador" method="POST" action="guardar_coordinador.php">
                        <input type="hidden" name="comite_id" value="<?php echo $comite_id; ?>">
                        <input type="hidden" name="cedula" id="cedula-coordinador-hidden">
                        <input type="hidden" name="nombre" id="nombre-coordinador-hidden">
                        <input type="hidden" name="fecha_nacimiento" id="fecha-nac-coordinador-hidden">
                        <input type="hidden" name="municipio" id="municipio-coordinador-hidden">
                        <input type="hidden" name="recinto" id="recinto-coordinador-hidden">
                        <input type="hidden" name="colegio" id="colegio-coordinador-hidden">
                        <input type="hidden" name="foto" id="foto-coordinador-hidden">
                        
                        <div class="mb-3">
                            <label for="telefono-coordinador" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="telefono-coordinador" name="telefono" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email-coordinador" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email-coordinador" name="email">
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar como Coordinador
                            </button>
                        </div>
                    </form>
                </div>
                
                <div id="error-coordinador" class="alert alert-danger d-none" role="alert">
                    No se encontraron resultados para la cédula ingresada.
                </div>
                
                <div id="loading-coordinador" class="text-center d-none">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p>Buscando información...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Eliminar Coordinador -->
<div class="modal fade" id="eliminarCoordinadorModal" tabindex="-1" aria-labelledby="eliminarCoordinadorModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eliminarCoordinadorModalLabel">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ¿Está seguro que desea quitar a <strong><?php echo $coordinador ? htmlspecialchars($coordinador['nombre']) : ''; ?></strong> como coordinador de este comité?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" action="quitar_coordinador.php">
                    <input type="hidden" name="comite_id" value="<?php echo $comite_id; ?>">
                    <button type="submit" class="btn btn-danger">Quitar Coordinador</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Buscar Miembro -->
<div class="modal fade" id="buscarMiembroModal" tabindex="-1" aria-labelledby="buscarMiembroModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="buscarMiembroModalLabel">Buscar Miembro por Cédula</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="cedula-miembro" class="form-label">Cédula</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="cedula-miembro" placeholder="Ingrese la cédula">
                        <button class="btn btn-primary" type="button" id="buscar-miembro-btn">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                    <div class="form-text">Ingrese la cédula sin guiones. Ejemplo: 00112345678</div>
                </div>
                
                <div id="resultado-miembro" class="d-none">
                    <div class="text-center mb-3">
                        <img id="foto-miembro" src="" alt="Foto" class="img-fluid rounded-circle mb-2" style="width: 120px; height: 120px; object-fit: cover;">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Nombre:</strong> <span id="nombre-miembro"></span></p>
                            <p><strong>Cédula:</strong> <span id="cedula-miembro-resultado"></span></p>
                            <p><strong>Fecha Nac.:</strong> <span id="fecha-nac-miembro"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Municipio:</strong> <span id="municipio-miembro"></span></p>
                            <p><strong>Recinto:</strong> <span id="recinto-miembro"></span></p>
                            <p><strong>Colegio:</strong> <span id="colegio-miembro"></span></p>
                        </div>
                    </div>
                    
                    <form id="form-guardar-miembro" method="POST" action="guardar_miembro.php">
                        <input type="hidden" name="comite_id" value="<?php echo $comite_id; ?>">
                        <input type="hidden" name="cedula" id="cedula-miembro-hidden">
                        <input type="hidden" name="nombre" id="nombre-miembro-hidden">
                        <input type="hidden" name="fecha_nacimiento" id="fecha-nac-miembro-hidden">
                        <input type="hidden" name="municipio" id="municipio-miembro-hidden">
                        <input type="hidden" name="recinto" id="recinto-miembro-hidden">
                        <input type="hidden" name="colegio" id="colegio-miembro-hidden">
                        <input type="hidden" name="foto" id="foto-miembro-hidden">
                        
                        <div class="mb-3">
                            <label for="telefono-miembro" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="telefono-miembro" name="telefono" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email-miembro" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email-miembro" name="email">
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Agregar como Miembro
                            </button>
                        </div>
                    </form>
                </div>
                
                <div id="error-miembro" class="alert alert-danger d-none" role="alert">
                    No se encontraron resultados para la cédula ingresada.
                </div>
                
                <div id="loading-miembro" class="text-center d-none">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p>Buscando información...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Agregar Miembro Manualmente -->
<div class="modal fade" id="agregarMiembroModal" tabindex="-1" aria-labelledby="agregarMiembroModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="agregarMiembroModalLabel">Agregar Miembro Manualmente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="guardar_miembro_manual.php">
                    <input type="hidden" name="comite_id" value="<?php echo $comite_id; ?>">
                    
                    <div class="mb-3">
                        <label for="nombre-manual" class="form-label">Nombre Completo</label>
                        <input type="text" class="form-control" id="nombre-manual" name="nombre" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="cedula-manual" class="form-label">Cédula</label>
                        <input type="text" class="form-control" id="cedula-manual" name="cedula" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="telefono-manual" class="form-label">Teléfono</label>
                        <input type="tel" class="form-control" id="telefono-manual" name="telefono" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email-manual" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email-manual" name="email">
                    </div>
                    
                    <div class="mb-3">
                        <label for="municipio-manual" class="form-label">Municipio</label>
                        <input type="text" class="form-control" id="municipio-manual" name="municipio" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="recinto-manual" class="form-label">Recinto</label>
                        <input type="text" class="form-control" id="recinto-manual" name="recinto">
                    </div>
                    
                    <div class="mb-3">
                        <label for="colegio-manual" class="form-label">Colegio</label>
                        <input type="text" class="form-control" id="colegio-manual" name="colegio">
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Miembro
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Confirmar Eliminación de Miembro -->
<div class="modal fade" id="eliminarMiembroModal" tabindex="-1" aria-labelledby="eliminarMiembroModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eliminarMiembroModalLabel">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ¿Está seguro que desea eliminar a <span id="miembro-nombre"></span> de este comité?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" action="">
                    <input type="hidden" id="miembro-id" name="miembro_id">
                    <input type="hidden" name="eliminar_miembro" value="1">
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Inicializar DataTable
$(document).ready(function() {
    $('#miembrosTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json'
        }
    });
    
    // Buscar coordinador por cédula
    $('#buscar-coordinador-btn').click(function() {
        const cedula = $('#cedula-coordinador').val().trim();
        
        if (cedula === '') {
            alert('Por favor ingrese una cédula');
            return;
        }
        
        // Mostrar loading
        $('#loading-coordinador').removeClass('d-none');
        $('#resultado-coordinador').addClass('d-none');
        $('#error-coordinador').addClass('d-none');
        
        // Realizar la búsqueda mediante AJAX
        $.ajax({
            url: 'api/consulta.php',
            type: 'GET',
            data: { cedula: cedula },
            dataType: 'json',
            success: function(data) {
                // Ocultar loading
                $('#loading-coordinador').addClass('d-none');
                
                if (data && data.nombre) {
                    // Mostrar resultados
                    $('#resultado-coordinador').removeClass('d-none');
                    
                    // Llenar datos
                    $('#nombre-coordinador').text(data.nombre);
                    $('#cedula-coordinador-resultado').text(data.cedula);
                    $('#fecha-nac-coordinador').text(data.fecha_nacimiento);
                    $('#municipio-coordinador').text(data.municipio);
                    $('#recinto-coordinador').text(data.recinto);
                    $('#colegio-coordinador').text(data.colegio);
                    
                    // Llenar campos ocultos
                    $('#cedula-coordinador-hidden').val(data.cedula);
                    $('#nombre-coordinador-hidden').val(data.nombre);
                    $('#fecha-nac-coordinador-hidden').val(data.fecha_nacimiento);
                    $('#municipio-coordinador-hidden').val(data.municipio);
                    $('#recinto-coordinador-hidden').val(data.recinto);
                    $('#colegio-coordinador-hidden').val(data.colegio);
                    $('#foto-coordinador-hidden').val(data.foto);
                    
                    // Mostrar foto
                    if (data.foto) {
                        $('#foto-coordinador').attr('src', 'data:image/jpeg;base64,' + data.foto);
                    } else {
                        // Mostrar avatar con inicial
                        const inicial = data.nombre.charAt(0).toUpperCase();
                        $('#foto-coordinador').attr('src', 'fotos/default.svg');
                    }
                } else {
                    // Mostrar error
                    $('#error-coordinador').removeClass('d-none');
                }
            },
            error: function() {
                // Ocultar loading y mostrar error
                $('#loading-coordinador').addClass('d-none');
                $('#error-coordinador').removeClass('d-none');
            }
        });
    });
    
    // Buscar miembro por cédula
    $('#buscar-miembro-btn').click(function() {
        const cedula = $('#cedula-miembro').val().trim();
        
        if (cedula === '') {
            alert('Por favor ingrese una cédula');
            return;
        }
        
        // Mostrar loading
        $('#loading-miembro').removeClass('d-none');
        $('#resultado-miembro').addClass('d-none');
        $('#error-miembro').addClass('d-none');
        
        // Realizar la búsqueda mediante AJAX
        $.ajax({
            url: 'api/consulta.php',
            type: 'GET',
            data: { cedula: cedula },
            dataType: 'json',
            success: function(data) {
                // Ocultar loading
                $('#loading-miembro').addClass('d-none');
                
                if (data && data.nombre) {
                    // Mostrar resultados
                    $('#resultado-miembro').removeClass('d-none');
                    
                    // Llenar datos
                    $('#nombre-miembro').text(data.nombre);
                    $('#cedula-miembro-resultado').text(data.cedula);
                    $('#fecha-nac-miembro').text(data.fecha_nacimiento);
                    $('#municipio-miembro').text(data.municipio);
                    $('#recinto-miembro').text(data.recinto);
                    $('#colegio-miembro').text(data.colegio);
                    
                    // Llenar campos ocultos
                    $('#cedula-miembro-hidden').val(data.cedula);
                    $('#nombre-miembro-hidden').val(data.nombre);
                    $('#fecha-nac-miembro-hidden').val(data.fecha_nacimiento);
                    $('#municipio-miembro-hidden').val(data.municipio);
                    $('#recinto-miembro-hidden').val(data.recinto);
                    $('#colegio-miembro-hidden').val(data.colegio);
                    $('#foto-miembro-hidden').val(data.foto);
                    
                    // Mostrar foto
                    if (data.foto) {
                        $('#foto-miembro').attr('src', 'data:image/jpeg;base64,' + data.foto);
                    } else {
                        // Mostrar avatar con inicial
                        const inicial = data.nombre.charAt(0).toUpperCase();
                        $('#foto-miembro').attr('src', 'fotos/default.svg');
                    }
                } else {
                    // Mostrar error
                    $('#error-miembro').removeClass('d-none');
                }
            },
            error: function() {
                // Ocultar loading y mostrar error
                $('#loading-miembro').addClass('d-none');
                $('#error-miembro').removeClass('d-none');
            }
        });
    });
});

// Función para confirmar eliminación de miembro
function confirmarEliminarMiembro(id, nombre) {
    document.getElementById('miembro-id').value = id;
    document.getElementById('miembro-nombre').textContent = nombre;
    var modal = new bootstrap.Modal(document.getElementById('eliminarMiembroModal'));
    modal.show();
}
</script>

<?php include 'includes/footer.php'; ?>