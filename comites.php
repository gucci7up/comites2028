<?php
require_once 'includes/auth.php';
require_once 'includes/funciones.php';
require_once 'db/config.php';

// Verificar que el usuario esté autenticado
requiereAutenticacion();

// Obtener todos los comités
$conn = conectarDB();

// Filtros
$filtro_nombre = isset($_GET['nombre']) ? $_GET['nombre'] : '';
$filtro_municipio = isset($_GET['municipio']) ? $_GET['municipio'] : '';
$filtro_usuario = isset($_GET['usuario']) ? $_GET['usuario'] : '';

// Construir la consulta SQL con filtros
$sql = "SELECT c.*, u.nombre as creador 
        FROM comites c 
        LEFT JOIN usuarios u ON c.creado_por = u.id 
        WHERE 1=1";
$params = [];

if (!empty($filtro_nombre)) {
    $sql .= " AND c.nombre LIKE ?"; 
    $params[] = "%$filtro_nombre%";
}

if (!empty($filtro_municipio)) {
    $sql .= " AND c.municipio LIKE ?"; 
    $params[] = "%$filtro_municipio%";
}

if (!empty($filtro_usuario)) {
    $sql .= " AND u.nombre LIKE ?"; 
    $params[] = "%$filtro_usuario%";
}

// Ordenar por fecha de creación descendente
$sql .= " ORDER BY c.fecha_creacion DESC";

$stmt = $conn->prepare($sql);

// Vincular parámetros si existen
if (!empty($params)) {
    $types = str_repeat('s', count($params)); // Asumimos que todos son strings
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$resultado = $stmt->get_result();
$comites = [];
while ($fila = $resultado->fetch_assoc()) {
    $comites[] = $fila;
}

// Obtener lista de municipios para el filtro
$stmt = $conn->prepare("SELECT DISTINCT municipio FROM comites ORDER BY municipio");
$stmt->execute();
$resultado = $stmt->get_result();
$municipios = [];
while ($fila = $resultado->fetch_assoc()) {
    $municipios[] = $fila['municipio'];
}

// Obtener lista de usuarios para el filtro
$stmt = $conn->prepare("SELECT DISTINCT u.nombre FROM usuarios u JOIN comites c ON u.id = c.creado_por ORDER BY u.nombre");
$stmt->execute();
$resultado = $stmt->get_result();
$usuarios = [];
while ($fila = $resultado->fetch_assoc()) {
    $usuarios[] = $fila['nombre'];
}

// Título de la página
$titulo_pagina = 'Gestión de Comités';

// Incluir el header
include 'includes/header.php';
?>

<!-- Filtros -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Filtros</h6>
    </div>
    <div class="card-body">
        <form method="GET" action="comites.php" class="row g-3">
            <div class="col-md-3">
                <label for="nombre" class="form-label">Nombre del Comité</label>
                <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($filtro_nombre); ?>">
            </div>
            <div class="col-md-3">
                <label for="municipio" class="form-label">Municipio</label>
                <select class="form-select" id="municipio" name="municipio">
                    <option value="">Todos</option>
                    <?php foreach ($municipios as $municipio): ?>
                    <option value="<?php echo htmlspecialchars($municipio); ?>" <?php echo $filtro_municipio === $municipio ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($municipio); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="usuario" class="form-label">Creado por</label>
                <select class="form-select" id="usuario" name="usuario">
                    <option value="">Todos</option>
                    <?php foreach ($usuarios as $usuario): ?>
                    <option value="<?php echo htmlspecialchars($usuario); ?>" <?php echo $filtro_usuario === $usuario ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($usuario); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="d-grid gap-2 d-md-flex">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="comites.php" class="btn btn-secondary">Limpiar</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Lista de Comités -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Comités Registrados</h6>
        <a href="crear_comite.php" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> Crear Comité
        </a>
    </div>
    <div class="card-body">
        <?php if (count($comites) > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Municipio</th>
                        <th>Coordinador</th>
                        <th>Miembros</th>
                        <th>Creado por</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comites as $comite): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($comite['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($comite['municipio']); ?></td>
                        <td>
                            <?php 
                            // Buscar coordinador en la tabla comite_coordinador
                            $stmt = $conn->prepare("SELECT c.nombre_completo FROM coordinadores c 
                                                    JOIN comite_coordinador cc ON c.id = cc.coordinador_id 
                                                    WHERE cc.comite_id = ?");
                            $stmt->bind_param("i", $comite['id']);
                            $stmt->execute();
                            $resultado = $stmt->get_result();
                            $coordinador = $resultado->fetch_assoc();
                            echo $coordinador ? htmlspecialchars($coordinador['nombre_completo']) : 'No asignado';
                            ?>
                        </td>
                        <td>
                            <?php 
                            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM comite_miembro WHERE comite_id = ?");
                            $stmt->bind_param("i", $comite['id']);
                            $stmt->execute();
                            $resultado = $stmt->get_result();
                            $fila = $resultado->fetch_assoc();
                            echo $fila['total']; 
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($comite['creador']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($comite['fecha_creacion'])); ?></td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="ver_comite.php?id=<?php echo $comite['id']; ?>" class="btn btn-sm btn-info" title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="editar_comite.php?id=<?php echo $comite['id']; ?>" class="btn btn-sm btn-primary" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-danger" title="Eliminar" 
                                        onclick="confirmarEliminar(<?php echo $comite['id']; ?>, '<?php echo htmlspecialchars(addslashes($comite['nombre'])); ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-4">
            <i class="fas fa-folder-open fa-3x text-gray-300 mb-3"></i>
            <p>No se encontraron comités<?php echo !empty($filtro_nombre) || !empty($filtro_municipio) || !empty($filtro_usuario) ? ' con los filtros aplicados' : ''; ?>.</p>
            <?php if (!empty($filtro_nombre) || !empty($filtro_municipio) || !empty($filtro_usuario)): ?>
            <a href="comites.php" class="btn btn-secondary">Limpiar filtros</a>
            <?php else: ?>
            <a href="crear_comite.php" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Crear Comité
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de Confirmación para Eliminar -->
<div class="modal fade" id="eliminarModal" tabindex="-1" aria-labelledby="eliminarModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eliminarModalLabel">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ¿Está seguro que desea eliminar el comité <span id="comite-nombre"></span>? Esta acción no se puede deshacer.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="form-eliminar" method="POST" action="eliminar_comite.php">
                    <input type="hidden" id="comite-id" name="id">
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
        },
        order: [[5, 'desc']] // Ordenar por fecha de creación (columna 5) descendente
    });
});

// Función para confirmar eliminación
function confirmarEliminar(id, nombre) {
    document.getElementById('comite-id').value = id;
    document.getElementById('comite-nombre').textContent = nombre;
    var modal = new bootstrap.Modal(document.getElementById('eliminarModal'));
    modal.show();
}
</script>

<?php include 'includes/footer.php'; ?>