<?php
require_once 'includes/auth.php';
require_once 'includes/funciones.php';
require_once 'db/config.php';

requiereAutenticacion();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) { header('Location: comites.php'); exit; }

$comite_id = (int)$_GET['id'];
$conn = conectarDB();

$stmt = $conn->prepare("SELECT c.*, u.nombre as creador, cand.nombre as candidato_nombre, cand.cargo as candidato_cargo, cand.foto as candidato_foto FROM comites c LEFT JOIN usuarios u ON c.creado_por = u.id LEFT JOIN candidatos cand ON c.candidato_id = cand.id WHERE c.id = ?");
$stmt->bind_param("i", $comite_id); $stmt->execute();
$comite = $stmt->get_result()->fetch_assoc();
if (!$comite) { header('Location: comites.php'); exit; }

$stmt = $conn->prepare("SELECT c.* FROM coordinadores c JOIN comite_coordinador cc ON c.id = cc.coordinador_id WHERE cc.comite_id = ?");
$stmt->bind_param("i", $comite_id); $stmt->execute();
$coordinador = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare("SELECT m.* FROM miembros m JOIN comite_miembro cm ON m.id = cm.miembro_id WHERE cm.comite_id = ? ORDER BY m.nombre_completo");
$stmt->bind_param("i", $comite_id); $stmt->execute();
$miembros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$titulo_pagina = 'Detalles del Comité';
include 'includes/header.php';
?>

<style>
.page-back { display:inline-flex;align-items:center;gap:8px;color:var(--text-secondary);font-size:13px;text-decoration:none;margin-bottom:20px;transition:color .15s; }
.page-back:hover { color:var(--accent); }
.info-row { display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid #f1f5f9;font-size:13px; }
.info-row:last-child { border-bottom:none; }
.info-label { color:var(--text-secondary);width:130px;flex-shrink:0; }
.info-value { color:var(--text-primary);font-weight:500; }
.coord-avatar { width:72px;height:72px;border-radius:50%;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:700;flex-shrink:0; }
.action-link { width:28px;height:28px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;border:1px solid var(--border);color:var(--text-secondary);text-decoration:none;background:none;cursor:pointer;transition:all .15s; }
.action-link:hover { border-color:var(--accent);color:var(--accent);background:#eff6ff; }
</style>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <a href="comites.php" class="page-back"><i class="fas fa-arrow-left"></i> Volver a Comités</a>
    <div class="d-flex gap-2">
        <a href="editar_comite.php?id=<?php echo $comite_id; ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-pen me-1"></i> Editar
        </a>
        <button class="btn btn-outline-secondary btn-sm" onclick="imprimirComite()">
            <i class="fas fa-print me-1"></i> Imprimir
        </button>
    </div>
</div>

<div class="row g-4">
    <!-- Comité Info -->
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-layer-group me-2 text-primary"></i>Información del Comité</div>
            <div class="p-4">
                <h5 class="fw-bold mb-3" style="color:var(--accent);"><?php echo htmlspecialchars($comite['nombre']); ?></h5>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-map-pin me-1"></i> Municipio</span>
                    <span class="info-value"><?php echo htmlspecialchars($comite['municipio']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-calendar me-1"></i> Creado</span>
                    <span class="info-value"><?php echo date('d/m/Y', strtotime($comite['fecha_creacion'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-user me-1"></i> Creado por</span>
                    <span class="info-value"><?php echo htmlspecialchars($comite['creador'] ?? '—'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-users me-1"></i> Miembros</span>
                    <span class="info-value">
                        <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold"><?php echo count($miembros); ?></span>
                    </span>
                </div>
                <?php if ($comite['fecha_modificacion']): ?>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-edit me-1"></i> Modificado</span>
                    <span class="info-value" style="font-size:12px;"><?php echo date('d/m/Y H:i', strtotime($comite['fecha_modificacion'])); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Coordinator -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-user-tie me-2 text-primary"></i>Coordinador del Comité</div>
            <div class="p-4">
                <?php if ($coordinador): ?>
                <div class="d-flex gap-4 align-items-start">
                    <?php if (!empty($coordinador['foto'])): ?>
                    <img src="data:image/jpeg;base64,<?php echo $coordinador['foto']; ?>" alt="Foto"
                         style="width:72px;height:72px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                    <?php else: ?>
                    <div class="coord-avatar"><?php echo strtoupper(substr($coordinador['nombre_completo'] ?? $coordinador['nombre'] ?? '?', 0, 1)); ?></div>
                    <?php endif; ?>
                    <div class="flex-fill">
                        <h6 class="fw-bold mb-3"><?php echo htmlspecialchars($coordinador['nombre_completo'] ?? $coordinador['nombre'] ?? '—'); ?></h6>
                        <div class="row g-2" style="font-size:13px;">
                            <div class="col-sm-6">
                                <div class="info-row"><span class="info-label">Cédula</span><span class="info-value"><?php echo htmlspecialchars($coordinador['cedula'] ?? '—'); ?></span></div>
                                <div class="info-row"><span class="info-label">Teléfono</span><span class="info-value"><?php echo htmlspecialchars($coordinador['telefono'] ?? '—'); ?></span></div>
                                <div class="info-row"><span class="info-label">Email</span><span class="info-value"><?php echo htmlspecialchars($coordinador['email'] ?? '—'); ?></span></div>
                            </div>
                            <div class="col-sm-6">
                                <div class="info-row"><span class="info-label">Municipio</span><span class="info-value"><?php echo htmlspecialchars($coordinador['municipio'] ?? '—'); ?></span></div>
                                <div class="info-row"><span class="info-label">Recinto</span><span class="info-value"><?php echo htmlspecialchars($coordinador['recinto'] ?? '—'); ?></span></div>
                                <div class="info-row"><span class="info-label">Colegio</span><span class="info-value"><?php echo htmlspecialchars($coordinador['colegio'] ?? '—'); ?></span></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div style="text-align:center;padding:30px 20px;color:var(--text-secondary);">
                    <i class="fas fa-user-slash" style="font-size:36px;opacity:.25;margin-bottom:12px;display:block;"></i>
                    <p style="font-size:13px;margin:0 0 12px;">No hay coordinador asignado.</p>
                    <a href="editar_comite.php?id=<?php echo $comite_id; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-user-plus me-1"></i> Asignar Coordinador
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Members -->
<div class="card">
    <div class="card-header">
        <span><i class="fas fa-users me-2 text-primary"></i>Miembros del Comité
            <span style="font-size:12px;color:var(--text-secondary);font-weight:400;"> — <?php echo count($miembros); ?></span>
        </span>
        <a href="editar_comite.php?id=<?php echo $comite_id; ?>#miembros" class="btn btn-primary btn-sm">
            <i class="fas fa-user-plus me-1"></i> Agregar
        </a>
    </div>
    <?php if (count($miembros) > 0): ?>
    <div class="table-responsive">
        <table class="table mb-0" id="miembrosTable">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Cédula</th>
                    <th>Teléfono</th>
                    <th>Municipio</th>
                    <th>Recinto</th>
                    <th>Colegio</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($miembros as $m): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div style="width:30px;height:30px;border-radius:50%;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;">
                                <?php echo strtoupper(substr($m['nombre_completo'] ?? $m['nombre'] ?? '?', 0, 1)); ?>
                            </div>
                            <span style="font-weight:500;"><?php echo htmlspecialchars($m['nombre_completo'] ?? $m['nombre'] ?? '—'); ?></span>
                        </div>
                    </td>
                    <td style="font-size:13px;"><?php echo htmlspecialchars($m['cedula'] ?? '—'); ?></td>
                    <td style="font-size:13px;"><?php echo htmlspecialchars($m['telefono'] ?? '—'); ?></td>
                    <td style="font-size:13px;"><?php echo htmlspecialchars($m['municipio'] ?? '—'); ?></td>
                    <td style="font-size:13px;"><?php echo htmlspecialchars($m['recinto'] ?? '—'); ?></td>
                    <td style="font-size:13px;"><?php echo htmlspecialchars($m['colegio'] ?? '—'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:40px 20px;color:var(--text-secondary);">
        <i class="fas fa-users-slash" style="font-size:36px;opacity:.2;margin-bottom:12px;display:block;"></i>
        <p style="font-size:13px;margin:0 0 12px;">No hay miembros registrados.</p>
        <a href="editar_comite.php?id=<?php echo $comite_id; ?>#miembros" class="btn btn-primary btn-sm">
            <i class="fas fa-user-plus me-1"></i> Agregar Miembros
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Print section -->
<div class="d-none" id="seccion-imprimir">
    <div style="text-align:center;margin-bottom:24px;">
        <?php
        $tema = cargarTemaPartido();
        $logo_print = !empty($tema['logo']) ? 'data:image/png;base64,'.base64_encode($tema['logo']) : 'logo1.png';
        ?>
        <img src="<?php echo $logo_print; ?>" alt="Logo" style="max-width:100px;max-height:80px;object-fit:contain;">
        <h2 style="margin-top:8px;font-size:16px;"><?php echo htmlspecialchars($tema['nombre']); ?></h2>
        <h3 style="font-size:13px;">Comité Afectivo</h3>
        <?php if (!empty($comite['candidato_nombre'])): ?>
        <div style="margin-top:16px;display:flex;align-items:center;justify-content:center;gap:16px;border:1px solid #ccc;border-radius:8px;padding:12px;display:inline-flex;">
            <?php if (!empty($comite['candidato_foto'])): ?>
            <img src="data:image/jpeg;base64,<?php echo base64_encode($comite['candidato_foto']); ?>" style="width:60px;height:60px;border-radius:50%;object-fit:cover;">
            <?php endif; ?>
            <div style="text-align:left;">
                <div style="font-weight:700;font-size:14px;"><?php echo htmlspecialchars($comite['candidato_nombre']); ?></div>
                <div style="font-size:12px;color:#666;"><?php echo ucfirst($comite['candidato_cargo'] ?? ''); ?></div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <p><strong>Nombre:</strong> <?php echo htmlspecialchars($comite['nombre']); ?></p>
    <p><strong>Municipio:</strong> <?php echo htmlspecialchars($comite['municipio']); ?></p>
    <p><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($comite['fecha_creacion'])); ?></p>
    <?php if ($coordinador): ?>
    <p><strong>Coordinador:</strong> <?php echo htmlspecialchars($coordinador['nombre_completo'] ?? $coordinador['nombre'] ?? '—'); ?></p>
    <p><strong>Cédula:</strong> <?php echo htmlspecialchars($coordinador['cedula'] ?? '—'); ?></p>
    <?php endif; ?>
    <?php if (count($miembros)): ?>
    <table border="1" width="100%" style="border-collapse:collapse;margin-top:16px;">
        <thead><tr><th>Nombre</th><th>Cédula</th><th>Teléfono</th><th>Municipio</th></tr></thead>
        <tbody>
        <?php foreach ($miembros as $m): ?>
        <tr>
            <td><?php echo htmlspecialchars($m['nombre_completo'] ?? $m['nombre'] ?? '—'); ?></td>
            <td><?php echo htmlspecialchars($m['cedula'] ?? '—'); ?></td>
            <td><?php echo htmlspecialchars($m['telefono'] ?? '—'); ?></td>
            <td><?php echo htmlspecialchars($m['municipio'] ?? '—'); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    if ($('#miembrosTable tbody tr').length > 4) {
        $('#miembrosTable').DataTable({ language: { url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json' }, pageLength: 10 });
    }
});
function imprimirComite() {
    const orig = document.body.innerHTML;
    document.body.innerHTML = '<div style="padding:20px;">' + document.getElementById('seccion-imprimir').innerHTML + '</div>';
    window.print();
    document.body.innerHTML = orig;
    location.reload();
}
</script>

<?php include 'includes/footer.php'; ?>
