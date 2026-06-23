<?php
/*----------------------------------------------------------
  API – Consulta de cédula       (GET api/consulta.php?cedula=123)
  ---------------------------------------------------------*/
header('Content-Type: application/json; charset=utf-8');

/* 1. Validar parámetro */
if (empty($_GET['cedula'])) {
    echo json_encode(['success' => false, 'message' => 'Parámetro "cedula" requerido']);
    exit;
}

$cedula = preg_replace('/[^0-9]/', '', $_GET['cedula']);   // Sanitizar

/* 2. Conexión */
require_once __DIR__ . '/../db/config.php';

try {
    $pdo = conectarSQLServer();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

/* 3. Consulta con *prepared statement* para evitar inyección SQL */
$sql = "
SELECT
              dbPadronFeb2024.dbo.Padron.nombres,
              dbPadronFeb2024.dbo.Padron.apellido1,
              dbPadronFeb2024.dbo.Padron.apellido2,
              dbPadronFeb2024.dbo.Padron.Cedula,
              dbPadronFeb2024.dbo.Padron.FechaNacimiento,
              dbPadronFeb2024.dbo.Padron.IdSexo,
              dbPadronFeb2024.dbo.Municipio.Descripcion AS Municipio,
              dbPadronFeb2024.dbo.Colegio.Descripcion AS Recinto,
              CodigoRecinto,
              dbPadronFeb2024.dbo.Colegio.CodigoColegio,
              dbPadronFeb2024.dbo.Colegio.CantidadInscritos,
              dbPadronFeb2024.dbo.Circunscripcion.Descripcion AS Circunscripcion,
              dbPRM.dbo.FOTOS_PRM_PRM.Imagen
          FROM
              [dbPadronFeb2024].[dbo].[Padron]
          INNER JOIN
              [dbPadronFeb2024].[dbo].[Municipio] ON [dbPadronFeb2024].[dbo].[Padron].[IdMunicipio] = [dbPadronFeb2024].[dbo].[Municipio].[ID]
          INNER JOIN
              [dbPadronFeb2024].[dbo].[Colegio] ON [dbPadronFeb2024].[dbo].[Padron].[IdColegio] = [dbPadronFeb2024].[dbo].[Colegio].[IDColegio]
          INNER JOIN
              [dbPadronFeb2024].[dbo].[Circunscripcion] ON [dbPadronFeb2024].[dbo].[Padron].[CodigoCircunscripcion] = [dbPadronFeb2024].[dbo].[Circunscripcion].[ID]
          INNER JOIN
              [dbPRM].[dbo].[FOTOS_PRM_PRM] ON [dbPadronFeb2024].[dbo].[Padron].[Cedula] = [dbPRM].[dbo].[FOTOS_PRM_PRM].[Cedula]
          WHERE
             [dbPadronFeb2024].[dbo].[Padron].[Cedula] = :cedula";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':cedula', $cedula, PDO::PARAM_STR);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Cédula no encontrada']);
    exit;
}

/* 4. Preparar salida */
$row['Imagen'] = base64_encode($row['Imagen']);  // Para que el front pueda pintarla rápido
echo json_encode(['success' => true, 'data' => $row]);
