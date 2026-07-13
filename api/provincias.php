<?php
/*----------------------------------------------------------
  API – Listado de provincias    (GET api/provincias.php)
  ---------------------------------------------------------*/
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db/config.php';

try {
    $pdo = conectarSQLServer();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

try {
    $stmt = $pdo->query("SELECT ID, Descripcion FROM [dbPadronFeb2024].[dbo].[Provincia] ORDER BY Descripcion");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $rows]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al consultar provincias']);
}
