<?php
/**
 * Funciones para el manejo de comités y miembros
 */

require_once __DIR__ . '/../db/config.php';

/**
 * Obtiene todos los comités de la base de datos
 */
function obtenerComites() {
    $conexion = conectarDB();
    $sql = "SELECT c.*, u.nombre as creador_nombre, 
           (SELECT COUNT(*) FROM comite_miembro WHERE comite_id = c.id) as total_miembros 
           FROM comites c 
           LEFT JOIN usuarios u ON c.creado_por = u.id 
           ORDER BY c.fecha_creacion DESC";
    $resultado = $conexion->query($sql);
    
    $comites = [];
    if ($resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            $comites[] = $fila;
        }
    }
    
    $conexion->close();
    return $comites;
}

/**
 * Obtiene un comité por su ID
 */
function obtenerComitePorId($id) {
    $conexion = conectarDB();
    $id = (int)$id;
    
    $sql = "SELECT c.*, u.nombre as creador_nombre 
           FROM comites c 
           LEFT JOIN usuarios u ON c.creado_por = u.id 
           WHERE c.id = $id";
    $resultado = $conexion->query($sql);
    
    if ($resultado->num_rows == 1) {
        $comite = $resultado->fetch_assoc();
        $conexion->close();
        return $comite;
    }
    
    $conexion->close();
    return null;
}

/**
 * Crea un nuevo comité
 */
function crearComite($nombre, $provincia, $municipio, $circunscripcion, $usuario_id) {
    $conexion = conectarDB();
    
    $nombre = sanitizar($conexion, $nombre);
    $provincia = sanitizar($conexion, $provincia);
    $municipio = sanitizar($conexion, $municipio);
    $circunscripcion = sanitizar($conexion, $circunscripcion);
    $usuario_id = (int)$usuario_id;
    
    $sql = "INSERT INTO comites (nombre, provincia, municipio, circunscripcion, creado_por) 
           VALUES ('$nombre', '$provincia', '$municipio', '$circunscripcion', $usuario_id)";
    
    if ($conexion->query($sql)) {
        $id = $conexion->insert_id;
        $conexion->close();
        return $id;
    }
    
    $conexion->close();
    return false;
}

/**
 * Actualiza un comité existente
 */
function actualizarComite($id, $nombre, $provincia, $municipio, $circunscripcion, $estado) {
    $conexion = conectarDB();
    
    $id = (int)$id;
    $nombre = sanitizar($conexion, $nombre);
    $provincia = sanitizar($conexion, $provincia);
    $municipio = sanitizar($conexion, $municipio);
    $circunscripcion = sanitizar($conexion, $circunscripcion);
    $estado = sanitizar($conexion, $estado);
    
    $sql = "UPDATE comites SET 
           nombre = '$nombre', 
           provincia = '$provincia', 
           municipio = '$municipio', 
           circunscripcion = '$circunscripcion', 
           estado = '$estado' 
           WHERE id = $id";
    
    $resultado = $conexion->query($sql);
    $conexion->close();
    
    return $resultado;
}

/**
 * Elimina un comité
 */
function eliminarComite($id) {
    $conexion = conectarDB();
    $id = (int)$id;
    
    $sql = "DELETE FROM comites WHERE id = $id";
    $resultado = $conexion->query($sql);
    
    $conexion->close();
    return $resultado;
}

/**
 * Obtiene los miembros de un comité
 */
function obtenerMiembrosComite($comite_id) {
    $conexion = conectarDB();
    $comite_id = (int)$comite_id;
    
    $sql = "SELECT m.*, cm.fecha_asignacion 
           FROM miembros m 
           INNER JOIN comite_miembro cm ON m.id = cm.miembro_id 
           WHERE cm.comite_id = $comite_id 
           ORDER BY cm.fecha_asignacion ASC";
    
    $resultado = $conexion->query($sql);
    
    $miembros = [];
    if ($resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            $miembros[] = $fila;
        }
    }
    
    $conexion->close();
    return $miembros;
}

/**
 * Obtiene el coordinador de un comité
 */
function obtenerCoordinadorComite($comite_id) {
    $conexion = conectarDB();
    $comite_id = (int)$comite_id;
    
    $sql = "SELECT c.*, cc.fecha_asignacion 
           FROM coordinadores c 
           INNER JOIN comite_coordinador cc ON c.id = cc.coordinador_id 
           WHERE cc.comite_id = $comite_id";
    
    $resultado = $conexion->query($sql);
    
    if ($resultado->num_rows == 1) {
        $coordinador = $resultado->fetch_assoc();
        $conexion->close();
        return $coordinador;
    }
    
    $conexion->close();
    return null;
}

/**
 * Guarda un coordinador en la base de datos
 */
function guardarCoordinador($cedula, $nombre_completo, $telefono, $email, $municipio, $recinto, $colegio, $foto = null) {
    $conexion = conectarDB();
    
    $cedula = sanitizar($conexion, $cedula);
    $nombre_completo = sanitizar($conexion, $nombre_completo);
    $telefono = sanitizar($conexion, $telefono);
    $email = sanitizar($conexion, $email);
    $municipio = sanitizar($conexion, $municipio);
    $recinto = sanitizar($conexion, $recinto);
    $colegio = sanitizar($conexion, $colegio);
    
    // Verificar si ya existe
    $sql = "SELECT id FROM coordinadores WHERE cedula = '$cedula'";
    $resultado = $conexion->query($sql);
    
    if ($resultado->num_rows > 0) {
        // Actualizar coordinador existente
        $fila = $resultado->fetch_assoc();
        $id = $fila['id'];
        
        $sql = "UPDATE coordinadores SET 
               nombre_completo = '$nombre_completo', 
               telefono = '$telefono', 
               email = '$email', 
               municipio = '$municipio', 
               recinto = '$recinto', 
               colegio = '$colegio'";
        
        if ($foto !== null) {
            $foto = $conexion->real_escape_string($foto);
            $sql .= ", foto = '$foto'";
        }
        
        $sql .= " WHERE id = $id";
        
        $conexion->query($sql);
        $conexion->close();
        return $id;
    } else {
        // Crear nuevo coordinador
        $sql = "INSERT INTO coordinadores (cedula, nombre_completo, telefono, email, municipio, recinto, colegio";
        
        if ($foto !== null) {
            $sql .= ", foto";
        }
        
        $sql .= ") VALUES ('$cedula', '$nombre_completo', '$telefono', '$email', '$municipio', '$recinto', '$colegio'";
        
        if ($foto !== null) {
            $foto = $conexion->real_escape_string($foto);
            $sql .= ", '$foto'";
        }
        
        $sql .= ")";
        
        if ($conexion->query($sql)) {
            $id = $conexion->insert_id;
            $conexion->close();
            return $id;
        }
    }
    
    $conexion->close();
    return false;
}

/**
 * Asigna un coordinador a un comité
 */
function asignarCoordinadorComite($comite_id, $coordinador_id) {
    $conexion = conectarDB();
    
    $comite_id = (int)$comite_id;
    $coordinador_id = (int)$coordinador_id;
    
    // Eliminar asignaciones previas
    $sql = "DELETE FROM comite_coordinador WHERE comite_id = $comite_id";
    $conexion->query($sql);
    
    // Crear nueva asignación
    $sql = "INSERT INTO comite_coordinador (comite_id, coordinador_id) 
           VALUES ($comite_id, $coordinador_id)";
    
    $resultado = $conexion->query($sql);
    $conexion->close();
    
    return $resultado;
}

/**
 * Guarda un miembro en la base de datos
 */
function guardarMiembro($cedula, $nombre_completo, $telefono, $email, $municipio, $recinto, $colegio, $foto = null) {
    $conexion = conectarDB();
    
    $cedula = sanitizar($conexion, $cedula);
    $nombre_completo = sanitizar($conexion, $nombre_completo);
    $telefono = sanitizar($conexion, $telefono);
    $email = sanitizar($conexion, $email);
    $municipio = sanitizar($conexion, $municipio);
    $recinto = sanitizar($conexion, $recinto);
    $colegio = sanitizar($conexion, $colegio);
    
    // Verificar si ya existe
    $sql = "SELECT id FROM miembros WHERE cedula = '$cedula'";
    $resultado = $conexion->query($sql);
    
    if ($resultado->num_rows > 0) {
        // Actualizar miembro existente
        $fila = $resultado->fetch_assoc();
        $id = $fila['id'];
        
        $sql = "UPDATE miembros SET 
               nombre_completo = '$nombre_completo', 
               telefono = '$telefono', 
               email = '$email', 
               municipio = '$municipio', 
               recinto = '$recinto', 
               colegio = '$colegio'";
        
        if ($foto !== null) {
            $foto = $conexion->real_escape_string($foto);
            $sql .= ", foto = '$foto'";
        }
        
        $sql .= " WHERE id = $id";
        
        $conexion->query($sql);
        $conexion->close();
        return $id;
    } else {
        // Crear nuevo miembro
        $sql = "INSERT INTO miembros (cedula, nombre_completo, telefono, email, municipio, recinto, colegio";
        
        if ($foto !== null) {
            $sql .= ", foto";
        }
        
        $sql .= ") VALUES ('$cedula', '$nombre_completo', '$telefono', '$email', '$municipio', '$recinto', '$colegio'";
        
        if ($foto !== null) {
            $foto = $conexion->real_escape_string($foto);
            $sql .= ", '$foto'";
        }
        
        $sql .= ")";
        
        if ($conexion->query($sql)) {
            $id = $conexion->insert_id;
            $conexion->close();
            return $id;
        }
    }
    
    $conexion->close();
    return false;
}

/**
 * Asigna un miembro a un comité
 */
function asignarMiembroComite($comite_id, $miembro_id) {
    $conexion = conectarDB();
    
    $comite_id = (int)$comite_id;
    $miembro_id = (int)$miembro_id;
    
    // Verificar si ya está asignado
    $sql = "SELECT id FROM comite_miembro WHERE comite_id = $comite_id AND miembro_id = $miembro_id";
    $resultado = $conexion->query($sql);
    
    if ($resultado->num_rows > 0) {
        $conexion->close();
        return true; // Ya está asignado
    }
    
    // Crear nueva asignación
    $sql = "INSERT INTO comite_miembro (comite_id, miembro_id) 
           VALUES ($comite_id, $miembro_id)";
    
    $resultado = $conexion->query($sql);
    $conexion->close();
    
    return $resultado;
}

/**
 * Elimina un miembro de un comité
 */
function eliminarMiembroComite($comite_id, $miembro_id) {
    $conexion = conectarDB();
    
    $comite_id = (int)$comite_id;
    $miembro_id = (int)$miembro_id;
    
    $sql = "DELETE FROM comite_miembro WHERE comite_id = $comite_id AND miembro_id = $miembro_id";
    $resultado = $conexion->query($sql);
    
    $conexion->close();
    return $resultado;
}

/**
 * Busca una persona por cédula en SQL Server
 */
function buscarPersonaPorCedula($cedula) {
    try {
        // Esta función debe seguir usando PDO ya que conectarSQLServer() devuelve una conexión PDO
        // para SQL Server, no una conexión mysqli
        $pdo = conectarSQLServer();
        
        $sql = "SELECT
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
        
        if ($row) {
            // Codificar la imagen en base64 para mostrarla en el frontend
            $row['Imagen'] = base64_encode($row['Imagen']);
            return $row;
        }
        
        return null;
    } catch (Exception $e) {
        return null;
    }
}
?>