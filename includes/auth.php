<?php
if (session_status() == PHP_SESSION_NONE) session_start();

function estaAutenticado()  { return isset($_SESSION['usuario_id']); }
function esOwner()          { return isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['owner','superadmin']); }
function esAdmin()          { return isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['admin','owner','superadmin']); }
function esSuperAdmin()     { return isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'superadmin'; }
function tieneCandidato()   { return isset($_SESSION['candidato_id']) && $_SESSION['candidato_id'] > 0; }

function requiereAutenticacion() {
    if (!estaAutenticado()) { header("Location: login.php"); exit; }
}

function requiereAdmin() {
    requiereAutenticacion();
    if (!esAdmin()) { header("Location: dashboard.php?error=permisos"); exit; }
}

function requiereOwner() {
    requiereAutenticacion();
    if (!esOwner()) { header("Location: dashboard.php?error=permisos"); exit; }
}

function requiereSuperAdmin() {
    requiereAutenticacion();
    if (!esSuperAdmin()) { header("Location: dashboard.php?error=permisos"); exit; }
}

function requiereCandidato() {
    requiereAutenticacion();
    // Owners y superadmins no necesitan candidato seleccionado
    if (esOwner()) return;
    if (!tieneCandidato()) {
        header("Location: seleccionar_candidato.php"); exit;
    }
}

function cerrarSesion() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
    }
    session_destroy();
}

/* Carga el tema del partido del usuario actual */
function cargarTemaPartido() {
    if (!isset($_SESSION['partido_id'])) {
        return ['color_primario'=>'#2563eb','color_sidebar'=>'#0d1b2a','color_accent'=>'#3b82f6','nombre'=>'Sistema','siglas'=>''];
    }
    require_once __DIR__ . '/../db/config.php';
    $conn = conectarDB();
    $stmt = $conn->prepare("SELECT nombre, siglas, color_primario, color_sidebar, color_accent, logo FROM partidos WHERE id=?");
    $stmt->bind_param("i", $_SESSION['partido_id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $conn->close();
    return $row ?: ['color_primario'=>'#2563eb','color_sidebar'=>'#0d1b2a','color_accent'=>'#3b82f6','nombre'=>'Sistema','siglas'=>''];
}
?>
