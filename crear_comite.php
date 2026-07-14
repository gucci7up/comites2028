<?php
require_once 'includes/auth.php';
require_once 'includes/funciones.php';
require_once 'db/config.php';

requiereAutenticacion();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre     = trim($_POST['nombre']);
    $provincia  = trim($_POST['provincia']);
    $municipio  = trim($_POST['municipio']);
    $zona       = trim($_POST['zona']);
    $candidato_id = !empty($_POST['candidato_id']) ? (int)$_POST['candidato_id'] : ($_SESSION['candidato_id'] ?? null);
    $usuario_id = $_SESSION['usuario_id'];
    $errores = [];
    if (empty($nombre))    $errores[] = "El nombre del comité es obligatorio.";
    if (empty($provincia)) $errores[] = "La provincia es obligatoria.";
    if (empty($municipio)) $errores[] = "El municipio es obligatorio.";
    if (empty($errores)) {
        try {
            $conn = conectarDB();
            $stmt = $conn->prepare("INSERT INTO comites (nombre, provincia, municipio, zona, creado_por, candidato_id, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssssii", $nombre, $provincia, $municipio, $zona, $usuario_id, $candidato_id);
            $stmt->execute();
            $comite_id = $conn->insert_id;
            $_SESSION['mensaje'] = "Comité creado correctamente. Ahora puede agregar un coordinador y miembros.";
            $_SESSION['tipo_mensaje'] = "success";
            header("Location: editar_comite.php?id=$comite_id");
            exit;
        } catch (Exception $e) {
            $errores[] = "Error al crear el comité: " . $e->getMessage();
        }
    }
}

$conn = conectarDB();
$candidatos = $conn->query("SELECT id, nombre, cargo FROM candidatos WHERE activo = 1 ORDER BY FIELD(cargo,'presidente','senador','diputado','alcalde','regidor'), nombre")->fetch_all(MYSQLI_ASSOC);
$cargos = ['presidente'=>'Presidente','senador'=>'Senador','diputado'=>'Diputado','alcalde'=>'Alcalde','regidor'=>'Regidor'];
$candidato_seleccionado = $candidato_id ?? ($_SESSION['candidato_id'] ?? null);

$titulo_pagina    = 'Crear Comité';
$subtitulo_pagina = 'Registrá un nuevo comité afectivo';
include 'includes/header.php';
?>

<style>
.create-layout { display:grid; grid-template-columns:minmax(0,1fr) 300px; gap:22px; align-items:start; }
@media (max-width:1100px) { .create-layout { grid-template-columns:1fr; } }
.step-card { background:#fff; border-radius:var(--radius-card); padding:26px; box-shadow:var(--shadow-sm); margin-bottom:22px; }
.step-hd { display:flex; align-items:center; gap:12px; margin-bottom:18px; }
.step-num { width:28px; height:28px; border-radius:8px; background:var(--accent); color:#fff; font-size:13px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.step-title { font-size:15px; font-weight:700; }
.field-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }
@media (max-width:560px) { .field-row { grid-template-columns:1fr; } }
.info-box { background:var(--accent-tint); color:var(--text-secondary); border-radius:var(--radius); padding:14px 16px; font-size:12.5px; display:flex; gap:10px; align-items:flex-start; margin-bottom:20px; }
.info-box i { color:var(--accent); margin-top:1px; }
.guide-panel { background:linear-gradient(160deg,var(--accent),var(--accent-700)); border-radius:var(--radius-card); padding:26px; color:#fff; }
.guide-title { font-size:15px; font-weight:700; margin-bottom:14px; }
.guide-copy { font-size:12.5px; color:rgba(255,255,255,.85); line-height:1.6; margin-bottom:18px; }
.guide-step { display:flex; align-items:center; gap:10px; font-size:12.5px; margin-bottom:10px; }
.guide-step:last-child { margin-bottom:0; }
.mini-step { text-align:center; }
.mini-step-icon { width:52px; height:52px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:20px; margin:0 auto 12px; }
</style>

<a href="comites.php" class="page-back"><i class="fas fa-arrow-left"></i> Volver a Comités</a>

<?php if (!empty($errores)): ?>
<div class="alert alert-danger alert-dismissible fade show mb-4">
    <ul class="mb-0"><?php foreach ($errores as $e): ?><li><?php echo $e; ?></li><?php endforeach; ?></ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="create-layout">
  <div style="min-width:0;">

    <div class="step-card">
        <div class="step-hd">
            <div class="step-num">1</div>
            <div class="step-title">Datos del Comité</div>
        </div>
        <form method="POST">
            <div style="margin-bottom:16px;">
                <label class="lbl">Nombre del comité</label>
                <input type="text" class="fld" name="nombre" placeholder="Ej: Comité Afectivo Zona Norte"
                       value="<?php echo isset($nombre) ? htmlspecialchars($nombre) : ''; ?>" required>
                <div class="form-text mt-1">Nombre descriptivo del comité.</div>
            </div>
            <div class="field-row">
                <div>
                    <label class="lbl">Provincia</label>
                    <input type="text" class="fld" id="provincia" name="provincia" list="provinciaList"
                           placeholder="Ej: Santo Domingo" autocomplete="off"
                           value="<?php echo isset($provincia) ? htmlspecialchars($provincia) : ''; ?>" required>
                    <datalist id="provinciaList"></datalist>
                </div>
                <div>
                    <label class="lbl">Municipio</label>
                    <input type="text" class="fld" id="municipio" name="municipio" list="municipioList"
                           placeholder="Seleccione primero una provincia" autocomplete="off" disabled
                           value="<?php echo isset($municipio) ? htmlspecialchars($municipio) : ''; ?>" required>
                    <datalist id="municipioList"></datalist>
                </div>
            </div>
            <div class="field-row">
                <div>
                    <label class="lbl">Zona</label>
                    <input type="text" class="fld" id="zona" name="zona"
                           placeholder="Ej: Zona Norte, Sector 4..."
                           value="<?php echo isset($zona) ? htmlspecialchars($zona) : ''; ?>">
                </div>
                <div>
                    <label class="lbl">Candidato</label>
                    <select class="fld" name="candidato_id">
                        <option value="">Sin candidato asignado</option>
                        <?php foreach ($candidatos as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo (int)$candidato_seleccionado === (int)$c['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['nombre']); ?> — <?php echo $cargos[$c['cargo']] ?? ucfirst($c['cargo']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <span>Después de crear el comité podrá asignar un coordinador y agregar miembros.</span>
            </div>

            <div style="display:flex;gap:12px;">
                <button type="submit" class="btn btn-primary" style="padding:12px 24px;">Guardar Comité</button>
                <a href="comites.php" class="btn" style="background:#fff;color:var(--text-secondary);box-shadow:var(--shadow-sm);padding:12px 24px;">Cancelar</a>
            </div>
        </form>
    </div>

    <div class="step-card" style="margin-bottom:0;">
        <div class="step-hd">
            <div class="step-num" style="background:var(--text-tertiary);">2</div>
            <div class="step-title">Cómo funciona</div>
        </div>
        <div class="row g-3">
            <div class="col-md-4 mini-step">
                <div class="mini-step-icon" style="background:var(--accent-tint);color:var(--accent);"><i class="fas fa-clipboard-list"></i></div>
                <div style="font-size:13px;font-weight:600;margin-bottom:6px;">1. Crear Comité</div>
                <div style="font-size:12px;color:var(--text-secondary);">Complete el formulario con el nombre y municipio del comité.</div>
            </div>
            <div class="col-md-4 mini-step">
                <div class="mini-step-icon" style="background:var(--success-bg);color:var(--success);"><i class="fas fa-user-tie"></i></div>
                <div style="font-size:13px;font-weight:600;margin-bottom:6px;">2. Coordinador</div>
                <div style="font-size:12px;color:var(--text-secondary);">Busque y asigne un coordinador usando su cédula.</div>
            </div>
            <div class="col-md-4 mini-step">
                <div class="mini-step-icon" style="background:#faf5ff;color:#7c3aed;"><i class="fas fa-users"></i></div>
                <div style="font-size:13px;font-weight:600;margin-bottom:6px;">3. Miembros</div>
                <div style="font-size:12px;color:var(--text-secondary);">Agregue miembros por cédula o manualmente.</div>
            </div>
        </div>
    </div>
  </div>

  <!-- GUIDE PANEL -->
  <div class="guide-panel">
    <div class="guide-title">Guía rápida</div>
    <div class="guide-copy">Completá los datos para registrar el comité. Podés agregar coordinador y miembros luego desde la vista de edición.</div>
    <div class="guide-step"><i class="fas fa-circle-notch"></i>Nombre y ubicación</div>
    <div class="guide-step"><i class="far fa-circle"></i>Coordinador asignado</div>
    <div class="guide-step"><i class="far fa-circle"></i>Miembros agregados</div>
  </div>
</div>

<script>
(function() {
    const provinciaInput = document.getElementById('provincia');
    const provinciaList  = document.getElementById('provinciaList');
    const municipioInput = document.getElementById('municipio');
    const municipioList  = document.getElementById('municipioList');
    let provincias = [];

    function escapeAttr(str) {
        return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function cargarMunicipios(provinciaId, valorPrevio) {
        municipioInput.disabled = true;
        municipioInput.placeholder = 'Cargando municipios...';
        municipioList.innerHTML = '';

        fetch(`api/municipios.php?provincia_id=${provinciaId}`)
            .then(r => r.json())
            .then(json => {
                if (!json.success) return;
                municipioList.innerHTML = json.data.map(m => `<option value="${escapeAttr(m.Descripcion)}"></option>`).join('');
                municipioInput.disabled = false;
                municipioInput.placeholder = 'Escriba para buscar...';
                municipioInput.value = valorPrevio || '';
            })
            .catch(err => {
                console.error('Error cargando municipios:', err);
                municipioInput.placeholder = 'Error al cargar municipios';
            });
    }

    provinciaInput.addEventListener('input', function() {
        const match = provincias.find(p => p.Descripcion === provinciaInput.value);
        if (match) {
            cargarMunicipios(match.ID);
        } else {
            municipioInput.disabled = true;
            municipioInput.value = '';
            municipioInput.placeholder = 'Seleccione primero una provincia';
            municipioList.innerHTML = '';
        }
    });

    fetch('api/provincias.php')
        .then(r => r.json())
        .then(json => {
            if (!json.success) return;
            provincias = json.data;
            provinciaList.innerHTML = provincias.map(p => `<option value="${escapeAttr(p.Descripcion)}"></option>`).join('');

            const match = provincias.find(p => p.Descripcion === provinciaInput.value);
            if (match) cargarMunicipios(match.ID, municipioInput.value);
        })
        .catch(err => console.error('Error cargando provincias:', err));
})();
</script>

<?php include 'includes/footer.php'; ?>
