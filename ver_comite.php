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
.action-link:hover { border-color:var(--accent);color:var(--accent);background:color-mix(in srgb,var(--accent) 8%,white); }
@media print {
    body * { visibility: hidden; }
    #seccion-imprimir, #seccion-imprimir * { visibility: visible; }
    #seccion-imprimir {
        display: block !important;
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
}
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
                        <span style="background:color-mix(in srgb,var(--accent) 10%,white);color:var(--accent);font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;"><?php echo count($miembros); ?></span>
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
                            <?php if (!empty($m['foto'])): ?>
                            <img src="data:image/jpeg;base64,<?php echo $m['foto']; ?>" alt="Foto"
                                 style="width:30px;height:30px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                            <?php else: ?>
                            <div style="width:30px;height:30px;border-radius:50%;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;">
                                <?php echo strtoupper(substr($m['nombre_completo'] ?? $m['nombre'] ?? '?', 0, 1)); ?>
                            </div>
                            <?php endif; ?>
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
<?php
$tema = cargarConfiguracion();
$logo_print = !empty($tema['logo']) ? 'data:image/png;base64,'.base64_encode($tema['logo']) : 'logo1.png';
$colorPrincipal = $tema['color_primario'] ?? '#2563eb';
?>
<div class="d-none" id="seccion-imprimir" style="font-family:Arial,Helvetica,sans-serif;color:#111;">
    <table width="100%" style="border-collapse:collapse;border-bottom:3px solid <?php echo htmlspecialchars($colorPrincipal); ?>;padding-bottom:10px;margin-bottom:14px;">
        <tr>
            <td style="width:64px;vertical-align:top;">
                <img src="<?php echo $logo_print; ?>" alt="Logo" style="width:56px;height:56px;object-fit:contain;">
            </td>
            <td style="vertical-align:top;">
                <div style="font-size:16px;font-weight:800;letter-spacing:.3px;"><?php echo htmlspecialchars(strtoupper($tema['nombre_partido'])); ?></div>
                <div style="font-size:10px;color:#555;margin-top:2px;">COMITÉ AFECTIVO</div>
                <div style="font-size:11px;font-weight:600;margin-top:4px;"><?php echo htmlspecialchars($comite['nombre']); ?></div>
            </td>
            <td style="width:230px;vertical-align:top;font-size:10px;">
                <table width="100%" style="border-collapse:collapse;">
                    <tr><td style="color:#666;padding:1px 6px 1px 0;">País:</td><td style="font-weight:600;">REPÚBLICA DOMINICANA</td></tr>
                    <tr><td style="color:#666;padding:1px 6px 1px 0;">Provincia:</td><td style="font-weight:600;"><?php echo htmlspecialchars($comite['provincia'] ?: '—'); ?></td></tr>
                    <tr><td style="color:#666;padding:1px 6px 1px 0;">Municipio:</td><td style="font-weight:600;"><?php echo htmlspecialchars($comite['municipio'] ?: '—'); ?></td></tr>
                    <tr><td style="color:#666;padding:1px 6px 1px 0;">Circ.:</td><td style="font-weight:600;"><?php echo htmlspecialchars($comite['circunscripcion'] ?: '—'); ?></td></tr>
                    <tr><td style="color:#666;padding:1px 6px 1px 0;">Zona:</td><td style="font-weight:600;"><?php echo htmlspecialchars($comite['zona'] ?: '—'); ?></td></tr>
                    <tr><td style="color:#666;padding:1px 6px 1px 0;">Fecha:</td><td style="font-weight:600;"><?php echo date('d/m/Y', strtotime($comite['fecha_creacion'])); ?></td></tr>
                </table>
            </td>
            <?php if (!empty($comite['candidato_nombre'])): ?>
            <td style="width:150px;vertical-align:top;text-align:right;">
                <?php if (!empty($comite['candidato_foto'])): ?>
                <img src="data:image/jpeg;base64,<?php echo base64_encode($comite['candidato_foto']); ?>" style="width:56px;height:56px;border-radius:50%;object-fit:cover;">
                <?php endif; ?>
                <div style="font-size:10px;font-weight:700;margin-top:2px;"><?php echo htmlspecialchars($comite['candidato_nombre']); ?></div>
                <div style="font-size:9px;color:#666;"><?php echo ucfirst($comite['candidato_cargo'] ?? ''); ?></div>
            </td>
            <?php endif; ?>
        </tr>
    </table>

    <?php if ($coordinador): ?>
    <div style="border:2px solid <?php echo htmlspecialchars($colorPrincipal); ?>;border-radius:8px;padding:12px;margin-bottom:16px;">
        <table width="100%" style="border-collapse:collapse;">
            <tr>
                <td style="width:110px;vertical-align:top;">
                    <?php if (!empty($coordinador['foto'])): ?>
                    <img src="data:image/jpeg;base64,<?php echo $coordinador['foto']; ?>" style="width:100px;height:120px;object-fit:cover;border-radius:6px;border:1px solid #ccc;">
                    <?php else: ?>
                    <div style="width:100px;height:120px;border-radius:6px;background:#eee;display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:700;color:#999;">
                        <?php echo strtoupper(substr($coordinador['nombre_completo'] ?? $coordinador['nombre'] ?? '?', 0, 1)); ?>
                    </div>
                    <?php endif; ?>
                </td>
                <td style="vertical-align:top;padding-left:14px;font-size:12px;">
                    <div style="font-size:11px;font-weight:700;color:<?php echo htmlspecialchars($colorPrincipal); ?>;letter-spacing:1px;">COORDINADOR</div>
                    <div style="font-size:17px;font-weight:700;margin:4px 0 6px;"><?php echo htmlspecialchars($coordinador['nombre_completo'] ?? $coordinador['nombre'] ?? '—'); ?></div>
                    <div>Cédula: <strong><?php echo htmlspecialchars($coordinador['cedula'] ?? '—'); ?></strong> &nbsp;&nbsp; Colegio: <strong><?php echo htmlspecialchars($coordinador['colegio'] ?? '—'); ?></strong></div>
                    <div>Teléfono: <strong><?php echo htmlspecialchars($coordinador['telefono'] ?? '—'); ?></strong></div>
                    <div style="color:#555;margin-top:4px;">Recinto <?php echo htmlspecialchars($coordinador['recinto'] ?? '—'); ?>, <?php echo htmlspecialchars($coordinador['municipio'] ?? '—'); ?></div>
                </td>
            </tr>
        </table>
    </div>
    <?php endif; ?>

    <?php if (count($miembros)): ?>
    <div style="font-size:11px;font-weight:700;color:<?php echo htmlspecialchars($colorPrincipal); ?>;margin-bottom:6px;">MIEMBROS (<?php echo count($miembros); ?>)</div>
    <table width="100%" style="border-collapse:collapse;">
        <?php foreach (array_chunk($miembros, 2) as $fila): ?>
        <tr>
            <?php foreach ($fila as $m): ?>
            <td width="50%" style="vertical-align:top;padding:4px;">
                <table width="100%" style="border-collapse:collapse;border:1px solid #ccc;border-radius:6px;">
                    <tr>
                        <td style="width:64px;padding:6px;vertical-align:top;">
                            <?php if (!empty($m['foto'])): ?>
                            <img src="data:image/jpeg;base64,<?php echo $m['foto']; ?>" style="width:56px;height:70px;object-fit:cover;border-radius:4px;">
                            <?php else: ?>
                            <div style="width:56px;height:70px;border-radius:4px;background:#eee;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;color:#999;">
                                <?php echo strtoupper(substr($m['nombre_completo'] ?? $m['nombre'] ?? '?', 0, 1)); ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td style="padding:6px 8px 6px 0;vertical-align:top;font-size:9.5px;">
                            <div style="font-weight:700;color:<?php echo htmlspecialchars($colorPrincipal); ?>;font-size:9px;">MIEMBRO</div>
                            <div style="font-weight:700;font-size:11px;margin:2px 0;"><?php echo htmlspecialchars($m['nombre_completo'] ?? $m['nombre'] ?? '—'); ?></div>
                            <div>Cédula: <?php echo htmlspecialchars($m['cedula'] ?? '—'); ?></div>
                            <div>Colegio: <?php echo htmlspecialchars($m['colegio'] ?? '—'); ?> &nbsp; Tel: <?php echo htmlspecialchars($m['telefono'] ?? '—'); ?></div>
                            <div style="color:#666;">Recinto <?php echo htmlspecialchars($m['recinto'] ?? '—'); ?>, <?php echo htmlspecialchars($m['municipio'] ?? '—'); ?></div>
                        </td>
                    </tr>
                </table>
            </td>
            <?php endforeach; ?>
            <?php if (count($fila) === 1): ?><td width="50%"></td><?php endif; ?>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    if ($('#miembrosTable tbody tr').length > 4) {
        $('#miembrosTable').DataTable({ language: { url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json' }, pageLength: 10 });
    }
});
function imprimirComite() {
    window.print();
}
</script>

<?php include 'includes/footer.php'; ?>
