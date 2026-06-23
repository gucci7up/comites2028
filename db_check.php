<?php
// Script para verificar la existencia de la base de datos

// Configuración de la base de datos
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'comites_prm';

// Función para verificar si la base de datos existe
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

// Verificar si la base de datos existe
$dbExists = verificarBaseDatos($host, $user, $password, $dbname);

if (!$dbExists) {
    // Redirigir a la página de configuración
    header("Location: db_setup.html");
    exit;
} else {
    // Redirigir a la página de login
    header("Location: login.php");
    exit;
}
?>