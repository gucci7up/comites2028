<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/auth.php';
$pagina_actual = basename($_SERVER['PHP_SELF']);

// Cargar configuración del sistema
$_cfg = cargarConfiguracion();
$_color_primary = $_cfg['color_primario'] ?? '#2563eb';
$_color_accent  = $_cfg['color_accent']   ?? '#3b82f6';
$_sistema_nombre = $_cfg['nombre_partido'] ?? 'Sistema';
$_sistema_siglas = $_cfg['siglas'] ?? '';
$_logo_b64 = !empty($_cfg['logo']) ? base64_encode($_cfg['logo']) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($titulo_pagina) ? htmlspecialchars($titulo_pagina).' — '.$_sistema_siglas : $_sistema_nombre; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --accent:         <?php echo $_color_primary; ?>;
            --accent-light:   <?php echo $_color_accent; ?>;
            --accent-700:     color-mix(in srgb, var(--accent) 70%, black);
            --accent-tint:    color-mix(in srgb, var(--accent) 8%, white);
            --success:        #16a34a;
            --success-bg:     #e8f9ee;
            --warning:        #d97706;
            --warning-bg:     #fef3e2;
            --danger:         #ef4444;
            --danger-bg:      #fef2f2;
            --info:           #06b6d4;
            --bg:             #f4f5fa;
            --surface:        #ffffff;
            --text-primary:   #1e1b2e;
            --text-secondary: #5b5678;
            --text-tertiary:  #9691b0;
            --border:         #eceafc;
            --shadow-sm:      0 1px 2px rgba(30,27,46,.05);
            --shadow-md:      0 8px 24px rgba(30,27,46,.08);
            --radius:         12px;
            --radius-sm:      9px;
            --radius-card:    22px;
            --sidebar-width:  248px;
            --topbar-h:       72px;
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
            background: var(--surface);
            box-shadow: 1px 0 0 var(--border);
            display: flex;
            flex-direction: column;
            padding: 22px 16px;
            z-index: 1040;
        }

        .sidebar-brand {
            display: flex; align-items: center; gap: 10px;
            padding: 0 8px 24px;
            flex-shrink: 0;
        }
        .brand-logo {
            width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0;
            background: linear-gradient(135deg, var(--accent), var(--accent-light));
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 14px; overflow: hidden;
        }
        .brand-logo img { width: 100%; height: 100%; object-fit: contain; }
        .brand-name { font-weight: 800; font-size: 16px; line-height: 1.2; }

        .sidebar-nav {
            flex: 1; overflow-y: auto;
            scrollbar-width: none;
        }
        .sidebar-nav::-webkit-scrollbar { display: none; }

        .nav-section-label {
            font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .06em;
            color: var(--text-tertiary);
            padding: 8px 12px 6px;
        }
        .nav-section-label:first-child { padding-top: 0; }

        .nav-link {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 12px;
            border-radius: 12px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 13.5px; font-weight: 500;
            margin-bottom: 4px;
            transition: background .15s, color .15s;
        }
        .nav-link i { width: 17px; text-align: center; font-size: 14px; flex-shrink: 0; }
        .nav-link:hover { background: var(--accent-tint); color: var(--accent); }
        .nav-link.active {
            background: linear-gradient(135deg, var(--accent), var(--accent-light));
            color: #fff; font-weight: 600;
        }
        .nav-link.active:hover { color: #fff; }

        /* Candidate strip */
        .sidebar-candidate {
            margin: 8px 4px 12px;
            padding: 10px 12px;
            border-radius: 14px;
            background: var(--accent-tint);
            display: flex; align-items: center; gap: 8px;
            flex-shrink: 0;
        }
        .sidebar-candidate-label {
            font-size: 10px; text-transform: uppercase; letter-spacing: .06em;
            color: var(--accent); font-weight: 700; margin-bottom: 2px;
        }
        .sidebar-candidate-name {
            font-size: 12px; font-weight: 600; color: var(--text-primary);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .sidebar-candidate-role { font-size: 10.5px; color: var(--text-tertiary); }
        .sidebar-candidate-switch { margin-left: auto; color: var(--text-tertiary); flex-shrink: 0; text-decoration: none; }
        .sidebar-candidate-switch:hover { color: var(--accent); }

        /* User footer card */
        .sidebar-footer { flex-shrink: 0; padding-top: 8px; }
        .sidebar-user {
            display: flex; align-items: center; gap: 6px;
            padding: 8px; border-radius: 14px;
            background: var(--bg);
        }
        .sidebar-user-link {
            flex: 1; min-width: 0; display: flex; align-items: center; gap: 10px;
            text-decoration: none; color: inherit;
        }
        .sidebar-avatar {
            width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0;
            background: linear-gradient(135deg, var(--accent), var(--accent-light));
            color: #fff; display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 12px;
        }
        .sidebar-user-info { flex: 1; min-width: 0; }
        .sidebar-user-name {
            font-size: 12.5px; font-weight: 600; color: var(--text-primary);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .sidebar-user-role { font-size: 10.5px; color: var(--text-tertiary); }
        .sidebar-logout {
            color: var(--text-tertiary); text-decoration: none;
            padding: 6px; border-radius: 8px; transition: all .15s; font-size: 14px;
            flex-shrink: 0;
        }
        .sidebar-logout:hover { color: var(--danger); background: var(--danger-bg); }

        /* ── TOPBAR ──────────────────────────────────────── */
        .topbar {
            height: var(--topbar-h); flex-shrink: 0;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 32px;
        }
        .page-title { font-size: 20px; font-weight: 800; color: var(--text-primary); line-height: 1.2; }
        .page-subtitle { font-size: 12.5px; color: var(--text-tertiary); margin-top: 2px; }
        .topbar-right { display: flex; align-items: center; gap: 14px; }
        .topbar-icon-btn {
            width: 40px; height: 40px; border-radius: 12px;
            background: var(--surface); box-shadow: var(--shadow-sm);
            border: none; display: flex; align-items: center; justify-content: center;
            color: var(--text-secondary); font-size: 15px; cursor: pointer;
        }
        .topbar-icon-btn:hover { color: var(--accent); }
        .topbar-avatar {
            width: 40px; height: 40px; border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), var(--accent-light));
            color: #fff; display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 13px; text-decoration: none; flex-shrink: 0;
        }

        /* ── MAIN LAYOUT ─────────────────────────────────── */
        .main-wrapper {
            margin-left: var(--sidebar-width);
            flex: 1; display: flex; flex-direction: column;
            min-height: 100vh; min-width: 0;
        }
        .page-content { padding: 0 32px 32px; flex: 1; }

        /* ── SHARED PAGE STYLES ──────────────────────────── */
        .card {
            background: var(--surface);
            border: none;
            border-radius: var(--radius-card);
            box-shadow: var(--shadow-sm);
        }
        .card-header {
            background: transparent;
            border-bottom: 1px solid #f0f0f6;
            padding: 18px 24px;
            font-weight: 700; font-size: 15px;
            color: var(--text-primary);
            display: flex; align-items: center; justify-content: space-between;
        }
        .page-back {
            display: inline-flex; align-items: center; gap: 8px;
            color: var(--text-secondary); font-size: 13px;
            text-decoration: none; margin-bottom: 20px;
            transition: color .15s;
        }
        .page-back:hover { color: var(--accent); }
        .table { font-size: 13px; margin: 0; }
        .table thead th {
            font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .05em;
            color: var(--text-tertiary); border-bottom: 1px solid #f0f0f6;
            padding: 12px 24px; background: #fafafd;
        }
        .table td {
            padding: 14px 24px; vertical-align: middle;
            border-bottom: 1px solid #f0f0f6; color: var(--text-primary);
        }
        .table tbody tr:last-child td { border-bottom: none; }
        .table tbody tr:hover td { background: #fafafd; }
        .btn { border-radius: var(--radius); font-size: 13px; font-weight: 600; }
        .btn-primary { background: var(--accent); border-color: var(--accent); }
        .btn-primary:hover { filter: brightness(1.08); border-color: transparent; background: var(--accent); }
        .form-control, .form-select {
            border-radius: var(--radius); font-size: 13px;
            border: 1px solid var(--border); background: #f7f7fb;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 15%, transparent);
            background: #fff;
        }
        .form-label { font-size: 13px; font-weight: 500; color: var(--text-primary); margin-bottom: 6px; }
        .form-text  { font-size: 11px; color: var(--text-tertiary); }
        .badge { font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; }
        .alert { border-radius: var(--radius); border: none; font-size: 13px; }
        .modal-content { border-radius: var(--radius-card); border: none; box-shadow: var(--shadow-md); }
        .modal-header { border-bottom: 1px solid #f0f0f6; padding: 18px 22px; }
        .modal-body { padding: 22px; }
        .modal-footer { border-top: 1px solid #f0f0f6; padding: 16px 22px; }
        .action-link {
            width: 30px; height: 30px; border-radius: var(--radius-sm);
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 12px; border: none;
            color: var(--text-secondary); background: var(--bg); cursor: pointer;
            text-decoration: none; transition: all .15s;
        }
        .action-link:hover { color: var(--accent); background: var(--accent-tint); }
        .action-link.danger { background: var(--danger-bg); color: var(--danger); }
        .action-link.danger:hover { background: var(--danger); color: #fff; }
        .empty-state { text-align: center; padding: 48px 20px; color: var(--text-tertiary); }
        .empty-state i { font-size: 40px; margin-bottom: 12px; display: block; opacity: .3; }
        .empty-state p { font-size: 13px; margin: 0; }

        /* Shared form field classes used across create/edit/profile/config forms */
        .fld {
            width: 100%; box-sizing: border-box;
            border: 1px solid var(--border); background: #f7f7fb;
            border-radius: var(--radius); padding: 11px 14px;
            font-size: 13.5px; font-family: inherit; color: var(--text-primary);
        }
        .fld:focus {
            outline: none; border-color: var(--accent); background: #fff;
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 15%, transparent);
        }
        .lbl { font-size: 11.5px; font-weight: 600; color: var(--text-secondary); display: block; margin-bottom: 6px; }

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
            .page-title { max-width: calc(100vw - 60px); font-size: 17px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .page-subtitle { display: none; }
            .topbar-right .topbar-icon-btn { display: none; }
            .page-content { padding: 0 14px 100px; }
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
        }
    </style>
</head>
<body>

<!-- ── DESKTOP SIDEBAR ── -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-logo">
            <?php if ($_logo_b64): ?>
            <img src="data:image/png;base64,<?php echo $_logo_b64; ?>" alt="Logo">
            <?php else: ?>
            <?php echo htmlspecialchars(mb_substr($_sistema_siglas ?: $_sistema_nombre, 0, 1)); ?>
            <?php endif; ?>
        </div>
        <span class="brand-name"><?php echo htmlspecialchars($_sistema_nombre); ?></span>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Principal</div>
        <a href="dashboard.php" class="nav-link <?php echo $pagina_actual=='dashboard.php'?'active':''; ?>">
            <i class="fas fa-chart-pie"></i>Dashboard
        </a>
        <a href="comites.php" class="nav-link <?php echo $pagina_actual=='comites.php'?'active':''; ?>">
            <i class="fas fa-layer-group"></i>Comités
        </a>
        <a href="crear_comite.php" class="nav-link <?php echo $pagina_actual=='crear_comite.php'?'active':''; ?>">
            <i class="fas fa-plus"></i>Crear Comité
        </a>

        <div class="nav-section-label">Herramientas</div>
        <a href="consultar.php" class="nav-link <?php echo $pagina_actual=='consultar.php'?'active':''; ?>">
            <i class="fas fa-id-card"></i>Consultar Cédula
        </a>

        <?php if (esSupervisor() || esAdmin()): ?>
        <div class="nav-section-label">Administración</div>
        <?php if (esSupervisor()): ?>
        <a href="usuarios.php" class="nav-link <?php echo $pagina_actual=='usuarios.php'?'active':''; ?>">
            <i class="fas fa-users-cog"></i>Usuarios
        </a>
        <?php endif; ?>
        <?php if (esAdmin()): ?>
        <a href="config.php" class="nav-link <?php echo $pagina_actual=='config.php'?'active':''; ?>">
            <i class="fas fa-cog"></i>Configuración
        </a>
        <?php endif; ?>
        <?php endif; ?>
    </nav>

    <?php if (tieneCandidato()): ?>
    <div class="sidebar-candidate">
        <div class="sidebar-avatar"><?php echo strtoupper(substr($_SESSION['candidato_nombre']??'?',0,1)); ?></div>
        <div style="min-width:0;">
            <div class="sidebar-candidate-label">Trabajando para</div>
            <div class="sidebar-candidate-name"><?php echo htmlspecialchars($_SESSION['candidato_nombre']??''); ?></div>
            <div class="sidebar-candidate-role">
                <?php echo ucfirst($_SESSION['candidato_cargo']??''); ?>
                <?php if (!empty($_SESSION['candidato_desc'])): ?>
                · <?php echo htmlspecialchars($_SESSION['candidato_desc']); ?>
                <?php endif; ?>
            </div>
        </div>
        <a href="seleccionar_candidato.php" class="sidebar-candidate-switch" title="Cambiar candidato">
            <i class="fas fa-exchange-alt"></i>
        </a>
    </div>
    <?php endif; ?>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <a href="perfil.php" class="sidebar-user-link">
                <div class="sidebar-avatar"><?php echo strtoupper(substr($_SESSION['usuario_nombre']??'U',0,1)); ?></div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name"><?php echo htmlspecialchars($_SESSION['usuario_nombre']??'Usuario'); ?></div>
                    <div class="sidebar-user-role"><?php echo ucfirst($_SESSION['usuario_rol']??'usuario'); ?></div>
                </div>
            </a>
            <a href="logout.php" class="sidebar-logout" title="Cerrar sesión"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </div>
</aside>

<!-- ── MAIN WRAPPER ── -->
<div class="main-wrapper" id="mainWrapper">

    <!-- Topbar -->
    <header class="topbar">
        <div>
            <h1 class="page-title"><?php echo isset($titulo_pagina)?htmlspecialchars($titulo_pagina):'Dashboard'; ?></h1>
            <?php if (!empty($subtitulo_pagina)): ?>
            <div class="page-subtitle"><?php echo htmlspecialchars($subtitulo_pagina); ?></div>
            <?php endif; ?>
        </div>
        <div class="topbar-right">
            <button type="button" class="topbar-icon-btn" title="Notificaciones"><i class="fas fa-bell"></i></button>
            <button type="button" class="topbar-icon-btn" title="Mensajes"><i class="fas fa-comment"></i></button>
            <?php if (isset($_SESSION['usuario_id'])): ?>
            <a href="perfil.php" class="topbar-avatar" title="Mi perfil"><?php echo strtoupper(substr($_SESSION['usuario_nombre']??'U',0,1)); ?></a>
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
        <a href="consultar.php" class="mob-pill-item <?php echo $pagina_actual=='consultar.php'?'active':''; ?>" title="Consultar Cédula">
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
