<?php
require_once 'includes/auth.php';
require_once 'db/config.php';

requiereAutenticacion();

// Si ya tiene candidato → dashboard
if (tieneCandidato()) { header('Location: dashboard.php'); exit; }

$conn = conectarDB();
$partido_id = $_SESSION['partido_id'] ?? 0;

// Guardar candidato seleccionado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['candidato_id'])) {
    $cid = (int)$_POST['candidato_id'];
    // Verificar que el candidato pertenece al partido del usuario
    $s = $conn->prepare("SELECT id, nombre, cargo FROM candidatos WHERE id=? AND partido_id=? AND activo=1");
    $s->bind_param("ii", $cid, $partido_id);
    $s->execute();
    $cand = $s->get_result()->fetch_assoc();
    if ($cand) {
        $_SESSION['candidato_id']     = $cand['id'];
        $_SESSION['candidato_nombre'] = $cand['nombre'];
        $_SESSION['candidato_cargo']  = $cand['cargo'];
        header('Location: dashboard.php'); exit;
    }
}

// Cargar candidatos del partido agrupados por cargo
$candidatos = [];
if ($partido_id) {
    $res = $conn->query("SELECT id, nombre, cargo, foto FROM candidatos WHERE partido_id=$partido_id AND activo=1 ORDER BY FIELD(cargo,'presidente','senador','diputado','alcalde','regidor'), nombre");
    while ($row = $res->fetch_assoc()) {
        $candidatos[$row['cargo']][] = $row;
    }
}

// Cargar tema del partido
$tema = cargarTemaPartido();
$logo_b64 = '';
if (!empty($tema['logo'])) $logo_b64 = base64_encode($tema['logo']);

$cargos_labels = [
    'presidente' => 'Presidente',
    'senador'    => 'Senador',
    'diputado'   => 'Diputado',
    'alcalde'    => 'Alcalde',
    'regidor'    => 'Regidor',
];
$cargos_icons = [
    'presidente' => 'fa-star',
    'senador'    => 'fa-landmark',
    'diputado'   => 'fa-university',
    'alcalde'    => 'fa-city',
    'regidor'    => 'fa-user-tie',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleccionar Candidato</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: <?php echo htmlspecialchars($tema['color_primario']); ?>;
            --sidebar: <?php echo htmlspecialchars($tema['color_sidebar']); ?>;
            --accent:  <?php echo htmlspecialchars($tema['color_accent']); ?>;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #f1f5f9;
            min-height: 100vh;
        }

        /* Top bar */
        .sel-topbar {
            background: var(--sidebar);
            padding: 16px 24px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.25);
        }
        .sel-topbar img { width: 40px; height: 40px; object-fit: contain; border-radius: 8px; background: rgba(255,255,255,.1); padding: 4px; }
        .sel-topbar-title { color: #fff; font-size: 15px; font-weight: 600; }
        .sel-topbar-sub   { color: rgba(255,255,255,.6); font-size: 12px; }
        .sel-topbar-user  { margin-left: auto; color: rgba(255,255,255,.75); font-size: 13px; }

        /* Content */
        .sel-content { max-width: 960px; margin: 0 auto; padding: 32px 20px 60px; }

        .sel-hero {
            text-align: center;
            margin-bottom: 36px;
        }
        .sel-hero h1 {
            font-size: 24px; font-weight: 700;
            color: #0f172a; margin-bottom: 8px;
        }
        .sel-hero p { font-size: 14px; color: #64748b; }

        /* Cargo section */
        .cargo-section { margin-bottom: 32px; }
        .cargo-label {
            display: flex; align-items: center; gap: 10px;
            font-size: 12px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .06em;
            color: #64748b;
            margin-bottom: 14px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        .cargo-label i { color: var(--primary); }

        .candidatos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 14px;
        }

        .candidato-card {
            background: #fff;
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            padding: 20px 16px;
            text-align: center;
            cursor: pointer;
            transition: all .2s;
            position: relative;
        }
        .candidato-card:hover {
            border-color: var(--primary);
            box-shadow: 0 6px 20px rgba(0,0,0,.1);
            transform: translateY(-2px);
        }
        .candidato-card.selected {
            border-color: var(--primary);
            background: color-mix(in srgb, var(--primary) 6%, white);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--primary) 15%, transparent);
        }
        .candidato-check {
            position: absolute; top: 10px; right: 10px;
            width: 22px; height: 22px; border-radius: 50%;
            background: var(--primary); color: #fff;
            display: none; align-items: center; justify-content: center;
            font-size: 11px;
        }
        .candidato-card.selected .candidato-check { display: flex; }

        .candidato-foto {
            width: 80px; height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 12px;
            border: 3px solid #e2e8f0;
            display: block;
        }
        .candidato-foto-placeholder {
            width: 80px; height: 80px;
            border-radius: 50%;
            background: var(--primary);
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 28px; font-weight: 700;
            margin: 0 auto 12px;
            border: 3px solid rgba(0,0,0,.08);
        }
        .candidato-nombre {
            font-size: 13px; font-weight: 600;
            color: #0f172a; line-height: 1.3;
        }
        .candidato-cargo-pill {
            display: inline-block;
            font-size: 10px; font-weight: 600;
            text-transform: uppercase; letter-spacing: .04em;
            color: var(--primary);
            background: color-mix(in srgb, var(--primary) 10%, white);
            padding: 2px 8px; border-radius: 10px;
            margin-top: 6px;
        }

        /* Confirm button */
        .sel-footer {
            position: fixed; bottom: 0; left: 0; right: 0;
            background: #fff;
            border-top: 1px solid #e2e8f0;
            padding: 14px 24px;
            display: flex; align-items: center; justify-content: space-between;
            gap: 12px;
            box-shadow: 0 -4px 20px rgba(0,0,0,.08);
            z-index: 100;
        }
        .sel-footer-info { font-size: 13px; color: #64748b; }
        .sel-footer-info strong { color: #0f172a; }
        .btn-confirmar {
            background: var(--primary); color: #fff;
            border: none; border-radius: 10px;
            padding: 11px 28px; font-size: 14px; font-weight: 600;
            cursor: pointer; transition: all .15s;
            display: flex; align-items: center; gap: 8px;
        }
        .btn-confirmar:hover { filter: brightness(1.1); }
        .btn-confirmar:disabled { opacity: .5; cursor: not-allowed; }

        .empty-state {
            text-align: center; padding: 60px 20px;
            color: #94a3b8;
        }
        .empty-state i { font-size: 48px; margin-bottom: 16px; display: block; opacity: .4; }

        @media (max-width: 576px) {
            .candidatos-grid { grid-template-columns: repeat(2, 1fr); }
            .sel-topbar-user { display: none; }
            .sel-hero h1 { font-size: 20px; }
        }
    </style>
</head>
<body>

<!-- Topbar -->
<div class="sel-topbar">
    <?php if ($logo_b64): ?>
    <img src="data:image/png;base64,<?php echo $logo_b64; ?>" alt="Logo">
    <?php else: ?>
    <div style="width:40px;height:40px;border-radius:8px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px;">
        <i class="fas fa-star"></i>
    </div>
    <?php endif; ?>
    <div>
        <div class="sel-topbar-title"><?php echo htmlspecialchars($tema['nombre']); ?></div>
        <div class="sel-topbar-sub">Sistema de Comités Afectivos</div>
    </div>
    <div class="sel-topbar-user">
        <i class="fas fa-user-circle me-1"></i>
        <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?>
        &nbsp;·&nbsp;
        <a href="logout.php" style="color:rgba(255,255,255,.5);text-decoration:none;font-size:12px;">Salir</a>
    </div>
</div>

<!-- Content -->
<div class="sel-content">
    <div class="sel-hero">
        <h1>¿Para qué candidato vas a trabajar hoy?</h1>
        <p>Selecciona un candidato para comenzar a registrar comités afectivos a su nombre.</p>
    </div>

    <form method="POST" id="formCandidato">
        <input type="hidden" name="candidato_id" id="candidatoIdInput">

        <?php if (empty($candidatos)): ?>
        <div class="empty-state">
            <i class="fas fa-user-slash"></i>
            <p>No hay candidatos configurados para tu partido.<br>Contacta al administrador.</p>
        </div>
        <?php else: ?>
            <?php foreach ($cargos_labels as $cargo => $label): ?>
            <?php if (empty($candidatos[$cargo])) continue; ?>
            <div class="cargo-section">
                <div class="cargo-label">
                    <i class="fas <?php echo $cargos_icons[$cargo]; ?>"></i>
                    <?php echo $label; ?>
                </div>
                <div class="candidatos-grid">
                    <?php foreach ($candidatos[$cargo] as $c): ?>
                    <div class="candidato-card" data-id="<?php echo $c['id']; ?>" onclick="seleccionar(this, <?php echo $c['id']; ?>, '<?php echo htmlspecialchars(addslashes($c['nombre'])); ?>', '<?php echo $label; ?>')">
                        <div class="candidato-check"><i class="fas fa-check"></i></div>
                        <?php if (!empty($c['foto'])): ?>
                        <img src="data:image/jpeg;base64,<?php echo base64_encode($c['foto']); ?>" alt="Foto" class="candidato-foto">
                        <?php else: ?>
                        <div class="candidato-foto-placeholder"><?php echo strtoupper(substr($c['nombre'],0,1)); ?></div>
                        <?php endif; ?>
                        <div class="candidato-nombre"><?php echo htmlspecialchars($c['nombre']); ?></div>
                        <div class="candidato-cargo-pill"><?php echo $label; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </form>
</div>

<!-- Footer confirm -->
<div class="sel-footer">
    <div class="sel-footer-info" id="footerInfo">
        Ningún candidato seleccionado
    </div>
    <button class="btn-confirmar" id="btnConfirmar" disabled onclick="document.getElementById('formCandidato').submit()">
        <i class="fas fa-check-circle"></i> Continuar
    </button>
</div>

<script>
let selectedId = null;

function seleccionar(el, id, nombre, cargo) {
    // Deselect all
    document.querySelectorAll('.candidato-card').forEach(c => c.classList.remove('selected'));
    // Select this
    el.classList.add('selected');
    selectedId = id;
    document.getElementById('candidatoIdInput').value = id;
    document.getElementById('btnConfirmar').disabled = false;
    document.getElementById('footerInfo').innerHTML =
        `Seleccionado: <strong>${nombre}</strong> — ${cargo}`;
}
</script>
</body>
</html>
