<?php
require_once 'includes/auth.php';
require_once 'includes/funciones.php';
require_once 'db/config.php';

// Verificar que el usuario esté autenticado
requiereAutenticacion();

// Verificar que se recibieron los datos necesarios
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['comite_id']) || !isset($_POST['cedula']) || !isset($_POST['nombre'])) {
    header('Location: comites.php');
    exit;
}

$comite_id = $_POST['comite_id'];
$cedula = $_POST['cedula'];
$nombre = $_POST['nombre'];
$telefono = $_POST['telefono'] ?? '';
$email = $_POST['email'] ?? '';
$municipio = $_POST['municipio'] ?? '';
$recinto = $_POST['recinto'] ?? '';
$colegio = $_POST['colegio'] ?? '';
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
$foto = $_POST['foto'] ?? null;

$conn = conectarDB();

try {
    // Iniciar transacción
    $conn->begin_transaction();
    
    // Verificar si el comité existe
    $stmt = $conn->prepare("SELECT c.id, cc.coordinador_id 
                          FROM comites c 
                          LEFT JOIN comite_coordinador cc ON c.id = cc.comite_id 
                          WHERE c.id = ?");
    $stmt->bind_param("i", $comite_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $comite = $resultado->fetch_assoc();
    
    if (!$comite) {
        throw new Exception("El comité no existe.");
    }
    
    // Si el comité ya tiene un coordinador, eliminar la relación
    if ($comite['coordinador_id']) {
        $stmt = $conn->prepare("DELETE FROM comite_coordinador WHERE comite_id = ?");
        $stmt->bind_param("i", $comite_id);
        $stmt->execute();
    }
    
    // Insertar el nuevo coordinador
    $stmt = $conn->prepare("INSERT INTO coordinadores (cedula, nombre, telefono, email, municipio, recinto, colegio, fecha_nacimiento, foto) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssss", $cedula, $nombre, $telefono, $email, $municipio, $recinto, $colegio, $fecha_nacimiento, $foto);
    $stmt->execute();
    
    $coordinador_id = $conn->insert_id;
    
    // Crear la relación entre comité y coordinador
    $stmt = $conn->prepare("INSERT INTO comite_coordinador (comite_id, coordinador_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $comite_id, $coordinador_id);
    $stmt->execute();
    
    // Actualizar la fecha de modificación del comité
    $stmt = $conn->prepare("UPDATE comites SET fecha_modificacion = NOW() WHERE id = ?");
    $stmt->bind_param("i", $comite_id);
    $stmt->execute();
    
    // Confirmar transacción
    $conn->commit();
    
    // Redirigir con mensaje de éxito
    $_SESSION['mensaje'] = "Coordinador asignado correctamente.";
    $_SESSION['tipo_mensaje'] = "success";
    
} catch (Exception $e) {
    // Revertir cambios en caso de error
    $conn->rollback();
    
    // Redirigir con mensaje de error
    $_SESSION['mensaje'] = "Error al asignar el coordinador: " . $e->getMessage();
    $_SESSION['tipo_mensaje'] = "danger";
}

header("Location: editar_comite.php?id=$comite_id");
exit;