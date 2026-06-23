<?php
require_once 'includes/auth.php';
require_once 'includes/funciones.php';
require_once 'includes/estadisticas.php';
require_once 'db/config.php';

requiereAutenticacion();

$total_comites       = obtenerTotalComites();
$total_miembros      = obtenerTotalMiembros();
$total_coordinadores = obtenerTotalCoordinadores();
$comites_recientes   = obtenerComitesRecientes(5);
$miembros_recientes  = obtenerMiembrosRecientes(5);
$est_municipio       = obtenerEstadisticasPorMunicipio(6);
$est_usuario         = obtenerEstadisticasPorUsuario(5);

$conn = conectarDB();
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM comites WHERE creado_por = ?");
$stmt->bind_param("i", $_SESSION['usuario_id']);
$stmt->execute();
$mis_comites = $stmt->get_result()->fetch_assoc()['total'];

$titulo_pagina = 'Dashboard';
include 'includes/header.php';
?>

<style>
/* ── RESPONSIVE DASHBOARD ───────────────────────── */
@media (max-width: 576px) {
    .welcome-banner { flex-direction: column; gap: 16px; }
    .welcome-actions { width: 100%; }
    .welcome-actions .btn { flex: 1; justify-content: center; font-size: 12px; }
    .welcome-name { font-size: 18px; }
    .stat-value { font-size: 24px; }
    .chart-wrap { height: 180px; }
}

/* ── STAT CARDS ──────────────────────────────────── */
.stat-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 20px 24px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: var(--shadow-sm);
    transition: box-shadow 0.2s, transform 0.2s;
    text-decoration: none;
    color: inherit;
}
.stat-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-1px);
    color: inherit;
}
.stat-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}
.stat-icon.blue   { background: #eff6ff; color: #2563eb; }
.stat-icon.green  { background: #f0fdf4; color: #16a34a; }
.stat-icon.purple { background: #faf5ff; color: #7c3aed; }
.stat-icon.amber  { background: #fffbeb; color: #d97706; }
.stat-body { flex: 1; min-width: 0; }
.stat-value {
    font-size: 28px;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1;
    margin-bottom: 4px;
}
.stat-label {
    font-size: 12px;
    font-weight: 500;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.stat-trend {
    font-size: 11px;
    font-weight: 600;
    margin-top: 6px;
    display: flex;
    align-items: center;
    gap: 3px;
}
.stat-trend.up   { color: #16a34a; }
.stat-trend.flat { color: var(--text-secondary); }

/* ── SECTION HEADER ──────────────────────────────── */
.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}
.section-title {
    font-size: 14px;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
}
.section-title span {
    font-size: 12px;
    font-weight: 400;
    color: var(--text-secondary);
    margin-left: 6px;
}

/* ── ACTIVITY FEED ───────────────────────────────── */
.activity-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
}
.activity-item:last-child { border-bottom: none; }
.activity-dot {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    flex-shrink: 0;
}
.activity-dot.create { background: #f0fdf4; color: #16a34a; }
.activity-dot.edit   { background: #eff6ff; color: #2563eb; }
.activity-body { flex: 1; min-width: 0; }
.activity-text { font-size: 13px; color: var(--text-primary); line-height: 1.4; }
.activity-text a { color: var(--accent); text-decoration: none; font-weight: 500; }
.activity-text a:hover { text-decoration: underline; }
.activity-time { font-size: 11px; color: var(--text-secondary); margin-top: 3px; }

/* ── MEMBER ROW ──────────────────────────────────── */
.member-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid #f1f5f9;
}
.member-row:last-child { border-bottom: none; }
.member-avatar-sm {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: var(--accent);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    flex-shrink: 0;
}
.member-info { flex: 1; min-width: 0; }
.member-name { font-size: 13px; font-weight: 500; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.member-meta { font-size: 11px; color: var(--text-secondary); }

/* ── CHART CONTAINER ─────────────────────────────── */
.chart-wrap { position: relative; height: 220px; }

/* ── EMPTY STATE ─────────────────────────────────── */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: var(--text-secondary);
}
.empty-state i { font-size: 36px; margin-bottom: 12px; opacity: 0.3; }
.empty-state p { font-size: 13px; margin: 0; }

/* ── COMITE TABLE ────────────────────────────────── */
.comite-name { font-weight: 500; color: var(--text-primary); }
.comite-location { font-size: 12px; color: var(--text-secondary); }
.action-link {
    width: 28px; height: 28px;
    border-radius: 6px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 12px;
    border: 1px solid var(--border);
    color: var(--text-secondary);
    text-decoration: none;
    transition: all 0.15s;
}
.action-link:hover { border-color: var(--accent); color: var(--accent); background: #eff6ff; }
.action-link.danger:hover { border-color: var(--danger); color: var(--danger); background: #fef2f2; }

/* ── WELCOME BANNER ──────────────────────────────── */
.welcome-banner {
    background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);
    border-radius: var(--radius);
    padding: 24px 28px;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}
.welcome-banner::before {
    content: '';
    position: absolute;
    top: -30px; right: -30px;
    width: 160px; height: 160px;
    border-radius: 50%;
    background: rgba(255,255,255,0.06);
}
.welcome-banner::after {
    content: '';
    position: absolute;
    bottom: -50px; right: 60px;
    width: 200px; height: 200px;
    border-radius: 50%;
    background: rgba(255,255,255,0.04);
}
.welcome-greeting {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    opacity: 0.8;
    margin-bottom: 4px;
}
.welcome-name { font-size: 22px; font-weight: 700; margin-bottom: 6px; }
.welcome-sub { font-size: 13px; opacity: 0.75; }
.welcome-actions { display: flex; gap: 10px; flex-shrink: 0; position: relative; z-index: 1; }
.btn-white {
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.25);
    color: #fff;
    backdrop-filter: blur(4px);
}
.btn-white:hover { background: rgba(255,255,255,0.25); color: #fff; }
.btn-white-solid { background: #fff; color: var(--accent); border: none; font-weight: 600; }
.btn-white-solid:hover { background: #f0f4ff; color: #1d4ed8; }
</style>

<!-- Welcome Banner -->
<div class="welcome-banner">
    <div style="position:relative;z-index:1;">
        <div class="welcome-greeting">Bienvenido de vuelta</div>
        <div class="welcome-name"><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Administrador'); ?></div>
        <div class="welcome-sub">Sistema de Gestión de Comités Afectivos — PRM <?php echo date('Y'); ?></div>
    </div>
    <div class="welcome-actions">
        <a href="crear_comite.php" class="btn btn-white-solid btn-sm">
            <i class="fas fa-plus me-1"></i> Crear Comité
        </a>
        <a href="consultar.html" class="btn btn-white btn-sm">
            <i class="fas fa-id-card me-1"></i> Consultar Cédula
        </a>
    </div>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <a href="comites.php" class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-layer-group"></i></div>
            <div class="stat-body">
                <div class="stat-value"><?php echo number_format($total_comites); ?></div>
                <div class="stat-label">Total Comités</div>
                <div class="stat-trend <?php echo $total_comites > 0 ? 'up' : 'flat'; ?>">
                    <i class="fas fa-circle" style="font-size:6px;"></i>
                    <?php echo $total_comites > 0 ? 'Activos' : 'Sin registros'; ?>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-users"></i></div>
            <div class="stat-body">
                <div class="stat-value"><?php echo number_format($total_miembros); ?></div>
                <div class="stat-label">Total Miembros</div>
                <div class="stat-trend flat">
                    <i class="fas fa-circle" style="font-size:6px;"></i>
                    Registrados
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon purple"><i class="fas fa-user-tie"></i></div>
            <div class="stat-body">
                <div class="stat-value"><?php echo number_format($total_coordinadores); ?></div>
                <div class="stat-label">Coordinadores</div>
                <div class="stat-trend flat">
                    <i class="fas fa-circle" style="font-size:6px;"></i>
                    Asignados
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon amber"><i class="fas fa-star"></i></div>
            <div class="stat-body">
                <div class="stat-value"><?php echo number_format($mis_comites); ?></div>
                <div class="stat-label">Mis Comités</div>
                <div class="stat-trend <?php echo $mis_comites > 0 ? 'up' : 'flat'; ?>">
                    <i class="fas fa-circle" style="font-size:6px;"></i>
                    Creados por mí
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<?php if (count($est_municipio) > 0 || count($est_usuario) > 0): ?>
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-map-marker-alt me-2 text-primary"></i>Comités por Municipio</span>
            </div>
            <div class="p-3">
                <div class="chart-wrap">
                    <canvas id="chartMunicipios"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-user-circle me-2 text-primary"></i>Distribución por Usuario</span>
            </div>
            <div class="p-3">
                <div class="chart-wrap">
                    <canvas id="chartUsuarios"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Tables Row -->
<div class="row g-3">
    <!-- Recent Comites -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-clock me-2 text-primary"></i>Comités Recientes</span>
                <a href="comites.php" class="btn btn-sm btn-outline-primary" style="font-size:12px;">Ver todos</a>
            </div>
            <?php if (count($comites_recientes) > 0): ?>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Comité</th>
                            <th>Coordinador</th>
                            <th>Miembros</th>
                            <th>Creado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($comites_recientes as $c):
                        $stmt2 = $conn->prepare("SELECT c.nombre_completo FROM coordinadores c JOIN comite_coordinador cc ON c.id = cc.coordinador_id WHERE cc.comite_id = ? LIMIT 1");
                        $stmt2->bind_param("i", $c['id']);
                        $stmt2->execute();
                        $coord = $stmt2->get_result()->fetch_assoc();

                        $stmt3 = $conn->prepare("SELECT COUNT(*) as t FROM comite_miembro WHERE comite_id = ?");
                        $stmt3->bind_param("i", $c['id']);
                        $stmt3->execute();
                        $cnt = $stmt3->get_result()->fetch_assoc()['t'];
                    ?>
                    <tr>
                        <td>
                            <div class="comite-name"><?php echo htmlspecialchars($c['nombre']); ?></div>
                            <div class="comite-location"><i class="fas fa-map-pin me-1"></i><?php echo htmlspecialchars($c['municipio']); ?></div>
                        </td>
                        <td style="font-size:13px;">
                            <?php echo $coord ? htmlspecialchars($coord['nombre_completo']) : '<span class="text-muted">—</span>'; ?>
                        </td>
                        <td>
                            <span class="badge bg-primary bg-opacity-10 text-primary"><?php echo $cnt; ?></span>
                        </td>
                        <td style="font-size:12px;color:var(--text-secondary);">
                            <?php echo date('d/m/Y', strtotime($c['fecha_creacion'])); ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="ver_comite.php?id=<?php echo $c['id']; ?>" class="action-link" title="Ver"><i class="fas fa-eye"></i></a>
                                <a href="editar_comite.php?id=<?php echo $c['id']; ?>" class="action-link" title="Editar"><i class="fas fa-pen"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-folder-open d-block"></i>
                <p>No hay comités creados aún</p>
                <a href="crear_comite.php" class="btn btn-primary btn-sm mt-2">
                    <i class="fas fa-plus me-1"></i> Crear primer comité
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Column -->
    <div class="col-lg-4">
        <!-- Recent Members -->
        <div class="card mb-3">
            <div class="card-header">
                <span><i class="fas fa-user-plus me-2 text-primary"></i>Miembros Recientes</span>
            </div>
            <div class="p-3">
                <?php if (count($miembros_recientes) > 0): ?>
                    <?php foreach ($miembros_recientes as $m): ?>
                    <div class="member-row">
                        <div class="member-avatar-sm">
                            <?php echo strtoupper(substr($m['nombre_completo'], 0, 1)); ?>
                        </div>
                        <div class="member-info">
                            <div class="member-name"><?php echo htmlspecialchars($m['nombre_completo']); ?></div>
                            <div class="member-meta">
                                <i class="fas fa-id-card me-1"></i><?php echo htmlspecialchars($m['cedula']); ?>
                            </div>
                        </div>
                        <div style="font-size:11px;color:var(--text-secondary);">
                            <?php echo date('d/m', strtotime($m['fecha_registro'])); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <div class="empty-state" style="padding:24px 16px;">
                    <i class="fas fa-users d-block"></i>
                    <p>Sin miembros registrados</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-bolt me-2 text-primary"></i>Acciones Rápidas</span>
            </div>
            <div class="p-3 d-grid gap-2">
                <a href="crear_comite.php" class="btn btn-primary btn-sm text-start">
                    <i class="fas fa-layer-group me-2"></i> Crear Comité
                </a>
                <a href="consultar.html" class="btn btn-outline-secondary btn-sm text-start">
                    <i class="fas fa-id-card me-2"></i> Consultar Cédula
                </a>
                <a href="comites.php" class="btn btn-outline-secondary btn-sm text-start">
                    <i class="fas fa-list me-2"></i> Ver todos los Comités
                </a>
                <?php if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] == 'admin'): ?>
                <a href="usuarios.php" class="btn btn-outline-secondary btn-sm text-start">
                    <i class="fas fa-users-cog me-2"></i> Gestión de Usuarios
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const CHART_COLORS = ['#2563eb','#10b981','#f59e0b','#7c3aed','#ef4444','#06b6d4'];

Chart.defaults.font.family = "'Inter', 'Segoe UI', sans-serif";
Chart.defaults.font.size   = 12;
Chart.defaults.color       = '#64748b';

<?php if (count($est_municipio) > 0): ?>
new Chart(document.getElementById('chartMunicipios'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($est_municipio, 'municipio')); ?>,
        datasets: [{
            label: 'Comités',
            data: <?php echo json_encode(array_column($est_municipio, 'total')); ?>,
            backgroundColor: CHART_COLORS.map(c => c + '22'),
            borderColor: CHART_COLORS,
            borderWidth: 2,
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ` ${ctx.parsed.y} comité${ctx.parsed.y !== 1 ? 's' : ''}`
                }
            }
        },
        scales: {
            x: { grid: { display: false }, border: { display: false } },
            y: {
                beginAtZero: true,
                ticks: { precision: 0, stepSize: 1 },
                grid: { color: '#f1f5f9' },
                border: { display: false }
            }
        }
    }
});
<?php endif; ?>

<?php if (count($est_usuario) > 0): ?>
new Chart(document.getElementById('chartUsuarios'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_column($est_usuario, 'nombre')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($est_usuario, 'total')); ?>,
            backgroundColor: CHART_COLORS,
            borderWidth: 0,
            hoverOffset: 6,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: { padding: 16, boxWidth: 10, boxHeight: 10, borderRadius: 3 }
            },
            tooltip: {
                callbacks: {
                    label: ctx => ` ${ctx.label}: ${ctx.parsed} comités`
                }
            }
        }
    }
});
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>
