-- Base de datos para el sistema de comités afectivos

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    rol ENUM('admin', 'usuario') DEFAULT 'usuario',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso DATETIME,
    activo BOOLEAN DEFAULT TRUE
);

-- Tabla de comités
CREATE TABLE IF NOT EXISTS comites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    provincia VARCHAR(100),
    municipio VARCHAR(100) NOT NULL,
    circunscripcion VARCHAR(100),
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    creado_por INT,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    FOREIGN KEY (creado_por) REFERENCES usuarios(id)
);

-- Tabla de coordinadores
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

-- Tabla de miembros
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

-- Tabla de relación entre comités y coordinadores
CREATE TABLE IF NOT EXISTS comite_coordinador (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comite_id INT NOT NULL,
    coordinador_id INT NOT NULL,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (comite_id) REFERENCES comites(id) ON DELETE CASCADE,
    FOREIGN KEY (coordinador_id) REFERENCES coordinadores(id) ON DELETE CASCADE,
    UNIQUE KEY (comite_id, coordinador_id)
);

-- Tabla de relación entre comités y miembros
CREATE TABLE IF NOT EXISTS comite_miembro (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comite_id INT NOT NULL,
    miembro_id INT NOT NULL,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (comite_id) REFERENCES comites(id) ON DELETE CASCADE,
    FOREIGN KEY (miembro_id) REFERENCES miembros(id) ON DELETE CASCADE,
    UNIQUE KEY (comite_id, miembro_id)
);

-- Insertar usuario administrador por defecto
INSERT INTO usuarios (nombre, usuario, password, email, rol) VALUES
('Administrador', 'admin', '$2y$10$Eaeut9oONLbcrveqy0/0T.siqryvF5b0CMSgp6b6fJ2E10pmSga7y', 'admin@example.com', 'admin');
-- Nota: La contraseña es 'Gucci1826' hasheada con bcrypt