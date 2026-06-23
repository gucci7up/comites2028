<?php
/**
 * Archivo para obtener estadísticas del sistema
 * Utilizado en el dashboard para mostrar información resumida
 */

require_once 'db/config.php';

/**
 * Obtiene el total de comités registrados en el sistema
 * @return int Número total de comités
 */
function obtenerTotalComites() {
    $conn = conectarDB();
    $stmt = $conn->query("SELECT COUNT(*) as total FROM comites");
    $resultado = $stmt->fetch_assoc();
    return $resultado['total'];
}

/**
 * Obtiene el total de miembros registrados en el sistema
 * @return int Número total de miembros
 */
function obtenerTotalMiembros() {
    $conn = conectarDB();
    $stmt = $conn->query("SELECT COUNT(*) as total FROM miembros");
    $resultado = $stmt->fetch_assoc();
    return $resultado['total'];
}

/**
 * Obtiene el total de coordinadores registrados en el sistema
 * @return int Número total de coordinadores
 */
function obtenerTotalCoordinadores() {
    $conn = conectarDB();
    $stmt = $conn->query("SELECT COUNT(*) as total FROM coordinadores");
    $resultado = $stmt->fetch_assoc();
    return $resultado['total'];
}

/**
 * Obtiene el total de usuarios registrados en el sistema
 * @return int Número total de usuarios
 */
function obtenerTotalUsuarios() {
    $conn = conectarDB();
    $stmt = $conn->query("SELECT COUNT(*) as total FROM usuarios");
    $resultado = $stmt->fetch_assoc();
    return $resultado['total'];
}

/**
 * Obtiene los comités creados recientemente
 * @param int $limite Número de comités a obtener
 * @return array Arreglo con los comités recientes
 */
function obtenerComitesRecientes($limite = 5) {
    $conn = conectarDB();
    $stmt = $conn->prepare(
        "SELECT c.*, u.nombre as creador_nombre 
         FROM comites c 
         LEFT JOIN usuarios u ON c.creado_por = u.id 
         ORDER BY c.fecha_creacion DESC 
         LIMIT ?"
    );
    $stmt->bind_param("i", $limite);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $comites = [];
    while ($fila = $resultado->fetch_assoc()) {
        $comites[] = $fila;
    }
    return $comites;
}

/**
 * Obtiene los miembros agregados recientemente
 * @param int $limite Número de miembros a obtener
 * @return array Arreglo con los miembros recientes
 */
function obtenerMiembrosRecientes($limite = 5) {
    $conn = conectarDB();
    $stmt = $conn->prepare(
        "SELECT m.*, c.nombre as comite_nombre 
         FROM miembros m 
         LEFT JOIN comite_miembro cm ON m.id = cm.miembro_id
         LEFT JOIN comites c ON cm.comite_id = c.id 
         ORDER BY m.fecha_registro DESC 
         LIMIT ?"
    );
    $stmt->bind_param("i", $limite);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $miembros = [];
    while ($fila = $resultado->fetch_assoc()) {
        $miembros[] = $fila;
    }
    return $miembros;
}

/**
 * Obtiene estadísticas de comités por municipio
 * @param int $limite Número de municipios a obtener
 * @return array Arreglo con estadísticas por municipio
 */
function obtenerEstadisticasPorMunicipio($limite = 10) {
    $conn = conectarDB();
    $stmt = $conn->prepare(
        "SELECT municipio, COUNT(*) as total 
         FROM comites 
         GROUP BY municipio 
         ORDER BY total DESC 
         LIMIT ?"
    );
    $stmt->bind_param("i", $limite);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $estadisticas = [];
    while ($fila = $resultado->fetch_assoc()) {
        $estadisticas[] = $fila;
    }
    return $estadisticas;
}

/**
 * Obtiene estadísticas de comités creados por mes
 * @param int $meses Número de meses a considerar
 * @return array Arreglo con estadísticas por mes
 */
function obtenerEstadisticasPorMes($meses = 6) {
    $conn = conectarDB();
    $stmt = $conn->prepare(
        "SELECT 
            DATE_FORMAT(fecha_creacion, '%Y-%m') as mes, 
            COUNT(*) as total 
         FROM comites 
         WHERE fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL ? MONTH) 
         GROUP BY DATE_FORMAT(fecha_creacion, '%Y-%m') 
         ORDER BY mes ASC"
    );
    $stmt->bind_param("i", $meses);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $estadisticas = [];
    while ($fila = $resultado->fetch_assoc()) {
        $estadisticas[] = $fila;
    }
    return $estadisticas;
}

/**
 * Obtiene estadísticas de comités creados por usuario
 * @param int $limite Número de usuarios a obtener
 * @return array Arreglo con estadísticas por usuario
 */
function obtenerEstadisticasPorUsuario($limite = 5) {
    $conn = conectarDB();
    $stmt = $conn->prepare(
        "SELECT u.nombre, COUNT(c.id) as total 
         FROM comites c 
         JOIN usuarios u ON c.creado_por = u.id 
         GROUP BY c.creado_por 
         ORDER BY total DESC 
         LIMIT ?"
    );
    $stmt->bind_param("i", $limite);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $estadisticas = [];
    while ($fila = $resultado->fetch_assoc()) {
        $estadisticas[] = $fila;
    }
    return $estadisticas;
}