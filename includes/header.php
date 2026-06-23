<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/auth.php';
$pagina_actual = basename($_SERVER['PHP_SELF']);

// Cargar configuración del sistema
$_cfg = cargarConfiguracion();
$_color_primary = $_cfg['color_primario'] ?? '#2563eb';
$_color_sidebar = $_cfg['color_sidebar']  ?? '#0d1b2a';
$_color_accent  = $_cfg['color_accent']   ?? '#3b82f6';
$_sistema_nombre = $_cfg['nombre_partido'] ?? 'Sistema';
$_sistema_siglas = $_cfg['siglas'] ?? '';
$_logo_b64 = !empty($_cfg['logo']) ? base64_encode($_cfg['logo']) : '';

function _lightenHex($hex, $factor = 1.5) {
    $hex = ltrim($hex,'#');
    $r = min(255, (int)(hexdec(substr($hex,0,2)) * $factor));
    $g = min(255, (int)(hexdec(substr($hex,2,2)) * $factor));
    $b = min(255, (int)(hexdec(substr($hex,4,2)) * $factor));
    return sprintf('#%02x%02x%02x',$r,$g,$b);
}
$_color_sidebar_hover = _lightenHex($_color_sidebar, 1.5);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($titulo_pagina) ? htmlspecialchars($titulo_pagina).' — '.$_sistema_siglas : $_sistema_nombre; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-bg:     <?php echo $_color_sidebar; ?>;
            --sidebar-hover:  <?php echo $_color_sidebar_hover; ?>;
            --sidebar-active: <?php echo $_color_primary; ?>;
            --sidebar-border: rgba(255,255,255,0.06);
            --sidebar-text:   rgba(255,255,255,0.65);
            --sidebar-width:  260px;
            --accent:         <?php echo $_color_primary; ?>;
            --accent-light:   <?php echo $_color_accent; ?>;
            --success:        #10b981;
            --warning:        #f59e0b;
            --danger:         #ef4444;
            --info:           #06b6d4;
            --bg:             #f1f5f9;
            --surface:        #ffffff;
            --text-primary:   #0f172a;
            --text-secondary: #64748b;
            --border:         #e2e8f0;
            --shadow-sm:      0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.04);
            --shadow-md:      0 4px 6px rgba(0,0,0,0.07), 0 2px 4px rgba(0,0,0,0.06);
            --shadow-lg:      0 10px 15px rgba(0,0,0,0.08), 0 4px 6px rgba(0,0,0,0.05);
            --radius:         12px;
            --radius-sm:      8px;
            --topbar-h:       60px;
            --fab-size:       56px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: var(--bg);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            font-size: 14px;
            line-height: 1.5;
        }

        /* ── SIDEBAR (desktop) ───────────────────────────── */
        .sidebar {
            position: fixed;
            top: 0; left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--sidebar-bg);
            display: flex;
            flex-direction: column;
            z-index: 1040;
            overflow: hidden;
            transition: transform 0.28s cubic-bezier(.4,0,.2,1),
                        box-shadow 0.28s;
        }

        .sidebar-brand {
            padding: 22px 20px 18px;
            border-bottom: 1px solid var(--sidebar-border);
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }
        .sidebar-brand img {
            width: 42px; height: 42px;
            object-fit: contain;
            border-radius: 8px;
            background: rgba(255,255,255,0.08);
            padding: 4px;
        }
        .sidebar-brand-name {
            font-size: 11px; font-weight: 700; color: #fff;
            text-transform: uppercase; letter-spacing: .05em; line-height: 1.3;
        }
        .sidebar-brand-sub { font-size: 10px; color: var(--sidebar-text); margin-top: 2px; }

        .sidebar-nav {
            flex: 1; overflow-y: auto; padding: 14px 10px;
            scrollbar-width: none;
        }
        .sidebar-nav::-webkit-scrollbar { display: none; }

        .nav-section-label {
            font-size: 10px; font-weight: 600;
            text-transform: uppercase; letter-spacing: .08em;
            color: rgba(255,255,255,0.28);
            padding: 10px 8px 5px;
        }

        .nav-item { margin-bottom: 2px; }

        .nav-link {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 12px;
            border-radius: 8px;
            color: var(--sidebar-text);
            text-decoration: none;
            font-size: 13.5px; font-weight: 500;
            transition: all .15s;
        }
        .nav-link:hover { background: var(--sidebar-hover); color: #fff; }
        .nav-link.active {
            background: var(--sidebar-active); color: #fff;
            box-shadow: 0 2px 8px rgba(37,99,235,.35);
        }
        .nav-icon {
            width: 30px; height: 30px; border-radius: 7px;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; flex-shrink: 0;
            background: rgba(255,255,255,0.06);
            transition: background .15s;
        }
        .nav-link.active .nav-icon { background: rgba(255,255,255,0.15); }
        .nav-link:hover .nav-icon   { background: rgba(255,255,255,0.10); }

        .sidebar-footer {
            padding: 14px 10px;
            border-top: 1px solid var(--sidebar-border);
            flex-shrink: 0;
        }
        .sidebar-user {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 10px; border-radius: 8px;
            background: rgba(255,255,255,0.05);
        }
        .sidebar-avatar {
            width: 32px; height: 32px; border-radius: 50%;
            background: var(--accent); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 12px; flex-shrink: 0;
        }
        .sidebar-user-info { flex: 1; min-width: 0; }
        .sidebar-user-name {
            font-size: 13px; font-weight: 600; color: #fff;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .sidebar-user-role { font-size: 11px; color: var(--sidebar-text); }
        .sidebar-logout {
            color: var(--sidebar-text); text-decoration: none;
            padding: 6px; border-radius: 6px; transition: all .15s; font-size: 14px;
        }
        .sidebar-logout:hover { color: var(--danger); background: rgba(239,68,68,.1); }

        /* ── TOPBAR ──────────────────────────────────────── */
        .topbar {
            position: sticky; top: 0; z-index: 100;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 0 20px;
            height: var(--topbar-h);
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: var(--shadow-sm);
        }
        .topbar-left { display: flex; align-items: center; gap: 10px; }
        .page-title {
            font-size: 16px; font-weight: 600;
            color: var(--text-primary); margin: 0;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            max-width: 200px;
        }
        .topbar-right { display: flex; align-items: center; gap: 8px; }
        .topbar-user {
            display: flex; align-items: center; gap: 8px;
            padding: 4px 10px 4px 4px; border-radius: 10px;
            cursor: pointer; transition: background .15s;
        }
        .topbar-user:hover { background: var(--bg); }
        .topbar-avatar {
            width: 32px; height: 32px; border-radius: 50%;
            background: var(--accent); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 12px; flex-shrink: 0;
        }
        .topbar-user-name { font-size: 13px; font-weight: 600; color: var(--text-primary); }

        /* ── MAIN LAYOUT ─────────────────────────────────── */
        .main-wrapper {
            margin-left: var(--sidebar-width);
            flex: 1; display: flex; flex-direction: column;
            min-height: 100vh; min-width: 0;
            transition: margin-left .28s cubic-bezier(.4,0,.2,1);
        }
        .page-content { padding: 20px; flex: 1; background: #f5f6fa; }

        /* ── SHARED PAGE STYLES ──────────────────────────── */
        .card {
            background: #fff;
            border: 1px solid #eef0f6;
            border-radius: 16px;
            box-shadow: none;
        }
        .card-header {
            background: transparent;
            border-bottom: 1px solid #eef0f6;
            padding: 16px 20px;
            font-weight: 700; font-size: 14px;
            color: #111827;
            border-radius: 16px 16px 0 0;
        }
        .page-back {
            display: inline-flex; align-items: center; gap: 8px;
            color: var(--text-secondary); font-size: 13px;
            text-decoration: none; margin-bottom: 20px;
            transition: color .15s;
        }
        .page-back:hover { color: var(--accent); }
        .table { font-size: 13px; }
        .table thead th {
            font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .05em;
            color: #9ca3af; border-bottom: 1px solid #eef0f6;
            padding: 10px 16px; background: #fafafa;
        }
        .table td {
            padding: 12px 16px; vertical-align: middle;
            border-bottom: 1px solid #f5f6fa; color: #374151;
        }
        .table tbody tr:last-child td { border-bottom: none; }
        .table tbody tr:hover td { background: #fafafa; }
        .btn { border-radius: 10px; font-size: 13px; font-weight: 500; }
        .btn-primary { background: var(--accent); border-color: var(--accent); }
        .btn-primary:hover { filter: brightness(1.08); border-color: transparent; }
        .form-control, .form-select {
            border-radius: 10px; font-size: 13px;
            border: 1px solid #eef0f6; background: #fafafa;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 15%, transparent);
            background: #fff;
        }
        .badge { font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 20px; }
        .alert { border-radius: 12px; border: none; font-size: 13px; }
        .modal-content { border-radius: 16px; border: 1px solid #eef0f6; }
        .modal-header { border-bottom: 1px solid #eef0f6; padding: 16px 20px; }
        .modal-footer { border-top: 1px solid #eef0f6; }
        .action-link {
            width: 30px; height: 30px; border-radius: 8px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 12px; border: 1px solid #eef0f6;
            color: #9ca3af; background: none; cursor: pointer;
            text-decoration: none; transition: all .15s;
        }
        .action-link:hover { border-color: var(--accent); color: var(--accent); background: color-mix(in srgb,var(--accent) 8%,white); }
        .action-link.danger:hover { border-color: #ef4444; color: #ef4444; background: #fef2f2; }
        .empty-state { text-align: center; padding: 48px 20px; color: #d1d5db; }
        .empty-state i { font-size: 40px; margin-bottom: 12px; display: block; }
        .empty-state p { font-size: 13px; margin: 0; color: #9ca3af; }

        /* ── OVERLAY ─────────────────────────────────────── */
        .sidebar-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.55);
            z-index: 1039;
            backdrop-filter: blur(2px);
            -webkit-backdrop-filter: blur(2px);
        }
        .sidebar-overlay.show { display: block; }

        /* ── CARDS ───────────────────────────────────────── */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
        }
        .card-header {
            background: transparent;
            border-bottom: 1px solid var(--border);
            padding: 14px 18px;
            font-weight: 600; font-size: 14px;
            color: var(--text-primary);
            display: flex; align-items: center; justify-content: space-between;
        }

        /* ── TABLES ──────────────────────────────────────── */
        .table { font-size: 13px; margin: 0; }
        .table thead th {
            font-size: 11px; font-weight: 600;
            text-transform: uppercase; letter-spacing: .05em;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border);
            padding: 10px 16px; background: #f8fafc;
        }
        .table td {
            padding: 11px 16px; vertical-align: middle;
            border-bottom: 1px solid #f1f5f9; color: var(--text-primary);
        }
        .table tbody tr:last-child td { border-bottom: none; }
        .table tbody tr:hover td { background: #f8fafc; }

        /* ── BUTTONS ─────────────────────────────────────── */
        .btn { font-size: 13px; font-weight: 500; border-radius: var(--radius-sm); }
        .btn-primary { background: var(--accent); border-color: var(--accent); }
        .btn-primary:hover { background: #1d4ed8; border-color: #1d4ed8; }

        /* ── FORMS ───────────────────────────────────────── */
        .form-control, .form-select {
            border-radius: var(--radius-sm); font-size: 13px;
            border: 1px solid var(--border);
            transition: border-color .15s, box-shadow .15s;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(37,99,235,.12);
        }
        .form-label { font-size: 13px; font-weight: 500; color: var(--text-primary); margin-bottom: 6px; }
        .form-text  { font-size: 11px; color: var(--text-secondary); }

        /* ── BADGE ───────────────────────────────────────── */
        .badge { font-size: 11px; font-weight: 600; padding: 3px 9px; border-radius: 20px; }

        /* ════════════════════════════════════════════════════
           MOBILE PILL NAV BAR
           ════════════════════════════════════════════════════ */
        .mob-nav { display: none; }

        .mob-pill {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1047;
            background: #1c1c1e;
            border-radius: 40px;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            gap: 4px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.45), 0 2px 8px rgba(0,0,0,0.3);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.08);
            white-space: nowrap;
        }

        .mob-pill-item {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            color: rgba(255,255,255,0.55);
            text-decoration: none;
            font-size: 19px;
            transition: all .18s cubic-bezier(.4,0,.2,1);
            flex-shrink: 0;
        }
        .mob-pill-item:hover { color: rgba(255,255,255,.85); }
        .mob-pill-item.active {
            background: var(--accent);
            color: #fff;
            box-shadow: 0 2px 12px color-mix(in srgb, var(--accent) 50%, transparent);
        }
        .mob-pill-item .mob-dot {
            position: absolute;
            top: 7px; right: 7px;
            width: 7px; height: 7px;
            border-radius: 50%;
            background: #ef4444;
            border: 1.5px solid #1c1c1e;
        }
        .mob-pill-divider {
            width: 1px; height: 24px;
            background: rgba(255,255,255,0.1);
            margin: 0 4px;
            flex-shrink: 0;
        }

        /* More sheet */
        .mob-backdrop {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1048;
            backdrop-filter: blur(3px);
            -webkit-backdrop-filter: blur(3px);
        }
        .mob-backdrop.show { display: block; }

        .mob-sheet {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            background: #1c1c1e;
            border-radius: 20px 20px 0 0;
            z-index: 1049;
            padding-bottom: env(safe-area-inset-bottom, 16px);
            transform: translateY(100%);
            transition: transform .28s cubic-bezier(.4,0,.2,1);
            box-shadow: 0 -4px 24px rgba(0,0,0,.4);
        }
        .mob-sheet.show { transform: translateY(0); }

        .mob-sheet-handle {
            width: 36px; height: 4px;
            background: rgba(255,255,255,.2);
            border-radius: 2px;
            margin: 12px auto 0;
        }
        .mob-sheet-user {
            display: flex; align-items: center; gap: 12px;
            padding: 16px 20px 14px;
            border-bottom: 1px solid rgba(255,255,255,.07);
        }
        .mob-sheet-avatar {
            width: 40px; height: 40px; border-radius: 50%;
            background: var(--accent); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 15px; flex-shrink: 0;
        }
        .mob-sheet-name { font-size: 14px; font-weight: 600; color: #fff; }
        .mob-sheet-role { font-size: 11px; color: rgba(255,255,255,.4); }

        .mob-sheet-links { padding: 10px 12px; }
        .mob-sheet-link {
            display: flex; align-items: center; gap: 14px;
            padding: 13px 12px;
            border-radius: 12px;
            color: rgba(255,255,255,.7);
            text-decoration: none;
            font-size: 14px; font-weight: 500;
            transition: all .15s;
            margin-bottom: 2px;
        }
        .mob-sheet-link:hover { background: rgba(255,255,255,.07); color: #fff; }
        .mob-sheet-link.active { background: var(--accent); color: #fff; }
        .mob-sheet-link-icon {
            width: 34px; height: 34px; border-radius: 9px;
            background: rgba(255,255,255,.07);
            display: flex; align-items: center; justify-content: center;
            font-size: 15px; flex-shrink: 0;
        }
        .mob-sheet-link.active .mob-sheet-link-icon { background: rgba(255,255,255,.18); }
        .mob-sheet-footer {
            padding: 10px 12px 16px;
            border-top: 1px solid rgba(255,255,255,.07);
        }
        .mob-logout {
            display: flex; align-items: center; gap: 14px;
            padding: 13px 12px; border-radius: 12px;
            color: #f87171; text-decoration: none;
            font-size: 14px; font-weight: 500;
            transition: all .15s;
        }
        .mob-logout:hover { background: rgba(239,68,68,.1); color: #ef4444; }
        .mob-logout-icon {
            width: 34px; height: 34px; border-radius: 9px;
            background: rgba(239,68,68,.12);
            display: flex; align-items: center; justify-content: center;
            font-size: 15px; flex-shrink: 0; color: #f87171;
        }

        /* ── RESPONSIVE ──────────────────────────────────── */
        @media (max-width: 767px) {
            .sidebar      { display: none !important; }
            .main-wrapper { margin-left: 0 !important; }
            .mob-nav      { display: block; }
            .topbar { padding: 0 14px; }
            .page-title { max-width: calc(100vw - 140px); font-size: 15px; }
            .topbar-user-name { display: none; }
            .topbar-right .btn { display: none; }
            .page-content { padding: 14px 14px 100px; }
            .card { border-radius: var(--radius); }
        }

        @media (min-width: 768px) {
            .mob-nav      { display: none !important; }
            .mob-backdrop { display: none !important; }
            .mob-sheet    { display: none !important; }
            .mob-pill     { display: none !important; }
        }

        /* Tablet adjustments */
        @media (max-width: 1024px) and (min-width: 768px) {
            :root { --sidebar-width: 220px; }
            .page-content { padding: 16px; }
        }
    </style>
</head>
<body>

<!-- ── DESKTOP SIDEBAR ── -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <?php if ($_logo_b64): ?>
        <img src="data:image/png;base64,<?php echo $_logo_b64; ?>" alt="Logo">
        <?php else: ?>
        <img src="logo_sistema.png" alt="Logo">
        <?php endif; ?>
        <div>
            <div class="sidebar-brand-name"><?php echo htmlspecialchars($_sistema_nombre); ?></div>
            <div class="sidebar-brand-sub">Comités Afectivos</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Principal</div>
        <div class="nav-item">
            <a href="dashboard.php" class="nav-link <?php echo $pagina_actual=='dashboard.php'?'active':''; ?>">
                <span class="nav-icon"><i class="fas fa-chart-pie"></i></span>Dashboard
            </a>
        </div>
        <div class="nav-item">
            <a href="comites.php" class="nav-link <?php echo $pagina_actual=='comites.php'?'active':''; ?>">
                <span class="nav-icon"><i class="fas fa-layer-group"></i></span>Comités
            </a>
        </div>
        <div class="nav-item">
            <a href="crear_comite.php" class="nav-link <?php echo $pagina_actual=='crear_comite.php'?'active':''; ?>">
                <span class="nav-icon"><i class="fas fa-plus"></i></span>Crear Comité
            </a>
        </div>
        <div class="nav-section-label">Herramientas</div>
        <div class="nav-item">
            <a href="consultar.html" class="nav-link <?php echo $pagina_actual=='consultar.html'?'active':''; ?>">
                <span class="nav-icon"><i class="fas fa-id-card"></i></span>Consultar Cédula
            </a>
        </div>
        <?php if (esSupervisor()): ?>
        <div class="nav-section-label">Administración</div>
        <div class="nav-item">
            <a href="usuarios.php" class="nav-link <?php echo $pagina_actual=='usuarios.php'?'active':''; ?>">
                <span class="nav-icon"><i class="fas fa-users-cog"></i></span>Usuarios
            </a>
        </div>
        <?php endif; ?>
        <?php if (esAdmin()): ?>
        <div class="nav-item">
            <a href="config.php" class="nav-link <?php echo $pagina_actual=='config.php'?'active':''; ?>">
                <span class="nav-icon"><i class="fas fa-cog"></i></span>Configuración
            </a>
        </div>
        <?php endif; ?>
    </nav>

    <?php if (tieneCandidato()): ?>
    <!-- Candidato activo -->
    <div style="padding:10px 12px;border-top:1px solid var(--sidebar-border);border-bottom:1px solid var(--sidebar-border);">
        <div style="font-size:10px;text-transform:uppercase;letter-spacing:.06em;color:rgba(255,255,255,.3);margin-bottom:6px;">Trabajando para</div>
        <div style="display:flex;align-items:center;gap:8px;">
            <div style="width:28px;height:28px;border-radius:50%;background:var(--sidebar-active);color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;">
                <?php echo strtoupper(substr($_SESSION['candidato_nombre']??'?',0,1)); ?>
            </div>
            <div style="min-width:0;">
                <div style="font-size:12px;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($_SESSION['candidato_nombre']??''); ?></div>
                <div style="font-size:10px;color:rgba(255,255,255,.5);">
                    <?php echo ucfirst($_SESSION['candidato_cargo']??''); ?>
                    <?php if (!empty($_SESSION['candidato_desc'])): ?>
                    · <?php echo htmlspecialchars($_SESSION['candidato_desc']); ?>
                    <?php endif; ?>
                </div>
            </div>
            <a href="seleccionar_candidato.php" style="margin-left:auto;color:rgba(255,255,255,.4);font-size:11px;text-decoration:none;" title="Cambiar candidato">
                <i class="fas fa-exchange-alt"></i>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar"><?php echo strtoupper(substr($_SESSION['usuario_nombre']??'U',0,1)); ?></div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?php echo htmlspecialchars($_SESSION['usuario_nombre']??'Usuario'); ?></div>
                <div class="sidebar-user-role"><?php echo ucfirst($_SESSION['usuario_rol']??'usuario'); ?></div>
            </div>
            <a href="logout.php" class="sidebar-logout" title="Cerrar sesión"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </div>
</aside>

<!-- ── MAIN WRAPPER ── -->
<div class="main-wrapper" id="mainWrapper">

    <!-- Topbar -->
    <header class="topbar">
        <div class="topbar-left">
            <h1 class="page-title"><?php echo isset($titulo_pagina)?htmlspecialchars($titulo_pagina):'Dashboard'; ?></h1>
        </div>
        <div class="topbar-right">
            <a href="crear_comite.php" class="btn btn-primary btn-sm d-none d-md-inline-flex align-items-center gap-2">
                <i class="fas fa-plus"></i> Nuevo Comité
            </a>
            <?php if (isset($_SESSION['usuario_id'])): ?>
            <div class="topbar-user">
                <div class="topbar-avatar"><?php echo strtoupper(substr($_SESSION['usuario_nombre']??'U',0,1)); ?></div>
                <span class="topbar-user-name"><?php echo htmlspecialchars($_SESSION['usuario_nombre']??''); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Page Content -->
    <main class="page-content">

<!-- ══════════════════════════════════════════════════════
     MOBILE PILL NAV BAR
     ══════════════════════════════════════════════════════ -->
<div class="mob-nav">
    <!-- Pill bar -->
    <nav class="mob-pill">
        <a href="dashboard.php" class="mob-pill-item <?php echo $pagina_actual=='dashboard.php'?'active':''; ?>" title="Dashboard">
            <i class="fas fa-house"></i>
        </a>
        <a href="comites.php" class="mob-pill-item <?php echo $pagina_actual=='comites.php'?'active':''; ?>" title="Comités">
            <i class="fas fa-layer-group"></i>
        </a>
        <a href="crear_comite.php" class="mob-pill-item <?php echo $pagina_actual=='crear_comite.php'?'active':''; ?>" title="Crear Comité">
            <i class="fas fa-plus"></i>
        </a>
        <a href="consultar.html" class="mob-pill-item <?php echo $pagina_actual=='consultar.html'?'active':''; ?>" title="Consultar Cédula">
            <i class="fas fa-magnifying-glass"></i>
        </a>
        <div class="mob-pill-divider"></div>
        <button class="mob-pill-item" id="mobMoreBtn" title="Más" style="background:none;border:none;cursor:pointer;">
            <i class="fas fa-circle-user"></i>
        </button>
    </nav>

    <!-- Backdrop -->
    <div class="mob-backdrop" id="mobBackdrop"></div>

    <!-- More sheet -->
    <div class="mob-sheet" id="mobSheet">
        <div class="mob-sheet-handle"></div>
        <div class="mob-sheet-user">
            <div class="mob-sheet-avatar"><?php echo strtoupper(substr($_SESSION['usuario_nombre']??'U',0,1)); ?></div>
            <div>
                <div class="mob-sheet-name"><?php echo htmlspecialchars($_SESSION['usuario_nombre']??'Usuario'); ?></div>
                <div class="mob-sheet-role"><?php echo ucfirst($_SESSION['usuario_rol']??''); ?></div>
            </div>
        </div>
        <div class="mob-sheet-links">
            <a href="perfil.php" class="mob-sheet-link <?php echo $pagina_actual=='perfil.php'?'active':''; ?>">
                <span class="mob-sheet-link-icon"><i class="fas fa-user"></i></span>Mi Perfil
            </a>
            <?php if (tieneCandidato()): ?>
            <a href="seleccionar_candidato.php" class="mob-sheet-link">
                <span class="mob-sheet-link-icon"><i class="fas fa-exchange-alt"></i></span>
                Cambiar candidato
            </a>
            <?php endif; ?>
            <?php if (esSupervisor()): ?>
            <a href="usuarios.php" class="mob-sheet-link <?php echo $pagina_actual=='usuarios.php'?'active':''; ?>">
                <span class="mob-sheet-link-icon"><i class="fas fa-users-cog"></i></span>Usuarios
            </a>
            <?php endif; ?>
            <?php if (esAdmin()): ?>
            <a href="config.php" class="mob-sheet-link <?php echo $pagina_actual=='config.php'?'active':''; ?>">
                <span class="mob-sheet-link-icon"><i class="fas fa-cog"></i></span>Configuración
            </a>
            <?php endif; ?>
        </div>
        <div class="mob-sheet-footer">
            <a href="logout.php" class="mob-logout">
                <span class="mob-logout-icon"><i class="fas fa-sign-out-alt"></i></span>
                Cerrar Sesión
            </a>
        </div>
    </div>
</div>
