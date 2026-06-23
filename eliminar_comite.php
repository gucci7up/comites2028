<?php
require_once 'includes/auth.php';
require_once 'includes/funciones.php';
require_once 'db/config.php';

// Verificar que el usuario esté autenticado
requiereAutenticacion();

// Verificar que se recibió un ID válido por POST
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    header('Location: comites.php');
    exit;
}

$comite_id = $_POST['id'];
$conn = conectarDB();

// Verificar que el comité existe
$stmt = $conn->prepare("SELECT id FROM comites WHERE id = ?");
$stmt->bind_param("i", $comite_id);
$stmt->execute();
$resultado = $stmt->get_result();
if (!$resultado->fetch_assoc()) {
    header('Location: comites.php');
    exit;
}

try {
    // Iniciar transacción
    $conn->begin_transaction();
    
    // Eliminar relaciones de miembros asociados al comité
    $stmt = $conn->prepare("DELETE FROM comite_miembro WHERE comite_id = ?");
    $stmt->bind_param("i", $comite_id);
    $stmt->execute();
    
    // Obtener el ID del coordinador para eliminarlo después
    $stmt = $conn->prepare("SELECT coordinador_id FROM comite_coordinador WHERE comite_id = ?");
    $stmt->bind_param("i", $comite_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $fila = $resultado->fetch_assoc();
    $coordinador_id = $fila ? $fila['coordinador_id'] : null;
    
    // Eliminar relación de coordinador
    $stmt = $conn->prepare("DELETE FROM comite_coordinador WHERE comite_id = ?");
    $stmt->bind_param("i", $comite_id);
    $stmt->execute();
    
    // Eliminar el comité
    $stmt = $conn->prepare("DELETE FROM comites WHERE id = ?");
    $stmt->bind_param("i", $comite_id);
    $stmt->execute();
    
    // Si hay un coordinador asociado, eliminarlo
    if ($coordinador_id) {
        $stmt = $conn->prepare("DELETE FROM coordinadores WHERE id = ?");
        $stmt->bind_param("i", $coordinador_id);
        $stmt->execute();
    }
    
    // Confirmar transacción
    $conn->commit();
    
    // Redirigir con mensaje de éxito
    $_SESSION['mensaje'] = "Comité eliminado correctamente.";
    $_SESSION['tipo_mensaje'] = "success";
    
} catch (Exception $e) {
    // Revertir cambios en caso de error
    $conn->rollback();
    
    // Redirigir con mensaje de error
    $_SESSION['mensaje'] = "Error al eliminar el comité: " . $e->getMessage();
    $_SESSION['tipo_mensaje'] = "danger";
}

header('Location: comites.php');
exit;