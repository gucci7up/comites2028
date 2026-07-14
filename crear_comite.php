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

$titulo_pagina = 'Crear Comité';
include 'includes/header.php';
?>

<style>
.page-back { display:inline-flex;align-items:center;gap:8px;color:var(--text-secondary);font-size:13px;text-decoration:none;margin-bottom:20px;transition:color .15s; }
.page-back:hover { color:var(--accent); }
.step-card { background:#f8fafc;border:1px solid var(--border);border-radius:10px;padding:20px;text-align:center; }
.step-card { background:#fafafa;border:1px solid #eef0f6;border-radius:14px;padding:20px;text-align:center; }
.step-icon { width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:20px;margin:0 auto 12px; }
</style>

<a href="comites.php" class="page-back"><i class="fas fa-arrow-left"></i> Volver a Comités</a>

<?php if (!empty($errores)): ?>
<div class="alert alert-danger alert-dismissible fade show mb-4">
    <ul class="mb-0"><?php foreach ($errores as $e): ?><li><?php echo $e; ?></li><?php endforeach; ?></ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><i class="fas fa-plus-circle me-2 text-primary"></i>Nuevo Comité</div>
            <div class="p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:13px;">Nombre del Comité <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nombre" placeholder="Ej: Comité Afectivo Zona Norte"
                               value="<?php echo isset($nombre) ? htmlspecialchars($nombre) : ''; ?>" required>
                        <div class="form-text">Nombre descriptivo del comité.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:13px;">Provincia <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="provincia" name="provincia" list="provinciaList"
                               placeholder="Ej: Santo Domingo" autocomplete="off"
                               value="<?php echo isset($provincia) ? htmlspecialchars($provincia) : ''; ?>" required>
                        <datalist id="provinciaList"></datalist>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:13px;">Municipio <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="municipio" name="municipio" list="municipioList"
                               placeholder="Seleccione primero una provincia" autocomplete="off" disabled
                               value="<?php echo isset($municipio) ? htmlspecialchars($municipio) : ''; ?>" required>
                        <datalist id="municipioList"></datalist>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:13px;">Zona</label>
                        <input type="text" class="form-control" id="zona" name="zona"
                               placeholder="Ej: Zona Norte, Sector 4..."
                               value="<?php echo isset($zona) ? htmlspecialchars($zona) : ''; ?>">
                        <div class="form-text">Campo de llenado manual, definido por el partido.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold" style="font-size:13px;">Candidato</label>
                        <select class="form-select" name="candidato_id">
                            <option value="">Sin candidato asignado</option>
                            <?php foreach ($candidatos as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo (int)$candidato_seleccionado === (int)$c['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['nombre']); ?> — <?php echo $cargos[$c['cargo']] ?? ucfirst($c['cargo']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">A qué candidato pertenece este comité (opcional).</div>
                    </div>
                    <div class="alert alert-info d-flex gap-2 align-items-start" style="font-size:13px;">
                        <i class="fas fa-info-circle mt-1"></i>
                        <span>Después de crear el comité podrá asignar un coordinador y agregar miembros.</span>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save me-2"></i> Crear Comité
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><i class="fas fa-map-signs me-2 text-primary"></i>Cómo funciona</div>
            <div class="p-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="step-card">
                            <div class="step-icon" style="background:color-mix(in srgb,var(--accent) 10%,white);color:var(--accent);">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <div style="font-size:13px;font-weight:600;margin-bottom:6px;">1. Crear Comité</div>
                            <div style="font-size:12px;color:var(--text-secondary);">Complete el formulario con el nombre y municipio del comité.</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="step-card">
                            <div class="step-icon" style="background:#f0fdf4;color:#16a34a;">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div style="font-size:13px;font-weight:600;margin-bottom:6px;">2. Coordinador</div>
                            <div style="font-size:12px;color:var(--text-secondary);">Busque y asigne un coordinador usando su cédula.</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="step-card">
                            <div class="step-icon" style="background:#faf5ff;color:#7c3aed;">
                                <i class="fas fa-users"></i>
                            </div>
                            <div style="font-size:13px;font-weight:600;margin-bottom:6px;">3. Miembros</div>
                            <div style="font-size:12px;color:var(--text-secondary);">Agregue miembros por cédula o manualmente.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
