<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$pagina_actual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($titulo_pagina) ? $titulo_pagina . ' — PRM Comités' : 'PRM Comités Afectivos'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-bg:      #0d1b2a;
            --sidebar-hover:   #1a2e42;
            --sidebar-active:  #1d4ed8;
            --sidebar-border:  rgba(255,255,255,0.06);
            --sidebar-text:    rgba(255,255,255,0.65);
            --sidebar-width:   260px;
            --accent:          #2563eb;
            --accent-light:    #3b82f6;
            --success:         #10b981;
            --warning:         #f59e0b;
            --danger:          #ef4444;
            --info:            #06b6d4;
            --bg:              #f1f5f9;
            --surface:         #ffffff;
            --text-primary:    #0f172a;
            --text-secondary:  #64748b;
            --border:          #e2e8f0;
            --shadow-sm:       0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.04);
            --shadow-md:       0 4px 6px rgba(0,0,0,0.07), 0 2px 4px rgba(0,0,0,0.06);
            --shadow-lg:       0 10px 15px rgba(0,0,0,0.08), 0 4px 6px rgba(0,0,0,0.05);
            --radius:          12px;
            --radius-sm:       8px;
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: var(--bg);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            font-size: 14px;
            line-height: 1.5;
        }

        /* ── SIDEBAR ─────────────────────────────────────── */
        .sidebar {
            position: fixed;
            top: 0; left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--sidebar-bg);
            display: flex;
            flex-direction: column;
            z-index: 1000;
            transition: transform 0.25s cubic-bezier(.4,0,.2,1);
            overflow: hidden;
        }

        .sidebar-brand {
            padding: 24px 20px 20px;
            border-bottom: 1px solid var(--sidebar-border);
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }

        .sidebar-brand img {
            width: 44px;
            height: 44px;
            object-fit: contain;
            border-radius: 8px;
            background: rgba(255,255,255,0.08);
            padding: 4px;
        }

        .sidebar-brand-text {
            flex: 1;
            min-width: 0;
        }

        .sidebar-brand-name {
            font-size: 11px;
            font-weight: 700;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            line-height: 1.3;
        }

        .sidebar-brand-sub {
            font-size: 10px;
            color: var(--sidebar-text);
            margin-top: 2px;
        }

        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: 16px 12px;
            scrollbar-width: none;
        }
        .sidebar-nav::-webkit-scrollbar { display: none; }

        .nav-section-label {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: rgba(255,255,255,0.3);
            padding: 12px 8px 6px;
        }

        .nav-item { margin-bottom: 2px; }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            border-radius: 8px;
            color: var(--sidebar-text);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
            transition: all 0.15s ease;
            position: relative;
        }

        .nav-link:hover {
            background: var(--sidebar-hover);
            color: #fff;
        }

        .nav-link.active {
            background: var(--sidebar-active);
            color: #fff;
            box-shadow: 0 2px 8px rgba(37,99,235,0.35);
        }

        .nav-link .nav-icon {
            width: 32px;
            height: 32px;
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
            background: rgba(255,255,255,0.06);
            transition: background 0.15s;
        }

        .nav-link.active .nav-icon {
            background: rgba(255,255,255,0.15);
        }

        .nav-link:hover .nav-icon {
            background: rgba(255,255,255,0.1);
        }

        .sidebar-footer {
            padding: 16px 12px;
            border-top: 1px solid var(--sidebar-border);
            flex-shrink: 0;
        }

        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 8px;
            background: rgba(255,255,255,0.05);
        }

        .sidebar-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: var(--accent);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
            flex-shrink: 0;
        }

        .sidebar-user-info { flex: 1; min-width: 0; }
        .sidebar-user-name {
            font-size: 13px;
            font-weight: 600;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sidebar-user-role {
            font-size: 11px;
            color: var(--sidebar-text);
        }

        .sidebar-logout {
            color: var(--sidebar-text);
            text-decoration: none;
            padding: 6px;
            border-radius: 6px;
            transition: all 0.15s;
            font-size: 15px;
        }
        .sidebar-logout:hover { color: var(--danger); background: rgba(239,68,68,0.1); }

        /* ── TOPBAR ──────────────────────────────────────── */
        .topbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 0 24px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow-sm);
        }

        .topbar-left { display: flex; align-items: center; gap: 12px; }

        .page-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }

        .breadcrumb-pill {
            background: #eff6ff;
            color: var(--accent);
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 20px;
        }

        .topbar-right { display: flex; align-items: center; gap: 8px; }

        .topbar-btn {
            width: 36px;
            height: 36px;
            border: none;
            background: transparent;
            border-radius: 8px;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s;
            font-size: 15px;
        }
        .topbar-btn:hover { background: var(--bg); color: var(--text-primary); }

        .topbar-user {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px 10px 4px 4px;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.15s;
        }
        .topbar-user:hover { background: var(--bg); }

        .topbar-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--accent);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 12px;
        }

        .topbar-user-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
        }

        /* ── MAIN LAYOUT ─────────────────────────────────── */
        .main-wrapper {
            margin-left: var(--sidebar-width);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            min-width: 0;
            transition: margin-left 0.25s cubic-bezier(.4,0,.2,1);
        }

        .page-content {
            padding: 24px;
            flex: 1;
        }

        /* ── TOGGLE BTN ──────────────────────────────────── */
        .toggle-sidebar {
            display: none;
            width: 36px;
            height: 36px;
            border: none;
            background: transparent;
            border-radius: 8px;
            color: var(--text-secondary);
            font-size: 18px;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

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
            padding: 16px 20px;
            font-weight: 600;
            font-size: 14px;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* ── TABLES ──────────────────────────────────────── */
        .table { font-size: 13px; }
        .table thead th {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border);
            padding: 10px 16px;
            background: #f8fafc;
        }
        .table td {
            padding: 12px 16px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            color: var(--text-primary);
        }
        .table tbody tr:last-child td { border-bottom: none; }
        .table tbody tr:hover td { background: #f8fafc; }

        /* ── BADGES ──────────────────────────────────────── */
        .badge { font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 20px; }

        /* ── BUTTONS ─────────────────────────────────────── */
        .btn { font-size: 13px; font-weight: 500; border-radius: var(--radius-sm); }
        .btn-primary { background: var(--accent); border-color: var(--accent); }
        .btn-primary:hover { background: #1d4ed8; border-color: #1d4ed8; }

        /* ── RESPONSIVE ──────────────────────────────────── */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .main-wrapper { margin-left: 0; }
            .toggle-sidebar { display: flex; }
            .page-content { padding: 16px; }
        }

        /* ── OVERLAY ─────────────────────────────────────── */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.4);
            z-index: 999;
        }
        .sidebar-overlay.show { display: block; }
    </style>
</head>
<body>

<!-- Sidebar overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ── SIDEBAR ── -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img src="<?php echo str_repeat('../', substr_count($pagina_actual, '/')); ?>logo1.png" alt="PRM">
        <div class="sidebar-brand-text">
            <div class="sidebar-brand-name">Partido Revolucionario<br>Moderno</div>
            <div class="sidebar-brand-sub">Comités Afectivos</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Principal</div>

        <div class="nav-item">
            <a href="dashboard.php" class="nav-link <?php echo $pagina_actual == 'dashboard.php' ? 'active' : ''; ?>">
                <span class="nav-icon"><i class="fas fa-chart-pie"></i></span>
                Dashboard
            </a>
        </div>
        <div class="nav-item">
            <a href="comites.php" class="nav-link <?php echo $pagina_actual == 'comites.php' ? 'active' : ''; ?>">
                <span class="nav-icon"><i class="fas fa-layer-group"></i></span>
                Comités
            </a>
        </div>
        <div class="nav-item">
            <a href="crear_comite.php" class="nav-link <?php echo $pagina_actual == 'crear_comite.php' ? 'active' : ''; ?>">
                <span class="nav-icon"><i class="fas fa-plus"></i></span>
                Crear Comité
            </a>
        </div>

        <div class="nav-section-label">Herramientas</div>

        <div class="nav-item">
            <a href="consultar.html" class="nav-link <?php echo $pagina_actual == 'consultar.html' ? 'active' : ''; ?>">
                <span class="nav-icon"><i class="fas fa-id-card"></i></span>
                Consultar Cédula
            </a>
        </div>

        <?php if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] == 'admin'): ?>
        <div class="nav-section-label">Administración</div>
        <div class="nav-item">
            <a href="usuarios.php" class="nav-link <?php echo $pagina_actual == 'usuarios.php' ? 'active' : ''; ?>">
                <span class="nav-icon"><i class="fas fa-users-cog"></i></span>
                Usuarios
            </a>
        </div>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar">
                <?php echo strtoupper(substr($_SESSION['usuario_nombre'] ?? 'U', 0, 1)); ?>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?></div>
                <div class="sidebar-user-role"><?php echo ucfirst($_SESSION['usuario_rol'] ?? 'usuario'); ?></div>
            </div>
            <a href="logout.php" class="sidebar-logout" title="Cerrar sesión">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</aside>

<!-- ── MAIN WRAPPER ── -->
<div class="main-wrapper" id="mainWrapper">

    <!-- Topbar -->
    <header class="topbar">
        <div class="topbar-left">
            <button class="toggle-sidebar" id="toggleSidebar">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="page-title"><?php echo isset($titulo_pagina) ? htmlspecialchars($titulo_pagina) : 'Dashboard'; ?></h1>
            <span class="breadcrumb-pill d-none d-sm-inline">PRM 2024</span>
        </div>
        <div class="topbar-right">
            <a href="crear_comite.php" class="btn btn-primary btn-sm d-none d-md-inline-flex align-items-center gap-2">
                <i class="fas fa-plus"></i> Nuevo Comité
            </a>
            <?php if (isset($_SESSION['usuario_id'])): ?>
            <div class="topbar-user" id="topbarUser">
                <div class="topbar-avatar">
                    <?php echo strtoupper(substr($_SESSION['usuario_nombre'] ?? 'U', 0, 1)); ?>
                </div>
                <span class="topbar-user-name d-none d-sm-inline">
                    <?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?>
                </span>
                <i class="fas fa-chevron-down" style="font-size:10px;color:var(--text-secondary);"></i>
            </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Page Content -->
    <main class="page-content">
