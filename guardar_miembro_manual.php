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

$conn = conectarDB();

try {
    // Verificar si el comité existe
    $stmt = $conn->prepare("SELECT id FROM comites WHERE id = ?");
    $stmt->bind_param("i", $comite_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    if (!$resultado->fetch_assoc()) {
        throw new Exception("El comité no existe.");
    }
    
    // Verificar si el miembro ya existe en este comité
    $stmt = $conn->prepare("SELECT m.id FROM miembros m 
                          JOIN comite_miembro cm ON m.id = cm.miembro_id 
                          WHERE cm.comite_id = ? AND m.cedula = ?");
    $stmt->bind_param("is", $comite_id, $cedula);
    $stmt->execute();
    $resultado = $stmt->get_result();
    if ($resultado->fetch_assoc()) {
        throw new Exception("Esta persona ya es miembro de este comité.");
    }
    
    // Insertar el nuevo miembro
    $stmt = $conn->prepare("INSERT INTO miembros (cedula, nombre_completo, telefono, email, municipio, recinto, colegio, fecha_registro) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssssss", $cedula, $nombre, $telefono, $email, $municipio, $recinto, $colegio);
    $stmt->execute();
    
    // Obtener el ID del miembro recién insertado
    $miembro_id = $conn->insert_id;
    
    // Crear la relación entre el comité y el miembro
    $stmt = $conn->prepare("INSERT INTO comite_miembro (comite_id, miembro_id, fecha_asignacion) VALUES (?, ?, NOW())");
    $stmt->bind_param("ii", $comite_id, $miembro_id);
    $stmt->execute();
    
    // Redirigir con mensaje de éxito
    $_SESSION['mensaje'] = "Miembro agregado correctamente.";
    $_SESSION['tipo_mensaje'] = "success";
    
} catch (Exception $e) {
    // Redirigir con mensaje de error
    $_SESSION['mensaje'] = "Error al agregar el miembro: " . $e->getMessage();
    $_SESSION['tipo_mensaje'] = "danger";
}

header("Location: editar_comite.php?id=$comite_id#miembros");
exit;