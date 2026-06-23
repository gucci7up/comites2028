-- Migration v3: simplificación de roles, un servidor por partido

-- 1. Tabla configuracion (fila única para este servidor)
CREATE TABLE IF NOT EXISTS configuracion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_partido VARCHAR(100) DEFAULT 'Mi Partido',
    siglas VARCHAR(20) DEFAULT 'MP',
    logo LONGBLOB,
    color_primario VARCHAR(7) DEFAULT '#2563eb',
    color_sidebar  VARCHAR(7) DEFAULT '#0d1b2a',
    color_accent   VARCHAR(7) DEFAULT '#3b82f6'
);
INSERT INTO configuracion (nombre_partido, siglas)
SELECT 'Mi Partido','MP' WHERE NOT EXISTS (SELECT 1 FROM configuracion);

-- 2. Tabla candidatos (sin partido_id, es por servidor)
CREATE TABLE IF NOT EXISTS candidatos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    cargo ENUM('presidente','senador','diputado','alcalde','regidor') NOT NULL,
    descripcion VARCHAR(150) NULL,
    foto LONGBLOB,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE candidatos ADD COLUMN IF NOT EXISTS descripcion VARCHAR(150) NULL AFTER cargo;
ALTER TABLE candidatos DROP FOREIGN KEY IF EXISTS candidatos_ibfk_1;
ALTER TABLE candidatos DROP COLUMN IF EXISTS partido_id;

-- 3. Simplificar roles de usuarios
ALTER TABLE usuarios
    MODIFY COLUMN rol ENUM('admin','supervisor','digitador') DEFAULT 'digitador';
ALTER TABLE usuarios DROP FOREIGN KEY IF EXISTS fk_usuario_partido;
ALTER TABLE usuarios DROP COLUMN IF EXISTS partido_id;

-- 4. candidato_id en comites
ALTER TABLE comites ADD COLUMN IF NOT EXISTS candidato_id INT NULL;

-- 5. Actualizar admin existente
UPDATE usuarios SET rol = 'admin' WHERE usuario = 'admin';

-- 6. Eliminar tabla partidos si existe
DROP TABLE IF EXISTS partidos;
