<?php
// Script para inicializar la base de datos

// Configuración de la base de datos
$host     = getenv('MYSQL_HOST') ?: 'localhost';
$user     = getenv('MYSQL_USER') ?: 'root';
$password = getenv('MYSQL_PASS') ?: '';

// Conectar a MySQL sin seleccionar base de datos
$conn = new mysqli($host, $user, $password);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

echo "<h2>Configuración de la base de datos</h2>";

// Crear la base de datos si no existe
$sql = "CREATE DATABASE IF NOT EXISTS comites_prm";
if ($conn->query($sql) === TRUE) {
    echo "<p>Base de datos 'comites_prm' creada o ya existente.</p>";
} else {
    echo "<p>Error al crear la base de datos: " . $conn->error . "</p>";
    exit;
}

// Seleccionar la base de datos
$conn->select_db("comites_prm");

// Leer el archivo SQL
$sqlFile = file_get_contents(__DIR__ . '/comites_prm.sql');

// Dividir el archivo en consultas individuales
$queries = explode(';', $sqlFile);

// Ejecutar cada consulta
$success = true;
foreach ($queries as $query) {
    $query = trim($query);
    if (empty($query)) continue;
    
    if ($conn->query($query) !== TRUE) {
        echo "<p>Error al ejecutar consulta: " . $conn->error . "</p>";
        $success = false;
    }
}

if ($success) {
    echo "<p>Base de datos inicializada correctamente.</p>";
    echo "<p>Usuario administrador creado:</p>";
    echo "<ul>";
    echo "<li><strong>Usuario:</strong> admin</li>";
    echo "<li><strong>Contraseña:</strong> admin123</li>";
    echo "</ul>";
} else {
    echo "<p>Hubo errores al inicializar la base de datos.</p>";
}

// Cerrar conexión
$conn->close();

echo "<p><a href='../login.php' class='btn btn-primary'>Ir al Login</a></p>";
?>