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
$est_municipio       = obtenerEstadisticasPorMunicipio(5);

$conn = conectarDB();
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM comites WHERE creado_por = ?");
$stmt->bind_param("i", $_SESSION['usuario_id']);
$stmt->execute();
$mis_comites = $stmt->get_result()->fetch_assoc()['total'];

$cfg     = cargarConfiguracion();
$logo_b64 = !empty($cfg['logo']) ? base64_encode($cfg['logo']) : '';
$first_name = explode(' ', trim($_SESSION['usuario_nombre'] ?? 'Usuario'))[0];

$titulo_pagina = 'Dashboard';
include 'includes/header.php';
?>
<style>
/* ─── PAGE LAYOUT ─────────────────────────────────── */
.dash-layout {
    display: grid;
    grid-template-columns: 1fr 296px;
    gap: 24px;
    align-items: start;
}
@media (max-width: 1200px) { .dash-layout { grid-template-columns: 1fr; } }
@media (max-width: 767px)  { .dash-layout { gap: 16px; } }

/* ─── TOPBAR SEARCH ───────────────────────────────── */
.topbar-search {
    flex: 1; max-width: 380px;
    display: flex; align-items: center; gap: 10px;
    background: #f5f6fa; border: 1px solid #eef0f6;
    border-radius: 12px; padding: 9px 16px;
}
.topbar-search input {
    border: none; outline: none; background: transparent;
    font-size: 13px; color: #374151; width: 100%; font-family: inherit;
}
.topbar-search i { color: #9ca3af; font-size: 14px; flex-shrink: 0; }

/* ─── HERO BANNER ─────────────────────────────────── */
.hero {
    background: linear-gradient(125deg, var(--accent) 0%, var(--accent-light) 100%);
    border-radius: 20px; padding: 28px 32px;
    display: flex; align-items: center; justify-content: space-between;
    gap: 20px; margin-bottom: 24px; overflow: hidden; position: relative;
}
.hero::before {
    content: ''; position: absolute;
    width: 220px; height: 220px; border-radius: 50%;
    background: rgba(255,255,255,.08);
    top: -60px; right: 60px; pointer-events: none;
}
.hero::after {
    content: ''; position: absolute;
    width: 140px; height: 140px; border-radius: 50%;
    background: rgba(255,255,255,.05);
    bottom: -40px; right: 20px; pointer-events: none;
}
.hero-tag {
    display: inline-block; margin-bottom: 10px;
    background: rgba(255,255,255,.22); color: #fff;
    font-size: 10px; font-weight: 700; letter-spacing: .08em;
    text-transform: uppercase; padding: 4px 12px; border-radius: 20px;
}
.hero-title {
    font-size: 24px; font-weight: 800; color: #fff;
    line-height: 1.2; margin-bottom: 18px;
}
.hero-cta {
    display: inline-flex; align-items: center; gap: 10px;
    background: #fff; color: var(--accent);
    font-size: 13px; font-weight: 700;
    padding: 10px 20px; border-radius: 30px;
    text-decoration: none; border: none; cursor: pointer;
    transition: transform .15s, box-shadow .15s;
    position: relative; z-index: 1;
}
.hero-cta:hover { transform: scale(1.04); box-shadow: 0 4px 16px rgba(0,0,0,.15); color: var(--accent); }
.hero-cta-arrow {
    width: 28px; height: 28px; border-radius: 50%;
    background: var(--accent); color: #fff;
    display: flex; align-items: center; justify-content: center; font-size: 11px;
}
.hero-illus {
    flex-shrink: 0; position: relative; z-index: 1;
    width: 100px; height: 100px; border-radius: 50%;
    background: rgba(255,255,255,.15);
    border: 4px solid rgba(255,255,255,.25);
    display: flex; align-items: center; justify-content: center;
    overflow: hidden;
}
.hero-illus img { width: 100%; height: 100%; object-fit: contain; padding: 8px; }
.hero-illus-icon { font-size: 40px; color: rgba(255,255,255,.8); }

/* ─── PROGRESS CARDS (Coursue style) ─────────────── */
.progress-cards { display: grid; grid-template-columns: repeat(3,1fr); gap: 14px; margin-bottom: 24px; }
@media (max-width: 600px) { .progress-cards { grid-template-columns: repeat(2,1fr); } }

.prog-card {
    background: #fff; border: 1px solid #eef0f6; border-radius: 16px;
    padding: 18px 20px; display: flex; align-items: center;
    gap: 14px; cursor: pointer; text-decoration: none; color: inherit;
    transition: box-shadow .2s, transform .2s;
}
.prog-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,.07); transform: translateY(-2px); color: inherit; }
.prog-icon {
    width: 48px; height: 48px; border-radius: 14px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: 20px;
    background: color-mix(in srgb, var(--accent) 10%, white);
    color: var(--accent);
}
.prog-sub  { font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 2px; }
.prog-val  { font-size: 26px; font-weight: 800; color: #111827; line-height: 1; }
.prog-label { font-size: 11px; color: #9ca3af; margin-top: 2px; }
.prog-bar  { height: 4px; background: #f1f3f6; border-radius: 2px; margin-top: 10px; }
.prog-bar-fill { height: 100%; border-radius: 2px; background: var(--accent); transition: width .6s ease; }

/* ─── SECTION HEADER ──────────────────────────────── */
.section-hd {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 16px;
}
.section-hd h2 { font-size: 15px; font-weight: 700; color: #111827; margin: 0; }
.section-hd-actions { display: flex; align-items: center; gap: 8px; }
.see-all { font-size: 12px; color: var(--accent); text-decoration: none; font-weight: 600; }
.nav-arrows { display: flex; gap: 6px; }
.nav-arrow {
    width: 30px; height: 30px; border-radius: 50%;
    border: 1px solid #eef0f6; background: #fff; color: #374151;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; cursor: pointer; transition: all .15s;
}
.nav-arrow:hover { border-color: var(--accent); color: var(--accent); }
.nav-arrow.filled { background: var(--accent); border-color: var(--accent); color: #fff; }

/* ─── COMITÉ CARDS (Continue Watching style) ─────── */
.comite-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 24px; }
@media (max-width: 900px) { .comite-cards { grid-template-columns: repeat(2,1fr); } }
@media (max-width: 500px) { .comite-cards { grid-template-columns: 1fr; } }

.comite-card {
    background: #fff; border: 1px solid #eef0f6; border-radius: 16px;
    overflow: hidden; transition: box-shadow .2s, transform .2s;
}
.comite-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,.08); transform: translateY(-2px); }
.comite-card-thumb {
    height: 110px; background: linear-gradient(135deg, color-mix(in srgb,var(--accent) 15%,white), color-mix(in srgb,var(--accent) 5%,white));
    display: flex; align-items: center; justify-content: center;
    position: relative;
}
.comite-card-cat {
    position: absolute; top: 10px; left: 10px;
    background: color-mix(in srgb,var(--accent) 15%,white);
    color: var(--accent); font-size: 9px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .06em;
    padding: 3px 8px; border-radius: 6px;
}
.comite-card-thumb-icon { font-size: 32px; color: var(--accent); opacity: .35; }
.comite-card-body { padding: 14px 16px; }
.comite-card-name { font-size: 13px; font-weight: 700; color: #111827; margin-bottom: 6px; line-height: 1.3; }
.comite-card-meta { font-size: 11px; color: #9ca3af; display: flex; align-items: center; gap: 8px; }
.comite-card-footer {
    padding: 10px 16px;
    border-top: 1px solid #f5f6fa;
    display: flex; align-items: center; justify-content: space-between;
}
.comite-card-count { font-size: 11px; color: #9ca3af; }
.comite-card-actions { display: flex; gap: 6px; }
.card-btn {
    width: 28px; height: 28px; border-radius: 8px;
    border: 1px solid #eef0f6; background: #fff; color: #9ca3af;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; text-decoration: none; cursor: pointer;
    transition: all .15s;
}
.card-btn:hover { border-color: var(--accent); color: var(--accent); background: color-mix(in srgb,var(--accent) 8%,white); }

/* ─── YOUR LESSON TABLE ───────────────────────────── */
.lesson-table { width: 100%; border-collapse: collapse; }
.lesson-table thead th {
    font-size: 10px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .06em; color: #9ca3af;
    padding: 10px 16px; background: #fafafa;
    border-bottom: 1px solid #eef0f6; text-align: left;
}
.lesson-table td {
    padding: 12px 16px; border-bottom: 1px solid #f5f6fa;
    font-size: 13px; color: #374151; vertical-align: middle;
}
.lesson-table tbody tr:last-child td { border-bottom: none; }
.lesson-table tbody tr:hover td { background: #fafafa; }
.lesson-av {
    width: 34px; height: 34px; border-radius: 50%;
    background: linear-gradient(135deg, var(--accent), var(--accent-light));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700; flex-shrink: 0;
}
.lesson-tag {
    display: inline-flex; align-items: center; gap: 4px;
    background: color-mix(in srgb,var(--accent) 10%,white); color: var(--accent);
    font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 6px;
}
.lesson-action {
    width: 28px; height: 28px; border-radius: 50%;
    border: 1px solid #eef0f6; background: #fff; color: var(--accent);
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 11px; cursor: pointer; transition: all .15s;
}
.lesson-action:hover { background: var(--accent); border-color: var(--accent); color: #fff; }

/* ─── RIGHT PANEL ─────────────────────────────────── */
.right-col { display: flex; flex-direction: column; gap: 16px; }

/* Statistic card */
.stat-card-r {
    background: #fff; border: 1px solid #eef0f6; border-radius: 20px; padding: 22px;
}
.stat-card-r-hd {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 18px;
}
.stat-card-r-title { font-size: 14px; font-weight: 700; color: #111827; }
.stat-card-r-dots { color: #9ca3af; cursor: pointer; font-size: 18px; }

/* Ring */
.ring-wrap {
    position: relative; width: 100px; height: 100px; margin: 0 auto 14px;
}
.ring-wrap svg { display: block; }
.ring-bg  { fill: none; stroke: #f1f3f6; stroke-width: 7; }
.ring-fg  { fill: none; stroke: var(--accent); stroke-width: 7; stroke-linecap: round;
            transition: stroke-dashoffset .7s cubic-bezier(.4,0,.2,1); }
.ring-inner {
    position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
}
.ring-av {
    width: 68px; height: 68px; border-radius: 50%;
    background: linear-gradient(135deg, var(--accent), var(--accent-light));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 24px; font-weight: 800;
    border: 3px solid #fff;
}
.ring-pct {
    position: absolute; top: 2px; right: 2px;
    background: var(--accent); color: #fff;
    font-size: 9px; font-weight: 700; padding: 2px 5px; border-radius: 8px;
}
.greeting-name { font-size: 15px; font-weight: 700; color: #111827; text-align: center; margin-bottom: 3px; }
.greeting-sub  { font-size: 12px; color: #9ca3af; text-align: center; }

/* Mini bar chart */
.mini-chart { margin-top: 16px; }
.mini-chart-label { font-size: 11px; font-weight: 600; color: #374151; margin-bottom: 10px; }
.bars { display: flex; align-items: flex-end; gap: 6px; height: 72px; }
.bar-col { display: flex; flex-direction: column; align-items: center; gap: 4px; flex: 1; }
.bar-body {
    width: 100%; border-radius: 6px 6px 0 0;
    background: color-mix(in srgb,var(--accent) 15%,white);
    min-height: 4px; transition: height .5s ease;
}
.bar-body.hi { background: var(--accent); }
.bar-lbl { font-size: 9px; color: #9ca3af; text-align: center; white-space: nowrap; overflow: hidden; }

/* Mentor / members list */
.mentor-card {
    background: #fff; border: 1px solid #eef0f6; border-radius: 20px; padding: 22px;
}
.mentor-item {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 0; border-bottom: 1px solid #f5f6fa;
}
.mentor-item:last-child { border-bottom: none; }
.mentor-av {
    width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
    background: linear-gradient(135deg, var(--accent), var(--accent-light));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 700; position: relative;
}
.mentor-online {
    position: absolute; bottom: 1px; right: 1px;
    width: 9px; height: 9px; border-radius: 50%;
    background: #10b981; border: 2px solid #fff;
}
.mentor-name  { font-size: 13px; font-weight: 600; color: #111827; }
.mentor-role  { font-size: 11px; color: #9ca3af; }
.follow-btn {
    margin-left: auto; flex-shrink: 0;
    background: color-mix(in srgb,var(--accent) 10%,white);
    color: var(--accent); font-size: 11px; font-weight: 700;
    padding: 5px 12px; border-radius: 20px;
    border: none; cursor: pointer; text-decoration: none;
    transition: all .15s; display: flex; align-items: center; gap: 4px;
}
.follow-btn:hover { background: var(--accent); color: #fff; }

/* Candidate context strip */
.cand-strip {
    background: color-mix(in srgb,var(--accent) 8%,white);
    border: 1px solid color-mix(in srgb,var(--accent) 20%,white);
    border-radius: 14px; padding: 14px 18px; margin-bottom: 24px;
    display: flex; align-items: center; gap: 12px;
}
.cand-strip-av {
    width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0;
    background: var(--accent); color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; font-weight: 800;
}
.cand-strip-lbl { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--accent); }
.cand-strip-name { font-size: 14px; font-weight: 700; color: #111827; margin-top: 1px; }
.cand-strip-cargo { font-size: 11px; color: var(--accent); }

/* Empty */
.dash-empty { text-align: center; padding: 32px 16px; }
.dash-empty i { font-size: 36px; color: #e5e7eb; margin-bottom: 10px; display: block; }
.dash-empty p { font-size: 13px; color: #9ca3af; margin: 0; }
</style>

<div class="dash-layout">

  <!-- ═══════════════ LEFT / CENTER ═══════════════ -->
  <div>

    <!-- Candidate strip (digitadores) -->
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
      <a href="seleccionar_candidato.php" class="follow-btn"><i class="fas fa-exchange-alt"></i> Cambiar</a>
    </div>
    <?php endif; ?>

    <!-- Hero -->
    <div class="hero">
      <div style="position:relative;z-index:1;">
        <div class="hero-tag">Comités Afectivos 2028</div>
        <div class="hero-title">Bienvenido de vuelta,<br><?php echo htmlspecialchars($first_name); ?> 👋</div>
        <a href="crear_comite.php" class="hero-cta">
          Crear Comité
          <span class="hero-cta-arrow"><i class="fas fa-arrow-right"></i></span>
        </a>
      </div>
      <div class="hero-illus">
        <?php if ($logo_b64): ?>
        <img src="data:image/png;base64,<?php echo $logo_b64; ?>" alt="Logo">
        <?php else: ?>
        <div class="hero-illus-icon"><i class="fas fa-star"></i></div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Progress cards -->
    <div class="progress-cards">
      <?php
      $cards = [
        ['icon'=>'fa-layer-group', 'sub'=>'Total', 'val'=>$total_comites, 'label'=>'Comités registrados', 'max'=>max($total_comites,1), 'link'=>'comites.php'],
        ['icon'=>'fa-users',       'sub'=>'Total', 'val'=>$total_miembros, 'label'=>'Miembros registrados', 'max'=>max($total_miembros,1), 'link'=>'#'],
        ['icon'=>'fa-user-tie',    'sub'=>'Total', 'val'=>$total_coordinadores, 'label'=>'Coordinadores', 'max'=>max($total_coordinadores,1), 'link'=>'#'],
      ];
      foreach ($cards as $card):
        $pct = $card['val'] > 0 ? min(100, round(($card['val']/$card['max'])*100)) : 0;
      ?>
      <a href="<?php echo $card['link']; ?>" class="prog-card" style="text-decoration:none;">
        <div style="flex:1;min-width:0;">
          <div class="prog-sub"><?php echo $card['sub']; ?></div>
          <div class="prog-val"><?php echo number_format($card['val']); ?></div>
          <div class="prog-label"><?php echo $card['label']; ?></div>
          <div class="prog-bar"><div class="prog-bar-fill" style="width:<?php echo $pct; ?>%"></div></div>
        </div>
        <div class="prog-icon"><i class="fas <?php echo $card['icon']; ?>"></i></div>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- Recent comités cards -->
    <div class="section-hd">
      <h2>Comités Recientes</h2>
      <div class="section-hd-actions">
        <a href="comites.php" class="see-all">Ver todos</a>
        <div class="nav-arrows">
          <button class="nav-arrow"><i class="fas fa-chevron-left"></i></button>
          <button class="nav-arrow filled"><i class="fas fa-chevron-right"></i></button>
        </div>
      </div>
    </div>

    <?php if (count($comites_recientes) > 0): ?>
    <div class="comite-cards">
      <?php foreach (array_slice($comites_recientes,0,6) as $c):
        $s2 = $conn->prepare("SELECT c.nombre_completo FROM coordinadores c JOIN comite_coordinador cc ON c.id=cc.coordinador_id WHERE cc.comite_id=? LIMIT 1");
        $s2->bind_param("i",$c['id']); $s2->execute();
        $coord = $s2->get_result()->fetch_assoc();
        $s3 = $conn->prepare("SELECT COUNT(*) as t FROM comite_miembro WHERE comite_id=?");
        $s3->bind_param("i",$c['id']); $s3->execute();
        $cnt = $s3->get_result()->fetch_assoc()['t'];
      ?>
      <div class="comite-card">
        <div class="comite-card-thumb">
          <span class="comite-card-cat"><?php echo htmlspecialchars($c['municipio']); ?></span>
          <i class="fas fa-layer-group comite-card-thumb-icon"></i>
        </div>
        <div class="comite-card-body">
          <div class="comite-card-name"><?php echo htmlspecialchars($c['nombre']); ?></div>
          <div class="comite-card-meta">
            <span><i class="fas fa-user-tie me-1"></i><?php echo $coord ? htmlspecialchars(explode(' ',$coord['nombre_completo'])[0]) : 'Sin coordinador'; ?></span>
            <span>·</span>
            <span><?php echo date('d/m/Y',strtotime($c['fecha_creacion'])); ?></span>
          </div>
        </div>
        <div class="comite-card-footer">
          <span class="comite-card-count"><i class="fas fa-users me-1"></i><?php echo $cnt; ?> miembros</span>
          <div class="comite-card-actions">
            <a href="ver_comite.php?id=<?php echo $c['id']; ?>" class="card-btn"><i class="fas fa-eye"></i></a>
            <a href="editar_comite.php?id=<?php echo $c['id']; ?>" class="card-btn"><i class="fas fa-pen"></i></a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="dash-empty" style="background:#fff;border:1px solid #eef0f6;border-radius:16px;margin-bottom:24px;">
      <i class="fas fa-folder-open"></i>
      <p>No hay comités aún</p>
      <a href="crear_comite.php" class="hero-cta d-inline-flex mt-3" style="font-size:12px;">
        <i class="fas fa-plus me-2"></i>Crear primero
        <span class="hero-cta-arrow"><i class="fas fa-arrow-right"></i></span>
      </a>
    </div>
    <?php endif; ?>

    <!-- Your Lesson → Mis Comités table -->
    <div class="section-hd">
      <h2>Mis Comités</h2>
      <a href="comites.php" class="see-all">Ver todos</a>
    </div>
    <div class="card" style="overflow:hidden;">
      <?php
      $stmt2 = $conn->prepare("SELECT c.*, cand.nombre as cand_nombre, cand.cargo as cand_cargo
                               FROM comites c LEFT JOIN candidatos cand ON c.candidato_id=cand.id
                               WHERE c.creado_por=? ORDER BY c.fecha_creacion DESC LIMIT 5");
      $stmt2->bind_param("i",$_SESSION['usuario_id']); $stmt2->execute();
      $mis = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
      ?>
      <?php if (count($mis)>0): ?>
      <table class="lesson-table">
        <thead>
          <tr>
            <th>Comité</th>
            <th>Cargo</th>
            <th>Municipio</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($mis as $m): ?>
        <tr>
          <td>
            <div class="d-flex align-items-center gap-2">
              <div class="lesson-av"><?php echo strtoupper(substr($m['nombre'],0,1)); ?></div>
              <div>
                <div style="font-weight:600;font-size:13px;"><?php echo htmlspecialchars($m['nombre']); ?></div>
                <div style="font-size:11px;color:#9ca3af;"><?php echo date('d/m/Y',strtotime($m['fecha_creacion'])); ?></div>
              </div>
            </div>
          </td>
          <td>
            <?php if (!empty($m['cand_cargo'])): ?>
            <span class="lesson-tag">
              <i class="fas fa-user-tie" style="font-size:9px;"></i>
              <?php echo ucfirst($m['cand_cargo']); ?>
            </span>
            <?php else: ?>
            <span style="color:#d1d5db;font-size:12px;">—</span>
            <?php endif; ?>
          </td>
          <td style="font-size:12px;color:#6b7280;"><?php echo htmlspecialchars($m['municipio']); ?></td>
          <td>
            <a href="ver_comite.php?id=<?php echo $m['id']; ?>" class="lesson-action">
              <i class="fas fa-arrow-right"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <div class="dash-empty"><i class="fas fa-inbox"></i><p>Aún no has creado comités.</p></div>
      <?php endif; ?>
    </div>

  </div>

  <!-- ═══════════════ RIGHT PANEL ═══════════════ -->
  <div class="right-col">

    <!-- Statistic card -->
    <div class="stat-card-r">
      <div class="stat-card-r-hd">
        <div class="stat-card-r-title">Estadística</div>
        <div class="stat-card-r-dots">⋯</div>
      </div>

      <!-- Ring -->
      <?php
      $pct_r = $total_comites > 0 ? min(100, round(($mis_comites/max($total_comites,1))*100)) : 0;
      $r=44; $c=2*M_PI*$r; $off=$c-(($pct_r/100)*$c);
      ?>
      <div class="ring-wrap">
        <svg width="100" height="100" viewBox="0 0 100 100">
          <circle class="ring-bg" cx="50" cy="50" r="<?php echo $r; ?>" transform="rotate(-90 50 50)"/>
          <circle class="ring-fg" cx="50" cy="50" r="<?php echo $r; ?>"
            stroke-dasharray="<?php echo round($c,2); ?>"
            stroke-dashoffset="<?php echo round($off,2); ?>"
            transform="rotate(-90 50 50)"/>
        </svg>
        <div class="ring-inner">
          <div class="ring-av"><?php echo strtoupper(substr($first_name,0,1)); ?></div>
        </div>
        <span class="ring-pct"><?php echo $pct_r; ?>%</span>
      </div>

      <div class="greeting-name">Buenos días, <?php echo htmlspecialchars($first_name); ?> 🔥</div>
      <div class="greeting-sub">Mis comités: <?php echo $mis_comites; ?> de <?php echo $total_comites; ?> totales</div>

      <!-- Mini bar chart -->
      <?php if (count($est_municipio)>0): ?>
      <div class="mini-chart">
        <div class="mini-chart-label">Por municipio</div>
        <?php $max_b = max(array_column($est_municipio,'total')?:[$total_comites?:1]); ?>
        <div class="bars">
          <?php foreach ($est_municipio as $i=>$e):
            $h = max(4, round(($e['total']/$max_b)*60));
            $hi = ($i===count($est_municipio)-1) ? 'hi' : '';
          ?>
          <div class="bar-col">
            <div class="bar-body <?php echo $hi; ?>" style="height:<?php echo $h; ?>px;"></div>
            <div class="bar-lbl"><?php echo mb_substr($e['municipio'],0,6); ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Recent members (Your Mentor style) -->
    <?php if (count($miembros_recientes)>0): ?>
    <div class="mentor-card">
      <div class="stat-card-r-hd">
        <div class="stat-card-r-title">Miembros Recientes</div>
        <a href="#" style="width:26px;height:26px;border-radius:50%;background:color-mix(in srgb,var(--accent) 10%,white);color:var(--accent);display:flex;align-items:center;justify-content:center;font-size:13px;text-decoration:none;">+</a>
      </div>
      <?php foreach ($miembros_recientes as $m): ?>
      <div class="mentor-item">
        <div class="mentor-av">
          <?php echo strtoupper(substr($m['nombre_completo'],0,1)); ?>
          <span class="mentor-online"></span>
        </div>
        <div style="min-width:0;">
          <div class="mentor-name" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars(mb_substr($m['nombre_completo'],0,20)); ?></div>
          <div class="mentor-role"><?php echo htmlspecialchars($m['cedula']); ?></div>
        </div>
        <a href="consultar.html" class="follow-btn">
          <i class="fas fa-id-card" style="font-size:10px;"></i> Ver
        </a>
      </div>
      <?php endforeach; ?>
      <a href="#" style="display:block;text-align:center;margin-top:14px;font-size:12px;font-weight:600;color:var(--accent);text-decoration:none;padding:10px;background:#fafafa;border-radius:10px;">Ver Todos</a>
    </div>
    <?php endif; ?>

    <!-- Quick actions -->
    <div class="stat-card-r" style="padding:18px;">
      <div class="d-grid gap-2">
        <a href="crear_comite.php" class="btn btn-primary btn-sm" style="border-radius:10px;">
          <i class="fas fa-plus me-2"></i>Crear Comité
        </a>
        <a href="consultar.html" class="btn btn-sm" style="background:#f5f6fa;color:#374151;border:1px solid #eef0f6;border-radius:10px;">
          <i class="fas fa-id-card me-2"></i>Consultar Cédula
        </a>
        <?php if (esAdmin()): ?>
        <a href="config.php" class="btn btn-sm" style="background:#f5f6fa;color:#374151;border:1px solid #eef0f6;border-radius:10px;">
          <i class="fas fa-cog me-2"></i>Configuración
        </a>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<?php include 'includes/footer.php'; ?>
