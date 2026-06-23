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
$est_municipio       = obtenerEstadisticasPorMunicipio(5);
$est_usuario         = obtenerEstadisticasPorUsuario(5);

$conn = conectarDB();
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM comites WHERE creado_por = ?");
$stmt->bind_param("i", $_SESSION['usuario_id']);
$stmt->execute();
$mis_comites = $stmt->get_result()->fetch_assoc()['total'];

$cfg = cargarConfiguracion();
$logo_b64 = !empty($cfg['logo']) ? base64_encode($cfg['logo']) : '';

$titulo_pagina = 'Dashboard';
include 'includes/header.php';
?>

<style>
/* ── RESET PAGE BG ────────────────────────────────── */
.page-content { background: #f5f6fa; }

/* ── TOPBAR SEARCH ────────────────────────────────── */
.dash-search {
    display: flex; align-items: center; gap: 10px;
    background: #fff; border: 1px solid #e8e8f0;
    border-radius: 12px; padding: 8px 16px;
    flex: 1; max-width: 360px;
}
.dash-search input {
    border: none; outline: none; font-size: 13px;
    color: #374151; background: transparent; width: 100%;
    font-family: inherit;
}
.dash-search i { color: #9ca3af; font-size: 14px; }

/* ── LAYOUT ───────────────────────────────────────── */
.dash-grid {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 20px;
    align-items: start;
}
@media (max-width: 1100px) { .dash-grid { grid-template-columns: 1fr; } }

/* ── HERO BANNER ──────────────────────────────────── */
.hero-banner {
    background: linear-gradient(135deg, var(--accent) 0%, var(--accent-light) 100%);
    border-radius: 20px;
    padding: 28px 32px;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 20px;
    position: relative;
    overflow: hidden;
    min-height: 140px;
}
.hero-banner::before {
    content: '';
    position: absolute; top: -40px; right: -20px;
    width: 200px; height: 200px; border-radius: 50%;
    background: rgba(255,255,255,.1);
}
.hero-banner::after {
    content: '';
    position: absolute; bottom: -60px; right: 80px;
    width: 160px; height: 160px; border-radius: 50%;
    background: rgba(255,255,255,.07);
}
.hero-tag {
    display: inline-block;
    background: rgba(255,255,255,.2);
    font-size: 10px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .08em;
    padding: 4px 10px; border-radius: 20px;
    margin-bottom: 10px;
}
.hero-title { font-size: 22px; font-weight: 700; line-height: 1.25; margin-bottom: 16px; }
.hero-btn {
    display: inline-flex; align-items: center; gap: 8px;
    background: #fff; color: var(--accent);
    border: none; border-radius: 30px;
    padding: 9px 20px; font-size: 13px; font-weight: 700;
    cursor: pointer; text-decoration: none;
    transition: transform .15s;
    position: relative; z-index: 1;
}
.hero-btn:hover { transform: scale(1.04); color: var(--accent); }
.hero-btn .arrow {
    width: 26px; height: 26px; border-radius: 50%;
    background: var(--accent); color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px;
}
.hero-illus {
    position: relative; z-index: 1; flex-shrink: 0;
    display: flex; align-items: center;
}
.hero-illus img {
    width: 90px; height: 90px; border-radius: 50%;
    border: 4px solid rgba(255,255,255,.3);
    object-fit: contain; background: rgba(255,255,255,.15);
    padding: 8px;
}
.hero-illus-placeholder {
    width: 90px; height: 90px; border-radius: 50%;
    border: 4px solid rgba(255,255,255,.3);
    background: rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 36px; color: rgba(255,255,255,.8);
}

/* ── STAT MINI CARDS ──────────────────────────────── */
.stat-row { display: grid; grid-template-columns: repeat(3,1fr); gap: 14px; margin-bottom: 20px; }
@media (max-width: 600px) { .stat-row { grid-template-columns: repeat(2,1fr); } }

.stat-mini {
    background: #fff; border-radius: 16px;
    padding: 18px 20px;
    border: 1px solid #eef0f6;
    display: flex; align-items: center; gap: 14px;
    transition: box-shadow .2s, transform .2s;
    text-decoration: none; color: inherit;
}
.stat-mini:hover { box-shadow: 0 8px 24px rgba(0,0,0,.08); transform: translateY(-2px); color: inherit; }
.stat-mini-icon {
    width: 46px; height: 46px; border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; flex-shrink: 0;
}
.si-purple { background: color-mix(in srgb, var(--accent) 10%, white); color: var(--accent); }
.si-green  { background: #f0fdf4; color: #16a34a; }
.si-blue   { background: color-mix(in srgb, var(--accent-light) 12%, white); color: var(--accent-light); }
.si-amber  { background: #fffbeb; color: #d97706; }
.stat-mini-val { font-size: 24px; font-weight: 800; color: #111827; line-height: 1; }
.stat-mini-lbl { font-size: 11px; font-weight: 500; color: #9ca3af; margin-top: 2px; text-transform: uppercase; letter-spacing: .04em; }

/* ── SECTION TITLE ────────────────────────────────── */
.section-head {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 14px;
}
.section-head h2 { font-size: 15px; font-weight: 700; color: #111827; margin: 0; }
.section-head a  { font-size: 12px; color: var(--accent); text-decoration: none; font-weight: 500; }

/* ── RECENT COMITES ───────────────────────────────── */
.comite-list { display: flex; flex-direction: column; gap: 10px; }
.comite-item {
    background: #fff; border-radius: 14px;
    padding: 14px 18px;
    display: flex; align-items: center; gap: 14px;
    border: 1px solid #eef0f6;
    transition: box-shadow .15s;
}
.comite-item:hover { box-shadow: 0 4px 16px rgba(0,0,0,.07); }
.comite-item-icon {
    width: 42px; height: 42px; border-radius: 12px;
    background: color-mix(in srgb, var(--accent) 10%, white); color: var(--accent);
    display: flex; align-items: center; justify-content: center;
    font-size: 17px; flex-shrink: 0;
}
.comite-item-name { font-size: 13px; font-weight: 600; color: #111827; }
.comite-item-meta { font-size: 11px; color: #9ca3af; margin-top: 2px; }
.comite-item-badge {
    margin-left: auto; flex-shrink: 0;
    background: color-mix(in srgb, var(--accent) 10%, white); color: var(--accent);
    font-size: 11px; font-weight: 700;
    padding: 4px 10px; border-radius: 20px;
}
.comite-item-actions { display: flex; gap: 6px; flex-shrink: 0; }
.ci-btn {
    width: 30px; height: 30px; border-radius: 8px;
    border: 1px solid #eef0f6; background: #fff;
    color: #9ca3af; display: flex; align-items: center;
    justify-content: center; font-size: 12px;
    cursor: pointer; text-decoration: none;
    transition: all .15s;
}
.ci-btn:hover { border-color: var(--accent); color: var(--accent); background: color-mix(in srgb, var(--accent) 8%, white); }

/* ── RIGHT PANEL ──────────────────────────────────── */
.right-panel { display: flex; flex-direction: column; gap: 16px; }

/* Greeting card */
.greeting-card {
    background: #fff; border-radius: 20px; padding: 24px;
    text-align: center; border: 1px solid #eef0f6;
}
.greeting-avatar-wrap { position: relative; display: inline-block; margin-bottom: 14px; }
.greeting-avatar {
    width: 72px; height: 72px; border-radius: 50%;
    background: linear-gradient(135deg, var(--accent), var(--accent-light));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 28px; font-weight: 800; margin: 0 auto;
    border: 4px solid #f5f6fa;
}
.greeting-pct {
    position: absolute; top: -4px; right: -8px;
    background: var(--accent); color: #fff;
    font-size: 10px; font-weight: 700;
    padding: 2px 6px; border-radius: 10px;
}
.greeting-name { font-size: 15px; font-weight: 700; color: #111827; margin-bottom: 4px; }
.greeting-sub  { font-size: 12px; color: #9ca3af; }

/* Progress ring */
.progress-ring-wrap {
    width: 90px; height: 90px; position: relative; margin: 0 auto 14px;
}
.progress-ring-wrap svg { transform: rotate(-90deg); }
.progress-ring-bg { fill: none; stroke: color-mix(in srgb, var(--accent) 10%, white); stroke-width: 6; }
.progress-ring-fg { fill: none; stroke: var(--accent); stroke-width: 6; stroke-linecap: round; transition: stroke-dashoffset .6s ease; }

/* Chart card */
.chart-card {
    background: #fff; border-radius: 20px; padding: 20px;
    border: 1px solid #eef0f6;
}
.chart-card-title { font-size: 14px; font-weight: 700; color: #111827; margin-bottom: 16px; }

/* Mini bar chart */
.mini-bar-chart { display: flex; align-items: flex-end; gap: 8px; height: 80px; }
.mini-bar-wrap { display: flex; flex-direction: column; align-items: center; gap: 4px; flex: 1; }
.mini-bar {
    width: 100%; border-radius: 6px 6px 0 0;
    background: color-mix(in srgb, var(--accent) 12%, white); transition: height .4s ease;
    min-height: 4px;
}
.mini-bar.active { background: var(--accent); }
.mini-bar-lbl { font-size: 9px; color: #9ca3af; text-align: center; }

/* Recent members card */
.members-card {
    background: #fff; border-radius: 20px; padding: 20px;
    border: 1px solid #eef0f6;
}
.member-row-mini {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 0; border-bottom: 1px solid #f5f6fa;
}
.member-row-mini:last-child { border-bottom: none; }
.member-av-sm {
    width: 34px; height: 34px; border-radius: 50%;
    background: linear-gradient(135deg, var(--accent), var(--accent-light));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700; flex-shrink: 0;
}
.member-nm { font-size: 12px; font-weight: 600; color: #374151; }
.member-cd { font-size: 10px; color: #9ca3af; }

/* Empty state */
.empty-dash { text-align: center; padding: 32px 16px; color: #d1d5db; }
.empty-dash i { font-size: 32px; margin-bottom: 10px; display: block; }
.empty-dash p { font-size: 12px; margin: 0; }
</style>

<?php
// Inject search bar into topbar via JS after load
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const topbarLeft = document.querySelector('.topbar-left');
    if (topbarLeft) {
        const search = document.createElement('div');
        search.className = 'dash-search d-none d-md-flex';
        search.innerHTML = '<i class="fas fa-search"></i><input type="text" placeholder="Buscar comités, miembros...">';
        topbarLeft.appendChild(search);
    }
});
</script>

<div class="dash-grid">
    <!-- ── LEFT COLUMN ── -->
    <div>
        <!-- Hero Banner -->
        <div class="hero-banner">
            <div style="position:relative;z-index:1;">
                <div class="hero-tag">Comités Afectivos 2028</div>
                <div class="hero-title">
                    Bienvenido,<br><?php echo htmlspecialchars(explode(' ', $_SESSION['usuario_nombre'])[0]); ?> 👋
                </div>
                <a href="crear_comite.php" class="hero-btn">
                    Crear Comité
                    <span class="arrow"><i class="fas fa-arrow-right"></i></span>
                </a>
            </div>
            <div class="hero-illus">
                <?php if ($logo_b64): ?>
                <img src="data:image/png;base64,<?php echo $logo_b64; ?>" alt="Logo">
                <?php else: ?>
                <div class="hero-illus-placeholder"><i class="fas fa-star"></i></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stat Mini Cards -->
        <div class="stat-row">
            <a href="comites.php" class="stat-mini">
                <div class="stat-mini-icon si-purple"><i class="fas fa-layer-group"></i></div>
                <div>
                    <div class="stat-mini-val"><?php echo number_format($total_comites); ?></div>
                    <div class="stat-mini-lbl">Comités</div>
                </div>
            </a>
            <div class="stat-mini">
                <div class="stat-mini-icon si-green"><i class="fas fa-users"></i></div>
                <div>
                    <div class="stat-mini-val"><?php echo number_format($total_miembros); ?></div>
                    <div class="stat-mini-lbl">Miembros</div>
                </div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon si-blue"><i class="fas fa-user-tie"></i></div>
                <div>
                    <div class="stat-mini-val"><?php echo number_format($total_coordinadores); ?></div>
                    <div class="stat-mini-lbl">Coordinadores</div>
                </div>
            </div>
        </div>

        <!-- Candidato activo (digitadores) -->
        <?php if (tieneCandidato()): ?>
        <div style="background:color-mix(in srgb,var(--accent) 8%,white);border-radius:16px;padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;gap:14px;border:1px solid color-mix(in srgb,var(--accent) 20%,white);">
            <div style="width:44px;height:44px;border-radius:50%;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:800;flex-shrink:0;">
                <?php echo strtoupper(substr($_SESSION['candidato_nombre']??'?',0,1)); ?>
            </div>
            <div style="flex:1;">
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--accent);margin-bottom:2px;">Trabajando para</div>
                <div style="font-size:14px;font-weight:700;color:var(--text-primary);"><?php echo htmlspecialchars($_SESSION['candidato_nombre']??''); ?></div>
                <div style="font-size:11px;color:var(--accent);"><?php echo ucfirst($_SESSION['candidato_cargo']??''); ?><?php if(!empty($_SESSION['candidato_desc'])): ?> · <?php echo htmlspecialchars($_SESSION['candidato_desc']); ?><?php endif; ?></div>
            </div>
            <a href="seleccionar_candidato.php" style="font-size:11px;color:var(--accent);font-weight:600;text-decoration:none;background:#fff;padding:6px 12px;border-radius:20px;border:1px solid var(--border);">
                Cambiar
            </a>
        </div>
        <?php endif; ?>

        <!-- Recent Comités -->
        <div class="section-head">
            <h2>Comités Recientes</h2>
            <a href="comites.php">Ver todos →</a>
        </div>

        <?php if (count($comites_recientes) > 0): ?>
        <div class="comite-list">
            <?php foreach ($comites_recientes as $c):
                $s2 = $conn->prepare("SELECT c.nombre_completo FROM coordinadores c JOIN comite_coordinador cc ON c.id=cc.coordinador_id WHERE cc.comite_id=? LIMIT 1");
                $s2->bind_param("i",$c['id']); $s2->execute();
                $coord = $s2->get_result()->fetch_assoc();
                $s3 = $conn->prepare("SELECT COUNT(*) as t FROM comite_miembro WHERE comite_id=?");
                $s3->bind_param("i",$c['id']); $s3->execute();
                $cnt = $s3->get_result()->fetch_assoc()['t'];
            ?>
            <div class="comite-item">
                <div class="comite-item-icon"><i class="fas fa-layer-group"></i></div>
                <div style="flex:1;min-width:0;">
                    <div class="comite-item-name"><?php echo htmlspecialchars($c['nombre']); ?></div>
                    <div class="comite-item-meta">
                        <i class="fas fa-map-pin me-1"></i><?php echo htmlspecialchars($c['municipio']); ?>
                        <?php if ($coord): ?> · <?php echo htmlspecialchars($coord['nombre_completo']); ?><?php endif; ?>
                    </div>
                </div>
                <span class="comite-item-badge"><?php echo $cnt; ?> miembros</span>
                <div class="comite-item-actions">
                    <a href="ver_comite.php?id=<?php echo $c['id']; ?>" class="ci-btn"><i class="fas fa-eye"></i></a>
                    <a href="editar_comite.php?id=<?php echo $c['id']; ?>" class="ci-btn"><i class="fas fa-pen"></i></a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="background:#fff;border-radius:16px;border:1px solid #eef0f6;">
            <div class="empty-dash">
                <i class="fas fa-folder-open"></i>
                <p>No hay comités creados aún</p>
                <a href="crear_comite.php" class="btn btn-primary btn-sm mt-2" style="font-size:12px;">
                    <i class="fas fa-plus me-1"></i> Crear primer comité
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── RIGHT PANEL ── -->
    <div class="right-panel">

        <!-- Greeting Card with progress -->
        <div class="greeting-card">
            <?php
            $pct = $total_comites > 0 ? min(100, round(($mis_comites / max($total_comites,1)) * 100)) : 0;
            $r = 40; $circ = 2 * M_PI * $r;
            $offset = $circ - ($pct / 100) * $circ;
            ?>
            <div class="greeting-avatar-wrap">
                <div style="position:relative;width:90px;height:90px;margin:0 auto;">
                    <svg width="90" height="90" style="transform:rotate(-90deg);">
                        <circle class="progress-ring-bg" cx="45" cy="45" r="<?php echo $r; ?>"/>
                        <circle class="progress-ring-fg" cx="45" cy="45" r="<?php echo $r; ?>"
                            stroke-dasharray="<?php echo $circ; ?>"
                            stroke-dashoffset="<?php echo $offset; ?>"/>
                    </svg>
                    <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;">
                        <div class="greeting-avatar" style="width:62px;height:62px;font-size:22px;border:3px solid #f5f6fa;">
                            <?php echo strtoupper(substr($_SESSION['usuario_nombre']??'U',0,1)); ?>
                        </div>
                    </div>
                </div>
                <span class="greeting-pct"><?php echo $pct; ?>%</span>
            </div>
            <div class="greeting-name">Buenos días, <?php echo htmlspecialchars(explode(' ',$_SESSION['usuario_nombre'])[0]); ?> 🔥</div>
            <div class="greeting-sub">Mis comités: <?php echo $mis_comites; ?> de <?php echo $total_comites; ?> totales</div>
        </div>

        <!-- Chart municipios -->
        <?php if (count($est_municipio) > 0): ?>
        <div class="chart-card">
            <div class="section-head" style="margin-bottom:12px;">
                <div class="chart-card-title">Por Municipio</div>
            </div>
            <?php
            $max_val = max(array_column($est_municipio,'total')) ?: 1;
            ?>
            <div class="mini-bar-chart">
                <?php foreach ($est_municipio as $i => $e):
                    $h = round(($e['total'] / $max_val) * 70);
                    $active = $i === count($est_municipio)-1 ? 'active' : '';
                ?>
                <div class="mini-bar-wrap">
                    <div class="mini-bar <?php echo $active; ?>" style="height:<?php echo $h; ?>px;"></div>
                    <div class="mini-bar-lbl"><?php echo mb_substr($e['municipio'],0,5); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recent Members -->
        <?php if (count($miembros_recientes) > 0): ?>
        <div class="members-card">
            <div class="section-head" style="margin-bottom:12px;">
                <div class="chart-card-title">Miembros Recientes</div>
                <a href="#" style="font-size:12px;color:var(--accent);text-decoration:none;font-weight:500;">Ver todos</a>
            </div>
            <?php foreach ($miembros_recientes as $m): ?>
            <div class="member-row-mini">
                <div class="member-av-sm"><?php echo strtoupper(substr($m['nombre_completo'],0,1)); ?></div>
                <div style="flex:1;min-width:0;">
                    <div class="member-nm"><?php echo htmlspecialchars(mb_substr($m['nombre_completo'],0,22)); ?></div>
                    <div class="member-cd"><?php echo htmlspecialchars($m['cedula']); ?></div>
                </div>
                <div style="font-size:10px;color:#d1d5db;"><?php echo date('d/m',strtotime($m['fecha_registro'])); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Quick actions -->
        <div class="members-card">
            <div class="chart-card-title" style="margin-bottom:12px;">Acciones Rápidas</div>
            <div class="d-grid gap-2">
                <a href="crear_comite.php" class="btn btn-primary btn-sm" style="border-radius:10px;font-size:13px;">
                    <i class="fas fa-plus me-2"></i>Crear Comité
                </a>
                <a href="consultar.html" class="btn btn-sm" style="border-radius:10px;font-size:13px;background:#f5f6fa;color:#374151;border:1px solid #eef0f6;">
                    <i class="fas fa-id-card me-2"></i>Consultar Cédula
                </a>
                <?php if (esAdmin()): ?>
                <a href="config.php" class="btn btn-sm" style="border-radius:10px;font-size:13px;background:#f5f6fa;color:#374151;border:1px solid #eef0f6;">
                    <i class="fas fa-cog me-2"></i>Configuración
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
