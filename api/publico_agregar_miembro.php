<?php
/*----------------------------------------------------------
  API pública – Agregar miembro a un comité ya existente
  (POST api/publico_agregar_miembro.php, body JSON
   {comite_id, cedula, ultimos4, miembro:{...}})
  Sin autenticación por diseño: usada por el panel "Ya tengo mi comité"
  de comite_publico.php, tras un login exitoso vía
  publico_login_comite.php. Vuelve a validar que la cédula+últimos4
  recibida realmente coordine ese comite_id antes de escribir nada,
  para que no se pueda inyectar miembros en comités ajenos con solo
  adivinar un comite_id.
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

$comite_id = (int)($input['comite_id'] ?? 0);
$cedula    = preg_replace('/\D/', '', $input['cedula'] ?? '');
$ultimos4  = preg_replace('/\D/', '', $input['ultimos4'] ?? '');
$m         = is_array($input['miembro'] ?? null) ? $input['miembro'] : [];

if ($comite_id <= 0 || $cedula === '' || $ultimos4 === '') {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
    exit;
}
if (substr($cedula, -4) !== $ultimos4) {
    echo json_encode(['success' => false, 'message' => 'Credenciales inválidas.']);
    exit;
}

// El coordinador con esta cédula debe estar realmente asignado a este comité.
$stmt = $conn->prepare("SELECT cc.id FROM comite_coordinador cc JOIN coordinadores c ON cc.coordinador_id = c.id WHERE cc.comite_id = ? AND c.cedula = ?");
$stmt->bind_param("is", $comite_id, $cedula);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) {
    echo json_encode(['success' => false, 'message' => 'No tenés permiso para modificar este comité.']);
    exit;
}

$m_cedula = trim($m['cedula'] ?? '');
$m_nombre = trim($m['nombre'] ?? '');
if ($m_cedula === '' || $m_nombre === '') {
    echo json_encode(['success' => false, 'message' => 'Datos del miembro incompletos.']);
    exit;
}
$m_telefono  = trim($m['telefono'] ?? '');
$m_email     = trim($m['email'] ?? '');
$m_municipio = trim($m['municipio'] ?? '');
$m_recinto   = trim($m['recinto'] ?? '');
$m_colegio   = trim($m['colegio'] ?? '');
$m_foto      = $m['foto'] ?? null;

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("SELECT cm.id FROM comite_miembro cm JOIN miembros mm ON cm.miembro_id = mm.id WHERE cm.comite_id = ? AND mm.cedula = ?");
    $stmt->bind_param("is", $comite_id, $m_cedula);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        throw new Exception('Esta persona ya es miembro de este comité.');
    }

    $stmt = $conn->prepare("SELECT id FROM miembros WHERE cedula = ? LIMIT 1");
    $stmt->bind_param("s", $m_cedula);
    $stmt->execute();
    $existente = $stmt->get_result()->fetch_assoc();

    if ($existente) {
        $miembro_id = $existente['id'];
    } else {
        $stmt = $conn->prepare("INSERT INTO miembros (cedula, nombre_completo, telefono, email, municipio, recinto, colegio, foto, fecha_registro) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssssss", $m_cedula, $m_nombre, $m_telefono, $m_email, $m_municipio, $m_recinto, $m_colegio, $m_foto);
        $stmt->execute();
        $miembro_id = $conn->insert_id;
    }

    $stmt = $conn->prepare("INSERT INTO comite_miembro (comite_id, miembro_id, fecha_asignacion) VALUES (?, ?, NOW())");
    $stmt->bind_param("ii", $comite_id, $miembro_id);
    $stmt->execute();

    $conn->commit();
    echo json_encode(['success' => true, 'miembro' => [
        'id' => $miembro_id, 'nombre' => $m_nombre, 'cedula' => $m_cedula, 'telefono' => $m_telefono,
        'municipio' => $m_municipio, 'recinto' => $m_recinto, 'colegio' => $m_colegio, 'foto' => $m_foto,
    ]]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
