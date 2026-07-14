<?php
/*----------------------------------------------------------
  API pública – Login de coordinador por cédula
  (POST api/publico_login_comite.php, body JSON {cedula, ultimos4})
  Sin autenticación por diseño: alimenta el panel "Ya tengo mi comité"
  de comite_publico.php. La cédula completa + los últimos 4 dígitos de
  esa misma cédula funcionan como identificación liviana del coordinador.
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

// Límite de intentos por IP (comparte el contador con el registro de comités).
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

$cedula   = preg_replace('/\D/', '', $input['cedula'] ?? '');
$ultimos4 = preg_replace('/\D/', '', $input['ultimos4'] ?? '');

if (strlen($cedula) < 5 || strlen($ultimos4) !== 4) {
    echo json_encode(['success' => false, 'message' => 'Ingresá tu cédula completa y los últimos 4 dígitos.']);
    exit;
}
if (substr($cedula, -4) !== $ultimos4) {
    echo json_encode(['success' => false, 'message' => 'Los últimos 4 dígitos no coinciden con la cédula.']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM coordinadores WHERE cedula = ? LIMIT 1");
$stmt->bind_param("s", $cedula);
$stmt->execute();
$coordinador = $stmt->get_result()->fetch_assoc();

if (!$coordinador) {
    echo json_encode(['success' => false, 'message' => 'No encontramos ningún comité asociado a esa cédula.']);
    exit;
}

$stmt = $conn->prepare("SELECT c.* FROM comites c JOIN comite_coordinador cc ON c.id = cc.comite_id WHERE cc.coordinador_id = ? ORDER BY c.fecha_creacion DESC");
$stmt->bind_param("i", $coordinador['id']);
$stmt->execute();
$comitesRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($comitesRows)) {
    echo json_encode(['success' => false, 'message' => 'No encontramos ningún comité asociado a esa cédula.']);
    exit;
}

$comitesData = [];
foreach ($comitesRows as $comite) {
    $candidato = null;
    if (!empty($comite['candidato_id'])) {
        $stmtC = $conn->prepare("SELECT nombre, cargo, foto FROM candidatos WHERE id = ?");
        $stmtC->bind_param("i", $comite['candidato_id']);
        $stmtC->execute();
        $candRow = $stmtC->get_result()->fetch_assoc();
        if ($candRow) {
            $candidato = [
                'nombre' => $candRow['nombre'],
                'cargo'  => $candRow['cargo'],
                'foto'   => !empty($candRow['foto']) ? base64_encode($candRow['foto']) : null,
            ];
        }
    }

    $stmtM = $conn->prepare("SELECT m.* FROM miembros m JOIN comite_miembro cm ON m.id = cm.miembro_id WHERE cm.comite_id = ? ORDER BY m.nombre_completo");
    $stmtM->bind_param("i", $comite['id']);
    $stmtM->execute();
    $miembrosRows = $stmtM->get_result()->fetch_all(MYSQLI_ASSOC);

    $comitesData[] = [
        'id'              => (int)$comite['id'],
        'nombre'          => $comite['nombre'],
        'provincia'       => $comite['provincia'],
        'municipio'       => $comite['municipio'],
        'zona'            => $comite['zona'],
        'circunscripcion' => $comite['circunscripcion'] ?? '',
        'candidato'       => $candidato,
        'coordinador'     => [
            'nombre'    => $coordinador['nombre_completo'],
            'cedula'    => $coordinador['cedula'],
            'telefono'  => $coordinador['telefono'],
            'municipio' => $coordinador['municipio'],
            'recinto'   => $coordinador['recinto'],
            'colegio'   => $coordinador['colegio'],
            'foto'      => $coordinador['foto'],
        ],
        'miembros' => array_map(function ($m) {
            return [
                'id'        => (int)$m['id'],
                'nombre'    => $m['nombre_completo'],
                'cedula'    => $m['cedula'],
                'telefono'  => $m['telefono'],
                'municipio' => $m['municipio'],
                'recinto'   => $m['recinto'],
                'colegio'   => $m['colegio'],
                'foto'      => $m['foto'],
            ];
        }, $miembrosRows),
    ];
}

echo json_encode(['success' => true, 'comites' => $comitesData]);
