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
    $provincia = trim($_POST['provincia']);
    $municipio = trim($_POST['municipio']);
    $zona = trim($_POST['zona']);
    $circunscripcion = trim($_POST['circunscripcion'] ?? '');
    $candidato_id = !empty($_POST['candidato_id']) ? (int)$_POST['candidato_id'] : null;

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
            $stmt = $conn->prepare("UPDATE comites SET nombre = ?, provincia = ?, municipio = ?, zona = ?, circunscripcion = ?, candidato_id = ?, fecha_modificacion = NOW() WHERE id = ?");
            $stmt->bind_param("sssssii", $nombre, $provincia, $municipio, $zona, $circunscripcion, $candidato_id, $comite_id);
            $stmt->execute();

            // Actualizar datos en memoria
            $comite['nombre'] = $nombre;
            $comite['provincia'] = $provincia;
            $comite['municipio'] = $municipio;
            $comite['zona'] = $zona;
            $comite['circunscripcion'] = $circunscripcion;
            $comite['candidato_id'] = $candidato_id;

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

// Candidatos para el selector
$candidatos = $conn->query("SELECT id, nombre, cargo FROM candidatos WHERE activo = 1 ORDER BY FIELD(cargo,'presidente','senador','diputado','alcalde','regidor'), nombre")->fetch_all(MYSQLI_ASSOC);
$cargos = ['presidente'=>'Presidente','senador'=>'Senador','diputado'=>'Diputado','alcalde'=>'Alcalde','regidor'=>'Regidor'];

// Título de la página
$titulo_pagina    = 'Editar Comité';
$subtitulo_pagina = htmlspecialchars($comite['nombre']);
include 'includes/header.php';
?>

<style>
.edit-layout { display:grid; grid-template-columns:1fr 1fr; gap:22px; margin-bottom:22px; }
@media (max-width:1000px) { .edit-layout { grid-template-columns:1fr; } }
.step-card { background:#fff; border:var(--card-border); border-radius:var(--radius-card); padding:26px; box-shadow:var(--shadow-sm); }
.step-hd { display:flex; align-items:center; justify-content:space-between; margin-bottom:18px; }
.step-hd-left { display:flex; align-items:center; gap:12px; }
.step-num { width:28px; height:28px; border-radius:8px; background:var(--accent); color:#fff; font-size:13px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.step-title { font-size:15px; font-weight:700; }
.field-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }
@media (max-width:560px) { .field-row { grid-template-columns:1fr; } }
.coord-avatar-lg { width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent-light));color:#fff;display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:700;margin:0 auto 12px; }
.info-row { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #f0f0f6; font-size:13px; }
.info-row:last-child { border-bottom:none; }
.info-row span:first-child { color:var(--text-tertiary); }
.info-row span:last-child { color:var(--text-primary); font-weight:500; }
</style>

<?php if (isset($_SESSION['mensaje'])): ?>
<div class="alert alert-<?php echo $_SESSION['tipo_mensaje']; ?> alert-dismissible fade show mb-4" role="alert">
    <?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje'],$_SESSION['tipo_mensaje']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <a href="comites.php" class="page-back" style="margin-bottom:0;"><i class="fas fa-arrow-left"></i> Volver a Comités</a>
    <a href="ver_comite.php?id=<?php echo $comite_id; ?>" class="btn" style="background:#fff;color:var(--text-secondary);border:var(--card-border);box-shadow:var(--shadow-sm);">
        <i class="fas fa-eye me-1"></i> Ver Comité
    </a>
</div>

<div class="edit-layout">
    <div class="step-card">
        <div class="step-hd">
            <div class="step-hd-left">
                <div class="step-num">1</div>
                <div class="step-title">Información del Comité</div>
            </div>
        </div>
        <form method="POST" action="">
            <div style="margin-bottom:16px;">
                <label class="lbl">Nombre del Comité</label>
                <input type="text" class="fld" id="nombre" name="nombre" value="<?php echo htmlspecialchars($comite['nombre']); ?>" required>
            </div>
            <div class="field-row">
                <div>
                    <label class="lbl">Provincia</label>
                    <input type="text" class="fld" id="provincia" name="provincia" list="provinciaList" autocomplete="off"
                           value="<?php echo htmlspecialchars($comite['provincia'] ?? ''); ?>">
                    <datalist id="provinciaList"></datalist>
                </div>
                <div>
                    <label class="lbl">Municipio</label>
                    <input type="text" class="fld" id="municipio" name="municipio" list="municipioList" autocomplete="off"
                           value="<?php echo htmlspecialchars($comite['municipio']); ?>" required>
                    <datalist id="municipioList"></datalist>
                </div>
            </div>
            <div class="field-row">
                <div>
                    <label class="lbl">Zona</label>
                    <input type="text" class="fld" id="zona" name="zona"
                           value="<?php echo htmlspecialchars($comite['zona'] ?? ''); ?>">
                </div>
                <div>
                    <label class="lbl">Circunscripción</label>
                    <input type="text" class="fld" id="circunscripcion" name="circunscripcion" list="circunscripcionList" autocomplete="off"
                           value="<?php echo htmlspecialchars($comite['circunscripcion'] ?? ''); ?>">
                    <datalist id="circunscripcionList"></datalist>
                </div>
            </div>
            <div style="margin-bottom:16px;">
                <label class="lbl">Candidato</label>
                <select class="fld" id="candidato_id" name="candidato_id">
                    <option value="">Sin candidato asignado</option>
                    <?php foreach ($candidatos as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo (int)($comite['candidato_id'] ?? 0) === (int)$c['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['nombre']); ?> — <?php echo $cargos[$c['cargo']] ?? ucfirst($c['cargo']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" name="actualizar_comite" class="btn btn-primary w-100">
                <i class="fas fa-save me-1"></i> Guardar Cambios
            </button>
        </form>
    </div>

    <div class="step-card">
        <div class="step-hd">
            <div class="step-hd-left">
                <div class="step-num">2</div>
                <div class="step-title">Coordinador</div>
            </div>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#buscarCoordinadorModal">
                <i class="fas fa-search me-1"></i> Buscar
            </button>
        </div>
        <div id="coordinador-info">
            <?php if ($coordinador): ?>
            <div class="text-center mb-3">
                <?php if (!empty($coordinador['foto'])): ?>
                <img src="data:image/jpeg;base64,<?php echo $coordinador['foto']; ?>" alt="Foto"
                     style="width:80px;height:80px;border-radius:50%;object-fit:cover;margin-bottom:8px;">
                <?php else: ?>
                <div class="coord-avatar-lg"><?php echo strtoupper(substr($coordinador['nombre_completo'] ?? $coordinador['nombre'] ?? '?', 0, 1)); ?></div>
                <?php endif; ?>
                <div style="font-weight:700;font-size:15px;"><?php echo htmlspecialchars($coordinador['nombre_completo'] ?? $coordinador['nombre'] ?? '—'); ?></div>
            </div>
            <div class="mb-3">
                <div class="info-row"><span>Cédula</span><span><?php echo htmlspecialchars($coordinador['cedula'] ?? '—'); ?></span></div>
                <div class="info-row"><span>Teléfono</span><span><?php echo htmlspecialchars($coordinador['telefono'] ?? '—'); ?></span></div>
                <div class="info-row"><span>Municipio</span><span><?php echo htmlspecialchars($coordinador['municipio'] ?? '—'); ?></span></div>
                <div class="info-row"><span>Recinto</span><span><?php echo htmlspecialchars($coordinador['recinto'] ?? '—'); ?></span></div>
                <div class="info-row"><span>Colegio</span><span><?php echo htmlspecialchars($coordinador['colegio'] ?? '—'); ?></span></div>
            </div>
            <button class="btn btn-outline-danger w-100 btn-sm" data-bs-toggle="modal" data-bs-target="#eliminarCoordinadorModal">
                <i class="fas fa-user-minus me-1"></i> Quitar Coordinador
            </button>
            <?php else: ?>
            <div style="text-align:center;padding:30px 0;color:var(--text-tertiary);">
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

<!-- Members -->
<div class="step-card" id="miembros">
    <div class="step-hd">
        <div class="step-hd-left">
            <div class="step-num">3</div>
            <div class="step-title">Miembros del Comité <span style="font-size:12px;color:var(--text-tertiary);font-weight:400;">— <?php echo count($miembros); ?></span></div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#buscarMiembroModal">
                <i class="fas fa-id-card me-1"></i> Por Cédula
            </button>
            <button class="btn btn-sm" style="background:var(--bg);color:var(--text-secondary);" data-bs-toggle="modal" data-bs-target="#agregarMiembroModal">
                <i class="fas fa-user-plus me-1"></i> Manual
            </button>
        </div>
    </div>
    <?php if (count($miembros) > 0): ?>
    <div class="table-responsive">
        <table class="table mb-0" id="miembrosTable" style="width:100%;">
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
    <div style="text-align:center;padding:40px 20px;color:var(--text-tertiary);">
        <i class="fas fa-users-slash" style="font-size:36px;opacity:.2;display:block;margin-bottom:12px;"></i>
        <p style="font-size:13px;margin:0 0 12px;">No hay miembros registrados.</p>
        <div class="d-flex gap-2 justify-content-center">
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#buscarMiembroModal">Por Cédula</button>
            <button class="btn btn-sm" style="background:var(--bg);color:var(--text-secondary);" data-bs-toggle="modal" data-bs-target="#agregarMiembroModal">Manual</button>
        </div>
    </div>
    <?php endif; ?>
</div>

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

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
// Inicializar DataTable
$(document).ready(function() {
    if ($('#miembrosTable tbody tr').length > 1) {
        $('#miembrosTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json'
            }
        });
    }

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
            success: function(resp) {
                // Ocultar loading
                $('#loading-coordinador').addClass('d-none');

                if (resp && resp.success && resp.data) {
                    const d = resp.data;
                    const nombreCompleto = `${d.nombres} ${d.apellido1} ${d.apellido2}`;

                    // Mostrar resultados
                    $('#resultado-coordinador').removeClass('d-none');

                    // Llenar datos
                    $('#nombre-coordinador').text(nombreCompleto);
                    $('#cedula-coordinador-resultado').text(d.Cedula);
                    $('#fecha-nac-coordinador').text(d.FechaNacimiento);
                    $('#municipio-coordinador').text(d.Municipio);
                    $('#recinto-coordinador').text(d.Recinto);
                    $('#colegio-coordinador').text(d.CodigoColegio);

                    // Llenar campos ocultos
                    $('#cedula-coordinador-hidden').val(d.Cedula);
                    $('#nombre-coordinador-hidden').val(nombreCompleto);
                    $('#fecha-nac-coordinador-hidden').val(d.FechaNacimiento);
                    $('#municipio-coordinador-hidden').val(d.Municipio);
                    $('#recinto-coordinador-hidden').val(d.Recinto);
                    $('#colegio-coordinador-hidden').val(d.CodigoColegio);
                    $('#foto-coordinador-hidden').val(d.Imagen);

                    // Mostrar foto
                    if (d.Imagen) {
                        $('#foto-coordinador').attr('src', 'data:image/jpeg;base64,' + d.Imagen);
                    } else {
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
            success: function(resp) {
                // Ocultar loading
                $('#loading-miembro').addClass('d-none');

                if (resp && resp.success && resp.data) {
                    const d = resp.data;
                    const nombreCompleto = `${d.nombres} ${d.apellido1} ${d.apellido2}`;

                    // Mostrar resultados
                    $('#resultado-miembro').removeClass('d-none');

                    // Llenar datos
                    $('#nombre-miembro').text(nombreCompleto);
                    $('#cedula-miembro-resultado').text(d.Cedula);
                    $('#fecha-nac-miembro').text(d.FechaNacimiento);
                    $('#municipio-miembro').text(d.Municipio);
                    $('#recinto-miembro').text(d.Recinto);
                    $('#colegio-miembro').text(d.CodigoColegio);

                    // Llenar campos ocultos
                    $('#cedula-miembro-hidden').val(d.Cedula);
                    $('#nombre-miembro-hidden').val(nombreCompleto);
                    $('#fecha-nac-miembro-hidden').val(d.FechaNacimiento);
                    $('#municipio-miembro-hidden').val(d.Municipio);
                    $('#recinto-miembro-hidden').val(d.Recinto);
                    $('#colegio-miembro-hidden').val(d.CodigoColegio);
                    $('#foto-miembro-hidden').val(d.Imagen);

                    // Mostrar foto
                    if (d.Imagen) {
                        $('#foto-miembro').attr('src', 'data:image/jpeg;base64,' + d.Imagen);
                    } else {
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

<script>
(function() {
    const provinciaInput = document.getElementById('provincia');
    const provinciaList  = document.getElementById('provinciaList');
    const municipioInput = document.getElementById('municipio');
    const municipioList  = document.getElementById('municipioList');
    const circunscripcionList = document.getElementById('circunscripcionList');
    let provincias = [];

    function escapeAttr(str) {
        return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function cargarMunicipios(provinciaId, valorPrevio) {
        municipioList.innerHTML = '';
        fetch(`api/municipios.php?provincia_id=${provinciaId}`)
            .then(r => r.json())
            .then(json => {
                if (!json.success) return;
                municipioList.innerHTML = json.data.map(m => `<option value="${escapeAttr(m.Descripcion)}"></option>`).join('');
                if (valorPrevio !== undefined) municipioInput.value = valorPrevio;
            })
            .catch(err => console.error('Error cargando municipios:', err));
    }

    provinciaInput.addEventListener('input', function() {
        const match = provincias.find(p => p.Descripcion === provinciaInput.value);
        if (match) cargarMunicipios(match.ID);
    });

    fetch('api/provincias.php')
        .then(r => r.json())
        .then(json => {
            if (!json.success) return;
            provincias = json.data;
            provinciaList.innerHTML = provincias.map(p => `<option value="${escapeAttr(p.Descripcion)}"></option>`).join('');

            const match = provincias.find(p => p.Descripcion === provinciaInput.value);
            if (match) cargarMunicipios(match.ID, municipioInput.value);
        })
        .catch(err => console.error('Error cargando provincias:', err));

    fetch('api/circunscripciones.php')
        .then(r => r.json())
        .then(json => {
            if (!json.success) return;
            circunscripcionList.innerHTML = json.data.map(c => `<option value="${escapeAttr(c.Descripcion)}"></option>`).join('');
        })
        .catch(err => console.error('Error cargando circunscripciones:', err));
})();
</script>

<?php include 'includes/footer.php'; ?>
