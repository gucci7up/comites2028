<?php
session_start();

// Si ya hay una sesión activa, redirigir al dashboard
if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Verificar si la base de datos existe
function verificarBaseDatos($host, $user, $password, $dbname) {
    try {
        $conn = new mysqli($host, $user, $password, $dbname);
        if ($conn->connect_error) {
            return false;
        }
        $conn->close();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Configuración de la base de datos
$host     = getenv('MYSQL_HOST')     ?: 'localhost';
$user     = getenv('MYSQL_USER')     ?: 'root';
$password = getenv('MYSQL_PASS')     ?: '';
$dbname   = getenv('MYSQL_DATABASE') ?: 'comites_prm';

// Verificar si la base de datos existe
$dbExists = verificarBaseDatos($host, $user, $password, $dbname);

if (!$dbExists) {
    // Redirigir a la página de configuración
    header("Location: db_setup.html");
    exit;
}

// Incluir configuración de base de datos
require_once 'db/config.php';

$error = '';

// Procesar el formulario de login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($usuario) || empty($password)) {
        $error = "Por favor, complete todos los campos.";
    } else {
        // Conectar a la base de datos
        $conexion = conectarDB();
        
        // Sanitizar entradas
        $usuario = sanitizar($conexion, $usuario);
        
        // Consultar usuario
        $sql = "SELECT id, nombre, usuario, password, rol FROM usuarios WHERE usuario = '$usuario' AND activo = 1";
        $resultado = $conexion->query($sql);
        
        if ($resultado->num_rows == 1) {
            $fila = $resultado->fetch_assoc();
            
            // Verificar contraseña
            if (password_verify($password, $fila['password'])) {
                // Iniciar sesión
                $_SESSION['usuario_id'] = $fila['id'];
                $_SESSION['usuario_nombre'] = $fila['nombre'];
                $_SESSION['usuario_rol'] = $fila['rol'];
                
                // Actualizar último acceso
                $sql = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = {$fila['id']}";
                $conexion->query($sql);
                
                // Redirigir al dashboard
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Contraseña incorrecta.";
            }
        } else {
            $error = "Usuario no encontrado o inactivo.";
        }
        
        $conexion->close();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Comités Afectivos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .login-body {
            padding: 30px;
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .logo-img {
            max-width: 150px;
            height: auto;
        }
        
        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }
        
        .btn-login {
            background: var(--secondary-color);
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            width: 100%;
            font-weight: 600;
            margin-top: 10px;
        }
        
        .btn-login:hover {
            background: var(--primary-color);
        }
        
        .alert {
            margin-bottom: 20px;
            padding: 10px 15px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2>PARTIDO REVOLUCIONARIO MODERNO</h2>
            <p>Sistema de Gestión de Comités Afectivos</p>
        </div>
        
        <div class="login-body">
            <div class="logo-container">
                <img src="logo1.png" alt="Logo PRM" class="logo-img">
            </div>
            
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="mb-3">
                    <label for="usuario" class="form-label">Usuario</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="usuario" name="usuario" placeholder="Ingrese su usuario" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Ingrese su contraseña" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i> Iniciar Sesión
                </button>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>