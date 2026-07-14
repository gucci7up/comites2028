<?php
require_once 'includes/auth.php';
require_once 'includes/funciones.php';
require_once 'includes/estadisticas.php';
require_once 'db/config.php';

requiereAutenticacion();

$total_comites       = obtenerTotalComites();
$total_miembros      = obtenerTotalMiembros();
$total_coordinadores = obtenerTotalCoordinadores();
$comites_recientes   = obtenerComitesRecientes(6);
$miembros_recientes  = obtenerMiembrosRecientes(4);
$est_mes             = obtenerEstadisticasPorMes(6);

$conn = conectarDB();
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM comites WHERE creado_por = ?");
$stmt->bind_param("i", $_SESSION['usuario_id']);
$stmt->execute();
$mis_comites = $stmt->get_result()->fetch_assoc()['total'];

$cfg      = cargarConfiguracion();
$logo_b64 = !empty($cfg['logo']) ? base64_encode($cfg['logo']) : '';
$first_name = explode(' ', trim($_SESSION['usuario_nombre'] ?? 'Usuario'))[0];

// ── Meta del mes: comités creados este mes vs. una meta derivada del historial real ──
$conteo_por_mes = [];
foreach ($est_mes as $e) { $conteo_por_mes[$e['mes']] = (int)$e['total']; }
$comites_mes_actual   = $conteo_por_mes[date('Y-m')] ?? 0;
$comites_mes_anterior = $conteo_por_mes[date('Y-m', strtotime('-1 month'))] ?? 0;
$meta_mes = (int)(ceil(max($comites_mes_actual, $comites_mes_anterior, 10) / 10) * 10);
$pct_mes  = $meta_mes > 0 ? min(100, round(($comites_mes_actual / $meta_mes) * 100)) : 0;

// ── Registros diarios reales (14 días) para los mini gráficos de barras ──
function registrosPorDia($conn, $tabla, $dias = 14) {
    if (!in_array($tabla, ['miembros', 'coordinadores'], true)) return [];
    $dias = (int)$dias;
    $stmt = $conn->prepare("SELECT DATE(fecha_registro) as dia, COUNT(*) as total FROM `$tabla` WHERE fecha_registro >= DATE_SUB(CURDATE(), INTERVAL ? DAY) GROUP BY DATE(fecha_registro)");
    $stmt->bind_param("i", $dias);
    $stmt->execute();
    return array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'total', 'dia');
}
function serieBarras($porDia, $dias = 7, $offset = 0) {
    $vals = [];
    for ($i = $dias - 1; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime('-' . ($i + $offset) . ' day'));
        $vals[] = (int)($porDia[$d] ?? 0);
    }
    return $vals;
}
function barrasHtml($valores) {
    $max = max(max($valores), 1);
    $html = '';
    foreach ($valores as $i => $v) {
        $h  = max(4, round(($v / $max) * 34));
        $hi = ($i === count($valores) - 1) ? ' hi' : '';
        $html .= '<div class="bar' . $hi . '" style="height:' . $h . 'px"></div>';
    }
    return $html;
}

$por_dia_miembros = registrosPorDia($conn, 'miembros');
$bars_miembros_actual = serieBarras($por_dia_miembros, 7, 0);
$bars_miembros_prev   = serieBarras($por_dia_miembros, 7, 7);
$delta_miembros = array_sum($bars_miembros_prev) > 0
    ? round(((array_sum($bars_miembros_actual) - array_sum($bars_miembros_prev)) / array_sum($bars_miembros_prev)) * 100)
    : (array_sum($bars_miembros_actual) > 0 ? 100 : 0);

$por_dia_coord = registrosPorDia($conn, 'coordinadores');
$bars_coord_actual = serieBarras($por_dia_coord, 7, 0);

// ── Polyline "Comités por mes" con datos reales ──
$puntos_mes = '';
if (count($est_mes) >= 2) {
    $vals  = array_column($est_mes, 'total');
    $maxV  = max(max($vals), 1);
    $n     = count($vals);
    $puntos = [];
    foreach ($vals as $i => $v) {
        $x = $n > 1 ? round(($i / ($n - 1)) * 560) : 0;
        $y = 170 - round(($v / $maxV) * 150);
        $puntos[] = "$x,$y";
    }
    $puntos_mes = implode(' ', $puntos);
}

$titulo_pagina = 'Dashboard';
include 'includes/header.php';
?>
<style>
.welcome-row { display:grid; grid-template-columns:minmax(0,1fr) 260px; gap:22px; margin-bottom:22px; }
@media (max-width:900px) { .welcome-row { grid-template-columns:1fr; } }

.welcome-card { background:linear-gradient(135deg,#eef0ff,#e3e6ff); border-radius:22px; padding:32px; display:grid; grid-template-columns:minmax(0,1fr) auto; gap:18px; align-items:center; }
.welcome-title { font-size:22px; font-weight:800; margin-bottom:6px; color:var(--text-primary); }
.welcome-copy { font-size:13px; color:var(--text-secondary); margin-bottom:16px; max-width:34ch; }
.welcome-stat { font-size:30px; font-weight:800; margin-bottom:14px; color:var(--text-primary); }
.welcome-stat small { font-size:13px; font-weight:600; color:var(--text-secondary); }
.welcome-cta { display:inline-flex; align-items:center; gap:8px; background:var(--accent); color:#fff; font-size:13px; font-weight:700; padding:10px 18px; border-radius:12px; text-decoration:none; }
.welcome-cta:hover { color:#fff; filter:brightness(1.08); }
.welcome-illus { flex-shrink:0; width:120px; height:120px; border-radius:20px; background:rgba(255,255,255,.55); display:flex; align-items:center; justify-content:center; overflow:hidden; }
.welcome-illus img { width:100%; height:100%; object-fit:contain; padding:12px; }
.welcome-illus i { font-size:40px; color:var(--accent); opacity:.4; }

.goal-card { background:linear-gradient(160deg,var(--accent),var(--accent-700)); border-radius:22px; padding:24px; color:#fff; display:flex; flex-direction:column; justify-content:space-between; }
.goal-pct { font-size:28px; font-weight:800; }
.goal-label { font-size:12px; color:rgba(255,255,255,.75); margin-top:2px; }
.goal-bar { height:6px; border-radius:6px; background:rgba(255,255,255,.25); margin-bottom:8px; }
.goal-bar-fill { height:100%; border-radius:6px; background:#fff; transition:width .6s ease; }
.goal-sub { font-size:11.5px; color:rgba(255,255,255,.75); }

.stat-row { display:grid; grid-template-columns:1fr 1fr; gap:22px; margin-bottom:22px; }
@media (max-width:600px) { .stat-row { grid-template-columns:1fr; } }
.stat-mini { background:#fff; border-radius:22px; padding:22px; box-shadow:var(--shadow-sm); }
.stat-mini-label { font-size:12px; color:var(--text-tertiary); margin-bottom:4px; }
.stat-mini-top { display:flex; align-items:center; justify-content:space-between; }
.stat-mini-val { font-size:24px; font-weight:800; color:var(--text-primary); }
.stat-mini-bars { display:flex; align-items:flex-end; gap:3px; height:34px; }
.stat-mini-bars .bar { width:5px; border-radius:3px; background:var(--accent-tint); }
.stat-mini-bars .bar.hi { background:var(--accent); }
.stat-mini-delta { font-size:11.5px; margin-top:6px; }
.stat-mini-delta.up   { color:var(--success); }
.stat-mini-delta.down { color:var(--danger); }
.stat-mini-delta.flat { color:var(--text-tertiary); }

.chart-card { background:#fff; border-radius:22px; padding:24px; box-shadow:var(--shadow-sm); }
.chart-hd { display:flex; align-items:center; justify-content:space-between; margin-bottom:4px; }
.chart-title { font-size:15px; font-weight:700; }
.chart-sub { font-size:12px; color:var(--text-tertiary); }

.right-col { display:flex; flex-direction:column; gap:22px; }
.ring-card { background:#fff; border-radius:22px; padding:24px; box-shadow:var(--shadow-sm); }
.ring-title { font-size:14px; font-weight:700; margin-bottom:2px; }
.ring-sub { font-size:12px; color:var(--text-tertiary); margin-bottom:16px; }
.ring-wrap { position:relative; width:150px; height:150px; margin:0 auto; }
.ring-num { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-size:20px; font-weight:800; }
.ring-caption { text-align:center; font-size:12px; color:var(--text-tertiary); margin-top:12px; }

.recent-card { background:#fff; border-radius:22px; padding:22px; box-shadow:var(--shadow-sm); }
.recent-hd { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; }
.recent-hd-title { font-size:14px; font-weight:700; }
.recent-item { display:flex; align-items:center; gap:10px; padding:9px 0; border-bottom:1px solid #f0f0f6; text-decoration:none; color:inherit; }
.recent-item:last-child { border-bottom:none; }
.recent-icon { width:36px; height:36px; border-radius:12px; background:var(--accent-tint); color:var(--accent); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.recent-name { font-size:12.5px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:var(--text-primary); }
.recent-meta { font-size:11px; color:var(--text-tertiary); }

.cta-block { display:flex; align-items:center; justify-content:center; gap:8px; background:var(--accent); color:#fff; font-size:13.5px; font-weight:700; padding:13px; border-radius:14px; text-decoration:none; }
.cta-block:hover { color:#fff; filter:brightness(1.08); }

.cand-strip { background:var(--accent-tint); border-radius:14px; padding:14px 18px; margin-bottom:22px; display:flex; align-items:center; gap:12px; }
.cand-strip-av { width:40px;height:40px;border-radius:50%;flex-shrink:0;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:800; }
.cand-strip-lbl { font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--accent); }
.cand-strip-name { font-size:14px;font-weight:700;margin-top:1px;color:var(--text-primary); }
.cand-strip-cargo { font-size:11px;color:var(--accent); }
.cand-strip-switch { margin-left:auto; background:#fff; color:var(--accent); font-size:11px; font-weight:700; padding:6px 12px; border-radius:20px; text-decoration:none; display:inline-flex; align-items:center; gap:6px; flex-shrink:0; }

.dash-empty { text-align:center; padding:40px 16px; color:var(--text-tertiary); background:#fff; border-radius:22px; box-shadow:var(--shadow-sm); margin-bottom:22px; }
.dash-empty i { font-size:36px; opacity:.3; margin-bottom:10px; display:block; }
</style>

<?php if (tieneCandidato()): ?>
<div class="cand-strip">
  <div class="cand-strip-av"><?php echo strtoupper(substr($_SESSION['candidato_nombre']??'?',0,1)); ?></div>
  <div style="flex:1;min-width:0;">
    <div class="cand-strip-lbl">Trabajando para</div>
    <div class="cand-strip-name"><?php echo htmlspecialchars($_SESSION['candidato_nombre']??''); ?></div>
    <div class="cand-strip-cargo">
      <?php echo ucfirst($_SESSION['candidato_cargo']??''); ?>
      <?php if(!empty($_SESSION['candidato_desc'])): ?> · <?php echo htmlspecialchars($_SESSION['candidato_desc']); ?><?php endif; ?>
    </div>
  </div>
  <a href="seleccionar_candidato.php" class="cand-strip-switch"><i class="fas fa-exchange-alt"></i> Cambiar</a>
</div>
<?php endif; ?>

<div class="dash-grid" style="display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:22px;align-items:start;">
  <div style="min-width:0;">

    <!-- Welcome + Goal -->
    <div class="welcome-row">
      <div class="welcome-card">
        <div>
          <div class="welcome-title">Bienvenido de vuelta, <?php echo htmlspecialchars($first_name); ?></div>
          <div class="welcome-copy">Seguimiento de los comités afectivos registrados en el sistema.</div>
          <div class="welcome-stat"><?php echo number_format($total_comites); ?> <small>comités registrados</small></div>
          <a href="comites.php" class="welcome-cta">Ver comités <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="welcome-illus">
          <?php if ($logo_b64): ?>
          <img src="data:image/png;base64,<?php echo $logo_b64; ?>" alt="Logo">
          <?php else: ?>
          <i class="fas fa-star"></i>
          <?php endif; ?>
        </div>
      </div>

      <div class="goal-card">
        <div>
          <div class="goal-pct"><?php echo $pct_mes; ?>%</div>
          <div class="goal-label">Meta del mes</div>
        </div>
        <div>
          <div class="goal-bar"><div class="goal-bar-fill" style="width:<?php echo $pct_mes; ?>%"></div></div>
          <div class="goal-sub"><?php echo $comites_mes_actual; ?> de <?php echo $meta_mes; ?> comités este mes</div>
        </div>
      </div>
    </div>

    <!-- Stat row -->
    <div class="stat-row">
      <div class="stat-mini">
        <div class="stat-mini-label">Miembros</div>
        <div class="stat-mini-top">
          <div class="stat-mini-val"><?php echo number_format($total_miembros); ?></div>
          <div class="stat-mini-bars"><?php echo barrasHtml($bars_miembros_actual); ?></div>
        </div>
        <div class="stat-mini-delta <?php echo $delta_miembros > 0 ? 'up' : ($delta_miembros < 0 ? 'down' : 'flat'); ?>">
          <?php echo $delta_miembros > 0 ? '↑ +' . $delta_miembros . '%' : ($delta_miembros < 0 ? '↓ ' . $delta_miembros . '%' : 'Sin cambios'); ?> vs semana anterior
        </div>
      </div>
      <div class="stat-mini">
        <div class="stat-mini-label">Coordinadores</div>
        <div class="stat-mini-top">
          <div class="stat-mini-val"><?php echo number_format($total_coordinadores); ?></div>
          <div class="stat-mini-bars"><?php echo barrasHtml($bars_coord_actual); ?></div>
        </div>
        <div class="stat-mini-delta flat">Registros de los últimos 7 días</div>
      </div>
    </div>

    <!-- Comités por mes -->
    <div class="chart-card">
      <div class="chart-hd">
        <div>
          <div class="chart-title">Comités por mes</div>
          <div class="chart-sub">Últimos <?php echo count($est_mes); ?> meses</div>
        </div>
        <span style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text-secondary);">
          <span style="width:8px;height:8px;border-radius:50%;background:var(--accent);"></span>Creados
        </span>
      </div>
      <?php if ($puntos_mes): ?>
      <svg viewBox="0 0 560 180" style="width:100%;height:180px;margin-top:8px;">
        <polyline points="<?php echo $puntos_mes; ?>" style="fill:none;stroke:var(--accent);stroke-width:3;stroke-linecap:round;stroke-linejoin:round"></polyline>
      </svg>
      <?php else: ?>
      <div style="text-align:center;padding:40px 0;color:var(--text-tertiary);font-size:13px;">Aún no hay suficientes datos históricos.</div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Right column -->
  <div class="right-col">

    <div class="ring-card">
      <div class="ring-title">Mis comités</div>
      <div class="ring-sub">Del total registrado en el sistema</div>
      <?php
      $pct_r = $total_comites > 0 ? min(100, round(($mis_comites/max($total_comites,1))*100)) : 0;
      $r = 62; $c = 2*M_PI*$r; $off = $c-(($pct_r/100)*$c);
      ?>
      <div class="ring-wrap">
        <svg width="150" height="150" viewBox="0 0 150 150">
          <circle cx="75" cy="75" r="<?php echo $r; ?>" fill="none" stroke="#eceafc" stroke-width="14"></circle>
          <circle cx="75" cy="75" r="<?php echo $r; ?>" fill="none" stroke="url(#ringGrad)" stroke-width="14" stroke-linecap="round"
            stroke-dasharray="<?php echo round($c,2); ?>" stroke-dashoffset="<?php echo round($off,2); ?>"
            transform="rotate(-90 75 75)"></circle>
          <defs>
            <linearGradient id="ringGrad" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0%" style="stop-color:var(--accent)"></stop>
              <stop offset="100%" style="stop-color:#22d3ee"></stop>
            </linearGradient>
          </defs>
        </svg>
        <div class="ring-num"><?php echo $pct_r; ?>%</div>
      </div>
      <div class="ring-caption">Mis comités: <?php echo $mis_comites; ?> de <?php echo $total_comites; ?> totales</div>
    </div>

    <div class="recent-card">
      <div class="recent-hd">
        <div class="recent-hd-title">Comités Recientes</div>
        <a href="comites.php" style="font-size:12px;font-weight:700;">Ver todos</a>
      </div>
      <?php if (count($comites_recientes) > 0): ?>
      <?php foreach ($comites_recientes as $c): ?>
      <a href="ver_comite.php?id=<?php echo $c['id']; ?>" class="recent-item">
        <div class="recent-icon"><i class="fas fa-layer-group" style="font-size:15px;"></i></div>
        <div style="min-width:0;flex:1;">
          <div class="recent-name"><?php echo htmlspecialchars($c['nombre']); ?></div>
          <div class="recent-meta"><?php echo htmlspecialchars($c['municipio']); ?></div>
        </div>
      </a>
      <?php endforeach; ?>
      <?php else: ?>
      <div style="text-align:center;padding:20px 0;color:var(--text-tertiary);font-size:12.5px;">No hay comités aún.</div>
      <?php endif; ?>
    </div>

    <a href="crear_comite.php" class="cta-block"><i class="fas fa-plus"></i>Crear Comité</a>

    <?php if (count($miembros_recientes) > 0): ?>
    <div class="recent-card">
      <div class="recent-hd">
        <div class="recent-hd-title">Miembros Recientes</div>
        <a href="consultar.php" style="font-size:12px;font-weight:700;">Consultar</a>
      </div>
      <?php foreach ($miembros_recientes as $m): ?>
      <div class="recent-item">
        <div class="recent-icon" style="background:linear-gradient(135deg,var(--accent),var(--accent-light));color:#fff;">
          <?php echo strtoupper(substr($m['nombre_completo'],0,1)); ?>
        </div>
        <div style="min-width:0;flex:1;">
          <div class="recent-name"><?php echo htmlspecialchars(mb_substr($m['nombre_completo'],0,22)); ?></div>
          <div class="recent-meta"><?php echo htmlspecialchars($m['cedula']); ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php include 'includes/footer.php'; ?>
