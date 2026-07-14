<?php
/*----------------------------------------------------------
  API pública – Registro de comité vía enlace público
  (POST api/publico_guardar_comite.php, body JSON)
  Sin autenticación por diseño: es el endpoint que alimenta
  comite_publico.php. Protegido con honeypot + límite por IP.
  ---------------------------------------------------------*/
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit;
}

$conn = conectarDB();
$ip = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';

// Honeypot: campo invisible para personas, tentador para bots.
if (!empty($input['sitio_web'])) {
    echo json_encode(['success' => false, 'message' => 'No se pudo procesar la solicitud.']);
    exit;
}

// Límite de intentos por IP (máx. 5 por hora), registrando este intento igual.
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM intentos_publico WHERE ip = ? AND creado >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
$stmt->bind_param("s", $ip);
$stmt->execute();
$intentos = (int)$stmt->get_result()->fetch_assoc()['total'];

$stmtLog = $conn->prepare("INSERT INTO intentos_publico (ip) VALUES (?)");
$stmtLog->bind_param("s", $ip);
$stmtLog->execute();

if ($intentos >= 5) {
    echo json_encode(['success' => false, 'message' => 'Demasiados intentos desde esta conexión. Probá de nuevo más tarde.']);
    exit;
}

// ── Datos del comité ──
$nombre          = trim($input['nombre'] ?? '');
$provincia       = trim($input['provincia'] ?? '');
$municipio       = trim($input['municipio'] ?? '');
$zona            = trim($input['zona'] ?? '');
$circunscripcion = trim($input['circunscripcion'] ?? '');
$candidato_id    = !empty($input['candidato_id']) ? (int)$input['candidato_id'] : 0;

// ── Coordinador (obligatorio) ──
$coord           = is_array($input['coordinador'] ?? null) ? $input['coordinador'] : [];
$coord_cedula    = trim($coord['cedula'] ?? '');
$coord_nombre    = trim($coord['nombre'] ?? '');
$coord_telefono  = trim($coord['telefono'] ?? '');
$coord_email     = trim($coord['email'] ?? '');
$coord_municipio = trim($coord['municipio'] ?? '');
$coord_recinto   = trim($coord['recinto'] ?? '');
$coord_colegio   = trim($coord['colegio'] ?? '');
$coord_foto      = $coord['foto'] ?? null;

// ── Miembros (opcional) ──
$miembros = is_array($input['miembros'] ?? null) ? $input['miembros'] : [];

$errores = [];
if ($nombre === '')          $errores[] = 'El nombre del comité es obligatorio.';
if ($provincia === '')       $errores[] = 'La provincia es obligatoria.';
if ($municipio === '')       $errores[] = 'El municipio es obligatorio.';
if ($candidato_id <= 0)      $errores[] = 'Debe seleccionar un candidato.';
if ($coord_cedula === '' || $coord_nombre === '' || $coord_telefono === '') {
    $errores[] = 'Los datos del coordinador (cédula, nombre y teléfono) son obligatorios.';
}

if ($candidato_id > 0) {
    $stmt = $conn->prepare("SELECT id FROM candidatos WHERE id = ? AND activo = 1");
    $stmt->bind_param("i", $candidato_id);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        $errores[] = 'El candidato seleccionado no es válido.';
    }
}

if (!empty($errores)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errores)]);
    exit;
}

try {
    $conn->begin_transaction();

    // Comité
    $stmt = $conn->prepare("INSERT INTO comites (nombre, provincia, municipio, zona, circunscripcion, candidato_id, creado_por, origen, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, NULL, 'publico', NOW())");
    $stmt->bind_param("sssssi", $nombre, $provincia, $municipio, $zona, $circunscripcion, $candidato_id);
    $stmt->execute();
    $comite_id = $conn->insert_id;

    // Coordinador: reusar si ya existe una cédula igual (cedula es UNIQUE en coordinadores)
    $stmt = $conn->prepare("SELECT id FROM coordinadores WHERE cedula = ? LIMIT 1");
    $stmt->bind_param("s", $coord_cedula);
    $stmt->execute();
    $coordExistente = $stmt->get_result()->fetch_assoc();

    if ($coordExistente) {
        $coordinador_id = $coordExistente['id'];
        $stmt = $conn->prepare("UPDATE coordinadores SET nombre_completo=?, telefono=?, email=?, municipio=?, recinto=?, colegio=? WHERE id=?");
        $stmt->bind_param("ssssssi", $coord_nombre, $coord_telefono, $coord_email, $coord_municipio, $coord_recinto, $coord_colegio, $coordinador_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO coordinadores (cedula, nombre_completo, telefono, email, municipio, recinto, colegio, foto) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $coord_cedula, $coord_nombre, $coord_telefono, $coord_email, $coord_municipio, $coord_recinto, $coord_colegio, $coord_foto);
        $stmt->execute();
        $coordinador_id = $conn->insert_id;
    }

    $stmt = $conn->prepare("INSERT INTO comite_coordinador (comite_id, coordinador_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $comite_id, $coordinador_id);
    $stmt->execute();

    // Miembros (opcional) — se ignoran filas sin cédula/nombre, se reusa un miembro existente por cédula
    foreach ($miembros as $m) {
        if (!is_array($m)) continue;
        $m_cedula = trim($m['cedula'] ?? '');
        $m_nombre = trim($m['nombre'] ?? '');
        if ($m_cedula === '' || $m_nombre === '') continue;

        $m_telefono  = trim($m['telefono'] ?? '');
        $m_email     = trim($m['email'] ?? '');
        $m_municipio = trim($m['municipio'] ?? '');
        $m_recinto   = trim($m['recinto'] ?? '');
        $m_colegio   = trim($m['colegio'] ?? '');
        $m_foto      = $m['foto'] ?? null;

        $stmt = $conn->prepare("SELECT id FROM miembros WHERE cedula = ? LIMIT 1");
        $stmt->bind_param("s", $m_cedula);
        $stmt->execute();
        $miembroExistente = $stmt->get_result()->fetch_assoc();

        if ($miembroExistente) {
            $miembro_id = $miembroExistente['id'];
        } else {
            $stmt = $conn->prepare("INSERT INTO miembros (cedula, nombre_completo, telefono, email, municipio, recinto, colegio, foto, fecha_registro) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssssssss", $m_cedula, $m_nombre, $m_telefono, $m_email, $m_municipio, $m_recinto, $m_colegio, $m_foto);
            $stmt->execute();
            $miembro_id = $conn->insert_id;
        }

        $stmt = $conn->prepare("SELECT id FROM comite_miembro WHERE comite_id = ? AND miembro_id = ?");
        $stmt->bind_param("ii", $comite_id, $miembro_id);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            $stmt = $conn->prepare("INSERT INTO comite_miembro (comite_id, miembro_id, fecha_asignacion) VALUES (?, ?, NOW())");
            $stmt->bind_param("ii", $comite_id, $miembro_id);
            $stmt->execute();
        }
    }

    $conn->commit();
    echo json_encode(['success' => true, 'comite_id' => $comite_id]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error al guardar el comité: ' . $e->getMessage()]);
}
