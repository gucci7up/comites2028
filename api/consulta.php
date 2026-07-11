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
              P.nombres,
              P.apellido1,
              P.apellido2,
              P.Cedula,
              P.FechaNacimiento,
              P.IdSexo,
              Mun.Descripcion AS Municipio,
              Col.Descripcion AS Recinto,
              P.CodigoRecinto,
              Col.CodigoColegio,
              Col.CantidadInscritos,
              Cir.Descripcion AS Circunscripcion,
              Sex.Descripcion AS SexoDescripcion,
              EC.Descripcion AS EstadoCivil,
              Nac.Descripcion AS Nacionalidad,
              Prov.Descripcion AS Provincia,
              Zon.Descripcion AS Zona,
              Rec.Descripcion AS RecintoVotacion,
              Foto.Imagen
          FROM
              [dbPadronFeb2024].[dbo].[Padron] P
          INNER JOIN
              [dbPadronFeb2024].[dbo].[Municipio] Mun ON P.IdMunicipio = Mun.ID
          INNER JOIN
              [dbPadronFeb2024].[dbo].[Colegio] Col ON P.IdColegio = Col.IDColegio
          INNER JOIN
              [dbPadronFeb2024].[dbo].[Circunscripcion] Cir ON P.CodigoCircunscripcion = Cir.ID
          INNER JOIN
              [dbPRM].[dbo].[FOTOS_PRM_PRM] Foto ON P.Cedula = Foto.Cedula
          LEFT JOIN
              [dbPadronFeb2024].[dbo].[Sexo] Sex ON P.IdSexo = Sex.IdSexo
          LEFT JOIN
              [dbPadronFeb2024].[dbo].[EstadoCivil] EC ON P.IdEstadoCivil = EC.Id
          LEFT JOIN
              [dbPadronFeb2024].[dbo].[Nacionalidad] Nac ON P.IdNacionalidad = Nac.ID
          LEFT JOIN
              [dbPadronFeb2024].[dbo].[Provincia] Prov ON P.IdProvincia = Prov.ID
          LEFT JOIN
              [dbPadronFeb2024].[dbo].[Zona] Zon ON Prov.ZONA = Zon.ID
          LEFT JOIN
              [dbPadronFeb2024].[dbo].[Recinto] Rec ON P.CodigoRecinto = Rec.CodigoRecinto
          WHERE
             P.Cedula = :cedula";

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
