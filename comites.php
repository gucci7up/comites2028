<?php
require_once 'includes/auth.php';
require_once 'includes/funciones.php';
require_once 'db/config.php';

requiereAutenticacion();

$conn = conectarDB();

$filtro_nombre    = $_GET['nombre']   ?? '';
$filtro_municipio = $_GET['municipio'] ?? '';
$filtro_usuario   = $_GET['usuario']  ?? '';

$sql    = "SELECT c.*, u.nombre as creador FROM comites c LEFT JOIN usuarios u ON c.creado_por = u.id WHERE 1=1";
$params = [];

if (!empty($filtro_nombre))    { $sql .= " AND c.nombre LIKE ?";   $params[] = "%$filtro_nombre%"; }
if (!empty($filtro_municipio)) { $sql .= " AND c.municipio LIKE ?"; $params[] = "%$filtro_municipio%"; }
if (!empty($filtro_usuario))   { $sql .= " AND u.nombre LIKE ?";   $params[] = "%$filtro_usuario%"; }

$sql .= " ORDER BY c.fecha_creacion DESC";
$stmt  = $conn->prepare($sql);
if (!empty($params)) { $stmt->bind_param(str_repeat('s', count($params)), ...$params); }
$stmt->execute();
$comites = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$municipios = $conn->query("SELECT DISTINCT municipio FROM comites ORDER BY municipio")->fetch_all(MYSQLI_ASSOC);
$usuarios   = $conn->query("SELECT DISTINCT u.nombre FROM usuarios u JOIN comites c ON u.id = c.creado_por ORDER BY u.nombre")->fetch_all(MYSQLI_ASSOC);

$titulo_pagina = 'Comités';
include 'includes/header.php';
?>

<style>
.filter-card { background:#fff; border:1px solid var(--border); border-radius:var(--radius); padding:20px; margin-bottom:20px; box-shadow:var(--shadow-sm); }
.filter-label { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:var(--text-secondary); margin-bottom:6px; display:block; }
.comite-badge { width:38px; height:38px; border-radius:10px; background:#eff6ff; color:var(--accent); display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; }
</style>

<?php if (isset($_SESSION['mensaje'])): ?>
<div class="alert alert-<?php echo $_SESSION['tipo_mensaje']; ?> alert-dismissible fade show mb-4" role="alert">
    <?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="filter-card">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="filter-label">Nombre</label>
            <input type="text" name="nombre" class="form-control form-control-sm" placeholder="Buscar comité..." value="<?php echo htmlspecialchars($filtro_nombre); ?>">
        </div>
        <div class="col-md-3">
            <label class="filter-label">Municipio</label>
            <select name="municipio" class="form-select form-select-sm">
                <option value="">Todos los municipios</option>
                <?php foreach ($municipios as $m): ?>
                <option value="<?php echo htmlspecialchars($m['municipio']); ?>" <?php echo $filtro_municipio === $m['municipio'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($m['municipio']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="filter-label">Creado por</label>
            <select name="usuario" class="form-select form-select-sm">
                <option value="">Todos los usuarios</option>
                <?php foreach ($usuarios as $u): ?>
                <option value="<?php echo htmlspecialchars($u['nombre']); ?>" <?php echo $filtro_usuario === $u['nombre'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($u['nombre']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm flex-fill">
                <i class="fas fa-search me-1"></i> Filtrar
            </button>
            <a href="comites.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-times"></i>
            </a>
        </div>
    </form>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header">
        <span><i class="fas fa-layer-group me-2 text-primary"></i>Comités Registrados
            <span style="font-size:12px;color:var(--text-secondary);font-weight:400;"> — <?php echo count($comites); ?> resultado<?php echo count($comites) !== 1 ? 's' : ''; ?></span>
        </span>
        <a href="crear_comite.php" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i> Crear Comité
        </a>
    </div>
    <?php if (count($comites) > 0): ?>
    <div class="table-responsive">
        <table class="table mb-0" id="dataTable">
            <thead>
                <tr>
                    <th>Comité</th>
                    <th>Coordinador</th>
                    <th>Miembros</th>
                    <th>Creado por</th>
                    <th>Fecha</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($comites as $c):
                $s2 = $conn->prepare("SELECT c.nombre_completo FROM coordinadores c JOIN comite_coordinador cc ON c.id=cc.coordinador_id WHERE cc.comite_id=? LIMIT 1");
                $s2->bind_param("i", $c['id']); $s2->execute();
                $coord = $s2->get_result()->fetch_assoc();

                $s3 = $conn->prepare("SELECT COUNT(*) as t FROM comite_miembro WHERE comite_id=?");
                $s3->bind_param("i", $c['id']); $s3->execute();
                $cnt = $s3->get_result()->fetch_assoc()['t'];
            ?>
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-3">
                        <div class="comite-badge"><i class="fas fa-layer-group"></i></div>
                        <div>
                            <div style="font-weight:500;color:var(--text-primary);"><?php echo htmlspecialchars($c['nombre']); ?></div>
                            <div style="font-size:12px;color:var(--text-secondary);"><i class="fas fa-map-pin me-1"></i><?php echo htmlspecialchars($c['municipio']); ?></div>
                        </div>
                    </div>
                </td>
                <td style="font-size:13px;">
                    <?php echo $coord ? htmlspecialchars($coord['nombre_completo']) : '<span class="text-muted">—</span>'; ?>
                </td>
                <td><span class="badge bg-primary bg-opacity-10 text-primary fw-semibold"><?php echo $cnt; ?></span></td>
                <td style="font-size:13px;color:var(--text-secondary);"><?php echo htmlspecialchars($c['creador'] ?? '—'); ?></td>
                <td style="font-size:12px;color:var(--text-secondary);"><?php echo date('d/m/Y', strtotime($c['fecha_creacion'])); ?></td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="ver_comite.php?id=<?php echo $c['id']; ?>" class="action-link" title="Ver"><i class="fas fa-eye"></i></a>
                        <a href="editar_comite.php?id=<?php echo $c['id']; ?>" class="action-link" title="Editar"><i class="fas fa-pen"></i></a>
                        <button class="action-link danger" title="Eliminar" onclick="confirmarEliminar(<?php echo $c['id']; ?>,'<?php echo htmlspecialchars(addslashes($c['nombre'])); ?>')">
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
    <div class="empty-state" style="padding:60px 20px;">
        <i class="fas fa-folder-open d-block" style="font-size:48px;opacity:.2;margin-bottom:16px;"></i>
        <p style="font-size:14px;color:var(--text-secondary);">
            <?php echo (!empty($filtro_nombre)||!empty($filtro_municipio)||!empty($filtro_usuario)) ? 'No se encontraron comités con esos filtros.' : 'No hay comités creados aún.'; ?>
        </p>
        <a href="crear_comite.php" class="btn btn-primary btn-sm mt-2"><i class="fas fa-plus me-1"></i>Crear primer comité</a>
    </div>
    <?php endif; ?>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="eliminarModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content" style="border-radius:var(--radius);border:1px solid var(--border);">
            <div class="modal-header" style="border-bottom:1px solid var(--border);">
                <h6 class="modal-title fw-semibold">Eliminar comité</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="font-size:13px;">
                ¿Eliminar <strong id="comite-nombre"></strong>? Esta acción no se puede deshacer.
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--border);gap:8px;">
                <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" action="eliminar_comite.php" class="d-inline">
                    <input type="hidden" id="comite-id" name="id">
                    <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.action-link { width:28px;height:28px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;border:1px solid var(--border);color:var(--text-secondary);text-decoration:none;background:none;cursor:pointer;transition:all .15s; }
.action-link:hover { border-color:var(--accent);color:var(--accent);background:#eff6ff; }
.action-link.danger:hover { border-color:var(--danger);color:var(--danger);background:#fef2f2; }
.empty-state { text-align:center; }
</style>

<script>
function confirmarEliminar(id, nombre) {
    document.getElementById('comite-id').value = id;
    document.getElementById('comite-nombre').textContent = nombre;
    new bootstrap.Modal(document.getElementById('eliminarModal')).show();
}
</script>

<?php include 'includes/footer.php'; ?>
