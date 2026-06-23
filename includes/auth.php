<?php
/**
 * Archivo de autenticación y control de sesiones
 * Debe ser incluido en todas las páginas que requieran autenticación
 */

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está autenticado
function estaAutenticado() {
    return isset($_SESSION['usuario_id']);
}

// Verificar si el usuario tiene rol de administrador
function esAdmin() {
    return isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] == 'admin';
}

// Redirigir si no está autenticado
function requiereAutenticacion() {
    if (!estaAutenticado()) {
        header("Location: login.php");
        exit;
    }
}

// Redirigir si no es administrador
function requiereAdmin() {
    requiereAutenticacion();
    if (!esAdmin()) {
        header("Location: dashboard.php?error=permisos");
        exit;
    }
}

// Cerrar sesión
function cerrarSesion() {
    // Eliminar todas las variables de sesión
    $_SESSION = array();
    
    // Destruir la cookie de sesión si existe
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"
        ]);
    }
    
    // Destruir la sesión
    session_destroy();
}
?>