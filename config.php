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

    if (!empty($_FILES['logo']['tmp_name'])) {
        $logo = file_get_contents($_FILES['logo']['tmp_name']);
        $s = $conn->prepare("UPDATE configuracion SET nombre_partido=?,siglas=?,color_primario=?,color_sidebar=?,color_accent=?,logo=? WHERE id=?");
        $null = null;
        $s->bind_param("sssssbi",$nombre,$siglas,$cp,$cs,$ca,$null,$cfg['id']);
        $s->send_long_data(5,$logo);
    } else {
        $s = $conn->prepare("UPDATE configuracion SET nombre_partido=?,siglas=?,color_primario=?,color_sidebar=?,color_accent=? WHERE id=?");
        $s->bind_param("sssssi",$nombre,$siglas,$cp,$cs,$ca,$cfg['id']);
    }
    $s->execute();
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

$titulo_pagina = 'Configuración';
include 'includes/header.php';
?>

<style>
.cargo-pill { font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;padding:2px 8px;border-radius:10px;background:#eff6ff;color:var(--accent); }
.cand-card { background:#fff;border:1px solid var(--border);border-radius:12px;padding:14px;display:flex;align-items:center;gap:12px; }
.cand-photo { width:52px;height:52px;border-radius:50%;object-fit:cover;flex-shrink:0;border:2px solid var(--border); }
.cand-placeholder { width:52px;height:52px;border-radius:50%;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;flex-shrink:0; }
.action-link { width:28px;height:28px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;border:1px solid var(--border);color:var(--text-secondary);background:none;cursor:pointer;transition:all .15s; }
.action-link:hover { border-color:var(--accent);color:var(--accent);background:#eff6ff; }
.action-link.danger:hover { border-color:var(--danger);color:var(--danger);background:#fef2f2; }
.upload-zone { border:2px dashed var(--border);border-radius:10px;padding:24px;text-align:center;cursor:pointer;background:#fafafa;transition:all .2s; }
.upload-zone:hover { border-color:var(--accent);background:#f0f7ff; }
.upload-zone input { display:none; }
</style>

<?php if (isset($_SESSION['mensaje'])): ?>
<div class="alert alert-<?php echo $_SESSION['tipo_mensaje']; ?> alert-dismissible fade show mb-4">
    <?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje'],$_SESSION['tipo_mensaje']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Identidad -->
    <div class="col-lg-5">
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-palette me-2 text-primary"></i>Identidad del Sistema</div>
            <div class="p-4">
                <form method="POST" enctype="multipart/form-data" id="formTema">
                    <div class="mb-3">
                        <label class="form-label">Nombre del Partido</label>
                        <input type="text" name="nombre_partido" class="form-control form-control-sm"
                               value="<?php echo htmlspecialchars($cfg['nombre_partido']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Siglas</label>
                        <input type="text" name="siglas" class="form-control form-control-sm" maxlength="10"
                               value="<?php echo htmlspecialchars($cfg['siglas']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Logo</label>
                        <div class="upload-zone" onclick="document.getElementById('logoInput').click()">
                            <input type="file" id="logoInput" name="logo" accept="image/*" onchange="onLogoChange(this)">
                            <i class="fas fa-cloud-upload-alt fa-2x mb-2" style="color:#94a3b8;"></i>
                            <div style="font-size:13px;color:#64748b;">Clic para subir el logo</div>
                            <div style="font-size:11px;color:#94a3b8;">Los colores se extraerán automáticamente</div>
                        </div>
                        <?php if ($logo_b64): ?>
                        <div style="margin-top:10px;text-align:center;">
                            <img id="logoPreviewImg" src="data:image/png;base64,<?php echo $logo_b64; ?>"
                                 style="max-height:80px;max-width:100%;object-fit:contain;border-radius:8px;border:1px solid var(--border);padding:8px;background:#f8fafc;">
                        </div>
                        <?php else: ?>
                        <div style="margin-top:10px;text-align:center;display:none;" id="logoPreviewBox">
                            <img id="logoPreviewImg" style="max-height:80px;max-width:100%;object-fit:contain;border-radius:8px;border:1px solid var(--border);padding:8px;background:#f8fafc;">
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Colores del tema</label>
                        <div class="row g-2">
                            <div class="col-4">
                                <div style="font-size:11px;color:#64748b;margin-bottom:4px;">Principal</div>
                                <input type="color" name="color_primario" id="cpPrimario" class="form-control form-control-color w-100 form-control-sm"
                                       value="<?php echo $cfg['color_primario']; ?>" oninput="updatePreview()">
                            </div>
                            <div class="col-4">
                                <div style="font-size:11px;color:#64748b;margin-bottom:4px;">Sidebar</div>
                                <input type="color" name="color_sidebar" id="cpSidebar" class="form-control form-control-color w-100 form-control-sm"
                                       value="<?php echo $cfg['color_sidebar']; ?>" oninput="updatePreview()">
                            </div>
                            <div class="col-4">
                                <div style="font-size:11px;color:#64748b;margin-bottom:4px;">Acento</div>
                                <input type="color" name="color_accent" id="cpAccent" class="form-control form-control-color w-100 form-control-sm"
                                       value="<?php echo $cfg['color_accent']; ?>" oninput="updatePreview()">
                            </div>
                        </div>
                        <div class="mt-2 d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setPreset('#2563eb','#0d1b2a','#3b82f6')">
                                <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#2563eb;margin-right:4px;"></span>Azul PRM
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setPreset('#7c3aed','#1e1035','#8b5cf6')">
                                <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#7c3aed;margin-right:4px;"></span>Morado PLD
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setPreset('#16a34a','#0a1f12','#22c55e')">
                                <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#16a34a;margin-right:4px;"></span>Verde FDP
                            </button>
                        </div>
                    </div>
                    <button type="submit" name="guardar_tema" class="btn btn-primary w-100 btn-sm">
                        <i class="fas fa-save me-1"></i> Guardar Configuración
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Preview -->
    <div class="col-lg-7">
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-eye me-2 text-primary"></i>Vista Previa</div>
            <div class="p-4">
                <div style="border-radius:12px;overflow:hidden;border:1px solid var(--border);box-shadow:0 4px 16px rgba(0,0,0,.08);">
                    <div style="display:flex;">
                        <div id="previewSidebar" style="width:130px;flex-shrink:0;background:#0d1b2a;padding:14px;display:flex;flex-direction:column;gap:6px;">
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                                <div id="previewLogo" style="width:32px;height:32px;border-radius:7px;background:rgba(255,255,255,.12);display:flex;align-items:center;justify-content:center;color:#fff;font-size:14px;overflow:hidden;">
                                    <?php if ($logo_b64): ?><img src="data:image/png;base64,<?php echo $logo_b64; ?>" style="width:100%;height:100%;object-fit:contain;"><?php else: ?><i class="fas fa-star"></i><?php endif; ?>
                                </div>
                                <div style="color:#fff;font-size:9px;font-weight:700;line-height:1.2;"><?php echo htmlspecialchars($cfg['siglas']); ?></div>
                            </div>
                            <div id="previewNavActive" style="padding:7px 9px;border-radius:6px;color:#fff;font-size:11px;display:flex;align-items:center;gap:6px;background:#2563eb;">
                                <i class="fas fa-chart-pie" style="font-size:10px;"></i>Dashboard
                            </div>
                            <div style="padding:7px 9px;border-radius:6px;color:rgba(255,255,255,.5);font-size:11px;display:flex;align-items:center;gap:6px;">
                                <i class="fas fa-layer-group" style="font-size:10px;"></i>Comités
                            </div>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="background:#fff;padding:10px 14px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
                                <span style="font-size:12px;font-weight:600;">Dashboard</span>
                                <button id="previewBtn" style="background:#2563eb;color:#fff;border:none;border-radius:6px;padding:4px 10px;font-size:10px;">+ Comité</button>
                            </div>
                            <div style="background:#f1f5f9;padding:12px;">
                                <div id="previewCard" style="background:#fff;border-radius:8px;padding:10px;font-size:10px;color:#64748b;border-left:3px solid #3b82f6;">
                                    <div style="font-weight:600;color:#0f172a;margin-bottom:2px;">Total Comités</div>
                                    <div style="font-size:18px;font-weight:700;color:#0f172a;">12</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Candidatos -->
<div class="card" id="candidatos">
    <div class="card-header">
        <span><i class="fas fa-users me-2 text-primary"></i>Candidatos
            <span style="font-size:12px;color:var(--text-secondary);font-weight:400;"> — <?php echo count($candidatos); ?> registrados</span>
        </span>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCandidato" onclick="resetCandModal()">
            <i class="fas fa-plus me-1"></i> Agregar
        </button>
    </div>
    <div class="p-4">
        <?php if (empty($candidatos)): ?>
        <div style="text-align:center;padding:40px;color:#94a3b8;">
            <i class="fas fa-user-slash" style="font-size:36px;opacity:.3;display:block;margin-bottom:12px;"></i>
            <p style="font-size:13px;">No hay candidatos. Agrega el primero.</p>
        </div>
        <?php else: ?>
        <div class="row g-3">
            <?php foreach ($candidatos as $c): ?>
            <div class="col-lg-6 col-xl-4">
                <div class="cand-card">
                    <?php if (!empty($c['foto'])): ?>
                    <img src="data:image/jpeg;base64,<?php echo base64_encode($c['foto']); ?>" class="cand-photo">
                    <?php else: ?>
                    <div class="cand-placeholder"><?php echo strtoupper(substr($c['nombre'],0,1)); ?></div>
                    <?php endif; ?>
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:600;font-size:13px;"><?php echo htmlspecialchars($c['nombre']); ?></div>
                        <span class="cargo-pill"><?php echo ucfirst($c['cargo']); ?></span>
                        <?php if (!empty($c['descripcion'])): ?>
                        <div style="font-size:11px;color:var(--text-secondary);margin-top:3px;">
                            <i class="fas fa-map-pin me-1"></i><?php echo htmlspecialchars($c['descripcion']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex flex-column gap-1">
                        <button class="action-link" onclick="editarCandidato(<?php echo $c['id']; ?>,'<?php echo htmlspecialchars(addslashes($c['nombre'])); ?>','<?php echo $c['cargo']; ?>','<?php echo htmlspecialchars(addslashes($c['descripcion']??'')); ?>')">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button class="action-link danger" onclick="eliminarCandidato(<?php echo $c['id']; ?>,'<?php echo htmlspecialchars(addslashes($c['nombre'])); ?>')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Candidato -->
<div class="modal fade" id="modalCandidato" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:var(--radius);">
            <div class="modal-header" style="border-bottom:1px solid var(--border);">
                <h6 class="modal-title fw-semibold" id="modalCandTitle">Nuevo Candidato</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="cand_id" id="candId">
                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px;font-weight:500;">Nombre Completo</label>
                        <input type="text" name="cand_nombre" id="candNombre" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px;font-weight:500;">Cargo</label>
                        <select name="cand_cargo" id="candCargo" class="form-select form-select-sm" required>
                            <option value="">Seleccionar cargo...</option>
                            <option value="presidente">Presidente</option>
                            <option value="senador">Senador</option>
                            <option value="diputado">Diputado</option>
                            <option value="alcalde">Alcalde</option>
                            <option value="regidor">Regidor</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px;font-weight:500;">Zona / Descripción <span style="color:var(--text-secondary);font-weight:400;">(opcional)</span></label>
                        <input type="text" name="cand_desc" id="candDesc" class="form-control form-control-sm"
                               placeholder="Ej: Zona Norte, Circunscripción 1...">
                        <div class="form-text">Útil cuando hay varios candidatos del mismo cargo.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px;font-weight:500;">Foto</label>
                        <input type="file" name="cand_foto" class="form-control form-control-sm" accept="image/*" onchange="previewCandFoto(this)">
                        <div id="candFotoPreview" style="display:none;margin-top:10px;text-align:center;">
                            <img id="candFotoImg" style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:2px solid var(--border);">
                        </div>
                    </div>
                    <button type="submit" name="guardar_candidato" class="btn btn-primary w-100 btn-sm">
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
    const cs = document.getElementById('cpSidebar').value;
    const ca = document.getElementById('cpAccent').value;
    document.getElementById('previewSidebar').style.background   = cs;
    document.getElementById('previewNavActive').style.background = cp;
    document.getElementById('previewBtn').style.background       = cp;
    document.getElementById('previewCard').style.borderLeftColor = ca;
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
        if (img) { img.src = src; img.closest('[style*="display:none"]') && (img.closest('[id]').style.display='block'); }
        document.getElementById('previewLogo').innerHTML = `<img src="${src}" style="width:100%;height:100%;object-fit:contain;">`;
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
