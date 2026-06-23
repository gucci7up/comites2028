<?php
/* ── MySQL (tablas propias del sistema) ── */
define('DB_HOST', getenv('MYSQL_HOST') ?: 'localhost');
define('DB_USER', getenv('MYSQL_USER') ?: 'root');
define('DB_PASS', getenv('MYSQL_PASS') ?: '');
define('DB_NAME', getenv('MYSQL_DATABASE') ?: 'comites_prm');

function conectarDB() {
    $conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conexion->connect_error) {
        die("Error de conexión MySQL: " . $conexion->connect_error);
    }
    $conexion->set_charset("utf8");
    return $conexion;
}

function sanitizar($conexion, $input) {
    return $conexion->real_escape_string($input);
}

/* ── SQL Server (padrón electoral / fotos PRM) ── */
function conectarSQLServer() {
    $server   = getenv('DB_HOST')     ?: '148.0.129.233';
    $port     = getenv('DB_PORT')     ?: '1433';
    $database = getenv('DB_DATABASE') ?: 'dbPRM';
    $user     = getenv('DB_USERNAME') ?: 'kmota';
    $password = getenv('DB_PASSWORD') ?: '';
    $encrypt  = getenv('DB_ENCRYPT')  ?: 'false';
    $trustCert = getenv('DB_TRUST_SERVER_CERTIFICATE') ?: 'true';

    try {
        $dsn = "sqlsrv:Server=$server,$port;Database=$database;Encrypt=$encrypt;TrustServerCertificate=$trustCert";
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        return $pdo;
    } catch (Exception $e) {
        die("Error de conexión SQL Server: " . $e->getMessage());
    }
}
?>
