<?php
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Determinar la página actual para resaltar en el menú
$pagina_actual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Comités Afectivos - PRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --sidebar-width: 250px;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: var(--sidebar-width);
            background: var(--primary-color);
            color: white;
            padding-top: 20px;
            z-index: 1000;
            transition: all 0.3s;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-logo {
            max-width: 120px;
            margin-bottom: 15px;
        }
        
        .sidebar-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .sidebar-subtitle {
            font-size: 14px;
            opacity: 0.8;
        }
        
        .sidebar-menu {
            padding: 20px 0;
            list-style: none;
            margin: 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left: 4px solid var(--secondary-color);
        }
        
        .sidebar-menu i {
            margin-right: 10px;
            font-size: 18px;
            width: 25px;
            text-align: center;
        }
        
        .sidebar-footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 15px 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            font-size: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .sidebar-footer a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
        }
        
        .sidebar-footer a:hover {
            color: white;
        }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            flex: 1;
            transition: all 0.3s;
        }
        
        /* Topbar */
        .topbar {
            background: white;
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .page-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }
        
        .user-dropdown {
            position: relative;
        }
        
        .user-dropdown-toggle {
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .user-dropdown-toggle:hover {
            background: #f8f9fa;
        }
        
        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: var(--secondary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 10px;
        }
        
        .user-name {
            font-weight: 600;
            margin-right: 5px;
        }
        
        .user-dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 10px 0;
            min-width: 200px;
            z-index: 1000;
            display: none;
        }
        
        .user-dropdown-menu.show {
            display: block;
        }
        
        .user-dropdown-item {
            display: flex;
            align-items: center;
            padding: 8px 15px;
            color: #333;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .user-dropdown-item:hover {
            background: #f8f9fa;
        }
        
        .user-dropdown-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .topbar {
                position: sticky;
                top: 0;
                z-index: 900;
            }
            
            .toggle-sidebar {
                display: block !important;
            }
        }
        
        /* Toggle Button */
        .toggle-sidebar {
            display: none;
            background: none;
            border: none;
            font-size: 20px;
            color: var(--primary-color);
            cursor: pointer;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="logo1.png" alt="Logo PRM" class="sidebar-logo">
            <div class="sidebar-title">PARTIDO REVOLUCIONARIO MODERNO</div>
            <div class="sidebar-subtitle">Sistema de Comités Afectivos</div>
        </div>
        
        <ul class="sidebar-menu">
            <li>
                <a href="dashboard.php" class="<?php echo $pagina_actual == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="comites.php" class="<?php echo $pagina_actual == 'comites.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> Comités
                </a>
            </li>
            <li>
                <a href="crear_comite.php" class="<?php echo $pagina_actual == 'crear_comite.php' ? 'active' : ''; ?>">
                    <i class="fas fa-plus-circle"></i> Crear Comité
                </a>
            </li>
            <li>
                <a href="consultar.html" class="<?php echo $pagina_actual == 'consultar.html' ? 'active' : ''; ?>">
                    <i class="fas fa-search"></i> Consultar Cédula
                </a>
            </li>
            <?php if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] == 'admin'): ?>
            <li>
                <a href="usuarios.php" class="<?php echo $pagina_actual == 'usuarios.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-cog"></i> Gestión de Usuarios
                </a>
            </li>
            <?php endif; ?>
        </ul>
        
        <div class="sidebar-footer">
            <span>&copy; 2024 PRM</span>
            <a href="logout.php" title="Cerrar Sesión"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content" id="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div class="d-flex align-items-center">
                <button class="toggle-sidebar" id="toggle-sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title"><?php echo isset($titulo_pagina) ? $titulo_pagina : 'Dashboard'; ?></h1>
            </div>
            
            <?php if (isset($_SESSION['usuario_id'])): ?>
            <div class="user-dropdown">
                <div class="user-dropdown-toggle" id="user-dropdown-toggle">
                    <div class="user-avatar">
                        <?php echo substr($_SESSION['usuario_nombre'] ?? 'U', 0, 1); ?>
                    </div>
                    <span class="user-name"><?php echo $_SESSION['usuario_nombre'] ?? 'Usuario'; ?></span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                
                <div class="user-dropdown-menu" id="user-dropdown-menu">
                    <a href="perfil.php" class="user-dropdown-item">
                        <i class="fas fa-user"></i> Mi Perfil
                    </a>
                    <a href="logout.php" class="user-dropdown-item">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Page Content -->
        <div class="container-fluid">