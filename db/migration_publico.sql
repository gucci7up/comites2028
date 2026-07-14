-- Migración: enlace público de registro de comités
-- Correr manualmente contra la base de datos de producción antes de desplegar
-- comite_publico.php / api/publico_guardar_comite.php.

ALTER TABLE comites
    ADD COLUMN origen ENUM('interno','publico') NOT NULL DEFAULT 'interno' AFTER estado;

CREATE TABLE IF NOT EXISTS intentos_publico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_creado (ip, creado)
);
