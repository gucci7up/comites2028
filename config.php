<?php
require_once 'includes/auth.php';
require_once 'db/config.php';

requiereAdmin();
$conn = conectarDB();

// Cargar config actual
$cfg = $conn->query("SELECT * FROM configuracion LIMIT 1")->fetch_assoc();
if (!$cfg) {
    $conn->query("INSERT INTO configuracion (nombre_partido,siglas) VALUES ('Mi Partido','MP')");
    $cfg = $conn->query("SELECT * FROM configuracion LIMIT 1")->fetch_assoc();
}

// ── Guardar tema ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_tema'])) {
    $nombre = trim($_POST['nombre_partido'] ?? '');
    $siglas = strtoupper(trim($_POST['siglas'] ?? ''));
    $cp = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['color_primario'] ?? '') ? $_POST['color_primario'] : '#2563eb';
    $cs = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['color_sidebar']  ?? '') ? $_POST['color_sidebar']  : '#0d1b2a';
    $ca = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['color_accent']   ?? '') ? $_POST['color_accent']   : '#3b82f6';

    $s = $conn->prepare("UPDATE configuracion SET nombre_partido=?,siglas=?,color_primario=?,color_sidebar=?,color_accent=? WHERE id=?");
    $s->bind_param("sssssi",$nombre,$siglas,$cp,$cs,$ca,$cfg['id']);
    $s->execute();

    if (!empty($_FILES['logo']['tmp_name'])) {
        $logo = file_get_contents($_FILES['logo']['tmp_name']);
        $s = $conn->prepare("UPDATE configuracion SET logo=? WHERE id=?");
        $null = null;
        $s->bind_param("bi",$null,$cfg['id']);
        $s->send_long_data(0,$logo);
        $s->execute();
    }

    $_SESSION['mensaje'] = "Configuración guardada.";
    $_SESSION['tipo_mensaje'] = "success";
    header('Location: config.php'); exit;
}

// ── Guardar candidato ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_candidato'])) {
    $nombre = trim($_POST['cand_nombre']);
    $cargo  = $_POST['cand_cargo'];
    $desc   = trim($_POST['cand_desc'] ?? '');
    $cid    = (int)($_POST['cand_id'] ?? 0);
    $cargos_validos = ['presidente','senador','diputado','alcalde','regidor'];
    if (!empty($nombre) && in_array($cargo, $cargos_validos)) {
        if ($cid > 0) {
            if (!empty($_FILES['cand_foto']['tmp_name'])) {
                $foto = file_get_contents($_FILES['cand_foto']['tmp_name']);
                $s = $conn->prepare("UPDATE candidatos SET nombre=?,cargo=?,descripcion=?,foto=? WHERE id=?");
                $null=null; $s->bind_param("sssbii",$nombre,$cargo,$desc,$null,$cid);
                $s->send_long_data(3,$foto);
            } else {
                $s = $conn->prepare("UPDATE candidatos SET nombre=?,cargo=?,descripcion=? WHERE id=?");
                $s->bind_param("sssi",$nombre,$cargo,$desc,$cid);
            }
        } else {
            if (!empty($_FILES['cand_foto']['tmp_name'])) {
                $foto = file_get_contents($_FILES['cand_foto']['tmp_name']);
                $s = $conn->prepare("INSERT INTO candidatos (nombre,cargo,descripcion,foto) VALUES (?,?,?,?)");
                $null=null; $s->bind_param("sssb",$nombre,$cargo,$desc,$null);
                $s->send_long_data(3,$foto);
            } else {
                $s = $conn->prepare("INSERT INTO candidatos (nombre,cargo,descripcion) VALUES (?,?,?)");
                $s->bind_param("sss",$nombre,$cargo,$desc);
            }
        }
        $s->execute();
        $_SESSION['mensaje'] = "Candidato guardado.";
        $_SESSION['tipo_mensaje'] = "success";
    }
    header('Location: config.php#candidatos'); exit;
}

// ── Eliminar candidato ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_candidato'])) {
    $cid = (int)$_POST['cand_id'];
    $conn->query("DELETE FROM candidatos WHERE id=$cid");
    $_SESSION['mensaje'] = "Candidato eliminado.";
    $_SESSION['tipo_mensaje'] = "success";
    header('Location: config.php#candidatos'); exit;
}

$candidatos = $conn->query("SELECT * FROM candidatos ORDER BY FIELD(cargo,'presidente','senador','diputado','alcalde','regidor'), nombre")->fetch_all(MYSQLI_ASSOC);
$logo_b64 = !empty($cfg['logo']) ? base64_encode($cfg['logo']) : '';
$cargos = ['presidente'=>'Presidente','senador'=>'Senador','diputado'=>'Diputado','alcalde'=>'Alcalde','regidor'=>'Regidor'];

$titulo_pagina    = 'Configuración';
$subtitulo_pagina = 'Identidad del partido y candidatos';
include 'includes/header.php';
?>

<style>
.config-layout { display:grid; grid-template-columns:minmax(0,1fr) 340px; gap:22px; align-items:start; }
@media (max-width:1100px) { .config-layout { grid-template-columns:1fr; } }
.step-card { background:#fff; border-radius:var(--radius-card); padding:26px; box-shadow:var(--shadow-sm); margin-bottom:22px; }
.step-title { font-size:15px; font-weight:700; margin-bottom:18px; }
.identity-row { display:grid; grid-template-columns:110px 1fr; gap:20px; align-items:start; margin-bottom:20px; }
@media (max-width:500px) { .identity-row { grid-template-columns:1fr; } }
.identity-fields { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.logo-slot {
    width:110px; height:110px; border-radius:16px; background:var(--bg);
    border:2px dashed var(--border); display:flex; align-items:center; justify-content:center;
    cursor:pointer; overflow:hidden; position:relative; flex-shrink:0;
}
.logo-slot:hover { border-color:var(--accent); background:var(--accent-tint); }
.logo-slot img { width:100%; height:100%; object-fit:contain; padding:8px; }
.logo-slot .placeholder { font-size:11px; color:var(--text-tertiary); text-align:center; padding:8px; }
.color-row { display:flex; gap:14px; flex-wrap:wrap; }
.color-swatch { text-align:center; }
.color-swatch-label { font-size:11px; color:var(--text-tertiary); margin-bottom:6px; }
.color-swatch input[type=color] {
    width:44px; height:44px; border-radius:12px; border:2px solid #fff;
    box-shadow:0 0 0 2px var(--border); cursor:pointer; padding:0; background:none;
}
.preset-row { display:flex; gap:8px; flex-wrap:wrap; margin-top:16px; }
.preset-btn {
    display:inline-flex; align-items:center; gap:6px; font-size:11.5px; font-weight:600;
    padding:6px 12px; border-radius:10px; border:1px solid var(--border); background:#fff;
    color:var(--text-secondary); cursor:pointer;
}
.preset-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
.cand-card { background:var(--bg); border-radius:12px; padding:14px; display:flex; align-items:center; gap:12px; }
.cand-photo { width:52px;height:52px;border-radius:50%;object-fit:cover;flex-shrink:0;border:2px solid #fff; }
.cand-placeholder { width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent-light));color:#fff;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;flex-shrink:0; }
.cargo-pill { font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;padding:2px 8px;border-radius:10px;background:var(--accent-tint);color:var(--accent); }
.activo-pill { background:var(--success-bg); color:var(--success); font-size:11px; font-weight:700; padding:3px 10px; border-radius:20px; }
.preview-panel { background:linear-gradient(160deg,var(--accent),var(--accent-700)); border-radius:var(--radius-card); padding:26px; color:#fff; }
.preview-title { font-size:14px; font-weight:700; margin-bottom:16px; }
.preview-box { background:rgba(255,255,255,.12); border-radius:16px; padding:18px; display:flex; align-items:center; gap:12px; margin-bottom:14px; }
.preview-logo { width:34px; height:34px; border-radius:10px; background:#fff; color:var(--accent); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:13px; overflow:hidden; flex-shrink:0; }
.preview-logo img { width:100%; height:100%; object-fit:contain; }
.preview-name { font-size:13px; font-weight:700; }
.preview-copy { font-size:12.5px; color:rgba(255,255,255,.85); line-height:1.6; }
.preview-save { display:block; width:100%; text-align:center; margin-top:20px; background:#fff; color:var(--accent-700); font-size:13px; font-weight:700; padding:11px; border-radius:12px; border:none; cursor:pointer; }
</style>

<?php if (isset($_SESSION['mensaje'])): ?>
<div class="alert alert-<?php echo $_SESSION['tipo_mensaje']; ?> alert-dismissible fade show mb-4">
    <?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje'],$_SESSION['tipo_mensaje']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="config-layout">
  <div style="min-width:0;">

    <!-- Identidad -->
    <div class="step-card">
        <div class="step-title">Identidad del partido</div>
        <form method="POST" enctype="multipart/form-data" id="formTema">
            <div class="identity-row">
                <label class="logo-slot" for="logoInput" id="logoSlot">
                    <?php if ($logo_b64): ?>
                    <img id="logoPreviewImg" src="data:image/png;base64,<?php echo $logo_b64; ?>">
                    <?php else: ?>
                    <span class="placeholder" id="logoPlaceholder"><i class="fas fa-cloud-upload-alt d-block mb-1"></i>Logo</span>
                    <img id="logoPreviewImg" style="display:none;">
                    <?php endif; ?>
                </label>
                <input type="file" id="logoInput" name="logo" accept="image/*" style="display:none;" onchange="onLogoChange(this)">
                <div class="identity-fields">
                    <div>
                        <label class="lbl">Nombre del partido</label>
                        <input type="text" name="nombre_partido" class="fld" id="inputNombrePartido"
                               value="<?php echo htmlspecialchars($cfg['nombre_partido']); ?>" required>
                    </div>
                    <div>
                        <label class="lbl">Siglas</label>
                        <input type="text" name="siglas" class="fld" id="inputSiglas" maxlength="10"
                               value="<?php echo htmlspecialchars($cfg['siglas']); ?>">
                    </div>
                </div>
            </div>

            <label class="lbl">Color principal del partido</label>
            <div class="color-row">
                <div class="color-swatch">
                    <div class="color-swatch-label">Principal</div>
                    <input type="color" name="color_primario" id="cpPrimario"
                           value="<?php echo $cfg['color_primario']; ?>" oninput="updatePreview()">
                </div>
                <div class="color-swatch">
                    <div class="color-swatch-label">Sidebar</div>
                    <input type="color" name="color_sidebar" id="cpSidebar"
                           value="<?php echo $cfg['color_sidebar']; ?>" oninput="updatePreview()">
                </div>
                <div class="color-swatch">
                    <div class="color-swatch-label">Acento</div>
                    <input type="color" name="color_accent" id="cpAccent"
                           value="<?php echo $cfg['color_accent']; ?>" oninput="updatePreview()">
                </div>
            </div>
            <div class="preset-row">
                <button type="button" class="preset-btn" onclick="setPreset('#2563eb','#0d1b2a','#3b82f6')">
                    <span class="preset-dot" style="background:#2563eb;"></span>Azul PRM
                </button>
                <button type="button" class="preset-btn" onclick="setPreset('#7c3aed','#1e1035','#8b5cf6')">
                    <span class="preset-dot" style="background:#7c3aed;"></span>Morado PLD
                </button>
                <button type="button" class="preset-btn" onclick="setPreset('#16a34a','#0a1f12','#22c55e')">
                    <span class="preset-dot" style="background:#16a34a;"></span>Verde FDP
                </button>
            </div>
        </form>
    </div>

    <!-- Candidatos -->
    <div class="step-card" id="candidatos" style="margin-bottom:0;">
        <div class="d-flex align-items-center justify-content-between" style="margin-bottom:18px;">
            <div class="step-title" style="margin-bottom:0;">Candidatos <span style="font-size:12px;color:var(--text-tertiary);font-weight:400;">— <?php echo count($candidatos); ?> registrados</span></div>
            <button class="btn btn-sm" style="background:var(--accent-tint);color:var(--accent);" data-bs-toggle="modal" data-bs-target="#modalCandidato" onclick="resetCandModal()">
                <i class="fas fa-plus me-1"></i> Agregar
            </button>
        </div>
        <?php if (empty($candidatos)): ?>
        <div style="text-align:center;padding:40px;color:var(--text-tertiary);">
            <i class="fas fa-user-slash" style="font-size:36px;opacity:.3;display:block;margin-bottom:12px;"></i>
            <p style="font-size:13px;">No hay candidatos. Agrega el primero.</p>
        </div>
        <?php else: ?>
        <div class="row g-3">
            <?php foreach ($candidatos as $c): ?>
            <div class="col-lg-6">
                <div class="cand-card">
                    <?php if (!empty($c['foto'])): ?>
                    <img src="data:image/jpeg;base64,<?php echo base64_encode($c['foto']); ?>" class="cand-photo">
                    <?php else: ?>
                    <div class="cand-placeholder"><?php echo strtoupper(substr($c['nombre'],0,1)); ?></div>
                    <?php endif; ?>
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:600;font-size:13.5px;"><?php echo htmlspecialchars($c['nombre']); ?></div>
                        <span class="cargo-pill"><?php echo ucfirst($c['cargo']); ?></span>
                        <?php if (!empty($c['descripcion'])): ?>
                        <div style="font-size:11.5px;color:var(--text-tertiary);margin-top:3px;">
                            <i class="fas fa-map-pin me-1"></i><?php echo htmlspecialchars($c['descripcion']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <span class="activo-pill"><?php echo $c['activo'] ? 'Activo' : 'Inactivo'; ?></span>
                    <button class="action-link" onclick="editarCandidato(<?php echo $c['id']; ?>,'<?php echo htmlspecialchars(addslashes($c['nombre'])); ?>','<?php echo $c['cargo']; ?>','<?php echo htmlspecialchars(addslashes($c['descripcion']??'')); ?>')">
                        <i class="fas fa-pen"></i>
                    </button>
                    <button class="action-link danger" onclick="eliminarCandidato(<?php echo $c['id']; ?>,'<?php echo htmlspecialchars(addslashes($c['nombre'])); ?>')">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
  </div>

  <!-- Preview -->
  <div class="preview-panel">
    <div class="preview-title">Vista previa</div>
    <div class="preview-box">
        <div class="preview-logo" id="previewLogo">
            <?php if ($logo_b64): ?><img src="data:image/png;base64,<?php echo $logo_b64; ?>"><?php else: ?><span id="previewLogoTxt"><?php echo htmlspecialchars(mb_substr($cfg['siglas'] ?: $cfg['nombre_partido'], 0, 1)); ?></span><?php endif; ?>
        </div>
        <div class="preview-name" id="previewName"><?php echo htmlspecialchars($cfg['nombre_partido']); ?></div>
    </div>
    <div class="preview-copy">Los cambios de logo y color se reflejan automáticamente en todo el sistema — sidebar, botones y gráficos.</div>
    <button type="submit" name="guardar_tema" form="formTema" class="preview-save">Guardar cambios</button>
  </div>
</div>

<!-- Modal Candidato -->
<div class="modal fade" id="modalCandidato" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold" id="modalCandTitle">Nuevo Candidato</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="cand_id" id="candId">
                    <div class="mb-3">
                        <label class="lbl">Nombre Completo</label>
                        <input type="text" name="cand_nombre" id="candNombre" class="fld" required>
                    </div>
                    <div class="mb-3">
                        <label class="lbl">Cargo</label>
                        <select name="cand_cargo" id="candCargo" class="fld" required>
                            <option value="">Seleccionar cargo...</option>
                            <option value="presidente">Presidente</option>
                            <option value="senador">Senador</option>
                            <option value="diputado">Diputado</option>
                            <option value="alcalde">Alcalde</option>
                            <option value="regidor">Regidor</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="lbl">Zona / Descripción <span style="color:var(--text-tertiary);font-weight:400;">(opcional)</span></label>
                        <input type="text" name="cand_desc" id="candDesc" class="fld"
                               placeholder="Ej: Zona Norte, Circunscripción 1...">
                        <div class="form-text mt-1">Útil cuando hay varios candidatos del mismo cargo.</div>
                    </div>
                    <div class="mb-3">
                        <label class="lbl">Foto</label>
                        <input type="file" name="cand_foto" class="fld" accept="image/*" onchange="previewCandFoto(this)">
                        <div id="candFotoPreview" style="display:none;margin-top:10px;text-align:center;">
                            <img id="candFotoImg" style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:2px solid var(--border);">
                        </div>
                    </div>
                    <button type="submit" name="guardar_candidato" class="btn btn-primary w-100">
                        <i class="fas fa-save me-1"></i> Guardar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<form method="POST" id="formEliminarCand" style="display:none;">
    <input type="hidden" name="cand_id" id="elimCandId">
    <input type="hidden" name="eliminar_candidato" value="1">
</form>

<script src="https://cdnjs.cloudflare.com/ajax/libs/color-thief/2.3.0/color-thief.umd.js"></script>
<script>
function updatePreview() {
    const cp = document.getElementById('cpPrimario').value;
    document.getElementById('previewName').textContent = document.getElementById('inputNombrePartido').value || 'Mi Partido';
    const logoTxt = document.getElementById('previewLogoTxt');
    if (logoTxt) {
        document.getElementById('previewLogo').style.color = cp;
    }
}
updatePreview();

function setPreset(cp,cs,ca) {
    document.getElementById('cpPrimario').value = cp;
    document.getElementById('cpSidebar').value  = cs;
    document.getElementById('cpAccent').value   = ca;
    updatePreview();
}

function onLogoChange(input) {
    if (!input.files[0]) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        const src = e.target.result;
        const img = document.getElementById('logoPreviewImg');
        const placeholder = document.getElementById('logoPlaceholder');
        img.src = src;
        img.style.display = 'block';
        if (placeholder) placeholder.style.display = 'none';
        document.getElementById('previewLogo').innerHTML = `<img src="${src}">`;
        const tmp = new Image();
        tmp.crossOrigin = 'Anonymous';
        tmp.onload = function() {
            try {
                const ct = new ColorThief();
                const d  = ct.getColor(tmp);
                const p  = ct.getPalette(tmp,3);
                const hex = ([r,g,b]) => '#'+[r,g,b].map(v=>v.toString(16).padStart(2,'0')).join('');
                const dark = ([r,g,b],f) => [Math.round(r*f),Math.round(g*f),Math.round(b*f)];
                document.getElementById('cpPrimario').value = hex(d);
                document.getElementById('cpSidebar').value  = hex(dark(d,0.25));
                document.getElementById('cpAccent').value   = hex(p[1]||d);
                updatePreview();
            } catch(e) {}
        };
        tmp.src = src;
    };
    reader.readAsDataURL(input.files[0]);
}

function resetCandModal() {
    document.getElementById('modalCandTitle').textContent = 'Nuevo Candidato';
    ['candId','candNombre','candDesc'].forEach(id => document.getElementById(id).value='');
    document.getElementById('candCargo').value = '';
    document.getElementById('candFotoPreview').style.display = 'none';
}
function editarCandidato(id,nombre,cargo,desc) {
    document.getElementById('modalCandTitle').textContent = 'Editar Candidato';
    document.getElementById('candId').value    = id;
    document.getElementById('candNombre').value = nombre;
    document.getElementById('candCargo').value  = cargo;
    document.getElementById('candDesc').value   = desc||'';
    new bootstrap.Modal(document.getElementById('modalCandidato')).show();
}
function eliminarCandidato(id,nombre) {
    if (confirm(`¿Eliminar a ${nombre}?`)) {
        document.getElementById('elimCandId').value = id;
        document.getElementById('formEliminarCand').submit();
    }
}
function previewCandFoto(input) {
    if (!input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('candFotoImg').src = e.target.result;
        document.getElementById('candFotoPreview').style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
}
</script>

<?php include 'includes/footer.php'; ?>
