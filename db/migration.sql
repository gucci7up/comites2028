-- Migration para base de datos existente
-- Ejecutar en Dokploy si ya tienes datos

-- 1. Tabla partidos
CREATE TABLE IF NOT EXISTS partidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    siglas VARCHAR(20),
    logo LONGBLOB,
    color_primario VARCHAR(7) DEFAULT '#2563eb',
    color_sidebar  VARCHAR(7) DEFAULT '#0d1b2a',
    color_accent   VARCHAR(7) DEFAULT '#3b82f6',
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO partidos (nombre, siglas, color_primario, color_sidebar, color_accent) VALUES
('Partido Revolucionario Moderno',     'PRM', '#2563eb', '#0d1b2a', '#3b82f6'),
('Partido de la Liberación Dominicana','PLD', '#7c3aed', '#1e1035', '#8b5cf6'),
('Fuerza del Pueblo',                  'FDP', '#16a34a', '#0a1f12', '#22c55e');

-- 2. Tabla candidatos
CREATE TABLE IF NOT EXISTS candidatos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partido_id INT NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    cargo ENUM('presidente','senador','diputado','alcalde','regidor') NOT NULL,
    descripcion VARCHAR(150) NULL,
    foto LONGBLOB,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (partido_id) REFERENCES partidos(id) ON DELETE CASCADE
);
-- Si candidatos ya existe, agregar columna descripcion
ALTER TABLE candidatos ADD COLUMN IF NOT EXISTS descripcion VARCHAR(150) NULL AFTER cargo;

-- 3. Actualizar usuarios (nuevos roles y partido_id)
ALTER TABLE usuarios
    MODIFY COLUMN rol ENUM('superadmin','owner','admin','usuario') DEFAULT 'usuario',
    ADD COLUMN IF NOT EXISTS partido_id INT NULL,
    ADD CONSTRAINT fk_usuario_partido FOREIGN KEY (partido_id) REFERENCES partidos(id) ON DELETE SET NULL;

-- 4. Actualizar comites (candidato_id)
ALTER TABLE comites
    ADD COLUMN IF NOT EXISTS candidato_id INT NULL,
    ADD CONSTRAINT fk_comite_candidato FOREIGN KEY (candidato_id) REFERENCES candidatos(id) ON DELETE SET NULL;

-- 5. Promover admin existente a superadmin
UPDATE usuarios SET rol = 'superadmin' WHERE usuario = 'admin' LIMIT 1;
