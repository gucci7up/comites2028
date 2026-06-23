<?php
require_once 'includes/auth.php';
require_once 'includes/funciones.php';
require_once 'db/config.php';

// Verificar que el usuario esté autenticado
requiereAutenticacion();

// Verificar que se recibió el ID del comité
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['comite_id'])) {
    header('Location: comites.php');
    exit;
}

$comite_id = $_POST['comite_id'];
$conn = conectarDB();

try {
    // Iniciar transacción
    $conn->begin_transaction();
    
    // Verificar si el comité existe y obtener el ID del coordinador
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
    
    if (!$comite['coordinador_id']) {
        throw new Exception("El comité no tiene un coordinador asignado.");
    }
    
    // Eliminar la relación en comite_coordinador
    $stmt = $conn->prepare("DELETE FROM comite_coordinador WHERE comite_id = ?");
    $stmt->bind_param("i", $comite_id);
    $stmt->execute();
    
    // Actualizar el comité para registrar la modificación
    $stmt = $conn->prepare("UPDATE comites SET fecha_modificacion = NOW() WHERE id = ?");
    $stmt->bind_param("i", $comite_id);
    $stmt->execute();
    
    // Confirmar transacción
    $conn->commit();
    
    // Redirigir con mensaje de éxito
    $_SESSION['mensaje'] = "Coordinador removido correctamente.";
    $_SESSION['tipo_mensaje'] = "success";
    
} catch (Exception $e) {
    // Revertir cambios en caso de error
    $conn->rollback();
    
    // Redirigir con mensaje de error
    $_SESSION['mensaje'] = "Error al remover el coordinador: " . $e->getMessage();
    $_SESSION['tipo_mensaje'] = "danger";
}

header("Location: editar_comite.php?id=$comite_id");
exit;