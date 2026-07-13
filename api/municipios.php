<?php
/*----------------------------------------------------------
  API – Municipios de una provincia
  (GET api/municipios.php?provincia_id=32)
  ---------------------------------------------------------*/
header('Content-Type: application/json; charset=utf-8');

if (empty($_GET['provincia_id']) || !ctype_digit((string) $_GET['provincia_id'])) {
    echo json_encode(['success' => false, 'message' => 'Parámetro "provincia_id" requerido']);
    exit;
}
$provinciaId = (int) $_GET['provincia_id'];

require_once __DIR__ . '/../db/config.php';

try {
    $pdo = conectarSQLServer();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT ID, Descripcion FROM [dbPadronFeb2024].[dbo].[Municipio] WHERE IDProvincia = :pid ORDER BY Descripcion");
    $stmt->bindParam(':pid', $provinciaId, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $rows]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al consultar municipios']);
}
