<?php
if (session_status() == PHP_SESSION_NONE) session_start();

function estaAutenticado()  { return isset($_SESSION['usuario_id']); }
function esAdmin()          { return isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin'; }
function esSupervisor()     { return isset($_SESSION['usuario_rol']) && in_array($_SESSION['usuario_rol'], ['admin','supervisor']); }
function esDigitador()      { return isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'digitador'; }
function tieneCandidato()   { return isset($_SESSION['candidato_id']) && $_SESSION['candidato_id'] > 0; }

function requiereAutenticacion() {
    if (!estaAutenticado()) { header("Location: login.php"); exit; }
}

function requiereAdmin() {
    requiereAutenticacion();
    if (!esAdmin()) { header("Location: dashboard.php?error=permisos"); exit; }
}

function requiereSupervisor() {
    requiereAutenticacion();
    if (!esSupervisor()) { header("Location: dashboard.php?error=permisos"); exit; }
}

function requiereCandidato() {
    requiereAutenticacion();
    if (esAdmin() || esSupervisor()) return;
    if (!tieneCandidato()) { header("Location: seleccionar_candidato.php"); exit; }
}

function cerrarSesion() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
    }
    session_destroy();
}

function cargarConfiguracion() {
    static $config = null;
    if ($config !== null) return $config;
    require_once __DIR__ . '/../db/config.php';
    $conn = conectarDB();
    $res = $conn->query("SELECT * FROM configuracion LIMIT 1");
    $config = $res ? $res->fetch_assoc() : [];
    $conn->close();
    return $config ?: [
        'nombre_partido' => 'Sistema',
        'siglas'         => '',
        'color_primario' => '#2563eb',
        'color_sidebar'  => '#0d1b2a',
        'color_accent'   => '#3b82f6',
        'logo'           => null,
    ];
}
?>
