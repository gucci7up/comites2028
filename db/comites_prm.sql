-- Sistema de Comités Afectivos — PRM
-- Esquema completo v2

-- ── PARTIDOS ──────────────────────────────────────────────────────
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

-- ── CANDIDATOS ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS candidatos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partido_id INT NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    cargo ENUM('presidente','senador','diputado','alcalde','regidor') NOT NULL,
    foto LONGBLOB,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (partido_id) REFERENCES partidos(id) ON DELETE CASCADE
);

-- ── USUARIOS ──────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    rol ENUM('superadmin','owner','admin','usuario') DEFAULT 'usuario',
    partido_id INT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso DATETIME,
    activo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (partido_id) REFERENCES partidos(id) ON DELETE SET NULL
);

-- ── COMITÉS ───────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS comites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    provincia VARCHAR(100),
    municipio VARCHAR(100) NOT NULL,
    circunscripcion VARCHAR(100),
    candidato_id INT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    creado_por INT,
    estado ENUM('activo','inactivo') DEFAULT 'activo',
    FOREIGN KEY (candidato_id) REFERENCES candidatos(id) ON DELETE SET NULL,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id)
);

-- ── COORDINADORES ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS coordinadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cedula VARCHAR(20) NOT NULL UNIQUE,
    nombre_completo VARCHAR(150) NOT NULL,
    telefono VARCHAR(20),
    email VARCHAR(100),
    municipio VARCHAR(100),
    recinto VARCHAR(150),
    colegio VARCHAR(50),
    foto LONGBLOB,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── MIEMBROS ──────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS miembros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cedula VARCHAR(20) NOT NULL,
    nombre_completo VARCHAR(150) NOT NULL,
    telefono VARCHAR(20),
    email VARCHAR(100),
    municipio VARCHAR(100),
    recinto VARCHAR(150),
    colegio VARCHAR(50),
    foto LONGBLOB,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── RELACIONES ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS comite_coordinador (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comite_id INT NOT NULL,
    coordinador_id INT NOT NULL,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (comite_id) REFERENCES comites(id) ON DELETE CASCADE,
    FOREIGN KEY (coordinador_id) REFERENCES coordinadores(id) ON DELETE CASCADE,
    UNIQUE KEY (comite_id, coordinador_id)
);

CREATE TABLE IF NOT EXISTS comite_miembro (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comite_id INT NOT NULL,
    miembro_id INT NOT NULL,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (comite_id) REFERENCES comites(id) ON DELETE CASCADE,
    FOREIGN KEY (miembro_id) REFERENCES miembros(id) ON DELETE CASCADE,
    UNIQUE KEY (comite_id, miembro_id)
);

-- ── PARTIDOS PREDEFINIDOS ─────────────────────────────────────────
INSERT INTO partidos (nombre, siglas, color_primario, color_sidebar, color_accent) VALUES
('Partido Revolucionario Moderno', 'PRM', '#2563eb', '#0d1b2a', '#3b82f6'),
('Partido de la Liberación Dominicana', 'PLD', '#7c3aed', '#1e1035', '#8b5cf6'),
('Fuerza del Pueblo', 'FDP', '#16a34a', '#0a1f12', '#22c55e');

-- ── SUPER ADMIN ───────────────────────────────────────────────────
INSERT INTO usuarios (nombre, usuario, password, email, rol) VALUES
('Super Administrador', 'superadmin', '$2y$10$Eaeut9oONLbcrveqy0/0T.siqryvF5b0CMSgp6b6fJ2E10pmSga7y', 'admin@sistema.com', 'superadmin');
-- Contraseña: Gucci1826
