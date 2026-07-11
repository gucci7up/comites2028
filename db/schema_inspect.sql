/*----------------------------------------------------------
  Script de inspección de esquema — dbPadronFeb2024
  Ejecutar en SSMS conectado al servidor 148.0.129.233,1433
  Objetivo: conocer columnas exactas y relaciones (FK) entre
  Padron y las tablas: Sexo, EstadoCivil, Nacionalidad,
  Ocupacion, Provincia, Recinto, Zona, CiudadSeccion,
  Circunscripcion, Municipio, Colegio.
  No modifica datos, solo lectura de metadatos.
----------------------------------------------------------*/

USE dbPadronFeb2024;
GO

-- 1) Todas las columnas de Padron (para ver los campos IdSexo, IdEstadoCivil, etc.)
SELECT
    c.COLUMN_NAME,
    c.DATA_TYPE,
    c.ORDINAL_POSITION
FROM INFORMATION_SCHEMA.COLUMNS c
WHERE c.TABLE_NAME = 'Padron'
ORDER BY c.ORDINAL_POSITION;
GO

-- 2) Columnas de cada tabla relacionada que queremos agregar al SELECT
SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, ORDINAL_POSITION
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME IN ('Sexo','EstadoCivil','Nacionalidad','Ocupacion','Provincia','Recinto','Zona','CiudadSeccion','Circunscripcion','Municipio','Colegio')
ORDER BY TABLE_NAME, ORDINAL_POSITION;
GO

-- 3) Relaciones (Foreign Keys) definidas desde Padron hacia esas tablas
--    Esto muestra la columna FK en Padron y la columna PK en la tabla referenciada
SELECT
    fk.name                     AS ForeignKey,
    tp.name                     AS TablaOrigen,
    cp.name                     AS ColumnaOrigen,
    tr.name                     AS TablaReferenciada,
    cr.name                     AS ColumnaReferenciada
FROM sys.foreign_keys fk
INNER JOIN sys.foreign_key_columns fkc ON fkc.constraint_object_id = fk.object_id
INNER JOIN sys.tables tp ON tp.object_id = fkc.parent_object_id
INNER JOIN sys.columns cp ON cp.object_id = tp.object_id AND cp.column_id = fkc.parent_column_id
INNER JOIN sys.tables tr ON tr.object_id = fkc.referenced_object_id
INNER JOIN sys.columns cr ON cr.object_id = tr.object_id AND cr.column_id = fkc.referenced_column_id
WHERE tp.name = 'Padron'
ORDER BY tr.name;
GO

-- 4) Si Padron NO tiene FKs formales (común cuando las relaciones son "por convención"),
--    esto muestra un registro de ejemplo completo para inspeccionar visualmente los valores
--    y deducir a qué tabla apunta cada columna Id/Codigo.
SELECT TOP 1 * FROM Padron;
GO
