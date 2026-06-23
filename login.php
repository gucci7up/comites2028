<?php
session_start();

if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit;
}

$host     = getenv('MYSQL_HOST')     ?: 'localhost';
$user     = getenv('MYSQL_USER')     ?: 'root';
$password = getenv('MYSQL_PASS')     ?: '';
$dbname   = getenv('MYSQL_DATABASE') ?: 'comites_prm';

function verificarBaseDatos($host, $user, $password, $dbname) {
    try {
        $conn = new mysqli($host, $user, $password, $dbname);
        if ($conn->connect_error) return false;
        $conn->close(); return true;
    } catch (Exception $e) { return false; }
}

if (!verificarBaseDatos($host, $user, $password, $dbname)) {
    header("Location: db_setup.html"); exit;
}

require_once 'db/config.php';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usu = $_POST['usuario']  ?? '';
    $pwd = $_POST['password'] ?? '';
    if (empty($usu) || empty($pwd)) {
        $error = "Por favor, complete todos los campos.";
    } else {
        $conexion = conectarDB();
        $usu = sanitizar($conexion, $usu);
        $sql = "SELECT id, nombre, usuario, password, rol FROM usuarios WHERE usuario = '$usu' AND activo = 1";
        $res = $conexion->query($sql);
        if ($res->num_rows == 1) {
            $fila = $res->fetch_assoc();
            if (password_verify($pwd, $fila['password'])) {
                $_SESSION['usuario_id']     = $fila['id'];
                $_SESSION['usuario_nombre'] = $fila['nombre'];
                $_SESSION['usuario_rol']    = $fila['rol'];
                $conexion->query("UPDATE usuarios SET ultimo_acceso=NOW() WHERE id={$fila['id']}");
                header("Location: dashboard.php"); exit;
            } else { $error = "Contraseña incorrecta."; }
        } else { $error = "Usuario no encontrado o inactivo."; }
        $conexion->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso — PRM Comités</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: #0d1b2a;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .login-wrap {
            display: flex;
            width: 100%;
            max-width: 900px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0,0,0,0.4);
        }
        /* Left panel */
        .login-left {
            flex: 1;
            background: linear-gradient(150deg, #1e3a8a 0%, #2563eb 70%, #3b82f6 100%);
            padding: 48px 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }
        .login-left::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 220px; height: 220px;
            border-radius: 50%;
            background: rgba(255,255,255,0.07);
        }
        .login-left::after {
            content: '';
            position: absolute;
            bottom: -80px; left: -40px;
            width: 260px; height: 260px;
            border-radius: 50%;
            background: rgba(255,255,255,0.04);
        }
        .brand-logo {
            width: 64px; height: 64px;
            border-radius: 14px;
            background: rgba(255,255,255,0.12);
            padding: 10px;
            object-fit: contain;
            margin-bottom: 24px;
        }
        .brand-title {
            font-size: 22px;
            font-weight: 700;
            color: #fff;
            line-height: 1.3;
            margin-bottom: 8px;
        }
        .brand-sub {
            font-size: 14px;
            color: rgba(255,255,255,.7);
            margin-bottom: 32px;
        }
        .feature-list { list-style: none; padding: 0; margin: 0; }
        .feature-list li {
            display: flex;
            align-items: center;
            gap: 10px;
            color: rgba(255,255,255,.8);
            font-size: 13px;
            margin-bottom: 12px;
        }
        .feature-list li i {
            width: 28px; height: 28px;
            border-radius: 8px;
            background: rgba(255,255,255,.12);
            display: flex; align-items: center; justify-content: center;
            font-size: 12px;
            flex-shrink: 0;
        }
        .left-footer {
            font-size: 11px;
            color: rgba(255,255,255,.4);
            position: relative;
            z-index: 1;
        }
        /* Right panel */
        .login-right {
            width: 400px;
            flex-shrink: 0;
            background: #fff;
            padding: 48px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-heading {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .login-sub {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 32px;
        }
        .form-label { font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px; }
        .form-control {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 14px;
            transition: border-color .15s, box-shadow .15s;
        }
        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,.12);
            outline: none;
        }
        .input-group-text {
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            color: #9ca3af;
            border-radius: 8px 0 0 8px;
            padding: 10px 14px;
        }
        .input-group .form-control { border-radius: 0 8px 8px 0; border-left: none; }
        .input-group:focus-within .input-group-text {
            border-color: #2563eb;
            color: #2563eb;
        }
        .btn-login {
            background: #2563eb;
            border: none;
            border-radius: 8px;
            padding: 11px;
            font-size: 14px;
            font-weight: 600;
            color: #fff;
            width: 100%;
            cursor: pointer;
            transition: background .15s;
            margin-top: 8px;
        }
        .btn-login:hover { background: #1d4ed8; }
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        @media (max-width: 640px) {
            .login-left { display: none; }
            .login-right { width: 100%; padding: 40px 28px; border-radius: 20px; }
            .login-wrap { max-width: 440px; box-shadow: 0 20px 40px rgba(0,0,0,.3); }
        }
    </style>
</head>
<body>
<div class="login-wrap">
    <!-- Left -->
    <div class="login-left">
        <div style="position:relative;z-index:1;">
            <img src="logo1.png" alt="PRM" class="brand-logo">
            <div class="brand-title">Partido Revolucionario Moderno</div>
            <div class="brand-sub">Sistema de Gestión de Comités Afectivos</div>
            <ul class="feature-list">
                <li><i class="fas fa-layer-group"></i> Gestión de comités por municipio</li>
                <li><i class="fas fa-users"></i> Control de miembros y coordinadores</li>
                <li><i class="fas fa-id-card"></i> Consulta por cédula en tiempo real</li>
                <li><i class="fas fa-chart-pie"></i> Dashboard con estadísticas</li>
            </ul>
        </div>
        <div class="left-footer" style="z-index:1;">© <?php echo date('Y'); ?> PRM — República Dominicana</div>
    </div>

    <!-- Right -->
    <div class="login-right">
        <div class="login-heading">Bienvenido</div>
        <div class="login-sub">Ingrese sus credenciales para acceder al sistema.</div>

        <?php if (!empty($error)): ?>
        <div class="alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="mb-3">
                <label class="form-label">Usuario</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control" name="usuario" placeholder="Ingrese su usuario" required autofocus>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" name="password" placeholder="Ingrese su contraseña" required>
                </div>
            </div>
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt me-2"></i> Iniciar Sesión
            </button>
        </form>
    </div>
</div>
</body>
</html>
